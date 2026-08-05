<template>
  <div class="roles-page h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Header ── -->
    <div class="roles-header shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="roles-header-icon">
        <component :is="ShieldCheckIcon" class="w-5 h-5" />
      </div>
      <div>
        <h1 class="roles-header-title">Roles y Permisos</h1>
        <p class="roles-header-sub">Gestión de roles del sistema y asignación de permisos</p>
      </div>
      <div class="roles-header-spacer"></div>
      <el-button v-permission="'roles.create'" type="primary" size="small" @click="openCreateDialog">
        <component :is="PlusIcon" class="w-3.5 h-3.5 mr-1" />
        Nuevo Rol
      </el-button>
    </div>

    <!-- ── Stat cards ── -->
    <div class="roles-stats-bar shrink-0">
      <div v-for="card in statCards" :key="card.label" class="roles-stat-card" :style="{ '--stat-color': card.color }">
        <div class="roles-stat-icon" :style="{ background: card.iconBg, color: card.color }">
          <component :is="card.icon" class="w-4 h-4" />
        </div>
        <div class="roles-stat-body">
          <p class="roles-stat-label">{{ card.label }}</p>
          <p class="roles-stat-value" :style="{ color: card.color }">{{ card.value }}</p>
          <div class="roles-stat-bar-track">
            <div class="roles-stat-bar-fill" :style="{ width: card.percent + '%', background: card.color }"></div>
          </div>
        </div>
        <span class="roles-stat-delta" :style="{ background: card.iconBg, color: card.color }">{{ card.delta }}</span>
      </div>
    </div>

    <!-- ── Contenido ── -->
    <div class="flex-1 overflow-hidden roles-content-panel">
      <!-- Loading skeleton -->
      <div v-if="rolesStore.loading" class="roles-skeleton-wrap">
        <div v-for="i in 4" :key="i" class="roles-skeleton-card">
          <div class="shimmer-box" style="width:36px; height:36px; border-radius:10px; flex-shrink:0;"></div>
          <div class="flex-1 space-y-2">
            <div class="shimmer-bar" style="width:40%; height:13px;"></div>
            <div class="shimmer-bar" style="width:25%; height:10px;"></div>
          </div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 h-full overflow-hidden">
        <!-- Lista de Roles -->
        <div class="md:col-span-1 roles-list-panel flex flex-col overflow-hidden">
          <div class="roles-list-header">
            <component :is="UsersIcon" class="w-3.5 h-3.5" />
            <span>Roles del Sistema</span>
          </div>
          <div class="flex-1 overflow-y-auto custom-scrollbar">
            <TransitionGroup name="role-item">
              <div
                v-for="role in rolesStore.roles"
                :key="role.id"
                class="roles-list-item"
                :class="{ 'roles-list-item-active': selectedRole?.id === role.id }"
                @click="selectRole(role)"
              >
                <div class="flex-1 min-w-0">
                  <div class="roles-list-item-name">{{ role.display_name || role.name }}</div>
                  <div class="roles-list-item-desc" v-if="role.description">{{ role.description }}</div>
                  <div class="roles-list-item-count">{{ role.users_count || 0 }} usuarios</div>
                </div>
                <div class="roles-list-item-actions">
                  <el-tooltip v-permission="'roles.update'" content="Editar" placement="top">
                    <button type="button" class="roles-action-btn" @click.stop="openEditDialog(role)">
                      <EditIcon class="w-3.5 h-3.5" />
                    </button>
                  </el-tooltip>
                  <el-tooltip v-permission="'roles.delete'" content="Eliminar" placement="top" v-if="role.name !== 'super-admin'">
                    <button type="button" class="roles-action-btn roles-action-danger" @click.stop="deleteRole(role)">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </el-tooltip>
                </div>
              </div>
            </TransitionGroup>
          </div>
        </div>

        <!-- Permisos del Rol Seleccionado -->
        <div class="md:col-span-2 roles-perms-panel flex flex-col overflow-hidden">
          <div v-if="selectedRole" class="flex flex-col h-full">
            <div class="roles-perms-header">
              <div>
                <div class="roles-perms-title">Permisos: {{ selectedRole.display_name || selectedRole.name }}</div>
                <div class="roles-perms-sub">
                  <span v-if="selectedRole.name === 'super-admin'">El super-administrador tiene todos los permisos por defecto</span>
                  <span v-else>Seleccione los permisos que aplican a este rol</span>
                </div>
              </div>
              <el-button
                v-permission="'roles.update'"
                type="primary"
                size="small"
                :loading="saving"
                :disabled="selectedRole.name === 'super-admin'"
                @click="savePermissions"
              >
                Guardar Cambios
              </el-button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 custom-scrollbar" v-loading="loadingPermissions">
              <TransitionGroup name="perm-group" tag="div">
                <div v-for="group in permissionGroups" :key="group.name" class="roles-perm-group">
                  <div class="roles-perm-group-header">{{ group.name }}</div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <el-checkbox
                      v-for="perm in group.permissions"
                      :key="perm.id"
                      :model-value="selectedPermissions.includes(perm.name)"
                      :disabled="selectedRole.name === 'super-admin'"
                      @change="(val: any) => togglePermission(perm.name, val)"
                    >
                      <span class="text-sm">{{ perm.description || perm.name }}</span>
                    </el-checkbox>
                  </div>
                </div>
              </TransitionGroup>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="roles-empty-wrap">
            <svg class="roles-empty-svg" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="60" cy="60" r="50" stroke="#e2e8f0" stroke-width="2" stroke-dasharray="6 4"/>
              <path d="M60 35v25l15 10" stroke="#94a3b8" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="60" cy="60" r="3" fill="#94a3b8"/>
            </svg>
            <p class="roles-empty-title">Seleccione un rol</p>
            <p class="roles-empty-sub">Elija un rol de la lista para ver y editar sus permisos</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <RoleFormDialog
      v-model="dialogVisible"
      :mode="dialogType"
      :role="selectedRoleForEdit"
      @saved="onRoleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Plus as PlusIcon, ShieldCheck as ShieldCheckIcon, Edit as EditIcon, Trash as TrashIcon, Users as UsersIcon, KeyRound as KeyRoundIcon, Lock as LockIcon, CheckCircle as CheckCircleIcon } from '@lucide/vue';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
