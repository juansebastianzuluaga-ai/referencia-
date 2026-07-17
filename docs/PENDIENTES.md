# Pendientes y Mejoras - Plantilla Laravel

Este documento lista todas las funcionalidades que faltan para que la plantilla esté completamente lista para usar en nuevos proyectos de la Clínica Santa Bárbara.

## 🔴 Crítico (Necesario antes de primer uso)

### 1. Documentación de Copia de Plantilla
**Estado:** ✅ Completado

Guía paso a paso para copiar la plantilla a un nuevo proyecto creada.

**Acciones realizadas:**
- ✅ Creado `docs/setup-guide.md` - Guía completa de configuración
- ✅ Creado `scripts/setup-project.php` - Script automatizado para cambiar nombre de app
- ✅ Documentados comandos para limpiar base de datos
- ✅ Checklist de personalización incluido en setup-guide.md
- ✅ Actualizado `docs/MODULE_GUIDE.md` con sección de primeros pasos

**Uso:**
```bash
# Copiar plantilla
git clone <repositorio> nuevo-proyecto
cd nuevo-proyecto

# Ejecutar script de configuración
php scripts/setup-project.php "Nombre del Proyecto"

# Seguir pasos en docs/setup-guide.md
```

### 2. Modularizar/Eliminar Módulo Específico
**Estado:** ✅ Completado

El módulo `IngresosActivosView` (censo) era específico de un proyecto y ha sido eliminado de la plantilla base.

**Acciones realizadas:**
- ✅ Eliminada carpeta `resources/js/views/censo/`
- ✅ Eliminada ruta `ingresos-activos` del router Vue
- ✅ Eliminado item de menú "Censo" del sidebar
- ✅ Verificado: no existía backend relacionado
- ✅ Verificado: no existían permisos en seeders

---

## 🟠 Importante (Recomendado para producción)

### 3. Documentación de Permisos
**Estado:** ✅ Completado

Documentación completa de permisos y roles creada.

**Acciones realizadas:**
- ✅ Creado `docs/permissions-matrix.md` con:
  - Lista completa de permisos disponibles por módulo
  - Documentación de roles predefinidos (super-admin, admin, medico)
  - Matriz de permisos por rol
  - Guía paso a paso para agregar nuevos permisos
  - Convención de nombres para permisos
  - Instrucciones para re-seedear permisos

### 4. Configuración de Correo Production-Ready
**Estado:** ✅ Completado

Sistema de configuración SMTP dinámica desde settings de BD implementado.

**Acciones realizadas:**
- ✅ Creado `app/Services/MailConfigService.php` - Servicio para leer configuración SMTP de settings
- ✅ Creado `app/Providers/MailConfigServiceProvider.php` - Provider para configurar mailer dinámicamente
- ✅ Registrado provider en `bootstrap/providers.php`
- ✅ Creado `docs/mail-configuration.md` con:
  - Documentación completa de keys de settings para SMTP
  - Ejemplos de configuración para Gmail, SendGrid, AWS SES, Mailgun, Postmark
  - Guía de cómo verificar configuración antes de enviar emails
  - Notas de seguridad

**Pendientes (menos crítico):**
- Plantillas de correo HTML personalizadas
- Sistema de colas para envío de emails

### 5. Tests Más Completos
**Estado:** Pendiente

Solo hay 5 tests actualmente. Faltan:
- Tests de autenticación (login, logout, 2FA)
- Tests de permisos (acceso a rutas, verificación de roles)
- Tests de los módulos principales (users, roles, settings)
- Tests de API endpoints
- Tests de cambio de contraseña
- Tests de perfil de usuario
- Tests de recuperación de contraseña

**Acción requerida:**
- Crear `tests/Feature/Auth/LoginTest.php`
- Crear `tests/Feature/Auth/PasswordResetTest.php`
- Crear `tests/Feature/Permissions/PermissionTest.php`
- Crear tests para cada controller principal

