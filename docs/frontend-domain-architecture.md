# Arquitectura Frontend por Dominios

Este documento define cómo estructurar el frontend Vue 3 para que refleje los
dominios del backend (`app/Domain/*`). El objetivo es que cualquier persona
(o IA) que conozca la organización del backend pueda ubicarse en el frontend
sin esfuerzo adicional, sin tocar el estilo visual actual (colores, tipografía,
componentes Element Plus) definido en `resources/js/assets/element-variables.scss`
y `resources/css/app.scss`.

**No se cambia ningún color, fuente, radio de borde ni componente UI existente.**
Solo se reorganiza dónde vive el código nuevo.

---

## 1. Diagnóstico del estado actual

Hoy `resources/js/views` mezcla dos tipos de módulos:

```
resources/js/views/
  DashboardView.vue          # núcleo de la app, no es un dominio de negocio
  LoginView.vue               # auth, no es un dominio de negocio
  ForgotPasswordView.vue
  ResetPasswordView.vue
  users/                       # núcleo (gestión de usuarios/roles/permisos)
  roles/
  settings/
  profile/
```

Esto funcionó bien mientras el backend solo tenía `Users`, `Roles` y `Settings`.
Pero el backend ya creció a dominios de negocio reales bajo `app/Domain/`:

```
app/Domain/
  Organization/     -> Company, Branch, Department, Position, Process
  Workflow/          -> Workflow, WorkflowState, WorkflowTransition, WorkflowHistory
  Configuration/     -> Parameter, Status, Priority, NotificationTemplate
  Cases/             -> ManagementCase, ActionPlan, Action, FollowUp,
                        RootCauseAnalysis, EffectivenessReview, Verification,
                        catálogos (ActionPlanType, Source, GutLevel,
                        PriorityThreshold, ControlEntity, Insurer),
                        polimórficas (Comment, Attachment, Evidence)
```

Si seguimos agregando vistas sueltas en `resources/js/views/`, el frontend se
vuelve plano e inconsistente con el backend. La recomendación es introducir
una carpeta `resources/js/domains/` que refleje 1 a 1 los dominios de
`app/Domain/`.

---

## 2. Estructura recomendada

```
resources/js/
  views/                        # Núcleo transversal (NO son dominios de negocio)
    DashboardView.vue
    LoginView.vue
    ForgotPasswordView.vue
    ResetPasswordView.vue
    users/
    roles/
    settings/
    profile/

  domains/                      # Refleja app/Domain/* del backend
    organization/
      views/
        OrganizationView.vue   # contenedor con tabs verticales
      components/
        CompaniesTab.vue
        BranchesTab.vue
        DepartmentsTab.vue
        PositionsTab.vue
        ProcessesTab.vue
      services/
        organization.api.ts    # llamadas Axios crudas del dominio
      stores/
        companies.ts
        branches.ts
        departments.ts
        positions.ts
        processes.ts
      types.ts                 # interfaces TypeScript del dominio

    workflow/
      views/
        WorkflowsView.vue
        WorkflowStatesView.vue
        WorkflowTransitionsView.vue
        WorkflowHistoryView.vue
      components/
        WorkflowFormDialog.vue
        WorkflowStateFormDialog.vue
        WorkflowTransitionFormDialog.vue
      services/
        workflow.api.ts
      stores/
        workflows.ts
      types.ts

    configuration/
      views/
        ParametersView.vue
        StatusesView.vue
        PrioritiesView.vue
        NotificationTemplatesView.vue
      components/
      services/
        configuration.api.ts
      stores/
      types.ts

    cases/
      views/
        ManagementCasesView.vue
        ManagementCaseDetailView.vue   # vista maestro-detalle con tabs
        catalogs/
          ActionPlanTypesView.vue
          SourcesView.vue
          GutLevelsView.vue
          PriorityThresholdsView.vue
          ControlEntitiesView.vue
          InsurersView.vue
      components/
        ActionPlanTab.vue
        ActionsTab.vue
        FollowUpsTab.vue
        RootCauseAnalysisTab.vue
        EffectivenessReviewTab.vue
        VerificationTab.vue
        CommentsPanel.vue        # reutilizable (polimórfico)
        AttachmentsPanel.vue     # reutilizable (polimórfico)
        EvidencesPanel.vue       # reutilizable (polimórfico)
      services/
        cases.api.ts
        case-catalogs.api.ts
      stores/
        managementCases.ts
        actionPlans.ts
      types.ts

  router/
    index.ts                    # importa vistas desde domains/ y views/
```

