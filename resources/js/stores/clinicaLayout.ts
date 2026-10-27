import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { useStorage } from '@vueuse/core';
import { useClinicaAuthStore } from './clinicaAuth';

// Store gemelo de `stores/layout.ts`, pero para el portal EXTERNO de
// clínicas — antes ambos portales compartían el mismo store (misma llave
// de localStorage), así que cambiar el modo oscuro en uno se reflejaba en
// el otro (y sobrevivía al cierre de sesión). Con un id y una llave de
// almacenamiento distintos, cada portal recuerda su propia preferencia.
export const useClinicaLayoutStore = defineStore('clinica-layout', () => {
  const isSidebarCollapsed = ref(false);
  const isMobileMenuOpen = ref(false);
  const clinicaAuth = useClinicaAuthStore();

  // Preferencia por clínica — dos clínicas que entren desde el mismo
  // navegador conservan cada una su propio modo claro/oscuro.
  const darkModePrefs = useStorage<Record<string, boolean>>('dark-mode-clinica-by-user', {});
  const legacyDarkMode = useStorage<boolean | null>('dark-mode-clinica', null);
  const darkModeKey = computed(() => (clinicaAuth.clinica?.id != null ? `c${clinicaAuth.clinica.id}` : 'anon'));

  const isDarkMode = computed<boolean>({
    get: () => darkModePrefs.value[darkModeKey.value] ?? false,
    set: (val) => {
      darkModePrefs.value = { ...darkModePrefs.value, [darkModeKey.value]: val };
    },
  });

  watch(darkModeKey, (key) => {
    if (key !== 'anon' && !(key in darkModePrefs.value) && legacyDarkMode.value !== null) {
      darkModePrefs.value = { ...darkModePrefs.value, [key]: legacyDarkMode.value };
      legacyDarkMode.value = null;
    }
  }, { immediate: true });

  function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }

  function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
  }

  function closeMobileMenu() {
    isMobileMenuOpen.value = false;
  }

  function toggleDarkMode() {
    isDarkMode.value = !isDarkMode.value;
  }

  watch(isDarkMode, (dark) => {
    if (dark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }, { immediate: true });

  return {
    isSidebarCollapsed,
    isMobileMenuOpen,
    isDarkMode,
    toggleSidebar,
    toggleMobileMenu,
    closeMobileMenu,
    toggleDarkMode,
  };
});
