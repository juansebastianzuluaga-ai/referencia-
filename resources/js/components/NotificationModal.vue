<template>
  <Teleport to="body">
    <Transition name="notify-modal">
      <div v-if="notifyState.current" class="notify-overlay" role="presentation" @click="onOverlayClick">
        <div
          class="notify-card"
          role="alertdialog"
          aria-modal="true"
          :aria-label="notifyState.current.message"
          @click.stop
        >
          <button class="notify-close" type="button" @click="dismissCurrent" aria-label="Cerrar">
            <component :is="XIcon" class="w-4 h-4" />
          </button>

          <div class="notify-head">
            <div class="notify-head-pattern"></div>
            <div class="notify-icon-badge" :class="`notify-icon-badge--${notifyState.current.type}`">
              <component :is="iconFor(notifyState.current.type)" class="w-5 h-5" />
            </div>
          </div>

          <div class="notify-body">
            <p class="notify-message">{{ notifyState.current.message }}</p>
          </div>

          <div class="notify-footer">
            <button
              type="button"
              class="notify-ok-btn"
              :class="`notify-ok-btn--${notifyState.current.type}`"
              @click="dismissCurrent"
            >
              Entendido
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import {
  CheckCircle2 as CheckCircleIcon,
  XCircle as XCircleIcon,
  AlertTriangle as AlertTriangleIcon,
  Info as InfoIcon,
  X as XIcon,
} from '@lucide/vue';
import { notifyState, dismissCurrent, type NotifyType } from '@/plugins/toast';

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
</script>

<style scoped>
.notify-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(11, 26, 51, 0.4);
  backdrop-filter: blur(4px);
  padding: 16px;
}

.notify-card {
  position: relative;
  width: 100%;
  max-width: 380px;
  background: #fff;
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(11, 35, 73, 0.28), 0 0 0 1px rgba(15,23,42,.04);
}

.notify-close {
  position: absolute;
  top: .75rem; right: .75rem;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 28px; height: 28px;
  border-radius: 50%;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #94a3b8;
  cursor: pointer;
  transition: all .2s ease;
}
.notify-close:hover { color: #475569; border-color: #cbd5e1; background: #f8fafc; }

/* Header claro — mismo lenguaje que el modal de detalle: fondo blanco,
   patrón de puntos sutil, ícono pequeño en cuadrado con tinte suave (nada
   de banners de color saturado, que se ve más a app de consumo que a
   herramienta clínica). */
.notify-head {
  position: relative;
  overflow: hidden;
  padding: 1.3rem 1.5rem 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
}
.notify-head-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(79,70,229,.05) 1.4px, transparent 1.4px);
  background-size: 15px 15px;
  -webkit-mask-image: radial-gradient(circle at center, rgba(0,0,0,.9), transparent 75%);
  mask-image: radial-gradient(circle at center, rgba(0,0,0,.9), transparent 75%);
  pointer-events: none;
}

.notify-icon-badge {
  position: relative; z-index: 1;
  width: 52px; height: 52px;
  border-radius: 15px;
  display: grid; place-items: center;
  box-shadow: 0 4px 12px rgba(15,23,42,.06);
}
.notify-icon-badge--success { background: #dcfce7; color: #16a34a; }
.notify-icon-badge--error   { background: #fee2e2; color: #dc2626; }
.notify-icon-badge--warning { background: #fef3c7; color: #d97706; }
.notify-icon-badge--info    { background: #e0e7ff; color: #4f46e5; }

.notify-body {
  padding: .9rem 1.6rem 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.notify-message {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.55;
  margin: 0;
}

.notify-footer {
  padding: 1.3rem 1.6rem 1.5rem;
}

.notify-ok-btn {
  width: 100%;
  padding: .65rem 1rem;
  border: none;
  border-radius: 11px;
  color: #fff;
  font-size: .85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s ease;
}
.notify-ok-btn--success { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 4px 14px rgba(22,163,74,.25); }
.notify-ok-btn--success:hover { background: linear-gradient(135deg, #34d399, #22c55e); box-shadow: 0 6px 18px rgba(22,163,74,.32); }
.notify-ok-btn--error { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 14px rgba(220,38,38,.25); }
.notify-ok-btn--error:hover { background: linear-gradient(135deg, #f87171, #ef4444); box-shadow: 0 6px 18px rgba(220,38,38,.32); }
.notify-ok-btn--warning { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(217,119,6,.25); }
.notify-ok-btn--warning:hover { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 6px 18px rgba(217,119,6,.32); }
.notify-ok-btn--info { background: linear-gradient(135deg, #4F46E5, #7C3AED); box-shadow: 0 4px 14px rgba(79,70,229,.25); }
.notify-ok-btn--info:hover { background: linear-gradient(135deg, #6366f1, #8b5cf6); box-shadow: 0 6px 18px rgba(79,70,229,.32); }

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
  transform: scale(0.94) translateY(6px);
  opacity: 0;
}
.notify-modal-leave-to .notify-card {
  transform: scale(0.96);
  opacity: 0;
}
</style>
