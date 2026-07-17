# Matriz de Permisos y Roles

Este documento documenta todos los permisos disponibles en el sistema y cómo se asignan a los roles predefinidos.

## 📋 Permisos Disponibles

Los permisos siguen el formato: `modulo.accion`

### Usuarios (users)
| Permiso | Display Name | Descripción |
|---------|-------------|-------------|
| `users.view` | Ver usuarios | Permite listar y consultar usuarios |
| `users.create` | Crear usuarios | Permite crear usuarios |
| `users.update` | Actualizar usuarios | Permite actualizar usuarios |
| `users.delete` | Eliminar usuarios | Permite eliminar usuarios |

### Roles (roles)
| Permiso | Display Name | Descripción |
|---------|-------------|-------------|
| `roles.view` | Ver roles | Permite listar y consultar roles |
| `roles.create` | Crear roles | Permite crear roles |
| `roles.update` | Actualizar roles | Permite actualizar roles |
| `roles.delete` | Eliminar roles | Permite eliminar roles |

### Permisos (permissions)
| Permiso | Display Name | Descripción |
|---------|-------------|-------------|
| `permissions.view` | Ver permisos | Permite listar permisos |

### Tipos de Identificación (identification-types)
| Permiso | Display Name | Descripción |
|---------|-------------|-------------|
| `identification-types.view` | Ver tipos de identificacion | Permite listar tipos de identificacion |
| `identification-types.create` | Crear tipos de identificacion | Permite crear tipos de identificacion |
| `identification-types.update` | Actualizar tipos de identificacion | Permite actualizar tipos de identificacion |
| `identification-types.delete` | Eliminar tipos de identificacion | Permite eliminar tipos de identificacion |

### Configuración (settings)
| Permiso | Display Name | Descripción |
|---------|-------------|-------------|
| `settings.view` | Ver configuracion | Permite ver la configuracion del sistema |
| `settings.update` | Actualizar configuracion | Permite actualizar la configuracion del sistema |

### API Keys (api-keys)
| Permiso | Display Name | Descripción |
|---------|-------------|-------------|
| `api-keys.view` | Ver API keys | Permite listar y consultar credenciales API |
| `api-keys.create` | Crear API keys | Permite crear nuevas credenciales API |
| `api-keys.update` | Actualizar API keys | Permite actualizar y regenerar credenciales API |
| `api-keys.delete` | Eliminar API keys | Permite eliminar credenciales API |

---

## 👥 Roles Predefinidos

### Super Admin (super-admin)
**Display Name:** Super administrador  
**Descripción:** Acceso total a la administración del sistema  
**Permisos:** TODOS los permisos del sistema

### Admin (admin)
**Display Name:** Administrador  
**Descripción:** Administra usuarios, roles y catálogos base  
**Permisos:**
- `users.view`
- `users.create`
- `users.update`
- `roles.view`
- `permissions.view`
- `identification-types.view`
- `identification-types.create`
- `identification-types.update`

### Médico (medico)
**Display Name:** Medico  
**Descripción:** Acceso base para profesionales médicos  
**Permisos:**
- `identification-types.view`

---

## 📊 Matriz de Permisos por Rol

| Permiso | Super Admin | Admin | Médico |
|---------|-------------|-------|--------|
| **Usuarios** |
| users.view | ✅ | ✅ | ❌ |
| users.create | ✅ | ✅ | ❌ |
| users.update | ✅ | ✅ | ❌ |
| users.delete | ✅ | ❌ | ❌ |
| **Roles** |
| roles.view | ✅ | ✅ | ❌ |
| roles.create | ✅ | ❌ | ❌ |
| roles.update | ✅ | ❌ | ❌ |
| roles.delete | ✅ | ❌ | ❌ |
| **Permisos** |
| permissions.view | ✅ | ✅ | ❌ |
| **Tipos de Identificación** |
| identification-types.view | ✅ | ✅ | ✅ |
| identification-types.create | ✅ | ✅ | ❌ |
| identification-types.update | ✅ | ✅ | ❌ |
| identification-types.delete | ✅ | ❌ | ❌ |
| **Configuración** |
| settings.view | ✅ | ❌ | ❌ |
| settings.update | ✅ | ❌ | ❌ |
| **API Keys** |
| api-keys.view | ✅ | ❌ | ❌ |
| api-keys.create | ✅ | ❌ | ❌ |
| api-keys.update | ✅ | ❌ | ❌ |
| api-keys.delete | ✅ | ❌ | ❌ |

---

## 🔧 Cómo Agregar Nuevos Permisos

### Paso 1: Agregar el Permiso en el Seeder

Editar `database/seeders/PermissionSeeder.php`:

```php
collect([
    // ... permisos existentes ...
    ['modulo.accion', 'Display Name', 'Descripción del permiso'],
])->each(fn (array $permission) => Permission::query()->updateOrCreate(
    [
        'name' => $permission[0],
        'guard_name' => 'web',
    ],
    [
        'display_name' => $permission[1],
        'description' => $permission[2],
    ],
));
```

### Paso 2: Asignar a Roles (Opcional)

Editar `database/seeders/RoleSeeder.php`:

```php
$roles = [
    'nombre-rol' => [
        'display_name' => 'Display Name',
        'description' => 'Descripción del rol',
        'permissions' => [
            'modulo.accion',  // Agregar aquí
            // ... otros permisos ...
        ],
    ],
];
```

### Paso 3: Usar en Rutas Vue

En `resources/js/router/index.ts`:

```typescript
{
  path: 'modulo',
  name: 'modulo',
  component: () => import('@/views/modulo/ModuloView.vue'),
  meta: { permissions: ['modulo.view'] },
},
```

### Paso 4: Usar en Componentes Vue

```vue
<template>
  <el-button 
    v-if="auth.hasPermission('modulo.create')"
    @click="crear"
  >
    Crear
  </el-button>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
</script>
```

### Paso 5: Usar en Backend (Middleware)

En `routes/web.php`:

```php
Route::middleware(['auth:sanctum', 'can:modulo.view'])
    ->get('/api/modulo', [ModuloController::class, 'index']);
```

O en controller:

```php
public function index()
{
    $this->authorize('modulo.view');
    // ...
}
```

---

## 🔄 Re-Seedear Permisos

Si agregas nuevos permisos o modificas roles, ejecuta:

```bash
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RoleSeeder
```

**⚠️ Advertencia:** Esto actualizará los permisos existentes pero no eliminará los que ya no están en el seeder.

---

## 📝 Convención de Nombres

Sigue esta convención para mantener consistencia:

- **Formato:** `modulo.accion`
- **Modulo:** singular, en minúsculas, con guiones si es necesario (ej: `identification-types`)
- **Accion:** verbo en inglés en minúsculas (ej: `view`, `create`, `update`, `delete`)
- **Ejemplos:**
  - ✅ `users.view`
  - ✅ `identification-types.create`
  - ✅ `api-keys.update`
  - ❌ `user.view` (debe ser plural)
  - ❌ `Users.View` (debe ser minúsculas)
  - ❌ `users_ver` (debe usar punto)

---

**Última actualización:** Julio 2026