### Por qué `domains/` y no meter todo en `views/`

- **Espejo directo del backend**: `app/Domain/Cases` ⇄ `resources/js/domains/cases`.
  Buscar "dónde está Cases" es igual en ambos lados.
- **Encapsulamiento**: un dominio no debería importar componentes internos de
  otro dominio. Solo puede reutilizar `components/ui/*` (el kit UI global) o
  `composables/*`.
- **Escalabilidad**: cuando se agregue `Documents`, `Risks`, `Audits`,
  `NonConformities` o `Committees` (ver `docs/SIG/2. DOMAIN_MODEL.md`), el
  patrón ya está definido: crear `resources/js/domains/{dominio}/` con la
  misma subestructura.

### Qué se queda en `views/` (núcleo, no dominio)

`Dashboard`, `Auth` (login/forgot/reset password), `Users`, `Roles`,
`Settings`, `Profile` no están bajo `app/Domain/` en el backend (son
transversales/plataforma), así que se quedan igual en `resources/js/views/`.
No hay que migrarlos.

---

## 3. Convenciones dentro de cada dominio

### `services/{dominio}.api.ts`

Centraliza las llamadas Axios crudas (sin estado). Los stores Pinia consumen
este archivo. Ejemplo para `organization`:

```ts
// resources/js/domains/organization/services/organization.api.ts
import http from '@/plugins/axios';
import type { Company, Branch } from '../types';

export const companiesApi = {
  getAll: (payload: Record<string, unknown> = {}) =>
    http.post('/api/companies/get-all', payload),
  get: (id: number) => http.get<{ data: Company }>(`/api/companies/${id}`),
  create: (payload: Partial<Company>) => http.post('/api/companies', payload),
  update: (id: number, payload: Partial<Company>) =>
    http.put(`/api/companies/${id}`, payload),
  remove: (id: number) => http.delete(`/api/companies/${id}`),
};
```

### `stores/{entidad}.ts`

Sigue el mismo patrón que `resources/js/stores/users.ts` (loading, paginación,
`get-all`), pero importa desde `services/` del dominio en vez de llamar Axios
directamente:

```ts
// resources/js/domains/organization/stores/companies.ts
import { defineStore } from 'pinia';
import { ref } from 'vue';
import { companiesApi } from '../services/organization.api';
import type { Company } from '../types';

export const useCompaniesStore = defineStore('organization.companies', () => {
  const companies = ref<Company[]>([]);
  const loading = ref(false);
  const pagination = ref({ current_page: 1, per_page: 10, total: 0, last_page: 1 });

  async function loadCompanies(payload: Record<string, unknown> = {}) {
    loading.value = true;
    try {
      const { data } = await companiesApi.getAll({
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
        ...payload,
      });
      companies.value = data.data?.data ?? [];
      pagination.value = {
        current_page: data.data?.current_page ?? 1,
        per_page: data.data?.per_page ?? 10,
        total: data.data?.total ?? 0,
        last_page: data.data?.last_page ?? 1,
      };
    } finally {
      loading.value = false;
    }
  }

  return { companies, loading, pagination, loadCompanies };
});
```

Nombrar el store con prefijo de dominio (`'organization.companies'`,
`'cases.actionPlans'`) evita colisiones de ids Pinia entre dominios.

### `types.ts`

Define las interfaces TypeScript que reflejan los API Resources del backend.
Ejemplo:

```ts
// resources/js/domains/organization/types.ts
export interface Company {
  id: number;
  uuid: string;
  code: string;
  name: string;
  tax_id: string | null;
  is_active: boolean;
}

export interface Branch {
  id: number;
  company_id: number;
  code: string;
  name: string;
  is_active: boolean;
}
```

### `views/`

Las vistas siguen exactamente el patrón ya documentado en
`docs/frontend-module-guide.md` (`ContentCard`, `el-table`, `el-pagination`,
iconos Lucide, permisos `v-permission`). Lo único que cambia es la ubicación
del archivo y que las llamadas pasan por el store del dominio en vez de Axios
directo.

