// Service worker de la extensión. Recibe el pedido de "autocompletar" desde
// content-bridge.js (que a su vez lo recibió de la página de la app), busca
// la pestaña de Gomedisys que el personal ya tiene abierta y logueada, la
// trae al frente, y coordina con content-gomedisys.js para que diligencie
// el formulario ahí mismo.
//
// Nunca abre una pestaña nueva ni toca el login — si no hay una pestaña de
// Gomedisys abierta, responde con un error claro para que la persona la
// abra ella misma primero.
//
// La coordinación con la pestaña de Gomedisys va por chrome.storage.session
// (no por una conexión directa): el clic en el menú de Gomedisys dispara
// una navegación real de página (confirmado probando en vivo), lo que mata
// cualquier puerto/mensaje en curso. storage.session sí sobrevive a la
// navegación — content-gomedisys.js retoma el trabajo solo en la página
// nueva, y aquí simplemente se escucha el cambio de estado.
//
// El puerto hacia la app (puertoApp, vía content-bridge.js) sí se mantiene
// abierto todo el tiempo — esa pestaña nunca navega, así que no hay riesgo
// ahí, y mantiene vivo a este service worker mientras se espera el
// resultado.

console.log('[Gomedisys ext] background.js arrancó.');

// Por defecto, chrome.storage.session SOLO es accesible desde contextos de
// la propia extensión (background, popup) — un content script (como
// content-gomedisys.js, que corre "adentro" de la página de Gomedisys) lo
// tiene bloqueado a menos que se habilite explícitamente así.
chrome.storage.session.setAccessLevel({ accessLevel: 'TRUSTED_AND_UNTRUSTED_CONTEXTS' }).catch((err) => {
  console.error('[Gomedisys ext] no se pudo habilitar storage.session para content scripts:', err);
});

const CLAVE_TRABAJO = 'gomedisysTrabajo';
// Con los reintentos automáticos de campos lentos (Ciudad de Origen,
// diagnóstico) sumando hasta ~36s cada uno en el peor caso, 45s se quedaba
// corto para todo el formulario completo — se sube a 100s de margen total.
const LIMITE_MS = 100000;

chrome.runtime.onConnect.addListener((puertoApp) => {
  console.log('[Gomedisys ext] puerto entrante:', puertoApp.name);
  if (puertoApp.name !== 'referencia-bridge') return;

  puertoApp.onMessage.addListener(async (mensaje) => {
    console.log('[Gomedisys ext] mensaje de la app:', mensaje);
    if (mensaje?.type !== 'autocompletar') return;

    try {
      const tabs = await chrome.tabs.query({ url: '*://*.gomedisys.com/*' });
      console.log('[Gomedisys ext] pestañas de Gomedisys encontradas:', tabs.map((t) => ({ id: t.id, url: t.url })));
      const [tabGomedisys] = tabs;
      if (!tabGomedisys) {
        puertoApp.postMessage({
          ok: false,
          error: 'No encontré ninguna pestaña de Gomedisys abierta. Ábrela, inicia sesión, y vuelve a intentar.',
        });
        return;
      }

      await chrome.tabs.update(tabGomedisys.id, { active: true });
      await chrome.windows.update(tabGomedisys.windowId, { focused: true });

      // Se inyecta a propósito antes de empezar (no basta con la inyección
      // automática de manifest.json) — si la pestaña llevaba abierta desde
      // antes de la última recarga de la extensión, la copia ya presente en
      // la página quedó con las APIs de extensión invalidadas. Después de
      // esto, content-gomedisys.js tiene su propia guarda para no
      // inicializarse dos veces si ya hay una copia viva.
      try {
        await chrome.scripting.executeScript({
          target: { tabId: tabGomedisys.id },
          files: ['content-gomedisys.js'],
        });
        console.log('[Gomedisys ext] content-gomedisys.js inyectado (o ya estaba) en la pestaña', tabGomedisys.id);
      } catch (err) {
        console.error('[Gomedisys ext] no se pudo inyectar content-gomedisys.js:', err);
        puertoApp.postMessage({ ok: false, error: 'No se pudo preparar la pestaña de Gomedisys: ' + err.message });
        return;
      }

      let terminado = false;

      // Con la navegación real (varios segundos) + espera del formulario,
      // todo el proceso puede tardar más de lo que Chrome deja "dormido"
      // a un service worker sin actividad — aunque el puerto siga abierto,
      // eso solo no siempre alcanza. Este pulso llama a una API real de la
      // extensión cada pocos segundos mientras se espera, para que Chrome
      // vea actividad y no lo apague a mitad de camino.
      const pulso = setInterval(() => {
        chrome.runtime.getPlatformInfo(() => {});
      }, 10000);

      const limite = setTimeout(() => {
        if (terminado) return;
        terminado = true;
        clearInterval(pulso);
        chrome.storage.onChanged.removeListener(escuchar);
        console.log('[Gomedisys ext] se agotó el tiempo esperando el resultado.');
        puertoApp.postMessage({
          ok: false,
          error: 'Gomedisys no terminó a tiempo. Revisa esa pestaña — puede que necesites completarla a mano.',
        });
      }, LIMITE_MS);

      function escuchar(cambios, area) {
        if (terminado || area !== 'session' || !cambios[CLAVE_TRABAJO]) return;
        const valor = cambios[CLAVE_TRABAJO].newValue;
        if (!valor || valor.estado === 'pendiente') return;

        terminado = true;
        clearInterval(pulso);
        clearTimeout(limite);
        chrome.storage.onChanged.removeListener(escuchar);
        console.log('[Gomedisys ext] resultado final:', valor);
        puertoApp.postMessage(
          valor.estado === 'completado' ? { ok: true } : { ok: false, error: valor.error || 'No se pudo diligenciar el formulario.' },
        );
      }
      chrome.storage.onChanged.addListener(escuchar);

      // Se escribe DESPUÉS de dejar el listener armado, para no perder el
      // cambio si content-gomedisys.js (ya inyectado) reacciona muy rápido.
      await chrome.storage.session.set({
        [CLAVE_TRABAJO]: { solicitud: mensaje.solicitud, estado: 'pendiente', creado: Date.now() },
      });
      console.log('[Gomedisys ext] trabajo escrito en storage, esperando resultado...');
    } catch (err) {
      console.error('[Gomedisys ext] error en background:', err);
      puertoApp.postMessage({ ok: false, error: err.message });
    }
  });
});
