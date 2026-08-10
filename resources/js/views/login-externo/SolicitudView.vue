<template>
  <div class="solicitud-page h-full flex flex-col overflow-hidden">

    <!-- Header compacto -->
    <div class="rf-stepper">
      <button class="rf-stepper-back" @click="router.push('/clinica/dashboard')">
        <component :is="ArrowLeftIcon" class="w-3.5 h-3.5" />
      </button>
      <div class="rf-stepper-title">
        <h2>Nueva solicitud de referencia</h2>
        <p>Complete los datos del paciente y la remisión</p>
      </div>
      <div class="rf-stepper-progress">
        <div class="rf-stepper-progress-track">
          <div class="rf-stepper-progress-bar" :style="{ width: progresoFormulario + '%' }"></div>
        </div>
        <span class="rf-stepper-progress-label">{{ progresoFormulario }}%</span>
      </div>
    </div>

    <!-- Body: 2 columnas -->
    <div class="rf-body">
      <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default" class="rf-form-col">

        <!-- Datos del paciente -->
        <div class="rf-section">
          <div class="rf-section-accent" style="background: linear-gradient(135deg,#2563c4,#3b82f6);"></div>
          <div class="rf-section-header">
            <div class="rf-section-icon" style="background: linear-gradient(135deg,#dbeafe,#bfdbfe); color:#2563c4;">
              <component :is="UserIcon" class="w-4 h-4" />
            </div>
            <div class="rf-section-heading">
              <h3>Datos del paciente</h3>
              <p>Información de identificación del paciente</p>
            </div>
            <div class="rf-section-glow"></div>
          </div>
          <div class="rf-grid">
            <el-form-item label="Tipo de documento" prop="tipo_documento" required :class="['rf-field rf-col-quarter rf-stagger-1', { 'is-success': camposValidos.tipo_documento }]">
              <el-select v-model="form.tipo_documento" class="w-full" placeholder="Tipo" popper-class="rf-select-popper">
                <el-option v-for="t in TIPOS_DOCUMENTO" :key="t" :label="t" :value="t" />
              </el-select>
            </el-form-item>
            <el-form-item label="Número de documento" prop="numero_documento" required :class="['rf-field rf-col-quarter rf-stagger-2', { 'is-success': camposValidos.numero_documento }]">
              <el-input v-model="form.numero_documento" autocomplete="off">
                <template #prefix><component :is="IdCardIcon" class="w-3.5 h-3.5 rf-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item label="Edad" prop="edad" required :class="['rf-field rf-col-quarter rf-stagger-3', { 'is-success': camposValidos.edad }]">
              <el-input-number v-model="form.edad" :min="0" :max="120" class="w-full" controls-position="right" />
            </el-form-item>
            <el-form-item label="Género" prop="genero" required :class="['rf-field rf-col-quarter rf-stagger-4', { 'is-success': camposValidos.genero }]">
              <el-select v-model="form.genero" class="w-full" placeholder="Seleccione" popper-class="rf-select-popper">
                <el-option label="Masculino" value="M" />
                <el-option label="Femenino" value="F" />
              </el-select>
            </el-form-item>
            <el-form-item label="EPS / Aseguradora" prop="eps" required :class="['rf-field rf-col-quarter rf-stagger-5', { 'is-success': camposValidos.eps }]">
              <el-select v-model="form.eps" filterable class="w-full" placeholder="Seleccione o escriba" popper-class="rf-select-popper">
                <el-option v-for="e in EPS_LIST" :key="e" :label="e" :value="e" />
              </el-select>
            </el-form-item>
            <el-form-item label="Primer nombre" prop="primer_nombre" required :class="['rf-field rf-col-quarter rf-stagger-6', { 'is-success': camposValidos.primer_nombre }]">
              <el-input v-model="form.primer_nombre" autocomplete="off" @input="capitalizar('primer_nombre')" />
            </el-form-item>
            <el-form-item label="Segundo nombre" class="rf-field rf-col-quarter rf-stagger-7">
              <el-input v-model="form.segundo_nombre" autocomplete="off" @input="capitalizar('segundo_nombre')" />
            </el-form-item>
            <el-form-item label="Primer apellido" prop="primer_apellido" required :class="['rf-field rf-col-quarter rf-stagger-8', { 'is-success': camposValidos.primer_apellido }]">
              <el-input v-model="form.primer_apellido" autocomplete="off" @input="capitalizar('primer_apellido')" />
            </el-form-item>
            <el-form-item label="Segundo apellido" class="rf-field rf-col-quarter rf-stagger-1">
              <el-input v-model="form.segundo_apellido" autocomplete="off" @input="capitalizar('segundo_apellido')" />
            </el-form-item>
            <el-form-item label="Municipio" prop="municipio_capita" required :class="['rf-field rf-col-quarter rf-stagger-2', { 'is-success': camposValidos.municipio_capita }]">
              <el-input v-model="form.municipio_capita" autocomplete="off" @input="capitalizar('municipio_capita')">
                <template #prefix><component :is="MapPinIcon" class="w-3.5 h-3.5 rf-input-icon" /></template>
              </el-input>
            </el-form-item>
          </div>
        </div>

        <!-- Divisor sutil entre secciones -->
        <div class="rf-section-divider"></div>

        <!-- Datos de la remisión -->
        <div class="rf-section">
          <div class="rf-section-accent" style="background: linear-gradient(135deg,#15966a,#22c55e);"></div>
          <div class="rf-section-header">
            <div class="rf-section-icon" style="background: linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15966a;">
              <component :is="StethoscopeIcon" class="w-4 h-4" />
            </div>
            <div class="rf-section-heading">
              <h3>Datos de la remisión</h3>
              <p>Detalle clínico y destino de la solicitud</p>
            </div>
            <div class="rf-section-glow" style="background: radial-gradient(circle, rgba(21,150,106,.08), transparent 70%);"></div>
          </div>

          <!-- Sub-sección: Destino de la remisión -->
          <div class="rf-subsection rf-subsection-first">
            <div class="rf-subsection-header">
              <div class="rf-subsection-icon rf-sub-icon-blue">
                <component :is="StethoscopeIcon" class="w-3.5 h-3.5" />
              </div>
              <h4>Destino de la remisión</h4>
            </div>
            <div class="rf-grid">
              <el-form-item label="Especialidad requerida" prop="especialidad_requerida" required :class="['rf-field rf-col-2 rf-stagger-1', { 'is-success': camposValidos.especialidad_requerida }]">
                <el-select v-model="form.especialidad_requerida" filterable class="w-full" placeholder="Seleccione" popper-class="rf-select-popper" @change="onEspecialidadSelect">
                  <el-option key="__otra_esp" label="➕ Otra especialidad..." value="__otra_esp" />
                  <el-option v-for="e in ESPECIALIDADES" :key="e" :label="e" :value="e" />
                </el-select>
              </el-form-item>
              <el-form-item label="Servicio / Ubicación actual" prop="servicio_ubicacion_actual" required :class="['rf-field rf-col-quarter rf-stagger-2', { 'is-success': camposValidos.servicio_ubicacion_actual }]">
                <el-select v-model="form.servicio_ubicacion_actual" class="w-full" placeholder="Seleccione" popper-class="rf-select-popper">
                  <el-option v-for="s in SERVICIOS" :key="s" :label="s" :value="s" />
                </el-select>
              </el-form-item>
              <el-form-item label="Servicio al que se remite" class="rf-field rf-col-quarter rf-stagger-3">
                <el-select v-model="form.servicio_remision" class="w-full" placeholder="Seleccione" clearable popper-class="rf-select-popper">
                  <el-option v-for="s in SERVICIOS" :key="s" :label="s" :value="s" />
                </el-select>
              </el-form-item>
              <el-form-item label="Vía de contacto" class="rf-field rf-col-quarter rf-stagger-4">
                <el-select v-model="form.via_contacto" class="w-full" placeholder="Seleccione" clearable popper-class="rf-select-popper">
                  <el-option label="Email" value="EMAIL" />
                  <el-option label="Telefónica" value="TELEFONICA" />
                  <el-option label="N/A" value="N/A" />
                </el-select>
              </el-form-item>
            </div>
          </div>

          <!-- Información clínica + Diagnósticos (una sola fila) -->
          <div class="rf-subsection">
            <div class="rf-grid rf-grid-dx">
              <el-form-item label="¿Paciente gestante?" class="rf-field rf-col-quarter rf-stagger-1">
                <div class="rf-gestante-btns">
                  <button type="button" class="rf-gestante-btn" :class="{ active: form.gestante === true }" @click="form.gestante = true">Sí</button>
                  <button type="button" class="rf-gestante-btn" :class="{ active: form.gestante === false }" @click="form.gestante = false">No</button>
                </div>
              </el-form-item>
              <el-form-item label="Condición especial" class="rf-field rf-col-quarter rf-stagger-2">
                <el-input v-model="form.condicion_especial" placeholder="Ej: discapacidad..." autocomplete="off" @input="capitalizar('condicion_especial')" />
              </el-form-item>
              <template v-for="(dx, i) in diagnosticos" :key="i">
                <el-form-item :label="i === 0 ? 'Código CIE-10' : ''" class="rf-field rf-col-quarter rf-stagger-3">
                  <el-select
                    v-model="dx.codigo_cie10"
                    filterable
                    class="w-full"
                    placeholder="Código CIE-10"
                    popper-class="rf-select-popper"
                    :filter-method="filtrarCIE10"
                    @change="(val: string) => onDxSelect(i, val)"
                  >
                    <el-option key="__otro_dx" label="➕ Otro..." value="__otro_dx" />
                    <el-option v-for="item in cie10Filtrados" :key="item.codigo" :label="item.codigo" :value="item.codigo" />
                  </el-select>
                </el-form-item>
                <el-form-item :label="i === 0 ? 'Descripción del diagnóstico' : ''" class="rf-field rf-col-quarter rf-stagger-4">
                  <div class="rf-dx-inline-row">
                    <el-input v-model="dx.descripcion" placeholder="Descripción del diagnóstico" />
                    <button v-if="diagnosticos.length > 1" type="button" class="rf-dx-remove" @click="quitarDiagnostico(i)">
                      <component :is="XIcon" class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </el-form-item>
              </template>
              <div class="rf-col-quarter rf-dx-add-cell">
                <button type="button" class="rf-attach-btn" @click="fileInput?.click()">
                  <component :is="UploadCloudIcon" class="w-4 h-4" />
                  <span>Adjuntar archivos</span>
                </button>
                <input ref="fileInput" class="hidden" type="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar" multiple @change="seleccionarAdjuntos" />
              </div>
              <p v-if="diagnosticos.length === 0" class="rf-dx-empty">Agregue al menos un diagnóstico.</p>
            </div>
            <transition-group v-if="adjuntos.length" name="file-list" tag="div" class="rf-file-list rf-file-list-inline">
              <div v-for="(archivo, i) in adjuntos" :key="archivo.name + i" class="rf-file-chip">
                <span class="rf-file-badge" :class="getFileTypeClass(archivo)">{{ getFileTypeLabel(archivo) }}</span>
                <span class="rf-file-name">{{ archivo.name }}</span>
                <button type="button" class="rf-file-remove" @click.stop="removerAdjunto(i)">
                  <component :is="XCircleIcon" class="w-3 h-3" />
                </button>
              </div>
            </transition-group>
            <el-form-item label="Historia clínica" prop="resumen_historia_clinica" required :class="['rf-field rf-col-3 rf-stagger-5', { 'is-success': camposValidos.resumen_historia_clinica }]" style="margin-top: 8px;">
              <el-input v-model="form.resumen_historia_clinica" type="textarea" :rows="3"
                placeholder="Motivo de remisión, antecedentes, estado actual..." @input="capitalizar('resumen_historia_clinica')" />
            </el-form-item>
          </div>

          <!-- Sub-sección: Quien remite -->
          <div class="rf-subsection">
            <div class="rf-subsection-header">
              <div class="rf-subsection-icon rf-sub-icon-cyan">
                <component :is="UserCheckIcon" class="w-3.5 h-3.5" />
              </div>
              <h4>Datos de quien remite</h4>
            </div>
            <div class="rf-grid">
              <el-form-item label="Quien remite" prop="quien_remitente" class="rf-field rf-col-2 rf-stagger-1">
                <el-input v-model="form.quien_remitente" placeholder="Nombre de quien remite" @input="capitalizar('quien_remitente')">
                  <template #prefix><component :is="UserIcon" class="w-3.5 h-3.5 rf-input-icon" /></template>
                </el-input>
              </el-form-item>
              <el-form-item label="Teléfono de contacto" prop="telefono_contacto" class="rf-field rf-col-quarter rf-stagger-2">
                <el-input v-model="form.telefono_contacto" placeholder="Teléfono">
                  <template #prefix><component :is="PhoneIcon" class="w-3.5 h-3.5 rf-input-icon" /></template>
                </el-input>
              </el-form-item>
              <el-form-item label="Correo de contacto" prop="correo_contacto" class="rf-field rf-col-2 rf-stagger-3">
                <el-input v-model="form.correo_contacto" placeholder="correo@ejemplo.com">
                  <template #prefix><component :is="MailIcon" class="w-3.5 h-3.5 rf-input-icon" /></template>
                </el-input>
              </el-form-item>
            </div>
          </div>
        </div>

      </el-form>
    </div>

    <!-- Footer -->
    <div class="rf-footer">
      <el-button class="rf-btn-back" @click="router.push('/clinica/dashboard')">Cancelar</el-button>
      <el-button type="primary" class="rf-btn-submit" @click="abrirConfirmacion">Enviar solicitud</el-button>
    </div>

    <!-- Dialog de confirmación -->
    <el-dialog
      v-model="showConfirmResumen"
      title="Confirmar solicitud"
      width="560px"
      class="confirm-dialog"
      :close-on-click-modal="false"
      append-to-body
      align-center
    >
      <div class="confirm-body">
        <div class="confirm-hero">
          <div class="confirm-hero-glow"></div>
          <div class="confirm-hero-glow-2"></div>
          <div class="confirm-icon-wrap">
            <component :is="FileCheckIcon" class="w-7 h-7" />
          </div>
          <h3 class="confirm-title">¿Confirmar envío?</h3>
          <p class="confirm-subtitle">Revise los datos antes de enviar la solicitud</p>
        </div>

        <div class="confirm-grid">
          <div class="confirm-item confirm-item-blue">
            <div class="confirm-item-icon"><component :is="UserIcon" class="w-3 h-3" /></div>
            <div class="confirm-item-content">
              <span>Paciente</span>
              <strong>{{ nombreCompleto }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-blue">
            <div class="confirm-item-icon"><component :is="FileTextIcon" class="w-3 h-3" /></div>
            <div class="confirm-item-content">
              <span>Documento</span>
              <strong>{{ form.tipo_documento }} {{ form.numero_documento }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-blue">
            <div class="confirm-item-content">
              <span>Edad / Género</span>
              <strong>{{ form.edad }} años · {{ form.genero === 'M' ? 'Masc.' : 'Fem.' }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-blue">
            <div class="confirm-item-content">
              <span>EPS</span>
              <strong>{{ form.eps || '—' }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-green">
            <div class="confirm-item-icon"><component :is="StethoscopeIcon" class="w-3 h-3" /></div>
            <div class="confirm-item-content">
              <span>Especialidad</span>
              <strong>{{ form.especialidad_requerida || '—' }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-green">
            <div class="confirm-item-content">
              <span>Servicio actual</span>
              <strong>{{ form.servicio_ubicacion_actual || '—' }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-green confirm-item-full">
            <span>Diagnósticos</span>
            <div v-if="diagnosticos.filter(d => d.codigo_cie10 || d.descripcion).length" class="confirm-dx-list">
              <div v-for="(dx, i) in diagnosticos.filter(d => d.codigo_cie10 || d.descripcion)" :key="i" class="confirm-dx-item">
                <span class="confirm-dx-code">{{ dx.codigo_cie10 }}</span>
                <span class="confirm-dx-desc">{{ dx.descripcion }}</span>
              </div>
            </div>
            <strong v-else>—</strong>
          </div>
          <div class="confirm-item confirm-item-green confirm-item-full">
            <span>Historia clínica</span>
            <strong class="confirm-clamp">{{ form.resumen_historia_clinica || '—' }}</strong>
          </div>
          <div class="confirm-item confirm-item-amber">
            <div class="confirm-item-icon"><component :is="UserCheckIcon" class="w-3 h-3" /></div>
            <div class="confirm-item-content">
              <span>Quien remite</span>
              <strong>{{ form.quien_remitente || '—' }}</strong>
            </div>
          </div>
          <div class="confirm-item confirm-item-amber">
            <div class="confirm-item-content">
              <span>Adjuntos</span>
              <strong>{{ adjuntos.length }} archivo(s)</strong>
            </div>
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
        <div class="exito-confetti">
          <span v-for="i in 12" :key="i" class="confetti-piece" :style="{ '--i': i }"></span>
        </div>
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
        <div class="exito-actions">
          <el-button class="exito-btn-secondary" @click="modalExito = false">
            <component :is="PlusIcon" class="w-4 h-4 mr-1" /> Nueva solicitud
          </el-button>
          <el-button type="primary" class="exito-btn !bg-[#0D2D6B] flex-1" @click="router.push('/clinica/historial')">
            Ver historial
          </el-button>
        </div>
      </div>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
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
  FileText as FileTextIcon,
  ArrowLeft as ArrowLeftIcon,
  IdCard as IdCardIcon,
  Phone as PhoneIcon,
  Mail as MailIcon,
  MapPin as MapPinIcon,
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

const progresoFormulario = computed(() => {
  const campos = [
    'tipo_documento', 'numero_documento', 'primer_nombre', 'primer_apellido',
    'genero', 'edad', 'municipio_capita', 'eps',
    'especialidad_requerida', 'servicio_ubicacion_actual', 'resumen_historia_clinica',
  ];
  const llenos = campos.filter(c => {
    const v = form.value[c as keyof typeof form.value];
    return v !== null && v !== '' && v !== undefined;
  }).length;
  const dxCount = diagnosticos.value.filter(d => d.codigo_cie10.trim() && d.descripcion.trim()).length;
  const dxBonus = dxCount > 0 ? 1 : 0;
  return Math.min(100, Math.round(((llenos + dxBonus) / (campos.length + 1)) * 100));
});

const camposValidos = computed(() => {
  const required = ['tipo_documento', 'numero_documento', 'primer_nombre', 'primer_apellido',
    'genero', 'edad', 'municipio_capita', 'eps',
    'especialidad_requerida', 'servicio_ubicacion_actual', 'resumen_historia_clinica'];
  const valid: Record<string, boolean> = {};
  for (const c of required) {
    const v = form.value[c as keyof typeof form.value];
    valid[c] = v !== null && v !== '' && v !== undefined;
  }
  return valid;
});

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
    notify.warning('Máximo 10 archivos permitidos');
    return;
  }
  const nuevos = files.slice(0, restantes);
  if (files.length > restantes) {
    notify.warning(`Solo se agregaron ${restantes} de ${files.length} archivos (límite 10)`);
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

async function abrirConfirmacion(): Promise<void> {
  const validDx = diagnosticos.value.filter(d => d.codigo_cie10.trim() && d.descripcion.trim());
  if (validDx.length === 0) {
    notify.warning('Agregue al menos un diagnóstico con código y descripción');
    return;
  }
  try {
    await formRef.value?.validate();
    showConfirmResumen.value = true;
  } catch {
    notify.error('Por favor complete todos los campos requeridos');
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
  catch { notify.error('Por favor complete todos los campos requeridos'); return; }
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
    diagnosticos.value = [{ codigo_cie10: '', descripcion: '' }];
  } catch (e: any) {
    const errors = e.response?.data?.data?.errors || e.response?.data?.errors;
    notify.error(errors ? Object.values(errors).flat().join('\n') : 'Error al enviar la solicitud');
  } finally { guardando.value = false; }
}

onMounted(() => {
  cie10Filtrados.value = CIE10.slice(0, 20);
});
</script>

<style scoped>
/* ── Page ── */
.solicitud-page {
  padding: .85rem 1.4rem;
  gap: .6rem;
  background: linear-gradient(160deg, #F0F5FF 0%, #E0EAF7 40%, #F0F5FF 100%);
}

/* ── Stepper compacto ── */
.rf-stepper {
  display: flex;
  align-items: center;
  gap: .6rem;
  flex-shrink: 0;
  background: linear-gradient(180deg, #FAFBFF 0%, #F5F8FE 100%);
  border: 1px solid #DBE4F0;
  border-radius: 14px;
  padding: .55rem .9rem;
  box-shadow: 0 4px 16px rgba(13, 45, 107, .08);
}
.rf-stepper-back {
  display: grid;
  place-items: center;
  width: 36px; height: 36px;
  border-radius: 9px;
  border: 1px solid #E2E8F0;
  background: #fff;
  color: #0D2D6B;
  cursor: pointer;
  transition: all .2s ease;
  flex-shrink: 0;
}
.rf-stepper-back:hover {
  background: #EFF6FF;
  border-color: #BFDBFE;
}
.rf-stepper-title {
  flex: 1;
  min-width: 0;
}
.rf-stepper-title h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0D2D6B;
  margin: 0;
  line-height: 1.25;
}
.rf-stepper-title p {
  font-size: .74rem;
  font-weight: 500;
  color: #8A9AB5;
  margin: 2px 0 0;
}
.rf-stepper-progress {
  display: flex; align-items: center; gap: 8px;
  flex-shrink: 0;
  position: relative;
}
.rf-stepper-progress-track {
  width: 80px; height: 6px;
  border-radius: 4px;
  background: #E2E8F0;
  overflow: hidden;
}
.rf-stepper-progress-bar {
  height: 100%;
  border-radius: 4px;
  background: linear-gradient(90deg, #16468E, #3B82F6);
  transition: width .4s ease;
}
.rf-stepper-progress-label {
  font-size: .72rem;
  font-weight: 700;
  color: #16468E;
  min-width: 32px;
  text-align: right;
}

/* ── Layout: 2 columnas ── */
.rf-body {
  flex: 1;
  min-height: 0;
  overflow: hidden;
  display: flex;
  gap: .6rem;
}
.rf-form-col {
  flex: 1;
  min-width: 0;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 4px;
}
.rf-form-col::-webkit-scrollbar { width: 6px; }
.rf-form-col::-webkit-scrollbar-thumb { background: #C7D5EA; border-radius: 999px; }
.rf-form-col::-webkit-scrollbar-thumb:hover { background: #9FB4D8; }
.rf-form-col::-webkit-scrollbar-track { background: transparent; }

/* ── Form overrides ── */
:deep(.rf-form-col .el-form) { display: flex; flex-direction: column; gap: .75rem; }
:deep(.rf-form-col .el-form-item) { margin-bottom: 0; display: flex; flex-direction: column; }
:deep(.rf-form-col .el-form-item__label) {
  color: #475569; font-size: .74rem; font-weight: 600;
  padding-bottom: .2rem; line-height: 1.2;
}
:deep(.rf-form-col .el-form-item__error) { padding-top: 3px; font-size: .66rem; }
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
:deep(.rf-dx-block .el-input__wrapper),
:deep(.rf-dx-block .el-select__wrapper) { min-height: 30px; }
:deep(.rf-attach .el-input__wrapper) { min-height: 30px; }
:deep(.rf-form-col .el-input-number) { width: 100%; }
:deep(.rf-form-col .el-input-number .el-input__wrapper) { min-height: 36px; }
:deep(.rf-form-col .el-textarea__inner) { min-height: 36px !important; border-radius: 9px; }

/* ── Sections ── */
.rf-section-divider {
  height: 1px;
  margin: .5rem 0;
  background: linear-gradient(90deg, transparent, #BFDBFE 20%, #93C5FD 50%, #BFDBFE 80%, transparent);
  opacity: .6;
}
.rf-section {
  position: relative;
  background: linear-gradient(180deg, #FAFBFF 0%, #F5F8FE 100%);
  border: 1px solid #DBE4F0;
  border-radius: 18px;
  padding: 1.1rem 1.3rem 1.3rem;
  box-shadow: 0 8px 28px rgba(13, 45, 107, .09);
  overflow: hidden;
}
.rf-section-accent {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
}
.rf-section-header {
  display: flex;
  align-items: center;
  gap: .65rem;
  margin-bottom: .85rem;
  padding-bottom: .7rem;
  border-bottom: 1px solid #EEF2F9;
}
.rf-section-icon {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 11px;
  flex-shrink: 0;
}
.rf-section-heading h3 {
  font-size: 1rem;
  font-weight: 800;
  color: #0D2D6B;
  margin: 0;
}
.rf-section-heading p {
  font-size: .74rem;
  font-weight: 500;
  color: #8A9AB5;
  margin: 2px 0 0;
}

/* ── Grid ── */
.rf-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: .9rem 1rem;
}
.rf-col-1 { grid-column: span 1; }
.rf-col-2 { grid-column: span 2; }
.rf-col-3 { grid-column: span 5; }
.rf-col-half { grid-column: span 2; }
.rf-col-third { grid-column: span 2; }
.rf-col-quarter { grid-column: span 1; }
.rf-col-fifth { grid-column: span 1; }
.rf-col-sixth { grid-column: span 1; }
.rf-col-4 { grid-column: span 4; }
.rf-col-5 { grid-column: span 5; }
.rf-mt { margin-top: .7rem; }

/* ── Input prefix icons ── */
.rf-input-icon { color: #94A3B8; margin-right: 2px; }
:deep(.rf-form-col .el-input__prefix) { color: #94A3B8; }

/* ── Subsection ── */
.rf-subsection {
  margin-top: .6rem;
  padding-top: .55rem;
  border-top: 1px dashed #E2E8F0;
}
.rf-subsection:first-of-type {
  margin-top: 0;
  padding-top: 0;
  border-top: none;
}
.rf-subsection-row {
  display: flex;
  gap: .5rem;
  align-items: stretch;
}
.rf-subsection-row.rf-subsection-first {
  margin-top: 0;
  padding-top: 0;
  border-top: none;
}
.rf-subsection-row .rf-subsection-half {
  flex: 1;
  min-width: 0;
  margin-top: 0;
  padding-top: 0;
  border-top: none;
}
.rf-subsection-row .rf-dx-block,
.rf-subsection-row .rf-attach {
  height: 100%;
}
.rf-subsection-header {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: .35rem;
}
.rf-subsection-icon {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 6px;
  flex-shrink: 0;
}
.rf-sub-icon-green { background: #DCFCE7; color: #15803D; }
.rf-sub-icon-blue { background: #DBEAFE; color: #2563EB; }
.rf-sub-icon-amber { background: #FEF3C7; color: #D97706; }
.rf-sub-icon-purple { background: #F3E8FF; color: #7C3AED; }
.rf-sub-icon-cyan { background: #CFFAFE; color: #0891B2; }
.rf-subsection-header h4 {
  font-size: .76rem;
  font-weight: 800;
  color: #0D2D6B;
  margin: 0;
}
.rf-subsection-badge {
  margin-left: auto;
  font-size: .58rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  background: #FEF2F2;
  color: #DC2626;
  border: 1px solid #FECACA;
}
.rf-badge-blue {
  background: #EFF6FF;
  color: #2563EB;
  border-color: #BFDBFE;
}

/* ── Diagnósticos ── */
.rf-dx-block {
  background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
  border: 1px solid #BFDBFE;
  border-radius: 10px;
  padding: .4rem .5rem;
}
.rf-dx-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .3rem;
}
.rf-dx-title {
  font-size: .72rem;
  font-weight: 800;
  color: #1E40AF;
}
.rf-req { color: #DC2626; }
.rf-dx-add {
  border-color: #0D2D6B !important;
  background: #DBEAFE !important;
  color: #0D2D6B !important;
  font-size: .66rem;
  font-weight: 600;
  border-radius: 7px;
  height: 28px !important;
}
.rf-dx-row {
  display: flex;
  align-items: center;
  gap: .35rem;
  margin-bottom: .3rem;
}
.rf-dx-code { width: 110px; flex-shrink: 0; }
.rf-dx-desc { flex: 1; }
.rf-dx-remove {
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1px solid #FCA5A5;
  background: #FEF2F2;
  color: #DC2626;
  cursor: pointer;
  flex-shrink: 0;
  transition: all .2s ease;
}
.rf-dx-remove:hover { background: #DC2626; color: #fff; }
.rf-dx-empty { font-size: .72rem; color: #94A3B8; margin: .3rem 0 0; }

/* ── Diagnósticos inline en grid ── */
.rf-dx-inline-row { display: flex; align-items: center; gap: .3rem; width: 100%; }
.rf-dx-inline-row .el-input { flex: 1; }
.rf-dx-add-cell { display: flex; align-items: flex-end; padding-bottom: .4rem; }

/* ── Botón Adjuntar archivos ── */
.rf-attach-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  width: 100%; height: 34px;
  border: 1px dashed #93C5FD; border-radius: 8px;
  background: #EFF6FF; color: #16468E;
  font-size: 13px; font-weight: 500; cursor: pointer;
  transition: all .2s ease;
}
.rf-attach-btn:hover { background: #DBEAFE; border-color: #16468E; }
.rf-file-list-inline { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; padding: 0 2px; }

/* ── Botones Sí/No gestante ── */
.rf-gestante-btns { display: flex; gap: 6px; width: 100%; }
.rf-gestante-btn {
  flex: 1;
  height: 34px;
  border: 1px solid #d4deea;
  border-radius: 8px;
  background: #fff;
  color: #64748B;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all .2s ease;
}
.rf-gestante-btn:hover { border-color: #16468E; color: #16468E; }
.rf-gestante-btn.active { background: #16468E; border-color: #16468E; color: #fff; }

/* ── Adjuntos ── */
.rf-attach {
  border: 1px solid #BFDBFE;
  background: linear-gradient(135deg, #EFF6FF, #F0F9FF);
  border-radius: 10px;
  padding: .4rem;
}
.rf-attach-active {
  border-color: #3B82F6;
  background: linear-gradient(135deg, #DBEAFE, #EFF6FF);
}
.rf-attach-header {
  display: flex;
  align-items: center;
  gap: .35rem;
  margin-bottom: .5rem;
  font-size: .78rem;
  font-weight: 800;
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
  padding: .4rem .4rem;
  text-align: center;
  cursor: pointer;
  transition: all .2s ease;
  color: #2563EB;
}
.rf-dropzone:hover {
  border-color: #2563EB;
  background: rgba(219, 234, 254, .5);
}
.rf-dropzone p { font-size: .66rem; font-weight: 600; margin: .1rem 0 0; color: #1E40AF; }
.rf-dropzone small { font-size: .58rem; color: #3B82F6; }
.rf-file-list { margin-top: .25rem; display: flex; flex-direction: column; gap: .15rem; }
.rf-file-chip {
  display: flex;
  align-items: center;
  gap: .3rem;
  padding: .2rem .35rem;
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 5px;
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
  font-size: .64rem;
  font-weight: 600;
  color: #475569;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.rf-file-remove {
  display: grid;
  place-items: center;
  width: 20px;
  height: 20px;
  color: #94A3B8;
  cursor: pointer;
  border: none;
  background: none;
  padding: 2px;
  transition: color .2s ease;
}
.rf-file-remove:hover { color: #DC2626; }

/* ── Sidebar: Resumen ── */
.rf-summary-card {
  background: linear-gradient(180deg, #FAFBFF 0%, #F5F8FE 100%);
  border: 1px solid #DBE4F0;
  border-radius: 14px;
  padding: .75rem .85rem;
  box-shadow: 0 4px 18px rgba(13, 45, 107, .08);
  position: sticky;
  top: 0;
}
.rf-summary-header {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: .8rem;
  padding-bottom: .6rem;
  border-bottom: 1px solid #F1F5F9;
}
.rf-summary-icon {
  display: grid;
  place-items: center;
  width: 32px; height: 32px;
  border-radius: 10px;
  background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
  color: #0D2D6B;
  flex-shrink: 0;
}
.rf-summary-header h3 {
  font-size: .82rem;
  font-weight: 800;
  color: #0D2D6B;
  margin: 0;
}
.rf-summary-progress {
  margin-bottom: .9rem;
}
.rf-summary-progress-bar {
  height: 6px;
  border-radius: 999px;
  background: #F1F5F9;
  overflow: hidden;
  margin-bottom: .3rem;
}
.rf-summary-progress-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #0D2D6B, #3B82F6);
  transition: width .4s cubic-bezier(.22,1,.36,1);
}
.rf-summary-progress-text {
  font-size: .62rem;
  font-weight: 600;
  color: #94A3B8;
}
.rf-summary-section {
  margin-bottom: .8rem;
}
.rf-summary-section:last-child { margin-bottom: 0; }
.rf-summary-section-title {
  font-size: .62rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #94A3B8;
  margin: 0 0 .35rem;
}
.rf-summary-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .35rem;
}
.rf-summary-section-header .rf-summary-section-title { margin: 0; }
.rf-summary-section-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
  transition: all .3s ease;
}
.dot-green {
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(34,197,94,.18);
}
.dot-amber {
  background: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245,158,11,.18);
  animation: rf-dot-pulse 2s ease-in-out infinite;
}
@keyframes rf-dot-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: .5; }
}
.rf-summary-field {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: .5rem;
  padding: .25rem .4rem;
  margin: 0 -.4rem;
  border-bottom: 1px solid #F8FAFC;
  border-radius: 6px;
  transition: background .2s ease;
}
.rf-summary-field:last-child { border-bottom: none; }
.rf-summary-field span {
  font-size: .65rem;
  font-weight: 600;
  color: #94A3B8;
  flex-shrink: 0;
}
.rf-summary-field strong {
  font-size: .72rem;
  font-weight: 700;
  color: #0D2D6B;
  text-align: right;
  word-break: break-word;
  transition: color .2s ease;
}
.rf-summary-field:hover { background: #F8FAFC; }
.rf-summary-field:hover strong { color: #16468E; }

/* ── Footer ── */
.rf-footer {
  display: flex;
  gap: .6rem;
  flex-shrink: 0;
  padding-top: .6rem;
  border-top: 1px solid #E2E8F0;
  background: linear-gradient(180deg, rgba(255,255,255,0), #fff 45%);
}
.rf-btn-back {
  background: #fff !important;
  border-color: #CBD5E1 !important;
  color: #475569 !important;
  font-weight: 600;
  border-radius: 10px;
  height: 40px !important;
  padding: 0 20px !important;
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
  border-radius: 10px;
  height: 40px !important;
  padding: 0 26px !important;
  margin-left: auto;
  box-shadow: 0 3px 12px rgba(13, 45, 107, .2);
}
.rf-btn-next:hover, .rf-btn-submit:hover {
  background: linear-gradient(135deg, #16468E, #1E5BBF) !important;
  box-shadow: 0 6px 20px rgba(13, 45, 107, .35);
  transform: translateY(-1px);
}

/* ── Dialog confirmación ── */
:deep(.confirm-dialog) {
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(13, 45, 107, .25);
}
:deep(.confirm-dialog .el-dialog__header) { display: none; }
:deep(.confirm-dialog .el-dialog__body) { padding: 0; }
:deep(.confirm-dialog .el-dialog__footer) { padding: 0; }
.confirm-body { padding: 0; }
.confirm-hero {
  position: relative;
  overflow: hidden;
  text-align: center;
  padding: 1.2rem 1.5rem .9rem;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 60%, #1E5BBF 100%);
}
.confirm-hero-glow {
  position: absolute;
  top: -50%; right: -10%;
  width: 60%; height: 200%;
  background: radial-gradient(ellipse, rgba(59,130,246,.2), transparent 65%);
  pointer-events: none;
}
.confirm-hero-glow-2 {
  position: absolute;
  bottom: -60%; left: 10%;
  width: 50%; height: 180%;
  background: radial-gradient(ellipse, rgba(30,91,191,.15), transparent 65%);
  pointer-events: none;
}
.confirm-icon-wrap {
  display: inline-grid;
  place-items: center;
  width: 48px; height: 48px;
  border-radius: 14px;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  color: #fff;
  margin-bottom: .5rem;
  box-shadow: 0 4px 14px rgba(0,0,0,.15);
  position: relative;
  z-index: 1;
}
.confirm-title { margin: 0 0 .15rem; font-size: 1rem; font-weight: 800; color: #fff; position: relative; z-index: 1; }
.confirm-subtitle { margin: 0; font-size: .72rem; color: rgba(255,255,255,.6); position: relative; z-index: 1; }

.confirm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .4rem .5rem;
  text-align: left;
  padding: .8rem 1.2rem;
}
.confirm-item {
  display: flex;
  align-items: flex-start;
  gap: .4rem;
  padding: .4rem .55rem;
  border: 1px solid #E2E8F0;
  border-radius: 10px;
  transition: all .2s ease;
}
.confirm-item-content {
  display: flex;
  flex-direction: column;
  gap: .1rem;
  min-width: 0;
  flex: 1;
}
.confirm-item-icon {
  display: grid;
  place-items: center;
  width: 22px; height: 22px;
  border-radius: 7px;
  flex-shrink: 0;
  margin-top: 1px;
}
.confirm-item-blue { background: #EFF6FF; border-color: #BFDBFE; }
.confirm-item-blue .confirm-item-icon { background: #DBEAFE; color: #2563EB; }
.confirm-item-blue:hover { border-color: #60A5FA; background: #DBEAFE; }
.confirm-item-blue strong { color: #1E40AF; }

.confirm-item-green { background: #F0FDF4; border-color: #BBF7D0; }
.confirm-item-green .confirm-item-icon { background: #DCFCE7; color: #16A34A; }
.confirm-item-green:hover { border-color: #4ADE80; background: #DCFCE7; }
.confirm-item-green strong { color: #166534; }

.confirm-item-amber { background: #FFFBEB; border-color: #FDE68A; }
.confirm-item-amber .confirm-item-icon { background: #FEF3C7; color: #D97706; }
.confirm-item-amber:hover { border-color: #FBBF24; background: #FEF3C7; }
.confirm-item-amber strong { color: #92400E; }

.confirm-item-full { grid-column: span 2; }
.confirm-item span { font-size: .58rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: .04em; }
.confirm-item strong { font-size: .72rem; font-weight: 700; word-break: break-word; }
.confirm-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.confirm-dx-list { display: flex; flex-direction: column; gap: .2rem; margin-top: .2rem; }
.confirm-dx-item {
  display: flex; align-items: baseline; gap: .4rem;
  padding: .2rem .4rem; border-radius: 6px;
  background: rgba(255,255,255,.6); border: 1px solid #BBF7D0;
}
.confirm-dx-code { font-size: .65rem; font-weight: 800; color: #15803D; font-family: monospace; flex-shrink: 0; }
.confirm-dx-desc { font-size: .65rem; color: #166534; }
.confirm-footer { display: flex; gap: .75rem; padding: 0 1.2rem .9rem; }

/* ── Modal éxito ── */
:deep(.exito-dialog) { border-radius: 20px; overflow: hidden; box-shadow: 0 28px 70px rgba(11, 35, 73, .35); }
:deep(.exito-dialog .el-dialog__header) { display: none; }
:deep(.exito-dialog .el-dialog__body) { padding: 0; }
.exito-content {
  padding: 2rem 1.8rem 1.6rem;
  text-align: center;
  background: linear-gradient(180deg, #f0f5ff 0%, #f8faff 40%, #ffffff 100%);
  position: relative;
  overflow: hidden;
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
.exito-actions {
  display: flex;
  gap: .6rem;
}
.exito-btn-secondary {
  border-radius: 10px !important;
  font-weight: 600 !important;
  height: 42px !important;
  font-size: .82rem !important;
  border: 1px solid #BFDBFE !important;
  background: #EFF6FF !important;
  color: #0D2D6B !important;
  display: inline-flex !important;
  align-items: center;
  transition: all .2s ease;
}
.exito-btn-secondary:hover {
  background: #DBEAFE !important;
  border-color: #60A5FA !important;
}

/* ── Confetti ── */
.exito-confetti {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 100%;
  overflow: hidden;
  pointer-events: none;
  border-radius: 20px 20px 0 0;
}
.confetti-piece {
  position: absolute;
  width: 8px; height: 8px;
  top: -10px;
  left: calc(8.33% * var(--i) - 4%);
  border-radius: 2px;
  animation: rf-confetti-fall 1.5s cubic-bezier(.22,1,.36,1) both;
  animation-delay: calc(var(--i) * .05s);
}
.confetti-piece:nth-child(1) { background: #0D2D6B; }
.confetti-piece:nth-child(2) { background: #3B82F6; }
.confetti-piece:nth-child(3) { background: #22c55e; }
.confetti-piece:nth-child(4) { background: #f59e0b; }
.confetti-piece:nth-child(5) { background: #0D2D6B; }
.confetti-piece:nth-child(6) { background: #60A5FA; }
.confetti-piece:nth-child(7) { background: #22c55e; }
.confetti-piece:nth-child(8) { background: #3B82F6; }
.confetti-piece:nth-child(9) { background: #f59e0b; }
.confetti-piece:nth-child(10) { background: #0D2D6B; }
.confetti-piece:nth-child(11) { background: #60A5FA; }
.confetti-piece:nth-child(12) { background: #22c55e; }
@keyframes rf-confetti-fall {
  0% {
    transform: translateY(0) rotate(0deg);
    opacity: 1;
  }
  100% {
    transform: translateY(200px) rotate(360deg);
    opacity: 0;
  }
}

/* ── Section glow ── */
.rf-section-glow {
  position: absolute;
  top: -20px; right: -20px;
  width: 120px; height: 120px;
  pointer-events: none;
  opacity: .8;
}

/* ── Field stagger animation ── */
@keyframes rf-field-in {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
.rf-field {
  animation: rf-field-in .35s cubic-bezier(.22,1,.36,1) both;
}
.rf-stagger-1 { animation-delay: .04s; }
.rf-stagger-2 { animation-delay: .08s; }
.rf-stagger-3 { animation-delay: .12s; }
.rf-stagger-4 { animation-delay: .16s; }
.rf-stagger-5 { animation-delay: .20s; }
.rf-stagger-6 { animation-delay: .24s; }
.rf-stagger-7 { animation-delay: .28s; }
.rf-stagger-8 { animation-delay: .32s; }

/* ── Step slide transition ── */
.step-slide-enter-active,
.step-slide-leave-active {
  transition: all .3s cubic-bezier(.22,1,.36,1);
}
.step-slide-enter-from {
  opacity: 0;
  transform: translateX(24px);
}
.step-slide-leave-to {
  opacity: 0;
  transform: translateX(-24px);
}

/* ── Fade scale transition ── */
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all .25s cubic-bezier(.22,1,.36,1);
}
.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(.85);
}

/* ── Progress bar shimmer ── */
@keyframes rf-progress-shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}
.rf-summary-progress-fill {
  background-image: linear-gradient(
    90deg,
    #0D2D6B 0%,
    #3B82F6 50%,
    #0D2D6B 100%
  );
  background-size: 200% 100%;
  animation: rf-progress-shimmer 2.5s linear infinite;
}
.rf-progress-complete {
  background: linear-gradient(90deg, #22c55e, #15803d) !important;
  background-size: 100% 100% !important;
  animation: none !important;
}

/* ── Progress info ── */
.rf-summary-progress-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.rf-summary-progress-done {
  display: inline-flex;
  align-items: center;
  gap: .2rem;
  font-size: .62rem;
  font-weight: 700;
  color: #22c55e;
}

/* ── Section entrance ── */
@keyframes sp-fade-up {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
.rf-section { animation: sp-fade-up .35s ease both; }

/* ── Input focus glow ── */
:deep(.rf-form-col .el-input__wrapper.is-focus),
:deep(.rf-form-col .el-select__wrapper.is-focus) {
  box-shadow: 0 0 0 2px rgba(22, 70, 142, .12) !important;
}

/* ── Validación visual: check verde en campos válidos ── */
:deep(.rf-form-col .el-form-item.rf-field.is-success .el-form-item__label::after) {
  content: '✓';
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px; height: 16px;
  margin-left: 6px;
  border-radius: 50%;
  background: #22c55e;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
}
:deep(.rf-form-col .el-form-item.rf-field.is-success .el-form-item__label) {
  color: #16468E;
}

/* ── Validacion visual: borde verde cuando el campo tiene valor y no hay error ── */
:deep(.rf-form-col .el-form-item:not(.is-error) .el-input__wrapper:not(.is-focus)),
:deep(.rf-form-col .el-form-item:not(.is-error) .el-select__wrapper:not(.is-focus)),
:deep(.rf-form-col .el-form-item:not(.is-error) .el-textarea__inner:not(:focus)) {
  transition: box-shadow .25s ease;
}
:deep(.rf-form-col .el-form-item.is-success .el-input__wrapper),
:deep(.rf-form-col .el-form-item.is-success .el-select__wrapper) {
  box-shadow: 0 0 0 1px #86EFAC inset !important;
}
:deep(.rf-form-col .el-form-item.is-success .el-textarea__inner) {
  border-color: #86EFAC !important;
}

/* ── Dropzone pulse when active ── */
@keyframes rf-drop-pulse {
  0%, 100% { border-color: #3B82F6; }
  50% { border-color: #60A5FA; }
}
.rf-attach-active .rf-dropzone {
  animation: rf-drop-pulse 1s ease-in-out infinite;
  background: rgba(219, 234, 254, .6) !important;
}

/* ── File list transition ── */
.file-list-enter-active,
.file-list-leave-active {
  transition: all .3s cubic-bezier(.22,1,.36,1);
}
.file-list-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}
.file-list-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* ── Success modal bounce ── */
@keyframes rf-success-bounce {
  0% { transform: scale(0); opacity: 0; }
  60% { transform: scale(1.15); opacity: 1; }
  100% { transform: scale(1); }
}
.exito-icon-circle {
  animation: rf-success-bounce .5s cubic-bezier(.22,1,.36,1) both;
}

/* ── Button shimmer on hover ── */
.rf-btn-next::before, .rf-btn-submit::before {
  content: '';
  position: absolute;
  top: 0; left: -100%;
  width: 100%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
  transition: left .5s ease;
}
.rf-btn-next:hover::before, .rf-btn-submit:hover::before {
  left: 100%;
}
.rf-btn-next, .rf-btn-submit {
  position: relative;
  overflow: hidden;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .rf-grid { grid-template-columns: repeat(2, 1fr); }
  .rf-col-3 { grid-column: span 2; }
  .rf-col-half { grid-column: span 2; }
  .rf-col-third { grid-column: span 1; }
  .rf-col-quarter { grid-column: span 1; }
  .sp-hero-badge, .rf-stepper-progress { display: none; }
  .rf-stepper { padding: .5rem .6rem; }
  .rf-step-label { display: none; }
  .rf-step:not(:last-child)::after { margin: 0 .4rem; }
}
</style>

<style>
/* ── Select dropdown personalizado ── */
.rf-select-popper {
  border-radius: 14px !important;
  border: 1px solid #DBEAFE !important;
  box-shadow: 0 12px 40px rgba(13, 45, 107, .15) !important;
  overflow: hidden;
  padding: 4px !important;
}
.rf-select-popper .el-select-dropdown__item {
  border-radius: 8px;
  margin: 2px 0;
  padding: 0 .7rem;
  font-size: .8rem;
  font-weight: 500;
  color: #475569;
  transition: all .18s ease;
  position: relative;
}
.rf-select-popper .el-select-dropdown__item:hover {
  background: #EFF6FF;
  color: #0D2D6B;
  padding-left: .9rem;
}
.rf-select-popper .el-select-dropdown__item.selected {
  background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
  color: #0D2D6B;
  font-weight: 700;
}
.rf-select-popper .el-select-dropdown__item.selected::after {
  content: '';
  position: absolute;
  right: .6rem;
  top: 50%;
  transform: translateY(-50%);
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #0D2D6B;
}
.rf-select-popper .el-select-dropdown__item.selected.hover {
  background: linear-gradient(135deg, #BFDBFE, #93C5FD);
}
.rf-select-popper .el-select-dropdown__empty {
  padding: .8rem;
  font-size: .75rem;
  color: #94A3B8;
}
.rf-select-popper .el-select-dropdown__loading {
  padding: .8rem;
  font-size: .75rem;
  color: #94A3B8;
}
/* Scrollbar del dropdown */
.rf-select-popper .el-select-dropdown__wrap {
  max-height: 240px;
}
.rf-select-popper .el-scrollbar__bar.is-vertical {
  width: 4px;
  right: 2px;
}
.rf-select-popper .el-scrollbar__thumb {
  background: #BFDBFE;
  border-radius: 4px;
}
.rf-select-popper .el-scrollbar__thumb:hover {
  background: #60A5FA;
}
/* Input de busqueda (filterable) */
.rf-select-popper .el-select-dropdown__option-item {
  border-radius: 8px;
}
</style>
