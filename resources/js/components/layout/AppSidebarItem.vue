<template>
  <el-tooltip v-if="visible" :content="text" placement="right" :disabled="!layout.isSidebarCollapsed">
    <router-link
      :to="to"
      class="menu-item no-underline"
      :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }"
      active-class="menu-item-active"
      @click="layout.closeMobileMenu()"
    >
      <div class="menu-icon-wrap">
        <component :is="resolvedIcon" class="shrink-0" style="width:20px; height:20px;" />
      </div>
      <span
        class="menu-label"
        :class="layout.isSidebarCollapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[240px]'"
      >
        {{ text }}
      </span>
      <span
        v-if="badge !== undefined"
        class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full transition-opacity"
        :class="layout.isSidebarCollapsed ? 'opacity-0' : 'opacity-100'"
        style="background: #fff; color: var(--rf-primary);"
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
.menu-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 10px 14px;
  border-radius: 12px;
  margin: 0 12px 4px;
  cursor: pointer;
  transition: all .2s ease;
  position: relative;
  white-space: nowrap;
  overflow: hidden;
}
.menu-item:hover {
  background: rgba(255,255,255,0.14);
}
.dark .menu-item:hover {
  background: rgba(255,255,255,0.06);
}

.menu-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px; height: 30px;
  border-radius: 9px;
  color: rgba(255,255,255,0.75);
  transition: all .2s ease;
  flex-shrink: 0;
}
.menu-item:hover .menu-icon-wrap {
  color: #ffffff;
}
.dark .menu-icon-wrap { color: #64748b; }
.dark .menu-item:hover .menu-icon-wrap { color: #a5b4fc; }

.menu-label {
  font-size: 13.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  transition: all .2s ease;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.menu-item:hover .menu-label {
  color: #ffffff;
}
.dark .menu-label { color: #94a3b8; }
.dark .menu-item:hover .menu-label { color: #e2e8f0; }

/* Active state: sobre fondo azul se invierte a pastilla blanca; en modo
   oscuro (fondo casi negro) sigue siendo la pastilla con degradado. */
.menu-item-active {
  background: #ffffff !important;
  box-shadow: 0 4px 14px rgba(15,23,42,0.18) !important;
}
.menu-item-active:hover {
  background: #ffffff !important;
}
.menu-item-active .menu-icon-wrap {
  color: var(--rf-primary) !important;
}
.menu-item-active .menu-label {
  color: var(--rf-primary) !important;
  font-weight: 700;
}
.dark .menu-item-active {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)) !important;
  box-shadow: 0 6px 16px rgba(22,70,142,0.35) !important;
}
.dark .menu-item-active:hover {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)) !important;
}
.dark .menu-item-active .menu-icon-wrap {
  color: #ffffff;
}
.dark .menu-item-active .menu-label {
  color: #ffffff;
  font-weight: 700;
}

/* Collapsed state */
.menu-item-collapsed {
  margin: 0 8px 4px;
  padding: 10px 0;
  justify-content: center;
  gap: 0;
}
.menu-item-collapsed .menu-icon-wrap {
  width: 34px;
  height: 34px;
}
</style>
