<template>
  <div class="users-page h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Header ── -->
    <div class="users-header shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="users-header-icon">
        <component :is="UsersIcon" class="w-5 h-5" />
      </div>
      <h1 class="users-header-title">Gestión de Usuarios</h1>
      <div class="users-header-spacer"></div>
      <el-button v-permission="'users.create'" type="primary" size="small" @click="openDialog('create')">
        <component :is="PlusIcon" class="w-3.5 h-3.5 mr-1" />
        Nuevo Usuario
      </el-button>
      <el-button size="small" @click="exportarExcel" :disabled="users.length === 0">
        <component :is="DownloadIcon" class="w-3.5 h-3.5 mr-1" />
        Exportar
      </el-button>
    </div>

    <!-- ── Stat cards ── -->
    <div class="users-stats-bar shrink-0">
      <StatCard variant="pastel" tone="info" label="Total" :value="statTotal" comparacion="Usuarios" :icon="UsersIcon" />
      <StatCard variant="pastel" tone="success" label="Activos" :value="statActivos" comparacion="Cuenta habilitada" :icon="UserCheckIcon" />
      <StatCard variant="pastel" tone="danger" label="Inactivos" :value="statInactivos" comparacion="Suspendidos" :icon="UserXIcon" />
      <StatCard variant="pastel" tone="violet" label="Con roles" :value="statConRoles" comparacion="Asignados" :icon="KeyRoundIcon" />
    </div>

    <!-- ── Filtros ── -->
    <div class="flex items-center gap-3 shrink-0 flex-wrap">
      <el-input
        v-model="search"
        placeholder="Buscar usuario, nombre, correo..."
        class="users-search"
        clearable
        size="small"
      >
        <template #prefix>
          <component :is="SearchIcon" class="w-3.5 h-3.5 text-gray-400" />
        </template>
      </el-input>
      <el-select v-model="roleFilter" placeholder="Rol" class="users-filter" clearable size="small">
        <el-option v-for="r in roles" :key="r.id" :label="r.display_name || r.name" :value="r.id" />
      </el-select>
      <el-select v-model="statusFilter" placeholder="Estado" class="users-filter" clearable size="small">
        <el-option label="Activo" :value="true" />
        <el-option label="Inactivo" :value="false" />
      </el-select>
      <el-button size="small" text @click="clearFilters">
        <component :is="XIcon" class="w-3 h-3 mr-0.5" />
        Limpiar
      </el-button>
    </div>

    <!-- ── Bulk actions bar ── -->
    <Transition name="bulk-slide">
      <div v-if="seleccionadas.size > 0" class="users-bulk-bar shrink-0">
        <span class="text-xs font-bold" style="color:#0D2D6B;">{{ seleccionadas.size }} seleccionado(s)</span>
        <el-button type="success" size="small" @click="bulkActivar(true)">
          <component :is="PowerIcon" class="w-3 h-3 mr-0.5" /> Activar
        </el-button>
        <el-button type="warning" size="small" @click="bulkActivar(false)">
          <component :is="PowerOffIcon" class="w-3 h-3 mr-0.5" /> Inactivar
        </el-button>
        <el-button v-permission="'users.delete'" type="danger" size="small" @click="bulkEliminar">
          <component :is="TrashIcon" class="w-3 h-3 mr-0.5" /> Eliminar
        </el-button>
        <el-button size="small" text @click="seleccionadas.clear()">Limpiar</el-button>
      </div>
    </Transition>

    <!-- ── Tabla ── -->
    <div class="flex-1 overflow-hidden users-table-panel">
      <!-- Loading -->
      <div v-if="usersStore.loading" class="users-table-loading">
        <div v-for="i in 6" :key="i" class="users-table-row-skeleton">
          <div class="shimmer-box" style="width:18px; height:18px; border-radius:4px; flex-shrink:0;"></div>
          <div class="shimmer-box" style="width:34px; height:34px; border-radius:10px; flex-shrink:0;"></div>
          <div class="flex-1 space-y-1.5">
            <div class="shimmer-bar" style="width:35%; height:13px;"></div>
            <div class="shimmer-bar" style="width:22%; height:10px;"></div>
          </div>
          <div class="shimmer-bar" style="width:18%; height:11px;"></div>
          <div class="shimmer-box" style="width:60px; height:22px; border-radius:999px;"></div>
          <div class="shimmer-bar" style="width:12%; height:10px;"></div>
          <div class="shimmer-box" style="width:90px; height:26px; border-radius:6px; flex-shrink:0;"></div>
        </div>
      </div>

      <!-- Vacío -->
      <div v-else-if="users.length === 0" class="users-empty-wrap">
        <div class="users-empty-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-3">
          <component :is="UsersIcon" class="w-7 h-7" />
        </div>
        <p class="font-bold text-sm mb-1" style="color:#0d2d5e;">Sin usuarios</p>
        <p class="text-xs max-w-[260px]" style="color:#64748b;">No se encontraron usuarios con los filtros aplicados.</p>
      </div>

      <!-- Tabla real -->
      <div v-else class="flex flex-col h-full overflow-hidden">
        <div class="overflow-y-auto custom-scrollbar flex-1">
          <table class="users-table">
            <thead class="users-table-thead">
              <tr>
                <th class="users-th users-th-check">
                  <input type="checkbox" :checked="todasSeleccionadas" @change="toggleSeleccionTodas" class="users-checkbox" />
                </th>
                <th class="users-th users-th-user users-th-sortable" @click="toggleSort('full_name')">
                  Usuario
                  <component :is="sortIcon('full_name')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortBy === 'full_name', 'opacity-30': sortBy !== 'full_name' }" />
                </th>
                <th class="users-th users-th-email users-th-sortable" @click="toggleSort('email')">
                  Correo
                  <component :is="sortIcon('email')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortBy === 'email', 'opacity-30': sortBy !== 'email' }" />
                </th>
                <th class="users-th users-th-status">Estado</th>
                <th class="users-th">Último acceso</th>
                <th class="users-th users-th-roles">Roles</th>
                <th class="users-th users-th-actions">Acciones</th>
              </tr>
            </thead>
            <TransitionGroup name="users-row" tag="tbody">
              <tr
                v-for="(u, idx) in users"
                :key="u.id"
                class="users-table-row"
                :class="{ 'users-table-row-selected': seleccionadas.has(u.id), 'users-table-row-resaltada': idsActualizados.has(u.id) }"
              >
                <td class="users-td users-td-check">
                  <input type="checkbox" :checked="seleccionadas.has(u.id)" @change="toggleSeleccion(u.id)" class="users-checkbox" />
                </td>
                <td class="users-td">
                  <div class="users-table-user">
                    <div class="users-table-avatar">
                      {{ userInitials(u) }}
                    </div>
                    <div class="min-w-0">
                      <p class="users-table-name">{{ u.full_name }}</p>
                      <p class="users-table-doc">{{ u.identification_type?.name }} {{ u.identification_number }}</p>
                    </div>
                  </div>
                </td>
                <td class="users-td">
                  <span class="users-table-email" :title="u.email">{{ u.email }}</span>
                </td>
                <td class="users-td">
                  <span class="users-table-status" :class="u.is_active ? 'status-active' : 'status-inactive'">
                    <span class="users-status-dot"></span>
                    {{ u.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="users-td">
                  <span class="users-table-doc">{{ u.last_login_at ? formatFecha(u.last_login_at) : 'Nunca' }}</span>
                </td>
                <td class="users-td">
                  <div class="users-table-roles">
                    <span v-for="role in visibleRoles(u.roles)" :key="role.id" class="users-role-tag">
                      {{ role.display_name || role.name }}
                    </span>
                    <span v-if="u.roles.length > 2" class="users-role-more">+{{ u.roles.length - 2 }}</span>
                  </div>
                </td>
                <td class="users-td">
                  <div class="users-table-actions">
                    <el-tooltip v-permission="'users.update'" :content="u.is_active ? 'Inactivar' : 'Activar'" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <button
                        v-if="!u.is_protected"
                        type="button"
                        class="users-action-btn"
                        :class="u.is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-green-600 hover:bg-green-50'"
                        @click="toggleUserStatus(u)"
                      >
                        <component :is="u.is_active ? PowerOffIcon : PowerIcon" class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.update'" content="Editar" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <button type="button" class="users-action-btn text-blue-600 hover:bg-blue-50" @click="openDialog('edit', u)">
                        <EditIcon class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.update'" content="Cambiar contraseña" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <button
                        v-if="!u.is_protected"
                        type="button"
                        class="users-action-btn text-purple-600 hover:bg-purple-50"
                        @click="openPasswordDialog(u)"
                      >
                        <KeyRoundIcon class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.view'" content="Ver historial" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <button type="button" class="users-action-btn text-slate-600 hover:bg-slate-50" @click="openHistorialDialog(u)">
                        <HistoryIcon class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.delete'" content="Eliminar" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <button
                        v-if="!u.is_protected"
                        type="button"
                        class="users-action-btn text-red-600 hover:bg-red-50"
                        @click="deleteUser(u)"
                      >
                        <TrashIcon class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                  </div>
                </td>
              </tr>
            </TransitionGroup>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Paginación ── -->
    <div class="flex justify-between items-center shrink-0 pt-1">
      <div class="text-xs text-gray-500">
        Total: <strong>{{ usersStore.pagination.total }}</strong> usuarios
      </div>
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 25, 50, 100]"
        :total="usersStore.pagination.total"
        layout="sizes, prev, pager, next, jumper"
        size="small"
        @size-change="handleSizeChange"
        @current-change="handlePageChange"
      />
    </div>

    <!-- Modal de Usuario -->
    <UserFormDialog
      v-model="dialogVisible"
      :mode="dialogType"
      :user="selectedUser"
      @saved="loadUsersData"
    />

    <!-- Modal de Cambio de Contraseña -->
    <UserPasswordDialog
      v-model="passwordDialogVisible"
      :user="selectedUser"
      @saved="loadUsersData"
    />

    <!-- Modal de Historial de cambios -->
    <el-dialog v-model="historialDialogVisible" width="520px" align-center>
      <template #header>
        <div class="flex items-center gap-2">
          <component :is="HistoryIcon" class="w-4 h-4" />
          <span class="font-bold text-sm">Historial de {{ historialUsuario?.full_name }}</span>
        </div>
      </template>
      <div v-if="historialCargando" class="py-6 text-center text-xs text-gray-400">Cargando…</div>
      <div v-else-if="!historialItems.length" class="py-6 text-center text-xs text-gray-400">
        Sin cambios administrativos registrados para esta cuenta.
      </div>
      <div v-else class="historial-list">
        <div v-for="(item, i) in historialItems" :key="i" class="historial-item">
          <p class="historial-item-head">
            <strong>{{ item.actor }}</strong>
            <span class="historial-item-fecha">{{ formatFecha(item.created_at) }}</span>
          </p>
          <ul class="historial-item-cambios">
            <li v-for="campo in Object.keys(item.new_values)" :key="campo">
              <span class="historial-campo">{{ campoLabel(campo) }}:</span>
              <span class="historial-old">{{ valorLegible(item.old_values[campo]) }}</span>
              →
              <span class="historial-new">{{ valorLegible(item.new_values[campo]) }}</span>
            </li>
          </ul>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { usePolling } from '@/lib/usePolling';
import { useDebounceFn } from '@vueuse/core';
import {
  Plus as PlusIcon,
  Search as SearchIcon,
  Edit as EditIcon,
  Trash as TrashIcon,
  X as XIcon,
  Power as PowerIcon,
  PowerOff as PowerOffIcon,
  KeyRound as KeyRoundIcon,
  Users as UsersIcon,
  Download as DownloadIcon,
  UserCheck as UserCheckIcon,
  UserX as UserXIcon,
  ArrowUp as ArrowUpIcon,
  ArrowDown as ArrowDownIcon,
  ArrowUpDown as ArrowUpDownIcon,
  History as HistoryIcon,
} from '@lucide/vue';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
import http from '@/plugins/axios';
import UserFormDialog from '@/views/users/UserFormDialog.vue';
import StatCard from '@/components/ui/StatCard.vue';
import UserPasswordDialog from '@/views/users/UserPasswordDialog.vue';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';

const search = ref('');
const searchDebounced = ref('');
const idsActualizados = ref<Set<number>>(new Set());
const updateSearchDebounced = useDebounceFn((val: string) => { searchDebounced.value = val; }, 300);
watch(search, (val) => updateSearchDebounced(val));
const roleFilter = ref(null);
const statusFilter = ref(null);

const currentPage = ref(1);
const pageSize = ref(10);
const sortBy = ref('id');
const sortOrder = ref('desc');

const seleccionadas = ref<Set<number>>(new Set());

const usersStore = useUsersStore();
const { users, roles } = storeToRefs(usersStore);

const dialogVisible = ref(false);
const dialogType = ref<'create' | 'edit'>('create');
const selectedUser = ref<any>(null);
const passwordDialogVisible = ref(false);

const historialDialogVisible = ref(false);
const historialUsuario = ref<any>(null);
const historialItems = ref<Array<{ event: string; created_at: string; actor: string; old_values: Record<string, unknown>; new_values: Record<string, unknown> }>>([]);
const historialCargando = ref(false);

const CAMPO_LABEL: Record<string, string> = {
  first_name: 'Nombre', middle_name: 'Segundo nombre', last_name: 'Apellido', sur_name: 'Segundo apellido',
  email: 'Correo', job_title: 'Cargo', is_active: 'Estado activo', must_change_password: 'Debe cambiar contraseña',
  must_update_profile: 'Debe actualizar perfil', user_name: 'Usuario', identification_number: 'Documento',
};

function campoLabel(campo: string): string {
  return CAMPO_LABEL[campo] ?? campo;
}

function valorLegible(v: unknown): string {
  if (v === null || v === undefined || v === '') return '—';
  if (typeof v === 'boolean') return v ? 'Sí' : 'No';
  return String(v);
}

async function openHistorialDialog(user: any) {
  historialUsuario.value = user;
  historialDialogVisible.value = true;
  historialCargando.value = true;
  try {
    const { data } = await http.get(`/api/users/${user.id}/audits`);
    historialItems.value = data.data;
  } catch {
    notify.error('No se pudo cargar el historial');
  } finally {
    historialCargando.value = false;
  }
}

const todasSeleccionadas = computed(() => {
  if (users.value.length === 0) return false;
  return users.value.every(u => seleccionadas.value.has(u.id));
});

const statTotal = computed(() => usersStore.pagination.total || users.value.length);
const statActivos = computed(() => users.value.filter(u => u.is_active).length);
const statInactivos = computed(() => users.value.filter(u => !u.is_active).length);
const statConRoles = computed(() => users.value.filter(u => u.roles && u.roles.length > 0).length);

function userInitials(u: any): string {
  const parts = [u.first_name, u.last_name].filter(Boolean);
  if (parts.length === 0) return '?';
  return parts.map((p: string) => p[0]).join('').toUpperCase().slice(0, 2);
}

function formatFecha(fecha: string): string {
  const d = new Date(fecha);
  if (isNaN(d.getTime())) return fecha;
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

function visibleRoles(roles: any[]): any[] {
  return (roles || []).slice(0, 2);
}

function clearFilters() {
  search.value = '';
  roleFilter.value = null;
  statusFilter.value = null;
}

function toggleSort(key: string) {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortOrder.value = 'asc';
  }
  loadUsersData();
}

function sortIcon(key: string) {
  if (sortBy.value !== key) return ArrowUpDownIcon;
  return sortOrder.value === 'asc' ? ArrowUpIcon : ArrowDownIcon;
}

function toggleSeleccion(id: number) {
  const s = new Set(seleccionadas.value);
  if (s.has(id)) s.delete(id);
  else s.add(id);
  seleccionadas.value = s;
}

function toggleSeleccionTodas() {
  const s = new Set(seleccionadas.value);
  if (todasSeleccionadas.value) {
    users.value.forEach(u => s.delete(u.id));
  } else {
    users.value.forEach(u => s.add(u.id));
  }
  seleccionadas.value = s;
}

async function bulkActivar(activar: boolean) {
  const ids = [...seleccionadas.value];
  const lote = users.value.filter(u => ids.includes(u.id) && !u.is_protected && u.is_active !== activar);
  if (lote.length === 0) {
    notify.warning('No hay usuarios para cambiar estado');
    return;
  }
  try {
    await ElMessageBox.confirm(`¿${activar ? 'Activar' : 'Inactivar'} ${lote.length} usuario(s)?`, 'Confirmar', { confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar', type: 'warning' });
    for (const u of lote) {
      await usersStore.updateUser(u.id, { is_active: activar });
    }
    notify.success(`${lote.length} usuario(s) ${activar ? 'activado(s)' : 'inactivado(s)'}`);
    seleccionadas.value = new Set();
    await loadUsersData();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al cambiar estado en lote');
  }
}

async function bulkEliminar() {
  const ids = [...seleccionadas.value];
  const lote = users.value.filter(u => ids.includes(u.id) && !u.is_protected);
  if (lote.length === 0) {
    notify.warning('No hay usuarios para eliminar');
    return;
  }
  try {
    await ElMessageBox.confirm(`¿Eliminar ${lote.length} usuario(s)? Esta acción no se puede deshacer.`, 'Confirmar', { confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar', type: 'error' });
    for (const u of lote) {
      await usersStore.deleteUser(u.id);
    }
    notify.success(`${lote.length} usuario(s) eliminado(s)`);
    seleccionadas.value = new Set();
    await loadUsersData();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al eliminar en lote');
  }
}

function exportarExcel() {
  const rows = users.value;
  const headers = ['ID', 'Usuario', 'Nombre', 'Email', 'Documento', 'Estado', 'Roles', 'Fecha creación'];
  const csv = [
    headers.join('\t'),
    ...rows.map(u => [
      u.id, u.user_name ?? '', u.full_name ?? '', u.email ?? '',
      `${u.identification_type?.name ?? ''} ${u.identification_number ?? ''}`,
      u.is_active ? 'Activo' : 'Inactivo',
      u.roles?.map((r: any) => r.display_name || r.name).join('; ') ?? '',
      u.created_at ?? '',
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join('\t')),
  ].join('\n');
  const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `usuarios_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
  notify.success(`Exportados ${rows.length} usuarios`);
}

function openDialog(type: 'create' | 'edit', user?: any) {
  dialogType.value = type;
  selectedUser.value = type === 'edit' ? user : null;
  dialogVisible.value = true;
}

function openPasswordDialog(user: any) {
  selectedUser.value = user;
  passwordDialogVisible.value = true;
}

async function toggleUserStatus(user: any) {
  const newStatus = !user.is_active;
  try {
    await ElMessageBox.confirm(
      `¿Está seguro de ${newStatus ? 'activar' : 'inactivar'} al usuario ${user.user_name}?`,
      newStatus ? 'Activar Usuario' : 'Inactivar Usuario',
      {
        confirmButtonText: newStatus ? 'Activar' : 'Inactivar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
      }
    );
  } catch {
    return;
  }

  usersStore.loading = true;
  try {
    await usersStore.updateUser(user.id, { is_active: newStatus });
    notify.success(`Usuario ${newStatus ? 'activado' : 'inactivado'} correctamente`);
    await loadUsersData();
  } catch (e: any) {
    console.error(e);
    notify.error('Error al cambiar el estado del usuario');
  } finally {
    usersStore.loading = false;
  }
}

async function deleteUser(user: any) {
  if (user.is_protected) {
    notify.warning('No se puede eliminar al usuario superadmin.');
    return;
  }

  try {
    await ElMessageBox.confirm(
      `¿Está seguro de eliminar el usuario ${user.user_name}? Esta acción no se puede deshacer.`,
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

  usersStore.loading = true;
  try {
    await usersStore.deleteUser(user.id);
    notify.success('Usuario eliminado correctamente');
    await loadUsersData();
  } catch (e: any) {
    console.error(e);
    notify.error('Error al eliminar el usuario');
  } finally {
    usersStore.loading = false;
  }
}

function handlePageChange(page: number) {
  currentPage.value = page;
  loadUsersData();
}

function handleSizeChange(size: number) {
  pageSize.value = size;
  currentPage.value = 1;
  loadUsersData();
}

function filtrosActuales() {
  return {
    general: searchDebounced.value || undefined,
    roles: roleFilter.value ? [roleFilter.value] : undefined,
    is_active: statusFilter.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value,
  };
}

async function loadUsersData() {
  await usersStore.loadUsers(filtrosActuales());
}

/** Refresco automático de fondo: sin esqueleto de carga, y solo resalta las
 * filas que de verdad cambiaron. */
async function loadUsersDataSilent() {
  const cambiados = await usersStore.loadUsersSilent(filtrosActuales());
  if (cambiados.length === 0) return;
  idsActualizados.value = new Set(cambiados);
  setTimeout(() => { idsActualizados.value = new Set(); }, 3000);
}

watch(searchDebounced, () => {
  currentPage.value = 1;
  loadUsersData();
});

watch([roleFilter, statusFilter], () => {
  currentPage.value = 1;
  loadUsersData();
});

onMounted(async () => {
  await usersStore.loadRoles();
  await usersStore.loadIdentificationTypes();
  await loadUsersData();
});

usePolling(() => {
  if (!usersStore.loading && !dialogVisible.value && !passwordDialogVisible.value) loadUsersDataSilent();
}, 30000);
</script>

<style scoped>
.users-page {
  background:
    radial-gradient(circle at 95% 0%, rgba(188, 218, 255, 0.45), transparent 24rem),
    radial-gradient(circle at 5% 100%, rgba(208, 242, 226, 0.25), transparent 28rem);
}

/* ── Header ── */
.users-header {
  display: flex; align-items: center; gap: .75rem;
  padding: .25rem 0;
}
.users-header-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  color: var(--rf-primary);
  flex-shrink: 0;
}
.dark .users-header-icon { background: rgba(99,102,241,0.15); color: #a5b4fc; }
.users-header-title {
  font-size: 16px; font-weight: 800; color: #1e293b;
  letter-spacing: 0.01em; white-space: nowrap;
}
.dark .users-header-title { color: #e2e8f0; }
.users-header-spacer { flex: 1; }

.users-search { width: 280px; }
.users-filter { width: 140px; }

/* ── Stat cards ── */
.users-stats-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .75rem;
}

/* ── Bulk actions bar ── */
.users-bulk-bar {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: linear-gradient(135deg, #eef2f9, #e0e8f5);
  border: 1px solid #c4d4e8;
  border-radius: 10px;
  padding: .4rem .8rem;
}
.bulk-slide-enter-active, .bulk-slide-leave-active {
  transition: all .25s ease;
}
.bulk-slide-enter-from, .bulk-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Checkbox ── */
.users-checkbox {
  width: 16px; height: 16px;
  border-radius: 4px;
  border: 1.5px solid #cbd5e1;
  cursor: pointer;
  accent-color: #16468E;
}
.users-th-check { width: 36px; text-align: center; }
.users-td-check { text-align: center; }

/* ── Sortable headers ── */
.users-th-sortable {
  cursor: pointer;
  user-select: none;
  transition: color .15s ease;
}
.users-th-sortable:hover { color: #16468E; }

/* ── Selected row ── */
.users-table-row-selected {
  background: rgba(22,70,142,.04) !important;
}

/* ── Row transitions ── */
.users-row-enter-active, .users-row-leave-active {
  transition: all .3s ease;
}
.users-row-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}
.users-row-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* ── Tabla ── */
.users-table-panel {
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(212, 222, 234, 0.65);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(22, 70, 142, .07);
  backdrop-filter: blur(10px);
  padding: 0.75rem;
}

.users-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12px;
}

.users-table-thead th {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #f1f5f9;
  color: #475569;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.55rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

.users-table-row {
  transition: background .15s ease, box-shadow .15s ease;
}
.users-table-row:hover { background: #f8fafc; box-shadow: inset 3px 0 0 var(--rf-primary); }
.users-table-row:hover .users-table-status { transform: scale(1.06); }

/* ── Fila resaltada (actualización silenciosa en segundo plano) ── */
.users-table-row-resaltada {
  animation: users-row-glow 2.2s ease-in-out 2;
  box-shadow: inset 3px 0 0 #D97706;
}
@keyframes users-row-glow {
  0%, 100% { background: transparent; }
  50% { background: rgba(217, 119, 6, .12); }
}

.users-td {
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  white-space: nowrap;
}

.users-th-user { width: 32%; }
.users-th-email { width: 28%; }
.users-th-status { width: 12%; }
.users-th-roles { width: 18%; }
.users-th-actions { width: 10%; text-align: center; }

/* ── Celdas usuario ── */
.users-table-user {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-width: 0;
}
.users-table-avatar {
  width: 34px; height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.users-table-name {
  font-weight: 700;
  color: #1e293b;
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.users-table-doc {
  font-size: 10px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.users-table-email {
  display: block;
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #334155;
}

/* ── Estado ── */
.users-table-status {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  transition: transform .2s ease;
}
.status-active {
  background: #dcfce7;
  color: #15803d;
}
.status-inactive {
  background: #fee2e2;
  color: #b91c1c;
}
.users-status-dot {
  width: 6px; height: 6px;
  border-radius: 999px;
  background: currentColor;
}

/* ── Roles ── */
.users-table-roles {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.3rem;
}
.users-role-tag {
  font-size: 10px;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  background: #e2e8f0;
  color: #475569;
}
.users-role-more {
  font-size: 10px;
  color: #94a3b8;
  font-weight: 600;
}

/* ── Acciones ── */
.users-table-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
}
.users-action-btn {
  width: 28px; height: 28px;
  border-radius: 7px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all .15s ease;
}

/* ── Loading skeleton ── */
.users-table-loading {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.75rem;
}
.users-table-row-skeleton {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.75rem;
  border-radius: 10px;
  background: #f8fafc;
}
.shimmer-box,
.shimmer-bar {
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── Empty state ── */
.users-empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
}
.users-empty-icon {
  background: linear-gradient(135deg, #eef2f9, #e0e8f5);
  color: #16468E;
}

/* ── Animaciones ── */
.anim-row-in {
  opacity: 0;
  transform: translateY(8px);
  animation: rowIn 0.35s ease forwards;
}
@keyframes rowIn {
  to { opacity: 1; transform: translateY(0); }
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

@media (max-width: 768px) {
  .users-search { width: 100%; }
  .users-filter { width: calc(50% - 0.25rem); }
}

/* ── Historial de cambios ── */
.historial-list { display: flex; flex-direction: column; gap: 10px; max-height: 420px; overflow-y: auto; }
.historial-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; }
.historial-item-head { display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-bottom: 4px; }
.historial-item-fecha { font-size: 10.5px; color: #94a3b8; }
.historial-item-cambios { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px; }
.historial-item-cambios li { font-size: 11px; color: #475569; display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
.historial-campo { font-weight: 700; color: #334155; }
.historial-old { color: #dc2626; text-decoration: line-through; }
.historial-new { color: #16a34a; font-weight: 600; }
</style>