### 6. Sistema de Notificaciones
**Estado:** ✅ Completado

Sistema de notificaciones implementado con backend y frontend completos.

**Acciones realizadas:**
- ✅ Creado modelo `Notification` con scopes, relaciones y métodos `markAsRead`/`markAsUnread`
- ✅ Creada migración `notifications` con campos: user_id, type, title, message, link, read_at
- ✅ Creada factory `NotificationFactory` para tests
- ✅ Creado `NotificationResource` para API responses
- ✅ Creado `NotificationController` con endpoints:
  - `GET /api/notifications` - Listar notificaciones (con filtro unread_only)
  - `GET /api/notifications/unread-count` - Contador de no leídas
  - `PATCH /api/notifications/{id}/read` - Marcar como leída
  - `PATCH /api/notifications/read-all` - Marcar todas como leídas
  - `DELETE /api/notifications/{id}` - Eliminar notificación
  - `DELETE /api/notifications` - Limpiar todas
- ✅ Creado store `notifications.ts` en Pinia con polling cada 60s
- ✅ Creado componente `NotificationCenter.vue` con:
  - Popover con lista de notificaciones
  - Badge con contador de no leídas
  - Iconos por tipo (info, success, warning, error)
  - Tiempo relativo (Hace X min/h/d)
  - Acciones: marcar leída, marcar todas, eliminar, limpiar
  - Click en notificación navega al link si existe
- ✅ Integrado `NotificationCenter` en `AppHeader.vue` reemplazando campana estática
- ✅ Migración ejecutada correctamente
- ✅ Pint format aplicado

**Pendientes (menos crítico):**
- Notificaciones en tiempo real (Laravel Broadcasting/WebSockets)

### 6.1 Recuperación de Contraseña
**Estado:** ✅ Completado

Flujo completo de recuperación de contraseña implementado con envío de email.

**Acciones realizadas:**
- ✅ Creada `app/Notifications/ResetPasswordNotification.php` - Email personalizado con branding de la Clínica
- ✅ Modificado `app/Models/User.php` - Método `sendPasswordResetNotification` usando notificación personalizada
- ✅ Creada vista `resources/js/views/ForgotPasswordView.vue` - Formulario para solicitar email de recuperación
- ✅ Creada vista `resources/js/views/ResetPasswordView.vue` - Formulario para nueva contraseña con política de validación
- ✅ Agregadas rutas Vue Router: `/forgot-password` y `/reset-password`
- ✅ Actualizado `LoginView.vue` - Link "¿Olvidó su contraseña?" ahora navega a forgot-password
- ✅ Movida ruta `password-policy` fuera de middleware autenticado para acceso público
- ✅ Fortify rutas verificadas: `POST /api/forgot-password` y `POST /api/reset-password` funcionando
- ✅ Pint format aplicado

**Flujo completo:**
1. Usuario hace click en "¿Olvidó su contraseña?" en login
2. Ingresa su correo en ForgotPasswordView
3. Fortify envía email con token (ResetPasswordNotification)
4. Usuario hace click en el enlace del email
5. Llega a ResetPasswordView con token y email
6. Ingresa nueva contraseña (con validación de política)
7. Fortify resetea la contraseña
8. Usuario es redirigido al login

### 6.2 Plantilla de Email Personalizada
**Estado:** ✅ Completado

Plantilla reutilizable para todos los emails del sistema con identidad visual de la Clínica Santa Bárbara.

**Acciones realizadas:**
- ✅ Publicados los views de mail de Laravel (`php artisan vendor:publish --tag=laravel-mail`)
- ✅ Personalizado `resources/views/vendor/mail/html/themes/default.css`:
  - Fuente `DM Sans` (consistente con la app web)
  - Paleta de colores de la clínica: header `#0a2347`, botones `#1e4d96`, fondo `#eff6ff`
  - Bordes redondeados (8px), sombra suave azul
  - Panel con borde izquierdo azul
