import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import http from '@/plugins/axios';
import { markTab, clearTab, isTabMarked, checkDuplicate as checkTabDuplicate } from '@/utils/tabGuard';

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
    display_name?: string;
    permissions: { name: string }[];
  }[];
}

const AUTH_KEY = 'interno';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);
  const isHydrated = ref(false);
  const isAuthenticated = computed(() => user.value !== null);

  async function initCsrf() {
    await http.get('/sanctum/csrf-cookie');
  }

  async function login(credentials: { username: string; password: string }) {
    await initCsrf();
    await http.post('/api/login', credentials);
    markTab(AUTH_KEY);
    // Se vuelve a pedir el usuario con /api/user (el mismo camino que se usa
    // al recargar la página) en vez de confiar en lo que trajo la respuesta
    // del login — así el menú lateral (que depende de los roles/permisos)
    // arranca siempre con los mismos datos completos, sin importar si se
    // acaba de iniciar sesión o si se recargó la página.
    await fetchUser();
  }

  async function fetchUser() {
    if (!isTabMarked(AUTH_KEY)) {
      user.value = null;
      isHydrated.value = true;
      return;
    }
    try {
      await initCsrf();
      const { data } = await http.get('/api/user', {
        headers: { 'X-Skip-Auth-Redirect': '1' },
        timeout: 8000,
      });
      user.value = data.data;
    } catch {
      user.value = null;
      clearTab(AUTH_KEY);
    } finally {
      isHydrated.value = true;
    }
  }

  async function logout() {
    await http.post('/api/logout');
    user.value = null;
    isHydrated.value = false;
    clearTab(AUTH_KEY);
    window.location.href = '/login';
  }

  async function checkDuplicate(): Promise<boolean> {
    return checkTabDuplicate(AUTH_KEY);
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
    checkDuplicate,
  };
});
