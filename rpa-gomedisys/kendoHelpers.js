/** Selecciona una opción en un widget Kendo UI DropDownList (no es un
 * <select> normal: hay que abrirlo con clic y luego hacer clic en la
 * opción dentro de la lista que aparece). `idCampo` es el id del <input>
 * oculto de Kendo (ej. "idServiceReferral") — de ahí se derivan el
 * combobox visible (por su aria-controls) y la lista de opciones. */
async function seleccionarKendoDropdown(page, idCampo, textoOpcion) {
  const combobox = page.locator(`span[role="combobox"][aria-controls="${idCampo}_listbox"]`);
  await combobox.click();
  const listbox = page.locator(`#${idCampo}_listbox`);
  await listbox.waitFor({ state: 'visible', timeout: 10000 });
  await listbox.getByText(textoOpcion, { exact: true }).click();
}

/** Igual que seleccionarKendoDropdown, pero para un Kendo ComboBox
 * (buscador con filtro, no un simple abrir-y-elegir) — hay que ESCRIBIR
 * para filtrar antes de que aparezcan las opciones. `idCampo` es el id
 * base (ej. "idHomePoliticalDivisionPatient"); el input real tiene el
 * sufijo "_input" y la lista de resultados el sufijo "_listbox".
 *
 * Si se pasa `textoOpcion`, hace clic en la opción con ESE texto exacto
 * (más seguro). Si no se pasa (todavía no sabemos el formato exacto del
 * texto de las opciones de este campo), hace clic en el primer resultado
 * filtrado — razonable para una primera prueba, pero conviene reemplazarlo
 * por coincidencia exacta en cuanto se vea el formato real de las opciones. */
async function seleccionarKendoComboBox(page, idCampo, textoBusqueda, textoOpcion) {
  // El input real de este widget no siempre tiene `id` propio (solo
  // `name`) — se busca por name para no depender de que exista.
  const input = page.locator(`[name="${idCampo}_input"]`);
  await input.click();
  await input.fill(textoBusqueda);
  const listbox = page.locator(`#${idCampo}_listbox`);
  await listbox.waitFor({ state: 'visible', timeout: 10000 });
  const opcion = textoOpcion
    ? listbox.getByText(textoOpcion, { exact: true }).first()
    : listbox.locator('li, [role="option"]').first();
  await opcion.click();
}

module.exports = { seleccionarKendoDropdown, seleccionarKendoComboBox };
