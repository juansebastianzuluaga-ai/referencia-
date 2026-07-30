<template>
  <div class="solicitud-page h-full flex flex-col overflow-hidden">

    <!-- Header -->
    <div class="sp-header"
      v-motion
      :initial="{ opacity: 0, y: 20 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 500, ease: 'easeOut' } }">
      <div class="sp-header-left">
        <button class="sp-back-btn" @click="router.push('/clinica/dashboard')">
          <component :is="ArrowLeftIcon" class="w-4 h-4" />
        </button>
        <div>
          <h1 class="sp-title">Nueva solicitud de referencia</h1>
          <p class="sp-subtitle">Complete el formulario para enviar una nueva solicitud</p>
        </div>
      </div>
    </div>

    <!-- Stepper -->
    <div class="rf-stepper">
      <div v-for="step in formSteps" :key="step.number" class="rf-step" :class="{ active: pasoFormulario >= step.number, current: pasoFormulario === step.number }">
        <span class="rf-step-num">{{ pasoFormulario > step.number ? '✓' : step.number }}</span>
        <span class="rf-step-label">{{ step.label }}</span>
      </div>
    </div>

    <!-- Body: 2 columnas -->
    <div class="rf-body">
      <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default" class="rf-form-col">

        <!-- PASO 1: Datos del paciente -->
        <div v-show="pasoFormulario === 1" class="rf-section">
          <div class="rf-section-header">
            <component :is="UserIcon" class="w-4 h-4" />
            <h3>Datos del paciente</h3>
          </div>
          <div class="rf-grid">
            <el-form-item label="Tipo de documento" prop="tipo_documento" required>
              <el-select v-model="form.tipo_documento" class="w-full" placeholder="Tipo">
                <el-option v-for="t in TIPOS_DOCUMENTO" :key="t" :label="t" :value="t" />
              </el-select>
            </el-form-item>
            <el-form-item label="Número de documento" prop="numero_documento" required>
              <el-input v-model="form.numero_documento" autocomplete="off" />
            </el-form-item>
            <el-form-item label="Primer nombre" prop="primer_nombre" required>
              <el-input v-model="form.primer_nombre" autocomplete="off" @input="capitalizar('primer_nombre')" />
            </el-form-item>
            <el-form-item label="Segundo nombre">
              <el-input v-model="form.segundo_nombre" autocomplete="off" @input="capitalizar('segundo_nombre')" />
            </el-form-item>
            <el-form-item label="Primer apellido" prop="primer_apellido" required>
              <el-input v-model="form.primer_apellido" autocomplete="off" @input="capitalizar('primer_apellido')" />
            </el-form-item>
            <el-form-item label="Segundo apellido">
              <el-input v-model="form.segundo_apellido" autocomplete="off" @input="capitalizar('segundo_apellido')" />
            </el-form-item>
            <el-form-item label="Edad" prop="edad" required>
              <el-input-number v-model="form.edad" :min="0" :max="120" class="w-full" controls-position="right" />
            </el-form-item>
            <el-form-item label="Género" prop="genero" required>
              <el-select v-model="form.genero" class="w-full" placeholder="Seleccione">
                <el-option label="Masculino" value="M" />
                <el-option label="Femenino" value="F" />
              </el-select>
            </el-form-item>
            <el-form-item label="Municipio" prop="municipio_capita" required>
              <el-input v-model="form.municipio_capita" autocomplete="off" @input="capitalizar('municipio_capita')" />
            </el-form-item>
            <el-form-item label="EPS / Aseguradora" prop="eps" required class="rf-col-2">
              <el-select v-model="form.eps" filterable class="w-full" placeholder="Seleccione o escriba">
                <el-option v-for="e in EPS_LIST" :key="e" :label="e" :value="e" />
              </el-select>
            </el-form-item>
          </div>
        </div>

        <!-- PASO 2: Datos de la remisión -->
        <div v-show="pasoFormulario === 2" class="rf-section">
          <div class="rf-section-header">
            <component :is="StethoscopeIcon" class="w-4 h-4" />
            <h3>Datos de la remisión</h3>
          </div>

          <!-- Diagnósticos -->
          <div class="rf-dx-block">
            <div class="rf-dx-header">
              <label class="rf-dx-title">Diagnósticos (CIE-10) <span class="rf-req">*</span></label>
              <el-button size="small" plain @click="agregarDiagnostico" class="rf-dx-add">
                <component :is="PlusIcon" class="w-3 h-3 mr-1" /> Agregar
              </el-button>
            </div>
            <div v-for="(dx, i) in diagnosticos" :key="i" class="rf-dx-row">
              <div class="rf-dx-code">
                <el-select
                  v-model="dx.codigo_cie10"
                  filterable
                  class="w-full"
                  placeholder="Código CIE-10"
                  :filter-method="filtrarCIE10"
                  @change="(val: string) => onDxSelect(i, val)"
                >
                  <el-option key="__otro_dx" label="➕ Otro..." value="__otro_dx" />
                  <el-option v-for="item in cie10Filtrados" :key="item.codigo" :label="item.codigo" :value="item.codigo" />
                </el-select>
              </div>
              <div class="rf-dx-desc">
                <el-input v-model="dx.descripcion" placeholder="Descripción del diagnóstico" />
              </div>
              <button v-if="diagnosticos.length > 1" type="button" class="rf-dx-remove" @click="quitarDiagnostico(i)">
                <component :is="XIcon" class="w-3.5 h-3.5" />
              </button>
            </div>
            <p v-if="diagnosticos.length === 0" class="rf-dx-empty">Agregue al menos un diagnóstico.</p>
          </div>

          <div class="rf-grid rf-mt">
            <el-form-item label="Especialidad requerida" prop="especialidad_requerida" required>
              <el-select v-model="form.especialidad_requerida" filterable class="w-full" placeholder="Seleccione" @change="onEspecialidadSelect">
                <el-option key="__otra_esp" label="➕ Otra especialidad..." value="__otra_esp" />
                <el-option v-for="e in ESPECIALIDADES" :key="e" :label="e" :value="e" />
              </el-select>
            </el-form-item>
            <el-form-item label="Servicio / Ubicación actual" prop="servicio_ubicacion_actual" required>
              <el-select v-model="form.servicio_ubicacion_actual" class="w-full" placeholder="Seleccione">
                <el-option v-for="s in SERVICIOS" :key="s" :label="s" :value="s" />
              </el-select>
            </el-form-item>
            <el-form-item label="Servicio al que se remite">
              <el-select v-model="form.servicio_remision" class="w-full" placeholder="Seleccione" clearable>
                <el-option v-for="s in SERVICIOS" :key="s" :label="s" :value="s" />
              </el-select>
            </el-form-item>
            <el-form-item label="Vía de contacto">
              <el-select v-model="form.via_contacto" class="w-full" placeholder="Seleccione" clearable>
                <el-option label="Email" value="EMAIL" />
                <el-option label="Telefónica" value="TELEFONICA" />
                <el-option label="N/A" value="N/A" />
              </el-select>
            </el-form-item>
            <el-form-item label="¿Paciente gestante?">
              <el-select v-model="form.gestante" class="w-full" placeholder="Seleccione" clearable>
                <el-option label="Sí" :value="true" />
                <el-option label="No" :value="false" />
              </el-select>
            </el-form-item>
            <el-form-item label="Condición especial" class="rf-col-3">
              <el-input v-model="form.condicion_especial" placeholder="Ej: discapacidad, obesidad mórbida..." autocomplete="off" @input="capitalizar('condicion_especial')" />
            </el-form-item>
            <el-form-item label="Historia clínica" prop="resumen_historia_clinica" required class="rf-col-3">
              <el-input v-model="form.resumen_historia_clinica" type="textarea" :rows="2"
                placeholder="Motivo de remisión, antecedentes, estado actual..." @input="capitalizar('resumen_historia_clinica')" />
            </el-form-item>
            <div class="rf-attach rf-col-1"
              :class="{ 'rf-attach-active': dragOver }"
              @dragover.prevent="dragOver = true"
              @dragleave.prevent="dragOver = false"
              @drop.prevent="onDrop"
            >
              <div class="rf-attach-header">
                <component :is="PaperclipIcon" class="w-3.5 h-3.5" />
                <span>Adjuntar soportes</span>
                <span v-if="adjuntos.length" class="rf-attach-count">{{ adjuntos.length }}/10</span>
              </div>
              <div class="rf-dropzone" @click="fileInput?.click()">
                <component :is="UploadCloudIcon" class="w-5 h-5" />
                <p>Clic o arrastre aquí</p>
                <small>PDF/img/Word · 10MB c/u</small>
                <input ref="fileInput" class="hidden" type="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar" multiple @change="seleccionarAdjuntos" />
              </div>
              <transition-group v-if="adjuntos.length" name="file-list" tag="div" class="rf-file-list">
                <div v-for="(archivo, i) in adjuntos" :key="archivo.name + i" class="rf-file-chip">
                  <span class="rf-file-badge" :class="getFileTypeClass(archivo)">{{ getFileTypeLabel(archivo) }}</span>
                  <span class="rf-file-name">{{ archivo.name }}</span>
                  <button type="button" class="rf-file-remove" @click.stop="removerAdjunto(i)">
                    <component :is="XCircleIcon" class="w-3 h-3" />
                  </button>
                </div>
              </transition-group>
            </div>
          </div>

          <!-- Quien remite -->
          <div class="rf-subsection">
            <div class="rf-subsection-header">
              <component :is="UserCheckIcon" class="w-3.5 h-3.5" />
              <h4>Datos de quien remite</h4>
            </div>
            <div class="rf-grid">
              <el-form-item label="Quien remite" prop="quien_remitente" class="rf-col-2">
                <el-input v-model="form.quien_remitente" placeholder="Nombre de quien remite" @input="capitalizar('quien_remitente')" />
              </el-form-item>
              <el-form-item label="Teléfono de contacto" prop="telefono_contacto">
                <el-input v-model="form.telefono_contacto" placeholder="Teléfono de contacto" />
              </el-form-item>
              <el-form-item label="Correo de contacto" prop="correo_contacto">
                <el-input v-model="form.correo_contacto" placeholder="correo@ejemplo.com" />
              </el-form-item>
            </div>
          </div>
        </div>

      </el-form>

      <!-- Panel lateral: Resumen en tiempo real -->
      <aside class="rf-summary-panel">
        <div class="rf-summary-panel-header">
          <component :is="FileCheckIcon" class="w-4 h-4" />
          <span>Vista previa</span>
        </div>
        <div class="rf-summary-panel-body">
          <div class="rf-sp-block">
            <span class="rf-sp-label">Paciente</span>
            <strong class="rf-sp-value">{{ nombreCompleto }}</strong>
            <span class="rf-sp-sub">{{ form.tipo_documento }} {{ form.numero_documento }} · {{ form.edad || '—' }} años · {{ form.genero === 'M' ? 'Masc.' : form.genero === 'F' ? 'Fem.' : '—' }}</span>
          </div>
          <div class="rf-sp-block">
            <span class="rf-sp-label">EPS / Municipio</span>
            <strong class="rf-sp-value">{{ form.eps || '—' }}</strong>
            <span class="rf-sp-sub">{{ form.municipio_capita || '—' }}</span>
          </div>
          <div class="rf-sp-block">
            <span class="rf-sp-label">Diagnósticos</span>
            <div v-if="diagnosticos.filter(d => d.codigo_cie10 || d.descripcion).length" class="rf-sp-dx-list">
              <div v-for="(dx, i) in diagnosticos.filter(d => d.codigo_cie10 || d.descripcion)" :key="i" class="rf-sp-dx-item">
                <span class="rf-sp-dx-code">{{ dx.codigo_cie10 }}</span>
                <span class="rf-sp-dx-desc">{{ dx.descripcion }}</span>
              </div>
            </div>
            <span v-else class="rf-sp-empty">Sin diagnósticos</span>
          </div>
          <div class="rf-sp-block">
            <span class="rf-sp-label">Especialidad</span>
            <strong class="rf-sp-value">{{ form.especialidad_requerida || '—' }}</strong>
            <span class="rf-sp-sub">{{ form.servicio_ubicacion_actual || '—' }} → {{ form.servicio_remision || '—' }}</span>
          </div>
          <div class="rf-sp-block">
            <span class="rf-sp-label">Quien remite</span>
            <strong class="rf-sp-value">{{ form.quien_remitente || '—' }}</strong>
            <span class="rf-sp-sub">{{ form.telefono_contacto || '—' }} · {{ form.correo_contacto || '—' }}</span>
          </div>
          <div class="rf-sp-block">
            <span class="rf-sp-label">Adjuntos</span>
            <strong class="rf-sp-value">{{ adjuntos.length }} archivo(s)</strong>
          </div>
        </div>
      </aside>
    </div>

    <!-- Footer -->
    <div class="rf-footer">
      <el-button class="rf-btn-back" @click="pasoFormulario === 1 ? router.push('/clinica/dashboard') : pasoFormulario--">
        {{ pasoFormulario === 1 ? 'Cancelar' : 'Anterior' }}
      </el-button>
      <el-button v-if="pasoFormulario < 2" type="primary" class="rf-btn-next" @click="avanzarPaso">Continuar</el-button>
      <el-button v-else type="primary" class="rf-btn-submit" @click="abrirConfirmacion">Enviar solicitud</el-button>
    </div>

    <!-- Dialog de confirmación -->
    <el-dialog
      v-model="showConfirmResumen"
      title="Confirmar solicitud"
      width="620px"
      class="confirm-dialog"
      :close-on-click-modal="false"
      append-to-body
      align-center
    >
      <div class="confirm-body">
        <div class="confirm-icon-wrap">
          <component :is="FileCheckIcon" class="w-8 h-8" />
        </div>
        <h3 class="confirm-title">¿Confirmar envío de la solicitud?</h3>
        <p class="confirm-subtitle">Revise los datos antes de enviar</p>

        <div class="confirm-grid">
          <div class="confirm-item">
            <span>Paciente</span>
            <strong>{{ nombreCompleto }}</strong>
          </div>
          <div class="confirm-item">
            <span>Documento</span>
            <strong>{{ form.tipo_documento }} {{ form.numero_documento }}</strong>
          </div>
          <div class="confirm-item">
            <span>Edad / Género</span>
            <strong>{{ form.edad }} años · {{ form.genero === 'M' ? 'Masc.' : 'Fem.' }}</strong>
          </div>
          <div class="confirm-item">
            <span>EPS</span>
            <strong>{{ form.eps || '—' }}</strong>
          </div>
          <div class="confirm-item">
            <span>Especialidad</span>
            <strong>{{ form.especialidad_requerida || '—' }}</strong>
          </div>
          <div class="confirm-item">
            <span>Servicio actual</span>
            <strong>{{ form.servicio_ubicacion_actual || '—' }}</strong>
          </div>
          <div class="confirm-item confirm-item-full">
            <span>Diagnósticos</span>
            <div v-if="diagnosticos.filter(d => d.codigo_cie10 || d.descripcion).length" class="confirm-dx-list">
              <div v-for="(dx, i) in diagnosticos.filter(d => d.codigo_cie10 || d.descripcion)" :key="i" class="confirm-dx-item">
                <span class="confirm-dx-code">{{ dx.codigo_cie10 }}</span>
                <span class="confirm-dx-desc">{{ dx.descripcion }}</span>
              </div>
            </div>
            <strong v-else>—</strong>
          </div>
          <div class="confirm-item confirm-item-full">
            <span>Historia clínica</span>
            <strong>{{ form.resumen_historia_clinica || '—' }}</strong>
          </div>
          <div class="confirm-item">
            <span>Quien remite</span>
            <strong>{{ form.quien_remitente || '—' }}</strong>
          </div>
          <div class="confirm-item">
            <span>Adjuntos</span>
            <strong>{{ adjuntos.length }} archivo(s)</strong>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="confirm-footer">
          <el-button class="rf-btn-back" @click="showConfirmResumen = false">Cancelar</el-button>
          <el-button type="primary" class="rf-btn-submit" :loading="guardando" @click="guardar">Confirmar y enviar</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Modal éxito -->
    <el-dialog v-model="modalExito" width="480px" class="exito-dialog" :show-close="false" align-center @close="onCerrarExito">
      <div class="exito-content">
        <div class="exito-icon-circle">
          <component :is="CheckCircleIcon" class="w-10 h-10" />
        </div>
        <h2 class="exito-title">¡Solicitud enviada!</h2>
        <p class="exito-subtitle">Su solicitud de referencia ha sido registrada correctamente</p>
        <div class="exito-details">
          <div class="exito-detail-row"><span>Paciente</span><strong>{{ ultimoEnviado.paciente }}</strong></div>
          <div class="exito-detail-row"><span>Documento</span><strong>{{ ultimoEnviado.documento }}</strong></div>
          <div class="exito-detail-row"><span>Especialidad</span><strong>{{ ultimoEnviado.especialidad }}</strong></div>
          <div class="exito-detail-row"><span>Diagnósticos</span><strong style="white-space: pre-line;">{{ ultimoEnviado.diagnosticos }}</strong></div>
          <div class="exito-detail-row"><span>Servicio actual</span><strong>{{ ultimoEnviado.servicio }}</strong></div>
          <div class="exito-detail-row" v-if="ultimoEnviado.adjuntos"><span>Adjuntos</span><strong>{{ ultimoEnviado.adjuntos }}</strong></div>
        </div>
        <el-button type="primary" class="exito-btn !bg-[#0D2D6B] w-full" @click="modalExito = false">Entendido</el-button>
      </div>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus as PlusIcon,
  X as XIcon,
  XCircle as XCircleIcon,
  CheckCircle as CheckCircleIcon,
  Paperclip as PaperclipIcon,
  UploadCloud as UploadCloudIcon,
  Stethoscope as StethoscopeIcon,
  User as UserIcon,
  UserCheck as UserCheckIcon,
  FileCheck as FileCheckIcon,
  ArrowLeft as ArrowLeftIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import { TIPOS_DOCUMENTO, EPS_LIST, ESPECIALIDADES, SERVICIOS } from '@/data/referencia';
