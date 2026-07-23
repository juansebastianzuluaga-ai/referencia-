<template>
  <div
    class="rounded-2xl p-5 flex items-center gap-4 transition-all duration-200 animate-fade-in-stagger"
    style="background:#e8ecf1; box-shadow: 8px 8px 16px #c5c9d0, -8px -8px 16px #ffffff;"
  >
    <div
      class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
      :style="iconContainerStyle"
    >
      <component :is="resolvedIcon" class="w-5 h-5" :style="{ color: iconColor }" />
    </div>
    <div>
      <div class="text-2xl font-bold leading-tight" style="color:#1e2d55;">{{ value }}</div>
      <div class="text-xs mt-0.5" style="color:#8a9ab5;">{{ label }}</div>
      <div
        v-if="delta"
        class="text-[11px] font-semibold mt-1"
        :class="{
          'text-green-600': deltaType === 'up',
          'text-red-500': deltaType === 'down',
        }"
        :style="deltaType === 'neutral' ? 'color:#8a9ab5' : ''"
      >
        {{ deltaPrefix }} {{ delta }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import * as icons from '@lucide/vue';

const props = withDefaults(defineProps<{
  value: string | number;
  label: string;
  icon: string;
  color?: 'blue' | 'green' | 'amber' | 'red';
  delta?: string;
  deltaType?: 'up' | 'down' | 'neutral';
}>(), {
  color: 'blue',
  deltaType: 'neutral'
});

const resolvedIcon = computed(() => {
  return (icons as any)[props.icon] || icons.Circle;
});

const colorMap = {
  blue:  { bg: '#dbe1ff', shadow: '#c2c8e8, #f4f6ff', color: '#3b5bdb' },
  green: { bg: '#d3f9d8', shadow: '#b4dab9, #f2fff4', color: '#2f9e44' },
  amber: { bg: '#fff3cd', shadow: '#e0d5a8, #fffff5', color: '#e67700' },
  red:   { bg: '#ffe0e0', shadow: '#e0bfbf, #fff8f8', color: '#c92a2a' },
};

const iconContainerStyle = computed(() => {
  const c = colorMap[props.color];
  return `background:${c.bg}; box-shadow: inset 3px 3px 6px ${c.shadow.split(',')[0]}, inset -3px -3px 6px ${c.shadow.split(',')[1]};`;
});

const iconColor = computed(() => colorMap[props.color].color);

const deltaPrefix = computed(() => {
  if (props.deltaType === 'up') return '↑';
  if (props.deltaType === 'down') return '↓';
  return '';
});
</script>

<style scoped>
.animate-fade-in-stagger {
  animation: fadeIn 0.25s ease both;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}
</style>
