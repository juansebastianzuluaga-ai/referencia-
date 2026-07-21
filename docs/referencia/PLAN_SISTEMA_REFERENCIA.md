# PLAN: Sistema de Referencia y Contrarreferencia

---

## 1. VISIÓN GENERAL

Plataforma web donde **clínicas externas** (sedes) se autoregistran y gestionan solicitudes de referencia para que pacientes reciban servicios especializados. El equipo de referencia interno revisa, aprueba/rechaza, y el sistema se integra con GoMedesys + notifica por email.

---

## 2. ACTORES DEL SISTEMA

| Actor | Rol |
|-------|-----|
| **Clínica externa (Sede)** | por primera vez se autoregistra, crea solicitudes de referencia, recibe notificaciones |
| Personal de Referencia| Revisa informacion de la clinica,Revisa solicitudes,Gestiona usuarios,verifica requisitos, aprueba/rechaza |



## 3. MÓDULOS DEL SISTEMA

### 3.1. MÓDULO DE AUTENTICACIÓN Y AUTORREGISTRO

**Autoregistro:**
- Formulario de registro con datos reales de la clínica (NIT, razón social, dirección, teléfono, email, representante legal, documentación adjunta)
- Validación de datos de las clínicas autorizadas o aprobación manual tras registro despues de validar la informacion
- Confirmación por email con link de activación

    **Login**: Ingresa NIT o cédula
                │
                ▼
        ┌─────────────────────┐
        │  ¿NIT registrado?   │── No → "Usuario no encontrado"
        └──────────┬──────────┘
                │ Sí
                ▼
        Selecciona método:
        ┌──────────────────┐
        │ 📧 Correo        │ → Se envía email con **botón mágico**
        │ 📱 Teléfono      │ → Se envía **código SMS** de 6 dígitos
        └──────────────────┘
                │
                ▼
        ┌──────────────────────────────────────┐
        │ Correo: clic en botón → abre sesión  │
        │ Teléfono: ingresa código → validar → │
        └──────────────────────────────────────┘
                │
                ▼
                🟢 ACCESO

**Roles:**
- Super Admin 
- Admin de Referencia
- Staff de Referencia
- Usuario Clínica (Sede)

### 3.2. MÓDULO DE GESTIÓN DE SOLICITUDES

**Formulario de solicitud** (lo llena la clínica externa):
- Datos del paciente: tipo documento, documento, nombres, apellidos, género, fecha nacimiento, edad, EPS, régimen, teléfono, dirección
- Datos de la solicitud: servicio requerido, especialidad, justificación clínica, diagnóstico (CIE-10), médico remitente, adjuntos (opcional)

**Estados de la solicitud:**

```
SOLICITADA → EN PROCESO → APROBADA
                         → RECHAZADA
                         → REQUIERE CORRECCIÓN → EN PROCESO
```

| Estado | Descripción |
|--------|------------|
| **SOLICITADA** | La clínica envió el formulario |
| **EN PROCESO** | El staff de referencia está evaluando |
| **APROBADA** | Cumple requisitos → pasa a GoMedesys |
| **RECHAZADA** | No cumple requisitos → notificación |
| **REQUIERE CORRECIÓN** | Faltan datos → la clínica externa debe corregir |

### 3.3. MÓDULO DE REVISIÓN (STAFF DE REFERENCIA)

**Pantalla de bandeja de solicitudes:**
- Lista filtrable por estado, fecha, EPS, servicio
- Detalle de cada solicitud con toda la info del paciente y la clínica

**Acciones del staff:**
1. **Aprobar** → se gatilla integración con GoMedesys + solicitud código EPS (investigar si lo da la EPS por webservice o GoMedesys)
2. **Rechazar** → se envía email automático a la clínica con el motivo de rechazo
3. **Solicitar corrección** → se notifica a la clínica qué datos deben ajustar

**Parámetros de decisión 
- Contratos 
- Políticas de aceptación
- Cobertura por servicio/especialidad
- parametros

### 3.4. MÓDULO DE INTEGRACIÓN GoMedesys

**Función:** Cuando una solicitud es APROBADA, el sistema debe auto-poblar los campos en GoMedesys.

### 3.5. MÓDULO DE CÓDIGO EPS (AUTORIZACIÓN)

⊗ **PENDIENTE DE INVESTIGAR:**

Posibles escenarios:
1. **El código lo da GoMedesys** → GoMedesys ya tiene integración con las EPS y devuelve el código automáticamente
2. **El código se obtiene por portal web externo** → el staff debe ingresar manualmente el código al sistema
3. **El código se solicita por teléfono** → el staff lo ingresa manualmente

**En el sistema:** Debe haber un campo "Código de autorización EPS" que se complete:
- Automáticamente (si hay integración)
- Manualmente por el staff (si no hay)

### 3.7. MÓDULO DE ADMINISTRACIÓN

**Gestión de usuarios:**
- CRUD de usuarios (crear, editar, desactivar, cambiar rol)
- Desbloqueo de cuentas
- Reset de contraseña

### 3.8. MÓDULO DE REPORTES

- Solicitudes por período (creadas, aprobadas, rechazadas)
- Tiempo promedio de respuesta por staff
- Solicitudes por EPS, por servicio, por clínica
- Reporte exportable a Excel/PDF
- Historico de solicitudes 

## 4. FLUJO COMPLETO DEL SISTEMA

```
┌─────────────────────────────────────────────────────────┐
│ 1. La clínica accede al link de la aplicación           │
│    y se registra (autoregistro)                       │
│    → Recibe email de confirmación                       │
└──────────────────────┬──────────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────────┐
│ 2. Login (máx 3 intentos)                               │
│    → Ingresa al dashboard de la clínica                 │
└──────────────────────┬──────────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────────┐
│ 3. La clínica llena el formulario de solicitud           │
│    con datos del paciente + servicio requerido           │
│    → Estado: SOLICITADA                                 │
│    → Email automático al staff: "Nueva solicitud"       │
└──────────────────────┬──────────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────────┐
│ 4. Staff de referencia revisa solicitud                  │
│    con parámetros, políticas, contratos                 │
│                                                         │
│    ┌─────────────┬──────────────┬──────────────┐        │
│    │ Cumple todo  │ No cumple    │ Faltan datos │        │
│    └──────┬──────┘ └──────┬──────┘ └──────┬──────┘        │
│           ▼               ▼               ▼              │
│      APROBADA        RECHAZADA     REQUIERE CORRECCIÓN  │
│                      (email motivos)(email + detalle)   │
└──────────────────────┬──────────────────────────────────┘
                       ▼ (Si aprobada)
┌─────────────────────────────────────────────────────────┐
│ 5. Integración GoMedesys                                 │
│    → Datos del paciente se crean en GoMedesys            │
│    → Se genera referencia en GoMedesys                   │
│    → Se obtiene # de referencia de GoMedesys            │
└──────────────────────┬──────────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────────┐
│ 6. Solicitud de código EPS                               │
│    → (Investigación pendiente: webservice? portal?       │
│      telefono? lo da GoMedesys?)                         │
│    → Estado: APROBADA - CÓDIGO EPS PENDIENTE            │
│    → Una vez obtenido: APROBADA - CÓDIGO EPS OBTENIDO   │
└──────────────────────┬──────────────────────────────────┘
                       ▼
┌─────────────────────────────────────────────────────────┐
│ 7. Notificación a la clínica:                            │
│    "Solicitud APROBADA - Código autorización: XXXXX      │
│    El paciente puede llegar a la clínica"               │
└─────────────────────────────────────────────────────────┘