import { CIE10 } from '@/data/cie10';

const router = useRouter();
const guardando = ref(false);
const showConfirmResumen = ref(false);
const formRef = ref();
const adjuntos = ref<File[]>([]);
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const pasoFormulario = ref(1);
const cie10Filtrados = ref(CIE10.slice(0, 20));

interface DiagnosticoItem {
  codigo_cie10: string;
  descripcion: string;
}

const diagnosticos = ref<DiagnosticoItem[]>([{ codigo_cie10: '', descripcion: '' }]);

function agregarDiagnostico() {
  diagnosticos.value.push({ codigo_cie10: '', descripcion: '' });
}

function quitarDiagnostico(i: number) {
  diagnosticos.value.splice(i, 1);
}

async function onDxSelect(i: number, val: string) {
  if (val === '__otro_dx') {
    diagnosticos.value[i].codigo_cie10 = '';
    try {
      const { value } = await ElMessageBox.prompt('Escriba el código CIE-10', 'Nuevo diagnóstico', {
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Cancelar',
        inputPlaceholder: 'Ej: J18.9',
        inputValidator: (v) => v?.trim() ? true : 'El código es requerido',
      });
      diagnosticos.value[i].codigo_cie10 = value.trim();
    } catch {
      diagnosticos.value[i].codigo_cie10 = '';
    }
    return;
  }
  if (!val) return;
  const match = CIE10.find(c => c.codigo === val);
  if (match) {
    diagnosticos.value[i].codigo_cie10 = match.codigo;
    if (!diagnosticos.value[i].descripcion) {
      diagnosticos.value[i].descripcion = match.descripcion;
    }
  }
}

