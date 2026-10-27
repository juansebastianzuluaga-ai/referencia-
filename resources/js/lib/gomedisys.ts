// Puente "Enviar a Gomedisys" — intenta las rutas en este orden:
//   1. Extensión de navegador (rpa-gomedisys/extension/) — corre dentro
//      del MISMO navegador y usa la pestaña de Gomedisys ya logueada.
//   2. Servidor local de Playwright (localhost:8787) — ventana gestionada.
//   3. Si ninguna responde, la vista cae a la ficha de copiado manual.

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
  // Sede de trabajo de Gomedisys donde se registra la referencia
  // ('URGENCIAS', 'CENTRAL DE ESPECIALISTAS' o 'CAC SANTA BARBARA') — se
  // elige al aceptar la solicitud.
  sede?: string;
}

const SERVIDOR_LOCAL = 'http://localhost:8787';

function mapearSolicitudParaGomedisys(s: SolicitudParaGomedisys) {
  return {
    documento: s.numero_documento,
    nombres: [s.primer_nombre, s.segundo_nombre].filter(Boolean).join(' '),
    apellidos: [s.primer_apellido, s.segundo_apellido].filter(Boolean).join(' '),
    edad: String(s.edad),
    // Se busca por CÓDIGO CIE-10 completo (ej. "B34.2") — el catálogo viene
    // de Gomedisys (tabla diagnosticos_cie10), así que el código guardado
    // ya es el específico.
    diagnosticoCodigo: s.diagnosticos?.length ? s.diagnosticos[0].codigo_cie10 : '',
    resumenClinico: s.resumen_historia_clinica || s.diagnostico || '',
    servicioRemision: s.servicio_remision || '',
    genero: s.genero,
    // "Ciudad, Departamento" tal cual aparece en Gomedisys — hay municipios
    // homónimos en departamentos distintos y sin el departamento no hay
    // forma de elegir el correcto.
    ciudadOrigen: s.municipio_capita,
    direccionPaciente: s.direccion_paciente || '',
    telefonoPaciente: s.telefono_paciente || '',
    sede: s.sede || '',
  };
}

// ── Ruta 1: extensión ──────────────────────────────────────────────────
// La página no habla directo con la extensión — postMessage a
// content-bridge.js, que la extensión inyecta en esta misma página. El
// acuse 'autocompletar-recibido' (enviado apenas llega el pedido) es lo
// que distingue "instalada y ocupada" de "no instalada": sin él la app no
// sabría si esperar el resultado o pasar ya al servidor local.
let contadorId = 0;

function intentarConExtension(solicitud: ReturnType<typeof mapearSolicitudParaGomedisys>): Promise<ResultadoAutocompletar | null> {
  return new Promise((resolve) => {
    const requestId = ++contadorId;
    let recibido = false;
    let resuelto = false;

    const terminar = (r: ResultadoAutocompletar | null) => {
      if (resuelto) return;
      resuelto = true;
      window.removeEventListener('message', escuchar);
      window.clearTimeout(timerAusente);
      window.clearTimeout(timerResultado);
      resolve(r);
    };

    const escuchar = (ev: MessageEvent) => {
      const datos = ev.data;
      if (ev.source !== window || !datos || datos.source !== 'gomedisys-extension' || datos.requestId !== requestId) return;
      if (datos.type === 'autocompletar-recibido') {
        recibido = true;
        return;
      }
      if (datos.type === 'autocompletar-resultado') {
        terminar({ ok: !!datos.ok, error: datos.error });
      }
    };

    window.addEventListener('message', escuchar);

    // Sin acuse en ~2.5s → la extensión no está instalada en esta página.
    const timerAusente = window.setTimeout(() => {
      if (!recibido) terminar(null);
    }, 2500);

    // Con acuse → el diligenciamiento puede tardar ~1min; se espera el
    // resultado final con el mismo margen que tenía el flujo original.
    const timerResultado = window.setTimeout(() => {
      terminar({ ok: false, error: 'La extensión no respondió a tiempo.' });
    }, 110000);

    window.postMessage(
      { source: 'referencia-app', type: 'autocompletar', requestId, solicitud },
      window.location.origin,
    );
  });
}

// ── Ruta 2: servidor local de Playwright ───────────────────────────────
async function intentarConServidorLocal(solicitud: ReturnType<typeof mapearSolicitudParaGomedisys>): Promise<ResultadoAutocompletar> {
  try {
    const res = await fetch(`${SERVIDOR_LOCAL}/autocompletar`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(solicitud),
      // El diligenciamiento toma ~30-60s — hay que darle aire al servidor.
      signal: AbortSignal.timeout(120000),
    });
    const datos = await res.json().catch(() => ({}));
    return { ok: !!datos.ok, error: datos.error || (res.ok ? undefined : 'El servidor local respondió con error.') };
  } catch {
    // Servidor local apagado o no instalado — la vista usa este marcador
    // para caer a la ficha de copiado sin asustar al usuario.
    return { ok: false, error: 'local-offline' };
  }
}

export async function autocompletarEnGomedisys(s: SolicitudParaGomedisys): Promise<ResultadoAutocompletar> {
  const solicitud = mapearSolicitudParaGomedisys(s);
  const porExtension = await intentarConExtension(solicitud);
  if (porExtension) return porExtension;
  return intentarConServidorLocal(solicitud);
}
