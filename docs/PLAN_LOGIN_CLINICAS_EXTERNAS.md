# Plan de Implementación: Login para Clínicas Externas

**Fecha de creación:** Julio 17, 2026  
**Estado:** Pendiente de aprobación  
**Versión:** 1.0.0

---

## 📋 Resumen Ejecutivo

Implementar un sistema de registro y autenticación para clínicas externas que deseen acceder a la plataforma, manteniendo el login interno actual (usuarios administrativos) completamente separado. El flujo incluye:

1. **Registro de clínica/sede** con validación de NIT
2. **Solicitud enviada** con notificación al staff administrativo
3. **Aprobación/rechazo** por staff o administrador
4. **Activación de cuenta** con notificación por correo y SMS
5. **Login de clínica externa** con verificación de cuenta activa
6. **Acceso diferenciado** según tipo de usuario (interno vs externo)

---

## 🎯 Objetivos

### Funcionales
- ✅ Permitir que clínicas externas se registren en el sistema
- ✅ Validar identidad de clínicas mediante NIT y documentación
- ✅ Flujo de aprobación con staff/administrador
- ✅ Notificaciones automáticas (email + SMS) en cada etapa
- ✅ Login separado para usuarios internos y clínicas externas
- ✅ Panel diferenciado según tipo de usuario

### No Funcionales
- ✅ Mantener compatibilidad total con el login interno actual
- ✅ Seguridad: validación de datos, rate limiting, auditoría completa
- ✅ UX: flujo claro con feedback visual en cada paso
- ✅ Escalabilidad: soporte para múltiples clínicas y sedes

---

## 🏗️ Arquitectura del Sistema

### Tipos de Usuario

| Tipo | Descripción | Login | Acceso |
|------|-------------|-------|--------|
| **Usuario Interno** | Staff administrativo, médicos, personal | `/login` (actual) | Panel administrativo completo |
| **Clínica Externa** | Clínicas/sedes registradas | `/login-clinica` (nuevo) | Panel de clínica (limitado) |

### Estados de Solicitud

```
┌─────────────────┐
│   REGISTRADA    │ ← Clínica completa formulario
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    PENDIENTE    │ ← Solicitud enviada, esperando revisión
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐  ┌──────────┐
│APROBADA│  │RECHAZADA │
└───┬────┘  └──────────┘
    │
    ▼
┌─────────────────┐
│ CUENTA ACTIVA   │ ← Puede hacer login
└─────────────────┘
```

---

## 📊 Modelo de Datos

### 1. Tabla: `external_clinics`

Almacena información de clínicas externas registradas.

```sql
CREATE TABLE external_clinics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    
    -- Identificación
    nit VARCHAR(20) UNIQUE NOT NULL COMMENT 'NIT de la clínica',
    business_name VARCHAR(255) NOT NULL COMMENT 'Razón social',
    trade_name VARCHAR(255) NULL COMMENT 'Nombre comercial',
    
    -- Contacto
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'Correo electrónico principal',
    phone VARCHAR(20) NOT NULL COMMENT 'Teléfono de contacto',
    mobile VARCHAR(20) NULL COMMENT 'Celular de contacto',
    
    -- Ubicación
    address TEXT NOT NULL COMMENT 'Dirección completa',
    city VARCHAR(100) NOT NULL COMMENT 'Ciudad',
    department VARCHAR(100) NOT NULL COMMENT 'Departamento',
    
    -- Representante legal
    legal_rep_name VARCHAR(255) NOT NULL COMMENT 'Nombre del representante legal',
    legal_rep_id_type_id BIGINT UNSIGNED NOT NULL COMMENT 'Tipo de identificación',
    legal_rep_id_number VARCHAR(30) NOT NULL COMMENT 'Número de identificación',
    
    -- Autenticación
    password VARCHAR(255) NOT NULL COMMENT 'Contraseña hasheada',
    
    -- Estado y aprobación
    status ENUM('pending', 'approved', 'rejected', 'active', 'inactive') DEFAULT 'pending' 
        COMMENT 'Estado de la solicitud/cuenta',
    rejection_reason TEXT NULL COMMENT 'Motivo de rechazo',
    approved_by BIGINT UNSIGNED NULL COMMENT 'Usuario que aprobó',
    approved_at TIMESTAMP NULL COMMENT 'Fecha de aprobación',
    rejected_by BIGINT UNSIGNED NULL COMMENT 'Usuario que rechazó',
    rejected_at TIMESTAMP NULL COMMENT 'Fecha de rechazo',
    
    -- Flags de seguridad
    must_change_password BOOLEAN DEFAULT FALSE COMMENT 'Debe cambiar contraseña',
    email_verified_at TIMESTAMP NULL COMMENT 'Fecha de verificación de email',
    last_login_at TIMESTAMP NULL COMMENT 'Último login',
    failed_login_attempts INT DEFAULT 0 COMMENT 'Intentos fallidos de login',
    
    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Índices y FKs
    FOREIGN KEY (legal_rep_id_type_id) REFERENCES identification_types(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (rejected_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    
    INDEX idx_nit (nit),
    INDEX idx_email (email),
    INDEX idx_status (status)
);
```

