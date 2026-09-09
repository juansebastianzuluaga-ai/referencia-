// Content script inyectado en la aplicación de referencia (ver "matches" en
// manifest.json). Es el puente entre la página web (que no puede hablar
// directo con la extensión) y el background script:
//
//   página (postMessage) → content-bridge.js → background.js → Gomedisys
//
// Reenvía cada solicitud de autocompletar y devuelve el resultado.
//
// Se usa una conexión de puerto (chrome.runtime.connect), no un mensaje
// suelto — el diligenciamiento puede tardar varios segundos y un mensaje
// "de una sola vez" corre el riesgo de que Chrome dé el intercambio por
// cerrado antes de que llegue la respuesta.

console.log('[Gomedisys ext] content-bridge.js cargado en', location.href);

window.addEventListener('message', (ev) => {
  if (ev.source !== window) return;
  const datos = ev.data;
  if (!datos || datos.source !== 'referencia-app' || datos.type !== 'autocompletar') return;
  console.log('[Gomedisys ext] pedido recibido de la app:', datos);

  const responder = (resultado) => {
    console.log('[Gomedisys ext] respondiendo a la app:', resultado);
    window.postMessage(
      { source: 'gomedisys-extension', type: 'autocompletar-resultado', requestId: datos.requestId, ...resultado },
      window.location.origin,
    );
  };

  // "Extension context invalidated" es el mensaje típico que da Chrome
  // cuando la extensión se actualizó/recargó DESPUÉS de que esta pestaña
  // ya estaba abierta — la copia de este script que quedó en la página es
  // vieja y no puede hablar con la versión nueva. Recargar la pestaña (no
  // la extensión) arregla esto siempre — se lo decimos directo a la
  // persona en vez de un error genérico.
  const MENSAJE_RECARGAR = 'La extensión se actualizó después de que esta pestaña ya estaba abierta. Recarga esta página (F5) e intenta de nuevo.';

  // Cuando el contexto queda invalidado, `chrome.runtime` mismo puede
  // quedar en `undefined` (no solo sus métodos fallando) — eso da un error
  // genérico de JS ("Cannot read properties of undefined (reading
  // 'connect')") que no contiene la frase "context invalidated", así que
  // NO calzaba con la detección de abajo. Se revisa esto primero, antes de
  // siquiera intentar conectar, para no depender de adivinar el mensaje.
  if (typeof chrome === 'undefined' || !chrome.runtime || !chrome.runtime.id) {
    responder({ ok: false, error: MENSAJE_RECARGAR });
    return;
  }

  let puerto;
  try {
    puerto = chrome.runtime.connect({ name: 'referencia-bridge' });
  } catch (err) {
    const esInvalidada = /context invalidated/i.test(err.message || '');
    responder({ ok: false, error: esInvalidada ? MENSAJE_RECARGAR : 'No se pudo conectar con la extensión: ' + err.message });
    return;
  }

  let respondido = false;
  puerto.onMessage.addListener((resultado) => {
    respondido = true;
    responder(resultado);
    puerto.disconnect();
  });
  puerto.onDisconnect.addListener(() => {
    if (respondido) return;
    const detalle = chrome.runtime.lastError?.message || '';
    const esInvalidada = /context invalidated|Extension context/i.test(detalle);
    responder({
      ok: false,
      error: esInvalidada ? MENSAJE_RECARGAR : 'Se perdió la conexión con la extensión antes de recibir una respuesta.',
    });
  });
  try {
    puerto.postMessage({ type: 'autocompletar', solicitud: datos.solicitud });
  } catch (err) {
    responder({ ok: false, error: /context invalidated/i.test(err.message || '') ? MENSAJE_RECARGAR : 'No se pudo enviar el pedido a la extensión: ' + err.message });
  }
});
