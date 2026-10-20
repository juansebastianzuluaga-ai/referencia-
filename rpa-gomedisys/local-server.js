// Servidor local "Enviar a Gomedisys" — reemplazo de la extensión.
//
// Corre en el PC del operador (localhost:8787). Flujo:
//   1. La app hace POST /autocompletar con los datos de la solicitud —
//      quedan "pendientes" aquí.
//   2. En la pestaña de Gomedisys del MISMO navegador (la sesión ya
//      logueada de siempre), la persona le da clic al marcador
//      "Autocompletar REF" — ese marcador inyecta /fill.js, que trae los
//      datos pendientes y diligencia el formulario. Nunca guarda.
//
// El marcador se crea UNA sola vez abriendo http://localhost:8787 y
// arrastrando el botón a la barra de marcadores.
//
// Uso:  npm install  →  node local-server.js   (o  npm run gomedisys)

require('dotenv').config();
const http = require('http');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');
const { diligenciarFormulario } = require('./diligenciar');

const PUERTO = Number(process.env.PORT) || 8787;
// Perfil persistente: la sesión de Gomedisys se guarda aquí — el login
// (con reCAPTCHA) se hace UNA sola vez en esa ventana, y cada clic
// posterior diligencia el formulario solo.
const PERFIL = path.join(__dirname, '.gomedisys-perfil');

let pendiente = null;
let contexto = null;
let enCurso = false;

async function obtenerContexto() {
  if (contexto) return contexto;
  console.log('→ Abriendo Chromium gestionado (perfil persistente)...');
  contexto = await chromium.launchPersistentContext(PERFIL, {
    headless: false,
    slowMo: 120,
    viewport: { width: 1280, height: 800 },
  });
  contexto.on('close', () => { contexto = null; });
  return contexto;
}

async function diligenciarAutomatico(solicitud, onPaso) {
  const url = process.env.GOMEDISYS_URL;
  if (!url) throw new Error('Falta GOMEDISYS_URL en rpa-gomedisys/.env');

  const ctx = await obtenerContexto();
  const page = ctx.pages()[0] || (await ctx.newPage());
  await page.bringToFront().catch(() => {});

  if (!page.url().includes('gomedisys.com')) {
    onPaso('Abriendo Gomedisys...');
    await page.goto(url, { waitUntil: 'domcontentloaded' });
  }

  // El menú lateral solo existe con sesión iniciada.
  const logueado = await page
    .waitForSelector('#menuID', { timeout: 8000 })
    .then(() => true)
    .catch(() => false);

  if (!logueado) {
    const err = new Error('login-requerido');
    err.codigo = 'login';
    throw err;
  }

  await diligenciarFormulario(page, solicitud, onPaso);
}

// ── Orígenes permitidos para fetch (CORS + Private Network Access) ─────
// La página de Gomedisys es https — los fetch a http://localhost desde ahí
// pasan por el chequeo de "Private Network Access" de Chrome: hay que
// devolver ACAO del origen exacto y, en el preflight OPTIONS, la cabecera
// Access-Control-Allow-Private-Network.
const ORIGENES_APP = (process.env.CORS_ORIGIN || 'http://referencia.test,http://localhost:8020,http://localhost:5173').split(',');

function origenPermitido(origen) {
  if (!origen) return false;
  if (ORIGENES_APP.includes(origen)) return true;
  try {
    return new URL(origen).hostname.endsWith('gomedisys.com');
  } catch {
    return false;
  }
}

// ── /fill.js — el motor de diligenciado servido como script inyectable ──
// Reutiliza extension/content-gomedisys.js (misma secuencia validada) con
// dos cirugías: la versión deja de venir de chrome.runtime, y el tramo
// final de coordinación por chrome.storage se reemplaza por un driver que
// trae la solicitud pendiente del servidor y muestra avisos en pantalla.
let fillJsCache = null;