### 2. Tabla: `external_clinic_requests`

Log histórico de solicitudes y cambios de estado.

```sql
CREATE TABLE external_clinic_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    external_clinic_id BIGINT UNSIGNED NOT NULL,
    
    -- Cambio de estado
    previous_status VARCHAR(50) NULL COMMENT 'Estado anterior',
    new_status VARCHAR(50) NOT NULL COMMENT 'Nuevo estado',
    
    -- Responsable del cambio
    changed_by BIGINT UNSIGNED NULL COMMENT 'Usuario que realizó el cambio',
    change_reason TEXT NULL COMMENT 'Motivo del cambio',
    
    -- Metadata
    ip_address VARCHAR(45) NULL COMMENT 'IP desde donde se hizo el cambio',
    user_agent TEXT NULL COMMENT 'User agent del navegador',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (external_clinic_id) REFERENCES external_clinics(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    
    INDEX idx_clinic (external_clinic_id),
    INDEX idx_status (new_status)
);
```

### 3. Tabla: `external_clinic_documents`

Documentos adjuntos durante el registro (RUT, Cámara de Comercio, etc.).

```sql
CREATE TABLE external_clinic_documents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    external_clinic_id BIGINT UNSIGNED NOT NULL,
    
    document_type ENUM('rut', 'chamber_of_commerce', 'legal_rep_id', 'other') NOT NULL 
        COMMENT 'Tipo de documento',
    file_name VARCHAR(255) NOT NULL COMMENT 'Nombre original del archivo',
    file_path VARCHAR(500) NOT NULL COMMENT 'Ruta en storage',
    file_size INT UNSIGNED NOT NULL COMMENT 'Tamaño en bytes',
    mime_type VARCHAR(100) NOT NULL COMMENT 'Tipo MIME',
    
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (external_clinic_id) REFERENCES external_clinics(id) ON UPDATE CASCADE ON DELETE CASCADE,
    
    INDEX idx_clinic (external_clinic_id)
);
```

---

## 🔐 Sistema de Autenticación

### Separación de Logins

#### Login Interno (actual - sin cambios)
- **Ruta:** `/login`
- **Vista:** `LoginView.vue` (existente)
- **Guard:** `auth:sanctum`
- **Tabla:** `users`
- **Acceso:** Panel administrativo completo

#### Login Clínicas Externas (nuevo)
- **Ruta:** `/login-clinica`
- **Vista:** `ExternalClinicLoginView.vue` (nueva)
- **Guard:** `auth:sanctum` + `external_clinic` middleware
- **Tabla:** `external_clinics`
- **Acceso:** Panel de clínica (limitado)

### Middleware: `external_clinic`

Verifica que el usuario autenticado sea una clínica externa y que su cuenta esté activa.

```php
// app/Http/Middleware/EnsureExternalClinic.php
public function handle(Request $request, Closure $next)
{
    $clinic = $request->user('external_clinic');
    
    if (!$clinic) {
        return response()->json(['message' => 'No autenticado como clínica'], 401);
    }
    
    if ($clinic->status !== 'active') {
        return response()->json([
            'message' => 'Su cuenta no está activa. Contacte al administrador.',
            'status' => $clinic->status
        ], 403);
    }
    
    return $next($request);
}
```

