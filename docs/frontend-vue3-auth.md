# Frontend Vue 3 — Autenticación SPA con Laravel Sanctum

Este documento explica cómo funciona la autenticación entre el frontend Vue 3 y el backend Laravel en este proyecto, y cómo debe consumirse la API desde Vue.

---

## Arquitectura: mismo dominio

El frontend Vue 3 vive **dentro del mismo proyecto Laravel**. Vite compila los assets y Laravel sirve un único `index.html` que arranca la SPA. El dominio es compartido:

```
https://miapp.com/          → Laravel sirve el index.html de Vue (SPA)
https://miapp.com/api/...   → Rutas de la API Laravel
https://miapp.com/login     → Fortify maneja el login
```

Esto significa que el navegador y el servidor comparten el mismo origen, lo cual habilita el uso de **cookies de sesión HttpOnly** para autenticación — el mecanismo más seguro disponible para SPAs.

---

## Por qué las rutas API están en `web.php`

Las rutas de la API se declaran dentro de `routes/web.php` (no en `api.php`) porque el stack de middleware `web` es el que activa:

- **Sesiones** (`StartSession`)
- **Cookies** (`EncryptCookies`, `AddQueuedCookiesToResponse`)
- **CSRF** (`VerifyCsrfToken`)

Sanctum en modo SPA necesita estos tres elementos para funcionar. Si las rutas estuvieran en `api.php` (middleware `api`), las cookies de sesión no existirían y la autenticación fallaría.

```php
// routes/web.php
Route::prefix('api')->name('api.')->group(function (): void {
    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        // Todas las rutas protegidas aquí
    });
});
```

---

## Flujo de autenticación paso a paso

### 1. El usuario abre la app por primera vez

Vue arranca. Antes de cualquier petición autenticada, se debe obtener la cookie CSRF que Sanctum require para todas las mutaciones (`POST`, `PUT`, `DELETE`).

```js
// Llamada inicial — solo una vez al arrancar la app
await axios.get('/sanctum/csrf-cookie')
```

Esto establece dos cookies en el navegador:
- `XSRF-TOKEN` — valor CSRF que Axios lee automáticamente
- `laravel_session` — cookie de sesión (HttpOnly, no accesible por JS)

### 2. Login

```js
await axios.post('/login', {
  username: 'superadmin',  // este backend usa username, no email
  password: 'Password123!'
})
// Laravel responde con el UserResource + datos de sesión
// La cookie laravel_session queda guardada en el navegador
```

> **Importante:** El campo de usuario es `username`, no `email`. Ver `FortifyServiceProvider` donde se configura `Fortify::authenticateUsing()`.

### 3. Peticiones autenticadas

Una vez logueado, el navegador envía la cookie de sesión automáticamente en cada petición. No hay que hacer nada extra:

```js
const { data } = await axios.get('/api/user')
// data.data → objeto del usuario autenticado con sus roles y permisos
```

### 4. Logout

```js
await axios.post('/logout')
// La sesión se destruye en el servidor
// La cookie laravel_session queda invalidada
```

---

## Configuración de Axios en Vue 3

Crear un archivo `src/plugins/axios.js` (o `src/lib/http.js`) con la configuración base:

```js
import axios from 'axios'

const http = axios.create({
  baseURL: '/',            // mismo dominio, sin URL absoluta
  withCredentials: true,   // CRÍTICO: envía y recibe cookies en cada petición
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest', // identifica peticiones AJAX para Laravel
  },
})

export default http
```

> **`withCredentials: true` es obligatorio.** Sin esto, el navegador no adjunta las cookies de sesión a las peticiones y todas responderán `401 Unauthenticated`.

---

## Ejemplo completo: store de autenticación (Pinia)

```js
// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import http from '@/plugins/axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const isAuthenticated = computed(() => user.value !== null)

  /**
   * Obtiene la cookie CSRF antes del primer login.
   * Llamar una sola vez al arrancar la aplicación.
   */
  async function initCsrf() {
    await http.get('/sanctum/csrf-cookie')
  }

  /**
   * Inicia sesión con username y password.
   * Fortify gestiona la cookie de sesión automáticamente.
   */
  async function login(username, password) {
    await initCsrf()

    const { data } = await http.post('/login', { username, password })

    // La respuesta tiene el formato estándar:
    // { code, success, message, data: { ...userResource } }
    user.value = data.data

    return user.value
  }

  /**
   * Carga el usuario autenticado desde la API.
   * Útil para rehidratar el estado al recargar la página.
   */
  async function fetchUser() {
    try {
      const { data } = await http.get('/api/user')
      user.value = data.data
    } catch {
      user.value = null
    }
  }

  /**
   * Cierra la sesión y limpia el estado local.
   */
  async function logout() {
    await http.post('/logout')
    user.value = null
  }

  /**
   * Verifica si el usuario tiene un permiso específico.
   * El rol super-admin siempre retorna true (resuelto en el backend).
   *
   * @param {string} permission - Ejemplo: 'users.view'
   */
  function hasPermission(permission) {
    if (!user.value) {
      return false
    }

    return user.value.roles?.some(role =>
      role.permissions?.some(p => p.name === permission)
    ) ?? false
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    fetchUser,
    hasPermission,
  }
})
```

