import { defineStore } from 'pinia';
import { ref, watch } from 'vue';
import { useStorage } from '@vueuse/core';

export const useLayoutStore = defineStore('layout', () => {
  const isSidebarCollapsed = ref(false);
  const isMobileMenuOpen = ref(false);
  const isDarkMode = useStorage('dark-mode', false);

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
