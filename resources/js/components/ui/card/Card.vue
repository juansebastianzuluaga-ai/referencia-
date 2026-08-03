<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue';
import { type VariantProps, cva } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const cardVariants = cva('rounded-xl border bg-card text-card-foreground shadow', {
  variants: {
    variant: {
      default: 'border-border',
      outline: 'border-2 border-border',
    },
  },
  defaultVariants: {
    variant: 'default',
  },
});

interface CardProps {
  class?: HTMLAttributes['class'];
  variant?: VariantProps<typeof cardVariants>['variant'];
}

const props = withDefaults(defineProps<CardProps>(), {
  variant: 'default',
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;
  return delegated;
});
</script>

<template>
  <div :class="cn(cardVariants({ variant }), props.class)" v-bind="delegatedProps">
    <slot />
  </div>
</template>
