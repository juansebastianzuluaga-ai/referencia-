# Guia para Crear un Nuevo Modulo Frontend

Este documento describe como crear un nuevo modulo frontend en la SPA Vue 3 de
este proyecto. El objetivo es replicar el patron existente sin inventar una
estructura nueva.

## 1. Convenciones generales

- Usar Vue 3 con Composition API.
- Usar `<script setup lang="ts">`.
- Usar el alias `@` para imports desde `resources/js`.
- Usar Element Plus para formularios, tablas, modales, paginacion y controles.
- Usar Lucide para iconos de acciones.
- Usar los componentes UI locales antes de crear nuevos:
  - `ContentCard`
  - `StatCard`
  - `StatusPill`
- Mantener las vistas en `resources/js/views`.
- Si el modulo pertenece a un dominio especifico, crear subcarpeta:

```
resources/js/views/censo/NuevoModuloView.vue
```

## 2. Relacion con los endpoints backend

Los modulos backend siguen este patron:

```
POST   /api/{modulo}/get-all
POST   /api/{modulo}
GET    /api/{modulo}/{id}
PUT    /api/{modulo}/{id}
PATCH  /api/{modulo}/{id}
DELETE /api/{modulo}/{id}
```

El listado siempre debe usar `POST /api/{modulo}/get-all`, porque el backend
acepta filtros complejos en el body.

Parametros comunes para listados:

```ts
{
  general?: string;
  per_page?: number;
  page?: number;
  sort_by?: string;
  sort_direction?: 'asc' | 'desc';
  [filter: string]: unknown;
}
```

La respuesta backend estandar tiene esta forma:

```json
{
  "code": 200,
  "success": true,
  "message": "Consulta correcta",
  "data": {}
}
```

Cuando el backend devuelve paginacion Laravel, los registros suelen estar en:

```ts
response.data.data.data
```

y la metadata en:

```ts
response.data.data.current_page
response.data.data.per_page
response.data.data.total
```

Verifica la respuesta real del endpoint antes de fijar nombres.

## 3. Crear la vista

Crear una vista con nombre PascalCase y sufijo `View.vue`.

Ejemplo:

```
resources/js/views/ProductsView.vue
```

Estructura base recomendada:

```vue
<template>
  <ContentCard title="Productos" subtitle="Gestion de productos del sistema">
    <template #actions>
      <el-button type="primary" :icon="PlusIcon" @click="openCreateDialog">
        Nuevo producto
      </el-button>
    </template>

    <div class="flex items-center gap-2.5 flex-wrap bg-gray-50 p-2.5 rounded-lg border border-gray-200 mb-4">
      <el-input
        v-model="filters.general"
        placeholder="Buscar..."
        :prefix-icon="SearchIcon"
        class="w-full md:w-80"
        clearable
        @keyup.enter="fetchRows"
      />

      <el-button :icon="RefreshCwIcon" @click="fetchRows">
        Actualizar
      </el-button>
    </div>

    <el-table
      :data="rows"
      v-loading="loading"
      style="width: 100%"
      class="border border-gray-200 rounded-lg"
    >
      <el-table-column prop="code" label="CODIGO" width="140" />
      <el-table-column prop="name" label="NOMBRE" min-width="220" />
      <el-table-column label="ESTADO" width="120">
        <template #default="{ row }">
          <StatusPill :type="row.is_active ? 'green' : 'red'">
            {{ row.is_active ? 'Activo' : 'Inactivo' }}
          </StatusPill>
        </template>
      </el-table-column>
      <el-table-column label="ACCIONES" width="120" align="center">
        <template #default="{ row }">
          <el-button-group>
            <el-button size="small" :icon="EditIcon" @click="openEditDialog(row)" />
            <el-button size="small" type="danger" :icon="TrashIcon" @click="deleteRow(row)" />
          </el-button-group>
        </template>
      </el-table-column>
    </el-table>

    <div class="mt-4 flex justify-end">
      <el-pagination
        background
        layout="prev, pager, next"
        :current-page="pagination.page"
        :page-size="pagination.per_page"
        :total="pagination.total"
        @current-change="changePage"
      />
    </div>
  </ContentCard>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import {
  Edit as EditIcon,
  Plus as PlusIcon,
  RefreshCw as RefreshCwIcon,
  Search as SearchIcon,
  Trash as TrashIcon,
} from '@lucide/vue';
import ContentCard from '@/components/ui/ContentCard.vue';
import StatusPill from '@/components/ui/StatusPill.vue';
import http from '@/plugins/axios';

interface Product {
  id: number;
  code: string;
  name: string;
  is_active: boolean;
}

const rows = ref<Product[]>([]);
const loading = ref(false);

const filters = reactive({
  general: '',
});

const pagination = reactive({
  page: 1,
  per_page: 10,
  total: 0,
});

async function fetchRows(): Promise<void> {
  loading.value = true;

  try {
    const { data } = await http.post('/api/products/get-all', {
      ...filters,
      page: pagination.page,
      per_page: pagination.per_page,
    });

    rows.value = data.data.data;
    pagination.page = data.data.current_page;
    pagination.per_page = data.data.per_page;
    pagination.total = data.data.total;
  } finally {
    loading.value = false;
  }
}

function changePage(page: number): void {
  pagination.page = page;
  void fetchRows();
}

function openCreateDialog(): void {
  // Abrir modal de creacion.
}

function openEditDialog(row: Product): void {
  // Abrir modal de edicion.
}

async function deleteRow(row: Product): Promise<void> {
  await http.delete(`/api/products/${row.id}`);
  await fetchRows();
}

onMounted(() => {
  void fetchRows();
});
</script>
```

