# Plantilla Laravel - Clínica Santa Bárbara

Plantilla base para aplicaciones web de la Clínica Santa Bárbara, construida con Laravel 13 y Vue 3. Esta plantilla incluye todos los módulos y configuraciones necesarias para arrancar nuevos proyectos rápidamente sin perder tiempo en configuraciones repetitivas.

## 🚀 Stack Tecnológico

### Backend
- **Laravel 13** - Framework PHP
- **PHP 8.3** - Versión mínima requerida
- **Laravel Fortify** - Autenticación (login, 2FA, reset password)
- **Laravel Sanctum** - API tokens y autenticación SPA
- **Spatie Laravel Permission** - Sistema de roles y permisos
- **OwenIt Laravel Auditing** - Auditoría de cambios
- **SQLite** - Base de datos por defecto (configurable a MySQL/PostgreSQL)

### Frontend
- **Vue 3** - Framework JavaScript con Composition API
- **TypeScript** - Tipado estático
- **Vite** - Build tool rápido
- **Element Plus** - Biblioteca de componentes UI
- **Tailwind CSS 4** - Framework CSS utility-first
- **Pinia** - State management
- **Vue Router 5** - Enrutamiento
- **Axios** - Cliente HTTP
- **Lucide Icons** - Iconos modernos

## 📋 Requisitos Previos

- PHP >= 8.3
- Composer
- Node.js >= 18
- pnpm (recomendado) o npm

## 🛠️ Instalación

### 1. Clonar el repositorio
```bash
git clone <repositorio-plantilla>
cd template-cacsb
```

### 2. Instalar dependencias
```bash
composer install
pnpm install
```

### 3. Configurar entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar base de datos
El `.env` viene configurado con SQLite por defecto. Para usar MySQL/PostgreSQL, modifica:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_proyecto
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 5. Ejecutar migraciones y seeders
```bash
php artisan migrate --seed
```

Esto creará:
- Usuario super-admin: `admin` / `password`
- Roles básicos (super-admin, admin, user)
- Permisos del sistema
- Tipos de identificación
- Configuración inicial

### 6. Compilar assets
```bash
pnpm run build
```

### 7. Iniciar servidor de desarrollo
```bash
# Opción 1: Solo servidor Laravel
php artisan serve

# Opción 2: Servidor completo (Laravel + Queue + Logs + Vite)
composer run dev
```

## 📁 Estructura del Proyecto

```
├── app/
│   ├── Http/Controllers/Api/    # Controladores API
│   ├── Models/                   # Modelos Eloquent
│   ├── Actions/                  # Acciones reutilizables
│   └── Services/                 # Lógica de negocio
├── database/
│   ├── migrations/               # Migraciones de BD
│   ├── seeders/                  # Datos iniciales
│   └── factories/                # Factories para tests
├── resources/
│   └── js/
│       ├── views/                # Componentes Vue por módulo
│       ├── stores/               # Stores Pinia
│       ├── components/           # Componentes reutilizables
│       └── router/               # Configuración de rutas
├── routes/
│   └── web.php                   # Rutas API y web
└── config/                       # Configuraciones Laravel
```

## 🎯 Módulos Incluidos

### ✅ Autenticación y Seguridad
- Login con username/email
- Autenticación 2FA (Two-Factor Authentication)
- Reset de contraseña por email
- API Tokens (Sanctum)
- Middleware de usuario activo
- Política de contraseñas configurable

### ✅ Gestión de Usuarios
- CRUD completo de usuarios
- Información personal (identificación, nombres, email, cargo)
- Asignación de roles
- Estado activo/inactivo
- Avatar con iniciales

### ✅ Perfil de Usuario
- Edición de información personal
- Cambio de contraseña con política de seguridad
- Visualización de roles asignados

### ✅ Roles y Permisos
- CRUD de roles
- CRUD de permisos
- Asignación de permisos a roles
- Sistema de guardias de permisos en rutas Vue
- Verificación de permisos en backend

### ✅ Configuración del Sistema
- Configuración de políticas de contraseña
- Configuración general de la aplicación
- Settings almacenados en base de datos

### ✅ API Credentials
- Gestión de credenciales API
- Generación y regeneración de tokens
- Revocación de credenciales
- Logs de solicitudes API
- Estadísticas de uso

