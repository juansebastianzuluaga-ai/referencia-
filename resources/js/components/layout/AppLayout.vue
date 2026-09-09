<template>
  <div class="flex flex-col h-screen overflow-hidden text-gray-700" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">
    <AppHeader />

    <!-- Overlay móvil -->
    <div 
      v-if="layout.isMobileMenuOpen" 
      class="fixed inset-0 bg-black/40 z-40 md:hidden" 
      @click="layout.closeMobileMenu"
    ></div>

    <div class="flex flex-1 overflow-hidden pt-[56px]">
      <AppSidebar />

      <main class="flex-1 flex flex-col min-h-0 p-4 overflow-hidden" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useLayoutStore } from '@/stores/layout';
import AppSidebar from './AppSidebar.vue';
import AppHeader from './AppHeader.vue';

const layout = useLayoutStore();
</script>

<style scoped>
.fade-enter-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