## 4. Registrar la ruta

Editar `resources/js/router/index.ts` y agregar el modulo como hijo de la ruta
que usa `AppLayout.vue`.

```ts
{
  path: 'productos',
  name: 'products',
  component: () => import('@/views/ProductsView.vue'),
  meta: { permission: 'products.view' },
}
```

Cuando se agregue control por permisos, el guard debe validar
`to.meta.permission` con `auth.hasPermission(...)`.

## 5. Agregar entrada al sidebar

Editar `resources/js/components/layout/AppSidebar.vue`.

```vue
<AppSidebarItem
  to="/productos"
  icon="Package"
  text="Productos"
/>
```

Usar nombres de iconos existentes en Lucide. Si el item debe ocultarse por
permiso, usar `auth.hasPermission('products.view')` en el componente o una
estructura de menu centralizada si se refactoriza mas adelante.

## 6. Formularios y modales

Para CRUD, usar:

- `el-dialog` para crear/editar.
- `el-form` y `el-form-item` para campos.
- `el-input`, `el-select`, `el-checkbox`, `el-switch`, `el-date-picker`, etc.
- Estado `dialogVisible`.
- Estado `dialogType: 'create' | 'edit'`.
- Un objeto `form` tipado.

Patron de guardado:

```ts
async function saveRow(): Promise<void> {
  if (dialogType.value === 'create') {
    await http.post('/api/products', form.value);
  } else {
    await http.put(`/api/products/${form.value.id}`, form.value);
  }

  dialogVisible.value = false;
  await fetchRows();
}
```

### Modales responsivas (obligatorio)

Nunca usar `width` fijo en `el-dialog`. Usar `useWindowSize` de `@vueuse/core`
para que el modal se adapte en movil:

```ts
import { computed, ref } from 'vue';
import { useWindowSize } from '@vueuse/core';

const { width: windowWidth } = useWindowSize();
const dialogWidth = computed(() => windowWidth.value < 640 ? '92vw' : '500px');
```

```vue
<el-dialog v-model="dialogVisible" title="..." :width="dialogWidth">
```

Un `width="500px"` fijo rompe el diseno en pantallas menores a 640px.

## 7. Estados minimos esperados

Todo modulo conectado a API debe manejar:

- `loading` para tablas o acciones largas.
- Estado vacio natural de `el-table`.
- Errores 422 de validacion cuando haya formularios.
- Errores 403 cuando el usuario no tenga permiso.
- Paginacion si el endpoint usa `get-all`.
- Filtros sincronizados con el body del endpoint.
- Limpieza del formulario al abrir creacion.
- Precarga del formulario al abrir edicion.

## 8. Permisos frontend

El backend es la fuente de verdad, pero el frontend debe usar permisos para
mejorar la experiencia:

- Ocultar rutas o redirigir si falta permiso.
- Ocultar botones de crear, editar o eliminar.
- Evitar mostrar opciones que fallaran con 403.

Convencion:

```
{modulo}.view
{modulo}.create
{modulo}.update
{modulo}.delete
```

Ejemplo:

```vue
<el-button
  v-if="auth.hasPermission('products.create')"
  type="primary"
  :icon="PlusIcon"
  @click="openCreateDialog"
>
  Nuevo producto
</el-button>
```

## 9. Checklist final

Antes de cerrar un modulo frontend:

- [ ] La vista esta en `resources/js/views` o subcarpeta de dominio.
- [ ] La ruta esta registrada en `router/index.ts`.
- [ ] El sidebar apunta a la ruta correcta.
- [ ] La vista usa `ContentCard`.
- [ ] Las acciones usan iconos Lucide.
- [ ] Las tablas usan `loading`.
- [ ] El listado consume `POST /api/{modulo}/get-all`.
- [ ] La paginacion usa metadata real del backend.
- [ ] Crear/editar/eliminar consumen endpoints reales.
- [ ] Los permisos controlan rutas y acciones visibles.
- [ ] No quedan datos mock en produccion.
- [ ] No quedan `TODO: Implementar API` en el modulo terminado.
- [ ] La pantalla funciona despues de recargar la pagina.

## 10. Verificacion

Para trabajar en frontend:

```bash
pnpm run dev
```

Para compilar assets:

```bash
pnpm run build
```

Si Laravel muestra un error de manifest de Vite, ejecutar `pnpm run build` o
levantar Vite con `pnpm run dev`.
