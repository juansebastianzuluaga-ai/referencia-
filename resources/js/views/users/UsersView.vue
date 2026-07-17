<template>
  <ContentCard title="Gestión de Usuarios" subtitle="Administración de cuentas de acceso al sistema">
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
            <el-select v-model="roleFilter" placeholder="Filtrar por rol" class="w-full" clearable :teleported="false">
              <el-option v-for="r in roles" :key="r.id" :label="r.display_name || r.name" :value="r.id" />
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
      <el-button v-permission="'users.create'" type="primary" :icon="PlusIcon" @click="openDialog('create')">
        Nuevo Usuario
      </el-button>
    </template>

    <el-table 
      :data="users" 
      v-loading="usersStore.loading" 
      style="width: 100%" 
      class="border border-gray-200 rounded-lg"
      @sort-change="handleSortChange"
    >
      <el-table-column prop="user_name" label="USUARIO" width="150" sortable="custom" />
      <el-table-column prop="full_name" label="NOMBRE COMPLETO" min-width="200" sortable="custom" />
      <el-table-column label="IDENTIFICACIÓN" min-width="180">
        <template #default="{ row }">
          <div class="text-sm">
            <div class="font-medium">{{ row.identification_number || '-' }}</div>
            <div class="text-xs text-gray-500">{{ row.identification_type?.name || '' }}</div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="email" label="CORREO" min-width="200" sortable="custom" />
      <el-table-column label="ESTADO" width="100">
        <template #default="{ row }">
          <StatusPill :type="row.is_active ? 'green' : 'red'">
            {{ row.is_active ? 'Activo' : 'Inactivo' }}
          </StatusPill>
        </template>
      </el-table-column>
      <el-table-column label="ROLES" min-width="150">
        <template #default="{ row }">
          <div class="flex flex-wrap gap-1">
            <el-tag v-for="role in row.roles" :key="role.id" size="small" type="info">
              {{ role.display_name || role.name }}
            </el-tag>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="ACCIONES" width="140" align="center" fixed="right">
        <template #default="{ row }">
          <div class="flex items-center justify-center gap-0.5">
            <el-tooltip v-permission="'users.update'" :content="row.is_active ? 'Inactivar' : 'Activar'" placement="top">
              <button
                v-if="row.user_name !== 'superadmin'"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 transition-colors cursor-pointer"
                :class="row.is_active ? 'hover:bg-amber-50 hover:text-amber-600' : 'hover:bg-green-50 hover:text-green-600'"
                @click="toggleUserStatus(row)"
              >
                <component :is="row.is_active ? PowerOffIcon : PowerIcon" class="w-4 h-4" />
              </button>
            </el-tooltip>
            <el-tooltip v-permission="'users.update'" content="Editar" placement="top">
              <button
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors cursor-pointer"
                @click="openDialog('edit', row)"
              >
                <EditIcon class="w-4 h-4" />
              </button>
            </el-tooltip>
            <el-tooltip v-permission="'users.update'" content="Cambiar contraseña" placement="top">
              <button
                v-if="row.user_name !== 'superadmin'"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-purple-50 hover:text-purple-600 transition-colors cursor-pointer"
                @click="openPasswordDialog(row)"
              >
                <KeyRoundIcon class="w-4 h-4" />
              </button>
            </el-tooltip>
            <el-tooltip v-permission="'users.delete'" content="Eliminar" placement="top">
              <button
                v-if="row.user_name !== 'superadmin'"
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors cursor-pointer"
                @click="deleteUser(row)"
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
        Total: {{ usersStore.pagination.total }} usuarios
      </div>
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 25, 50, 100]"
        :total="usersStore.pagination.total"
        layout="sizes, prev, pager, next, jumper"
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
  </ContentCard>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Plus as PlusIcon, Search as SearchIcon, Edit as EditIcon, Trash as TrashIcon, FilterIcon, Power as PowerIcon, PowerOff as PowerOffIcon, KeyRound as KeyRoundIcon } from '@lucide/vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import ContentCard from '@/components/ui/ContentCard.vue';
import StatusPill from '@/components/ui/StatusPill.vue';
import UserFormDialog from '@/views/users/UserFormDialog.vue';
import UserPasswordDialog from '@/views/users/UserPasswordDialog.vue';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';

const search = ref('');
const roleFilter = ref(null);
const statusFilter = ref(null);
const showFilters = ref(false);

const activeFilterCount = computed(() => {
  let count = 0;
  if (roleFilter.value) count++;
  if (statusFilter.value !== null) count++;
  if (search.value && search.value.length >= 10) count++;
  return count;
});
const hasActiveFilters = computed(() => activeFilterCount.value > 0);

function clearFilters() {
  search.value = '';
  roleFilter.value = null;
  statusFilter.value = null;
}
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
    ElMessage.success(`Usuario ${newStatus ? 'activado' : 'inactivado'} correctamente`);
    await loadUsersData();
  } catch (e: any) {
    console.error(e);
    ElMessage.error('Error al cambiar el estado del usuario');
  } finally {
    usersStore.loading = false;
  }
}

async function deleteUser(user: any) {
  if (user.user_name === 'superadmin') {
    ElMessage.warning('No se puede eliminar al usuario superadmin.');
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
    ElMessage.success('Usuario eliminado correctamente');
    await loadUsersData();
  } catch (e: any) {
    console.error(e);
    ElMessage.error('Error al eliminar el usuario');
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

function handleSortChange({ prop, order }: any) {
  if (prop) {
    sortBy.value = prop;
    sortOrder.value = order === 'ascending' ? 'asc' : 'desc';
  } else {
    sortBy.value = 'id';
    sortOrder.value = 'desc';
  }
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

watch(search, (val, oldVal) => {
  const shouldFilter = val.length === 0 || val.length >= 10;
  const shouldFilterOld = (oldVal?.length || 0) === 0 || (oldVal?.length || 0) >= 10;
  if (shouldFilter || shouldFilterOld) {
    currentPage.value = 1;
    loadUsersData();
  }
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

// reloadData removed (debug helper)
</script>
