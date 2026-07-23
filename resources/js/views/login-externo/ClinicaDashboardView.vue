<template>
  <div class="h-full flex flex-col gap-4 p-4 overflow-hidden" style="background:#e8ecf1;">

    <!-- ── Saludo ──────────────────────────────────────────────────────── -->
    <div class="rounded-2xl p-5 flex items-center gap-4 anim-fade-down" style="background:#e8ecf1; box-shadow: 8px 8px 16px #c5c9d0, -8px -8px 16px #ffffff;">
      <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" style="background:#e8ecf1; box-shadow: 5px 5px 10px #c5c9d0, -5px -5px 10px #ffffff;">
        <component :is="saludoIcono" class="w-5 h-5" style="color:#1e2d55;" />
      </div>
      <div class="flex-1 min-w-0">
        <h1 class="text-base font-bold leading-tight" style="color:#1e2d55;">{{ saludoTexto }}, {{ clinicaAuth.clinica?.nombre }}</h1>
        <p class="text-xs capitalize mt-0.5" style="color:#8a9ab5;">{{ fechaHoy }}</p>
      </div>
      <button
        @click="abrirFormulario"
        class="inline-flex items-center gap-2 font-semibold text-sm px-4 py-2.5 rounded-xl transition-all duration-150 active:scale-95 shrink-0"
        style="background:#1e2d55; color:#fff; box-shadow: 4px 4px 8px #c5c9d0, -2px -2px 6px #ffffff;"
      >
        <component :is="PlusIcon" class="w-4 h-4" />
        Nueva solicitud
      </button>
    </div>

    <!-- ── Tarjetas de estadísticas ───────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

      <div v-for="(card, i) in statCards" :key="i"
        class="rounded-2xl p-4 flex items-center gap-3 overflow-hidden anim-slide-up stat-card-hover"
        :style="{ background: card.gradient, boxShadow: '6px 6px 14px #c5c9d0, -6px -6px 14px #ffffff', animationDelay: (i * 0.08) + 's' }"
      >
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 icon-pulse" style="background:rgba(255,255,255,0.18);">
          <component :is="card.icon" class="w-5 h-5 text-white" />
        </div>
        <div>
          <p class="text-2xl font-extrabold leading-none text-white counter">{{ displayStats[i] }}</p>
          <p class="text-[11px] font-bold uppercase tracking-wide text-white/80 mt-0.5">{{ card.label }}</p>
          <p class="text-[10px] text-white/60 mt-0.5">{{ card.sub(displayStats[i]) }}</p>
        </div>
      </div>
    </div>

    <!-- ── Fila inferior: dos columnas ──────────────────────────────── -->
    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-5 gap-4">

      <!-- Columna izquierda: Estado + Accesos rápidos -->
      <div class="lg:col-span-2 flex flex-col gap-4 min-h-0 anim-fade-left" style="animation-delay:0.3s">

        <!-- Estado de solicitudes -->
        <div class="rounded-2xl p-4 flex-1 flex flex-col" style="background:#e8ecf1; box-shadow: 8px 8px 16px #c5c9d0, -8px -8px 16px #ffffff;">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-sm" style="color:#1e2d55;">Estado de solicitudes</h2>
            <button @click="cargar" class="inline-flex items-center gap-1.5 text-xs font-medium" style="color:#8a9ab5;">
              <component :is="RefreshCwIcon" class="w-3.5 h-3.5" />
              Actualizar
            </button>
          </div>
          <div v-if="cargando" class="space-y-3">
            <div v-for="i in 3" :key="i" class="h-4 rounded-full animate-pulse" style="background:#d8dce3;" />
          </div>
          <div v-else-if="stats.total === 0" class="py-2 text-center text-xs" style="color:#8a9ab5;">Sin solicitudes aún</div>
          <div v-else class="space-y-3">
            <div v-for="(item, i) in [
              { label: 'Pendientes', count: stats.pendientes, color: '#e67700' },
              { label: 'Aceptadas',  count: stats.aceptadas,  color: '#2f9e44' },
              { label: 'Negadas',    count: stats.negadas,    color: '#c92a2a' },
            ]" :key="i">
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-semibold" :style="{ color: item.color }">{{ item.label }}</span>
                <span class="text-xs font-bold" style="color:#1e2d55;">{{ item.count }}</span>
              </div>
              <div class="h-2.5 rounded-full overflow-hidden" style="box-shadow: inset 3px 3px 6px #c5c9d0, inset -3px -3px 6px #ffffff;">
                <div class="h-full rounded-full transition-all duration-700"
                  :style="{ width: stats.total > 0 ? (item.count / stats.total * 100) + '%' : '0%', background: item.color }" />
              </div>
            </div>
          </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="flex flex-col gap-3">
          <button @click="verTodasSolicitudes" class="rounded-2xl p-3.5 flex items-center gap-3 text-left transition-all active:scale-95" style="background:#e8ecf1; box-shadow: 6px 6px 12px #c5c9d0, -6px -6px 12px #ffffff;">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#d3f9d8; box-shadow: inset 3px 3px 6px #b4dab9, inset -3px -3px 6px #f2fff4;">
              <component :is="ClipboardListIcon" class="w-4 h-4" style="color:#2f9e44;" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-xs" style="color:#1e2d55;">Mis solicitudes</p>
              <p class="text-[11px] mt-0.5" style="color:#8a9ab5;">Ver historial completo</p>
            </div>
            <component :is="ChevronRightIcon" class="w-4 h-4 shrink-0" style="color:#c5c9d0;" />
          </button>
          <div class="rounded-2xl p-3.5 flex items-center gap-3" style="background:#e8ecf1; box-shadow: 6px 6px 12px #c5c9d0, -6px -6px 12px #ffffff;">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#fff3cd; box-shadow: inset 3px 3px 6px #e0d5a8, inset -3px -3px 6px #fffff5;">
              <component :is="BuildingIcon" class="w-4 h-4" style="color:#e67700;" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-xs" style="color:#1e2d55;">Mi institución</p>
              <p class="text-[11px] mt-0.5 truncate" style="color:#8a9ab5;">NIT {{ clinicaAuth.clinica?.nit }}</p>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0" style="background:#d3f9d8; color:#2f9e44;">
              <span class="w-1.5 h-1.5 rounded-full" style="background:#2f9e44;"></span>Activa
            </span>
          </div>
        </div>

      </div>

      <!-- Columna derecha: Solicitudes recientes -->
      <div class="lg:col-span-3 rounded-2xl overflow-hidden flex flex-col min-h-0 anim-fade-right" style="background:#e8ecf1; box-shadow: 8px 8px 16px #c5c9d0, -8px -8px 16px #ffffff; animation-delay:0.4s">
        <div class="px-5 py-3.5 flex items-center justify-between shrink-0" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
          <h2 class="font-bold text-sm" style="color:#1e2d55;">Solicitudes recientes</h2>
          <span class="text-xs" style="color:#8a9ab5;">{{ solicitudes.length }} solicitudes</span>
        </div>

        <div v-if="cargando" class="p-4 space-y-3">
          <div v-for="i in 4" :key="i" class="h-11 rounded-xl animate-pulse" style="background:#d8dce3;" />
        </div>

        <div v-else-if="solicitudes.length === 0" class="flex-1 flex flex-col items-center justify-center py-8">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3" style="background:#e8ecf1; box-shadow: 5px 5px 10px #c5c9d0, -5px -5px 10px #ffffff;">
            <component :is="ClipboardListIcon" class="w-6 h-6" style="color:#c5c9d0;" />
          </div>
          <p class="font-semibold text-sm mb-1" style="color:#1e2d55;">Sin solicitudes aún</p>
          <p class="text-xs" style="color:#8a9ab5;">Las solicitudes que envíe aparecerán aquí</p>
        </div>

        <div v-else class="flex-1 overflow-y-auto">
          <div
            v-for="(sol, idx) in solicitudes.slice(0, 10)"
            :key="sol.id"
            class="px-5 py-3 flex items-center gap-3 cursor-pointer transition-all"
            :style="{ borderTop: idx > 0 ? '1px solid rgba(0,0,0,0.04)' : 'none' }"
            @click="verDetalle(sol)"
          >
            <div class="w-2 h-2 rounded-full shrink-0"
              :style="{
                background: sol.estado === 'pendiente' ? '#e67700' : sol.estado === 'aceptado' ? '#2f9e44' : '#c92a2a',
                boxShadow: `0 0 5px ${sol.estado === 'pendiente' ? '#e6770055' : sol.estado === 'aceptado' ? '#2f9e4455' : '#c92a2a55'}`
              }"
            ></div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold truncate" style="color:#1e2d55;">{{ sol.primer_nombre }} {{ sol.primer_apellido }}</p>
              <p class="text-[11px] truncate mt-0.5" style="color:#8a9ab5;">{{ sol.especialidad_requerida }} · {{ sol.eps }}</p>
            </div>
            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full shrink-0"
              :style="{
                background: sol.estado === 'pendiente' ? '#fff3cd' : sol.estado === 'aceptado' ? '#d3f9d8' : '#ffe0e0',
                color: sol.estado === 'pendiente' ? '#e67700' : sol.estado === 'aceptado' ? '#2f9e44' : '#c92a2a',
              }"
            >{{ sol.estado === 'pendiente' ? 'Pendiente' : sol.estado === 'aceptado' ? 'Aceptada' : 'Negada' }}</span>
            <p class="text-[11px] shrink-0 hidden sm:block" style="color:#8a9ab5;">{{ formatFecha(sol.created_at) }}</p>
            <component :is="ChevronRightIcon" class="w-3.5 h-3.5 shrink-0" style="color:#c5c9d0;" />
          </div>
        </div>
      </div>
    </div>

    <!-- ── Modales ──────────────────────────────────────────────────────── -->

    <!-- ── Drawer: Nueva Solicitud ──────────────────────────────────────── -->
    <el-drawer
      v-model="drawerVisible"
      title="Nueva Solicitud de Referencia"
      direction="rtl"
      size="600px"
      :close-on-click-modal="false"
      :destroy-on-close="true"
    >
      <div class="px-1 pb-6">
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default">

          <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-[#0D2D6B] text-white text-xs flex items-center justify-center font-bold shrink-0">1</div>
              <p class="font-semibold text-gray-700 text-sm">Datos del paciente</p>
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
              <el-form-item label="Fecha" prop="fecha" required>
                <el-date-picker v-model="form.fecha" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" class="w-full" placeholder="Seleccione" />
              </el-form-item>
              <el-form-item label="Hora" prop="hora" required>
                <el-time-picker v-model="form.hora" format="HH:mm" value-format="HH:mm" class="w-full" placeholder="HH:MM" />
              </el-form-item>
              <el-form-item label="Primer nombre" prop="primer_nombre" required>
                <el-input v-model="form.primer_nombre" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Segundo nombre">
                <el-input v-model="form.segundo_nombre" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Primer apellido" prop="primer_apellido" required>
                <el-input v-model="form.primer_apellido" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Segundo apellido">
                <el-input v-model="form.segundo_apellido" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Tipo de documento" prop="tipo_documento" required>
                <el-select v-model="form.tipo_documento" class="w-full" placeholder="Tipo">
                  <el-option v-for="t in TIPOS_DOCUMENTO" :key="t" :label="t" :value="t" />
                </el-select>
              </el-form-item>
              <el-form-item label="Número de documento" prop="numero_documento" required>
                <el-input v-model="form.numero_documento" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Edad" prop="edad" required>
                <el-input-number v-model="form.edad" :min="0" :max="120" class="w-full" />
              </el-form-item>
              <el-form-item label="Género" prop="genero" required>
                <el-select v-model="form.genero" class="w-full" placeholder="Seleccione">
                  <el-option label="Masculino" value="M" />
                  <el-option label="Femenino" value="F" />
                </el-select>
              </el-form-item>
            </div>
            <el-form-item label="EPS / Aseguradora" prop="eps" required>
              <el-select v-model="form.eps" filterable class="w-full" placeholder="Seleccione o escriba">
                <el-option v-for="e in EPS_LIST" :key="e" :label="e" :value="e" />
              </el-select>
            </el-form-item>
          </div>

          <el-divider />

          <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-[#0D2D6B] text-white text-xs flex items-center justify-center font-bold shrink-0">2</div>
              <p class="font-semibold text-gray-700 text-sm">Datos de la remisión</p>
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
              <el-form-item label="Diagnóstico (CIE-10)" prop="diagnostico" required class="col-span-2">
                <el-input v-model="form.diagnostico" placeholder="Ej: J18.9 Neumonía no especificada" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Municipio de origen" prop="municipio_capita" required>
                <el-input v-model="form.municipio_capita" autocomplete="off" />
              </el-form-item>
              <el-form-item label="Especialidad requerida" prop="especialidad_requerida" required>
                <el-select v-model="form.especialidad_requerida" filterable class="w-full" placeholder="Seleccione">
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
              <el-form-item label="Condición especial" class="col-span-2">
                <el-input v-model="form.condicion_especial" placeholder="Ej: Paciente con discapacidad, obesidad mórbida..." autocomplete="off" />
              </el-form-item>
            </div>
          </div>

          <el-divider />

          <div class="mb-5">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 rounded-full bg-[#0D2D6B] text-white text-xs flex items-center justify-center font-bold shrink-0">3</div>
              <p class="font-semibold text-gray-700 text-sm">Resumen clínico</p>
            </div>
            <el-form-item label="Resumen de la historia clínica" prop="resumen_historia_clinica" required>
              <el-input v-model="form.resumen_historia_clinica" type="textarea" :rows="5"
                placeholder="Describa el motivo de remisión, antecedentes relevantes, estado actual del paciente..." />
            </el-form-item>
            <el-form-item label="Observaciones adicionales">
              <el-input v-model="form.observaciones" type="textarea" :rows="2" placeholder="Información adicional relevante..." />
            </el-form-item>
          </div>

        </el-form>

        <div class="flex gap-3 pt-2 border-t border-gray-100">
          <el-button class="flex-1" @click="drawerVisible = false">Cancelar</el-button>
          <el-button type="primary" class="flex-1 !bg-[#0D2D6B]" :loading="guardando" @click="guardar">
            Enviar solicitud
          </el-button>
        </div>
      </div>
    </el-drawer>

    <!-- ── Modal: Detalle ──────────────────────────────────────────────── -->
    <el-dialog v-model="modalDetalle" title="Detalle de solicitud" width="580px" class="rounded-2xl">
      <template v-if="solicitudSeleccionada">
        <div class="space-y-5 text-sm">

          <div
            class="flex items-center justify-between p-4 rounded-xl border"
            :class="{
              'bg-amber-50 border-amber-100': solicitudSeleccionada.estado === 'pendiente',
              'bg-emerald-50 border-emerald-100': solicitudSeleccionada.estado === 'aceptado',
              'bg-red-50 border-red-100': solicitudSeleccionada.estado === 'negado',
            }"
          >
            <div class="flex items-center gap-2.5">
              <component
                :is="solicitudSeleccionada.estado === 'aceptado' ? CheckCircleIcon : solicitudSeleccionada.estado === 'negado' ? XCircleIcon : ClockIcon"
                class="w-5 h-5 shrink-0"
                :class="{
                  'text-amber-500': solicitudSeleccionada.estado === 'pendiente',
                  'text-emerald-600': solicitudSeleccionada.estado === 'aceptado',
                  'text-red-500': solicitudSeleccionada.estado === 'negado',
                }"
              />
              <span class="font-bold"
                :class="{
                  'text-amber-700': solicitudSeleccionada.estado === 'pendiente',
                  'text-emerald-700': solicitudSeleccionada.estado === 'aceptado',
                  'text-red-600': solicitudSeleccionada.estado === 'negado',
                }"
              >
                {{ solicitudSeleccionada.estado === 'pendiente' ? 'Pendiente de respuesta' : solicitudSeleccionada.estado === 'aceptado' ? 'Solicitud aceptada' : 'Solicitud negada' }}
              </span>
            </div>
            <span v-if="solicitudSeleccionada.codigo_aceptacion"
              class="font-mono text-emerald-700 font-extrabold bg-white px-3 py-1.5 rounded-lg border border-emerald-200 shadow-sm"
            >
              {{ solicitudSeleccionada.codigo_aceptacion }}
            </span>
          </div>

          <div>
            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Paciente</p>
            <div class="grid grid-cols-2 gap-3 bg-slate-50 rounded-xl p-4">
              <div><p class="text-[10px] text-slate-400 mb-0.5">Nombre completo</p><p class="font-semibold text-slate-800">{{ solicitudSeleccionada.primer_nombre }} {{ solicitudSeleccionada.segundo_nombre }} {{ solicitudSeleccionada.primer_apellido }} {{ solicitudSeleccionada.segundo_apellido }}</p></div>
              <div><p class="text-[10px] text-slate-400 mb-0.5">Documento</p><p>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</p></div>
              <div><p class="text-[10px] text-slate-400 mb-0.5">Edad / Género</p><p>{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masculino' : 'Femenino' }}</p></div>
              <div><p class="text-[10px] text-slate-400 mb-0.5">EPS</p><p>{{ solicitudSeleccionada.eps }}</p></div>
              <div class="col-span-2"><p class="text-[10px] text-slate-400 mb-0.5">Diagnóstico</p><p>{{ solicitudSeleccionada.diagnostico }}</p></div>
            </div>
          </div>

          <div>
            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Remisión</p>
            <div class="grid grid-cols-2 gap-3 bg-slate-50 rounded-xl p-4">
              <div><p class="text-[10px] text-slate-400 mb-0.5">Municipio origen</p><p>{{ solicitudSeleccionada.municipio_capita }}</p></div>
              <div><p class="text-[10px] text-slate-400 mb-0.5">Especialidad</p><p>{{ solicitudSeleccionada.especialidad_requerida }}</p></div>
              <div><p class="text-[10px] text-slate-400 mb-0.5">Servicio actual</p><p>{{ solicitudSeleccionada.servicio_ubicacion_actual }}</p></div>
              <div v-if="solicitudSeleccionada.servicio_remision"><p class="text-[10px] text-slate-400 mb-0.5">Servicio destino</p><p>{{ solicitudSeleccionada.servicio_remision }}</p></div>
            </div>
          </div>

          <div>
            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-2">Historia clínica</p>
            <p class="text-slate-700 bg-slate-50 rounded-xl p-4 leading-relaxed">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
          </div>

          <div v-if="solicitudSeleccionada.nombre_quien_responde" class="bg-blue-50 rounded-xl p-4 border border-blue-100">
            <p class="text-[10px] font-extrabold text-blue-600 uppercase tracking-widest mb-2">Respuesta del equipo de referencia</p>
            <p class="text-xs text-blue-700 mb-1">Respondió: <strong>{{ solicitudSeleccionada.nombre_quien_responde }}</strong> · {{ solicitudSeleccionada.hora_respuesta }}</p>
            <p v-if="solicitudSeleccionada.observaciones_respuesta" class="text-xs text-blue-600 mt-1">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
          </div>

          <div v-if="solicitudSeleccionada.motivo_negacion" class="bg-red-50 rounded-xl p-4 border border-red-100">
            <p class="text-[10px] font-extrabold text-red-500 uppercase tracking-widest mb-2">Motivo de negación</p>
            <p class="text-slate-700">{{ solicitudSeleccionada.motivo_negacion }}</p>
          </div>

        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { ElMessage } from 'element-plus';
import {
  Plus as PlusIcon,
  ClipboardList as ClipboardListIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  ChevronRight as ChevronRightIcon,
  RefreshCw as RefreshCwIcon,
  Sun as SunIcon,
  Sunset as SunsetIcon,
  Moon as MoonIcon,
  FilePlus as FilePlusIcon,
  Building2 as BuildingIcon,
  Hospital as HospitalIcon,
  Hash as HashIcon,
  MapPin as MapPinIcon,
  Phone as PhoneIcon,
  ShieldCheck as ShieldCheckIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { TIPOS_DOCUMENTO, EPS_LIST, ESPECIALIDADES, SERVICIOS } from '@/data/referencia';

const clinicaAuth = useClinicaAuthStore();
const solicitudes = ref<any[]>([]);
const cargando = ref(false);
const drawerVisible = ref(false);
const guardando = ref(false);
const formRef = ref();
const modalDetalle = ref(false);
const solicitudSeleccionada = ref<any>(null);

// ── Stat cards config ───────────────────────────────────────────────────────
const statCards = [
  { gradient: 'linear-gradient(135deg,#1a73e8,#0d47a1)', icon: ClipboardListIcon, label: 'Solicitudes', sub: (n: number) => 'total enviadas' },
  { gradient: 'linear-gradient(135deg,#f59f00,#e67700)',  icon: ClockIcon,         label: 'Pendientes',  sub: (n: number) => 'en espera' },
  { gradient: 'linear-gradient(135deg,#40c057,#2f9e44)',  icon: CheckCircleIcon,   label: 'Aceptadas',   sub: (n: number) => 'aprobadas' },
  { gradient: 'linear-gradient(135deg,#f03e3e,#c92a2a)',  icon: XCircleIcon,       label: 'Negadas',     sub: (n: number) => n > 0 ? `${n}% del total` : '0% del total' },
];

// Contador animado
const displayStats = ref([0, 0, 0, 0]);

function animateCounters(targets: number[]) {
  const duration = 900;
  const steps = 40;
  const interval = duration / steps;
  let step = 0;
  const timer = setInterval(() => {
    step++;
    const progress = step / steps;
    const ease = 1 - Math.pow(1 - progress, 3);
    displayStats.value = targets.map(t => Math.round(t * ease));
    if (step >= steps) {
      displayStats.value = [...targets];
      clearInterval(timer);
    }
  }, interval);
}

const hora = new Date().getHours();
const saludoTexto = hora < 12 ? 'Buenos días' : hora < 19 ? 'Buenas tardes' : 'Buenas noches';
const saludoIcono = hora < 12 ? SunIcon : hora < 19 ? SunsetIcon : MoonIcon;

const fechaHoy = new Date().toLocaleDateString('es-CO', {
  weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
});

const stats = computed(() => ({
  total: solicitudes.value.length,
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

function emptyForm() {
  const now = new Date();
  return {
    fecha: now.toISOString().slice(0, 10),
    hora: now.toTimeString().slice(0, 5),
    primer_nombre: '', segundo_nombre: '', primer_apellido: '', segundo_apellido: '',
    genero: '', edad: null as number | null, tipo_documento: '', numero_documento: '',
    eps: '', diagnostico: '', municipio_capita: '', especialidad_requerida: '',
    servicio_ubicacion_actual: '', servicio_remision: '', via_contacto: '',
    gestante: null as boolean | null, condicion_especial: '',
    resumen_historia_clinica: '', observaciones: '',
  };
}

const form = ref(emptyForm());

const rules = {
  fecha: [{ required: true, message: 'Requerido', trigger: 'change' }],
  hora: [{ required: true, message: 'Requerido', trigger: 'change' }],
  primer_nombre: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  primer_apellido: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  genero: [{ required: true, message: 'Requerido', trigger: 'change' }],
  edad: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  tipo_documento: [{ required: true, message: 'Requerido', trigger: 'change' }],
  numero_documento: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  eps: [{ required: true, message: 'Requerido', trigger: 'change' }],
  diagnostico: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  municipio_capita: [{ required: true, message: 'Requerido', trigger: 'blur' }],
  especialidad_requerida: [{ required: true, message: 'Requerido', trigger: 'change' }],
  servicio_ubicacion_actual: [{ required: true, message: 'Requerido', trigger: 'change' }],
  resumen_historia_clinica: [{ required: true, message: 'Requerido', trigger: 'blur' }],
};

async function cargar() {
  cargando.value = true;
  try {
    const { data } = await http.get('/api/externo/solicitudes');
    solicitudes.value = data.data;
  } catch { ElMessage.error('Error al cargar solicitudes'); }
  finally { cargando.value = false; }
}

function abrirFormulario() { form.value = emptyForm(); drawerVisible.value = true; }
function verTodasSolicitudes() { /* scroll o tab futuro */ }
function verDetalle(sol: any) { solicitudSeleccionada.value = sol; modalDetalle.value = true; }
async function logout() { await clinicaAuth.logout(); }

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

async function guardar() {
  try { await formRef.value?.validate(); }
  catch { ElMessage.error('Por favor complete todos los campos requeridos'); return; }
  guardando.value = true;
  try {
    await http.post('/api/externo/solicitudes', form.value);
    ElMessage.success('Solicitud enviada correctamente');
    drawerVisible.value = false;
    await cargar();
  } catch (e: any) {
    const errors = e.response?.data?.data?.errors || e.response?.data?.errors;
    ElMessage.error(errors ? Object.values(errors).flat().join('\n') : 'Error al enviar la solicitud');
  } finally { guardando.value = false; }
}

watch(stats, (s) => {
  animateCounters([s.total, s.pendientes, s.aceptadas, s.negadas]);
});

onMounted(cargar);
</script>

<style scoped>
/* ── Entradas ── */
.anim-fade-down  { animation: fadeDown  0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-slide-up   { animation: slideUp   0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-fade-left  { animation: fadeLeft  0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-fade-right { animation: fadeRight 0.5s cubic-bezier(.22,1,.36,1) both; }

@keyframes fadeDown  { from { opacity:0; transform:translateY(-18px); } to { opacity:1; transform:none; } }
@keyframes slideUp   { from { opacity:0; transform:translateY(20px);  } to { opacity:1; transform:none; } }
@keyframes fadeLeft  { from { opacity:0; transform:translateX(-20px); } to { opacity:1; transform:none; } }
@keyframes fadeRight { from { opacity:0; transform:translateX(20px);  } to { opacity:1; transform:none; } }

/* ── Hover tarjetas stat ── */
.stat-card-hover {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: default;
}
.stat-card-hover:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 10px 10px 20px #c5c9d0, -10px -10px 20px #ffffff !important;
}

/* ── Pulso en íconos ── */
.icon-pulse {
  animation: pulse 2.5s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.25); }
  50%       { box-shadow: 0 0 0 6px rgba(255,255,255,0); }
}

/* ── Contador ── */
.counter {
  transition: all 0.1s ease;
  font-variant-numeric: tabular-nums;
}
</style>