### `components/`

Diálogos de creación/edición (`*FormDialog.vue`) y widgets específicos del
dominio. **Obligatorio**: usar `BaseModal.vue` / `BaseDrawer.vue` del kit UI
global (`resources/js/components/ui/`) para cualquier modal o drawer. Nunca
usar `el-dialog` o `el-drawer` directamente. Esto garantiza consistencia
visual (header azul, iconos, botones de cancelar/guardar) en toda la app.

#### `BaseModal.vue`

Props principales: `modelValue`, `title`, `subtitle`, `icon`, `width`,
`loading`, `mode` (`'form'` | `'close-only'`), `confirmText`, `cancelText`.

Emits: `update:modelValue`, `confirm`, `cancel`, `closed`.

Slots: `default` (cuerpo), `footer` (opcional, reemplaza botones por defecto).

#### `BaseDrawer.vue`

Mismas props y emits que `BaseModal` pero usa `size` en vez de `width`.

#### Regla

```vue
<!-- CORRECTO -->
<BaseModal v-model="dialogVisible" title="Nuevo parámetro" :loading="saving" @confirm="save">
  <el-form>...</el-form>
</BaseModal>

<!-- INCORRECTO -->
<el-dialog v-model="dialogVisible" title="Nuevo parámetro">
  ...
</el-dialog>
```

Reutilizan siempre `ContentCard.vue` / `StatusPill.vue` / `CrudTable.vue`
del kit UI global, nunca los duplican.

#### Botones de acción en tablas

Todos los botones de acción dentro de `el-table` deben usar **icon buttons con
tooltips**, no `el-button` ni `el-button-group`. El patrón es:

```vue
<el-table-column label="ACCIONES" width="100" align="center" fixed="right">
  <template #default="{ row }">
    <div class="flex items-center justify-center gap-0.5">
      <el-tooltip v-permission="'entity.edit'" content="Editar" placement="top">
        <button
          type="button"
          class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors cursor-pointer"
          @click="openEdit(row)"
        >
          <EditIcon class="w-4 h-4" />
        </button>
      </el-tooltip>
      <el-tooltip v-permission="'entity.delete'" content="Eliminar" placement="top">
        <button
          type="button"
          class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer"
          @click="handleDelete(row)"
        >
          <TrashIcon class="w-4 h-4" />
        </button>
      </el-tooltip>
    </div>
  </template>
</el-table-column>
```

Reglas de estilo por acción:

- **Editar**: `hover:bg-blue-50 hover:text-blue-600`, icono `Edit`
- **Eliminar**: `hover:bg-red-50 hover:text-red-600`, icono `Trash`
- **Activar/Desactivar**: `hover:bg-amber-50 hover:text-amber-600` (desactivar) o `hover:bg-green-50 hover:text-green-600` (activar), icono `Power`/`PowerOff`
- **Cambiar contraseña**: `hover:bg-purple-50 hover:text-purple-600`, icono `KeyRound`
- **Ver detalle**: `hover:bg-blue-50 hover:text-blue-600`, icono `Eye`

Todas las acciones usan `v-permission` en el `el-tooltip` (no en el botón)
para controlar visibilidad. El botón base es siempre `w-8 h-8` con icono
`w-4 h-4`, `text-gray-500` y `transition-colors`.

#### Paginación obligatoria en tablas

Toda tabla de un dominio debe usar paginación del servidor con el mismo patrón
visual de `UsersView.vue`: total a la izquierda y `el-pagination` a la derecha.
No se permite cargar arbitrariamente `per_page: 100` para simular una tabla
completa ni paginar un arreglo únicamente en el navegador.

Cuando se usa `CrudTable.vue`, se deben entregar acciones CRUD asíncronas,
`pagination` y los eventos remotos:

```vue
<CrudTable
  :rows="store.items"
  :pagination="store.pagination"
  total-label="registros"
  :create-action="store.create"
  :update-action="store.update"
  :delete-action="store.remove"
  @page-change="store.changePage"
  @size-change="store.changePageSize"
  @search-change="store.changeSearch"
/>
```