---

## Rehidratación al recargar la página

Cuando el usuario recarga el navegador, el store de Pinia se pierde (es en memoria), pero la cookie de sesión sigue válida. Hay que recuperar el usuario al arrancar:

```js
// src/main.js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from '@/stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Rehidratar usuario antes de montar la app
const authStore = useAuthStore()
authStore.fetchUser().finally(() => {
  app.mount('#app')
})
```

---

## Protección de rutas en Vue Router

```js
// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/usuarios',
    name: 'users',
    component: () => import('@/views/UsersView.vue'),
    meta: { requiresAuth: true, permission: 'users.view' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  // Ruta protegida y usuario no autenticado
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  // Ruta de invitado y usuario ya autenticado
  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // Verificación de permiso específico por ruta
  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return { name: 'dashboard' } // o una vista de "sin acceso"
  }
})

export default router
```

---

## Manejo de errores HTTP globales

Configurar un interceptor en Axios para manejar respuestas de error de forma centralizada:

```js
// src/plugins/axios.js
import axios from 'axios'
import router from '@/router'

const http = axios.create({
  baseURL: '/',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    if (status === 401) {
      // Sesión expirada — redirigir al login
      router.push({ name: 'login' })
    }

    if (status === 403) {
      // Sin permiso — mostrar notificación o redirigir
      console.warn('Sin permisos para esta acción')
    }

    if (status === 419) {
      // Token CSRF expirado — refrescar y reintentar
      // En la mayoría de los casos basta con recargar la página
      window.location.reload()
    }

    return Promise.reject(error)
  }
)

export default http
```

---

## Estructura del UserResource (respuesta de `/api/user`)

El objeto `data` que devuelve la API tiene esta forma, basada en `UserResource`:

```json
{
  "id": 1,
  "identification_type": {
    "id": 1,
    "code": "CC",
    "name": "Cédula de Ciudadanía",
    "is_active": true
  },
  "identification_number": "1000000000",
  "username": "superadmin",
  "first_name": "Super",
  "middle_name": null,
  "first_last_name": "Administrador",
  "second_last_name": null,
  "full_name": "Super Administrador",
  "email": "superadmin@example.com",
  "position": "Super administrador",
  "is_active": true,
  "must_change_password": true,
  "must_update_profile": true,
  "last_login_at": "2026-06-01T12:00:00.000000Z",
  "roles": [
    {
      "id": 1,
      "name": "super-admin",
      "display_name": "Super administrador",
      "description": "Acceso total",
      "is_active": true,
      "permissions": [
        { "id": 1, "name": "users.view", "display_name": "Ver usuarios" },
        { "id": 2, "name": "users.create", "display_name": "Crear usuarios" }
      ]
    }
  ],
  "created_at": "2026-06-01T12:00:00.000000Z",
  "updated_at": "2026-06-01T12:00:00.000000Z"
}
```

> **Nota:** `full_name` es un campo computado generado en `UserResource`, no existe como columna en la base de datos.

---

## Campos a tener en cuenta en la lógica del frontend

| Campo | Tipo | Acción recomendada en Vue |
|---|---|---|
| `must_change_password` | `boolean` | Redirigir a pantalla de cambio de contraseña antes de acceder al dashboard |
| `must_update_profile` | `boolean` | Redirigir a pantalla de perfil antes de acceder al dashboard |
| `is_active` | `boolean` | El backend ya bloquea usuarios inactivos con `403`, pero puede usarse para UI |
| `roles[].permissions` | `array` | Fuente de verdad para mostrar/ocultar elementos de UI según permisos |

### Ejemplo: redirección por flags del usuario

```js
// En el guard del router o tras el login:
if (auth.user?.must_change_password) {
  return { name: 'change-password' }
}

if (auth.user?.must_update_profile) {
  return { name: 'update-profile' }
}
```

---

## Resumen del flujo completo

```
1. App monta → fetchUser()
   ├─ 200 OK  → usuario rehidratado, ir al dashboard
   └─ 401     → usuario no autenticado, ir al login

2. Login
   ├─ initCsrf() → GET /sanctum/csrf-cookie
   ├─ POST /login { username, password }
   ├─ 200 OK  → guardar user en store, redirigir
   └─ 422     → mostrar errores de validación

3. Petición protegida (ejemplo: listar usuarios)
   ├─ POST /api/users/get-all { per_page: 10, page: 1 }
   ├─ 200 OK  → renderizar tabla
   ├─ 401     → sesión expirada, ir al login
   └─ 403     → sin permiso, mostrar mensaje

4. Logout
   ├─ POST /logout
   ├─ Limpiar user en store
   └─ Redirigir al login
```
