<template>
  <div class="bg-white border border-gray-200 rounded-xl p-4 lg:py-4 lg:px-4.5 shadow-sm flex items-center gap-3.5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md animate-fade-in-stagger">
    <div 
      class="w-10.5 h-10.5 rounded-lg flex items-center justify-center shrink-0"
      :class="colorClasses"
    >
      <component :is="resolvedIcon" class="w-5 h-5" />
    </div>
    <div>
      <div class="text-2xl font-semibold text-gray-900 leading-tight font-mono">{{ value }}</div>
      <div class="text-xs text-gray-400 mt-0.5">{{ label }}</div>
      <div 
        v-if="delta" 
        class="text-[11px] font-medium mt-1"
        :class="{
          'text-green-600': deltaType === 'up',
          'text-red-600': deltaType === 'down',
          'text-gray-400': deltaType === 'neutral'
        }"
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

const colorClasses = computed(() => {
  const map = {
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-green-50 text-green-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
  };
  return map[props.color];
});

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
