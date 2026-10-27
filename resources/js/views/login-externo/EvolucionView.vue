<template>
  <div class="evo-page h-full overflow-y-auto p-4 sm:p-5 animate-fade-in-up"
    style="animation-duration: 0.4s; animation-fill-mode: both;">

    <!-- ── Header ── -->
    <div class="flex items-center justify-between mb-4 shrink-0">
      <div class="flex items-center gap-2.5">
        <span class="evo-header-bar"></span>
        <div>
          <h1 class="evo-title text-lg sm:text-xl font-bold">Evolución de pacientes</h1>
          <p class="evo-sub text-xs mt-1">Registre cada día cómo evolucionan los pacientes con referencia pendiente.</p>
        </div>
      </div>
      <button @click="cargar" class="evo-refresh inline-flex items-center gap-1.5 text-xs font-medium transition-colors">
        <component :is="RefreshCwIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': cargando }" />
        Actualizar
      </button>
    </div>

    <!-- ── Buscador ── -->
    <div class="evo-search-card rounded-2xl p-3 sm:p-4 mb-4">
      <label class="filter-label">Buscar paciente</label>
      <el-input
        v-model="busqueda"
        placeholder="ID de la referencia (ej. ID000865) o cédula del paciente…"
        :prefix-icon="SearchIcon"
        clearable
        size="default"
      />
    </div>

      <!-- ── Cargando ── -->
      <div v-if="cargando" class="space-y-3">
        <div v-for="i in 3" :key="i" class="evo-card rounded-2xl p-4 space-y-2.5">
          <div class="flex items-center gap-3">
            <div class="skeleton-circle"></div>
            <div class="flex-1 space-y-1.5">
              <div class="skeleton-bar" style="width:35%; height:11px;"></div>
              <div class="skeleton-bar" style="width:55%; height:9px;"></div>
            </div>
          </div>
          <div class="skeleton-bar" style="width:100%; height:44px; border-radius:10px;"></div>
        </div>
      </div>

      <!-- ── Vacío ── -->
      <div v-else-if="solicitudesFiltradas.length === 0" class="evo-login-prompt">
        <div class="evo-empty-icon"><component :is="busqueda ? SearchIcon : ActivityIcon" class="w-7 h-7" /></div>
        <p class="evo-empty-title">{{ tituloVacio }}</p>
        <p class="evo-empty-sub">{{ subtituloVacio }}</p>
      </div>

      <!-- ── Pacientes ── -->
      <div v-else class="space-y-3">
        <div v-for="sol in solicitudesFiltradas" :key="sol.id" class="evo-card rounded-2xl overflow-hidden anim-slide-up">
          <!-- Cabecera del paciente -->
          <div class="evo-card-head px-4 py-3 flex items-center gap-3 flex-wrap">
            <div class="evo-avatar">{{ inicialesPaciente(sol) }}</div>
            <div class="flex-1 min-w-[160px]">
              <div class="flex items-center gap-2 flex-wrap">
                <p class="font-bold text-[13px]" :style="{ color: isDark ? '#e2e8f0' : '#1e2d55' }">
                  {{ nombrePaciente(sol) }}
                </p>
                <span class="evo-id-chip">{{ formatId(sol.id) }}</span>
                <span v-if="sol.urgencia_vital" class="evo-urgencia-chip">URGENCIA VITAL</span>
              </div>
              <p class="text-[11px] mt-0.5" :style="{ color: isDark ? '#94a3b8' : '#8a9ab5' }">
                {{ sol.tipo_documento }} {{ sol.numero_documento }} · {{ sol.especialidad_requerida }} · {{ sol.eps }}
              </p>
            </div>
            <div class="flex items-center gap-2 shrink-0 ml-auto">
              <span class="evo-estado-chip" :class="'estado-' + sol.estado">{{ estadoLabel(sol.estado) }}</span>
              <span class="evo-dias-chip" :title="'Remitido el ' + formatFecha(sol.created_at)">
                <component :is="ClockIcon" class="w-3 h-3" />
                {{ diasEsperando(sol) }}
              </span>
              <button class="evo-detail-btn" type="button" @click="verDetalle(sol)">
                <component :is="EyeIcon" class="w-3.5 h-3.5" />
                <span>Ver ficha</span>
              </button>
            </div>
          </div>

          <div class="evo-card-body px-4 py-3 space-y-3">
            <div class="evo-quick-facts">
              <div class="evo-quick-fact">
                <span>Paciente</span>
                <strong>{{ sol.genero === 'F' ? 'Femenino' : sol.genero === 'M' ? 'Masculino' : '—' }}<template v-if="sol.edad"> · {{ sol.edad }} años</template></strong>
              </div>
              <div class="evo-quick-fact">
                <span>Remitido</span>
                <strong>{{ formatFecha(sol.fecha || sol.created_at) }}<template v-if="sol.hora"> · {{ formatHora(sol.hora) }}</template></strong>
              </div>
              <div class="evo-quick-fact">
                <span>Servicio actual</span>
                <strong>{{ sol.servicio_ubicacion_actual || '—' }}</strong>
              </div>
            </div>

            <!-- Nueva nota -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="evo-nota-label">Evolución de hoy — {{ hoyLabel }}</label>
                <span v-if="tieneNotaHoy(sol)" class="evo-nota-hoy">
                  <component :is="CheckCircleIcon" class="w-3 h-3" /> Ya registrada hoy
                </span>
              </div>
              <div class="flex gap-2 items-start">
                <textarea
                  v-model="drafts[sol.id]"
                  rows="2"
                  maxlength="2000"
                  class="evo-textarea"
                  placeholder="Describa la evolución del paciente: estado clínico, signos, procedimientos, observaciones…"
                ></textarea>
                <button
                  class="evo-save-btn"
                  :disabled="!drafts[sol.id]?.trim() || guardando.has(sol.id)"
                  @click="guardarNota(sol)"
                >
                  <component :is="guardando.has(sol.id) ? LoaderIcon : SendIcon" class="w-4 h-4" :class="{ 'animate-spin': guardando.has(sol.id) }" />
                  <span class="hidden sm:inline">{{ guardando.has(sol.id) ? 'Guardando…' : 'Registrar' }}</span>
                </button>
              </div>
            </div>

            <!-- Historial de notas -->
            <div v-if="sol.evoluciones?.length" class="evo-timeline">
              <div v-for="nota in sol.evoluciones" :key="nota.id" class="evo-nota">
                <div class="evo-nota-dot"></div>
                <div class="evo-nota-body">
                  <div class="evo-nota-fecha">
                    <component :is="CalendarIcon" class="w-3 h-3" />
                    {{ formatFecha(nota.created_at) }} · {{ formatHoraNota(nota.created_at) }}
                  </div>
                  <p class="evo-nota-texto">{{ nota.nota }}</p>
                </div>
              </div>
            </div>
            <p v-else class="evo-sin-notas">Sin evoluciones registradas todavía — anote la primera arriba.</p>
          </div>
        </div>
      </div>

    <el-dialog
      v-model="detalleVisible"
      width="min(920px, calc(100vw - 32px))"
      class="evo-detail-dialog"
      :show-close="false"
      align-center
      append-to-body
    >
      <template #header>
        <div v-if="solicitudDetalle" class="evo-modal-header">
          <div class="evo-modal-title-group">
            <div class="evo-modal-title-icon"><component :is="ClipboardIcon" class="w-4 h-4" /></div>
            <div>
              <h2 class="evo-modal-title">Detalle de solicitud</h2>
              <p class="evo-modal-subtitle">{{ formatId(solicitudDetalle.id) }} · {{ formatFecha(solicitudDetalle.created_at) }} · {{ formatHoraNota(solicitudDetalle.created_at) }}</p>
            </div>
          </div>
          <div class="evo-modal-actions">
            <span class="evo-modal-status" :class="'status-' + solicitudDetalle.estado">{{ estadoLabel(solicitudDetalle.estado) }}</span>
            <span v-if="solicitudDetalle.urgencia_vital" class="evo-urgencia-chip">URGENCIA VITAL</span>
            <button class="evo-detail-close" type="button" aria-label="Cerrar detalle" @click="detalleVisible = false">
              <component :is="XIcon" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </template>

      <div v-if="solicitudDetalle" class="evo-modal-body">
        <div class="evo-detail-top">
          <section class="evo-panel evo-panel-blue">
            <div class="evo-panel-title"><span class="evo-panel-icon"><component :is="UserIcon" class="w-4 h-4" /></span> Paciente</div>
            <div class="evo-info-rows">
              <div class="evo-info-row"><span>Nombre</span><strong>{{ nombrePaciente(solicitudDetalle) }}</strong></div>
              <div class="evo-info-row"><span>Documento</span><strong>{{ solicitudDetalle.tipo_documento }} {{ solicitudDetalle.numero_documento }}</strong></div>
              <div class="evo-info-row"><span>Edad / Género</span><strong>{{ solicitudDetalle.edad ? `${solicitudDetalle.edad} años` : '—' }} · {{ solicitudDetalle.genero === 'F' ? 'Femenino' : solicitudDetalle.genero === 'M' ? 'Masculino' : '—' }}</strong></div>
              <div class="evo-info-row"><span>EPS</span><strong>{{ solicitudDetalle.eps || '—' }}</strong></div>
              <div class="evo-info-row"><span>Municipio</span><strong>{{ solicitudDetalle.municipio_capita || '—' }}</strong></div>
              <div class="evo-info-row"><span>Especialidad</span><strong>{{ solicitudDetalle.especialidad_requerida || '—' }}</strong></div>
              <div class="evo-info-row"><span>Servicio actual</span><strong>{{ solicitudDetalle.servicio_ubicacion_actual || '—' }}</strong></div>
            </div>
          </section>

          <section class="evo-panel evo-panel-amber">
            <div class="evo-panel-title"><span class="evo-panel-icon"><component :is="BuildingIcon" class="w-4 h-4" /></span> Remisión</div>
            <div class="evo-info-rows">
              <div class="evo-info-row"><span>Fecha solicitud</span><strong>{{ formatFecha(solicitudDetalle.fecha || solicitudDetalle.created_at) }} · {{ solicitudDetalle.hora ? formatHora(solicitudDetalle.hora) : '—' }}</strong></div>
              <div class="evo-info-row"><span>Institución</span><strong>{{ solicitudDetalle.clinica?.nombre || solicitudDetalle.institucion_remitente || '—' }}</strong></div>
              <div class="evo-info-row"><span>Remite</span><strong>{{ solicitudDetalle.quien_remitente || '—' }}</strong></div>
              <div class="evo-info-row"><span>Teléfono contacto</span><strong>{{ solicitudDetalle.telefono_contacto || solicitudDetalle.telefono_paciente || '—' }}</strong></div>
              <div class="evo-info-row"><span>Correo</span><strong>{{ solicitudDetalle.correo_contacto || '—' }}</strong></div>
              <div class="evo-info-row"><span>Tipo referencia</span><strong>{{ solicitudDetalle.tipo_referencia || '—' }}</strong></div>
              <div v-if="solicitudDetalle.hora_llegada" class="evo-info-row"><span>Llegada esperada</span><strong>{{ formatHora(solicitudDetalle.hora_llegada) }} · {{ solicitudDetalle.lugar_llegada || '—' }}</strong></div>
            </div>
          </section>

          <section class="evo-panel evo-panel-violet">
            <div class="evo-panel-title"><span class="evo-panel-icon"><component :is="StethoscopeIcon" class="w-4 h-4" /></span> Diagnósticos</div>
            <div v-if="solicitudDetalle.diagnosticos?.length" class="evo-diagnosis-list">
              <div v-for="(dx, index) in solicitudDetalle.diagnosticos" :key="dx.id || index" class="evo-diagnosis">
                <span class="evo-diagnosis-code">{{ dx.codigo_cie10 || '—' }}</span>
                <span class="evo-diagnosis-description">{{ dx.descripcion }}</span>
                <span v-if="index === 0" class="evo-principal-chip">Principal</span>
              </div>
            </div>
            <p v-else class="evo-detail-paragraph">{{ solicitudDetalle.diagnostico || 'Sin diagnóstico registrado' }}</p>
          </section>
        </div>

        <div class="evo-detail-bottom">
          <section class="evo-panel evo-panel-history">
            <div class="evo-panel-title"><span class="evo-panel-icon"><component :is="ClipboardIcon" class="w-4 h-4" /></span> Historia clínica</div>
            <div class="evo-history-copy">{{ solicitudDetalle.resumen_historia_clinica || 'No se agregó resumen de historia clínica.' }}</div>
            <div v-if="solicitudDetalle.observaciones || solicitudDetalle.condicion_especial || solicitudDetalle.requiere_aislamiento" class="evo-extra-info">
              <div v-if="solicitudDetalle.observaciones"><strong>Observaciones:</strong> {{ solicitudDetalle.observaciones }}</div>
              <div v-if="solicitudDetalle.condicion_especial"><strong>Condición especial:</strong> {{ solicitudDetalle.condicion_especial }}</div>
              <div v-if="solicitudDetalle.requiere_aislamiento"><strong>Aislamiento:</strong> {{ solicitudDetalle.tipo_aislamiento || 'Requerido' }}</div>
            </div>
          </section>

          <div class="evo-detail-side">
            <section class="evo-panel evo-panel-slate">
              <div class="evo-panel-title"><span class="evo-panel-icon"><component :is="ClockIcon" class="w-4 h-4" /></span> Seguimiento</div>
              <div class="evo-tracking-row">
                <span class="evo-tracking-dot done"><component :is="CheckCircleIcon" class="w-3 h-3" /></span>
                <div><strong>Solicitud enviada</strong><small>{{ formatFecha(solicitudDetalle.created_at) }} · {{ formatHoraNota(solicitudDetalle.created_at) }}</small></div>
              </div>
              <div class="evo-tracking-row">
                <span class="evo-tracking-dot current"><component :is="ActivityIcon" class="w-3 h-3" /></span>
                <div><strong>{{ estadoLabel(solicitudDetalle.estado) }}</strong><small>{{ solicitudDetalle.hora_respuesta ? `Respuesta: ${formatHora(solicitudDetalle.hora_respuesta)}` : 'Estado actual de la referencia' }}</small></div>
              </div>
              <div v-if="solicitudDetalle.hora_llegada || solicitudDetalle.lugar_llegada" class="evo-arrival-chip">
                Llegada {{ solicitudDetalle.hora_llegada ? formatHora(solicitudDetalle.hora_llegada) : '' }} {{ solicitudDetalle.lugar_llegada ? `· ${solicitudDetalle.lugar_llegada}` : '' }} {{ solicitudDetalle.sede ? `· ${solicitudDetalle.sede}` : '' }}
              </div>
            </section>

            <section class="evo-panel evo-panel-red">
              <div class="evo-panel-title"><span class="evo-panel-icon"><component :is="PaperclipIcon" class="w-4 h-4" /></span> Soportes <span class="evo-count">{{ solicitudDetalle.adjuntos?.length || 0 }}</span></div>
              <div v-if="solicitudDetalle.adjuntos?.length" class="evo-attachments">
                <div v-for="adjunto in solicitudDetalle.adjuntos" :key="adjunto.id" class="evo-attachment">
                  <span class="evo-attachment-type">{{ extensionArchivo(adjunto.nombre_original) }}</span>
                  <span class="evo-attachment-name">{{ adjunto.nombre_original }}</span>
                  <small>{{ formatTamano(adjunto.tamano) }}</small>
                </div>
              </div>
              <p v-else class="evo-no-attachments">Esta solicitud no tiene soportes adjuntos.</p>
            </section>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="evo-modal-footer">
          <span><component :is="ShieldIcon" class="w-3.5 h-3.5" /> Información clínica confidencial</span>
          <button class="evo-detail-footer-btn" type="button" @click="detalleVisible = false">
            <component :is="XIcon" class="w-3.5 h-3.5" /> Cerrar
          </button>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { Search as SearchIcon, RefreshCw as RefreshCwIcon, Clock as ClockIcon, Activity as ActivityIcon, CheckCircle as CheckCircleIcon, Send as SendIcon, Loader2 as LoaderIcon, Calendar as CalendarIcon, Eye as EyeIcon, X as XIcon, User as UserIcon, Building2 as BuildingIcon, Stethoscope as StethoscopeIcon, ClipboardList as ClipboardIcon, Paperclip as PaperclipIcon, ShieldCheck as ShieldIcon } from '@lucide/vue';
