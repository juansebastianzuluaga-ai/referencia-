import { onMounted, onUnmounted } from 'vue';

/**
 * Sondea `callback` cada `intervalMs`, pausando mientras la pestaña no está
 * visible (evita llamadas innecesarias al servidor en segundo plano) y
 * ejecutando `callback` de inmediato al volver a la pestaña en vez de
 * esperar a que se cumpla el siguiente intervalo completo.
 */
export function usePolling(callback: () => void, intervalMs: number): void {
  let timer: ReturnType<typeof setInterval> | null = null;

  function handleVisibility(): void {
    if (document.visibilityState === 'visible') callback();
  }

  onMounted(() => {
    timer = setInterval(() => {
      if (document.visibilityState === 'visible') callback();
    }, intervalMs);
    document.addEventListener('visibilitychange', handleVisibility);
  });

  onUnmounted(() => {
    if (timer) clearInterval(timer);
    document.removeEventListener('visibilitychange', handleVisibility);
  });
}