- ✅ Personalizado `resources/views/vendor/mail/html/message.blade.php`:
  - Header con logo blanco (`logo-w.png`) sobre fondo azul oscuro
  - Footer con nombre de la clínica, mensaje "no responda a este correo" y copyright en español
- ✅ Personalizado `resources/views/vendor/mail/html/header.blade.php`:
  - Removido el logo genérico de Laravel, ahora renderiza contenido personalizado
- ✅ Actualizado `SettingSeeder.php` - `mail_from_name` cambiado de `'Sistema'` a `'Clínica Santa Bárbara'`
- ✅ Actualizado valor en BD vía tinker
- ✅ Corregido `MailConfigService.php` - Mapeo de `mailpit` → `smtp` (Mailpit funciona sobre SMTP)
- ✅ Removido `ShouldQueue` de `ResetPasswordNotification` para envío síncrono inmediato
- ✅ Creada `lang/es/passwords.php` - Traducciones en español para mensajes de reset
- ✅ Creada `lang/es/auth.php` - Traducciones en español para mensajes de auth
- ✅ Pint format aplicado

**Uso de la plantilla:**
La plantilla se usa automáticamente en todos los emails que usan `MailMessage` (Laravel Markdown mail).
Cualquier notificación que use `(new MailMessage)->subject()->greeting()->line()->action()`
heredará automáticamente el diseño personalizado. No requiere configuración adicional.

**Archivos modificados:**
- `resources/views/vendor/mail/html/themes/default.css` - Tema CSS con colores y tipografía de la clínica
- `resources/views/vendor/mail/html/message.blade.php` - Layout base con logo y footer personalizado
- `resources/views/vendor/mail/html/header.blade.php` - Header sin logo genérico de Laravel
- `app/Services/MailConfigService.php` - Mapeo `mailpit` → `smtp`
- `app/Notifications/ResetPasswordNotification.php` - Envío síncrono (sin queue)
- `database/seeders/SettingSeeder.php` - `from_name` actualizado
- `lang/es/passwords.php` - Traducciones de password reset
- `lang/es/auth.php` - Traducciones de autenticación

---

## 🟡 Útil (Mejoras de experiencia)

### 7. Sistema de Archivos/Media
**Estado:** Pendiente

No hay configuración para:
- Subida de archivos (avatar, documentos)
- Gestión de documentos
- Almacenamiento S3/local
- Compresión de imágenes
- Generación de thumbnails

**Acción requerida:**
- Configurar `filesystems.php` para S3
- Crear endpoint de subida de archivos
- Crear componente de file upload
- Documentar uso de Storage

### 8. Exportación de Datos
**Estado:** Pendiente

Falta:
- Exportar tablas a Excel/CSV
- Generación de reportes PDF
- Filtros avanzados para exportación
- Programación de reportes automáticos

**Acción requerida:**
- Instalar `laravel-excel` o similar
- Crear endpoint de exportación
- Agregar botones de exportación en vistas
- Crear sistema de reportes PDF con dompdf/snappy

### 9. Nombre de App Configurable
**Estado:** Pendiente

El logo y colores son fijos para la Clínica Santa Bárbara, pero el nombre de la aplicación debería ser configurable:
- Nombre de app configurable desde settings de BD
- Nombre en `.env` para diferentes entornos
- Título de la pestaña del navegador

**Nota:** No se requiere personalización de logo o colores.

**Acción requerida:**
- Agregar setting para nombre de app en `SettingSeeder.php`
- Usar el setting en componentes de layout
- Documentar cómo cambiar el nombre de app

### 10. Configuración de Backups
**Estado:** Pendiente

No hay:
- Estrategia de backup automatizado
- Script de backup de base de datos
- Configuración de retención de backups
- Backup de archivos storage

**Acción requerida:**
- Configurar `laravel-backup` package
- Crear comando artisan para backup manual
- Configurar schedule para backups automáticos
- Documentar proceso de restore

