<template>
  <el-tooltip v-if="visible" :content="text" placement="right" :disabled="!layout.isSidebarCollapsed">
    <router-link 
      :to="to"
      class="flex items-center gap-2.5 px-3.5 py-2 mx-2 mb-0.5 rounded-lg text-gray-400 no-underline transition-colors relative overflow-hidden whitespace-nowrap hover:bg-white/5 hover:text-white"
      active-class="bg-blue-600/25 text-white before:absolute before:left-0 before:top-[20%] before:bottom-[20%] before:w-[3px] before:rounded-r-[3px] before:bg-blue-400"
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
