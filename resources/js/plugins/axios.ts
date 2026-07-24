import axios from 'axios';

const http = axios.create({
  baseURL: '/',
  withCredentials: true, // Crucial for Sanctum SPA Auth
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

http.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error.response?.status;

    // If the request opted out of automatic auth redirects, skip handling here
    const skipAuthRedirect = error.config?.headers?.['X-Skip-Auth-Redirect'];

    if (status === 401 && !skipAuthRedirect) {
      // Si aún no reintentamos, refrescamos el CSRF y reintentamos una vez
      if (!error.config?._retry) {
        error.config._retry = true;
        try {
          await http.get('/sanctum/csrf-cookie');
          return await http(error.config);
        } catch {
          // El reintento también falló → sesión realmente expirada
        }
      }
      // Redirige limpiamente sin mostrar mensajes de error
      window.location.href = '/login';
      return new Promise(() => {}); // catch blocks no se ejecutan
    }

    if (status === 419) {
      // CSRF expirado — recarga solo una vez
      if (!sessionStorage.getItem('csrf_reloading')) {
        sessionStorage.setItem('csrf_reloading', '1');
        window.location.reload();
        return new Promise(() => {});
      } else {
        sessionStorage.removeItem('csrf_reloading');
      }
    }

    return Promise.reject(error);
  }
);

export default http;