### 11. Monitoring Mejorado
**Estado:** Pendiente

Aunque hay `activity_logs`, falta:
- Dashboard de errores y excepciones
- Logs de performance (tiempos de respuesta)
- Alertas de errores críticos (email/Slack)
- Integración con Sentry o similar

**Acción requerida:**
- Instalar y configurar Sentry
- Crear dashboard de errores en UI
- Configurar alertas automáticas
- Documentar monitoreo

### 12. Sistema de Búsqueda Global
**Estado:** Pendiente

Falta:
- Buscador global en la aplicación
- Búsqueda across módulos
- Historial de búsquedas
- Búsqueda avanzada con filtros

**Acción requerida:**
- Crear componente `GlobalSearch.vue`
- Implementar búsqueda con Scout o similar
- Agregar shortcut de teclado (Ctrl+K)
- Indexar modelos principales

### 13. Internacionalización (i18n)
**Estado:** Pendiente

La aplicación está solo en español. Falta:
- Sistema de traducciones
- Soporte multi-idioma
- Selector de idioma en UI
- Traducciones de todos los textos

**Acción requerida:**
- Instalar `laravel-lang` o similar
- Crear archivos de idioma
- Crear sistema de traducción en Vue
- Documentar cómo agregar traducciones

### 14. Documentación de API
**Estado:** Pendiente

No hay documentación de la API:
- Swagger/OpenAPI documentation
- Ejemplos de requests/responses
- Documentación de endpoints
- Schema de datos

**Acción requerida:**
- Instalar `laravel-apidoc` o `scribe`
- Generar documentación automática
- Agregar ejemplos en controllers
- Configurar actualización automática

### 15. Arquitectura de Dominios Frontend (En progreso)
**Estado:** 🟡 Parcialmente completado

Se definió `docs/frontend-domain-architecture.md` proponiendo una estructura
frontend (`resources/js/domains/`) que refleja los dominios backend
(`Organization`, `Workflow`, `Configuration`, `Cases`). Se completaron los
dominios **Configuration**, **Organization** y **Workflow**; **Cases** queda
pendiente.

**Completado:**
- ✅ `docs/frontend-domain-architecture.md` — propuesta completa de arquitectura, patrones de UI y migración incremental
- ✅ `resources/js/components/ui/CrudTable.vue` — componente reutilizable de tabla CRUD (búsqueda, `BaseModal`, formulario dinámico con `input`/`textarea`/`number`/`switch`/`select`/`color`)
- ✅ Regla documentada: todo modal/drawer debe usar `BaseModal.vue`/`BaseDrawer.vue` (nunca `el-dialog`/`el-drawer` directo)
- ✅ Regla documentada: las acciones CRUD de `CrudTable.vue` deben recibirse como promesas mediante `create-action`/`update-action`/`delete-action`; la modal permanece abierta y conserva el formulario ante errores, se bloquea mientras espera la API y solo se cierra después de una respuesta exitosa. El detalle del contrato está en `frontend-domain-architecture.md`.
- ✅ Regla documentada: botones de acción en tablas deben ser icon-buttons con `el-tooltip` (patrón de `UsersView.vue`), no `el-button`/`el-button-group`
- ✅ Dominio **Configuration** integrado como 4 tabs nuevos en `SettingsView.vue` (híbrido: settings existentes + catálogos CRUD):
  - `views/settings/tabs/ParametersTab.vue` (clave-valor configurable, campo `type` con select)
  - `views/settings/tabs/StatusesTab.vue` (campo `entity_type` con select, `color` con color-picker)
  - `views/settings/tabs/PrioritiesTab.vue` (color-picker, `level`, `order`)
  - `views/settings/tabs/NotificationTemplatesTab.vue`