import { ElDialog, ElInput } from 'element-plus';
import http from '@/plugins/axios';
import notify from '@/plugins/toast';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { useClinicaLayoutStore } from '@/stores/clinicaLayout';

interface Evolucion {
  id: number;
  nota: string;
  created_at: string;
}

interface Solicitud {
  id: number;
  primer_nombre: string;
  segundo_nombre?: string;
  primer_apellido: string;
  segundo_apellido?: string;
  tipo_documento: string;
  numero_documento: string;
  edad?: number;
  genero?: string;
  eps: string;
  fecha?: string;
  hora?: string;
  telefono_paciente?: string;
  direccion_paciente?: string;
  municipio_capita?: string;
  especialidad_requerida: string;
  tipo_referencia?: string;
  servicio_ubicacion_actual?: string;
  institucion_remitente?: string;
  quien_remitente?: string;
  telefono_contacto?: string;
  correo_contacto?: string;
  clinica?: { nombre?: string };
  estado: string;
  urgencia_vital?: boolean;
  hora_llegada?: string;
  hora_respuesta?: string;
  lugar_llegada?: string;
  sede?: string;
  diagnostico?: string;
  diagnosticos?: { id?: number; codigo_cie10?: string; descripcion: string }[];
  resumen_historia_clinica?: string;
  observaciones?: string;
  condicion_especial?: string;
  requiere_aislamiento?: boolean;
  tipo_aislamiento?: string;
  created_at: string;
  adjuntos?: { id: number; nombre_original: string; mime_type?: string; tamano: number }[];
  evoluciones?: Evolucion[];
}