### Guards de Sanctum

Configurar en `config/auth.php`:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'external_clinic' => [
        'driver' => 'session',
        'provider' => 'external_clinics',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'external_clinics' => [
        'driver' => 'eloquent',
        'model' => App\Models\ExternalClinic::class,
    ],
],
```

---

## 🎨 Flujo de Usuario (Frontend)

### 1. Página de Registro (`/registro-clinica`)

**Componente:** `ExternalClinicRegisterView.vue`

#### Paso 1: Datos de la Clínica/Sede
- NIT (validación de formato y unicidad)
- Razón social
- Nombre comercial (opcional)
- Correo electrónico (validación y unicidad)
- Teléfono
- Celular (opcional)

#### Paso 2: Ubicación
- Dirección completa
- Ciudad (select con autocomplete)
- Departamento (select)

#### Paso 3: Representante Legal
- Nombre completo
- Tipo de identificación (select desde `identification_types`)
- Número de identificación

#### Paso 4: Documentos
- RUT (PDF, max 5MB)
- Cámara de Comercio (PDF, max 5MB)
- Cédula del representante legal (PDF/imagen, max 5MB)

#### Paso 5: Credenciales
- Contraseña (validación según política)
- Confirmar contraseña
- Checkbox: Acepto términos y condiciones

**Botón:** "Enviar Solicitud"

### 2. Página de Confirmación (`/solicitud-enviada`)

**Componente:** `ExternalClinicRequestSentView.vue`

- ✅ Icono de éxito
- Mensaje: "¡Solicitud enviada!"
- Texto: "Hemos recibido tu registro. Nuestro equipo revisará los datos y te notificaremos por correo electrónico el resultado en las próximas 24-48 horas."
- Información de contacto: NIT, correo, teléfono
- Botón: "Volver al inicio"

### 3. Panel de Solicitudes (Staff/Admin) (`/solicitudes-clinicas`)

**Componente:** `ExternalClinicRequestsView.vue`

**Tabla con filtros:**
- Estado (pendiente, aprobada, rechazada)
- Fecha de solicitud
- NIT
- Razón social
- Ciudad

**Columnas:**
- NIT
- Razón social
- Ciudad
- Representante legal
- Fecha de solicitud
- Estado (badge con color)
- Acciones:
  - 👁️ Ver detalles
  - ✅ Aprobar (solo si está pendiente)
  - ❌ Rechazar (solo si está pendiente)

### 4. Modal de Detalle de Solicitud

**Componente:** `ExternalClinicRequestDetailModal.vue`

**Tabs:**
1. **Información General**
   - Datos de la clínica
   - Datos del representante legal
   - Ubicación

2. **Documentos Adjuntos**
   - Lista de documentos con preview
   - Botón de descarga

3. **Historial**
   - Timeline de cambios de estado
   - Usuario responsable
   - Fecha y hora

**Acciones:**
- Botón "Aprobar" (verde) → Abre modal de confirmación
- Botón "Rechazar" (rojo) → Abre modal con textarea para motivo

### 5. Login de Clínica Externa (`/login-clinica`)

**Componente:** `ExternalClinicLoginView.vue`

**Diseño similar al login interno pero con:**
- Header azul con logo blanco
- Título: "Acceso para Clínicas Externas"
- Campos:
  - NIT (en lugar de username)
  - Contraseña
  - Checkbox "Recordarme"
- Botón: "Ingresar"
- Link: "¿Aún no tienes cuenta? Regístrate aquí"
- Link: "¿Olvidaste tu contraseña?"

**Validaciones:**
- Verificar que la cuenta esté en estado `active`
- Mostrar mensaje específico si está `pending`, `rejected` o `inactive`

### 6. Panel de Clínica Externa (`/panel-clinica`)

**Componente:** `ExternalClinicDashboardView.vue`

**Layout diferenciado:**
- Sidebar con opciones limitadas:
  - 🏠 Inicio
  - 📊 Mis Estadísticas
  - 📄 Mis Datos
  - 🔐 Cambiar Contraseña
  - 🚪 Cerrar Sesión

**Dashboard:**
- Card de bienvenida con nombre de la clínica
- Estadísticas básicas (según módulos futuros)
- Acceso rápido a funcionalidades

---

## 🔧 Backend (API Endpoints)

### Rutas Públicas (sin autenticación)

```php
// routes/web.php

