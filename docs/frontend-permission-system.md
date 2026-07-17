# Sistema de Control de Acceso por Permisos

El sistema implementa control de acceso basado en permisos (RBAC) en el frontend mediante 3 mecanismos complementarios: una directiva Vue (`v-permission`), un composable (`usePermission`) y un componente wrapper (`PermissionGuard`), además de protección a nivel de rutas en el router.

---

## Arquitectura

```
resources/js/
├── composables/
│   └── usePermission.ts       # Lógica reutilizable de permisos
├── directives/
│   └── permission.ts          # Directiva v-permission
├── components/ui/
│   └── PermissionGuard.vue    # Componente wrapper para casos complejos
├── stores/
│   └── auth.ts                # Store con hasPermission() (base de todo)
├── router/
│   └── index.ts               # Guard de rutas con meta.permissions
└── app.ts                     # Registro global de la directiva
```

### Flujo de dependencias

```
auth store (hasPermission)
       ↓
usePermission composable (can, canAny, canAll)
       ↓
    ├── v-permission directive (templates)
    ├── PermissionGuard component (slots)
    └── router guard (meta.permissions)
```

El store `auth` es la única fuente de verdad. El composable, la directiva y el componente dependen de él, pero ningún componente de vista necesita importar el store directamente.

---

## 1. Composable `usePermission`

**Archivo:** `resources/js/composables/usePermission.ts`

Capa de lógica reutilizable que expone tres funciones reactivas basadas en el auth store.

### Funciones

| Función | Parámetros | Retorna | Descripción |
|---------|-----------|---------|-------------|
| `can(permission)` | `string` | `boolean` | Verifica si el usuario tiene un permiso específico |
| `canAny(permissions)` | `string[]` | `boolean` | Verifica si tiene **al menos uno** de los permisos (OR) |
| `canAll(permissions)` | `string[]` | `boolean` | Verifica si tiene **todos** los permisos (AND) |

### Ejemplo de uso programático

```vue
<script setup lang="ts">
import { usePermission } from '@/composables/usePermission';

const { can, canAny, canAll } = usePermission();

const canEdit = can('users.update');
const canEditOrCreate = canAny(['users.update', 'users.create']);
const hasFullAccess = canAll(['users.view', 'users.create', 'users.update', 'users.delete']);
</script>

<template>
  <div v-if="canEdit">
    Contenido solo visible para usuarios con permiso de edición
  </div>
</template>
```

### Comportamiento con super-admin

El usuario con rol `super-admin` tiene bypass automático: `hasPermission()` siempre retorna `true` sin importar el permiso consultado. Esto está implementado en el auth store:

```typescript
function hasPermission(permission: string) {
  if (!user.value) return false;
  if (user.value.roles?.some(r => r.name === 'super-admin')) return true;
  return user.value.roles?.some(role =>
    role.permissions?.some(p => p.name === permission)
  ) ?? false;
}
```

---

## 2. Directiva `v-permission`

**Archivo:** `resources/js/directives/permission.ts`

Directiva personalizada de Vue registrada globalmente en `app.ts`. Elimina del DOM los elementos cuyo permiso no es válido.

### Registro global

```typescript
// app.ts
import PermissionDirective from './directives/permission';
app.directive('permission', PermissionDirective);
```

### Sintaxis

#### Permiso único

```vue
<el-button v-permission="'users.create'" type="primary">
  Nuevo Usuario
</el-button>
```

#### Múltiples permisos — AND (debe tener todos)

String separado por comas:

```vue
<el-button v-permission="'users.create,users.update'">
  Acción restringida
</el-button>
```

Array de strings:

```vue
<el-button v-permission="['users.create', 'users.update']">
  Acción restringida
</el-button>
```

#### Múltiples permisos — OR (al menos uno)

Usar el modificador `.any`:

```vue
<el-button v-permission.any="'users.create,users.update'">
  Acción restringida
</el-button>
```

```vue
<el-button v-permission.any="['users.create', 'users.update']">
  Acción restringida
</el-button>
```

### Comportamiento

- Si el usuario **no tiene** el permiso, el elemento se **elimina del DOM** (`parentNode.removeChild`).
- La evaluación ocurre en los hooks `mounted` y `updated`, por lo que es reactiva.
- Si el valor es `null` o vacío, el elemento se muestra sin restricción.

### Ejemplo real en vistas

```vue
<!-- UsersView.vue -->
<template #actions>
  <el-button v-permission="'users.create'" type="primary" :icon="PlusIcon" @click="openDialog('create')">
    Nuevo Usuario
  </el-button>
</template>

<el-table-column label="ACCIONES" width="100" align="center">
  <template #default="{ row }">
    <el-tooltip v-permission="'users.update'" content="Editar" placement="top">
      <button @click="openDialog('edit', row)">...</button>
    </el-tooltip>
    <el-tooltip v-permission="'users.delete'" content="Eliminar" placement="top">
      <button @click="deleteUser(row)">...</button>
    </el-tooltip>
  </template>
</el-table-column>
```