const clinicaAuth = useClinicaAuthStore();
const layout = useClinicaLayoutStore();
const isDark = computed(() => layout.isDarkMode);

const solicitudes = ref<Solicitud[]>([]);
const cargando = ref(false);
const busqueda = ref('');
const drafts = reactive<Record<number, string>>({});
const guardando = reactive(new Set<number>());
const detalleVisible = ref(false);
const solicitudDetalle = ref<Solicitud | null>(null);

const hoyLabel = new Date().toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' });

// Con sesión el servidor ya devuelve solo las de la clínica y el filtro es
// instantáneo en cliente; sin sesión (portal público) la búsqueda va al
// servidor — no se muestra nada hasta escribir el ID o la cédula.
const solicitudesFiltradas = computed(() => {
  if (!clinicaAuth.isAuthenticated) return solicitudes.value;
  const term = busqueda.value.trim().toLowerCase();
  if (!term) return solicitudes.value;
  const digitos = term.replace(/\D/g, '');
  return solicitudes.value.filter((sol) => {
    if (sol.numero_documento?.toLowerCase().includes(term)) return true;
    if (formatId(sol.id).toLowerCase().includes(term)) return true;
    if (digitos && String(sol.id) === String(Number(digitos))) return true;
    return false;
  });
});

const tituloVacio = computed(() => {
  if (!clinicaAuth.isAuthenticated && busqueda.value.trim().length < 3) return 'Busque a su paciente';
  if (busqueda.value.trim()) return 'Sin resultados';
  return 'No hay pacientes pendientes';
});

