<template>
  <div class="external-dashboard h-full flex flex-col gap-2 p-2 sm:p-3 overflow-hidden">

    <!-- ── Saludo ──────────────────────────────────────────────────────── -->
    <div class="welcome-panel p-3 flex items-center gap-3 anim-fade-down">
      <div class="welcome-icon w-12 h-12 rounded-2xl flex items-center justify-center shrink-0">
        <component :is="saludoIcono" class="w-5 h-5" style="color:#1e2d55;" />
      </div>
      <div class="flex-1 min-w-0">
        <p class="welcome-eyebrow">Centro de referencia</p>
        <h1 class="text-lg sm:text-xl font-bold leading-tight" style="color:#0d2d5e;">{{ saludoTexto }}, {{ clinicaAuth.clinica?.nombre }}</h1>
        <p class="text-xs capitalize mt-1" style="color:#64748b;">{{ fechaHoy }}</p>
      </div>
      <button
        @click="abrirFormulario"
        class="inline-flex items-center gap-2 font-semibold text-sm px-4 py-2.5 rounded-xl transition-all duration-150 active:scale-95 shrink-0"
        style="background:linear-gradient(135deg,#0d2d5e,#21549a); color:#fff; box-shadow:0 10px 20px rgba(13,45,94,.22);"
      >
        <component :is="PlusIcon" class="w-4 h-4" />
        Nueva solicitud
      </button>
    </div>

    <!-- ── Tarjetas de estadísticas ───────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 shrink-0">

      <div v-for="(card, i) in statCards" :key="i"
        class="rounded-2xl p-2.5 flex items-center gap-2.5 overflow-hidden anim-slide-up stat-card-hover"
        :style="{ background: card.gradient, animationDelay: (i * 0.08) + 's' }"
      >
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 icon-pulse" style="background:rgba(255,255,255,0.18);">
          <component :is="card.icon" class="w-5 h-5 text-white" />
        </div>
        <div>
          <p class="text-2xl font-extrabold leading-none text-white counter">{{ displayStats[i] }}</p>
          <p class="text-[11px] font-bold uppercase tracking-wide text-white/80 mt-0.5">{{ card.label }}</p>
          <p class="text-[10px] text-white/60 mt-0.5">{{ card.sub(displayStats[i]) }}</p>
          <p class="text-[10px] font-semibold text-white/80 mt-1">{{ card.context(displayStats[i]) }}</p>
        </div>
      </div>
    </div>

    <!-- ── Fila inferior: dos columnas ──────────────────────────────── -->
    <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-5 gap-2">

      <!-- Columna izquierda: Estado + Accesos rápidos -->
      <div class="lg:col-span-2 flex flex-col gap-2 min-h-0 anim-fade-left" style="animation-delay:0.3s">

        <!-- Estado de solicitudes -->
        <div class="content-panel rounded-2xl p-3 flex-1 flex flex-col">
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
          <button @click="verTodasSolicitudes" class="quick-link rounded-2xl p-2.5 flex items-center gap-3 text-left transition-all active:scale-95">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:#d3f9d8; box-shadow: inset 3px 3px 6px #b4dab9, inset -3px -3px 6px #f2fff4;">
              <component :is="ClipboardListIcon" class="w-4 h-4" style="color:#2f9e44;" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-bold text-xs" style="color:#1e2d55;">Mis solicitudes</p>
              <p class="text-[11px] mt-0.5" style="color:#8a9ab5;">Ver historial completo</p>
            </div>
            <component :is="ChevronRightIcon" class="w-4 h-4 shrink-0" style="color:#c5c9d0;" />
          </button>
          <div class="quick-link rounded-2xl p-2.5 flex items-center gap-3">
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
      <div class="content-panel lg:col-span-3 rounded-2xl overflow-hidden flex flex-col min-h-0 anim-fade-right" style="animation-delay:0.4s">
        <div class="px-5 py-3.5 flex items-center justify-between shrink-0" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
          <h2 class="font-bold text-sm" style="color:#1e2d55;">Solicitudes recientes</h2>
          <span class="text-xs" style="color:#8a9ab5;">{{ solicitudes.length }} solicitudes</span>
        </div>

        <div v-if="cargando" class="p-4 space-y-3">
          <div v-for="i in 4" :key="i" class="h-11 rounded-xl animate-pulse" style="background:#d8dce3;" />
        </div>

        <div v-else-if="solicitudes.length === 0" class="flex-1 flex flex-col items-center justify-center py-8 text-center">
          <div class="empty-state-icon w-12 h-12 rounded-2xl flex items-center justify-center mb-3">
            <component :is="ClipboardListIcon" class="w-6 h-6" />
          </div>
          <p class="font-semibold text-sm mb-1" style="color:#0d2d5e;">Aún no hay solicitudes</p>
          <p class="text-xs max-w-[240px]" style="color:#64748b;">Crea la primera remisión para iniciar el seguimiento con el equipo de referencia.</p>
          <button class="empty-state-action mt-3" @click="abrirFormulario"><component :is="PlusIcon" class="w-3.5 h-3.5" /> Crear primera solicitud</button>
        </div>

        <div v-else class="flex-1 overflow-y-auto">
          <div
            v-for="(sol, idx) in solicitudes.slice(0, 10)"
            :key="sol.id"
            class="px-5 py-3 flex items-center gap-3 cursor-pointer transition-all"
            :style="{ borderTop: idx > 0 ? '1px solid rgba(0,0,0,0.04)' : 'none' }"
            @click="verDetalle(sol)"
          >
            <div class="request-avatar" :class="sol.estado">{{ initialesPaciente(sol) }}</div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-semibold truncate" style="color:#1e2d55;">{{ sol.primer_nombre }} {{ sol.primer_apellido }}</p>
              <p class="text-[11px] truncate mt-0.5" style="color:#64748b;">{{ sol.especialidad_requerida }} · {{ sol.eps }}</p>
              <p class="text-[10px] mt-1 font-medium" style="color:#94a3b8;">{{ tiempoRelativo(sol.created_at) }}</p>
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
    <el-dialog
      v-model="drawerVisible"
      title="Nueva Solicitud de Referencia"
      width="760px"
      class="request-dialog"
      :close-on-click-modal="false"
      :destroy-on-close="true"
      align-center
    >
      <div class="request-form px-1 pb-6">
        <div class="form-hero mb-5">
          <div class="form-hero-icon"><component :is="ClipboardListIcon" class="w-5 h-5" /></div>
          <div><p>Nueva remisión</p><span>Complete la información para que el equipo de referencia pueda gestionar el caso.</span></div>
        </div>
        <div class="stepper mb-6">
          <div v-for="step in formSteps" :key="step.number" class="stepper-item" :class="{ active: pasoFormulario >= step.number }">
            <span class="stepper-number">{{ pasoFormulario > step.number ? '✓' : step.number }}</span>
            <p>{{ step.label }}</p>
          </div>
        </div>
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default">

          <div v-show="pasoFormulario === 1" class="mb-5">
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

          <div v-show="pasoFormulario === 2" class="mb-5">
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

          <div v-show="pasoFormulario === 3" class="mb-5">
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
            <div class="review-card mb-4">
              <p class="review-title">Revise antes de enviar</p>
              <div class="grid grid-cols-2 gap-3 text-xs">
                <div><span>Paciente</span><strong>{{ form.primer_nombre }} {{ form.primer_apellido }}</strong></div>
                <div><span>Documento</span><strong>{{ form.tipo_documento }} {{ form.numero_documento }}</strong></div>
                <div><span>Especialidad</span><strong>{{ form.especialidad_requerida }}</strong></div>
                <div><span>Servicio actual</span><strong>{{ form.servicio_ubicacion_actual }}</strong></div>
              </div>
            </div>
            <div class="attachments-card rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
              <div class="flex items-center gap-2"><span class="attachments-icon">+</span><p class="text-sm font-semibold text-slate-700">Soportes clínicos</p></div>
              <p class="text-xs text-slate-500 mt-1">Adjunte resultados, órdenes o documentos relevantes. Hasta 5 archivos de 10 MB.</p>
              <input class="mt-3 block w-full text-sm text-slate-600" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple @change="seleccionarAdjuntos" />
              <ul v-if="adjuntos.length" class="mt-2 space-y-1 text-xs text-slate-600">
                <li v-for="archivo in adjuntos" :key="archivo.name">{{ archivo.name }}</li>
              </ul>
            </div>
          </div>

        </el-form>

        <div class="flex gap-3 pt-4 border-t border-gray-100">
          <el-button class="flex-1" @click="pasoFormulario === 1 ? drawerVisible = false : pasoFormulario--">{{ pasoFormulario === 1 ? 'Cancelar' : 'Anterior' }}</el-button>
          <el-button v-if="pasoFormulario < 3" type="primary" class="flex-1 !bg-[#0D2D6B]" @click="avanzarPaso">Continuar</el-button>
          <el-button v-else type="primary" class="flex-1 !bg-[#0D2D6B]" :loading="guardando" @click="guardar">Enviar solicitud</el-button>
        </div>
      </div>
    </el-dialog>

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

          <div v-if="solicitudSeleccionada.eventos?.length" class="space-y-2">
            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Seguimiento</p>
            <div v-for="evento in solicitudSeleccionada.eventos" :key="evento.id" class="flex gap-3 rounded-xl bg-slate-50 p-3">
              <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-[#0D2D6B]"></div>
              <div><p class="text-sm font-semibold text-slate-700">{{ evento.titulo }}</p><p v-if="evento.descripcion" class="text-xs text-slate-500">{{ evento.descripcion }}</p><p class="mt-1 text-[10px] text-slate-400">{{ formatFecha(evento.created_at) }}</p></div>
            </div>
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
const adjuntos = ref<File[]>([]);
const pasoFormulario = ref(1);
const formSteps = [
  { number: 1, label: 'Paciente' },
  { number: 2, label: 'Remisión' },
  { number: 3, label: 'Resumen' },
];

// ── Stat cards config ───────────────────────────────────────────────────────
const statCards = [
  { gradient: 'linear-gradient(135deg,#1a73e8,#0d47a1)', icon: ClipboardListIcon, label: 'Solicitudes', sub: () => 'total enviadas', context: (n: number) => n ? 'Seguimiento activo' : 'Sin actividad aún' },
  { gradient: 'linear-gradient(135deg,#f59f00,#e67700)', icon: ClockIcon, label: 'Pendientes', sub: () => 'en espera', context: (n: number) => n ? 'Requieren seguimiento' : 'Sin pendientes' },
  { gradient: 'linear-gradient(135deg,#40c057,#2f9e44)', icon: CheckCircleIcon, label: 'Aceptadas', sub: () => 'aprobadas', context: (n: number) => n ? 'Con respuesta recibida' : 'Sin respuestas aún' },
  { gradient: 'linear-gradient(135deg,#f03e3e,#c92a2a)', icon: XCircleIcon, label: 'Negadas', sub: (n: number) => n ? `${n} del total` : 'sin novedades', context: (n: number) => n ? 'Revise los motivos' : 'Operación estable' },
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

function abrirFormulario() { form.value = emptyForm(); adjuntos.value = []; pasoFormulario.value = 1; drawerVisible.value = true; }
function seleccionarAdjuntos(event: Event) { adjuntos.value = Array.from((event.target as HTMLInputElement).files ?? []).slice(0, 5); }

async function avanzarPaso(): Promise<void> {
  const camposPorPaso = [
    ['fecha', 'hora', 'primer_nombre', 'primer_apellido', 'genero', 'edad', 'tipo_documento', 'numero_documento', 'eps'],
    ['diagnostico', 'municipio_capita', 'especialidad_requerida', 'servicio_ubicacion_actual'],
  ];

  try {
    await formRef.value?.validateField(camposPorPaso[pasoFormulario.value - 1]);
    pasoFormulario.value++;
  } catch {
    ElMessage.warning('Complete los campos requeridos para continuar');
  }
}

function verTodasSolicitudes() { /* scroll o tab futuro */ }
function verDetalle(sol: any) { solicitudSeleccionada.value = sol; modalDetalle.value = true; }
async function logout() { await clinicaAuth.logout(); }

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function initialesPaciente(solicitud: any): string {
  return `${solicitud.primer_nombre?.[0] ?? ''}${solicitud.primer_apellido?.[0] ?? ''}`.toUpperCase();
}

function tiempoRelativo(fecha: string): string {
  const minutos = Math.max(1, Math.round((Date.now() - new Date(fecha).getTime()) / 60000));
  if (minutos < 60) return `Hace ${minutos} min`;
  const horas = Math.round(minutos / 60);
  if (horas < 24) return `Hace ${horas} h`;
  return `Hace ${Math.round(horas / 24)} días`;
}

async function guardar() {
  try { await formRef.value?.validate(); }
  catch { ElMessage.error('Por favor complete todos los campos requeridos'); return; }
  guardando.value = true;
  try {
    const payload = new FormData();
    Object.entries(form.value).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
      }
    });
    adjuntos.value.forEach((archivo) => payload.append('adjuntos[]', archivo));
    await http.post('/api/externo/solicitudes', payload);
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
:deep(.request-dialog) {
  max-width: calc(100vw - 2rem);
  overflow: hidden;
  border-radius: 22px;
  box-shadow: 0 28px 70px rgba(11, 35, 73, .28);
}

:deep(.request-dialog .el-dialog__header) {
  position: relative;
  margin: .9rem .9rem 0;
  padding: 1.05rem 1.35rem 1.05rem 4.2rem;
  color: #fff;
  background:
    radial-gradient(circle at 90% 10%, rgba(77, 132, 202, .3), transparent 28%),
    linear-gradient(115deg, #081f45, #0d2d6b 58%, #16468e);
  border: 1px solid rgba(255,255,255,.3);
  border-radius: 18px 18px 7px 18px;
  box-shadow: 0 10px 20px rgba(12, 62, 116, .22);
  font-size: 1rem;
  font-weight: 800;
}

:deep(.request-dialog .el-dialog__header::before) {
  position: absolute;
  top: 50%;
  left: 1.25rem;
  width: 2.15rem;
  height: 2.15rem;
  border-radius: 11px;
  background: linear-gradient(135deg, #3c72b4, #16468e);
  box-shadow: 0 6px 14px rgba(4, 31, 69, .35);
  content: '✚';
  color: #fff;
  font-size: 1rem;
  line-height: 2.15rem;
  text-align: center;
  transform: translateY(-50%);
}

:deep(.request-dialog .el-dialog__title) { color: #fff; font-weight: 800; letter-spacing: .01em; }
:deep(.request-dialog .el-dialog__headerbtn .el-dialog__close) { color: #e1f7ff; }
:deep(.request-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #fff; }
:deep(.request-dialog .el-dialog__body) { max-height: min(72vh, 690px); padding: 1.25rem 1.5rem; overflow-y: auto; background: linear-gradient(180deg, #f7faff, #fff); }
:deep(.el-overlay) { background-color: rgba(8, 27, 58, .56); backdrop-filter: blur(4px); }
:deep(.request-form .el-form-item__label) { color: #334e70; font-size: .75rem; font-weight: 700; }
:deep(.request-form .el-input__wrapper),
:deep(.request-form .el-select__wrapper),
:deep(.request-form .el-textarea__inner) {
  box-shadow: 0 0 0 1px #dce7f2 inset;
  border-radius: 10px;
  background: #fff;
}
:deep(.request-form .el-input__wrapper:hover),
:deep(.request-form .el-select__wrapper:hover) { box-shadow: 0 0 0 1px #86b4e8 inset; }

.form-hero {
  display: flex;
  gap: .8rem;
  align-items: center;
  padding: 1rem;
  border: 1px solid #d9e9fb;
  border-radius: 15px;
  background: linear-gradient(120deg, #eaf4ff, #f8fbff);
}
.form-hero-icon {
  display: grid;
  width: 2.5rem;
  height: 2.5rem;
  place-items: center;
  border-radius: 12px;
  color: #fff;
  background: linear-gradient(135deg, #0d2d5e, #3174c4);
  box-shadow: 0 6px 12px rgba(13, 45, 94, .2);
}
.form-hero p { margin: 0 0 .15rem; color: #0d2d5e; font-size: .9rem; font-weight: 800; }
.form-hero span { color: #61748d; font-size: .72rem; line-height: 1.35; }

.attachments-card { border-color: #bcd7f3; background: linear-gradient(135deg, #f4faff, #fbfdff); }
.attachments-icon { display: inline-grid; width: 1.35rem; height: 1.35rem; place-items: center; color: #fff; background: #2f70bb; border-radius: 6px; font-weight: 800; }

.review-card {
  padding: .85rem;
  background: linear-gradient(135deg, #eff6ff, #f8fbff);
  border: 1px solid #d7e8fc;
  border-radius: 12px;
}

.review-title {
  margin: 0 0 .6rem;
  color: #21549a;
  font-size: .72rem;
  font-weight: 800;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.review-card span,
.review-card strong { display: block; }
.review-card span { color: #64748b; font-size: .65rem; }
.review-card strong { margin-top: .1rem; color: #1e3a5f; font-weight: 700; }

.stepper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .4rem;
  padding: .35rem;
  border: 1px solid #dbe8f5;
  border-radius: 14px;
  background: #edf5fc;
}

.stepper-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .45rem;
  min-height: 2.45rem;
  border-radius: 10px;
  color: #7890aa;
  font-size: .72rem;
  font-weight: 700;
  transition: .2s ease;
}

.stepper-item p { margin: 0; }
.stepper-number {
  display: grid;
  width: 1.45rem;
  height: 1.45rem;
  place-items: center;
  border: 1px solid #c6d7e9;
  border-radius: 999px;
  background: #fff;
  color: #7590ac;
  font-size: .68rem;
}

.stepper-item.active {
  color: #fff;
  background: linear-gradient(135deg, #0d2d5e, #2469b2);
  box-shadow: 0 5px 12px rgba(20, 74, 135, .2);
}

.stepper-item.active .stepper-number {
  border-color: rgba(255,255,255,.32);
  background: rgba(255,255,255,.18);
  color: #fff;
}

.external-dashboard {
  background:
    radial-gradient(circle at 90% 0%, rgba(180, 210, 250, .42), transparent 28rem),
    radial-gradient(circle at 2% 100%, rgba(190, 233, 216, .28), transparent 25rem),
    #eef3f8;
}

.welcome-panel,
.content-panel,
.quick-link {
  background: rgba(255, 255, 255, .78);
  border: 1px solid rgba(255, 255, 255, .92);
  box-shadow: 0 10px 28px rgba(37, 67, 105, .09), inset 0 1px 0 rgba(255, 255, 255, .95);
  backdrop-filter: blur(14px);
}

.welcome-panel {
  border-radius: 22px;
  background:
    radial-gradient(circle at right center, rgba(181, 214, 252, .56), transparent 20rem),
    rgba(255, 255, 255, .82);
}

.welcome-icon {
  background: linear-gradient(145deg, #f7fbff, #dfebf7);
  box-shadow: 5px 5px 12px rgba(54, 84, 120, .12), -5px -5px 12px #fff;
}

.welcome-eyebrow {
  margin: 0 0 .25rem;
  color: #4778b8;
  font-size: .65rem;
  font-weight: 800;
  letter-spacing: .14em;
  text-transform: uppercase;
}

.content-panel { border-radius: 18px; }

.quick-link {
  border-radius: 16px;
  cursor: pointer;
}

button.quick-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 24px rgba(37, 67, 105, .13), inset 0 1px 0 rgba(255, 255, 255, .95);
}

.stat-card-hover {
  border: 1px solid rgba(255, 255, 255, .2);
  box-shadow: 0 12px 20px rgba(33, 58, 95, .16);
}

.empty-state-icon {
  color: #2f70bb;
  background: #e4f0ff;
  box-shadow: inset 2px 2px 5px rgba(47, 112, 187, .12), inset -2px -2px 5px #fff;
}

.empty-state-action {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .5rem .75rem;
  color: #fff;
  background: #0d2d5e;
  border: 0;
  border-radius: 10px;
  font-size: .75rem;
  font-weight: 700;
}

.request-avatar {
  display: flex;
  width: 2rem;
  height: 2rem;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 10px;
  font-size: .7rem;
  font-weight: 800;
}

.request-avatar.pendiente { color: #b96800; background: #fff1d0; }
.request-avatar.aceptado { color: #18794e; background: #dff7ea; }
.request-avatar.negado { color: #bd3030; background: #ffe1e1; }

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
