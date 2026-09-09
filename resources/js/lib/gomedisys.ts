// Puente hacia la extensión de navegador "Autocompletar Gomedisys"
// (ver rpa-gomedisys/extension/). La página nunca habla directo con la
// extensión — le manda un postMessage a content-bridge.js, que la
// extensión inyecta en esta misma página, y este archivo escucha su
// respuesta.

export interface ResultadoAutocompletar {
  ok: boolean;
  error?: string;
}

interface SolicitudParaGomedisys {
  primer_nombre: string;
  segundo_nombre?: string;
  primer_apellido: string;
  segundo_apellido?: string;
  genero: 'M' | 'F';
  edad: number;
  numero_documento: string;
  diagnostico: string;
  diagnosticos?: { codigo_cie10: string; descripcion: string }[];
  municipio_capita: string;
  direccion_paciente?: string;
  telefono_paciente?: string;
  servicio_remision?: string;
  resumen_historia_clinica: string;
}

let contadorId = 0;
const pendientes = new Map<number, (r: ResultadoAutocompletar) => void>();

// El "ready ping" de la extensión (evento 'extension-lista') no se usa como
// puerta de entrada: si llega tarde (por ejemplo justo tras cargar la
// página) bloquearía el botón con un falso negativo. En vez de eso, cada
// intento se manda directo y es el propio timeout de abajo el que informa
// si la extensión no respondió.
window.addEventListener('message', (ev) => {
  if (ev.source !== window) return;
  const datos = ev.data;
  if (!datos || datos.source !== 'gomedisys-extension' || datos.type !== 'autocompletar-resultado') return;

  const resolver = pendientes.get(datos.requestId);
  if (resolver) {
    pendientes.delete(datos.requestId);
    resolver({ ok: !!datos.ok, error: datos.error });
  }
});

function mapearSolicitudParaGomedisys(s: SolicitudParaGomedisys) {
  return {
    documento: s.numero_documento,
    nombres: [s.primer_nombre, s.segundo_nombre].filter(Boolean).join(' '),
    apellidos: [s.primer_apellido, s.segundo_apellido].filter(Boolean).join(' '),
    edad: String(s.edad),
    // Ahora sí se busca por CÓDIGO — antes no funcionaba porque solo
    // guardábamos códigos de categoría CIE-10 sin el sufijo específico
    // (ej. "B34" en vez de "B34.2"). Desde que el catálogo de diagnósticos
    // se trae directo de Gomedisys (tabla diagnosticos_cie10), el código
    // que se guarda ya es el específico y completo, así que buscar por
    // código es más simple y confiable que por descripción.
    diagnosticoCodigo: s.diagnosticos?.length ? s.diagnosticos[0].codigo_cie10 : '',
    resumenClinico: s.resumen_historia_clinica || s.diagnostico || '',
    servicioRemision: s.servicio_remision || '',
    genero: s.genero,
    // El municipio ya se captura en el formulario como "Ciudad, Departamento"
    // (tal cual aparece en Gomedisys, ej. "PALMIRA, VALLE DEL CAUCA") — se
    // usa el mismo texto para filtrar el buscador y para elegir la opción
    // exacta, porque Gomedisys tiene varios municipios con el mismo nombre
    // en departamentos distintos y sin el departamento no hay forma de
    // saber cuál es el correcto.
    ciudadOrigen: s.municipio_capita,
    direccionPaciente: s.direccion_paciente || '',
    telefonoPaciente: s.telefono_paciente || '',
  };
}

export function autocompletarEnGomedisys(s: SolicitudParaGomedisys): Promise<ResultadoAutocompletar> {
  return new Promise((resolve) => {
    const requestId = ++contadorId;
    // Tiene que ser MAYOR que el límite interno de background.js (100s) —
    // si este se cumple primero, la app le avisa a la persona que "no
    // respondió a tiempo" mientras la extensión sigue trabajando de fondo,
    // lo cual es confuso y puede hacer que se reintente por encima de un
    // envío que en realidad sí iba a terminar bien.
    const timeoutId = window.setTimeout(() => {
      pendientes.delete(requestId);
      resolve({ ok: false, error: 'La extensión de Gomedisys no respondió a tiempo. ¿Está instalada y la pestaña de Gomedisys abierta?' });
    }, 110000);

    pendientes.set(requestId, (r) => {
      window.clearTimeout(timeoutId);
      resolve(r);
    });

    window.postMessage(
      { source: 'referencia-app', type: 'autocompletar', requestId, solicitud: mapearSolicitudParaGomedisys(s) },
      window.location.origin,
    );
  });
}
