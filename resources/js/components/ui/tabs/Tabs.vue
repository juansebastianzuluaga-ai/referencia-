<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue';
import { type VariantProps, cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const tabsVariants = cva('inline-flex items-center justify-center rounded-lg p-1 text-muted-foreground', {
  variants: {
    variant: {
      default: 'bg-muted',
      outline: 'border border-input bg-background shadow-sm',
    },
  },
  defaultVariants: {
    variant: 'default',
  },
});

interface TabsProps {
  class?: HTMLAttributes['class'];
  variant?: VariantProps<typeof tabsVariants>['variant'];
}

const props = withDefaults(defineProps<TabsProps>(), {
  variant: 'default',
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;
  return delegated;
});
</script>

<template>
  <div :class="cn(tabsVariants({ variant }), props.class)" v-bind="delegatedProps">
    <slot />
  </div>
</template>
