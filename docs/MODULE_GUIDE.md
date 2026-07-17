# Guía para Crear Módulos Completos (Backend + Frontend)

Esta guía documenta el patrón estándar para crear módulos CRUD completos en esta aplicación Laravel 13 + Vue 3, basado en el módulo de usuarios como referencia.

## 🚀 Primeros Pasos - Configurar Nuevo Proyecto

Si estás copiando esta plantilla para crear un nuevo proyecto, primero sigue la guía de configuración:

**📖 Ver:** `docs/setup-guide.md` - Guía completa para configurar un nuevo proyecto

**Resumen rápido:**
1. Copiar la plantilla
2. Ejecutar script de configuración: `php scripts/setup-project.php "Nombre del Proyecto"`
3. Limpiar base de datos: `php artisan migrate:fresh --seed`
4. Instalar dependencias: `composer install && pnpm install`
5. Compilar assets: `pnpm run build`
6. Iniciar servidor: `php artisan serve`

---

## Nota sobre Skills

Para el backend Laravel, activa la skill `laravel-best-practices` para seguir las mejores prácticas del framework. Los ejemplos en esta guía son complementarios y específicos a la arquitectura de este proyecto.

## Índice

1. [Estructura de Archivos](#estructura-de-archivos)
2. [Backend - Laravel](#backend---laravel)
3. [Frontend - Vue 3](#frontend---vue-3)
4. [Patrones Comunes](#patrones-comunes)
5. [Componentes UI Reutilizables](#componentes-ui-reutilizables)

---

## Estructura de Archivos

### Backend
```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── {Module}Controller.php
│   ├── Requests/
│   │   ├── Store{Module}Request.php
│   │   └── Update{Module}Request.php
│   └── Resources/
│       └── {Module}Resource.php
├── Models/
│   └── {Module}.php
database/
└── migrations/
    └── xxxx_xx_xx_create_{modules}_table.php
routes/
└── web.php
```

### Frontend
```
resources/js/
├── views/
│   └── {module}/
│       ├── {Module}View.vue        # Vista principal (tabla + filtros)
│       └── {Module}FormDialog.vue  # Modal de formulario
├── stores/
│   └── {module}.ts                 # Pinia store
└── components/
    └── ui/
        ├── BaseModal.vue          # Modal genérico (ya existe)
        ├── ContentCard.vue        # Card contenedor (ya existe)
        └── StatusPill.vue          # Badge de estado (ya existe)
```

---

## Backend - Laravel

### 1. Model

```php
<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'description',
    'is_active',
    'related_model_id',
    // ... otros campos
])]
#[Hidden(['password', 'remember_token'])]
class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory, SoftDeletes;

    // Relaciones
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class, 'related_model_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

### 2. Migration

```php
<?php

use App\Models\{Module};
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{modules}', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre del {module}');
            $table->text('description')->nullable()->comment('Descripción del {module}');
            $table->boolean('is_active')->default(true)->comment('Indica si el {module} está activo');
            $table->foreignId('related_model_id')
                ->nullable()
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete()
                ->comment('ID del modelo relacionado');
            $table->timestamps();
            $table->softDeletes();

            // Índices únicos compuestos si son necesarios
            // $table->unique(['related_model_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{modules}');
    }
};
```

### 3. Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\FiltersPaginatedResults;
use App\Http\Requests\Store{Module}Request;
use App\Http\Requests\Update{Module}Request;
use App\Http\Resources\{Module}Resource;
use App\Models\{Module};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class {Module}Controller extends BaseController
{
    use FiltersPaginatedResults;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [
        'name',
        'description',
    ];

    /**
     * @var array<int, string>
     */
    protected array $filterable = [
        'name',
        'is_active',
        'related_model_id',
    ];

    /**
     * @var array<int, string>
     */
    protected array $sortable = [
        'id',
        'name',
        'created_at',
    ];

    public function getAll(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('{modules}.view'), Response::HTTP_FORBIDDEN);

        $items = $this->filterPaginated(
            query: {Module}::query()->with(['relatedModel', 'roles']),
            request: $request,
            searchable: $this->searchable,
            filterable: $this->filterable,
            relationFilters: [
                'roles' => ['relation' => 'roles', 'column' => 'id'],
            ],
            sortable: $this->sortable,
            defaultPageSize: 10,
        );

        return $this->sendResponse(
            {Module}Resource::collection($items),
            '{Modules} consultados correctamente',
        );
    }

    public function store(Store{Module}Request $request): JsonResponse
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $item = DB::transaction(function () use ($validated, $roles): {Module} {
            $item = {Module}::create($validated);
            $this->syncRoles($item, $roles);

            return $item;
        });

        return $this->sendResponse(
            {Module}Resource::make($item->load(['relatedModel', 'roles'])),
            '{Module} creado correctamente',
            Response::HTTP_CREATED,
        );
    }

    public function show({Module} $module): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('{modules}.view'), Response::HTTP_FORBIDDEN);

        return $this->sendResponse({Module}Resource::make($module->load(['relatedModel', 'roles'])));
    }

    public function update(Update{Module}Request $request, {Module} $module): JsonResponse
    {
        $validated = $request->validated();
        $roles = $validated['roles'] ?? null;
        unset($validated['roles']);

        $module->update($validated);

        if (is_array($roles)) {
            $this->syncRoles($module, $roles);
        }

        return $this->sendResponse(
            {Module}Resource::make($module->load(['relatedModel', 'roles'])),
            '{Module} actualizado correctamente',
        );
    }

    public function destroy({Module} $module): JsonResponse
    {
        abort_unless(request()->user()->hasPermission('{modules}.delete'), Response::HTTP_FORBIDDEN);

        $module->delete();

        return $this->sendResponse(new \stdClass, '{Module} eliminado correctamente');
    }

    /**
     * @param  array<int, string>  $roleNames
     */
    private function syncRoles({Module} $module, array $roleNames): void
    {
        if (!empty($roleNames)) {
            $module->roles()->sync($roleNames);
        }
    }
}
```

### 4. Store Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Store{Module}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('{modules}.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'related_model_id' => ['nullable', 'integer', 'exists:related_models,id'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
```

### 5. Update Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Update{Module}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('{modules}.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $moduleId = $this->route('module')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('{modules}', 'name')->ignore($moduleId)],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'related_model_id' => ['nullable', 'integer', 'exists:related_models,id'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }
}
```

### 6. Resource

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Module}Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'related_model' => RelatedModelResource::make($this->whenLoaded('relatedModel')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### 7. Routes (web.php)

Las rutas se definen en `routes/web.php` dentro del grupo `api` con middleware `auth:sanctum` y `active`:

```php
use App\Http\Controllers\Api\{Module}Controller;

// Dentro de Route::prefix('api')->name('api.')->group(function (): void {
//   Route::middleware(['auth:sanctum', 'active'])->group(function (): void {

Route::post('{modules}/get-all', [{Module}Controller::class, 'getAll'])->name('{modules}.get-all');
Route::apiResource('{modules}', {Module}Controller::class)->except(['index']);

//   });
// });
```

**Nota:** `apiResource` genera automáticamente las rutas estándar REST:
- `POST /api/{modules}` → store
- `GET /api/{modules}/{module}` → show
- `PUT/PATCH /api/{modules}/{module}` → update
- `DELETE /api/{modules}/{module}` → destroy

La ruta `index` se excluye porque se usa `get-all` para paginación y filtros.

---

## Frontend - Vue 3

### 1. Pinia Store (stores/{module}.ts)

```typescript
import { defineStore } from 'pinia';
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import http from '@/plugins/axios';

export const use{Modules}Store = defineStore('{modules}', () => {
  const items = ref<any[]>([]);
  const relatedItems = ref<any[]>([]);
  const loading = ref(false);
  const itemsLoaded = ref(false);
  const relatedItemsLoaded = ref(false);
  const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  async function loadRelatedItems(options: { force?: boolean } = {}) {
    if (relatedItemsLoaded.value && !options.force) {
      return;
    }
    try {
      // Ensure CSRF cookie is present for POST endpoints (Sanctum)
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const { data } = await http.post('/api/related-items/get-all', { per_page: 100 }, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      relatedItems.value = data.data?.data || data.data || [];
      relatedItemsLoaded.value = true;
    } catch (e: any) {
      relatedItems.value = [];
      ElMessage.error('Error cargando items relacionados.');
    }
  }

  async function loadItems(payload: any = {}, options: { force?: boolean } = {}) {
    loading.value = true;
    try {
      if (itemsLoaded.value && !options.force && Object.keys(payload).length === 0) {
        loading.value = false;
        return;
      }
      // Ensure CSRF cookie is present for POST endpoints (Sanctum)
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const body = { per_page: pagination.value.per_page, page: pagination.value.current_page, ...payload };
      const { data } = await http.post('/api/{modules}/get-all', body, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      items.value = data.data?.data || data.data || [];
      pagination.value = {
        current_page: data.data?.current_page || data.data?.meta?.current_page || 1,
        per_page: data.data?.per_page || data.data?.meta?.per_page || 10,
        total: data.data?.total || data.data?.meta?.total || 0,
        last_page: data.data?.last_page || data.data?.meta?.last_page || 1,
      };
      itemsLoaded.value = true;
    } catch (e: any) {
      items.value = [];
      // error handled below
      const status = e?.response?.status;
      if (status === 403) {
        ElMessage.error('No tiene permiso para ver {modules} (403)');
      } else if (status === 401) {
        ElMessage.error('No autenticado (401). Revisa sesión.');
      } else {
        ElMessage.error('Error cargando {modules}.');
      }
    } finally {
      loading.value = false;
    }
  }

  function resetLoaded() {
    itemsLoaded.value = false;
    relatedItemsLoaded.value = false;
    pagination.value = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
  }

  async function getItem(id: number) {
    const { data } = await http.get(`/api/{modules}/${id}`);
    return data.data;
  }

  async function createItem(payload: any) {
    await http.post('/api/{modules}', payload);
  }

  async function updateItem(id: number, payload: any) {
    await http.put(`/api/{modules}/${id}`, payload);
  }

  async function deleteItem(id: number) {
    await http.delete(`/api/{modules}/${id}`);
  }

  return {
    items,
    relatedItems,
    loading,
    pagination,
    loadRelatedItems,
    loadItems,
    getItem,
    createItem,
    updateItem,
    deleteItem,
  };
});
```

### 2. Main View (views/{module}/{Module}View.vue)

```vue
<template>
  <ContentCard title="Gestión de {Modules}" subtitle="Descripción del módulo">
    <template #actions>
      <el-popover
        v-model:visible="showFilters"
        placement="bottom-end"
        :width="420"
        trigger="click"
        popper-class="!p-0"
      >
        <template #reference>
          <el-badge :value="activeFilterCount" :hidden="activeFilterCount === 0">
            <el-button :icon="FilterIcon" :type="hasActiveFilters ? 'primary' : 'default'">
              Filtros
            </el-button>
          </el-badge>
        </template>
        <div class="p-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <el-input
              v-model="search"
              placeholder="Filtro general"
              :prefix-icon="SearchIcon"
              class="md:col-span-2"
              clearable
            />
            <el-select v-model="relatedFilter" placeholder="Filtrar por relación" class="w-full" clearable :teleported="false">
              <el-option v-for="item in relatedItems" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
            <el-select v-model="statusFilter" placeholder="Filtrar por estado" class="w-full" clearable :teleported="false">
              <el-option label="Activo" :value="true" />
              <el-option label="Inactivo" :value="false" />
            </el-select>
          </div>
          <div class="flex justify-end mt-3 pt-3 border-t border-gray-100">
            <el-button size="small" text @click="clearFilters">Limpiar filtros</el-button>
          </div>
        </div>
      </el-popover>
      <el-button type="primary" :icon="PlusIcon" @click="openDialog('create')">
        Nuevo {Module}
      </el-button>
    </template>

    <el-table 
      :data="items" 
      v-loading="store.loading" 
      style="width: 100%" 
      class="border border-gray-200 rounded-lg"
      @sort-change="handleSortChange"
    >
      <el-table-column prop="name" label="NOMBRE" min-width="200" sortable="custom" />
      <el-table-column prop="description" label="DESCRIPCIÓN" min-width="250" />
      <el-table-column label="ESTADO" width="100">
        <template #default="{ row }">
          <StatusPill :type="row.is_active ? 'green' : 'red'">
            {{ row.is_active ? 'Activo' : 'Inactivo' }}
          </StatusPill>
        </template>
      </el-table-column>
      <el-table-column label="ACCIONES" width="100" align="center" fixed="right">
        <template #default="{ row }">
          <div class="flex items-center justify-center gap-0.5">
            <el-tooltip :content="row.is_active ? 'Inactivar' : 'Activar'" placement="top">
              <button
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 transition-colors cursor-pointer"
                :class="row.is_active ? 'hover:bg-amber-50 hover:text-amber-600' : 'hover:bg-green-50 hover:text-green-600'"
                @click="toggleItemStatus(row)"
              >
                <component :is="row.is_active ? PowerOffIcon : PowerIcon" class="w-4 h-4" />
              </button>
            </el-tooltip>
            <el-tooltip content="Editar" placement="top">
              <button
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors cursor-pointer"
                @click="openDialog('edit', row)"
              >
                <EditIcon class="w-4 h-4" />
              </button>
            </el-tooltip>
            <el-tooltip content="Eliminar" placement="top">
              <button
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer"
                @click="deleteItem(row)"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </el-tooltip>
          </div>
        </template>
      </el-table-column>
    </el-table>

    <div class="flex justify-between items-center mt-4">
      <div class="text-sm text-gray-500">
        Total: {{ store.pagination.total }} {modules}
      </div>
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 25, 50, 100]"
        :total="store.pagination.total"
        layout="sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <{Module}FormDialog
      v-model="dialogVisible"
      :mode="dialogType"
      :item="selectedItem"
      @saved="loadItemsData"
    />
  </ContentCard>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Plus as PlusIcon, Search as SearchIcon, Edit as EditIcon, Trash as TrashIcon, Filter as FilterIcon, Power as PowerIcon, PowerOff as PowerOffIcon } from '@lucide/vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import ContentCard from '@/components/ui/ContentCard.vue';
import StatusPill from '@/components/ui/StatusPill.vue';
import {Module}FormDialog from '@/views/{module}/{Module}FormDialog.vue';
import { use{Modules}Store } from '@/stores/{module}';
import { storeToRefs } from 'pinia';

const search = ref('');
const relatedFilter = ref(null);
const statusFilter = ref(null);
const showFilters = ref(false);

const activeFilterCount = computed(() => {
  let count = 0;
  if (relatedFilter.value) count++;
  if (statusFilter.value !== null) count++;
  if (search.value && search.value.length >= 10) count++;
  return count;
});
const hasActiveFilters = computed(() => activeFilterCount.value > 0);

function clearFilters() {
  search.value = '';
  relatedFilter.value = null;
  statusFilter.value = null;
}

const currentPage = ref(1);
const pageSize = ref(10);
const sortBy = ref('id');
const sortOrder = ref('desc');

const store = use{Modules}Store();
const { items, relatedItems } = storeToRefs(store);

const dialogVisible = ref(false);
const dialogType = ref<'create' | 'edit'>('create');
const selectedItem = ref<any>(null);

function openDialog(type: 'create' | 'edit', item?: any) {
  dialogType.value = type;
  selectedItem.value = type === 'edit' ? item : null;
  dialogVisible.value = true;
}

async function toggleItemStatus(item: any) {
  const newStatus = !item.is_active;
  try {
    await ElMessageBox.confirm(
      `¿Está seguro de ${newStatus ? 'activar' : 'inactivar'} el item ${item.name}?`,
      newStatus ? 'Activar {Module}' : 'Inactivar {Module}',
      {
        confirmButtonText: newStatus ? 'Activar' : 'Inactivar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
      }
    );
  } catch {
    return;
  }

  store.loading = true;
  try {
    await store.updateItem(item.id, { is_active: newStatus });
    ElMessage.success(`{Module} ${newStatus ? 'activado' : 'inactivado'} correctamente`);
    await loadItemsData();
  } catch (e: any) {
    console.error(e);
    ElMessage.error('Error al cambiar el estado del {module}');
  } finally {
    store.loading = false;
  }
}

async function deleteItem(item: any) {
  try {
    await ElMessageBox.confirm(
      `¿Está seguro de eliminar el {module} ${item.name}? Esta acción no se puede deshacer.`,
      'Confirmar Eliminación',
      {
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
      }
    );
  } catch {
    return;
  }

  store.loading = true;
  try {
    await store.deleteItem(item.id);
    ElMessage.success('{Module} eliminado correctamente');
    await loadItemsData();
  } catch (e: any) {
    console.error(e);
    ElMessage.error('Error al eliminar el {module}');
  } finally {
    store.loading = false;
  }
}

function handlePageChange(page: number) {
  currentPage.value = page;
  loadItemsData();
}

function handleSizeChange(size: number) {
  pageSize.value = size;
  currentPage.value = 1;
  loadItemsData();
}

function handleSortChange({ prop, order }: any) {
  if (prop) {
    sortBy.value = prop;
    sortOrder.value = order === 'ascending' ? 'asc' : 'desc';
  } else {
    sortBy.value = 'id';
    sortOrder.value = 'desc';
  }
  loadItemsData();
}

async function loadItemsData() {
  await store.loadItems({ 
    general: search.value || undefined, 
    related_model_id: relatedFilter.value ? [relatedFilter.value] : undefined, 
    is_active: statusFilter.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value,
  });
}

watch(search, (val, oldVal) => {
  const shouldFilter = val.length === 0 || val.length >= 10;
  const shouldFilterOld = (oldVal?.length || 0) === 0 || (oldVal?.length || 0) >= 10;
  if (shouldFilter || shouldFilterOld) {
    currentPage.value = 1;
    loadItemsData();
  }
});

watch([relatedFilter, statusFilter], () => {
  currentPage.value = 1;
  loadItemsData();
});

onMounted(async () => {
  await store.loadRelatedItems();
  await loadItemsData();
});
</script>
```

### 3. Form Dialog (views/{module}/{Module}FormDialog.vue)

```vue
<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="(val: boolean) => emit('update:modelValue', val)"
    :title="mode === 'create' ? 'Nuevo {Module}' : 'Editar {Module}'"
    :subtitle="mode === 'create' ? 'Registre un nuevo {module} en el sistema' : 'Actualice la información del {module}'"
    :icon="mode === 'create' ? PlusIcon : EditIcon"
    :width="dialogWidth"
    confirm-text="Guardar"
    @confirm="save"
    @cancel="handleCancel"
  >
    <div v-loading="loading" element-loading-text="Cargando datos...">
      <el-form :model="form" label-position="top" :rules="formRules" ref="formRef">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1">
          <el-form-item label="Nombre" prop="name" required>
            <el-input v-model="form.name" placeholder="Ingrese el nombre" />
          </el-form-item>
          <el-form-item label="Descripción" class="sm:col-span-2 lg:col-span-3">
            <el-input v-model="form.description" type="textarea" :rows="3" placeholder="Descripción" />
          </el-form-item>
          <el-form-item label="Relación" prop="related_model_id">
            <el-select v-model="form.related_model_id" class="w-full" placeholder="Seleccione">
              <el-option v-for="item in relatedItems" :key="item.id" :label="item.name" :value="item.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Roles" class="sm:col-span-2 lg:col-span-1">
            <el-select v-model="form.roles" multiple class="w-full" placeholder="Seleccione roles">
              <el-option v-for="r in roles" :key="r.id" :label="r.display_name || r.name" :value="r.name" />
            </el-select>
          </el-form-item>
        </div>
        <div class="flex flex-wrap gap-4 pt-1 pb-2">
          <el-form-item class="!mb-0">
            <el-checkbox v-model="form.is_active">Activo</el-checkbox>
          </el-form-item>
        </div>
      </el-form>
    </div>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useWindowSize } from '@vueuse/core';
import { Plus as PlusIcon, Edit as EditIcon } from '@lucide/vue';
import { ElMessage } from 'element-plus';
import BaseModal from '@/components/ui/BaseModal.vue';
import { use{Modules}Store } from '@/stores/{module}';
import { storeToRefs } from 'pinia';

const props = defineProps<{
  modelValue: boolean;
  mode: 'create' | 'edit';
  item?: any;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved'): void;
}>();

const { width: windowWidth } = useWindowSize();
const dialogWidth = computed(() => {
  if (windowWidth.value < 640) return '95vw';
  if (windowWidth.value < 1024) return '600px';
  if (windowWidth.value < 1280) return '760px';
  return '900px';
});

const store = use{Modules}Store();
const { relatedItems, roles } = storeToRefs(store);

const loading = ref(false);
const formRef = ref();

function emptyForm() {
  return {
    id: null as number | null,
    name: '',
    description: '',
    is_active: true,
    related_model_id: null,
    roles: [] as string[],
  };
}

const form = ref(emptyForm());

const formRules = {
  name: [{ required: true, message: 'El nombre es requerido', trigger: 'blur' }],
  related_model_id: [{ required: true, message: 'La relación es requerida', trigger: 'change' }],
};

function fillFormFromItem(item: any) {
  form.value = {
    id: item.id,
    name: item.name,
    description: item.description || '',
    is_active: item.is_active,
    related_model_id: item.related_model?.id ?? null,
    roles: (item.roles || []).map((r: any) => r.name),
  };
}

async function loadFormData() {
  if (props.mode === 'edit' && props.item) {
    loading.value = true;
    try {
      const item = await store.getItem(props.item.id);
      fillFormFromItem(item);
    } catch {
      fillFormFromItem(props.item);
    } finally {
      loading.value = false;
    }
  } else {
    form.value = emptyForm();
  }
}

watch(() => props.modelValue, (visible) => {
  if (visible) {
    loadFormData();
  }
});

function handleCancel() {
  emit('update:modelValue', false);
}

async function save() {
  try {
    if (formRef.value) {
      await formRef.value.validate();
    }
  } catch {
    ElMessage.error('Por favor, corrija los errores en el formulario');
    return;
  }

  loading.value = true;
  try {
    const payload: any = {
      name: form.value.name,
      description: form.value.description,
      is_active: !!form.value.is_active,
      related_model_id: form.value.related_model_id,
      roles: form.value.roles || [],
    };

    if (props.mode === 'create') {
      await store.createItem(payload);
    } else if (props.mode === 'edit' && form.value.id) {
      await store.updateItem(form.value.id, payload);
    }

    ElMessage.success(props.mode === 'create' ? '{Module} creado correctamente' : '{Module} actualizado correctamente');
    emit('update:modelValue', false);
    emit('saved');
  } catch (e: any) {
    console.error(e);
    if (e.response?.status === 422 && e.response?.data?.errors) {
      const errors = e.response.data.errors;
      const errorMessages = Object.values(errors).flat().join('\n');
      ElMessage.error(errorMessages || 'Error de validación');
    } else {
      ElMessage.error('Error al guardar el {module}');
    }
  } finally {
    loading.value = false;
  }
}
</script>
```

### 4. Router Update (router/index.ts)

```typescript
{
  path: '{modules}',
  name: '{modules}',
  component: () => import('@/views/{module}/{Module}View.vue'),
},
```

---

## Patrones Comunes

### Paginación

- **Backend**: Usa el trait `FiltersPaginatedResults` con `defaultPageSize: 10`
- **Frontend**: Usa `el-pagination` con page-sizes `[10, 25, 50, 100]`
- **Store**: Maneja estado de paginación en `pagination.value`

### Filtros

- **Búsqueda general**: Se activa a partir de 10 caracteres
- **Filtros específicos**: Usan `el-select` con `teleported="false"` para evitar cierre del popover
- **Popover de filtros**: No se cierra al seleccionar, solo al hacer clic fuera o en el botón
- **Badge**: Muestra cantidad de filtros activos en el botón de filtros

### Ordenamiento

- **Backend**: Define `$sortable` array en el controller
- **Frontend**: Usa `@sort-change` en `el-table` con `sortable="custom"`
- **Store**: Envía `sort_by` y `sort_order` en el payload

### Acciones Rápidas

- **Toggle estado**: Botón con icono Power/PowerOff, requiere confirmación
- **Editar**: Abre modal en modo 'edit' con datos cargados
- **Eliminar**: Requiere confirmación, usa soft delete si está configurado

### Validaciones

- **Backend**: FormRequest con reglas de validación
- **Frontend**: `el-form` con `:rules` y validación antes de enviar
- **Errores 422**: Se muestran mensajes de validación del backend

### Permisos

- **Backend**: Verifica permisos con `hasPermission()` en cada método del controller
- **Frontend**: Maneja errores 403 con mensaje específico

### Loading States

- **Tabla**: `v-loading="store.loading"` en `el-table`
- **Modal**: `v-loading="loading"` con `element-loading-text` en el formulario
- **Botones**: `:loading` en botones de acción

---

## Componentes UI Reutilizables

### BaseModal

Modal genérico con:
- Header con icono, título y subtítulo
- Botón de cerrar
- Footer configurable (modo 'form' o 'close-only')
- Props: `title`, `subtitle`, `icon`, `width`, `loading`, `mode`, `cancelText`, `confirmText`, `confirmIcon`
- Slots: default (body), footer

### ContentCard

Card contenedor con:
- Título y subtítulo
- Slot para acciones (botones)
- Slot para contenido principal

### StatusPill

Badge de estado con:
- Props: `type` ('green', 'red', 'yellow', 'blue')
- Muestra texto con color de fondo correspondiente

---

## Checklist para Crear un Nuevo Módulo

### Backend
- [ ] Crear migration
- [ ] Crear model con relaciones
- [ ] Crear StoreRequest
- [ ] Crear UpdateRequest
- [ ] Crear Resource
- [ ] Crear Controller con trait FiltersPaginatedResults
- [ ] Agregar rutas en api.php
- [ ] Ejecutar migration
- [ ] Configurar permisos

### Frontend
- [ ] Crear carpeta `views/{module}/`
- [ ] Crear store `stores/{module}.ts`
- [ ] Crear vista principal `{Module}View.vue`
- [ ] Crear modal de formulario `{Module}FormDialog.vue`
- [ ] Agregar ruta en router/index.ts
- [ ] Probar CRUD completo
- [ ] Verificar filtros y paginación
- [ ] Verificar validaciones

---

## Notas Importantes

1. **CSRF**: Todas las peticiones POST requieren llamar `/sanctum/csrf-cookie` primero
2. **Headers**: Usar `{ headers: { 'X-Skip-Auth-Redirect': '1' } }` en peticiones API
3. **Relaciones**: Cargar relaciones con `with()` en backend y `whenLoaded()` en Resource
4. **Soft Deletes**: Considerar usar soft deletes para datos importantes
5. **Roles**: Si el módulo tiene roles, usar syncRoles() en create/update
6. **Iconos**: Usar iconos de `@lucide/vue` para consistencia
7. **Colores**: Usar variables CSS definidas en `resources/css/app.scss`
8. **Responsivo**: Modal se ajusta según tamaño de pantalla con `useWindowSize()`
