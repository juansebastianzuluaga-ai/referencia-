<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue';
import { type VariantProps, cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const tabsTriggerVariants = cva(
  'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow',
        outline: 'border border-input bg-background shadow-sm data-[state=active]:bg-accent data-[state=active]:text-accent-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

interface TabsTriggerProps {
  class?: HTMLAttributes['class'];
  value: string;
  variant?: VariantProps<typeof tabsTriggerVariants>['variant'];
}

const props = withDefaults(defineProps<TabsTriggerProps>(), {
  variant: 'default',
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;
  return delegated;
});
</script>

<template>
  <button
    :class="cn(tabsTriggerVariants({ variant }), props.class)"
    v-bind="delegatedProps"
    :value="value"
  >
    <slot />
  </button>
</template>
