// Servidor de streaming — la pieza que faltaba de la arquitectura: un
// navegador corriendo en el servidor, transmitiendo su pantalla en vivo por
// WebSocket, y recibiendo clics/tecleo de vuelta para que una persona lo
// controle desde el navegador (en esta prueba: desde public/viewer.html;
// en la versión real, desde un componente dentro de la aplicación Vue).
//
// Reusa exactamente la misma lógica de diligenciar.js que ya se validó en
// el modo terminal (autocompletar.js) — nada de esa parte se reescribió.
//
// Protocolo (JSON sobre WebSocket):
//   Servidor → cliente:
//     { type: 'ready', width, height }      — sesión lista, tamaño de pantalla
//     { type: 'frame', data }                — un cuadro de video (JPEG en base64)
//     { type: 'status', mensaje }            — texto de estado (mismo que la consola en modo terminal)
//     { type: 'error', mensaje }             — algo falló
//   Cliente → servidor:
//     { type: 'mousemove'|'mousedown'|'mouseup', x, y, button }
//     { type: 'wheel', x, y, deltaX, deltaY }
//     { type: 'keydown'|'keyup', key, code, text }
//     { type: 'autocompletar', solicitud }   — dispara el diligenciamiento
//                                               con los datos reales de la
//                                               solicitud (si no vienen, se
//                                               usan los datos de prueba —
//                                               así public/viewer.html sigue
//                                               funcionando para pruebas)

require('dotenv').config();
const http = require('http');
const fs = require('fs');
const path = require('path');
const { WebSocketServer } = require('ws');
const { chromium } = require('playwright');
const { diligenciarFormulario } = require('./diligenciar');
const { solicitud: solicitudPrueba } = require('./datosPrueba');

const PUERTO = process.env.PORT || 8787;
const ANCHO = 1280;
const ALTO = 800;

// ── Servidor HTTP mínimo, solo para servir public/viewer.html (el cliente
// de prueba) — en la integración real esto lo serviría la app Vue. ──────
const servidorHttp = http.createServer((req, res) => {
  if (req.url === '/' || req.url === '/viewer.html') {
    const archivo = path.join(__dirname, 'public', 'viewer.html');
    fs.readFile(archivo, (err, contenido) => {
      if (err) { res.writeHead(500); res.end('No se encontró viewer.html'); return; }
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(contenido);
    });
    return;
  }
  res.writeHead(404);
  res.end('No encontrado');
});

const wss = new WebSocketServer({ server: servidorHttp });

wss.on('connection', async (ws) => {
  console.log('→ Nueva sesión en vivo conectada.');
  let browser;
  let cdp;

  function enviar(mensaje) {
    if (ws.readyState === ws.OPEN) ws.send(JSON.stringify(mensaje));
  }

  try {
    const url = process.env.GOMEDISYS_URL;
    if (!url) throw new Error('Falta GOMEDISYS_URL en el archivo .env');

    browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: ANCHO, height: ALTO } });
    const page = await context.newPage();
    cdp = await context.newCDPSession(page);

    await cdp.send('Page.enable');

    // Cada cuadro de video se manda al cliente y se confirma su recibo —
    // así el navegador controla el ritmo (no manda el siguiente cuadro
    // hasta que se confirma el anterior), evitando saturar la conexión.
    cdp.on('Page.screencastFrame', async (frame) => {
      enviar({ type: 'frame', data: frame.data });
      try {
        await cdp.send('Page.screencastFrameAck', { sessionId: frame.sessionId });
      } catch {
        // La sesión ya pudo haberse cerrado — se ignora.
      }
    });

    await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 70, maxWidth: ANCHO, maxHeight: ALTO, everyNthFrame: 1 });

    enviar({ type: 'ready', width: ANCHO, height: ALTO });
    enviar({ type: 'status', mensaje: 'Abriendo Gomedisys...' });
    await page.goto(url);
    enviar({ type: 'status', mensaje: 'Inicia sesión tú mismo (usuario, contraseña y reCAPTCHA), y cuando estés listo dale clic en "Autocompletar".' });

    // ── Entrada del usuario: se inyecta directamente en el navegador
    // remoto vía CDP, como si viniera de un mouse/teclado real. ─────────
    ws.on('message', async (raw) => {
      let msg;
      try { msg = JSON.parse(raw.toString()); } catch { return; }

      try {
        switch (msg.type) {
          case 'mousemove':
            await cdp.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: msg.x, y: msg.y });
            break;
          case 'mousedown':
            await cdp.send('Input.dispatchMouseEvent', { type: 'mousePressed', x: msg.x, y: msg.y, button: msg.button || 'left', clickCount: 1 });
            break;
          case 'mouseup':
            await cdp.send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: msg.x, y: msg.y, button: msg.button || 'left', clickCount: 1 });
            break;
          case 'wheel':
            await cdp.send('Input.dispatchMouseEvent', { type: 'mouseWheel', x: msg.x, y: msg.y, deltaX: msg.deltaX || 0, deltaY: msg.deltaY || 0 });
            break;
          case 'keydown':
            await cdp.send('Input.dispatchKeyEvent', { type: 'keyDown', key: msg.key, code: msg.code, text: msg.text });
            if (msg.text) {
              await cdp.send('Input.dispatchKeyEvent', { type: 'char', key: msg.key, text: msg.text });
            }
            break;
          case 'keyup':
            await cdp.send('Input.dispatchKeyEvent', { type: 'keyUp', key: msg.key, code: msg.code });
            break;
          case 'autocompletar':
            enviar({ type: 'status', mensaje: 'Diligenciando formulario...' });
            try {
              const datos = msg.solicitud || solicitudPrueba;
              await diligenciarFormulario(page, datos, (mensaje) => enviar({ type: 'status', mensaje }));
              enviar({ type: 'status', mensaje: '✓ Formulario diligenciado. Revisa, completa lo que falte y decide tú si guardar.' });
            } catch (err) {
              enviar({ type: 'error', mensaje: `No se pudo diligenciar: ${err.message}` });
            }
            break;
        }
      } catch (err) {
        enviar({ type: 'error', mensaje: `Error al procesar la acción: ${err.message}` });
      }
    });
  } catch (err) {
    console.error('✗ Error al iniciar la sesión:', err.message);
    enviar({ type: 'error', mensaje: err.message });
    if (browser) await browser.close();
    ws.close();
    return;
  }

  ws.on('close', async () => {
    console.log('→ Sesión cerrada, liberando el navegador...');
    try { await browser.close(); } catch {}
  });
});

servidorHttp.listen(PUERTO, () => {
  console.log(`✓ Servidor de streaming en http://localhost:${PUERTO}`);
  console.log('  Abre esa URL en tu navegador para ver la sesión en vivo.');
});
