<template>
  <slot v-if="hasAccess" />
  <slot v-else name="fallback" />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePermission } from '@/composables/usePermission';

const props = defineProps<{
  permission?: string;
  any?: string[];
  all?: string[];
}>();

const { can, canAny, canAll } = usePermission();

const hasAccess = computed(() => {
  if (props.all && props.all.length > 0) {
    return canAll(props.all);
  }
  if (props.any && props.any.length > 0) {
    return canAny(props.any);
  }
  if (props.permission) {
    return can(props.permission);
  }
  return true;
});
</script>
