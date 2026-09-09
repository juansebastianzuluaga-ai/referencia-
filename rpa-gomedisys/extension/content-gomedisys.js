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
  const enAreaReferencia = /EncounterArea|CounterReferral/i.test(location.href);
  const yaEnFormulario = enAreaReferencia && !!document.querySelector('#documentNumberPatient');
  const yaEnListado = enAreaReferencia && !yaEnFormulario && !!document.querySelector('#btnNewRecord');

  if (!yaEnFormulario && !yaEnListado) {
    onPaso('Revisando si pide seleccionar sede...');
    const botonConfirmarSede = await esperarTexto('button', /confirmar/i, { timeout: 5000 }).catch(() => null);
    if (botonConfirmarSede) {
      onPaso('Seleccionando sede "Urgencias"...');
      const urgencias = await esperarTexto('*', /urgencias/i, { timeout: 5000 });
      clic(urgencias);
      clic(botonConfirmarSede);
    }

    onPaso('Abriendo el menú...');
    clic(await esperarSelector('#menuID'));

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

  onPaso('Seleccionando "Servicio que refiere"...');
  await seleccionarKendoDropdown('idServiceReferral', solicitud.servicioRemision);

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
