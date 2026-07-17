<template>
  <ContentCard title="Roles y Permisos" subtitle="Gestión de roles de sistema y asignación de permisos">
    <template #actions>
      <el-button v-permission="'roles.create'" type="primary" :icon="PlusIcon" @click="openCreateDialog">
        Nuevo Rol
      </el-button>
    </template>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Lista de Roles -->
      <div class="md:col-span-1 border border-gray-200 rounded-lg overflow-hidden flex flex-col h-[500px]">
        <div class="p-3 bg-gray-50 border-b border-gray-200 font-medium text-sm text-gray-700">
          Roles del Sistema
        </div>
        <div class="flex-1 overflow-y-auto" v-loading="rolesStore.loading">
          <div 
            v-for="role in rolesStore.roles" 
            :key="role.id"
            class="p-3 border-b border-gray-100 cursor-pointer hover:bg-blue-50 transition-colors flex items-center justify-between group"
            :class="{ 'bg-blue-50 border-l-4 border-l-blue-600': selectedRole?.id === role.id }"
            @click="selectRole(role)"
          >
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900">{{ role.display_name || role.name }}</div>
              <div class="text-xs text-gray-500 mt-0.5 truncate" v-if="role.description">{{ role.description }}</div>
              <div class="text-xs text-gray-400 mt-0.5">{{ role.users_count || 0 }} usuarios</div>
            </div>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0 ml-2">
              <el-tooltip v-permission="'roles.update'" content="Editar" placement="top">
                <button
                  type="button"
                  class="w-7 h-7 flex items-center justify-center rounded-md text-gray-500 hover:bg-blue-100 hover:text-blue-600 transition-colors cursor-pointer"
                  @click.stop="openEditDialog(role)"
                >
                  <EditIcon class="w-3.5 h-3.5" />
                </button>
              </el-tooltip>
              <el-tooltip v-permission="'roles.delete'" content="Eliminar" placement="top" v-if="role.name !== 'super-admin'">
                <button
                  type="button"
                  class="w-7 h-7 flex items-center justify-center rounded-md text-gray-500 hover:bg-red-100 hover:text-red-600 transition-colors cursor-pointer"
                  @click.stop="deleteRole(role)"
                >
                  <TrashIcon class="w-3.5 h-3.5" />
                </button>
              </el-tooltip>
            </div>
          </div>
        </div>
      </div>

      <!-- Permisos del Rol Seleccionado -->
      <div class="md:col-span-2 border border-gray-200 rounded-lg flex flex-col h-[500px]">
        <div v-if="selectedRole" class="flex flex-col h-full">
          <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-white">
            <div>
              <div class="text-base font-semibold text-gray-900">Permisos: {{ selectedRole.display_name || selectedRole.name }}</div>
              <div class="text-xs text-gray-500 mt-0.5">
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
          
          <div class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-50/50" v-loading="loadingPermissions">
            <div v-for="group in permissionGroups" :key="group.name" class="mb-6 bg-white p-4 rounded-lg border border-gray-100 shadow-sm">
              <div class="font-medium text-sm text-gray-800 mb-3 pb-2 border-b border-gray-100">{{ group.name }}</div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <el-checkbox 
                  v-for="perm in group.permissions" 
                  :key="perm.id"
                  :model-value="selectedPermissions.includes(perm.name)"
                  :disabled="selectedRole.name === 'super-admin'"
                  @change="(val: any) => togglePermission(perm.name, val)"
                >
                  <span class="text-sm text-gray-700">{{ perm.description || perm.name }}</span>
                </el-checkbox>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400 h-full p-8 text-center bg-gray-50/50">
          <ShieldCheckIcon class="w-12 h-12 mb-3 text-gray-300" />
          <p>Seleccione un rol de la lista para ver y editar sus permisos</p>
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
  </ContentCard>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Plus as PlusIcon, ChevronRight as ChevronRightIcon, ShieldCheck as ShieldCheckIcon, Edit as EditIcon, Trash as TrashIcon } from '@lucide/vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import ContentCard from '@/components/ui/ContentCard.vue';
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
    ElMessage.success('Rol eliminado correctamente');
    if (selectedRole.value?.id === role.id) {
      selectedRole.value = null;
    }
    await rolesStore.loadRoles({}, { force: true });
  } catch (e: any) {
    console.error(e);
    ElMessage.error('Error al eliminar el rol');
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
    
    ElMessage.success('Permisos actualizados correctamente');
    await selectRole(selectedRole.value);
  } catch (e: any) {
    console.error(e);
    ElMessage.error('Error al guardar los permisos');
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
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: var(--color-gray-300, #cbd5e1); border-radius: 4px; }
</style>
