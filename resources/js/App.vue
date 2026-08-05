<template>
  <router-view v-slot="{ Component }">
    <transition name="route-fade" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
  <NotificationModal />
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import AOS from 'aos';
import NotificationModal from '@/components/NotificationModal.vue';

onMounted(() => {
  AOS.init({
    duration: 600,
    easing: 'ease-out-cubic',
    once: true,
    offset: 40,
  });
});
</script>

<style>
.route-fade-enter-active {
  transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.route-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.route-fade-enter-from {
  opacity: 0;
  transform: translateY(12px) scale(0.99);
}
.route-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.99);
}
</style>
