<template>
  <el-drawer
    :model-value="modelValue"
    @update:model-value="(val: boolean) => emit('update:modelValue', val)"
    :size="size"
    class="!p-0 base-drawer"
    append-to-body
    :destroy-on-close="destroyOnClose"
    :close-on-click-modal="closeOnClickModal"
    :close-on-press-escape="closeOnPressEscape"
    :show-close="false"
    @closed="emit('closed')"
  >
    <template #header>
      <div class="flex items-center justify-between gap-3 px-5 py-4 bg-[var(--blue-800)]">
        <div class="flex items-center gap-3 min-w-0">
          <div v-if="icon" class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
            <component :is="icon" class="w-5 h-5 text-white" />
          </div>
          <div class="min-w-0">
            <h3 class="text-[15px] font-semibold text-white leading-tight truncate">
              {{ title }}
            </h3>
            <p v-if="subtitle" class="text-xs text-[var(--blue-300)] mt-0.5 truncate">
              {{ subtitle }}
            </p>
          </div>
        </div>
        <button
          type="button"
          class="w-8 h-8 rounded-md flex items-center justify-center text-[var(--blue-300)] hover:bg-white/10 hover:text-white transition-colors cursor-pointer shrink-0"
          @click="handleClose"
        >
          <XIcon class="w-4.5 h-4.5" />
        </button>
      </div>
    </template>

    <div class="px-5 pt-4">
      <slot></slot>
    </div>

    <template #footer v-if="showFooter">
      <div class="flex justify-end gap-2 px-5 py-4">
        <slot name="footer">
          <el-button :icon="XIcon" @click="handleClose">{{ cancelText }}</el-button>
          <el-button
            v-if="mode !== 'close-only'"
            type="primary"
            :icon="confirmIcon"
            :loading="loading"
            @click="emit('confirm')"
          >
            {{ confirmText }}
          </el-button>
        </slot>
      </div>
    </template>
  </el-drawer>
</template>

<script setup lang="ts">
import { X as XIcon, Save as SaveIcon, type LucideIcon } from '@lucide/vue';

withDefaults(defineProps<{
  modelValue: boolean;
  title: string;
  subtitle?: string;
  icon?: LucideIcon;
  size?: string | number;
  loading?: boolean;
  showFooter?: boolean;
  /** 'form' shows Cancel + Confirm buttons. 'close-only' shows just a Close/Cancel button (for detail views). */
  mode?: 'form' | 'close-only';
  cancelText?: string;
  confirmText?: string;
  confirmIcon?: LucideIcon;
  destroyOnClose?: boolean;
  closeOnClickModal?: boolean;
  closeOnPressEscape?: boolean;
}>(), {
  subtitle: undefined,
  icon: undefined,
  size: '500px',
  loading: false,
  showFooter: true,
  mode: 'form',
  cancelText: 'Cancelar',
  confirmText: 'Guardar',
  confirmIcon: () => SaveIcon,
  destroyOnClose: true,
  closeOnClickModal: false,
  closeOnPressEscape: true,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'confirm'): void;
  (e: 'cancel'): void;
  (e: 'closed'): void;
}>();

function handleClose() {
  emit('update:modelValue', false);
  emit('cancel');
}
</script>

<style>
.base-drawer.el-drawer {
  padding: 0 !important;
}
.base-drawer .el-drawer__header {
  padding: 0 !important;
  margin: 0 !important;
}
.base-drawer .el-drawer__body {
  padding: 0 !important;
}
.base-drawer .el-drawer__footer {
  padding: 0 !important;
  border-top: 1px solid var(--gray-200, #e2e8f0);
}
</style>
