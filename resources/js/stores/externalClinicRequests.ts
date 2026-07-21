import { defineStore } from 'pinia';
import { ref } from 'vue';
import http from '@/plugins/axios';
import type { ExternalClinic } from './externalClinicAuth';

export interface ExternalClinicFilters {
  status?: string;
  city?: string;
  department?: string;
  general?: string;
}

export const useExternalClinicRequestsStore = defineStore('externalClinicRequests', () => {
  const requests = ref<ExternalClinic[]>([]);
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });
  const isLoading = ref(false);

  async function getAll(filters: ExternalClinicFilters = {}, page = 1, perPage = 10) {
    isLoading.value = true;

    try {
      const { data } = await http.post('/api/admin/external-clinics/get-all', {
        ...filters,
        page,
        per_page: perPage,
      });

      requests.value = data.data.data;
      meta.value = {
        current_page: data.data.current_page,
        last_page: data.data.last_page,
        per_page: data.data.per_page,
        total: data.data.total,
      };

      return data;
    } finally {
      isLoading.value = false;
    }
  }

  async function show(id: number) {
    const { data } = await http.get(`/api/admin/external-clinics/${id}`);
    return data.data;
  }

  async function approve(id: number, reason?: string) {
    const { data } = await http.post(`/api/admin/external-clinics/${id}/approve`, { change_reason: reason });
    return data.data;
  }

  async function reject(id: number, rejectionReason: string) {
    const { data } = await http.post(`/api/admin/external-clinics/${id}/reject`, { rejection_reason: rejectionReason });
    return data.data;
  }

  async function toggleStatus(id: number, reason?: string) {
    const { data } = await http.patch(`/api/admin/external-clinics/${id}/toggle-status`, { reason });
    return data.data;
  }

  async function getDocuments(id: number) {
    const { data } = await http.get(`/api/admin/external-clinics/${id}/documents`);
    return data.data;
  }

  return {
    requests,
    meta,
    isLoading,
    getAll,
    show,
    approve,
    reject,
    toggleStatus,
    getDocuments,
  };
});