Las props `create-action`, `update-action` y `delete-action` deben devolver una
`Promise<void>` que resuelva únicamente cuando la API y la recarga necesaria
terminen correctamente. Si la API falla, la promesa debe rechazarse; el store
no debe capturar y silenciar el error.

`CrudTable` mantiene `BaseModal` abierta y en estado de carga mientras espera la
acción. La modal se cierra únicamente cuando la promesa resuelve. Si rechaza,
permanece abierta, conserva los datos diligenciados y muestra primero los
errores de validación enviados por el backend. Mientras `loading` sea verdadero,
`BaseModal` tampoco puede cerrarse con Cancelar, la X o la tecla Escape.

No se deben usar eventos `@create`/`@update` para operaciones que necesiten ser
esperadas: `emit()` de Vue no retorna la promesa del listener y produciría un
cierre prematuro de la modal.

El estado mínimo de paginación del store es:

```ts
{
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
}
```

Reglas frontend:

- `page-sizes` estándar: `[10, 25, 50, 100]`.
- Cambiar el tamaño reinicia `current_page` a `1`.
- Cambiar la búsqueda reinicia `current_page` a `1`.
- La búsqueda remota usa debounce de 300 ms.
- Después de eliminar el último registro de una página distinta de la primera,
  se retrocede una página antes de recargar.
- Los catálogos usados por selects deben cargarse desde un endpoint `get-all`
  separado; nunca deben depender solamente de la página visible de la tabla.

Contrato backend requerido para cada tabla paginada:

- Endpoint de índice que acepte `page`, `per_page` y `search`.
- El servicio debe devolver `LengthAwarePaginator`.
- El repositorio debe aplicar filtros antes de llamar `paginate($perPage)`.
- La respuesta debe conservar los metadatos Laravel `current_page`, `per_page`,
  `total` y `last_page` (directos o dentro de `meta`, según el Resource).
- El endpoint `get-all` queda reservado para opciones de selects y relaciones,
  no para renderizar tablas administrativas.

El dominio Organization ya cumple este contrato en backend; sus cinco tablas
usan el helper `stores/createOrganizationCrudStore.ts` para mantener una sola
implementación de paginación, búsqueda y recarga CRUD.

---

## 4. Caso especial: `Cases` (maestro-detalle)

El dominio `Cases` es el núcleo funcional del SIG (ver
`docs/SIG/2. DOMAIN_MODEL.md`). Su patrón de UI recomendado por la
documentación de dominio es **una sola vista con pestañas**, no pantallas
separadas por entidad relacionada:

```
domains/cases/views/ManagementCaseDetailView.vue
  <el-tabs>
    <el-tab-pane label="Plan de Acción">      -> components/ActionPlanTab.vue
    <el-tab-pane label="Actividades">          -> components/ActionsTab.vue
    <el-tab-pane label="Seguimientos">         -> components/FollowUpsTab.vue
    <el-tab-pane label="Análisis de Causa">    -> components/RootCauseAnalysisTab.vue (solo si action_plan_type.requires_root_cause_analysis)
    <el-tab-pane label="Revisión de Eficacia">  -> components/EffectivenessReviewTab.vue (solo si requires_effectiveness_review)
    <el-tab-pane label="Verificación">          -> components/VerificationTab.vue
```

Las pestañas se habilitan/deshabilitan según el estado del Workflow del Plan
(consultar `current_state_id` vía el dominio `workflow`).

Los paneles polimórficos (`CommentsPanel.vue`, `AttachmentsPanel.vue`,
`EvidencesPanel.vue`) se implementan una sola vez en `domains/cases/components/`
y se reutilizan pasando `commentable_type` / `attachable_type` /
`evidenceable_type` + el id como props, ya que el backend expone los mismos
endpoints (`/api/comments`, `/api/attachments`, `/api/evidences`) para
cualquier entidad.

---

## 4b. Patrón maestro-detalle: dominio Workflow

El dominio Workflow tiene una jerarquía natural de tres niveles:

```
Workflow → Estados (WorkflowState) → Transiciones (WorkflowTransition)
```

Donde las transiciones referencian dos estados (origen y destino) del mismo
workflow. Esto hace que el CRUD simple por pestañas (como Organization) sea
insuficiente, porque las transiciones dependen de que los estados ya existan.

