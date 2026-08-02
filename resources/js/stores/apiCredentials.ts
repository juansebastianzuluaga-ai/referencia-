import { defineStore } from 'pinia';
import { ref } from 'vue';
import notify from '@/plugins/toast';
import http from '@/plugins/axios';

export interface ApiCredential {
  id: number;
  name: string;
  description: string | null;
  api_key: string;
  status: 'active' | 'inactive' | 'expired' | 'revoked';
  expires_at: string | null;
  last_used_at: string | null;
  allowed_ips: string[] | null;
  abilities: string[] | null;
  rate_limit: number;
  metadata: Record<string, any> | null;
  created_at: string;
  updated_at: string;
  request_logs_count?: number;
}

export const useApiCredentialsStore = defineStore('apiCredentials', () => {
  const credentials = ref<ApiCredential[]>([]);
  const loading = ref(false);
  const saving = ref(false);
  const pagination = ref({
    current_page: 1,
    per_page: 10,
    total: 0,
  });
  const filters = ref({
    search: '',
    status: '',
  });

  async function loadCredentials() {
    loading.value = true;
    try {
      const params: Record<string, any> = {
        per_page: pagination.value.per_page,
        page: pagination.value.current_page,
      };
      if (filters.value.search) params.search = filters.value.search;
      if (filters.value.status) params.status = filters.value.status;

      const { data } = await http.get('/api/api-credentials', { params, headers: { 'X-Skip-Auth-Redirect': '1' } });
      credentials.value = data.data?.data || [];
      pagination.value.total = data.data?.total || 0;
    } catch (e: any) {
      console.error(e);
      notify.error('Error cargando credenciales API');
    } finally {
      loading.value = false;
    }
  }

  async function createCredential(payload: Record<string, any>): Promise<any> {
    saving.value = true;
    try {
      const { data } = await http.post('/api/api-credentials', payload, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      notify.success('Credencial API creada exitosamente');
      await loadCredentials();
      return data.data;
    } catch (e: any) {
      console.error(e);
      notify.error(e?.response?.data?.message || 'Error al crear credencial API');
      throw e;
    } finally {
      saving.value = false;
    }
  }

  async function updateCredential(id: number, payload: Record<string, any>) {
    saving.value = true;
    try {
      const { data } = await http.put(`/api/api-credentials/${id}`, payload, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      notify.success('Credencial API actualizada exitosamente');
      await loadCredentials();
      return data.data;
    } catch (e: any) {
      console.error(e);
      notify.error(e?.response?.data?.message || 'Error al actualizar credencial API');
      throw e;
    } finally {
      saving.value = false;
    }
  }

  async function deleteCredential(id: number) {
    try {
      await http.delete(`/api/api-credentials/${id}`, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      notify.success('Credencial API eliminada exitosamente');
      await loadCredentials();
    } catch (e: any) {
      console.error(e);
      notify.error(e?.response?.data?.message || 'Error al eliminar credencial API');
      throw e;
    }
  }

  async function regenerateCredential(id: number): Promise<any> {
    try {
      const { data } = await http.post(`/api/api-credentials/${id}/regenerate`, {}, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      notify.success('Credenciales regeneradas exitosamente');
      await loadCredentials();
      return data.data;
    } catch (e: any) {
      console.error(e);
      notify.error(e?.response?.data?.message || 'Error al regenerar credenciales');
      throw e;
    }
  }

  async function toggleStatus(id: number) {
    try {
      const { data } = await http.patch(`/api/api-credentials/${id}/toggle-status`, {}, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      notify.success(data.message || 'Estado actualizado');
      await loadCredentials();
    } catch (e: any) {
      console.error(e);
      notify.error(e?.response?.data?.message || 'Error al cambiar estado');
      throw e;
    }
  }

  async function revokeCredential(id: number) {
    try {
      await http.post(`/api/api-credentials/${id}/revoke`, {}, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      notify.success('Credencial API revocada exitosamente');
      await loadCredentials();
    } catch (e: any) {
      console.error(e);
      notify.error(e?.response?.data?.message || 'Error al revocar credencial');
      throw e;
    }
  }

  async function getLogs(id: number, page = 1, perPage = 50) {
    try {
      const { data } = await http.get(`/api/api-credentials/${id}/logs`, {
        params: { page, per_page: perPage },
        headers: { 'X-Skip-Auth-Redirect': '1' },
      });
      return data.data;
    } catch (e: any) {
      console.error(e);
      notify.error('Error al cargar logs');
      throw e;
    }
  }

  async function getStats(id: number) {
    try {
      const { data } = await http.get(`/api/api-credentials/${id}/stats`, { headers: { 'X-Skip-Auth-Redirect': '1' } });
      return data.data;
    } catch (e: any) {
      console.error(e);
      notify.error('Error al cargar estadísticas');
      throw e;
    }
  }

  function setPagination(page: number, perPage?: number) {
    pagination.value.current_page = page;
    if (perPage) pagination.value.per_page = perPage;
  }

  return {
    credentials,
    loading,
    saving,
    pagination,
    filters,
    loadCredentials,
    createCredential,
    updateCredential,
    deleteCredential,
    regenerateCredential,
    toggleStatus,
    revokeCredential,
    getLogs,
    getStats,
    setPagination,
  };
});