function filtrarCIE10(query: string) {
  if (!query) {
    cie10Filtrados.value = CIE10.slice(0, 20);
    return;
  }
  const q = query.toLowerCase().trim();
  cie10Filtrados.value = CIE10.filter(
    c => c.codigo.toLowerCase().includes(q) || c.descripcion.toLowerCase().includes(q),
  ).slice(0, 30);
}

async function onEspecialidadSelect(val: string) {
  if (val === '__otra_esp') {
    form.value.especialidad_requerida = '';
    try {
      const { value } = await ElMessageBox.prompt('Escriba el nombre de la especialidad', 'Nueva especialidad', {
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        inputPlaceholder: 'Ej: Cardiología pediátrica',
        inputValidator: (v) => v?.trim() ? true : 'El nombre es requerido',
      });
      form.value.especialidad_requerida = value.trim();
    } catch {
      form.value.especialidad_requerida = '';
    }
    return;
  }
}

const formSteps = [
  { number: 1, label: 'Paciente' },
  { number: 2, label: 'Remisión' },
];

function emptyForm() {
  return {
    primer_nombre: '', segundo_nombre: '', primer_apellido: '', segundo_apellido: '',
    genero: '', edad: null as number | null, tipo_documento: '', numero_documento: '',
    eps: '', municipio_capita: '', especialidad_requerida: '',
    servicio_ubicacion_actual: '', servicio_remision: '', quien_remitente: '', telefono_contacto: '', correo_contacto: '', via_contacto: '',
    gestante: null as boolean | null, condicion_especial: '',
    resumen_historia_clinica: '', observaciones: '',
  };
}