- ✅ `resources/js/stores/configuration.ts` — store Pinia con CRUD para las 4 entidades, campos alineados a los `*Resource` del backend
- ✅ `database/seeders/DomainDefaultsSeeder.php` — datos por defecto para Configuration (16 parámetros, 15 estados, 9 plantillas), Organization (empresa, sede, 12 departamentos, 14 cargos, 11 procesos) y Workflow (1 workflow de casos con 6 estados y 8 transiciones). Registrado en `DatabaseSeeder`.
- ✅ Dominio **Organization** en `resources/js/domains/organization/` — vista independiente con tabs verticales para Empresas, Sedes, Departamentos, Cargos y Procesos; tipos, servicio API y stores separados por entidad; selects relacionales y permisos por tab.
- ✅ Paginación backend en las cinco tablas de Organization con total, tamaños `[10, 25, 50, 100]`, búsqueda remota y listas `get-all` separadas para selects. Patrón documentado en `frontend-domain-architecture.md`.
- ✅ Ruta `/organizacion` e ítem de menú "Organización" en `AppSidebar.vue`, visibles cuando el usuario posee al menos un permiso de consulta del dominio.
- ✅ Dominio **Workflow** en `resources/js/domains/workflow/` — patrón maestro-detalle en una sola ruta: `CrudTable` de workflows con botón "Configurar" que cambia al modo detalle con pestañas de Estados y Transiciones. `StatesManager.vue` con CRUD inline (color, orden, flags inicial/final). `TransitionsManager.vue` con CRUD inline (selects de origen/destino, requisitos, SLA, badge visual de flujo). Stores Pinia separados por entidad. Eager loading de `fromState`/`toState` en backend.
- ✅ `CrudTable.vue` extendido con slot `#row-actions` para acciones personalizadas por fila y prop `actionWidth` para ensanchar la columna de acciones.
- ✅ Ruta `/workflows` e ítem de menú "Workflows" en `AppSidebar.vue` con permiso `workflows.view`.
- ✅ Patrón maestro-detalle documentado en `frontend-domain-architecture.md` sección 4b.

**Pendiente:**
- ⬜ Dominio **Cases** — vista maestro-detalle con pestañas (ver sección 4 de `frontend-domain-architecture.md`), es el módulo más grande y crítico
- ⬜ Revisar permisos: los 4 tabs de Configuration usan `parameters.create/edit/delete` como permiso compartido para las 4 entidades (Parámetros, Estados, Prioridades, Plantillas). Decidir si se separan en permisos granulares (`statuses.*`, `priorities.*`, `notification-templates.*`) o se mantiene un solo grupo `parameters.*` para todo Configuration
- ⬜ Auditar vistas existentes (`RolesView`, otras) que puedan seguir usando `el-button`/`el-button-group` en tablas y migrarlas al patrón de icon-buttons con tooltip
- ⬜ Tests feature para los endpoints CRUD de Configuration (parameters, statuses, priorities, notification-templates) — actualmente solo existen `ActionParticipantController` y `WorkflowHistoryController` en `MissingEntitiesTest.php`
- ⬜ Migrar dominios existentes (`Users`, `Roles`) a la nueva estructura `resources/js/domains/` si se decide adoptarla globalmente, o mantenerlos en `resources/js/views/` como excepción histórica (definir criterio en la documentación)

---

## 📊 Resumen

| Categoría | Items | Completados | Pendientes |
|-----------|-------|-------------|------------|
| 🔴 Crítico | 2 | 2 | 0 |
| 🟠 Importante | 4 | 3 | 1 |
| 🟡 Útil | 9 | 0 | 9 |
| **Total** | **15** | **5** | **10** |

---

## 🎯 Prioridad Sugerida

1. **Inmediato:** Completar el dominio Workflow en frontend (item 15)
2. **Corto plazo:** Dominio Cases (maestro-detalle), tests de Configuration
3. **Medio plazo:** Tests más completos, revisión de permisos granulares
4. **Largo plazo:** Resto de mejoras útiles (i18n, exportación, monitoring, etc.)

---

**Última actualización:** Julio 2026  
**Versión de plantilla:** 1.0.0