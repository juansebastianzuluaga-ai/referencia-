# Arquitectura Frontend Vue 3

Este documento resume el estado actual del frontend para que otra IA o
desarrollador pueda continuar el trabajo sin redescubrir la estructura.

## Objetivo

La aplicacion usa Laravel como backend y servidor de la SPA. Vue 3 vive dentro
del mismo proyecto Laravel y se compila con Vite. No es una aplicacion separada
ni consume una URL externa por defecto.

```
Laravel
  -> sirve resources/views/welcome.blade.php
  -> carga assets compilados por Vite
  -> expone endpoints JSON bajo /api

Vue 3 SPA
  -> vive en resources/js
  -> usa Vue Router para navegacion interna
  -> usa Pinia para estado global
  -> usa Axios con cookies de sesion Sanctum
```

## Stack frontend

- Vue 3 con Composition API y `<script setup lang="ts">`.
- TypeScript.
- Vite con `laravel-vite-plugin`.
- Tailwind 4 para utilidades CSS.
- Element Plus como libreria UI principal.
- Pinia para stores.
- Vue Router para navegacion.
- Axios para HTTP.
- Lucide para iconos.
- `unplugin-auto-import` y `unplugin-vue-components` para auto-imports.

## Archivos principales

```
resources/
  css/
    app.scss                         # Estilos globales de la plantilla
  js/
    app.ts                           # Punto de entrada de Vue
    App.vue                          # Componente raiz
    assets/
      element-variables.scss         # Variables Sass de Element Plus
    plugins/
      axios.ts                       # Cliente HTTP global
    router/
      index.ts                       # Rutas SPA y guards
    stores/
      auth.ts                        # Usuario, login, logout, permisos
      layout.ts                      # Sidebar, menu movil, estado visual
    components/
      layout/
        AppLayout.vue
        AppHeader.vue
        AppSidebar.vue
        AppSidebarItem.vue
        AppFooter.vue
      ui/
        ContentCard.vue
        StatCard.vue
        StatusPill.vue
    views/
      LoginView.vue
      DashboardView.vue
      UsersView.vue
      RolesView.vue
      censo/
        IngresosActivosView.vue
  views/
    welcome.blade.php                # Blade host de la SPA
```

## Configuracion Vite

El archivo `vite.config.ts` registra:

- Entrada Laravel: `resources/css/app.scss` y `resources/js/app.ts`.
- Plugin Vue.
- Plugin Tailwind.
- Auto-imports para Vue, Vue Router, Pinia, VueUse y Element Plus.
- Auto-registro de componentes en `resources/js/components`.
- Alias `@` apuntando a `resources/js`.
- Inyeccion global de `element-variables.scss` en Sass.

Cuando se ejecuta Vite por primera vez, pueden generarse:

- `resources/js/auto-imports.d.ts`
- `resources/js/components.d.ts`

Estos archivos son normales y sirven para TypeScript/autocompletado.

## Autenticacion

El proyecto esta pensado para Sanctum en modo SPA con cookies de sesion:

- Axios usa `baseURL: '/'`.
- Axios usa `withCredentials: true`.
- El login se hace con `POST /api/login`.
- Antes del login se obtiene CSRF con `GET /sanctum/csrf-cookie`.
- El usuario autenticado se obtiene con `GET /api/user`.
- El logout se hace con `POST /api/logout`.

El store `resources/js/stores/auth.ts` centraliza:

- `user`
- `isAuthenticated`
- `initCsrf()`
- `login(credentials)`
- `fetchUser()`
- `logout()`
- `hasPermission(permission)`

El campo de login esperado es `username`, no `email`.

## Rutas frontend actuales

Las rutas estan en `resources/js/router/index.ts`.

```
/login              -> LoginView
/                   -> AppLayout, requiere auth
/dashboard          -> DashboardView
/usuarios           -> UsersView
/roles              -> RolesView
/ingresos-activos   -> censo/IngresosActivosView
```

El guard actual intenta rehidratar la sesion con `auth.fetchUser()` cuando una
ruta requiere autenticacion y no hay usuario cargado en Pinia.

## Layout

Las rutas autenticadas renderizan dentro de `AppLayout.vue`.

```
AppLayout
  -> AppHeader
  -> AppSidebar
  -> router-view
  -> AppFooter
```

El sidebar se define en `AppSidebar.vue` mediante `AppSidebarItem.vue`. Para
agregar una entrada de menu hay que registrar un nuevo `AppSidebarItem` con:

- `to`: ruta frontend.
- `icon`: nombre del icono Lucide.
- `text`: etiqueta visible.
- `badge`: opcional.

### Comportamiento movil del sidebar

En movil el sidebar es `position: fixed` y se mueve con `translate-x`. La
visibilidad se controla desde `layout.ts` con `isMobileMenuOpen`.

**Regla importante:** el sidebar siempre debe tener un ancho definido (`w-[240px]`).
No usar `w-0` para ocultarlo porque eso rompe la transicion. La entrada y salida
se controla unicamente con `translate-x`:

```html
<!-- Correcto -->
:class="[
  layout.isMobileMenuOpen
    ? 'w-[240px] translate-x-0 shadow-2xl'
    : 'w-[240px] -translate-x-full md:translate-x-0',
  layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
]"
```

El componente `AppSidebarItem.vue` incluye `@click="layout.closeMobileMenu()"`
en el `router-link` para cerrar el menu automaticamente al navegar. Cualquier
nuevo componente de menu debe mantener este comportamiento.

El `AppLayout.vue` tiene un overlay semitransparente que cubre el contenido
cuando el menu movil esta abierto. Al hacer clic en el overlay se llama a
`layout.closeMobileMenu()`.

## UI Kit local

Antes de crear componentes nuevos, revisar:

- `ContentCard.vue`: contenedor estandar de vistas/modulos.
- `StatCard.vue`: tarjetas de metricas.
- `StatusPill.vue`: etiquetas de estado.

Las vistas de modulo deben preferir estos componentes para mantener consistencia
visual.

## Estado actual de los modulos

La estructura visual de los modulos ya existe:

- Dashboard.
- Usuarios.
- Roles y permisos.
- Censo / ingresos activos.

Importante: algunas vistas aun usan datos mock y tienen `TODO: Implementar API`.
Antes de considerar completo un modulo, hay que conectarlo al backend real,
validar permisos, manejar carga, errores, paginacion y formularios.

## Relacion con el backend

El backend documentado en `docs/backend-module-guide.md` expone endpoints bajo
`/api` dentro de `routes/web.php`, protegidos con:

```
auth:sanctum
active
```

El patron de listado backend es:

```
POST /api/{modulo}/get-all
```

El frontend debe enviar filtros, busqueda, paginacion y ordenamiento en el body
de esa peticion.

## Siguiente paso recomendado

La siguiente etapa natural es terminar el cableado de autenticacion y API real:

1. Confirmar rutas en `routes/web.php`.
2. Confirmar Fortify/Sanctum y respuesta de `POST /api/login`.
3. Confirmar `/api/user`.
4. Reemplazar datos mock de Usuarios, Roles y Censo por llamadas Axios.
5. Agregar permisos en rutas y menu.
