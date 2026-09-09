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
async function diligenciarFormulario(page, solicitud, onPaso = () => {}) {
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
    onPaso('Seleccionando sede "Urgencias"...');
    await page.getByText(/urgencias/i).first().click();
    await botonConfirmarSede.click();
  }

  // ── Navegar a "Registro de Referencia" ──────────────────────────────
  onPaso('Abriendo el menú...');
  await page.locator('#menuID').click({ timeout: 15000 });

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
  await seleccionarKendoDropdown(page, 'idServiceReferral', solicitud.servicioRemision);

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
