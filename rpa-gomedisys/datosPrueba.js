// Datos de prueba de la solicitud — por ahora quemados aquí mismo; cuando
// se conecte con Laravel, estos vendrán de la API en vez de estar fijos.
const solicitud = {
  documento: '123456789',
  nombres: 'JUAN SEBASTIAN',
  apellidos: 'ZULUAGA ALVAREZ',
  edad: '18',
  diagnosticoCodigo: 'J449',
  resumenClinico: 'Prueba de automatización — resumen clínico de ejemplo, no es un dato real.',
  servicioRemision: 'Acondicionamiento Fisico', // valor de prueba — debe coincidir EXACTO con una opción real de Gomedisys
  genero: 'M', // 'M' o 'F', como en nuestro sistema
  ciudadOrigen: 'Palmira', // se escribe y se filtra — no hace falta el nombre exacto completo
  direccionPaciente: '', // no lo capturamos hoy en nuestro sistema — queda vacío hasta que se agregue
  telefonoPaciente: '', // ídem — vacío hasta que tengamos este dato
};

// Equivalencias de catálogos: valor de nuestro sistema → texto exacto de
// la opción en Gomedisys. Mismo patrón para Régimen/Contrato si se agregan.
const MAPA_GENERO = { M: 'Hombre', F: 'Mujer' };

module.exports = { solicitud, MAPA_GENERO };
