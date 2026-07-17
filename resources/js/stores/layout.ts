import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLayoutStore = defineStore('layout', () => {
  const isSidebarCollapsed = ref(false);
  const isMobileMenuOpen = ref(false);

  function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }

  function toggleMobileMenu() {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
  }

  function closeMobileMenu() {
    isMobileMenuOpen.value = false;
  }

  return {
    isSidebarCollapsed,
    isMobileMenuOpen,
    toggleSidebar,
    toggleMobileMenu,
    closeMobileMenu,
  };
});
