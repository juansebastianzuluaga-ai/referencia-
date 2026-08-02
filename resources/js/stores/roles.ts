import { defineStore } from 'pinia';
import { ref } from 'vue';
import notify from '@/plugins/toast';
import http from '@/plugins/axios';

export const useRolesStore = defineStore('roles', () => {
  const roles = ref<any[]>([]);
  const permissions = ref<any[]>([]);
  const loading = ref(false);
  const rolesLoaded = ref(false);
  const permissionsLoaded = ref(false);
  const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
    last_page: 1,
  });

  async function loadPermissions(options: { force?: boolean } = {}) {
    if (permissionsLoaded.value && !options.force) {
      return;
    }
    try {
      // Ensure CSRF cookie is present for POST endpoints (Sanctum)
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const { data } = await http.post('/api/permissions/get-all', { per_page: 1000 });
      permissions.value = data.data?.data || data.data || [];
      permissionsLoaded.value = true;
    } catch (e: any) {
      permissions.value = [];
      notify.error('Error cargando permisos.');
    }
  }

  async function loadRoles(payload: any = {}, options: { force?: boolean } = {}) {
    loading.value = true;
    try {
      if (rolesLoaded.value && !options.force && Object.keys(payload).length === 0) {
        loading.value = false;
        return;
      }
      // Ensure CSRF cookie is present for POST endpoints (Sanctum)
      await http.get('/sanctum/csrf-cookie', { headers: { 'X-Skip-Auth-Redirect': '1' } }).catch(() => {});
      const body = { per_page: pagination.value.per_page, page: pagination.value.current_page, ...payload };
      const { data } = await http.post('/api/roles/get-all', body);
      roles.value = data.data?.data || data.data || [];
      pagination.value = {
        current_page: data.data?.current_page || data.data?.meta?.current_page || 1,
        per_page: data.data?.per_page || data.data?.meta?.per_page || 10,
        total: data.data?.total || data.data?.meta?.total || 0,
        last_page: data.data?.last_page || data.data?.meta?.last_page || 1,
      };
      rolesLoaded.value = true;
    } catch (e: any) {
      roles.value = [];
      if (e?.response?.status === 403) {
        notify.error('No tiene permiso para ver roles.');
      } else {
        notify.error('Error cargando roles.');
      }
    } finally {
      loading.value = false;
    }
  }

  function resetLoaded() {
    rolesLoaded.value = false;
    permissionsLoaded.value = false;
    pagination.value = { current_page: 1, per_page: 10, total: 0, last_page: 1 };
  }

  async function getRole(id: number) {
    const { data } = await http.get(`/api/roles/${id}`);
    return data.data;
  }

  async function createRole(payload: any) {
    await http.post('/api/roles', payload);
  }

  async function updateRole(id: number, payload: any) {
    await http.put(`/api/roles/${id}`, payload);
  }

  async function deleteRole(id: number) {
    await http.delete(`/api/roles/${id}`);
  }

  return {
    roles,
    permissions,
    loading,
    pagination,
    loadPermissions,
    loadRoles,
    getRole,
    createRole,
    updateRole,
    deleteRole,
  };
});