const form = ref(emptyForm());

function capitalizar(campo: 'primer_nombre' | 'segundo_nombre' | 'primer_apellido' | 'segundo_apellido' | 'municipio_capita' | 'resumen_historia_clinica' | 'condicion_especial' | 'quien_remitente' | 'observaciones') {
  const val = form.value[campo];
  if (val && val.length === 1) {
    form.value[campo] = val.charAt(0).toUpperCase();
  } else if (val && val.length > 1) {
    form.value[campo] = val.charAt(0).toUpperCase() + val.slice(1);
  }
}

const nombreCompleto = computed(() =>
  [form.value.primer_nombre, form.value.segundo_nombre, form.value.primer_apellido, form.value.segundo_apellido]
    .filter(Boolean)
    .join(' ') || '—'
);

const rules = {
  primer_nombre: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  primer_apellido: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  genero: [{ required: true, message: 'Requerido', trigger: 'change' }],
  edad: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  tipo_documento: [{ required: true, message: 'Requerido', trigger: 'change' }],
  numero_documento: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  eps: [{ required: true, message: 'Requerido', trigger: 'change' }],
  municipio_capita: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  especialidad_requerida: [{ required: true, message: 'Requerido', trigger: 'change' }],
  servicio_ubicacion_actual: [{ required: true, message: 'Requerido', trigger: 'change' }],
  resumen_historia_clinica: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  correo_contacto: [{ type: 'email', message: 'Correo no válido', trigger: 'blur' }],
};