const DRIVER = `
function mostrarAviso(texto, cargando) {
  var v = document.getElementById('rpa-aviso');
  if (!v) {
    v = document.createElement('div');
    v.id = 'rpa-aviso';
    v.style.cssText = 'position:fixed;bottom:18px;right:18px;z-index:999999;padding:10px 14px;border-radius:10px;color:#fff;font:600 12px/1.45 system-ui,sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.35);max-width:330px;';
    document.body.appendChild(v);
  }
  v.textContent = texto;
  v.style.background = cargando ? '#4f46e5'
    : texto.charAt(0) === '\\u2713' ? '#16a34a'
    : texto.charAt(0) === '\\u2717' ? '#dc2626'
    : '#1e293b';
}

async function iniciar() {
  try {
    var res = await fetch('http://localhost:${PUERTO}/pendiente');
    var datos = await res.json();
    if (!datos.ok || !datos.solicitud) {
      mostrarAviso('No hay solicitud pendiente — primero dale "Enviar a Gomedisys" en la app.');
      return;
    }
    mostrarAviso('Diligenciando formulario\\u2026', true);
    try {
      await diligenciarFormulario(datos.solicitud, function (m) { console.log('[RPA Gomedisys]', m); });
      mostrarAviso('\\u2713 Formulario diligenciado — rev\\u00edsalo y decide t\\u00fa si guardar.');
      fetch('http://localhost:${PUERTO}/consumir', { method: 'POST' }).catch(function () {});
    } catch (err) {
      mostrarAviso('\\u2717 ' + err.message);
    }
  } catch (e) {
    mostrarAviso('\\u2717 No se pudo hablar con el servidor local (localhost:${PUERTO}).');
  }
}
iniciar();
`;

function construirFillJs() {
  const src = fs.readFileSync(path.join(__dirname, 'extension', 'content-gomedisys.js'), 'utf8');
  return src
    .replace("chrome.runtime.getManifest().version", JSON.stringify('bm-' + Date.now()))
    .replace(/const CLAVE_TRABAJO[\s\S]*$/, DRIVER + '\n}\n');
}

// ── Página del marcador (abrir http://localhost:8787 una sola vez) ──────
const MARCADOR = `javascript:(function(){var s=document.createElement('script');s.src='http://localhost:${PUERTO}/fill.js?t='+Date.now();document.body.appendChild(s);})();`;

const PAGINA_MARCADOR = `<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Marcador Autocompletar REF</title>
<style>
  body{font-family:system-ui,sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .card{background:#fff;border-radius:16px;padding:32px;max-width:440px;box-shadow:0 10px 40px rgba(0,0,0,.08);text-align:center}
  h1{font-size:18px;color:#0f172a;margin:0 0 8px}
  p{font-size:13px;color:#64748b;line-height:1.6}
  a.bm{display:inline-block;margin:14px 0;padding:12px 24px;border-radius:10px;background:linear-gradient(135deg,#a78bfa,#7c3aed);color:#fff;font-weight:700;font-size:14px;cursor:grab;text-decoration:none;box-shadow:0 4px 14px rgba(124,58,237,.35)}
  code{display:block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:10.5px;word-break:break-all;color:#475569;text-align:left}
</style></head><body><div class="card">
<h1>Marcador "Autocompletar REF"</h1>
<p>Arrastra este bot&oacute;n a tu <strong>barra de marcadores</strong> (una sola vez):</p>
<a class="bm" href="${MARCADOR}">Autocompletar REF</a>
<p>Despu&eacute;s, cuando una solicitud est&eacute; pendiente, abre el formulario "Registro de Referencia" en Gomedisys y dale clic al marcador.</p>
<p>Si la barra de marcadores no se ve: <strong>Ctrl+Shift+B</strong>.</p>
<code>${MARCADOR}</code>
</div></body></html>`;

// ── Utilidades HTTP ────────────────────────────────────────────────────
function responder(res, datos, estado = 200) {
  res.writeHead(estado, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(datos));
}

