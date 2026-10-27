// Content script inyectado en las pestañas de Gomedisys (ver manifest.json).
// Diligencia el formulario "Registro de Referencia" usando la pestaña que
// el personal YA tiene abierta y con sesión iniciada — nunca toca login ni
// reCAPTCHA, nunca abre una pestaña nueva por su cuenta.
//
// Es la misma secuencia validada en ../diligenciar.js + ../kendoHelpers.js
// (con Playwright), reescrita en DOM puro porque un content script no tiene
// Playwright disponible: solo puede manipular la página real con las APIs
// normales del navegador.
//
// NUNCA hace clic en "Agregar registro" (Guardar) — deja el formulario
// diligenciado, listo para que una persona lo revise y decida.
//
// El trabajo se coordina por chrome.storage.session, NO por una conexión
// directa (puerto/mensaje) — el clic en el menú de Gomedisys dispara una
// navegación real de página (confirmado probando en vivo), y eso mata
// cualquier conexión directa a mitad de camino. storage.session, en
// cambio, sobrevive a la navegación: cuando la página nueva carga, este
// mismo script se vuelve a inyectar solo (por manifest.json) y retoma el
// trabajo pendiente donde estaba.

// Guarda contra doble inyección: background.js inyecta este script a
// propósito antes de cada uso (además de la inyección automática por
// manifest.json) para asegurar una copia siempre viva, sin depender de que
// la primera inyección haya sobrevivido. Sin esta guarda, la segunda
// inyección redeclararía `const`/`function` de nivel superior y volvería a
// registrar los listeners por duplicado.
//
// La guarda se compara contra la VERSIÓN de la extensión (no un simple
// true/false) — si alguien actualiza la extensión (nueva versión del
// manifest) pero la pestaña de Gomedisys se quedó abierta de antes, la
// próxima inyección SÍ debe reemplazar el código viejo por el nuevo, en vez
// de quedarse callada para siempre con la copia desactualizada. Igual sigue
// evitando duplicar listeners cuando es la MISMA versión reinyectándose.
// OJO: `var`, no `const` — esta línea vive FUERA del bloque guardado de
// abajo (tiene que estar ahí para poder decidir qué rama tomar), así que se
// re-ejecuta en cada inyección dentro de la MISMA pestaña. Un `const` aquí
// revienta con "Identifier ya declarado" en la segunda inyección (fue
// exactamente el bug que causó esto: el resto del archivo si vive adentro
// del bloque, que sí se re-crea limpio en cada ejecución) — `var` permite
// redeclarar sin error.
var VERSION_ACTUAL = chrome.runtime.getManifest().version;
if (window.__gomedisysAutocompletarCargado === VERSION_ACTUAL) {
  console.log('[Gomedisys ext] content-gomedisys.js ya estaba cargado (v' + VERSION_ACTUAL + '), no se vuelve a inicializar.');
} else {
if (window.__gomedisysAutocompletarCargado) {
  console.log('[Gomedisys ext] versión distinta detectada (' + window.__gomedisysAutocompletarCargado + ' → ' + VERSION_ACTUAL + '), reinicializando.');
}
window.__gomedisysAutocompletarCargado = VERSION_ACTUAL;

const MAPA_GENERO = { M: 'Hombre', F: 'Mujer' };

function dormir(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

// Algunos botones quedan con `disabled` un instante mientras Gomedisys
// termina de procesar el campo anterior — un clic (real o simulado) sobre
// un botón deshabilitado no hace nada. Se espera a que se habilite de
// verdad antes de intentar el clic, en vez de asumir que ya lo está apenas
// aparece en el DOM.
function esperarHabilitado(el, { timeout = 5000, intervalo = 100 } = {}) {
  return new Promise((resolve) => {
    const inicio = Date.now();
    const revisar = setInterval(() => {
      if (!el.disabled || Date.now() - inicio >= timeout) {
        clearInterval(revisar);
        resolve();
      }
    }, intervalo);
  });
}

// El HTML de Gomedisys no siempre es válido — se confirmó que #add-
// diagnostic-btn-coRef (el botón "Incluir" del buscador de diagnóstico) y
// #findCodeDxICD10-coRef (el campo de código) existen DOS VECES en la
// página al mismo tiempo (restos escondidos de un modal anterior que nunca
// se limpian). querySelector por sí solo agarra el PRIMERO en el orden del
// documento, que no necesariamente es el visible.
//
// offsetWidth/offsetHeight/getClientRects es la forma estándar de detectar
// si un elemento se está renderizando o no — se prefiere el primero que
// esté REALMENTE visible. OJO con el caso de "ninguno visible todavía":
// cuando SOLO hay un candidato, se devuelve igual (así sigue funcionando
// para selectores normales, sin duplicados, mientras recién aparecen y
// aún no terminan su layout — como siempre funcionó). Pero cuando hay
// VARIOS candidatos y NINGUNO es visible todavía (justo cuando el modal se
// está abriendo), NO se cae ciegamente al primero — eso fue exactamente el
// bug que dejaba el clic/tecleo cayéndole a la copia escondida. En ese
// caso se devuelve null para que esperarSelector seguir esperando (vía su
// MutationObserver) hasta que el visible de verdad aparezca.
function elementoVisible(el) {
  return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
}

function buscarElementoUnico(selector, raiz) {
  const candidatos = raiz.querySelectorAll(selector);
  if (candidatos.length <= 1) return candidatos[0] || null;
  for (const el of candidatos) {
    if (elementoVisible(el)) return el;
  }
  return null;
}

// Espera a que exista un elemento que cumpla `selector` — Kendo y el resto
// del formulario cargan piezas de forma asíncrona, así que no basta con
// buscar una sola vez.
function esperarSelector(selector, { timeout = 15000, raiz = document } = {}) {
  return new Promise((resolve, reject) => {
    const existente = buscarElementoUnico(selector, raiz);
    if (existente) return resolve(existente);

    const observer = new MutationObserver(() => {
      const el = buscarElementoUnico(selector, raiz);
      if (el) {
        observer.disconnect();
        resolve(el);
      }
    });
    observer.observe(raiz === document ? document.documentElement : raiz, {
      childList: true,
      subtree: true,
      attributes: true,
    });
    setTimeout(() => {
      observer.disconnect();
      reject(new Error(`No apareció "${selector}" a tiempo.`));
    }, timeout);
  });
}

function normalizarTexto(texto) {
  return (texto || '')
    .trim()
    // Normaliza el espacio alrededor de comas antes de colapsar el resto —
    // "Palmira,Valle del Cauca" (sin espacio, como a veces queda guardado
    // el municipio) debe considerarse igual a "Palmira, Valle del Cauca"
    // (como Gomedisys siempre lo muestra). Sin esto, una opción que SÍ
    // está en la lista se reporta como "no encontrada".
    .replace(/\s*,\s*/g, ', ')
    .replace(/\s+/g, ' ')
    .toLowerCase();
}

function coincideTexto(el, textoOMatch) {
  const texto = (el.textContent || '').trim();
  if (textoOMatch instanceof RegExp) return textoOMatch.test(texto);
  // Comparación insensible a mayúsculas/espacios — el texto real de
  // Gomedisys no siempre coincide en formato exacto con nuestro dato.
  return normalizarTexto(texto) === normalizarTexto(textoOMatch);
}

// Entre varios elementos que coinciden con el texto, se prefiere el más
// específico (el que ningún otro candidato contiene) — así no se hace clic
// en un contenedor grande cuando lo que se quería era la etiqueta pequeña
// de adentro.
function buscarPorTexto(selector, textoOMatch, raiz = document) {
  const candidatos = Array.from(raiz.querySelectorAll(selector)).filter((el) => coincideTexto(el, textoOMatch));
  return candidatos.find((el) => !candidatos.some((otro) => otro !== el && el.contains(otro))) || candidatos[0] || null;
}

function esperarTexto(selector, textoOMatch, { timeout = 15000 } = {}) {
  return new Promise((resolve, reject) => {
    const existente = buscarPorTexto(selector, textoOMatch);
    if (existente) return resolve(existente);

    const observer = new MutationObserver(() => {
      const el = buscarPorTexto(selector, textoOMatch);
      if (el) {
        observer.disconnect();
        resolve(el);
      }
    });
    observer.observe(document.documentElement, { childList: true, subtree: true, characterData: true });
    setTimeout(() => {
      observer.disconnect();
      reject(new Error(`No apareció texto "${textoOMatch}" a tiempo.`));
    }, timeout);
  });
}

// Simula un clic real (mousedown/mouseup/click) — los widgets de Kendo
// escuchan estos eventos con jQuery, que sí reacciona a eventos disparados
// por código, no solo a los del usuario.
function clic(el) {
  el.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
  el.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
  el.click();
}

// Pone el valor de un <input>/<textarea> usando el setter nativo de SU
// PROPIO tipo (no el de una posible clase que lo envuelva) y dispara
// 'input'/'change' — así los widgets que escuchan esos eventos (Kendo,
// validación de ASP.NET) se enteran del cambio, igual que si la persona
// hubiera escrito. El setter de "value" es distinto entre <input> y
// <textarea> (son interfaces distintas) — usar siempre el de
// HTMLInputElement revienta con "Illegal invocation" en un <textarea>
// (como #clinicalSummary), así que se toma el del prototipo real del
// elemento en vez de asumir cuál es.
function ponerValor(el, valor) {
  const descriptor = Object.getOwnPropertyDescriptor(Object.getPrototypeOf(el), 'value');
  if (descriptor && descriptor.set) {
    descriptor.set.call(el, valor);
  } else {
    el.value = valor;
  }
  el.dispatchEvent(new Event('input', { bubbles: true }));
  el.dispatchEvent(new Event('change', { bubbles: true }));
}

// Los navegadores modernos IGNORAN keyCode/which al construir un
// KeyboardEvent por código — son propiedades viejas que el constructor
// estándar ya no deja fijar (siempre quedan en 0). El problema es que
// widgets viejos tipo jQuery/Kendo muchas veces solo miran esas dos
// propiedades (no `key`) para decidir si hubo una tecla real y arrancar su
// búsqueda — si es así, todo evento de teclado disparado "normal" pasa
// desapercibido para ellos aunque `input.value` sí haya cambiado. Se
// sobreescribe manualmente con defineProperty, que sí puede reemplazar el
// getter heredado en la instancia concreta del evento.
function crearEventoTeclado(tipo, caracter) {
  const evento = new KeyboardEvent(tipo, { bubbles: true, cancelable: true, key: caracter });
  const codigo = caracter.toUpperCase().charCodeAt(0);
  Object.defineProperty(evento, 'keyCode', { get: () => codigo });
  Object.defineProperty(evento, 'which', { get: () => codigo });
  return evento;
}

// El buscador de diagnóstico (por código) a veces no arranca su búsqueda si
// solo se le pone el valor de una — arma su búsqueda leyendo el valor LETRA
// POR LETRA a medida que llegan los eventos de teclado. Por eso esto no
// pone el texto de un tirón: lo teclea de verdad, carácter por carácter,
// con keydown/input/keyup en cada uno (como Playwright/Puppeteer hacen con
// `.type()`).
//
// No todos los campos son un <input>/<textarea> real con .value nativo —
// el desplegable de "Diagnóstico" (búsqueda por descripción) resultó ser un
// <span role="combobox" tabindex="0">, sin .value propio: Kendo arma el
// texto buscado leyendo directo los eventos de teclado. Si el elemento no
// tiene un setter de .value nativo, se le manda SOLO el teclado (sin
// intentar tocar .value ni disparar input/change).
async function escribirComoHumano(el, texto) {
  const descriptor = Object.getOwnPropertyDescriptor(Object.getPrototypeOf(el), 'value');
  const ponerValorCrudo = descriptor && descriptor.set ? (v) => descriptor.set.call(el, v) : null;

  el.focus({ preventScroll: true });
  if (ponerValorCrudo) {
    ponerValorCrudo('');
    el.dispatchEvent(new Event('input', { bubbles: true }));
  }

  let acumulado = '';
  for (const caracter of texto) {
    acumulado += caracter;
    el.dispatchEvent(crearEventoTeclado('keydown', caracter));
    if (ponerValorCrudo) {
      ponerValorCrudo(acumulado);
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }
    el.dispatchEvent(crearEventoTeclado('keyup', caracter));
    await dormir(45);
  }
  if (ponerValorCrudo) {
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }
}

function opcionesDisponibles(listbox) {
  return Array.from(listbox.querySelectorAll('li, [role="option"]'))
    .map((el) => el.textContent.trim())
    .filter(Boolean);
}

async function seleccionarKendoDropdown(idCampo, textoOpcion) {
  const combobox = await esperarSelector(`span[role="combobox"][aria-controls="${idCampo}_listbox"]`);
  clic(combobox);
  const listbox = await esperarSelector(`#${idCampo}_listbox`);
  const opcion = buscarPorTexto('li, [role="option"]', textoOpcion, listbox);
  if (!opcion) {
    // Se listan las opciones reales en el error — así, si el texto no
    // coincide, se sabe de una vez qué SÍ hay disponible, sin adivinar.
    throw new Error(`No encontré la opción "${textoOpcion}" en ${idCampo}. Opciones disponibles: ${opcionesDisponibles(listbox).join(' | ')}`);
  }
  clic(opcion);
}

// Se busca por CÓDIGO en la pestaña "Código" del modal — ahora que el
// catálogo de diagnósticos viene directo de Gomedisys (tabla
// diagnosticos_cie10), el código que llega acá ya es el específico y
// completo (ej. "B342", no "B34"), así que la búsqueda por código sí
// encuentra el diagnóstico exacto de una — más simple y confiable que
// buscar por descripción, que resultó poco confiable con este widget en
// particular (un Kendo DropDownList, no un <input> normal).
//
// El buscador a veces no reacciona a la primera — se reintenta hasta 2
// veces más antes de rendirse.
async function buscarYSeleccionarDiagnostico(codigo) {
  const INTENTOS = 3;
  let ultimoError;
  for (let intento = 1; intento <= INTENTOS; intento++) {
    try {
      await intentarBuscarYSeleccionarDiagnostico(codigo);
      return;
    } catch (err) {
      ultimoError = err;
      await dormir(500);
    }
  }
  throw ultimoError;
}

// Cuando el código es exacto, Gomedisys NO muestra una lista para elegir —
// rellena directo el campo "Diagnóstico" de al lado con el nombre
// encontrado (confirmado viendo la pantalla real: escribir "J852" hace que
// ese campo pase de mostrar el placeholder "Buscar diagnóstico CIE-10..."
// al nombre real del diagnóstico). Por eso la señal de éxito no es una
// lista — es que ese texto cambie de placeholder a lo que sea.
const PLACEHOLDER_DX = 'Buscar diagnóstico CIE-10...';

// Igual que #add-diagnostic-btn-coRef, este span puede existir varias
// veces en la página (restos de un modal anterior) — se reutiliza
// buscarElementoUnico (ver arriba) para preferir el visible cuando hay
// varios, sin exigirle visibilidad cuando solo hay uno (este span en
// particular puede medir 0 de ancho mientras todavía no tiene texto, así
// que exigir visibilidad SIEMPRE lo dejaba descartado por error incluso
// siendo el único real).
function textoResueltoDx() {
  const span = buscarElementoUnico('span[role="combobox"][aria-controls="ddDiagnosticsICD10-coRef_listbox"] .k-input-value-text', document);
  const texto = span ? span.textContent.trim() : '';
  return texto && texto !== PLACEHOLDER_DX ? texto : null;
}

function esperarTextoResueltoDx({ timeout = 10000, intervalo = 200 } = {}) {
  return new Promise((resolve) => {
    const inicio = Date.now();
    const revisar = setInterval(() => {
      const texto = textoResueltoDx();
      if (texto) {
        clearInterval(revisar);
        resolve(texto);
        return;
      }
      if (Date.now() - inicio >= timeout) {
        clearInterval(revisar);
        resolve(null);
      }
    }, intervalo);
  });
}

async function intentarBuscarYSeleccionarDiagnostico(codigo) {
  const campoDx = await esperarSelector('#findCodeDxICD10-coRef');
  await escribirComoHumano(campoDx, codigo);

  const encontrado = await esperarTextoResueltoDx();
  if (!encontrado) {
    throw new Error(`No se encontraron resultados buscando el código "${codigo}" en el buscador de diagnóstico de Gomedisys. Verifica el código o complétalo a mano en esa pestaña.`);
  }
  // Pequeño margen para que Gomedisys termine de asentar el texto resuelto
  // antes del clic.
  await dormir(400);
  clic(await esperarSelector('#add-diagnostic-btn-coRef'));
}

// La barra del usuario muestra "...(Sede: X)". Si la sesión quedó fijada
// en otra sede (ej. entró por CAC Santa Bárbara y esta solicitud es de
// Urgencias), el registro caería en la sede equivocada — se compara y, si
// no coincide, se usa el botón "Cambiar sede" del panel del usuario (es el
// mismo selector de "Asignar sede" de la entrada, pero desde dentro de la
// sesión). El cambio recarga la página: el trabajo se retoma solo cuando
// este script se reinyecta en la página nueva.
//
// sessionStorage (sobrevive a la navegación dentro de la misma pestaña)
// lleva la cuenta de intentos — si el cambio no "pega" después de 2
// navegaciones, se frena con un error claro en vez de quedar reintentando
// en bucle.
const CLAVE_INTENTOS_SEDE = 'gomedisysIntentosCambioSede';

// Devuelve true cuando disparó un cambio de sede (la página va a navegar —
// quien la llama debe parar ahí y dejar que el trabajo se retome solo).
// `dentroDelArea` indica que ya se está en el formulario/listado de
// referencia: ahí solo se actúa cuando la sede se puede leer y no coincide
// — un cambio a ciegas navegaría y arriesgaría lo ya diligenciado.
async function cambiarSedeSiHaceFalta(sedeObjetivo, regexSede, onPaso, dentroDelArea = false) {
  const marcador = buscarPorTexto('*', /sede\s*:/i);
  const sedeActual = marcador
    ? ((marcador.textContent || '').match(/sede\s*:\s*([^)\n]+)/i)?.[1] || '').trim()
    : '';

  // Sede legible y correcta — nada que hacer.
  if (sedeActual && regexSede.test(sedeActual)) {
    sessionStorage.removeItem(CLAVE_INTENTOS_SEDE);
    return false;
  }

  // Sin lectura de sede dentro del área se continúa: forzar un cambio sin
  // saber si hace falta podría recargar y perder lo ya diligenciado.
  if (!sedeActual && dentroDelArea) return false;

  // El botón "Cambiar sede" vive en el panel del usuario (el ícono ≡). Lo
  // normal es llamar esta función con el menú ya abierto, así que el botón
  // ya debería estar en el DOM — el clic sintético dispara su handler
  // igual aunque el panel no esté a la vista.
  const botonCambiar = buscarPorTexto('button, a, span', /cambiar\s+sede/i);
  if (!botonCambiar) {
    throw new Error(sedeActual
      ? `La sesión está en la sede "${sedeActual}" y esta solicitud es de "${sedeObjetivo}". No encontré el botón "Cambiar sede" — cámbiala a mano y reintenta.`
      : `No puedo verificar la sede activa: la página no muestra "(Sede: …)" ni el botón "Cambiar sede". Confirma a mano que la sesión de Gomedisys esté en "${sedeObjetivo}" (panel del usuario, ícono ≡) y reintenta.`);
  }

  const intentos = parseInt(sessionStorage.getItem(CLAVE_INTENTOS_SEDE) || '0', 10);
  if (intentos >= 2) {
    sessionStorage.removeItem(CLAVE_INTENTOS_SEDE);
    throw new Error(`Intenté fijar la sede "${sedeObjetivo}" y la sesión sigue ${sedeActual ? `mostrando "${sedeActual}"` : 'sin mostrar sede'} — cámbiala a mano con "Cambiar sede" del panel del usuario y reintenta.`);
  }
  sessionStorage.setItem(CLAVE_INTENTOS_SEDE, String(intentos + 1));

  onPaso(sedeActual
    ? `La sesión está en sede "${sedeActual}" y la solicitud es de "${sedeObjetivo}" — cambiando sede...`
    : `Fijando la sede "${sedeObjetivo}" desde "Cambiar sede"...`);

  clic(botonCambiar);

  // En el modal "Cambio de Sede" se elige la opción. Se prefiere el
  // <select> real (aunque Kendo lo deje oculto — el 'change' sobre él es
  // lo que entera al widget y dispara la acción del servidor). Si no hay
  // select con la opción, se va por el combo Kendo visible.
  const tituloModal = await esperarTexto('*', /cambio de sede/i, { timeout: 8000 })
    .catch(() => { throw new Error(`Se abrió "Cambiar sede" pero no apareció el selector. Cámbiala a mano a "${sedeObjetivo}" y reintenta.`); });
  const modal = tituloModal.closest('.k-window, .k-dialog, .modal, [role="dialog"], .modal-content, form') || document;

  const selectConOpcion = Array.from(modal.querySelectorAll('select'))
    .find((s) => Array.from(s.options).some((o) => regexSede.test(o.textContent || '')));

  if (selectConOpcion) {
    const opcion = Array.from(selectConOpcion.options).find((o) => regexSede.test(o.textContent || ''));
    selectConOpcion.value = opcion.value;
    selectConOpcion.dispatchEvent(new Event('input', { bubbles: true }));
    selectConOpcion.dispatchEvent(new Event('change', { bubbles: true }));
  } else {
    const combo = buscarElementoUnico('span[role="combobox"], .k-dropdown, .k-dropdownlist, .k-select', modal)
      || buscarElementoUnico('span[role="combobox"], .k-dropdown, .k-dropdownlist, .k-select', document);
    if (combo) {
      clic(combo);
      await dormir(600);
    }
    const opcion = await esperarTexto('li, [role="option"], option', regexSede, { timeout: 8000 })
      .catch(() => { throw new Error(`No encontré la sede "${sedeObjetivo}" entre las opciones del cambio de sede.`); });
    clic(opcion);
  }

  // Confirmar si el modal trae botón (algunos aplican el cambio apenas se
  // elige la opción). Se busca primero DENTRO del modal para no darle a un
  // "Confirmar" de otra ventana que haya quedado en el DOM.
  const regexConfirmar = /confirmar|aceptar|aplicar|guardar|continuar|\bok\b/i;
  const confirmar = buscarPorTexto('button, a, input[type="submit"], input[type="button"]', regexConfirmar, modal)
    || buscarPorTexto('button, a, input[type="submit"], input[type="button"]', regexConfirmar, document);
  if (confirmar) clic(confirmar);

  // El cambio de sede RECARGA la página — y esta función NO debe resolver
  // como si el trabajo hubiera terminado (eso marcaba la solicitud como
  // diligenciada sin haber llenado nada). Se espera la navegación: si no
  // arranca, se intenta cerrar el modal con la X (algunas ventanas aplican
  // el cambio al cerrar) y, si tampoco navega, se reporta como error.
  const esperarNavegacion = (ms) => new Promise((resolve) => {
    const irse = () => resolve(true);
    window.addEventListener('pagehide', irse, { once: true });
    window.addEventListener('beforeunload', irse, { once: true });
    setTimeout(() => resolve(false), ms);
  });

  if (!await esperarNavegacion(8000)) {
    const cerrar = modal.querySelector('.k-window-close, .k-i-close, [aria-label="Close"], .close');
    if (cerrar) {
      clic(cerrar);
      if (await esperarNavegacion(6000)) {
        // el contexto muere con la navegación — si no, el tope de abajo corta
        await new Promise((_, reject) => setTimeout(() => reject(new Error('El cambio de sede no terminó de recargar — reintenta.')), 30000));
      }
    }
    throw new Error(`Elegí "${sedeObjetivo}" en "Cambio de Sede" pero el cambio no se aplicó (la página no recargó). Revisa ese modal y termina el cambio a mano, luego reintenta.`);
  }

  // La página está navegando — se deja la función pendiente para que este
  // contexto muera sin marcar el trabajo como completado; la copia nueva
  // del script lo retoma en la página ya cambiada de sede. Si la
  // navegación se atascó, a los 30s se reporta como error.
  await new Promise((_, reject) => {
    setTimeout(() => reject(new Error('La página empezó a recargar tras el cambio de sede pero no terminó — reintenta.')), 30000);
  });
}

async function diligenciarFormulario(solicitud, onPaso = () => {}) {
  // El foco (aunque ya se pide sin scroll, ver escribirComoHumano) y los
  // clics durante el llenado pueden mover el scroll de la página igual —
  // se fija en el tope mientras el bot trabaja, y se suelta al terminar
  // (haya salido bien o con error), para que la persona nunca vea la
  // pantalla saltar sola durante el proceso.
  const fijarScroll = () => window.scrollTo({ top: 0, left: 0, behavior: 'instant' });
  window.addEventListener('scroll', fijarScroll);
  try {
  // Ir por el menú y buscar "Registro de Referencia" es el paso por
  // defecto, sin importar en qué pantalla de Gomedisys se esté parado.
  // Se salta SOLO en dos casos, y los dos se verifican con la URL (no solo
  // con que exista un elemento) — un id como "#btnNewRecord" NO es
  // exclusivo de Registro de Referencia, otras pantallas (ej. Contratos)
  // tienen su propio botón con el mismo id, y confiar solo en el elemento
  // hacía que el bot llenara el formulario equivocado:
  //   1. Ya está DENTRO del formulario (ej. retomando después de una
  //      recarga a mitad de camino) — no repetir nada, se perdería lo ya
  //      llenado.
  //   2. Ya está en el LISTADO de Registro de Referencia (ej. la
  //      navegación del menú ya aterrizó ahí en un intento anterior) —
  //      no hace falta volver a pasar por el menú, solo falta "Nuevo".
  //      Sin este caso, el bot vuelve a buscar "referencia" en bucle cada
  //      vez que aterriza ahí sin haber llegado todavía al formulario.
  // La sede viene de la solicitud (la elige quien acepta, en el modal de
  // aceptación). Si la solicitud no trae sede — registros viejos — se
  // asume "URGENCIAS", que era el valor fijo anterior.
  const sedeObjetivo = (solicitud.sede || 'URGENCIAS').trim();
  // Coincidencia por texto contenido, insensible a mayúsculas — la barra
  // muestra "CAC SANTA BARBARA" y el selector puede mostrar otro formato.
  const regexSedeObjetivo = new RegExp(sedeObjetivo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');

  const enAreaReferencia = /EncounterArea|CounterReferral/i.test(location.href);
  const yaEnFormulario = enAreaReferencia && !!document.querySelector('#documentNumberPatient');
  const yaEnListado = enAreaReferencia && !yaEnFormulario && !!document.querySelector('#btnNewRecord');

  // Ya dentro del área (retomando un trabajo) solo se actúa si la sede se
  // puede leer y no coincide — la verificación a ciegas no se fuerza.
  if ((yaEnFormulario || yaEnListado)
    && await cambiarSedeSiHaceFalta(sedeObjetivo, regexSedeObjetivo, onPaso, true)) {
    return;
  }

  if (!yaEnFormulario && !yaEnListado) {
    onPaso('Revisando si pide seleccionar sede...');
    const botonConfirmarSede = await esperarTexto('button', /confirmar/i, { timeout: 5000 }).catch(() => null);
    if (botonConfirmarSede) {
      onPaso(`Seleccionando sede "${sedeObjetivo}"...`);
      const opcionSede = await esperarTexto('*', regexSedeObjetivo, { timeout: 5000 })
        .catch(() => { throw new Error(`No encontré la sede "${sedeObjetivo}" entre las opciones que muestra Gomedisys.`); });
      clic(opcionSede);
      clic(botonConfirmarSede);
    }

    // El menú se abre ANTES de verificar la sede: el indicador "(Sede: X)"
    // y el botón "Cambiar sede" viven dentro de ese panel y solo existen
    // en el DOM cuando está desplegado — sin abrirlo no hay cómo saber en
    // qué sede quedó la sesión.
    onPaso('Abriendo el menú...');
    clic(await esperarSelector('#menuID'));
    await dormir(600);

    // Si la sesión ya tenía otra sede fijada se cambia con "Cambiar sede"
    // antes de seguir — el cambio recarga la página y este trabajo se
    // retoma solo; por eso aquí se corta la ejecución.
    if (await cambiarSedeSiHaceFalta(sedeObjetivo, regexSedeObjetivo, onPaso)) {
      return;
    }

    onPaso('Buscando "Registro de Referencia"...');
    const barraBusqueda = await esperarSelector('#searchInputMenu');
    ponerValor(barraBusqueda, 'referencia');
    const linkReferencia = await esperarTexto('.menu-link__label', 'Registro de Referencia');
    clic(linkReferencia);
  }

  if (!yaEnFormulario) {
    onPaso('Abriendo formulario nuevo...');
    clic(await esperarSelector('#btnNewRecord'));
  }

  onPaso('Diligenciando campos...');
  ponerValor(await esperarSelector('#documentNumberPatient'), solicitud.documento);
  ponerValor(await esperarSelector('#givenNamePatient'), solicitud.nombres);
  ponerValor(await esperarSelector('#familyNamePatient'), solicitud.apellidos);
  ponerValor(await esperarSelector('#age'), solicitud.edad);

  if (solicitud.direccionPaciente) {
    ponerValor(await esperarSelector('#addressPatient'), solicitud.direccionPaciente);
  }
  if (solicitud.telefonoPaciente) {
    ponerValor(await esperarSelector('#telecomPatient'), solicitud.telefonoPaciente);
  }

  // "Ciudad de Origen" se deja SIN llenar a propósito — su buscador en
  // Gomedisys reacciona de forma demasiado inconsistente a la automatización
  // (con o sin teclado real) como para confiar en el resultado. Se deja
  // ese único campo para completarlo a mano; todo lo demás sí se diligencia
  // automático.
  onPaso(`Ciudad de Origen se deja para completar a mano: "${solicitud.ciudadOrigen}"`);

  // "Servicio que refiere" se deja SIN llenar — es un catálogo cerrado de
  // Gomedisys (~190 servicios) y la app no guarda ese dato, así que el
  // operador lo elige a mano antes de guardar el registro.
  onPaso('"Servicio que refiere" se deja para completar a mano.');

  onPaso('Seleccionando "Género"...');
  await seleccionarKendoDropdown('idGenere', MAPA_GENERO[solicitud.genero]);

  ponerValor(await esperarSelector('#clinicalSummary'), solicitud.resumenClinico);

  onPaso('Abriendo el buscador de diagnóstico...');
  const botonBuscarDx = await esperarSelector('#openDiagnosticLookupBtn');
  // Puede quedar deshabilitado un instante mientras Gomedisys termina de
  // procesar el campo anterior (Resumen clínico) — hacerle clic justo en
  // ese hueco no tiene ningún efecto, sin importar cómo se dispare el
  // clic. Se espera a que esté habilitado de verdad antes de intentarlo.
  await esperarHabilitado(botonBuscarDx);
  clic(botonBuscarDx);

  onPaso('Buscando el diagnóstico por código...');
  await buscarYSeleccionarDiagnostico(solicitud.diagnosticoCodigo);

  onPaso('Formulario diligenciado.');
  } finally {
    window.removeEventListener('scroll', fijarScroll);
  }
}

const CLAVE_TRABAJO = 'gomedisysTrabajo';
let procesando = false;

async function revisarTrabajoPendiente(gatillo) {
  if (procesando) return;
  const datos = await chrome.storage.session.get(CLAVE_TRABAJO);
  const trabajo = datos[CLAVE_TRABAJO];
  if (!trabajo || trabajo.estado !== 'pendiente') return;

  procesando = true;
  console.log(`[Gomedisys ext] retomando trabajo pendiente (${gatillo}).`);
  try {
    await diligenciarFormulario(trabajo.solicitud, (paso) => console.log('[Gomedisys ext] paso:', paso));
    console.log('[Gomedisys ext] diligenciado OK.');
    await chrome.storage.session.set({ [CLAVE_TRABAJO]: { ...trabajo, estado: 'completado' } });
  } catch (err) {
    console.error('[Gomedisys ext] error diligenciando:', err);
    await chrome.storage.session.set({ [CLAVE_TRABAJO]: { ...trabajo, estado: 'error', error: err.message } });
  } finally {
    procesando = false;
  }
}

console.log('[Gomedisys ext] content-gomedisys.js cargado en', location.href);
revisarTrabajoPendiente('carga de página');

chrome.storage.onChanged.addListener((cambios, area) => {
  if (area !== 'session' || !cambios[CLAVE_TRABAJO]) return;
  revisarTrabajoPendiente('cambio de storage');
});

} // fin de la guarda contra doble inyección
