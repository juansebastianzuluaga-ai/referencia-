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
  (error) => {
    const status = error.response?.status;

    // If the request opted out of automatic auth redirects, skip handling here
    const skipAuthRedirect = error.config?.headers?.['X-Skip-Auth-Redirect'];

    if (status === 401 && !skipAuthRedirect) {
      // Session expired or unauthenticated
      window.location.href = '/login';
    }

    if (status === 419) {
      // CSRF token expired, reload the page
      window.location.reload();
    }

    return Promise.reject(error);
  }
);

export default http;