function leerBody(req) {
  return new Promise((resolve, reject) => {
    let cuerpo = '';
    req.on('data', (chunk) => {
      cuerpo += chunk;
      if (cuerpo.length > 100_000) req.destroy();
    });
    req.on('end', () => {
      try { resolve(JSON.parse(cuerpo || '{}')); } catch { reject(new Error('JSON inválido')); }
    });
    req.on('error', reject);
  });
}

const servidor = http.createServer(async (req, res) => {
  const origen = req.headers.origin;
  if (origenPermitido(origen)) {
    res.setHeader('Access-Control-Allow-Origin', origen);
    res.setHeader('Vary', 'Origin');
  }
  res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.headers['access-control-request-private-network']) {
    res.setHeader('Access-Control-Allow-Private-Network', 'true');
  }
  if (req.method === 'OPTIONS') { res.writeHead(204); res.end(); return; }

  const ruta = req.url.split('?')[0];

  // Página del marcador
  if (req.method === 'GET' && ruta === '/') {
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(PAGINA_MARCADOR);
    return;
  }

  // Script que inyecta el marcador en la pestaña de Gomedisys
  if (req.method === 'GET' && ruta === '/fill.js') {
    fillJsCache = fillJsCache || construirFillJs();
    res.writeHead(200, { 'Content-Type': 'application/javascript; charset=utf-8', 'Cache-Control': 'no-store' });
    res.end(fillJsCache);
    return;
  }

  if (req.method === 'GET' && ruta === '/status') {
    responder(res, { ok: true });
    return;
  }

  // La app manda la solicitud: se intenta el diligenciamiento automático
  // en la ventana gestionada, y de paso queda pendiente para el marcador
  // (por si la ventana gestionada pide login o algo falla).
  if (req.method === 'POST' && ruta === '/autocompletar') {
    if (enCurso) {
      responder(res, { ok: false, error: 'Ya hay un diligenciamiento en curso — espera a que termine.' }, 409);
      return;
    }
    enCurso = true;
    try {
      const solicitud = await leerBody(req);
      if (!solicitud || !solicitud.documento) {
        responder(res, { ok: false, error: 'La solicitud llegó vacía o sin documento.' }, 400);
        return;
      }
      pendiente = solicitud;
      console.log(`→ Solicitud recibida: ${solicitud.nombres} ${solicitud.apellidos} (${solicitud.documento})`);
      try {
        await diligenciarAutomatico(solicitud, (m) => console.log(`→ ${m}`));
        pendiente = null;
        responder(res, { ok: true, modo: 'automatico' });
      } catch (err) {
        if (err.codigo === 'login') {
          // La ventana quedó abierta para que la persona inicie sesión —
          // la pendiente se conserva para reintentar o usar el marcador.
          responder(res, { ok: false, error: 'login' });
        } else {
          console.error('✗', err.message);
          responder(res, { ok: false, error: err.message });
        }
      }
    } finally {
      enCurso = false;
    }
    return;
  }

  // El marcador trae la solicitud pendiente
  if (req.method === 'GET' && ruta === '/pendiente') {
    responder(res, pendiente ? { ok: true, solicitud: pendiente } : { ok: false });
    return;
  }

  // El marcador avisa que ya diligenció — limpia la pendiente
  if (req.method === 'POST' && ruta === '/consumir') {
    pendiente = null;
    console.log('✓ Formulario diligenciado desde el marcador.');
    responder(res, { ok: true });
    return;
  }

  responder(res, { ok: false, error: 'Ruta no encontrada' }, 404);
});

servidor.listen(PUERTO, '127.0.0.1', () => {
  console.log(`✓ Servidor "Enviar a Gomedisys" en http://localhost:${PUERTO}`);
  console.log('  Una sola vez: abre http://localhost:' + PUERTO + ' y arrastra');
  console.log('  el botón "Autocompletar REF" a tu barra de marcadores.');
});