Route::prefix('api/external-clinics')->name('api.external-clinics.')->group(function () {
    
    // Registro
    Route::post('register', [ExternalClinicAuthController::class, 'register'])
        ->name('register');
    
    // Login
    Route::post('login', [ExternalClinicAuthController::class, 'login'])
        ->name('login');
    
    // Verificar disponibilidad de NIT
    Route::post('check-nit', [ExternalClinicAuthController::class, 'checkNit'])
        ->name('check-nit');
});
```

### Rutas Protegidas (clínica autenticada)

```php
Route::prefix('api/external-clinics')->name('api.external-clinics.')
    ->middleware(['auth:sanctum', 'external_clinic'])
    ->group(function () {
    
    // Perfil
    Route::get('profile', [ExternalClinicController::class, 'profile'])
        ->name('profile');
    
    Route::put('profile', [ExternalClinicController::class, 'updateProfile'])
        ->name('profile.update');
    
    Route::put('password', [ExternalClinicController::class, 'updatePassword'])
        ->name('password.update');
    
    // Logout
    Route::post('logout', [ExternalClinicAuthController::class, 'logout'])
        ->name('logout');
});
```

### Rutas Administrativas (staff/admin)

```php
Route::prefix('api/admin/external-clinics')->name('api.admin.external-clinics.')
    ->middleware(['auth:sanctum', 'active'])
    ->group(function () {
    
    // Listar solicitudes
    Route::post('get-all', [AdminExternalClinicController::class, 'getAll'])
        ->name('get-all');
    
    // Ver detalle
    Route::get('{clinic}', [AdminExternalClinicController::class, 'show'])
        ->name('show');
    
    // Aprobar
    Route::post('{clinic}/approve', [AdminExternalClinicController::class, 'approve'])
        ->name('approve');
    
    // Rechazar
    Route::post('{clinic}/reject', [AdminExternalClinicController::class, 'reject'])
        ->name('reject');
    
    // Activar/Desactivar
    Route::patch('{clinic}/toggle-status', [AdminExternalClinicController::class, 'toggleStatus'])
        ->name('toggle-status');
    
    // Documentos
    Route::get('{clinic}/documents', [AdminExternalClinicController::class, 'documents'])
        ->name('documents');
    
    Route::get('documents/{document}/download', [AdminExternalClinicController::class, 'downloadDocument'])
        ->name('documents.download');
});
```

---

## 📧 Sistema de Notificaciones

### 1. Notificación: Solicitud Recibida (a la clínica)

**Trigger:** Después de completar el registro

**Canal:** Email

**Contenido:**
- Asunto: "Solicitud de registro recibida - Clínica Santa Bárbara"
- Cuerpo:
  - Saludo con nombre de la clínica
  - Confirmación de recepción
  - Tiempo estimado de respuesta (24-48 horas)
  - Datos de contacto para dudas

### 2. Notificación: Nueva Solicitud (al staff/admin)

**Trigger:** Después de completar el registro

**Canal:** Email + Notificación en sistema

**Contenido:**
- Asunto: "Nueva solicitud de clínica externa - [Razón Social]"
- Cuerpo:
  - NIT
  - Razón social
  - Ciudad
  - Representante legal
  - Link directo a la solicitud en el panel

### 3. Notificación: Solicitud Aprobada (a la clínica)

**Trigger:** Cuando el staff/admin aprueba la solicitud

**Canal:** Email + SMS

**Contenido Email:**
- Asunto: "¡Tu cuenta ha sido activada! - Clínica Santa Bárbara"
- Cuerpo:
  - Felicitación por aprobación
  - Instrucciones de acceso
  - Link al login de clínicas externas
  - Credenciales: NIT y contraseña que registraron
  - Recomendación de cambiar contraseña

**Contenido SMS:**
- "Tu cuenta en Clínica Santa Bárbara ha sido activada. Ingresa con tu NIT en [URL]. Clínica Santa Bárbara"

### 4. Notificación: Solicitud Rechazada (a la clínica)

**Trigger:** Cuando el staff/admin rechaza la solicitud

**Canal:** Email

**Contenido:**
- Asunto: "Actualización sobre tu solicitud - Clínica Santa Bárbara"
- Cuerpo:
  - Lamentamos informar que la solicitud no fue aprobada
  - Motivo del rechazo (ingresado por el admin)
  - Posibilidad de volver a aplicar corrigiendo los datos
  - Datos de contacto para aclaraciones

---

## 🔒 Seguridad

### Validaciones Backend

1. **NIT:**
   - Formato válido (números y guión)
   - Unicidad en la base de datos
   - No permitir caracteres especiales

2. **Email:**
   - Formato válido
   - Unicidad en la base de datos
   - Verificación de dominio (opcional)

3. **Contraseña:**
   - Longitud mínima según política del sistema
   - Complejidad según configuración (mayúsculas, números, especiales)
   - Hash con bcrypt

4. **Documentos:**
   - Tipos de archivo permitidos: PDF, JPG, PNG
   - Tamaño máximo: 5MB por archivo
   - Validación de MIME type
   - Almacenamiento seguro en `storage/app/external_clinic_documents/`

### Rate Limiting

```php
// routes/web.php
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('api/external-clinics/register', ...);
    Route::post('api/external-clinics/login', ...);
});
```

- Máximo 5 intentos por minuto por IP

### Auditoría

- Todos los modelos implementan `Auditable`
- Log de cambios de estado en `external_clinic_requests`
- Activity logs para acciones críticas (aprobación, rechazo)

---

## 🎨 Diseño y UX

### Colores y Branding

- **Mantener identidad visual de Clínica Santa Bárbara:**
  - Azul principal: `#0D2D6B`
  - Azul contraste: `#16468E`
  - Fondo claro: `#EFF6FF`

