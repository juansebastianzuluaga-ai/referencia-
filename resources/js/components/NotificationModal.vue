<template>
  <Teleport to="body">
    <Transition name="notify-modal">
      <div v-if="notifyState.current" class="notify-overlay" @click="onOverlayClick">
        <div class="notify-card" :class="`notify-card--${notifyState.current.type}`" @click.stop>
          <button class="notify-close" @click="dismissCurrent" aria-label="Cerrar">
            <component :is="XIcon" class="w-4 h-4" />
          </button>

          <div class="notify-icon-badge">
            <component :is="iconFor(notifyState.current.type)" class="w-7 h-7" />
          </div>

          <p class="notify-message">{{ notifyState.current.message }}</p>

          <div class="notify-progress-track">
            <div
              class="notify-progress-bar"
              :key="notifyState.current.id"
              :style="{ animationDuration: `${notifyState.current.duration}ms` }"
            ></div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import {
  CheckCircle2 as CheckCircleIcon,
  XCircle as XCircleIcon,
  AlertTriangle as AlertTriangleIcon,
  Info as InfoIcon,
  X as XIcon,
} from '@lucide/vue';
import { notifyState, dismissCurrent, type NotifyType } from '@/plugins/toast';

let timer: ReturnType<typeof setTimeout> | null = null;

function iconFor(type: NotifyType) {
  switch (type) {
    case 'success':
      return CheckCircleIcon;
    case 'error':
      return XCircleIcon;
    case 'warning':
      return AlertTriangleIcon;
    default:
      return InfoIcon;
  }
}

function onOverlayClick() {
  dismissCurrent();
}

watch(
  () => notifyState.current,
  (item) => {
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }
    if (item) {
      timer = setTimeout(() => {
        dismissCurrent();
      }, item.duration);
    }
  },
  { immediate: true },
);
</script>

<style scoped>
.notify-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(11, 26, 51, 0.35);
  backdrop-filter: blur(4px);
  padding: 16px;
}

.notify-card {
  position: relative;
  width: 100%;
  max-width: 360px;
  background: #fff;
  border-radius: 18px;
  padding: 28px 24px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 24px 60px rgba(11, 35, 73, 0.35);
  overflow: hidden;
}

.notify-close {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  cursor: pointer;
  transition: background .2s ease, color .2s ease;
}
.notify-close:hover {
  background: #e2e8f0;
  color: #334155;
}

.notify-icon-badge {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  color: #fff;
}

.notify-card--success .notify-icon-badge { background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%); }
.notify-card--error .notify-icon-badge { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); }
.notify-card--warning .notify-icon-badge { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
.notify-card--info .notify-icon-badge { background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); }

.notify-message {
  font-size: 14.5px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.5;
  margin: 0 0 18px;
}

.notify-progress-track {
  width: 100%;
  height: 4px;
  border-radius: 999px;
  background: #eef2f7;
  overflow: hidden;
}

.notify-progress-bar {
  height: 100%;
  width: 100%;
  transform-origin: left;
  animation-name: notify-shrink;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

.notify-card--success .notify-progress-bar { background: linear-gradient(90deg, #16a34a, #22c55e); }
.notify-card--error .notify-progress-bar { background: linear-gradient(90deg, #dc2626, #ef4444); }
.notify-card--warning .notify-progress-bar { background: linear-gradient(90deg, #d97706, #f59e0b); }
.notify-card--info .notify-progress-bar { background: linear-gradient(90deg, #0D2D6B, #16468E); }

@keyframes notify-shrink {
  from { transform: scaleX(1); }
  to { transform: scaleX(0); }
}

.notify-modal-enter-active,
.notify-modal-leave-active {
  transition: opacity .2s ease;
}
.notify-modal-enter-from,
.notify-modal-leave-to {
  opacity: 0;
}
.notify-modal-enter-active .notify-card,
.notify-modal-leave-active .notify-card {
  transition: transform .25s ease, opacity .25s ease;
}
.notify-modal-enter-from .notify-card {
  transform: scale(0.9) translateY(8px);
  opacity: 0;
}
.notify-modal-leave-to .notify-card {
  transform: scale(0.95);
  opacity: 0;
}
</style>
