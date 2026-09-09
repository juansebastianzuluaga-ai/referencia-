import type { Ref } from 'vue';

/**
 * Reemplaza `actual.value` por `nuevos` SOLO si algo realmente cambió
 * (comparando cada registro por su `id`). Si todo sigue igual, no toca el
 * ref — evita el re-render/parpadeo que causaría un `usePolling` que
 * reasigna el arreglo entero cada vez, aunque el servidor devuelva
 * exactamente lo mismo.
 *
 * Devuelve los ids que cambiaron o son nuevos, para que el que llama pueda
 * resaltar solo esas filas en vez de refrescar toda la tabla.
 */
export function actualizarSiCambio<T extends { id: number }>(actual: Ref<T[]>, nuevos: T[]): number[] {
  const previos = new Map(actual.value.map(item => [item.id, JSON.stringify(item)]));
  const cambiados: number[] = [];

  for (const item of nuevos) {
    if (previos.get(item.id) !== JSON.stringify(item)) {
      cambiados.push(item.id);
    }
  }

  const nuevosIds = new Set(nuevos.map(item => item.id));
  const huboEliminados = actual.value.some(item => !nuevosIds.has(item.id));

  if (cambiados.length === 0 && !huboEliminados) {
    return [];
  }

  actual.value = nuevos;
  return cambiados;
}