const subtituloVacio = computed(() => {
  if (!clinicaAuth.isAuthenticated && busqueda.value.trim().length < 3) {
    return 'Escriba el ID de la referencia (ID000865) o la cédula completa del paciente para ver sus evoluciones.';
  }
  if (busqueda.value.trim()) {
    return 'Ningún paciente pendiente coincide con esa búsqueda. Verifique el ID de la referencia o la cédula.';
  }
  return 'Cuando una referencia de su clínica esté pendiente, el paciente aparecerá aquí para anotar su evolución diaria.';
});

function formatId(id: number): string {
  return 'ID' + String(id).padStart(6, '0');
}

function inicialesPaciente(sol: Solicitud): string {
  return `${sol.primer_nombre?.[0] ?? ''}${sol.primer_apellido?.[0] ?? ''}`.toUpperCase();
}

function nombrePaciente(sol: Solicitud): string {
  return [sol.primer_nombre, sol.segundo_nombre, sol.primer_apellido, sol.segundo_apellido].filter(Boolean).join(' ');
}

function estadoLabel(estado: string): string {
  return { pendiente: 'Pendiente', pendiente_cama: 'Esperando cama', en_espera: 'En espera' }[estado] ?? estado;
}

function diasEsperando(sol: Solicitud): string {
  const dias = Math.max(0, Math.floor((Date.now() - new Date(sol.created_at).getTime()) / 86400000));
  return dias === 0 ? 'Hoy' : dias === 1 ? '1 día' : `${dias} días`;
}