---

## 3. Componente `PermissionGuard`

**Archivo:** `resources/js/components/ui/PermissionGuard.vue`

Componente wrapper que usa slots para envolver contenido. Útil cuando se necesita:
- Envolvar múltiples elementos
- Mostrar contenido alternativo (fallback) cuando no hay permiso
- Lógica condicional más compleja

### Props

| Prop | Tipo | Descripción |
|------|------|-------------|
| `permission` | `string` | Permiso único a validar |
| `any` | `string[]` | Lista de permisos (OR) |
| `all` | `string[]` | Lista de permisos (AND) |

### Slots

| Slot | Descripción |
|------|-------------|
| `default` | Contenido a mostrar si tiene permiso |
| `fallback` | Contenido alternativo si no tiene permiso |

### Ejemplos

#### Permiso único

```vue
<PermissionGuard permission="users.create">
  <el-button type="primary">Nuevo Usuario</el-button>
</PermissionGuard>
```

#### Con contenido fallback

```vue
<PermissionGuard permission="users.create">
  <el-button type="primary">Nuevo Usuario</el-button>
  <template #fallback>
    <span class="text-gray-400 text-sm">No tiene permiso para crear usuarios</span>
  </template>
</PermissionGuard>
```

#### Cualquier permiso (OR)

```vue
<PermissionGuard :any="['users.create', 'users.update']">
  <div class="action-buttons">
    <el-button>Crear</el-button>
    <el-button>Editar</el-button>
  </div>
</PermissionGuard>
```

#### Todos los permisos (AND)

```vue
<PermissionGuard :all="['users.view', 'users.delete']">
  <el-button type="danger">Eliminar todos los usuarios</el-button>
</PermissionGuard>
```

---

## 4. Protección de rutas (Router Guard)

**Archivo:** `resources/js/router/index.ts`

Las rutas pueden definir `meta.permissions` con un permiso o array de permisos. El router guard valida acceso antes de cargar la vista.

### Definición de permisos en rutas

```typescript
{
  path: 'usuarios',
  name: 'users',
  component: () => import('@/views/users/UsersView.vue'),
  meta: { permissions: ['users.view'] },
},
{
  path: 'roles',
  name: 'roles',
  component: () => import('@/views/roles/RolesView.vue'),
  meta: { permissions: ['roles.view'] },
},
```

### Lógica del guard

```typescript
if (to.meta.permissions) {
  const required = Array.isArray(to.meta.permissions)
    ? to.meta.permissions as string[]
    : [to.meta.permissions as string];
  const hasAccess = required.some((p: string) => auth.hasPermission(p));
  if (!hasAccess) {
    ElMessage.error('No tiene permisos para acceder a este módulo.');
    return next({ name: 'dashboard' });
  }
}
```

- Si el usuario no tiene **al menos uno** de los permisos requeridos, se redirige al dashboard con un mensaje de error.
- La validación usa lógica OR: basta con tener uno de los permisos listados.

---

## 5. Filtrado del Sidebar

**Archivos:** `resources/js/components/layout/AppSidebarItem.vue`, `AppSidebar.vue`

Los items del sidebar se filtran automáticamente según los permisos del usuario.

### Uso

```vue
<AppSidebarItem
  to="/usuarios"
  icon="Users"
  text="Gestión de usuarios"
  permission="users.view"
/>
```

- Si el usuario no tiene el permiso `users.view`, el item **no se renderiza**.
- Si no se pasa el prop `permission`, el item siempre es visible (ej: Dashboard, Configuración).

---

## Cuándo usar cada mecanismo

| Mecanismo | Cuándo usar |
|-----------|-------------|
| `v-permission` | Caso más común. Ocultar botones, enlaces o elementos individuales en templates |
| `usePermission` | Lógica programática en script (condicionales, computed, funciones) |
| `PermissionGuard` | Envolvar múltiples elementos o mostrar contenido fallback |
| `meta.permissions` | Proteger acceso completo a una vista/ruta |

---

## Convención de nombres de permisos

Los permisos siguen el formato `modulo.accion`:

| Permiso | Descripción |
|---------|-------------|
| `users.view` | Ver listado de usuarios |
| `users.create` | Crear nuevos usuarios |
| `users.update` | Editar usuarios existentes |
| `users.delete` | Eliminar usuarios |
| `roles.view` | Ver listado de roles |
| `roles.create` | Crear nuevos roles |
| `roles.update` | Editar roles y asignar permisos |
| `roles.delete` | Eliminar roles |
| `ingresos.view` | Ver ingresos activos |

Esta convención permite agrupar permisos por módulo y facilita la asignación en el panel de Roles y Permisos.
