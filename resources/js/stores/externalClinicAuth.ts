import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import http from '@/plugins/axios';

export interface ExternalClinic {
  id: number;
  nit: string;
  business_name: string;
  trade_name?: string | null;
  email: string;
  phone: string;
  mobile?: string | null;
  address: string;
  city: string;
  department: string;
  legal_rep_name: string;
  legal_rep_id_type?: { id: number; name: string };
  legal_rep_id_number: string;
  status: 'pending' | 'approved' | 'rejected' | 'active' | 'inactive';
  status_label: string;
  must_change_password: boolean;
  last_login_at?: string | null;
}

export const useExternalClinicAuthStore = defineStore('externalClinicAuth', () => {
  const clinic = ref<ExternalClinic | null>(null);
  const isHydrated = ref(false);
  const isAuthenticated = computed(() => clinic.value !== null);
  const isActive = computed(() => clinic.value?.status === 'active');
  const mustChangePassword = computed(() => clinic.value?.must_change_password ?? false);

  async function initCsrf() {
    await http.get('/sanctum/csrf-cookie');
  }

  async function login(credentials: { nit: string; password: string; remember?: boolean }) {
    await initCsrf();
    const { data } = await http.post('/api/external-clinics/login', credentials);
    clinic.value = data.data;
  }

  async function fetchClinic() {
    try {
      const { data } = await http.get('/api/external-clinics/profile', {
        headers: { 'X-Skip-Auth-Redirect': '1' },
      });
      clinic.value = data.data;
    } catch {
      clinic.value = null;
    } finally {
      isHydrated.value = true;
    }
  }

  async function logout() {
    await http.post('/api/external-clinics/logout');
    clinic.value = null;
    isHydrated.value = false;
    window.location.href = '/login-clinica';
  }

  async function updateProfile(payload: Partial<ExternalClinic>) {
    const { data } = await http.put('/api/external-clinics/profile', payload);
    clinic.value = data.data;
  }

  async function updatePassword(payload: { current_password: string; password: string; password_confirmation: string }) {
    await http.put('/api/external-clinics/password', payload);
  }

  return {
    clinic,
    isHydrated,
    isAuthenticated,
    isActive,
    mustChangePassword,
    login,
    fetchClinic,
    logout,
    updateProfile,
    updatePassword,
  };
});