### Diseño de UI: lista → detalle con pestañas

La vista `WorkflowView.vue` implementa un patrón **maestro-detalle en una sola
ruta** (`/workflows`), sin navegación a rutas hijas:

1. **Modo lista**: `CrudTable` estándar con los workflows paginados. Cada fila
   tiene un botón "Configurar" (slot `#row-actions` de `CrudTable`) que
   selecciona el workflow y cambia al modo detalle.

2. **Modo detalle**: Header con botón de retroceso, nombre del workflow y
   badge de estado. Debajo, `el-tabs` con dos pestañas:
   - **Estados**: `StatesManager.vue` — tabla inline con CRUD propio (crear,
     editar, eliminar estados). Cada estado tiene código, nombre, color, orden,
     y flags `is_initial`/`is_final`.
   - **Transiciones**: `TransitionsManager.vue` — tabla inline con CRUD propio.
     Cada transición muestra el flujo visual `Estado A → Estado B` usando
     `StateBadge` (render function con color del estado). Los selects de
     origen/destino se alimentan de los estados ya creados.

### Reglas del patrón

- **No hay ruta hija**: el detalle se maneja con `ref<Workflow | null>` en la
  vista. Al retroceder se recarga la lista.
- **Stores separados por entidad**: `workflows`, `workflowStates`,
  `workflowTransitions`, cada uno con Pinia `defineStore`.
- **Los stores de estados y transiciones reciben `workflowId` en `load()`**,
  no en el constructor, porque son dependientes del workflow seleccionado.
- **El botón "Configurar" respetar el permiso `workflows.edit`**.
- **Las transiciones se deshabilitan si hay menos de 2 estados** (mensaje
  informativo en lugar de botón activo).
- **`CrudTable` tiene un slot `#row-actions`** para acciones personalizadas
  antes de Editar/Eliminar, y un prop `actionWidth` para ensanchar la columna
  cuando hay acciones extra.
- **`StateBadge`** es una render function inline en `TransitionsManager.vue`
  que muestra el nombre del estado con su color de fondo. No es un componente
  reutilizable global porque es específico del dominio.

### Estructura de archivos

```
domains/workflow/
  types.ts
  services/workflow.api.ts
  stores/workflows.ts
  stores/workflowStates.ts
  stores/workflowTransitions.ts
  components/StatesManager.vue
  components/TransitionsManager.vue
  views/WorkflowView.vue
```

### Contrato backend

- `GET /api/workflows` — lista paginada con `states` eager loaded.
- `GET /api/workflows/{id}` — detalle con `states`, `transitions.fromState`,
  `transitions.toState` eager loaded.
- `GET /api/workflow-states?workflow_id=X` — lista de estados del workflow.
- `GET /api/workflow-transitions?workflow_id=X` — lista de transiciones con
  `fromState` y `toState` eager loaded.
- Permisos: `workflows.view`, `workflows.create`, `workflows.edit`,
  `workflows.delete` (compartidos para estados y transiciones).

---

## 5. Mapeo de endpoints por dominio (referencia rápida)

| Dominio | Rutas backend | Carpeta frontend |
|---|---|---|
| Organization | `/api/companies`, `/api/branches`, `/api/departments`, `/api/positions`, `/api/processes` | `domains/organization` |
| Workflow | `/api/workflows`, `/api/workflow-states`, `/api/workflow-transitions`, `/api/workflow-histories` | `domains/workflow` |
| Configuration | `/api/parameters`, `/api/statuses`, `/api/priorities`, `/api/notification-templates` | `domains/configuration` |
| Cases | `/api/cases`, `/api/action-plans`, `/api/actions`, `/api/follow-ups`, `/api/actions/{id}/participants` | `domains/cases` |
| Cases (secundarias) | `/api/root-cause-analyses`, `/api/effectiveness-reviews`, `/api/verifications` | `domains/cases` |
| Cases (catálogos) | `/api/case-catalogs/*` | `domains/cases/views/catalogs` |
| Cases (polimórficas) | `/api/comments`, `/api/attachments`, `/api/evidences` | `domains/cases/components` |

Todas usan `auth:sanctum` + `active` (ver `routes/domains/*.php`).

---