function seleccionarAdjuntos(event: Event) {
  const files = Array.from((event.target as HTMLInputElement).files ?? []);
  agregarAdjuntos(files);
  if (fileInput.value) fileInput.value.value = '';
}

function onDrop(event: DragEvent) {
  dragOver.value = false;
  const files = Array.from(event.dataTransfer?.files ?? []);
  agregarAdjuntos(files);
}

function agregarAdjuntos(files: File[]) {
  const restantes = 10 - adjuntos.value.length;
  if (restantes <= 0) {
    ElMessage.warning('Máximo 10 archivos permitidos');
    return;
  }
  const nuevos = files.slice(0, restantes);
  if (files.length > restantes) {
    ElMessage.warning(`Solo se agregaron ${restantes} de ${files.length} archivos (límite 10)`);
  }
  adjuntos.value = [...adjuntos.value, ...nuevos];
}

function removerAdjunto(index: number) {
  adjuntos.value.splice(index, 1);
}

function getFileTypeLabel(file: File): string {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
  if (['pdf'].includes(ext)) return 'PDF';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'].includes(ext)) return 'IMG';
  if (['doc', 'docx'].includes(ext)) return 'DOC';
  if (['xls', 'xlsx', 'csv'].includes(ext)) return 'XLS';
  if (['ppt', 'pptx'].includes(ext)) return 'PPT';
  if (['zip', 'rar'].includes(ext)) return 'ZIP';
  if (['txt'].includes(ext)) return 'TXT';
  return ext.toUpperCase().slice(0, 3);
}

function getFileTypeClass(file: File): string {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
  if (['pdf'].includes(ext)) return 'ft-pdf';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'].includes(ext)) return 'ft-img';
  if (['doc', 'docx'].includes(ext)) return 'ft-doc';
  if (['xls', 'xlsx', 'csv'].includes(ext)) return 'ft-xls';
  if (['ppt', 'pptx'].includes(ext)) return 'ft-ppt';
  if (['zip', 'rar'].includes(ext)) return 'ft-zip';
  return 'ft-default';
}

async function avanzarPaso(): Promise<void> {
  const camposPorPaso = [
    ['primer_nombre', 'primer_apellido', 'genero', 'edad', 'tipo_documento', 'numero_documento', 'municipio_capita', 'eps'],
    ['resumen_historia_clinica', 'especialidad_requerida', 'servicio_ubicacion_actual'],
  ];

  if (pasoFormulario.value === 2) {
    const validDx = diagnosticos.value.filter(d => d.codigo_cie10.trim() && d.descripcion.trim());
    if (validDx.length === 0) {
      ElMessage.warning('Agregue al menos un diagnóstico con código y descripción');
      return;
    }
  }

  try {
    await formRef.value?.validateField(camposPorPaso[pasoFormulario.value - 1]);
    pasoFormulario.value++;
  } catch {
    ElMessage.warning('Complete los campos requeridos para continuar');
  }
}

async function abrirConfirmacion(): Promise<void> {
  const validDx = diagnosticos.value.filter(d => d.codigo_cie10.trim() && d.descripcion.trim());
  if (validDx.length === 0) {
    ElMessage.warning('Agregue al menos un diagnóstico con código y descripción');
    return;
  }
  try {
    await formRef.value?.validate();
    showConfirmResumen.value = true;
  } catch {
    ElMessage.error('Por favor complete todos los campos requeridos');
  }
}

const modalExito = ref(false);
const ultimoEnviado = ref({
  paciente: '', documento: '', especialidad: '', diagnosticos: '', servicio: '', adjuntos: '',
});

function onCerrarExito() {
  ultimoEnviado.value = { paciente: '', documento: '', especialidad: '', diagnosticos: '', servicio: '', adjuntos: '' };
}