### ✅ Auditoría
- Registro de cambios en modelos
- Activity logs de usuarios
- Integración con OwenIt Auditing

### ✅ Layout y UI
- Layout responsive con sidebar
- Header con navegación
- Sistema de notificaciones
- Componentes reutilizables:
  - ContentCard
  - BaseModal
  - StatusPill
  - StatCard
  - PermissionGuard

## 🔐 Credenciales por Defecto

Después de ejecutar `php artisan migrate --seed`:

- **Usuario:** superadmin
- **Contraseña:** admin123
- **Rol:** super-admin (acceso total)

## 📖 Guía para Crear Nuevos Módulos

Consulta `MODULE_GUIDE.md` para instrucciones detalladas sobre cómo crear módulos CRUD completos siguiendo el patrón estándar del proyecto.

Resumen rápido:
1. Crear Model, Migration, Factory, Seeder
2. Crear Controller con trait `FiltersPaginatedResults`
3. Crear Form Requests (Store/Update)
4. Crear API Resource
5. Definir rutas en `routes/web.php`
6. Crear Pinia store
7. Crear vista Vue principal con tabla
8. Crear modal de formulario
9. Agregar ruta en Vue Router

## 🎨 Personalización para Nuevos Proyectos

Al copiar esta plantilla para un nuevo proyecto:

### 1. Modificar nombre de aplicación
```env
# .env
APP_NAME="Nombre del Proyecto"
VITE_APP_NAME="${APP_NAME}"
```

### 2. Actualizar README.md
- Cambiar nombre del proyecto
- Actualizar descripción
- Modificar logo si aplica

### 3. Personalizar branding
- Logo en `resources/js/components/layout/AppHeader.vue`
- Colores en Tailwind config
- Nombre en sidebar

### 4. Eliminar módulos no necesarios
- Eliminar vistas en `resources/js/views/`
- Eliminar stores en `resources/js/stores/`
- Eliminar rutas en `resources/js/router/index.ts`
- Eliminar controllers en `app/Http/Controllers/Api/`
- Eliminar rutas en `routes/web.php`

### 5. Limpiar datos de seeders
- Modificar `DatabaseSeeder.php`
- Ajustar seeders específicos del proyecto

## 🧪 Tests

Ejecutar tests:
```bash
php artisan test
```

Ejecutar tests específicos:
```bash
php artisan test --filter testNombre
```

## 📝 Scripts Disponibles

### Composer
```bash
composer run setup        # Instalación completa del proyecto
composer run dev          # Servidor completo (Laravel + Queue + Logs + Vite)
composer run test         # Ejecutar tests
```

### pnpm
```bash
pnpm run build            # Compilar assets para producción
pnpm run dev              # Servidor Vite en modo desarrollo
```

## 🔧 Configuraciones Importantes

### Autenticación
- Username configurado en `config/fortify.php`
- 2FA habilitado por defecto
- Login rate limiting activo

### Permisos
- Sistema basado en Spatie Laravel Permission
- Permisos en formato: `modulo.accion` (ej: `users.view`)
- Guardia de permisos en rutas Vue Router

### Base de Datos
- SQLite por defecto para desarrollo rápido
- Soft deletes habilitados en modelos principales
- Migraciones con comentarios descriptivos

### Logging
- Logs en `storage/logs`
- Integración con Laravel Pail para logs en tiempo real
- Activity logs para auditoría

## 🚨 Seguridad

- Todas las rutas API requieren autenticación
- Middleware `active` verifica usuario activo
- CSRF protection habilitado
- Password hashing con Bcrypt
- API tokens con Sanctum
- Rate limiting en login

## 📚 Documentación Adicional

- `MODULE_GUIDE.md` - Guía completa para crear módulos
- `docs/backend-module-guide.md` - Guía de backend
- `docs/frontend-module-guide.md` - Guía de frontend
- `docs/frontend-app-architecture.md` - Arquitectura frontend
- `docs/frontend-permission-system.md` - Sistema de permisos frontend
- `docs/frontend-vue3-auth.md` - Autenticación Vue 3

## 🤝 Contribución

Esta es una plantilla interna para la Clínica Santa Bárbara. Para sugerencias o mejoras, contactar al equipo de desarrollo.

## 📄 Licencia

Propiedad de Clínica Santa Bárbara. Uso interno exclusivo.

---

**Versión:** 1.0.0  
**Última actualización:** Julio 2026