### Estados Visuales

| Estado | Color Badge | Icono |
|--------|-------------|-------|
| Pendiente | Amarillo (`warning`) | ⏳ |
| Aprobada | Verde (`success`) | ✅ |
| Rechazada | Rojo (`danger`) | ❌ |
| Activa | Verde (`success`) | ✓ |
| Inactiva | Gris (`info`) | ⊘ |

### Componentes Reutilizables

- `BaseModal.vue` - Para modales de aprobación/rechazo
- `ContentCard.vue` - Para cards de información
- `StatusPill.vue` - Para badges de estado
- `BaseDrawer.vue` - Para panel de detalles (opcional)

---

## 📝 Permisos Necesarios

### Nuevos Permisos a Crear

```php
// database/seeders/PermissionSeeder.php

[
    'external-clinics.view',
    'Ver solicitudes de clínicas externas',
    'Permite listar y consultar solicitudes de clínicas externas'
],
[
    'external-clinics.approve',
    'Aprobar clínicas externas',
    'Permite aprobar solicitudes de clínicas externas'
],
[
    'external-clinics.reject',
    'Rechazar clínicas externas',
    'Permite rechazar solicitudes de clínicas externas'
],
[
    'external-clinics.manage',
    'Gestionar clínicas externas',
    'Permite activar/desactivar cuentas de clínicas externas'
],
```

### Asignación a Roles

- **super-admin:** Todos los permisos
- **admin:** `external-clinics.view`, `external-clinics.approve`, `external-clinics.reject`
- **staff:** `external-clinics.view` (solo lectura)

---

## 🧪 Testing

### Tests Backend (PHPUnit)

1. **ExternalClinicRegistrationTest**
   - ✅ Puede registrar una clínica con datos válidos
   - ✅ No puede registrar con NIT duplicado
   - ✅ No puede registrar con email duplicado
   - ✅ Valida formato de NIT
   - ✅ Valida política de contraseñas
   - ✅ Crea registro en estado `pending`