## 6. Router

`resources/js/router/index.ts` sigue siendo el único archivo de rutas, pero
las importaciones apuntan a `domains/*`:

```ts
{
  path: 'organizacion/empresas',
  name: 'organization.companies',
  component: () => import('@/domains/organization/views/CompaniesView.vue'),
  meta: { permissions: ['companies.view'] },
},
{
  path: 'workflow/workflows',
  name: 'workflow.workflows',
  component: () => import('@/domains/workflow/views/WorkflowsView.vue'),
  meta: { permissions: ['workflows.view'] },
},
{
  path: 'casos',
  name: 'cases.list',
  component: () => import('@/domains/cases/views/ManagementCasesView.vue'),
  meta: { permissions: ['cases.view'] },
},
{
  path: 'casos/:id',
  name: 'cases.detail',
  component: () => import('@/domains/cases/views/ManagementCaseDetailView.vue'),
  meta: { permissions: ['cases.view'] },
},
```

Si se agrupan muchas rutas por dominio, se puede extraer cada bloque a
`resources/js/domains/{dominio}/routes.ts` y hacer `import` en
`router/index.ts`, igual que el backend agrupa rutas en `routes/domains/*.php`.
Esto es opcional y solo se recomienda cuando un dominio supere ~5 rutas.

---

## 7. Sidebar

`AppSidebar.vue` puede agrupar por dominio usando submenús Element Plus
(`el-sub-menu`), reflejando la misma agrupación:

```vue
<el-sub-menu index="organization">
  <template #title>Organización</template>
  <AppSidebarItem to="/organizacion/empresas" icon="Building2" text="Empresas" permission="companies.view" />
  <AppSidebarItem to="/organizacion/sedes" icon="MapPin" text="Sedes" permission="branches.view" />
  ...
</el-sub-menu>

<el-sub-menu index="cases">
  <template #title>Gestión de Casos</template>
  <AppSidebarItem to="/casos" icon="FolderKanban" text="Casos" permission="cases.view" />
</el-sub-menu>

<el-sub-menu index="workflow">
  <template #title>Workflow</template>
  <AppSidebarItem to="/workflow/workflows" icon="Workflow" text="Flujos" permission="workflows.view" />
</el-sub-menu>
```

No se cambia el look del sidebar actual (colores, ancho, comportamiento móvil
descrito en `docs/frontend-app-architecture.md`), solo se agrupa el contenido.

---

## 8. Migración incremental (no romper lo existente)

No es necesario mover nada de golpe. Regla práctica:

1. **Todo módulo nuevo** que corresponda a un `app/Domain/*` del backend se
   crea directamente en `resources/js/domains/{dominio}/`.
2. **Los módulos núcleo existentes** (`users`, `roles`, `settings`, `profile`,
   auth, dashboard) se quedan en `views/` tal cual están; no se tocan.
3. Si en el futuro se decide mover `users`/`roles` a un dominio propio del
   backend, se migran junto con ese cambio de backend, no antes.

---

## 9. Checklist para un módulo de dominio nuevo

- [ ] Existe `resources/js/domains/{dominio}/` con `views/`, `components/`,
      `services/`, `stores/`, `types.ts`.
- [ ] `types.ts` refleja los campos reales del API Resource del backend.
- [ ] `services/{dominio}.api.ts` centraliza las llamadas Axios (sin lógica
      de estado).
- [ ] El store Pinia usa `services/` del dominio, no Axios directo, y tiene
      id con prefijo (`'{dominio}.{entidad}'`).
- [ ] Las vistas usan `ContentCard`, `el-table`, `el-pagination`, iconos
      Lucide, igual que `docs/frontend-module-guide.md`.
- [ ] Los formularios usan `BaseModal.vue`/`BaseDrawer.vue` existentes.
- [ ] Las rutas están registradas en `router/index.ts` con `meta.permissions`.
- [ ] El sidebar agrupa el dominio en su propio `el-sub-menu`.
- [ ] No se modificó ningún color, variable Sass o componente UI global.
- [ ] No quedan datos mock ni `TODO: Implementar API`.
- [ ] Los `BaseModal` incluyen prop `:icon` con un icono Lucide apropiado
      (ver sección 11).

---

