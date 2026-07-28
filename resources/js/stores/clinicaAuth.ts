import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import http from '@/plugins/axios';
import { markTab, clearTab, checkDuplicate as checkTabDuplicate } from '@/utils/tabGuard';

export interface Clinica {
  id: number;
  nit: string;
  nombre: string;
  email: string;
  telefono?: string;
  ciudad?: string;
  is_active: boolean;
}

const AUTH_KEY = 'clinica';
const TOKEN_KEY = 'clinica_token';

export const useClinicaAuthStore = defineStore('clinicaAuth', () => {
  const clinica = ref<Clinica | null>(null);
  const isHydrated = ref(false);
  const isAuthenticated = computed(() => clinica.value !== null);

  function getToken(): string | null {
    return sessionStorage.getItem(TOKEN_KEY);
  }

  function setToken(token: string): void {
    sessionStorage.setItem(TOKEN_KEY, token);
    markTab(AUTH_KEY);
  }

  function clearToken(): void {
    sessionStorage.removeItem(TOKEN_KEY);
    clearTab(AUTH_KEY);
  }

  async function checkDuplicate(): Promise<boolean> {
    return checkTabDuplicate(AUTH_KEY);
  }

  /** Busca la clínica por NIT y retorna nombre (para mostrar antes de elegir método) */
  async function buscarClinica(nit: string): Promise<{ nombre: string }> {
    const { data } = await http.post('/api/externo/buscar-clinica', { nit });
    return data.data;
  }

  /** Solicita un código de acceso (magic link por email o código OTP por SMS) */
  async function solicitarAcceso(nit: string, metodo: 'email' | 'sms'): Promise<void> {
    await http.post('/api/externo/solicitar-acceso', { nit, metodo });
  }

  /** Verifica el código OTP recibido por SMS */
  async function verificarOtp(nit: string, codigo: string): Promise<Clinica> {
    const { data } = await http.post('/api/externo/verificar-otp', { nit, codigo });
    clinica.value = data.data;
    if (data.token) setToken(data.token);
    return data.data;
  }

  /** Verifica el token del magic link recibido por correo */
  async function verificarMagicLink(token: string): Promise<{ nombre: string }> {
    const { data } = await http.post('/api/externo/verificar-magic-link', { token });
    clinica.value = data.data;
    if (data.token) setToken(data.token);
    return data.data;
  }

  /** Carga la sesión de clínica activa (para guards del router) */
  async function fetchClinica(): Promise<void> {
    const token = getToken();
    if (!token) {
      clinica.value = null;
      isHydrated.value = true;
      return;
    }
    try {
      const { data } = await http.get('/api/externo/clinica', {
        headers: {
          'X-Skip-Auth-Redirect': '1',
          'Authorization': `Bearer ${token}`,
        },
        timeout: 8000,
      });
      clinica.value = data.data;
    } catch {
      clinica.value = null;
      clearToken();
    } finally {
      isHydrated.value = true;
    }
  }

  /** Cierra la sesión de la clínica */
  async function logout(): Promise<void> {
    const token = getToken();
    if (token) {
      try {
        await http.post('/api/externo/logout', {}, {
          headers: { 'Authorization': `Bearer ${token}` },
        });
      } catch { /* ignore */ }
    }
    clearToken();
    clinica.value = null;
    isHydrated.value = false;
    window.location.href = '/login-externo';
  }

  return {
    clinica,
    isHydrated,
    isAuthenticated,
    getToken,
    checkDuplicate,
    buscarClinica,
    solicitarAcceso,
    verificarOtp,
    verificarMagicLink,
    fetchClinica,
    logout,
  };
});