import RoleFormDialog from './RoleFormDialog.vue';
import { useRolesStore } from '@/stores/roles';
import { storeToRefs } from 'pinia';

const rolesStore = useRolesStore();
const { roles, permissions } = storeToRefs(rolesStore);

const selectedRole = ref<any>(null);
const selectedRoleForEdit = ref<any>(null);
const dialogVisible = ref(false);
const dialogType = ref<'create' | 'edit'>('create');
const saving = ref(false);
const loadingPermissions = ref(false);
const selectedPermissions = ref([] as string[]);

const statCards = computed(() => {
  const totalRoles = rolesStore.roles.length;
  const totalPerms = permissions.value.length;
  const totalUsers = rolesStore.roles.reduce((sum: number, r: any) => sum + (r.users_count || 0), 0);
  const superAdmin = rolesStore.roles.filter((r: any) => r.name === 'super-admin').length;
  const pct = (n: number, t: number) => t > 0 ? Math.round((n / t) * 100) : 0;
  return [
    { label: 'Roles', value: totalRoles, icon: ShieldCheckIcon, color: '#2563eb', iconBg: '#dbeafe', percent: 100, delta: 'Total roles' },
    { label: 'Permisos', value: totalPerms, icon: KeyRoundIcon, color: '#7c3aed', iconBg: '#ede9fe', percent: 100, delta: 'Disponibles' },
    { label: 'Usuarios asignados', value: totalUsers, icon: UsersIcon, color: '#16a34a', iconBg: '#dcfce7', percent: pct(totalUsers, Math.max(totalUsers, 1)), delta: 'Con rol' },
    { label: 'Protegidos', value: superAdmin, icon: LockIcon, color: '#dc2626', iconBg: '#fee2e2', percent: superAdmin > 0 ? 100 : 0, delta: 'Super-admin' },
  ];
});