## 11. Iconos en BaseModal

`BaseModal.vue` acepta una prop `:icon` que recibe un componente de icono
Lucide. Esto mejora la identificación visual de cada modal y mantiene
consistencia con el resto de la UI.

### Patrón

1. Importar los iconos necesarios desde `@lucide/vue` en el `<script setup>`:

```vue
<script setup lang="ts">
import {
  Plus as PlusIcon,
  Pencil as PencilIcon,
} from '@lucide/vue';
</script>
```

2. Pasar el icono al `BaseModal` mediante `:icon`:

```vue
<BaseModal
  v-model="showModal"
  :title="editingId ? 'Editar Entidad' : 'Nueva Entidad'"
  :icon="editingId ? PencilIcon : PlusIcon"
  @confirm="save"
  @cancel="showModal = false"
>
  <!-- contenido del modal -->
</BaseModal>
```

### Convención de iconos por operación

| Operación | Icono sugerido | Import Lucide |
|---|---|---|
| Crear genérico | `Plus` | `Plus as PlusIcon` |
| Editar genérico | `Pencil` | `Pencil as PencilIcon` |
| Crear usuario | `UserPlus` | `UserPlus as UserPlusIcon` |
| Editar usuario | `UserCog` | `UserCog as UserCogIcon` |
| Crear caso/plan | `ClipboardList` | `ClipboardList as ClipboardListIcon` |
| Crear acción | `ListChecks` | `ListChecks as ListChecksIcon` |
| Editar acción | `ClipboardList` | `ClipboardList as ClipboardListIcon` |
| Crear seguimiento | `ClipboardCheck` | `ClipboardCheck as ClipboardCheckIcon` |
| Editar seguimiento | `LineChart` | `LineChart as LineChartIcon` |
| Crear análisis causa raíz | `GitBranch` | `GitBranch as GitBranchIcon` |
| Editar análisis causa raíz | `Search` | `Search as SearchIcon` |
| Crear revisión eficacia | `CheckCircle2` | `CheckCircle2 as CheckCircle2Icon` |
| Editar revisión eficacia | `TrendingUp` | `TrendingUp as TrendingUpIcon` |
| Crear verificación | `BadgeCheck` | `BadgeCheck as BadgeCheckIcon` |
| Editar verificación | `ShieldCheck` | `ShieldCheck as ShieldCheckIcon` |
| Crear comentario | `MessageSquare` | `MessageSquare as MessageSquareIcon` |
| Crear adjunto | `FolderPlus` | `FolderPlus as FolderPlusIcon` |
| Crear evidencia | `FilePlus` | `FilePlus as FilePlusIcon` |
| Crear estado (workflow) | `Plus` | `Plus as PlusIcon` |
| Editar estado (workflow) | `CircleDot` | `CircleDot as CircleDotIcon` |
| Crear transición (workflow) | `Plus` | `Plus as PlusIcon` |
| Editar transición (workflow) | `GitMerge` | `GitMerge as GitMergeIcon` |

### Reglas

- **Siempre** usar el alias `as {Nombre}Icon` (ej: `Plus as PlusIcon`) para
  mantener consistencia con el resto del código.
- Para modales de solo creación (sin edición), pasar un único icono fijo.
- Para modales create/edit, usar un ternario basado en `editingId` o
  `dialogType` para alternar entre el icono de crear y el de editar.
- El icono es opcional en `BaseModal` (no rompe si no se pasa), pero es
  obligatorio en todos los modales de dominio.

---

## 12. Relación con la documentación existente

Este documento complementa, no reemplaza:

- `docs/frontend-app-architecture.md` — estado general del frontend, stack,
  autenticación, layout.
- `docs/frontend-module-guide.md` — cómo se ve y se comporta una vista CRUD
  individual (patrón de tabla, formulario, paginación).
- `docs/frontend-permission-system.md` — cómo aplicar permisos en vistas,
  botones y rutas.
- `docs/SIG/2. DOMAIN_MODEL.md` — definición funcional de cada dominio de
  negocio (qué entidades y reglas tiene cada uno).
- `docs/SIG/4. LARAVEL_ARCHITECTURE.md` — estructura de dominios en el
  backend (`app/Domain/*`), que este documento refleja en el frontend.
