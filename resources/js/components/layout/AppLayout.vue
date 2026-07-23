<template>
  <div class="flex flex-col h-screen overflow-hidden text-gray-700" style="background:#e8ecf1;">
    <!-- Overlay móvil -->
    <div 
      v-if="layout.isMobileMenuOpen" 
      class="fixed inset-0 bg-black/40 z-40 md:hidden" 
      @click="layout.closeMobileMenu"
    ></div>

    <AppHeader />

    <div class="flex flex-1 mt-[56px] overflow-hidden">
      <AppSidebar />

      <main class="flex-1 flex flex-col min-h-0 p-4 overflow-hidden" style="background:#e8ecf1;">
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
import AppHeader from './AppHeader.vue';
import AppSidebar from './AppSidebar.vue';

const layout = useLayoutStore();
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