const permissionGroups = computed(() => {
  const groups: Record<string, any[]> = {};
  
  permissions.value.forEach((perm: any) => {
    const parts = perm.name.split('.');
    const groupName = parts.length > 1 ? parts[0].charAt(0).toUpperCase() + parts[0].slice(1) : 'General';
    
    if (!groups[groupName]) {
      groups[groupName] = [];
    }
    
    groups[groupName].push({
      ...perm,
      selected: selectedPermissions.value.includes(perm.name),
    });
  });
  
  return Object.entries(groups).map(([name, perms]) => ({
    name,
    permissions: perms.sort((a, b) => a.name.localeCompare(b.name)),
  })).sort((a, b) => a.name.localeCompare(b.name));
});

async function selectRole(role: any) {
  selectedRole.value = role;
  loadingPermissions.value = true;
  try {
    const fullRole = await rolesStore.getRole(role.id);
    selectedRole.value = fullRole;
    // Actualizar permisos seleccionados
    selectedPermissions.value = fullRole.permissions?.map((p: any) => p.name) || [];
  } catch (e) {
    console.error(e);
  } finally {
    loadingPermissions.value = false;
  }
}

function togglePermission(permissionName: string, value: any) {
  if (value) {
    if (!selectedPermissions.value.includes(permissionName)) {
      selectedPermissions.value.push(permissionName);
    }
  } else {
    const index = selectedPermissions.value.indexOf(permissionName);
    if (index > -1) {
      selectedPermissions.value.splice(index, 1);
    }
  }
}

function openCreateDialog() {
  dialogType.value = 'create';
  selectedRoleForEdit.value = null;
  dialogVisible.value = true;
}

function openEditDialog(role: any) {
  dialogType.value = 'edit';
  selectedRoleForEdit.value = role;
  dialogVisible.value = true;
}

async function deleteRole(role: any) {
  try {
    await ElMessageBox.confirm(
      `¿Está seguro de eliminar el rol ${role.display_name || role.name}? Esta acción no se puede deshacer.`,
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

  rolesStore.loading = true;
  try {
    await rolesStore.deleteRole(role.id);
    notify.success('Rol eliminado correctamente');
    if (selectedRole.value?.id === role.id) {
      selectedRole.value = null;
    }
    await rolesStore.loadRoles({}, { force: true });
  } catch (e: any) {
    console.error(e);
    notify.error('Error al eliminar el rol');
  } finally {
    rolesStore.loading = false;
  }
}

async function savePermissions() {
  saving.value = true;
  try {
    await rolesStore.updateRole(selectedRole.value.id, {
      permissions: selectedPermissions.value,
    });
    
    notify.success('Permisos actualizados correctamente');
    await selectRole(selectedRole.value);
  } catch (e: any) {
    console.error(e);
    notify.error('Error al guardar los permisos');
  } finally {
    saving.value = false;
  }
}

async function onRoleSaved() {
  await rolesStore.loadRoles({}, { force: true });
  // Si hay un rol seleccionado, recargar sus datos actualizados
  if (selectedRole.value) {
    await selectRole(selectedRole.value);
  }
}

onMounted(async () => {
  await rolesStore.loadPermissions();
  await rolesStore.loadRoles();
});
</script>

<style scoped>
.roles-page {
  background: linear-gradient(160deg, #eef4fc 0%, #e3edf8 40%, #f0f5fa 100%);
}

/* ── Header ── */
.roles-header {
  display: flex;
  align-items: center;
  gap: .65rem;
}
.roles-header-icon {
  width: 2.2rem; height: 2.2rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.roles-header-title {
  font-size: 1rem;
  font-weight: 800;
  color: #0D2D6B;
  line-height: 1.2;
}
.roles-header-sub {
  font-size: 11px;
  color: #64748b;
}
.roles-header-spacer { flex: 1; }

/* ── Stat cards ── */
.roles-stats-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .5rem;
}
.roles-stat-card {
  display: flex;
  align-items: center;
  gap: .6rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: .65rem .8rem;
  position: relative;
  overflow: hidden;
  transition: transform .2s ease, box-shadow .2s ease;
}
.roles-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(13,45,107,.08);
}
.roles-stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--stat-color);
  opacity: .8;
}
.roles-stat-icon {
  width: 2.2rem; height: 2.2rem;
  border-radius: 10px;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.roles-stat-body { flex: 1; min-width: 0; }
.roles-stat-label {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin: 0;
}
.roles-stat-value {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1.1;
  margin: 0;
}
.roles-stat-bar-track {
  height: 3px;
  border-radius: 2px;
  background: #f1f5f9;
  margin-top: .25rem;
  overflow: hidden;
}
.roles-stat-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width .4s ease;
}
.roles-stat-delta {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 6px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Content panel ── */
.roles-content-panel {
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212,222,234,0.65);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(22,70,142,.07);
  backdrop-filter: blur(10px);
}

