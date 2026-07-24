import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import http from '@/plugins/axios';

export interface User {
  id: number;
  user_name: string;
  first_name?: string;
  middle_name?: string;
  last_name?: string;
  sur_name?: string;
  full_name: string;
  job_title: string | null;
  identification_number?: string;
  identification_type?: { id: number; name: string };
  email?: string;
  is_active: boolean;
  must_change_password?: boolean;
  must_update_profile?: boolean;
  failed_login_attempts?: number;
  roles?: {
    name: string;
    permissions: { name: string }[];
  }[];
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isHydrated = ref(false);
  const isAuthenticated = computed(() => user.value !== null);

  async function initCsrf() {
    await http.get('/sanctum/csrf-cookie');
  }

  async function login(credentials: { username: string; password: string }) {
    await initCsrf();
    const { data } = await http.post('/api/login', credentials);
    user.value = data.data;
    isHydrated.value = true;
  }

  async function fetchUser() {
    try {
      const { data } = await http.get('/api/user', {
        headers: { 'X-Skip-Auth-Redirect': '1' },
        timeout: 8000,
      });
      user.value = data.data;
    } catch {
      user.value = null;
    } finally {
      isHydrated.value = true;
    }
  }

  async function logout() {
    await http.post('/api/logout');
    user.value = null;
    isHydrated.value = false;
    window.location.href = '/login';
  }

  function hasPermission(permission: string) {
    if (!user.value) return false;
    if (user.value.roles?.some(r => r.name === 'super-admin')) return true;
    return user.value.roles?.some(role =>
      role.permissions?.some(p => p.name === permission)
    ) ?? false;
  }

  return {
    user,
    isHydrated,
    isAuthenticated,
    login,
    logout,
    fetchUser,
    hasPermission,
  };
});
