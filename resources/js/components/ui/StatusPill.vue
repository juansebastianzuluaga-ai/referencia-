<template>
  <span 
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium"
    :class="colorClasses"
  >
    <slot></slot>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  type?: 'blue' | 'green' | 'amber' | 'red' | 'gray';
}>(), {
  type: 'gray'
});

const colorClasses = computed(() => {
  const map = {
    blue: 'bg-blue-50 text-blue-700',
    green: 'bg-green-50 text-green-700',
    amber: 'bg-amber-50 text-amber-700',
    red: 'bg-red-50 text-red-700',
    gray: 'bg-gray-100 text-gray-600',
  };
  return map[props.type];
});
</script>
