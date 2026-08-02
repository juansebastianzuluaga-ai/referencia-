<template>
  <div ref="referenceRef" class="inline-flex" @mouseenter="show" @mouseleave="hide" @focus="show" @blur="hide">
    <slot />
    <div
      v-if="isVisible"
      ref="floatingRef"
      class="floating-tooltip z-[9999] px-3 py-2 rounded-lg text-xs font-medium shadow-lg"
      :style="{ position: 'absolute', left: position.x + 'px', top: position.y + 'px' }"
    >
      <slot name="content">{{ text }}</slot>
      <div class="floating-tooltip-arrow" :style="arrowStyle" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { computePosition, offset, flip, shift, arrow, autoUpdate } from '@floating-ui/vue';

const props = withDefaults(defineProps<{
  text?: string;
  placement?: 'top' | 'bottom' | 'left' | 'right';
}>(), {
  text: '',
  placement: 'top',
});

const referenceRef = ref<HTMLElement | null>(null);
const floatingRef = ref<HTMLElement | null>(null);
const arrowRef = ref<HTMLElement | null>(null);
const isVisible = ref(false);
const position = reactive({ x: 0, y: 0 });
const arrowStyle = reactive({ left: '', top: '' });

let cleanup: (() => void) | null = null;

function show() {
  isVisible.value = true;
}

function hide() {
  isVisible.value = false;
}

watch(isVisible, async (visible) => {
  if (!visible || !referenceRef.value || !floatingRef.value) {
    cleanup?.();
    return;
  }

  cleanup = autoUpdate(referenceRef.value, floatingRef.value, async () => {
    if (!referenceRef.value || !floatingRef.value) return;
    const { x, y, placement, middlewareData } = await computePosition(
      referenceRef.value,
      floatingRef.value,
      {
        placement: props.placement,
        middleware: [offset(8), flip(), shift({ padding: 8 }), arrow({ element: arrowRef })],
      }
    );
    position.x = x;
    position.y = y;

    const arrowData = middlewareData.arrow;
    const side = placement.split('-')[0];
    const arrowX = arrowData?.x ?? 0;
    const arrowY = arrowData?.y ?? 0;

    const staticSide = {
      top: 'bottom',
      right: 'left',
      bottom: 'top',
      left: 'right',
    }[side] as string;

    arrowStyle.left = arrowX ? `${arrowX}px` : '';
    arrowStyle.top = arrowY ? `${arrowY}px` : '';
    arrowStyle[staticSide] = '-4px';
  });
}, { flush: 'post' });
</script>

<style scoped>
.floating-tooltip {
  background: #1e2d55;
  color: #fff;
  max-width: 280px;
  line-height: 1.4;
  pointer-events: none;
  animation: ft-in 0.2s ease;
}

.floating-tooltip-arrow {
  position: absolute;
  width: 8px;
  height: 8px;
  background: #1e2d55;
  transform: rotate(45deg);
}

@keyframes ft-in {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>
