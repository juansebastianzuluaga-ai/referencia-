const { seleccionarKendoDropdown, seleccionarKendoComboBox } = require('./kendoHelpers');
const { MAPA_GENERO } = require('./datosPrueba');

/**
 * Diligencia el formulario "Registro de Referencia" en Gomedisys.
 * Asume que `page` YA está en el dashboard de Gomedisys, con sesión
 * iniciada (login manual, hecho por la persona) — este módulo no toca
 * nada del login.
 *
 * `onPaso(mensaje)` se llama antes de cada paso, para que quien use este
 * módulo (script de terminal, servidor de streaming) pueda mostrarlo donde
 * corresponda, sin que este archivo sepa nada de consola ni de WebSockets.
 *
 * NUNCA hace clic en "Agregar registro" (Guardar) — se detiene con el
 * formulario diligenciado, listo para que una persona lo revise y decida.
 */
// `intentosSede` evita un bucle infinito si el cambio de sede recarga la
// página pero la sesión sigue mostrando otra: a la tercera se frena con un
// error claro en vez de registrar en la sede equivocada.
async function diligenciarFormulario(page, solicitud, onPaso = () => {}, intentosSede = 0) {
  // La sede viene de la solicitud (se elige al aceptarla). Si no llega —
  // datos de prueba o registros viejos — se asume "URGENCIAS", el valor
  // fijo que se usaba antes.
  const sedeObjetivo = (solicitud.sede || 'URGENCIAS').trim();
  const regexSedeObjetivo = new RegExp(sedeObjetivo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');

  // ── Selección de sede (si aparece) ──────────────────────────────────
  // Gomedisys a veces recuerda la sede de una sesión anterior y salta
  // directo al dashboard — en ese caso no hay nada que seleccionar aquí.
  onPaso('Revisando si pide seleccionar sede...');
  const botonConfirmarSede = page.getByRole('button', { name: /confirmar/i });
  const apareceSelectorSede = await botonConfirmarSede
    .waitFor({ state: 'visible', timeout: 5000 })
    .then(() => true)
    .catch(() => false);

  if (apareceSelectorSede) {
    onPaso(`Seleccionando sede "${sedeObjetivo}"...`);
    await page.getByText(regexSedeObjetivo).first().click();
    await botonConfirmarSede.click();
  }

  // ── Navegar a "Registro de Referencia" ──────────────────────────────
  // El menú se abre ANTES de verificar la sede: el indicador "(Sede: X)" y
  // el botón "Cambiar sede" viven dentro del panel del usuario y solo
  // existen en el DOM con el menú desplegado.
  onPaso('Abriendo el menú...');
  await page.locator('#menuID').click({ timeout: 15000 });
  await page.waitForTimeout(600);

  // ── Sede activa vs. sede de la solicitud ────────────────────────────
  // Si la sesión quedó fijada en otra sede, la barra del usuario muestra
  // "(Sede: X)" — se compara con la de la solicitud y, si no coincide, se
  // cambia con "Cambiar sede" antes de llenar nada. El cambio recarga la
  // página, así que después se vuelve a correr este flujo.
  const textoSede = await page.getByText(/sede\s*:/i).first().textContent().catch(() => '');
  const sedeActual = ((textoSede || '').match(/sede\s*:\s*([^)\n]+)/i)?.[1] || '').trim();
  if (sedeActual && !regexSedeObjetivo.test(sedeActual)) {
    if (intentosSede >= 2) {
      throw new Error(`Intenté cambiar a la sede "${sedeObjetivo}" y la sesión sigue en "${sedeActual}" — cámbiala a mano con "Cambiar sede" del panel del usuario y reintenta.`);
    }
    onPaso(`La sesión está en sede "${sedeActual}" y la solicitud es de "${sedeObjetivo}" — cambiando sede...`);
    await page.getByText(/cambiar sede/i).first().click();
    await page.getByText(/cambio de sede/i).first().waitFor({ state: 'visible', timeout: 8000 });

    // Se prefiere el <select> real (aunque Kendo lo deje oculto); si ninguno
    // tiene la opción, se abre el combo y se elige en la lista.
    let elegida = false;
    const selects = page.locator('select');
    const totalSelects = await selects.count();
    for (let i = 0; i < totalSelects; i++) {
      const textos = await selects.nth(i).locator('option').allTextContents().catch(() => []);
      const objetivo = textos.find((t) => regexSedeObjetivo.test(t));
      if (objetivo) {
        await selects.nth(i).selectOption({ label: objetivo });
        elegida = true;
        break;
      }
    }
    if (!elegida) {
      const combo = page.locator('span[role="combobox"], .k-dropdown, .k-dropdownlist, .k-select').first();
      await combo.click({ timeout: 6000 }).catch(() => {});
      await page.getByRole('option', { name: regexSedeObjetivo }).first().click({ timeout: 8000 })
        .catch(async () => page.getByText(regexSedeObjetivo).first().click());
    }
    await page.getByRole('button', { name: /confirmar|aceptar|aplicar|guardar|continuar|ok/i }).first().click({ timeout: 6000 }).catch(() => {});

    // El cambio de sede recarga la página — se espera a que aterrice y se
    // retoma el flujo completo (esta vez la sede ya debe coincidir).
    await page.waitForLoadState('domcontentloaded', { timeout: 20000 }).catch(() => {});
    await page.waitForTimeout(1500);
    return diligenciarFormulario(page, solicitud, onPaso, intentosSede + 1);
  }

  onPaso('Buscando "Registro de Referencia"...');
  const barraBusqueda = page.locator('#searchInputMenu');
  await barraBusqueda.waitFor({ state: 'visible', timeout: 15000 });
  await barraBusqueda.fill('referencia');
  await page.locator('.menu-link__label', { hasText: 'Registro de Referencia' }).click({ timeout: 15000 });

  // ── Abrir formulario nuevo ───────────────────────────────────────────
  onPaso('Abriendo formulario nuevo...');
  await page.locator('#btnNewRecord').click({ timeout: 15000 });

  // ── Diligenciar los campos confirmados ───────────────────────────────
  onPaso('Diligenciando campos...');
  await page.locator('#documentNumberPatient').waitFor({ state: 'visible', timeout: 15000 });
  await page.locator('#documentNumberPatient').fill(solicitud.documento);
  await page.locator('#givenNamePatient').fill(solicitud.nombres);
  await page.locator('#familyNamePatient').fill(solicitud.apellidos);
  await page.locator('#age').fill(solicitud.edad);

  // Estos dos solo se llenan si tenemos el dato — hoy no lo capturamos,
  // así que quedan vacíos (el personal los completa a mano si aplica).
  if (solicitud.direccionPaciente) {
    await page.locator('#addressPatient').fill(solicitud.direccionPaciente);
  }
  if (solicitud.telefonoPaciente) {
    await page.locator('#telecomPatient').fill(solicitud.telefonoPaciente);
  }

  onPaso('Seleccionando "Ciudad de Origen"...');
  await seleccionarKendoComboBox(page, 'idHomePoliticalDivisionPatient', solicitud.ciudadOrigen);

  onPaso('Seleccionando "Servicio que refiere"...');
  // "Servicio que refiere" se deja SIN llenar — catálogo cerrado de
  // Gomedisys que la app no guarda; el operador lo elige a mano.
  onPaso('"Servicio que refiere" se deja para completar a mano.');

  onPaso('Seleccionando "Género"...');
  await seleccionarKendoDropdown(page, 'idGenere', MAPA_GENERO[solicitud.genero]);

  await page.locator('#clinicalSummary').fill(solicitud.resumenClinico);

  // Diagnóstico: los campos de búsqueda están ocultos hasta darle clic a
  // la lupa junto a "Seleccionar Diagnóstico".
  onPaso('Abriendo el buscador de diagnóstico...');
  await page.locator('#openDiagnosticLookupBtn').click();
  await page.locator('#findCodeDxICD10-coRef').waitFor({ state: 'visible', timeout: 10000 });
  await page.locator('#findCodeDxICD10-coRef').fill(solicitud.diagnosticoCodigo);
  // Gomedisys busca el diagnóstico de forma asíncrona — hay que darle
  // tiempo a que aparezca el resultado antes de "Incluir", si no el clic
  // cae en el vacío porque todavía no hay nada que incluir.
  await page.waitForTimeout(1500);
  await page.locator('#add-diagnostic-btn-coRef').click();

  onPaso('Formulario diligenciado.');
}

module.exports = { diligenciarFormulario };