async function guardar() {
  try { await formRef.value?.validate(); }
  catch { ElMessage.error('Por favor complete todos los campos requeridos'); return; }
  guardando.value = true;
  try {
    const now = new Date();
    const payload = new FormData();
    payload.append('fecha', now.toISOString().slice(0, 10));
    payload.append('hora', now.toTimeString().slice(0, 5));
    Object.entries(form.value).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
      }
    });
    const validDx = diagnosticos.value.filter(d => d.codigo_cie10.trim() && d.descripcion.trim());
    validDx.forEach((dx, i) => {
      payload.append(`diagnosticos[${i}][codigo_cie10]`, dx.codigo_cie10.trim());
      payload.append(`diagnosticos[${i}][descripcion]`, dx.descripcion.trim());
    });
    adjuntos.value.forEach((archivo) => payload.append('adjuntos[]', archivo));
    await http.post('/api/externo/solicitudes', payload);
    ultimoEnviado.value = {
      paciente: nombreCompleto.value,
      documento: `${form.value.tipo_documento} ${form.value.numero_documento}`,
      especialidad: form.value.especialidad_requerida || '—',
      diagnosticos: validDx.map(d => `${d.codigo_cie10} — ${d.descripcion}`).join('\n') || '—',
      servicio: form.value.servicio_ubicacion_actual || '—',
      adjuntos: adjuntos.value.length ? `${adjuntos.value.length} archivo(s)` : '',
    };
    showConfirmResumen.value = false;
    modalExito.value = true;
    form.value = emptyForm();
    adjuntos.value = [];
    pasoFormulario.value = 1;
    diagnosticos.value = [{ codigo_cie10: '', descripcion: '' }];
  } catch (e: any) {
    const errors = e.response?.data?.data?.errors || e.response?.data?.errors;
    ElMessage.error(errors ? Object.values(errors).flat().join('\n') : 'Error al enviar la solicitud');
  } finally { guardando.value = false; }
}

onMounted(() => {
  cie10Filtrados.value = CIE10.slice(0, 20);
});
</script>

<style scoped>
/* ── Page header ── */
.solicitud-page {
  padding: 1rem 1.25rem;
  gap: .75rem;
}
.sp-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}
.sp-header-left {
  display: flex;
  align-items: center;
  gap: .75rem;
}
.sp-back-btn {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  border: 1px solid #E2E8F0;
  background: #fff;
  color: #0D2D6B;
  cursor: pointer;
  transition: all .2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
}
.sp-back-btn:hover {
  background: #EFF6FF;
  border-color: #BFDBFE;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .1);
}
.sp-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0D2D6B;
  margin: 0;
  line-height: 1.2;
}
.sp-subtitle {
  font-size: .78rem;
  color: #64748B;
  margin: 2px 0 0;
}

