import { defineStore } from 'pinia';
import { ref, watch } from 'vue';
import { useStorage } from '@vueuse/core';

// Store gemelo de `stores/layout.ts`, pero para el portal EXTERNO de
// clínicas — antes ambos portales compartían el mismo store (misma llave
// de localStorage), así que cambiar el modo oscuro en uno se reflejaba en
// el otro (y sobrevivía al cierre de sesión). Con un id y una llave de
// almacenamiento distintos, cada portal recuerda su propia preferencia.
export const useClinicaLayoutStore = defineStore('clinica-layout', () => {
  const isSidebarCollapsed = ref(false);
  const isMobileMenuOpen = ref(false);
  const isDarkMode = useStorage('dark-mode-clinica', false);

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
