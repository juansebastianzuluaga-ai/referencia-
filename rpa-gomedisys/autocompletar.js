// Prueba 1: automatización de "Registro de Referencia" en Gomedisys.
//
// Motor de diligenciamiento probado de punta a punta (login manual → sede
// → navegar → llenar los 9 campos confirmados). La lógica de diligenciado
// vive en diligenciar.js — este archivo es solo el "modo terminal" para
// probarlo sin la aplicación real (ver streaming-server.js para el modo
// en vivo, que reusa exactamente el mismo diligenciar.js).
//
// El script se detiene ANTES de guardar — el diligenciado queda visible en
// pantalla para revisión, y el navegador se queda abierto hasta que
// presiones Enter en la terminal. Nunca hace clic en "Agregar registro"
// (Guardar) por su cuenta.

require('dotenv').config();
const { chromium } = require('playwright');
const readline = require('readline');
const { diligenciarFormulario } = require('./diligenciar');
const { solicitud } = require('./datosPrueba');

function esperarEnter(mensaje) {
  return new Promise((resolve) => {
    const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
    rl.question(mensaje, () => {
      rl.close();
      resolve();
    });
  });
}

async function main() {
  const url = process.env.GOMEDISYS_URL;
  const usuario = process.env.GOMEDISYS_USER;

  if (!url) {
    console.error('✗ Falta GOMEDISYS_URL en el archivo .env (copia .env.example a .env y complétalo).');
    process.exit(1);
  }

  const browser = await chromium.launch({ headless: process.env.HEADLESS === 'true', slowMo: 150 });
  const page = await browser.newPage();

  try {
    console.log('→ Abriendo Gomedisys...');
    await page.goto(url);

    // ── Login — MANUAL a propósito ──────────────────────────────────
    // El formulario de login tiene reCAPTCHA. La persona inicia sesión
    // ella misma por completo (usuario, contraseña y reCAPTCHA) — el
    // script no toca nada de este formulario.
    console.log('\n⚠ El login es completamente manual — el script no lo toca.');
    if (usuario) console.log(`  Usuario sugerido (de tu .env): ${usuario}`);
    console.log('  Inicia sesión tú mismo en la ventana que se abrió (usuario,');
    console.log('  contraseña y marcar "No soy un robot"), hasta llegar a la');
    console.log('  pantalla de "Asignar sede de trabajo" (o al dashboard, si te');
    console.log('  la salta).\n');
    await esperarEnter('Presiona Enter aquí cuando ya hayas iniciado sesión...\n');

    await diligenciarFormulario(page, solicitud, (mensaje) => console.log(`→ ${mensaje}`));

    console.log('\n✓ Formulario diligenciado con los campos automáticos.');
    console.log('  Revisa en pantalla que todo haya quedado bien, completa a mano');
    console.log('  lo que falte (CRUE, Especialidad, etc.), y decide tú si guardar.');
    console.log('  Este script NO le da clic a Guardar — eso lo haces tú.\n');

    if (process.env.AUTO_CLOSE === 'true') {
      await page.screenshot({ path: 'resultado.png', fullPage: true });
      console.log('→ Captura guardada en resultado.png (modo de diagnóstico automático).');
    } else {
      await esperarEnter('Presiona Enter aquí cuando termines de revisar (esto cierra el navegador)...\n');
    }
  } catch (err) {
    console.error('\n✗ Algo falló:', err.message);
    try {
      await page.screenshot({ path: 'error.png', fullPage: true });
      console.error('  Captura del momento del error guardada en error.png');
    } catch {}
    if (process.env.AUTO_CLOSE !== 'true') {
      console.error('  Deja el navegador abierto para que puedas ver en qué paso se quedó.');
      await esperarEnter('Presiona Enter para cerrar...\n');
    }
  } finally {
    await browser.close();
  }
}

main();
