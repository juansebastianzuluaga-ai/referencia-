import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';
import { useStorage } from '@vueuse/core';
import { useAuthStore } from './auth';

export const useLayoutStore = defineStore('layout', () => {
  const isSidebarCollapsed = ref(false);
  const isMobileMenuOpen = ref(false);
  const auth = useAuthStore();

  // La preferencia se guarda por usuario — antes era una sola llave global
  // ('dark-mode'), así que activar el modo oscuro con una cuenta se lo
  // aplicaba a todas las demás que usaran el mismo navegador.
  const darkModePrefs = useStorage<Record<string, boolean>>('dark-mode-by-user', {});
  const legacyDarkMode = useStorage<boolean | null>('dark-mode', null);
  const darkModeKey = computed(() => (auth.user?.id != null ? `u${auth.user.id}` : 'anon'));

  const isDarkMode = computed<boolean>({
    get: () => darkModePrefs.value[darkModeKey.value] ?? false,
    set: (val) => {
      darkModePrefs.value = { ...darkModePrefs.value, [darkModeKey.value]: val };
    },
  });

  // Migra la preferencia global anterior al primer usuario que inicie sesión.
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