/* ── Stepper ── */
.rf-stepper {
  display: flex;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
}
.rf-step {
  display: flex;
  align-items: center;
  gap: .4rem;
  flex: 1;
  position: relative;
}
.rf-step:not(:last-child)::after {
  content: '';
  flex: 1;
  height: 2px;
  margin: 0 .4rem;
  border-radius: 1px;
  background: #E2E8F0;
  transition: background .3s ease;
}
.rf-step.active:not(:last-child)::after {
  background: linear-gradient(90deg, #0D2D6B, #3B82F6);
}
.rf-step-num {
  display: grid;
  place-items: center;
  width: 1.75rem;
  height: 1.75rem;
  border-radius: 50%;
  background: #F1F5F9;
  color: #94A3B8;
  font-size: .75rem;
  font-weight: 700;
  flex-shrink: 0;
  transition: all .3s ease;
}
.rf-step.active .rf-step-num {
  background: linear-gradient(135deg, #0D2D6B, #3B82F6);
  color: #fff;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .25);
}
.rf-step.current .rf-step-num {
  ring: 2px solid #3B82F6;
}
.rf-step-label {
  font-size: .78rem;
  font-weight: 600;
  color: #94A3B8;
  white-space: nowrap;
}
.rf-step.active .rf-step-label { color: #0D2D6B; }

/* ── Layout 2 columnas ── */
.rf-body {
  display: flex;
  gap: .75rem;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}
.rf-form-col {
  flex: 1;
  min-width: 0;
  overflow-y: auto;
  overflow-x: hidden;
}

/* ── Form overrides ── */
:deep(.rf-form-col .el-form) { display: flex; flex-direction: column; gap: .5rem; }
:deep(.rf-form-col .el-form-item) { margin-bottom: 0; display: flex; flex-direction: column; }
:deep(.rf-form-col .el-form-item__label) {
  color: #475569; font-size: .72rem; font-weight: 600;
  padding-bottom: .15rem; line-height: 1.2;
}
:deep(.rf-form-col .el-form-item__error) { padding-top: 2px; font-size: .62rem; }
:deep(.rf-form-col .el-input__wrapper),
:deep(.rf-form-col .el-select__wrapper),
:deep(.rf-form-col .el-textarea__inner) {
  box-shadow: 0 0 0 1px #CBD5E1 inset;
  border-radius: 8px;
  background: #fff;
  transition: box-shadow .2s ease;
}
:deep(.rf-form-col .el-input__wrapper.is-focus),
:deep(.rf-form-col .el-select__wrapper.is-focus) { box-shadow: 0 0 0 2px #0D2D6B inset; }
:deep(.rf-form-col .el-input__wrapper:hover),
:deep(.rf-form-col .el-select__wrapper:hover) { box-shadow: 0 0 0 1px #3B82F6 inset; }
:deep(.rf-form-col .el-input__wrapper),
:deep(.rf-form-col .el-select__wrapper) { min-height: 36px; }
:deep(.rf-form-col .el-input-number) { width: 100%; }
:deep(.rf-form-col .el-input-number .el-input__wrapper) { min-height: 36px; }
:deep(.rf-form-col .el-textarea__inner) { min-height: 36px !important; border-radius: 8px; }

/* ── Sections ── */
.rf-section {
  background: #fff;
  border: 1px solid #E2E8F0;
  border-top: 3px solid #0D2D6B;
  border-radius: 10px;
  padding: .75rem .9rem;
  box-shadow: 0 2px 12px rgba(13, 45, 107, .06);
}
.rf-section-header {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: .6rem;
  padding-bottom: .4rem;
  border-bottom: 1px solid #F1F5F9;
}
.rf-section-header svg { color: #0D2D6B; }
.rf-section-header h3 {
  font-size: .85rem;
  font-weight: 700;
  color: #0D2D6B;
  margin: 0;
}

/* ── Grid ── */
.rf-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem .75rem;
}
.rf-col-1 { grid-column: span 1; }
.rf-col-2 { grid-column: span 2; }
.rf-col-3 { grid-column: span 3; }
.rf-mt { margin-top: .6rem; }

/* ── Subsection ── */
.rf-subsection {
  margin-top: .6rem;
  padding-top: .5rem;
  border-top: 1px solid #F1F5F9;
}
.rf-subsection-header {
  display: flex;
  align-items: center;
  gap: .35rem;
  margin-bottom: .4rem;
}
.rf-subsection-header svg { color: #0D2D6B; }
.rf-subsection-header h4 {
  font-size: .78rem;
  font-weight: 700;
  color: #0D2D6B;
  margin: 0;
}

/* ── Diagnósticos ── */
.rf-dx-block {
  background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
  border: 1px solid #93C5FD;
  border-radius: 8px;
  padding: .6rem .7rem;
}
.rf-dx-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .35rem;
}
.rf-dx-title {
  font-size: .72rem;
  font-weight: 700;
  color: #1E40AF;
}
.rf-req { color: #DC2626; }
.rf-dx-add {
  border-color: #0D2D6B !important;
  background: #DBEAFE !important;
  color: #0D2D6B !important;
  font-size: .68rem;
  font-weight: 600;
}
.rf-dx-row {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: .3rem;
}
.rf-dx-code { width: 140px; flex-shrink: 0; }
.rf-dx-desc { flex: 1; }
.rf-dx-remove {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid #FCA5A5;
  background: #FEF2F2;
  color: #DC2626;
  cursor: pointer;
  flex-shrink: 0;
  transition: all .2s ease;
}
.rf-dx-remove:hover { background: #DC2626; color: #fff; }
.rf-dx-empty { font-size: .72rem; color: #94A3B8; margin: .3rem 0 0; }

/* ── Adjuntos ── */
.rf-attach {
  border: 1px solid #BFDBFE;
  background: linear-gradient(135deg, #EFF6FF, #F0F9FF);
  border-radius: 8px;
  padding: .6rem;
}
.rf-attach-active {
  border-color: #3B82F6;
  background: linear-gradient(135deg, #DBEAFE, #EFF6FF);
}
.rf-attach-header {
  display: flex;
  align-items: center;
  gap: .35rem;
  margin-bottom: .3rem;
  font-size: .72rem;
  font-weight: 700;
  color: #0D2D6B;
}
.rf-attach-count {
  margin-left: auto;
  background: #0D2D6B;
  color: #fff;
  padding: 1px 8px;
  border-radius: 10px;
  font-size: .62rem;
}
.rf-dropzone {
  border: 2px dashed #60A5FA;
  background: rgba(219, 234, 254, .3);
  border-radius: 8px;
  padding: .75rem;
  text-align: center;
  cursor: pointer;
  transition: all .2s ease;
  color: #2563EB;
}
.rf-dropzone:hover {
  border-color: #2563EB;
  background: rgba(219, 234, 254, .5);
}
.rf-dropzone p { font-size: .72rem; font-weight: 600; margin: .2rem 0 0; color: #1E40AF; }
.rf-dropzone small { font-size: .62rem; color: #3B82F6; }
.rf-file-list { margin-top: .4rem; display: flex; flex-direction: column; gap: .2rem; }
.rf-file-chip {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .5rem;
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 6px;
}
.rf-file-badge {
  font-size: .58rem;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 4px;
  background: #F1F5F9;
  color: #475569;
  flex-shrink: 0;
}
.ft-pdf { background: #FEE2E2; color: #DC2626; }
.ft-img { background: #DBEAFE; color: #2563EB; }
.ft-doc { background: #DBEAFE; color: #1E40AF; }
.ft-xls { background: #DCFCE7; color: #15803D; }
.ft-ppt { background: #FED7AA; color: #C2410C; }
.ft-zip { background: #F3E8FF; color: #7C3AED; }
.ft-default { background: #F1F5F9; color: #475569; }
.rf-file-name {
  flex: 1;
  font-size: .68rem;
  font-weight: 600;
  color: #475569;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.rf-file-remove {
  display: grid;
  place-items: center;
  color: #94A3B8;
  cursor: pointer;
  border: none;
  background: none;
  padding: 2px;
  transition: color .2s ease;
}
.rf-file-remove:hover { color: #DC2626; }

/* ── Panel lateral ── */
.rf-summary-panel {
  width: 280px;
  flex-shrink: 0;
  background: linear-gradient(180deg, #EFF6FF, #F8FAFC);
  border: 1px solid #BFDBFE;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.rf-summary-panel-header {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .6rem .75rem;
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
}
.rf-summary-panel-header svg { color: #fff; }
.rf-summary-panel-body {
  padding: .6rem .75rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.rf-sp-block {
  display: flex;
  flex-direction: column;
  gap: .1rem;
  padding-bottom: .4rem;
  border-bottom: 1px solid #DBEAFE;
}
.rf-sp-block:last-child { border-bottom: none; padding-bottom: 0; }
.rf-sp-label {
  font-size: .62rem;
  font-weight: 700;
  color: #64748B;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.rf-sp-value {
  font-size: .78rem;
  font-weight: 700;
  color: #0D2D6B;
  word-break: break-word;
}
.rf-sp-sub {
  font-size: .68rem;
  color: #3B82F6;
  font-weight: 500;
}
.rf-sp-dx-list { display: flex; flex-direction: column; gap: .2rem; margin-top: .15rem; }
.rf-sp-dx-item {
  display: flex;
  align-items: baseline;
  gap: .3rem;
  padding: .2rem .35rem;
  border-radius: 4px;
  background: rgba(255, 255, 255, .7);
}
.rf-sp-dx-code {
  font-size: .68rem;
  font-weight: 800;
  color: #15803D;
  font-family: monospace;
  flex-shrink: 0;
}
.rf-sp-dx-desc { font-size: .68rem; color: #166534; }
.rf-sp-empty { font-size: .68rem; color: #94A3B8; font-style: italic; }

/* ── Footer ── */
.rf-footer {
  display: flex;
  gap: .5rem;
  flex-shrink: 0;
  padding-top: .5rem;
  border-top: 1px solid #E2E8F0;
}
.rf-btn-back {
  background: #fff !important;
  border-color: #CBD5E1 !important;
  color: #475569 !important;
  font-weight: 600;
  border-radius: 8px;
}
.rf-btn-back:hover {
  color: #0D2D6B !important;
  border-color: #0D2D6B !important;
  background: #EFF6FF !important;
}
.rf-btn-next, .rf-btn-submit {
  background: linear-gradient(135deg, #0D2D6B, #16468E) !important;
  border: none !important;
  color: #fff !important;
  font-weight: 700;
  border-radius: 8px;
  box-shadow: 0 4px 14px rgba(13, 45, 107, .25);
}
.rf-btn-next:hover, .rf-btn-submit:hover {
  background: linear-gradient(135deg, #16468E, #1E5BBF) !important;
  box-shadow: 0 6px 20px rgba(13, 45, 107, .35);
}

/* ── Dialog confirmación ── */
:deep(.confirm-dialog) {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(13, 45, 107, .25);
}
:deep(.confirm-dialog .el-dialog__header) { display: none; }
:deep(.confirm-dialog .el-dialog__body) { padding: 0; }
.confirm-body { padding: 1.5rem 1.5rem 1rem; text-align: center; }
.confirm-icon-wrap {
  display: inline-grid;
  place-items: center;
  width: 56px; height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
  color: #0D2D6B;
  margin-bottom: .75rem;
}
.confirm-title { margin: 0 0 .25rem; font-size: 1.05rem; font-weight: 700; color: #0D2D6B; }
.confirm-subtitle { margin: 0 0 1rem; font-size: .78rem; color: #64748B; }
.confirm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem .75rem; text-align: left; }
.confirm-item {
  display: flex; flex-direction: column; gap: .1rem;
  padding: .4rem .6rem;
  background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px;
}
.confirm-item-full { grid-column: span 2; }
.confirm-item span { font-size: .62rem; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: .03em; }
.confirm-item strong { font-size: .75rem; font-weight: 700; color: #0D2D6B; word-break: break-word; }
.confirm-dx-list { display: flex; flex-direction: column; gap: .2rem; }
.confirm-dx-item {
  display: flex; align-items: baseline; gap: .4rem;
  padding: .2rem .4rem; border-radius: 6px;
  background: #F0FDF4; border: 1px solid #BBF7D0;
}
.confirm-dx-code { font-size: .68rem; font-weight: 800; color: #15803D; font-family: monospace; flex-shrink: 0; }
.confirm-dx-desc { font-size: .68rem; color: #166534; }
.confirm-footer { display: flex; gap: .75rem; padding: 0 1.5rem 1.25rem; }

/* ── Modal éxito ── */
:deep(.exito-dialog) { border-radius: 20px; overflow: hidden; box-shadow: 0 28px 70px rgba(11, 35, 73, .35); }
:deep(.exito-dialog .el-dialog__header) { display: none; }
:deep(.exito-dialog .el-dialog__body) { padding: 0; }
.exito-content {
  padding: 2rem 1.8rem 1.6rem;
  text-align: center;
  background: linear-gradient(180deg, #f0f5ff 0%, #f8faff 40%, #ffffff 100%);
}
.exito-icon-circle {
  display: inline-grid;
  place-items: center;
  width: 64px; height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e, #15803d);
  color: #fff;
  box-shadow: 0 8px 24px rgba(34, 197, 94, .3);
  margin-bottom: 1rem;
}
.exito-title { font-size: 1.2rem; font-weight: 800; color: #0D2D6B; margin: 0 0 .25rem; }
.exito-subtitle { font-size: .82rem; color: #64748B; margin: 0 0 1.2rem; }
.exito-details {
  text-align: left;
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: .8rem 1rem;
  margin-bottom: 1.2rem;
}
.exito-detail-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: .3rem 0;
  border-bottom: 1px solid #F1F5F9;
  font-size: .78rem;
}
.exito-detail-row:last-child { border-bottom: none; }
.exito-detail-row span { color: #94A3B8; font-weight: 600; }
.exito-detail-row strong { color: #0D2D6B; font-weight: 700; text-align: right; }
.exito-btn {
  border-radius: 10px !important;
  font-weight: 700 !important;
  height: 42px !important;
  font-size: .88rem !important;
}

/* ── Responsive ── */
@media (max-width: 900px) {
  .rf-body { flex-direction: column; }
  .rf-summary-panel { width: 100%; max-height: 200px; }
  .rf-grid { grid-template-columns: repeat(2, 1fr); }
  .rf-col-3 { grid-column: span 2; }
}
</style>