2. **ExternalClinicAuthTest**
   - ✅ Puede hacer login con NIT y contraseña correctos
   - ✅ No puede hacer login si la cuenta está `pending`
   - ✅ No puede hacer login si la cuenta está `rejected`
   - ✅ No puede hacer login si la cuenta está `inactive`
   - ✅ Incrementa `failed_login_attempts` en login fallido
   - ✅ Actualiza `last_login_at` en login exitoso

3. **ExternalClinicApprovalTest**
   - ✅ Admin puede aprobar una solicitud pendiente
   - ✅ Admin puede rechazar una solicitud pendiente
   - ✅ No puede aprobar una solicitud ya aprobada
   - ✅ Envía notificación al aprobar
   - ✅ Envía notificación al rechazar
   - ✅ Crea registro en `external_clinic_requests`

### Tests Frontend (Vitest - opcional)

1. **ExternalClinicRegisterView.test.ts**
   - ✅ Renderiza formulario correctamente
   - ✅ Valida campos requeridos
   - ✅ Valida formato de NIT
   - ✅ Valida formato de email
   - ✅ Valida política de contraseñas

2. **ExternalClinicLoginView.test.ts**
   - ✅ Renderiza formulario de login
   - ✅ Muestra error en credenciales incorrectas
   - ✅ Redirige al dashboard en login exitoso

---

## 📦 Estructura de Archivos

### Backend

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── ExternalClinicAuthController.php
│   │       ├── ExternalClinicController.php
│   │       └── AdminExternalClinicController.php
│   ├── Requests/
│   │   ├── RegisterExternalClinicRequest.php
│   │   ├── LoginExternalClinicRequest.php
│   │   ├── ApproveExternalClinicRequest.php
│   │   └── RejectExternalClinicRequest.php
│   ├── Resources/
│   │   ├── ExternalClinicResource.php
│   │   └── ExternalClinicRequestResource.php
│   └── Middleware/
│       └── EnsureExternalClinic.php
├── Models/
│   ├── ExternalClinic.php
│   ├── ExternalClinicRequest.php
│   └── ExternalClinicDocument.php
├── Notifications/
│   ├── ExternalClinicRegistered.php
│   ├── ExternalClinicApproved.php
│   └── ExternalClinicRejected.php
database/
├── migrations/
│   ├── xxxx_create_external_clinics_table.php
│   ├── xxxx_create_external_clinic_requests_table.php
│   └── xxxx_create_external_clinic_documents_table.php
├── factories/
│   └── ExternalClinicFactory.php
└── seeders/
    └── ExternalClinicSeeder.php (solo para desarrollo)
```

### Frontend

```
resources/js/
├── views/
│   └── external-clinic/
│       ├── ExternalClinicRegisterView.vue
│       ├── ExternalClinicRequestSentView.vue
│       ├── ExternalClinicLoginView.vue
│       ├── ExternalClinicDashboardView.vue
│       ├── ExternalClinicProfileView.vue
│       └── admin/
│           ├── ExternalClinicRequestsView.vue
│           └── ExternalClinicRequestDetailModal.vue
├── stores/
│   ├── externalClinicAuth.ts
│   └── externalClinicRequests.ts
└── router/
    └── index.ts (agregar rutas nuevas)
