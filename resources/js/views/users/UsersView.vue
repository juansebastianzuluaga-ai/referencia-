<template>
  <div class="users-page h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Header ── -->
    <div class="flex items-center justify-between shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div>
        <h1 class="text-lg font-bold text-gray-900">Gestión de Usuarios</h1>
        <p class="text-xs text-gray-500">Administración de cuentas de acceso al sistema</p>
      </div>
      <el-button v-permission="'users.create'" type="primary" size="small" @click="openDialog('create')">
        <component :is="PlusIcon" class="w-3.5 h-3.5 mr-1" />
        Nuevo Usuario
      </el-button>
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

    <!-- ── Tabla ── -->
    <div class="flex-1 overflow-hidden users-table-panel">
      <!-- Loading -->
      <div v-if="usersStore.loading" class="users-table-loading">
        <div v-for="i in 5" :key="i" class="users-table-row-skeleton">
          <div class="shimmer-box" style="width:32px; height:32px; border-radius:8px; flex-shrink:0;"></div>
          <div class="flex-1 space-y-1">
            <div class="shimmer-bar" style="width:35%; height:12px;"></div>
            <div class="shimmer-bar" style="width:25%; height:9px;"></div>
          </div>
          <div class="shimmer-bar" style="width:15%; height:11px;"></div>
          <div class="shimmer-box" style="width:60px; height:22px; border-radius:999px;"></div>
          <div class="shimmer-box" style="width:80px; height:26px; border-radius:6px; flex-shrink:0;"></div>
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
                <th class="users-th users-th-user">Usuario</th>
                <th class="users-th users-th-email">Correo</th>
                <th class="users-th users-th-status">Estado</th>
                <th class="users-th users-th-roles">Roles</th>
                <th class="users-th users-th-actions">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(u, idx) in users"
                :key="u.id"
                class="users-table-row anim-row-in"
                :style="{ animationDelay: (idx * 0.02) + 's' }"
              >
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
                  <div class="users-table-roles">
                    <span v-for="role in visibleRoles(u.roles)" :key="role.id" class="users-role-tag">
                      {{ role.display_name || role.name }}
                    </span>
                    <span v-if="u.roles.length > 2" class="users-role-more">+{{ u.roles.length - 2 }}</span>
                  </div>
                </td>
                <td class="users-td">
                  <div class="users-table-actions">
                    <el-tooltip v-permission="'users.update'" :content="u.is_active ? 'Inactivar' : 'Activar'" placement="top">
                      <button
                        v-if="u.user_name !== 'superadmin'"
                        type="button"
                        class="users-action-btn"
                        :class="u.is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-green-600 hover:bg-green-50'"
                        @click="toggleUserStatus(u)"
                      >
                        <component :is="u.is_active ? PowerOffIcon : PowerIcon" class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.update'" content="Editar" placement="top">
                      <button type="button" class="users-action-btn text-blue-600 hover:bg-blue-50" @click="openDialog('edit', u)">
                        <EditIcon class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.update'" content="Cambiar contraseña" placement="top">
                      <button
                        v-if="u.user_name !== 'superadmin'"
                        type="button"
                        class="users-action-btn text-purple-600 hover:bg-purple-50"
                        @click="openPasswordDialog(u)"
                      >
                        <KeyRoundIcon class="w-3.5 h-3.5" />
                      </button>
                    </el-tooltip>
                    <el-tooltip v-permission="'users.delete'" content="Eliminar" placement="top">
                      <button
                        v-if="u.user_name !== 'superadmin'"
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
            </tbody>
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
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
} from '@lucide/vue';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
import UserFormDialog from '@/views/users/UserFormDialog.vue';
import UserPasswordDialog from '@/views/users/UserPasswordDialog.vue';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';

const search = ref('');
const roleFilter = ref(null);
const statusFilter = ref(null);

const currentPage = ref(1);
const pageSize = ref(10);
const sortBy = ref('id');
const sortOrder = ref('desc');

const usersStore = useUsersStore();
const { users, roles } = storeToRefs(usersStore);

const dialogVisible = ref(false);
const dialogType = ref<'create' | 'edit'>('create');
const selectedUser = ref<any>(null);
const passwordDialogVisible = ref(false);

function userInitials(u: any): string {
  const parts = [u.first_name, u.last_name].filter(Boolean);
  if (parts.length === 0) return '?';
  return parts.map((p: string) => p[0]).join('').toUpperCase().slice(0, 2);
}

function visibleRoles(roles: any[]): any[] {
  return (roles || []).slice(0, 2);
}

function clearFilters() {
  search.value = '';
  roleFilter.value = null;
  statusFilter.value = null;
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
  if (user.user_name === 'superadmin') {
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

async function loadUsersData() {
  await usersStore.loadUsers({
    general: search.value || undefined,
    roles: roleFilter.value ? [roleFilter.value] : undefined,
    is_active: statusFilter.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value,
  });
}

watch(search, () => {
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
</script>

<style scoped>
.users-page {
  background:
    radial-gradient(circle at 95% 0%, rgba(188, 218, 255, 0.45), transparent 24rem),
    radial-gradient(circle at 5% 100%, rgba(208, 242, 226, 0.25), transparent 28rem);
}

.users-search { width: 280px; }
.users-filter { width: 140px; }

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
  transition: background .15s ease;
}
.users-table-row:hover { background: #f8fafc; }

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
</style>
