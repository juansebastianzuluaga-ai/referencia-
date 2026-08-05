<template>
  <el-tooltip v-if="visible" :content="text" placement="right" :disabled="!layout.isSidebarCollapsed">
    <router-link
      :to="to"
      class="menu-item no-underline"
      :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }"
      active-class="menu-item-active"
      @click="layout.closeMobileMenu()"
    >
      <span class="menu-accent-bar"></span>
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
.menu-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 12px 14px;
  border-radius: 14px;
  margin: 0 16px 8px;
  cursor: pointer;
  transition: all .2s ease;
  position: relative;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.04);
  white-space: nowrap;
  overflow: hidden;
}
.menu-item:hover {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.08);
}

.menu-accent-bar {
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 3px; height: 60%;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #7eb3ff, #16468e);
  box-shadow: 0 0 8px rgba(126,179,255,0.4);
  opacity: 0;
  transition: opacity .2s ease;
}

.menu-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px; height: 32px;
  border-radius: 10px;
  background: rgba(255,255,255,0.05);
  color: #6b82a8;
  transition: all .2s ease;
  flex-shrink: 0;
}
.menu-item:hover .menu-icon-wrap {
  color: #8294b8;
  background: rgba(255,255,255,0.08);
}

.menu-label {
  font-size: 13.5px;
  font-weight: 500;
  color: #8294b8;
  transition: all .2s ease;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.menu-item:hover .menu-label {
  color: #a0b3d0;
}

/* Active state */
.menu-item-active {
  background: linear-gradient(135deg, rgba(22,70,142,0.5), rgba(13,45,107,0.6)) !important;
  border-color: rgba(126,179,255,0.15) !important;
  box-shadow: 0 4px 14px rgba(22,70,142,0.3), inset 0 1px 0 rgba(255,255,255,0.06) !important;
}
.menu-item-active:hover {
  background: linear-gradient(135deg, rgba(22,70,142,0.55), rgba(13,45,107,0.65)) !important;
}
.menu-item-active .menu-accent-bar {
  opacity: 1;
}
.menu-item-active .menu-icon-wrap {
  background: linear-gradient(135deg, rgba(126,179,255,0.2), rgba(22,70,142,0.3));
  color: #7eb3ff;
  box-shadow: 0 2px 8px rgba(126,179,255,0.15);
}
.menu-item-active .menu-label {
  color: #ffffff;
  font-weight: 700;
}

/* Collapsed state */
.menu-item-collapsed {
  margin: 0 8px 8px;
  padding: 10px 0;
  justify-content: center;
  gap: 0;
}
.menu-item-collapsed .menu-icon-wrap {
  width: 36px;
  height: 36px;
}
.menu-item-collapsed .menu-accent-bar {
  left: -8px;
}
</style>