```

---

## 🚀 Plan de Implementación (Fases)

> **Estado actual:** Implementación completada el 17 de julio de 2026. Las migraciones deben ejecutarse manualmente con `php artisan migrate`.

### Fase 1: Base de Datos y Modelos (2-3 días) ✅ Completada
- ✅ Crear migraciones
- ✅ Crear modelos con relaciones
- ✅ Crear factories
- ✅ Crear seeders de prueba
- ⏳ Ejecutar migraciones (pendiente manual)

### Fase 2: Backend - Registro y Autenticación (3-4 días)
- ✅ Configurar guards de Sanctum
- ✅ Crear middleware `EnsureExternalClinic`
- ✅ Implementar `ExternalClinicAuthController`
- ✅ Implementar `ExternalClinicController`
- ✅ Crear Form Requests con validaciones
- ✅ Crear API Resources
- ✅ Definir rutas públicas y protegidas
- ✅ Tests unitarios y de integración

### Fase 3: Backend - Panel Administrativo (2-3 días) ✅ Completada
- ✅ Implementar `AdminExternalClinicController`
- ✅ Endpoints de listado con filtros
- ✅ Endpoints de aprobación/rechazo
- ✅ Sistema de subida y descarga de documentos
- ✅ Tests de permisos y autorización

### Fase 4: Sistema de Notificaciones (2 días) ✅ Completada
- ✅ Crear notificaciones de email
- ✅ Integrar con servicio de correo configurado
- ✅ Configurar templates de email con branding
- ✅ Tests de envío de notificaciones

### Fase 5: Frontend - Registro (3-4 días) ✅ Completada
- ✅ Crear `ExternalClinicRegisterView.vue`
- ✅ Formulario multi-paso con validaciones
- ✅ Componente de subida de archivos
- ✅ Integración con API de registro
- ✅ Vista de confirmación
- ✅ Manejo de errores

### Fase 6: Frontend - Login y Dashboard (2-3 días) ✅ Completada
- ✅ Crear `ExternalClinicLoginView.vue`
- ✅ Crear store `externalClinicAuth.ts`
- ✅ Implementar guards de router
- ✅ Crear `ExternalClinicDashboardView.vue`
- ✅ Layout diferenciado para clínicas externas
- ✅ Vista de perfil

### Fase 7: Frontend - Panel Administrativo (3-4 días) ✅ Completada
- ✅ Crear `ExternalClinicRequestsView.vue`
- ✅ Tabla con filtros y paginación
- ✅ Modal de detalle con tabs
- ✅ Funcionalidad de aprobación/rechazo
- ✅ Visualización de documentos
- ✅ Integración con notificaciones del sistema

### Fase 8: Testing y Refinamiento (2-3 días) ✅ Completada
- ✅ Tests backend de registro, autenticación y aprobación
- ✅ Verificar notificaciones
- ✅ Pruebas de seguridad en permisos
- ✅ Optimización de performance
- ✅ Ajustes de UX

### Fase 9: Documentación (1 día) ✅ Completada
- ✅ Actualizar README
- ✅ Actualizar matriz de permisos
- ✅ Documentar rutas y flujo en el plan

---

## 📊 Estimación Total

**Tiempo estimado:** 20-27 días hábiles (4-5.5 semanas)

**Recursos necesarios:**
- 1 desarrollador backend (Laravel)
- 1 desarrollador frontend (Vue 3)
- 1 diseñador UX/UI (revisión de flujos)
- 1 QA tester (fase de testing)

---

## ⚠️ Consideraciones Importantes

### 1. Compatibilidad con Sistema Actual
- El login interno **NO debe modificarse**
- Los usuarios internos **NO deben verse afectados**
- Mantener separación total entre guards de autenticación

### 2. Escalabilidad
- Diseñar para soportar cientos de clínicas externas
- Considerar índices en BD para queries frecuentes
- Implementar caché para listados administrativos

### 3. Seguridad
- Validación exhaustiva de documentos subidos
- Rate limiting estricto en endpoints públicos
- Auditoría completa de todas las acciones
- Encriptación de datos sensibles

### 4. UX
- Feedback claro en cada paso del proceso
- Mensajes de error descriptivos
- Tiempos de espera realistas
- Diseño responsive (mobile-first)

### 5. Mantenibilidad
- Seguir patrones existentes del proyecto
- Documentación inline en código
- Tests automatizados
- Logs detallados para debugging

---

## 🔄 Flujo de Aprobación de Este Plan

1. **Revisión técnica** - Validar factibilidad y estimaciones
2. **Revisión de negocio** - Confirmar requerimientos funcionales
3. **Revisión de diseño** - Validar flujos de usuario
4. **Aprobación final** - Autorización para iniciar implementación

---

## 📞 Contacto

Para dudas o sugerencias sobre este plan:
- **Desarrollador:** [Nombre]
- **Email:** [email]
- **Fecha límite de aprobación:** [Fecha]

---

**Última actualización:** Julio 17, 2026  
**Versión:** 1.0.0  
**Estado:** ⏳ Pendiente de aprobación
