import { defineStore } from 'pinia';
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import http from '@/plugins/axios';

export const useUsersStore = defineStore('users', () => {
  const users = ref<any[]>([]);
  const roles = ref<any[]>([]);
  const identificationTypes = ref<any[]>([]);
  const loading = ref(false);
  const usersLoaded = ref(false);
  const rolesLoaded = ref(false);
  const identificationTypesLoaded = ref(false);
  const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  async function loadRoles(options: { force?: boolean } = {}) {
    if (rolesLoaded.value && !options.force) {
      return;
    }
    try {
      // Ensure CSRF cookie is present for POST endpoints (Sanctum)
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const { data } = await http.post('/api/roles/get-all', { per_page: 100 });
      roles.value = data.data?.data || data.data || [];
      rolesLoaded.value = true;
    } catch (e: any) {
      roles.value = [];
      ElMessage.error('Error cargando roles.');
    }
  }

  async function loadIdentificationTypes(options: { force?: boolean } = {}) {
    if (identificationTypesLoaded.value && !options.force) {
      return;
    }
    try {
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const { data } = await http.post('/api/identification-types/get-all', { per_page: 100 });
      identificationTypes.value = data.data?.data || data.data || [];
      identificationTypesLoaded.value = true;
    } catch (e: any) {
      identificationTypes.value = [];
      ElMessage.error('Error cargando tipos de identificación.');
    }
  }

  async function loadUsers(payload: any = {}, options: { force?: boolean } = {}) {
    loading.value = true;
    try {
      if (usersLoaded.value && !options.force && Object.keys(payload).length === 0) {
        loading.value = false;
        return;
      }
      // Ensure CSRF cookie is present for POST endpoints (Sanctum)
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const body = { per_page: pagination.value.per_page, page: pagination.value.current_page, ...payload };
      const { data } = await http.post('/api/users/get-all', body);
      users.value = data.data?.data || data.data || [];
      pagination.value = {
        current_page: data.data?.current_page || data.data?.meta?.current_page || 1,
        per_page: data.data?.per_page || data.data?.meta?.per_page || 10,
        total: data.data?.total || data.data?.meta?.total || 0,
        last_page: data.data?.last_page || data.data?.meta?.last_page || 1,
      };
      usersLoaded.value = true;
    } catch (e: any) {
      users.value = [];
      if (e?.response?.status === 403) {
        ElMessage.error('No tiene permiso para ver usuarios.');
      } else {
        ElMessage.error('Error cargando usuarios.');
      }
    } finally {
      loading.value = false;
    }
  }

  function resetLoaded() {
    usersLoaded.value = false;
    rolesLoaded.value = false;
    identificationTypesLoaded.value = false;
    pagination.value = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
  }

  async function getUser(id: number) {
    const { data } = await http.get(`/api/users/${id}`);
    return data.data;
  }

  async function createUser(payload: any) {
    await http.post('/api/users', payload);
  }

  async function updateUser(id: number, payload: any) {
    await http.put(`/api/users/${id}`, payload);
  }

  async function deleteUser(id: number) {
    await http.delete(`/api/users/${id}`);
  }

  return {
    users,
    roles,
    identificationTypes,
    loading,
    pagination,
    loadRoles,
    loadIdentificationTypes,
    loadUsers,
    getUser,
    createUser,
    updateUser,
    deleteUser,
  };
});