function formatFecha(fecha: string): string {
  if (!fecha) return '—';
  const d = new Date(fecha);
  if (isNaN(d.getTime())) return fecha;
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatHoraNota(fecha: string): string {
  return new Date(fecha).toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit', hour12: true });
}

function formatHora(hora: string): string {
  if (!hora) return '';
  const [hours, minutes] = hora.split(':').map(Number);
  if (Number.isNaN(hours) || Number.isNaN(minutes)) return hora;
  const ampm = hours >= 12 ? 'p. m.' : 'a. m.';
  const hour12 = hours % 12 || 12;
  return `${hour12}:${String(minutes).padStart(2, '0')} ${ampm}`;
}

function verDetalle(solicitud: Solicitud): void {
  solicitudDetalle.value = solicitud;
  detalleVisible.value = true;
}

function extensionArchivo(nombre: string): string {
  return nombre.split('.').pop()?.slice(0, 4).toUpperCase() || 'FILE';
}

function formatTamano(bytes: number): string {
  if (!bytes) return '—';
  return bytes >= 1024 * 1024
    ? `${(bytes / (1024 * 1024)).toFixed(1)} MB`
    : `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

function tieneNotaHoy(sol: Solicitud): boolean {
  const hoy = new Date().toDateString();
  return (sol.evoluciones ?? []).some((n) => new Date(n.created_at).toDateString() === hoy);
}

async function cargar() {
  // Portal público: sin sesión no se pide nada hasta que haya búsqueda —
  // el servidor exige el ID o la cédula (mín. 3 caracteres) para devolver datos.
  if (!clinicaAuth.isAuthenticated && busqueda.value.trim().length < 3) {
    solicitudes.value = [];
    return;
  }
  cargando.value = true;
  try {
    const { data } = await http.get('/api/externo/evoluciones', {
      params: { buscar: busqueda.value.trim() || undefined },
      headers: { 'X-Skip-Auth-Redirect': '1' },
    });
    solicitudes.value = data.data;
  } catch {
    notify.error('No se pudieron cargar los pacientes pendientes');
  } finally {
    cargando.value = false;
  }
}

let buscarTimer: ReturnType<typeof setTimeout> | null = null;
watch(busqueda, () => {
  if (buscarTimer) clearTimeout(buscarTimer);
  buscarTimer = setTimeout(cargar, 350);
});

async function guardarNota(sol: Solicitud) {
  const nota = drafts[sol.id]?.trim();
  if (!nota) return;
  guardando.add(sol.id);
  try {
    const { data } = await http.post(`/api/externo/solicitudes/${sol.id}/evoluciones`, { nota });
    sol.evoluciones = [data.data, ...(sol.evoluciones ?? [])];
    drafts[sol.id] = '';
    notify.success('Evolución registrada');
  } catch (e: any) {
    notify.error(e?.response?.data?.message ?? 'No se pudo registrar la evolución');
  } finally {
    guardando.delete(sol.id);
  }
}

onMounted(async () => {
  if (!clinicaAuth.isHydrated) {
    await clinicaAuth.fetchClinica();
  }
  cargar();
});
</script>

<style scoped>
.evo-header-bar {
  width: 4px;
  height: 38px;
  border-radius: 4px;
  background: linear-gradient(180deg, var(--rf-primary, #0d2d6b), #2563eb);
}
.evo-title { color: #1e2d55; letter-spacing: -0.01em; }
.dark .evo-title { color: #e2e8f0; }
.evo-sub { color: #8a9ab5; }
.dark .evo-sub { color: #94a3b8; }

.evo-refresh {
  padding: 7px 13px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
}
.evo-refresh:hover { border-color: var(--rf-primary, #0d2d6b); color: var(--rf-primary, #0d2d6b); }
.dark .evo-refresh { background: #1e293b; border-color: #334155; color: #cbd5e1; }

.evo-search-card, .evo-card {
  background: #fff;
  border: 1px solid #e8edf5;
  box-shadow: 0 1px 3px rgba(13, 45, 107, 0.05);
}
.dark .evo-search-card, .dark .evo-card {
  background: #131a2a;
  border-color: #1f2b45;
  box-shadow: none;
}

.filter-label {
  display: block;
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #8a9ab5;
  margin-bottom: 5px;
}
.dark .filter-label { color: #64748b; }

.evo-card-head {
  border-bottom: 1px solid #eef2f9;
  background: linear-gradient(90deg, rgba(13, 45, 107, 0.025), transparent 60%);
}
.dark .evo-card-head { border-color: #1f2b45; background: rgba(255,255,255,0.02); }
.evo-card {
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.evo-card:hover {
  transform: translateY(-2px);
  border-color: #cbdaf2;
  box-shadow: 0 12px 28px rgba(13, 45, 107, 0.09);
}
.dark .evo-card:hover { border-color: #334a70; box-shadow: 0 12px 28px rgba(0,0,0,.22); }
.evo-card-body { background: linear-gradient(180deg, #fff, #fcfdff); }
.dark .evo-card-body { background: linear-gradient(180deg, #131a2a, #111827); }

.evo-quick-facts {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border: 1px solid #edf1f7;
  border-radius: 12px;
  overflow: hidden;
  background: #f9fbfe;
}
.dark .evo-quick-facts { background: rgba(15,23,42,.45); border-color: #263352; }
.evo-quick-fact { display: flex; flex-direction: column; gap: 3px; padding: 9px 12px; min-width: 0; }
.evo-quick-fact + .evo-quick-fact { border-left: 1px solid #edf1f7; }
.dark .evo-quick-fact + .evo-quick-fact { border-color: #263352; }
.evo-quick-fact span { color: #94a3b8; font-size: 9px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
.evo-quick-fact strong { color: #334155; font-size: 11px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dark .evo-quick-fact strong { color: #cbd5e1; }

.evo-detail-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid #d9e5f6;
  border-radius: 9px;
  background: #fff;
  color: #16468e;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
  transition: background .15s ease, border-color .15s ease, transform .15s ease;
}
.evo-detail-btn:hover { background: #eff6ff; border-color: #9fc0ef; transform: translateY(-1px); }
.dark .evo-detail-btn { background: #17243b; border-color: #2d4265; color: #93c5fd; }
.dark .evo-detail-btn:hover { background: #1e3a5f; border-color: #3b82f6; }

.evo-avatar {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, var(--rf-primary, #0d2d6b), #2563eb);
  box-shadow: inset 0 0 0 1.5px rgba(255,255,255,0.3);
  flex-shrink: 0;
}

.evo-id-chip {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: 0.04em;
  padding: 2px 7px;
  border-radius: 6px;
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #dbeafe;
}
.dark .evo-id-chip { background: #1e3a5f; color: #93c5fd; border-color: #1e3a5f; }

.evo-urgencia-chip {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 2px 7px;
  border-radius: 6px;
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  animation: urgencia-pulse 1.6s ease-in-out infinite;
}
.dark .evo-urgencia-chip { background: #4c1d1d; color: #fca5a5; border-color: #7f1d1d; }
@keyframes urgencia-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

.evo-estado-chip {
  font-size: 10px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 999px;
}
.evo-estado-chip.estado-pendiente { background: #fef3c7; color: #b45309; }
.evo-estado-chip.estado-pendiente_cama { background: #ffedd5; color: #c2410c; }
.evo-estado-chip.estado-en_espera { background: #dbeafe; color: #1d4ed8; }
.dark .evo-estado-chip.estado-pendiente { background: #422f0c; color: #fbbf24; }
.dark .evo-estado-chip.estado-pendiente_cama { background: #431407; color: #fdba74; }
.dark .evo-estado-chip.estado-en_espera { background: #1e3a5f; color: #60a5fa; }

.evo-dias-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 600;
  padding: 3px 9px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #64748b;
}
.dark .evo-dias-chip { background: #1e293b; color: #94a3b8; }

.evo-nota-label {
  font-size: 10.5px;
  font-weight: 700;
  color: #64748b;
  text-transform: capitalize;
}
.dark .evo-nota-label { color: #94a3b8; }

.evo-nota-hoy {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 700;
  color: #16a34a;
}
.dark .evo-nota-hoy { color: #4ade80; }

.evo-textarea {
  flex: 1;
  resize: vertical;
  min-height: 52px;
  padding: 9px 12px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  font-size: 12.5px;
  line-height: 1.5;
  color: #1e2d55;
  background: #fbfcfe;
  outline: none;
  transition: border-color .15s ease, box-shadow .15s ease;
}
.evo-textarea::placeholder { color: #a5b3c9; }
.evo-textarea:focus {
  border-color: var(--rf-primary, #0d2d6b);
  box-shadow: 0 0 0 3px rgba(13, 45, 107, 0.08);
}
.dark .evo-textarea {
  background: #0f172a;
  border-color: #334155;
  color: #e2e8f0;
}
.dark .evo-textarea::placeholder { color: #475569; }
.dark .evo-textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

.evo-save-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0 14px;
  height: 52px;
  border-radius: 10px;
  border: none;
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, var(--rf-primary, #0d2d6b), #2563eb);
  cursor: pointer;
  transition: opacity .15s ease, transform .15s ease, box-shadow .15s ease;
  box-shadow: 0 4px 12px rgba(13, 45, 107, 0.25);
}
.evo-save-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(13, 45, 107, 0.32); }
.evo-save-btn:disabled { opacity: 0.45; cursor: not-allowed; box-shadow: none; }

.evo-timeline {
  display: flex;
  flex-direction: column;
  gap: 0;
  border-top: 1px dashed #e2e8f0;
  padding-top: 10px;
}
.dark .evo-timeline { border-color: #1f2b45; }

.evo-nota {
  display: flex;
  gap: 10px;
  position: relative;
  padding: 4px 0 12px 0;
}
.evo-nota:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 5px;
  top: 16px;
  bottom: -2px;
  width: 1.5px;
  background: #e2e8f0;
}
.dark .evo-nota:not(:last-child)::before { background: #263352; }

.evo-nota-dot {
  width: 11px;
  height: 11px;
  border-radius: 50%;
  margin-top: 4px;
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--rf-primary, #0d2d6b), #2563eb);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.evo-nota-fecha {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10.5px;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 3px;
}
.dark .evo-nota-fecha { color: #94a3b8; }

.evo-nota-texto {
  font-size: 12.5px;
  line-height: 1.55;
  color: #334155;
  white-space: pre-line;
}
.dark .evo-nota-texto { color: #cbd5e1; }

.evo-sin-notas {
  font-size: 11.5px;
  color: #a5b3c9;
  font-style: italic;
  border-top: 1px dashed #e2e8f0;
  padding-top: 10px;
}
.dark .evo-sin-notas { color: #475569; border-color: #1f2b45; }

/* Vacío / login */
.evo-login-prompt {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 60px 20px;
  background: #fff;
  border: 1px dashed #dbe3ef;
  border-radius: 20px;
}
.dark .evo-login-prompt { background: #131a2a; border-color: #1f2b45; }

.evo-empty-icon {
  width: 60px;
  height: 60px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
  color: var(--rf-primary, #0d2d6b);
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
}
.dark .evo-empty-icon { background: #1e293b; color: #60a5fa; }

.evo-empty-title { font-size: 15px; font-weight: 800; color: #1e2d55; margin-bottom: 6px; }
.dark .evo-empty-title { color: #e2e8f0; }
.evo-empty-sub { font-size: 12px; color: #8a9ab5; max-width: 380px; line-height: 1.55; }
.dark .evo-empty-sub { color: #94a3b8; }

.evo-detail-dialog :deep(.el-dialog) {
  border: 1px solid rgba(226,232,240,.9);
  border-radius: 20px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 28px 80px rgba(15, 35, 75, .28);
  max-height: calc(100vh - 36px);
  display: flex;
  flex-direction: column;
}
.evo-detail-dialog :deep(.el-dialog__header) {
  margin: 0;
  padding: 12px 18px;
  border-bottom: 1px solid #e8edf5;
  background:
    radial-gradient(circle, rgba(59,130,246,.13) 1px, transparent 1.2px) 0 0 / 13px 13px,
    linear-gradient(110deg, #fff 10%, #f8fbff 100%);
}
.evo-detail-dialog :deep(.el-dialog__body) { padding: 8px 12px 10px; max-height: calc(100vh - 132px); overflow-y: auto; background: #f5f8fc; }
.evo-detail-dialog :deep(.el-dialog__footer) { padding: 0 16px 10px; background: #fff; flex-shrink: 0; }
.dark .evo-detail-dialog :deep(.el-dialog) { border-color: #263352; background: #0f172a; }
.dark .evo-detail-dialog :deep(.el-dialog__header) { border-color: #263352; background: radial-gradient(circle, rgba(96,165,250,.11) 1px, transparent 1.2px) 0 0 / 13px 13px, #111a2b; }
.dark .evo-detail-dialog :deep(.el-dialog__body) { background: #0b1220; color: #cbd5e1; }
.dark .evo-detail-dialog :deep(.el-dialog__footer) { background: #0f172a; }
.evo-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.evo-modal-title-group { display: flex; align-items: center; gap: 10px; min-width: 0; }
.evo-modal-title-icon { display: grid; place-items: center; width: 30px; height: 30px; border-radius: 10px; background: linear-gradient(135deg, #6d4aff, #3b82f6); color: white; box-shadow: 0 5px 12px rgba(79,70,229,.22); }
.evo-modal-title { margin: 0; color: #17233b; font-size: 13px; font-weight: 850; }
.dark .evo-modal-title { color: #e2e8f0; }
.evo-modal-subtitle { margin: 3px 0 0; color: #8493aa; font-size: 9.5px; }
.evo-modal-actions { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
.evo-modal-status { padding: 4px 9px; border-radius: 999px; background: #e0f2fe; color: #0369a1; font-size: 9px; font-weight: 800; }
.evo-modal-status.status-pendiente { background: #fef3c7; color: #b45309; }
.evo-modal-status.status-pendiente_cama { background: #ffedd5; color: #c2410c; }
.evo-modal-status.status-en_espera { background: #dbeafe; color: #1d4ed8; }
.dark .evo-modal-status { background: #164e63; color: #a5f3fc; }
.dark .evo-modal-status.status-pendiente { background: #422f0c; color: #fbbf24; }
.dark .evo-modal-status.status-pendiente_cama { background: #431407; color: #fdba74; }
.dark .evo-modal-status.status-en_espera { background: #1e3a5f; color: #93c5fd; }
.evo-detail-close {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  color: #64748b;
  background: #fff;
  cursor: pointer;
}
.evo-detail-close:hover { background: #f1f5f9; color: #1e293b; }
.dark .evo-detail-close { border-color: #334155; background: #172033; color: #94a3b8; }
.evo-modal-body { display: flex; flex-direction: column; gap: 7px; }
.evo-detail-top { display: grid; grid-template-columns: 1fr 1fr .95fr; gap: 7px; align-items: stretch; }
.evo-detail-bottom { display: grid; grid-template-columns: 1.7fr .9fr; gap: 7px; align-items: stretch; }
.evo-detail-side { display: flex; flex-direction: column; gap: 7px; min-width: 0; }
.evo-panel { min-width: 0; padding: 8px 9px; border: 1px solid #e2e9f4; border-radius: 12px; background: #fff; box-shadow: 0 2px 5px rgba(20,48,88,.025); }
.dark .evo-panel { border-color: #263352; background: #121c2d; box-shadow: none; }
.evo-panel-blue { --panel: #2563eb; --panel-soft: #eff6ff; --panel-border: #dbeafe; }
.evo-panel-amber { --panel: #d97706; --panel-soft: #fffbeb; --panel-border: #fde68a; }
.evo-panel-violet { --panel: #7c3aed; --panel-soft: #f5f3ff; --panel-border: #ddd6fe; }
.evo-panel-slate { --panel: #475569; --panel-soft: #f1f5f9; --panel-border: #e2e8f0; }
.evo-panel-red { --panel: #e11d48; --panel-soft: #fff1f2; --panel-border: #fecdd3; }
.evo-panel-title { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; color: var(--panel); font-size: 9.5px; font-weight: 850; }
.evo-panel-icon { display: grid; place-items: center; width: 20px; height: 20px; border-radius: 6px; background: var(--panel-soft); color: var(--panel); }
.dark .evo-panel-icon { background: color-mix(in srgb, var(--panel) 17%, #121c2d); }
.evo-info-rows { display: flex; flex-direction: column; gap: 5px; }
.evo-info-row { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; min-width: 0; }
.evo-info-row span { flex: 0 0 39%; color: #94a3b8; font-size: 7.5px; font-weight: 800; letter-spacing: .035em; text-transform: uppercase; }
.evo-info-row strong { min-width: 0; flex: 1; color: #334155; font-size: 8.5px; font-weight: 700; line-height: 1.25; text-align: right; overflow-wrap: anywhere; }
.dark .evo-info-row strong { color: #d5deea; }
.evo-panel-violet .evo-diagnosis { background: #faf8ff; border-color: #ede9fe; }
.dark .evo-panel-violet .evo-diagnosis { background: #1b1730; border-color: #3b2c64; }
.evo-diagnosis-list { display: flex; flex-direction: column; gap: 6px; }
.evo-diagnosis { display: flex; align-items: flex-start; gap: 7px; padding: 7px; border: 1px solid #edf1f7; border-radius: 9px; color: #475569; font-size: 9px; line-height: 1.4; }
.dark .evo-diagnosis { border-color: #263352; color: #cbd5e1; }
.evo-diagnosis-code { flex-shrink: 0; padding: 2px 5px; border-radius: 5px; background: #ede9fe; color: #6d28d9; font-weight: 850; }
.dark .evo-diagnosis-code { color: #93c5fd; }
.dark .evo-diagnosis-code { background: #31245a; color: #c4b5fd; }
.evo-diagnosis-description { min-width: 0; }
.evo-principal-chip { flex-shrink: 0; margin-left: auto; padding: 2px 6px; border-radius: 999px; background: #eff6ff; color: #2563eb; font-size: 7px; font-weight: 800; }
.evo-detail-paragraph { margin: 0; padding: 9px; border-radius: 9px; background: #f8fafc; color: #64748b; font-size: 9px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
.dark .evo-detail-paragraph { background: #0f172a; color: #cbd5e1; }
.evo-history-copy { max-height: 148px; overflow-y: auto; padding: 8px; border-radius: 9px; background: #f5f9ff; color: #53647d; font-size: 8px; line-height: 1.45; white-space: pre-wrap; overflow-wrap: anywhere; }
.dark .evo-history-copy { background: #0d1728; color: #b6c3d7; }
.evo-extra-info { display: flex; flex-direction: column; gap: 4px; margin-top: 6px; padding: 7px 8px; border-radius: 9px; background: #fff7ed; color: #9a3412; font-size: 8px; line-height: 1.35; }
.dark .evo-extra-info { background: #2a1b16; color: #fdba74; }
.evo-tracking-row { display: flex; gap: 8px; position: relative; padding: 2px 0 10px; }
.evo-tracking-row + .evo-tracking-row { padding-bottom: 4px; }
.evo-tracking-row:first-of-type::after { content: ''; position: absolute; left: 8px; top: 20px; bottom: -1px; width: 1px; background: #dbe5f1; }
.dark .evo-tracking-row:first-of-type::after { background: #334155; }
.evo-tracking-dot { z-index: 1; display: grid; place-items: center; width: 17px; height: 17px; flex-shrink: 0; border-radius: 50%; }
.evo-tracking-dot.done { background: #dbeafe; color: #2563eb; }
.evo-tracking-dot.current { background: #fef3c7; color: #d97706; }
.evo-tracking-row strong, .evo-tracking-row small { display: block; }
.evo-tracking-row strong { color: #334155; font-size: 9px; font-weight: 800; }
.evo-tracking-row small { margin-top: 2px; color: #94a3b8; font-size: 8px; }
.dark .evo-tracking-row strong { color: #dbe4f0; }
.evo-arrival-chip { margin-top: 7px; padding: 6px 7px; border-radius: 7px; background: #ecfdf5; color: #047857; font-size: 8px; font-weight: 750; }
.dark .evo-arrival-chip { background: #102b28; color: #6ee7b7; }
.evo-count { margin-left: auto; min-width: 17px; padding: 2px 5px; border-radius: 999px; background: var(--panel-soft); text-align: center; font-size: 8px; }
.evo-attachments { display: flex; flex-direction: column; gap: 5px; }
.evo-attachment { display: flex; align-items: center; gap: 7px; min-width: 0; padding: 5px 6px; border: 1px solid #f1f5f9; border-radius: 8px; }
.dark .evo-attachment { border-color: #263352; }
.evo-attachment-type { display: grid; place-items: center; min-width: 24px; height: 23px; padding: 0 3px; border-radius: 6px; background: #ffe4e6; color: #e11d48; font-size: 6px; font-weight: 900; }
.evo-attachment-name { min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #475569; font-size: 8px; font-weight: 700; }
.evo-attachment small { flex-shrink: 0; color: #94a3b8; font-size: 7px; }
.dark .evo-attachment-name { color: #cbd5e1; }
.evo-no-attachments { margin: 0; color: #94a3b8; font-size: 8px; font-style: italic; }
.evo-modal-footer { display: flex; align-items: center; justify-content: space-between; }
.evo-modal-footer > span { display: inline-flex; align-items: center; gap: 5px; color: #94a3b8; font-size: 8px; }
.evo-detail-footer-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 29px; padding: 0 12px; border: 0; border-radius: 8px; background: linear-gradient(135deg, #2563eb, #16468e); color: #fff; font-size: 8.5px; font-weight: 850; cursor: pointer; box-shadow: 0 4px 12px rgba(37,99,235,.2); }
.evo-detail-footer-btn:hover { filter: brightness(1.06); transform: translateY(-1px); }

@media (max-width: 900px) {
  .evo-detail-top { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .evo-panel-violet { grid-column: 1 / -1; }
  .evo-detail-bottom { grid-template-columns: 1.3fr 1fr; }
}

.skeleton-circle {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  flex-shrink: 0;
}
.skeleton-bar, .skeleton-circle {
  background: linear-gradient(90deg, #eef2f9 25%, #e2e8f0 50%, #eef2f9 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.4s ease-in-out infinite;
  border-radius: 6px;
}
.dark .skeleton-bar, .dark .skeleton-circle {
  background: linear-gradient(90deg, #1e293b 25%, #263352 50%, #1e293b 75%);
  background-size: 200% 100%;
}
@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 640px) {
  .evo-quick-facts { grid-template-columns: 1fr; }
  .evo-quick-fact + .evo-quick-fact { border-left: 0; border-top: 1px solid #edf1f7; }
  .dark .evo-quick-fact + .evo-quick-fact { border-color: #263352; }
  .evo-card-head { align-items: flex-start; }
  .evo-card-head > div:nth-child(3) { width: 100%; justify-content: flex-end; flex-wrap: wrap; }
  .evo-detail-dialog :deep(.el-dialog__header) { padding: 15px; }
  .evo-detail-dialog :deep(.el-dialog__body) { padding: 12px 14px 6px; }
  .evo-detail-dialog :deep(.el-dialog__footer) { padding: 0 14px 14px; }
  .evo-modal-header { align-items: flex-start; }
  .evo-modal-actions { justify-content: flex-end; }
  .evo-detail-top, .evo-detail-bottom { grid-template-columns: 1fr; }
  .evo-panel-violet { grid-column: auto; }
}

@media (max-width: 420px) {
  .evo-modal-header { flex-direction: column; }
  .evo-modal-actions { width: 100%; justify-content: flex-start; }
  .evo-modal-actions .evo-detail-close { margin-left: auto; }
}
</style>