/* ── Skeleton ── */
.roles-skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: .5rem;
  padding: 1rem;
}
.roles-skeleton-card {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .65rem .8rem;
  border-radius: 10px;
  background: #f8fafc;
}
.shimmer-box, .shimmer-bar {
  background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ── List panel ── */
.roles-list-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}
.roles-list-header {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .6rem .8rem;
  font-size: 11px;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: .04em;
  background: #f1f5f9;
  border-bottom: 1px solid #e2e8f0;
}
.roles-list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .65rem .8rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: all .15s ease;
}
.roles-list-item:hover {
  background: #eff6ff;
}
.roles-list-item-active {
  background: #dbeafe;
  border-left: 3px solid #2563eb;
  padding-left: calc(.8rem - 3px);
}
.roles-list-item-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
}
.roles-list-item-desc {
  font-size: 11px;
  color: #64748b;
  margin-top: .15rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.roles-list-item-count {
  font-size: 10px;
  color: #94a3b8;
  margin-top: .15rem;
}
.roles-list-item-actions {
  display: flex;
  gap: .2rem;
  opacity: 0;
  transition: opacity .15s ease;
  flex-shrink: 0;
}
.roles-list-item:hover .roles-list-item-actions {
  opacity: 1;
}
.roles-action-btn {
  width: 28px; height: 28px;
  border-radius: 7px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all .15s ease;
  cursor: pointer;
  border: none;
  background: transparent;
}
.roles-action-btn:hover {
  background: #dbeafe;
  color: #2563eb;
}
.roles-action-danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

/* ── Perms panel ── */
.roles-perms-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}
.roles-perms-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .8rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
}
.roles-perms-title {
  font-size: 14px;
  font-weight: 700;
  color: #1e293b;
}
.roles-perms-sub {
  font-size: 11px;
  color: #64748b;
  margin-top: .15rem;
}
.roles-perm-group {
  background: #fff;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  padding: .8rem 1rem;
  margin-bottom: .8rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.03);
  transition: box-shadow .2s ease;
}
.roles-perm-group:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.roles-perm-group-header {
  font-size: 12px;
  font-weight: 700;
  color: #334e70;
  margin-bottom: .6rem;
  padding-bottom: .4rem;
  border-bottom: 1px solid #f1f5f9;
}

/* ── Empty state ── */
.roles-empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  padding: 2rem;
}
.roles-empty-svg {
  width: 100px;
  height: 100px;
  margin-bottom: 1rem;
}
.roles-empty-title {
  font-size: 14px;
  font-weight: 700;
  color: #0d2d5e;
  margin: 0 0 .25rem;
}
.roles-empty-sub {
  font-size: 12px;
  color: #64748b;
  max-width: 240px;
}

/* ── Transitions ── */
.role-item-enter-active, .role-item-leave-active {
  transition: all .3s ease;
}
.role-item-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}
.role-item-leave-to {
  opacity: 0;
  transform: translateX(12px);
}
.perm-group-enter-active {
  transition: all .35s ease;
}
.perm-group-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

/* ── Scrollbar ── */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

@media (max-width: 768px) {
  .roles-stats-bar { grid-template-columns: repeat(2, 1fr); }
}
</style>
