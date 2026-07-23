<template>
  <el-tooltip v-if="visible" :content="text" placement="right" :disabled="!layout.isSidebarCollapsed">
    <router-link
      :to="to"
      class="flex items-center gap-2.5 px-3.5 py-2.5 mx-2 mb-2 rounded-2xl no-underline transition-all duration-200 relative overflow-hidden whitespace-nowrap neu-item"
      active-class="neu-item-active"
      @click="layout.closeMobileMenu()"
    >
      <div class="w-4.5 h-4.5 shrink-0 flex items-center justify-center">
        <component :is="resolvedIcon" class="w-4.5 h-4.5" />
      </div>
      <span 
        class="text-[13.5px] font-normal flex-1 transition-all duration-200 overflow-hidden"
        :class="layout.isSidebarCollapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[200px]'"
      >
        {{ text }}
      </span>
      <span 
        v-if="badge !== undefined"
        class="text-[10px] font-semibold bg-blue-600 text-white px-1.5 py-0.5 rounded-full transition-opacity"
        :class="layout.isSidebarCollapsed ? 'opacity-0' : 'opacity-100'"
      >
        {{ badge }}
      </span>
    </router-link>
  </el-tooltip>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useLayoutStore } from '@/stores/layout';
import { usePermission } from '@/composables/usePermission';
import * as icons from '@lucide/vue';

const props = defineProps<{
  to: string;
  icon: string;
  text: string;
  badge?: number | string;
  permission?: string;
}>();

const layout = useLayoutStore();
const { can } = usePermission();

const visible = computed(() => !props.permission || can(props.permission));

const resolvedIcon = computed(() => {
  return (icons as any)[props.icon] || icons.Circle;
});
</script>

<style scoped>
.neu-item {
  color: #8294b8;
  background: #1e2d55;
  box-shadow: 4px 4px 8px #16223f, -4px -4px 8px #26386b;
}
.neu-item:hover {
  color: #ffffff;
  box-shadow: 5px 5px 10px #16223f, -5px -5px 10px #26386b;
}
.neu-item-active {
  color: #ffffff !important;
  background: #1e2d55 !important;
  box-shadow: inset 4px 4px 8px #16223f, inset -4px -4px 8px #26386b !important;
}
</style>
