<template>
  <div class="ph-dashboard h-full flex overflow-hidden">

    <!-- ══ Columna principal ═══════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col gap-2 p-3 sm:p-4 min-w-0 overflow-hidden">

      <!-- ── Hero banner ── -->
      <div class="hero-card rounded-2xl p-3 sm:p-4 flex items-center gap-4 relative overflow-hidden shrink-0 anim-fade-down">
        <div class="hero-glow"></div>
        <div class="hero-pattern"></div>
        <div class="flex-1 min-w-0 z-10">
          <div class="flex items-center gap-2 mb-1">
            <span class="hero-live-dot"></span>
            <p class="text-[10px] font-semibold uppercase tracking-wider" style="color:rgba(255,255,255,0.6);">{{ fechaHoy }}</p>
          </div>
          <h1 class="text-sm sm:text-lg font-bold leading-tight text-white">
            {{ saludoTexto }}, <span class="clinic-name font-extrabold">{{ clinicaAuth.clinica?.nombre }}</span>
          </h1>
          <p class="text-[11px] sm:text-xs mt-1" style="color:rgba(255,255,255,0.65);">Gestione sus remisiones de pacientes al centro de referencia.</p>
        </div>
        <button @click="abrirFormulario" class="hero-link shrink-0 z-10">
          <component :is="PlusIcon" class="w-5 h-5" />
          <span>Nueva solicitud</span>
        </button>
        <span class="hero-pill" style="top:12%; right:22%; background:rgba(255,255,255,0.15);"></span>
        <span class="hero-pill" style="top:55%; right:9%; background:rgba(126,179,255,0.25); width:14px; height:14px;"></span>
        <span class="hero-pill" style="bottom:14%; right:30%; background:rgba(255,255,255,0.1); width:10px; height:10px;"></span>
      </div>

      <!-- ── Tarjetas tipo "órganos" ── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 shrink-0">
        <template v-if="cargando">
          <div v-for="i in 4" :key="i" class="organ-card rounded-xl p-2.5 flex flex-col gap-1">
            <div class="flex items-center gap-2 mb-1">
              <div class="shimmer-box" style="width:32px; height:32px; border-radius:8px;"></div>
              <div class="shimmer-bar" style="width:50px; height:12px;"></div>
            </div>
            <div class="shimmer-bar" style="width:70%; height:9px;"></div>
            <div class="flex items-center gap-2 mt-auto">
              <div class="shimmer-bar flex-1" style="height:6px; border-radius:999px;"></div>
              <div class="shimmer-box" style="width:24px; height:16px; border-radius:4px;"></div>
            </div>
          </div>
        </template>
        <template v-else>
          <div
            v-for="(card, i) in statCards" :key="i"
            class="organ-card rounded-xl p-2.5 flex flex-col gap-1 anim-slide-up"
            :style="{ animationDelay: (i * 0.08) + 's' }"
          >
            <div class="flex items-center gap-2">
              <div class="organ-icon w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                :style="{ background: card.iconBackground, color: card.color }">
                <component :is="card.icon" class="w-4 h-4" />
              </div>
              <p class="text-xs font-bold" style="color:#1e2d55;">{{ card.label }}</p>
            </div>
            <p class="text-[10px]" style="color:#8a9ab5;">{{ card.sub(i === 0 ? stats.total : i === 1 ? stats.pendientes : i === 2 ? stats.aceptadas : stats.negadas) }}</p>
            <div class="flex items-center gap-2 mt-auto">
              <div class="h-1.5 rounded-full overflow-hidden flex-1" style="background:#edf2f7;">
                <div class="h-full rounded-full transition-all duration-700"
                  :style="{ width: statPercents[i] + '%', background: card.color }" />
              </div>
              <span class="text-sm font-extrabold" style="color:#16468e;">{{ displayStats[i] }}</span>
            </div>
          </div>
        </template>
      </div>

      <!-- ── Distribución de estados ── -->
      <div v-if="cargando" class="distrib-card rounded-xl p-3 flex items-center gap-4 shrink-0">
        <div class="flex-1">
          <div class="flex items-center justify-between mb-2">
            <div class="shimmer-bar" style="width:140px; height:12px;"></div>
            <div class="shimmer-bar" style="width:60px; height:10px;"></div>
          </div>
          <div class="shimmer-bar w-full" style="height:10px; border-radius:999px;"></div>
          <div class="flex items-center gap-4 mt-2.5">
            <div v-for="i in 3" :key="i" class="flex items-center gap-1.5">
              <div class="shimmer-box" style="width:10px; height:10px; border-radius:50%;"></div>
              <div class="shimmer-bar" style="width:50px; height:9px;"></div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="distrib-card rounded-xl p-3 flex items-center gap-4 shrink-0 anim-slide-up" style="animation-delay:0.15s">
        <div class="flex-1">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold" style="color:#1e2d55;">Distribución de solicitudes</p>
            <p class="text-[10px]" style="color:#8a9ab5;">{{ stats.total }} en total</p>
          </div>
          <div class="flex h-2.5 rounded-full overflow-hidden" style="background:#edf2f7;">
            <div v-if="stats.pendientes" class="distrib-segment transition-all duration-700" :style="{ width: (stats.pendientes / Math.max(1, stats.total)) * 100 + '%', background: '#fbbf24' }"
              :data-tooltip="`${stats.pendientes} pendientes (${Math.round(stats.pendientes / Math.max(1, stats.total) * 100)}%)`"></div>
            <div v-if="stats.aceptadas" class="distrib-segment transition-all duration-700" :style="{ width: (stats.aceptadas / Math.max(1, stats.total)) * 100 + '%', background: '#22c55e' }"
              :data-tooltip="`${stats.aceptadas} aceptadas (${Math.round(stats.aceptadas / Math.max(1, stats.total) * 100)}%)`"></div>
            <div v-if="stats.negadas" class="distrib-segment transition-all duration-700" :style="{ width: (stats.negadas / Math.max(1, stats.total)) * 100 + '%', background: '#f87171' }"
              :data-tooltip="`${stats.negadas} negadas (${Math.round(stats.negadas / Math.max(1, stats.total) * 100)}%)`"></div>
          </div>
          <div class="flex items-center gap-4 mt-2.5">
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full" style="background:#fbbf24;"></span>
              <span class="text-[10px] font-medium" style="color:#8a9ab5;">Pendientes <strong style="color:#d97706;">{{ stats.pendientes }}</strong></span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full" style="background:#22c55e;"></span>
              <span class="text-[10px] font-medium" style="color:#8a9ab5;">Aceptadas <strong style="color:#16a34a;">{{ stats.aceptadas }}</strong></span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="w-2.5 h-2.5 rounded-full" style="background:#f87171;"></span>
              <span class="text-[10px] font-medium" style="color:#8a9ab5;">Negadas <strong style="color:#dc2626;">{{ stats.negadas }}</strong></span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Métricas y accesos rápidos ── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 shrink-0">
        <template v-if="cargando">
          <div v-for="i in 3" :key="i" class="metric-card rounded-xl p-3 flex items-center gap-3">
            <div class="shimmer-box" style="width:44px; height:44px; border-radius:12px;"></div>
            <div class="flex-1 space-y-1.5">
              <div class="shimmer-bar" style="width:60%; height:11px;"></div>
              <div class="shimmer-bar" style="width:40%; height:9px;"></div>
            </div>
          </div>
        </template>
        <template v-else>
        <!-- Tasa de aceptación -->
        <div class="metric-card rounded-xl p-3 flex items-center gap-3 anim-slide-up" style="animation-delay:0.2s">
          <div class="metric-ring shrink-0">
            <svg class="w-14 h-14 -rotate-90" viewBox="0 0 56 56">
              <circle cx="28" cy="28" r="24" stroke="#edf2f7" stroke-width="5" fill="none" />
              <circle cx="28" cy="28" r="24" :stroke="tasaAceptacion > 60 ? '#16a34a' : tasaAceptacion > 30 ? '#d97706' : '#dc2626'" stroke-width="5" fill="none" stroke-linecap="round"
                :stroke-dasharray="150.8" :stroke-dashoffset="150.8 - (150.8 * tasaAceptacion / 100)" class="metric-ring-fill" />
            </svg>
            <span class="metric-ring-text">{{ tasaAceptacion }}%</span>
          </div>
          <div>
            <p class="text-xs font-bold" style="color:#1e2d55;">Tasa de aceptación</p>
            <p class="text-[10px] mt-0.5" style="color:#8a9ab5;">{{ stats.aceptadas }} de {{ stats.total }} solicitudes</p>
          </div>
        </div>

        <!-- Tiempo promedio -->
        <div class="metric-card rounded-xl p-3 flex items-center gap-3 anim-slide-up" style="animation-delay:0.28s">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:#e0f2fe; color:#0284c7;">
            <component :is="ClockIcon" class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-bold" style="color:#1e2d55;">Tiempo de respuesta</p>
            <p class="text-lg font-extrabold" style="color:#0284c7;">{{ tiempoPromedio }}</p>
            <p class="text-[10px] mt-0.5" style="color:#8a9ab5;">promedio del centro</p>
          </div>
        </div>

        <!-- Acceso rápido historial -->
        <div class="metric-card rounded-xl p-3 flex items-center gap-3 anim-slide-up cursor-pointer" style="animation-delay:0.36s" @click="irHistorial">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:#dbeafe; color:#2563eb;">
            <component :is="ClipboardListIcon" class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-bold" style="color:#1e2d55;">Ver historial completo</p>
            <p class="text-[10px] mt-0.5" style="color:#8a9ab5;">{{ stats.total }} solicitudes registradas</p>
          </div>
          <component :is="ChevronRightIcon" class="w-5 h-5 ml-auto shrink-0" style="color:#b0bccf;" />
        </div>
        </template>
      </div>

      <!-- ── Solicitudes recientes ── -->
      <div class="flex-1 min-h-0 flex flex-col">
        <div class="flex items-center justify-between mb-1.5 shrink-0">
          <div class="flex items-center gap-2">
            <div class="recent-header-icon">
              <component :is="ClipboardListIcon" class="w-3.5 h-3.5" />
            </div>
            <h2 class="font-bold text-sm" style="color:#1e2d55;">Solicitudes recientes</h2>
            <span v-if="!cargando && solicitudes.length > 0" class="recent-count-badge">{{ solicitudes.length }}</span>
          </div>
          <button @click="cargar" class="recent-refresh-btn">
            <component :is="RefreshCwIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': cargando }" />
            <span>Actualizar</span>
          </button>
        </div>

        <div v-if="cargando" class="space-y-1.5">
          <div v-for="i in 4" :key="i" class="skeleton-row rounded-xl px-3 py-2 flex items-center gap-3">
            <div class="shimmer-box" style="width:32px; height:32px; border-radius:8px;"></div>
            <div class="flex-1 space-y-1.5">
              <div class="shimmer-bar" style="width:45%; height:10px;"></div>
              <div class="shimmer-bar" style="width:70%; height:8px;"></div>
            </div>
            <div class="shimmer-box" style="width:48px; height:18px; border-radius:999px;"></div>
          </div>
        </div>

        <div v-else-if="solicitudes.length === 0" class="flex-1 flex flex-col items-center justify-center py-6 text-center">
          <div class="empty-state-icon w-12 h-12 rounded-2xl flex items-center justify-center mb-3">
            <component :is="ClipboardListIcon" class="w-6 h-6" />
          </div>
          <p class="font-semibold text-sm mb-1" style="color:#0d2d5e;">Aún no hay solicitudes</p>
          <p class="text-xs max-w-[240px]" style="color:#64748b;">Cree la primera remisión para iniciar el seguimiento.</p>
          <button class="empty-state-action mt-3" @click="abrirFormulario"><component :is="PlusIcon" class="w-3.5 h-3.5" /> Crear primera solicitud</button>
        </div>

        <div v-else class="flex-1 overflow-y-auto space-y-1.5 pr-1">
          <div
            v-for="(sol, idx) in solicitudes.slice(0, 3)"
            :key="sol.id"
            class="med-row rounded-xl px-3 py-2 flex items-center gap-3 cursor-pointer transition-all anim-row-in"
            :style="[
          { borderLeftColor: sol.estado === 'pendiente' ? '#fbbf24' : sol.estado === 'aceptado' ? '#22c55e' : '#f87171' },
          { animationDelay: (idx * 0.06) + 's' }
        ]"
            @click="verDetalle(sol)"
          >
            <div class="med-icon w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
              :style="{ background: sol.estado === 'pendiente' ? '#fef3c7' : sol.estado === 'aceptado' ? '#dcfce7' : '#fee2e2', color: sol.estado === 'pendiente' ? '#d97706' : sol.estado === 'aceptado' ? '#16a34a' : '#dc2626' }">
              {{ initialesPaciente(sol) }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-bold truncate" style="color:#1e2d55;">
                {{ sol.primer_nombre }} {{ sol.primer_apellido }}
              </p>
              <p class="text-[10px] truncate mt-0.5" style="color:#8a9ab5;">
                {{ sol.especialidad_requerida }} · {{ sol.eps }} · {{ tiempoRelativo(sol.created_at) }}
              </p>
            </div>
            <svg class="w-16 h-6 shrink-0" viewBox="0 0 64 24" fill="none">
              <path :d="sparklinePath(idx)" stroke-width="2" stroke-linecap="round" fill="none"
                :stroke="sol.estado === 'pendiente' ? '#eab308' : sol.estado === 'aceptado' ? '#22c55e' : '#f87171'" />
            </svg>
            <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0"
              :style="{
                background: sol.estado === 'pendiente' ? '#fff3cd' : sol.estado === 'aceptado' ? '#d3f9d8' : '#ffe0e0',
                color: sol.estado === 'pendiente' ? '#b96800' : sol.estado === 'aceptado' ? '#2f9e44' : '#c92a2a',
              }"
            >
              {{ sol.estado === 'pendiente' ? 'Pendiente' : sol.estado === 'aceptado' ? 'Aceptada' : 'Negada' }}
            </span>
          </div>
        </div>
      </div>

    </div>


    <!-- ── Modales ──────────────────────────────────────────────────────── -->

    <!-- ── Drawer: Nueva Solicitud ──────────────────────────────────────── -->
    <el-dialog
      v-model="drawerVisible"
      title="Nueva Solicitud de Referencia"
      width="900px"
      class="request-dialog"
      :close-on-click-modal="false"
      :lock-scroll="true"
      :destroy-on-close="true"
      :before-close="confirmarCierre"
      align-center
    >
      <div class="request-form px-1">
        <div class="form-hero mb-1.5">
          <div class="form-hero-icon"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
          <div><p>Nueva remisión</p><span>Complete la información para que el equipo de referencia pueda gestionar el caso.</span></div>
        </div>
        <div class="stepper mb-1.5">
          <div v-for="step in formSteps" :key="step.number" class="stepper-item" :class="{ active: pasoFormulario >= step.number }">
            <span class="stepper-number">{{ pasoFormulario > step.number ? '✓' : step.number }}</span>
            <p>{{ step.label }}</p>
          </div>
        </div>
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default">

          <div v-show="pasoFormulario === 1" class="mb-0">
            <div class="flex items-center gap-2 mb-1.5">
              <div class="w-5 h-5 rounded-full bg-[#0D2D6B] text-white text-[10px] flex items-center justify-center font-bold shrink-0">1</div>
              <p class="font-semibold text-gray-700 text-xs">Datos del paciente</p>
            </div>
            <div class="grid grid-cols-4 gap-x-3 gap-y-0 form-patient-grid">
              <el-form-item label="Fecha" prop="fecha" required>
                <el-date-picker v-model="form.fecha" type="date" format="DD/MM/YYYY" value-format="YYYY-MM-DD" class="w-full" placeholder="Seleccione" />
              </el-form-item>
              <el-form-item label="Hora" prop="hora" required>
                <el-time-picker v-model="form.hora" format="HH:mm" value-format="HH:mm" class="w-full" placeholder="HH:MM" />
              </el-form-item>
              <el-form-item label="Edad" prop="edad" required>
                <el-input-number v-model="form.edad" :min="0" :max="120" class="w-full" controls-position="right" />
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
              <el-form-item label="Género" prop="genero" required>
                <el-select v-model="form.genero" class="w-full" placeholder="Seleccione">
                  <el-option label="Masculino" value="M" />
                  <el-option label="Femenino" value="F" />
                </el-select>
              </el-form-item>
              <el-form-item label="EPS / Aseguradora" prop="eps" required class="col-span-2">
                <el-select v-model="form.eps" filterable class="w-full" placeholder="Seleccione o escriba">
                  <el-option v-for="e in EPS_LIST" :key="e" :label="e" :value="e" />
                </el-select>
              </el-form-item>
            </div>
          </div>

          <el-divider class="form-section-divider" />

          <div v-show="pasoFormulario === 2" class="mb-0">
            <div class="flex items-center gap-2 mb-1.5">
              <div class="w-5 h-5 rounded-full bg-[#0D2D6B] text-white text-[10px] flex items-center justify-center font-bold shrink-0">2</div>
              <p class="font-semibold text-gray-700 text-xs">Datos de la remisión</p>
            </div>
            <div class="grid grid-cols-3 gap-x-3 gap-y-0">
              <el-form-item label="Diagnóstico (CIE-10)" prop="diagnostico" required class="col-span-3">
                <el-select
                  v-model="form.diagnostico"
                  filterable
                  allow-create
                  default-first-option
                  class="w-full"
                  placeholder="Escriba el código (ej: J18.9) o el nombre del diagnóstico"
                  :filter-method="filtrarCIE10"
                  @change="onDiagnosticoSelect"
                >
                  <el-option
                    v-for="item in cie10Filtrados"
                    :key="item.codigo"
                    :label="`${item.codigo} — ${item.descripcion}`"
                    :value="`${item.codigo} ${item.descripcion}`"
                  />
                </el-select>
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
              <el-form-item label="Condición especial" class="col-span-3">
                <el-input v-model="form.condicion_especial" placeholder="Ej: Paciente con discapacidad, obesidad mórbida..." autocomplete="off" />
              </el-form-item>
            </div>
          </div>

          <el-divider class="form-section-divider" />

          <div v-show="pasoFormulario === 3" class="mb-0">
            <div class="flex items-center gap-2 mb-1.5">
              <div class="w-5 h-5 rounded-full bg-[#0D2D6B] text-white text-[10px] flex items-center justify-center font-bold shrink-0">3</div>
              <p class="font-semibold text-gray-700 text-xs">Resumen clínico</p>
            </div>
            <div class="grid grid-cols-2 gap-x-3 gap-y-0">
              <el-form-item label="Resumen de la historia clínica" prop="resumen_historia_clinica" required class="col-span-2">
                <el-input v-model="form.resumen_historia_clinica" type="textarea" :rows="2"
                  placeholder="Describa el motivo de remisión, antecedentes relevantes, estado actual del paciente..." />
              </el-form-item>
              <el-form-item label="Observaciones adicionales" class="col-span-2">
                <el-input v-model="form.observaciones" type="textarea" :rows="1" placeholder="Información adicional relevante..." />
              </el-form-item>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div class="review-card">
                <p class="review-title">Revise antes de enviar</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                  <div><span>Paciente</span><strong>{{ form.primer_nombre }} {{ form.primer_apellido }}</strong></div>
                  <div><span>Documento</span><strong>{{ form.tipo_documento }} {{ form.numero_documento }}</strong></div>
                  <div><span>Especialidad</span><strong>{{ form.especialidad_requerida }}</strong></div>
                  <div><span>Servicio</span><strong>{{ form.servicio_ubicacion_actual }}</strong></div>
                </div>
              </div>
              <div class="attachments-card rounded-xl border border-dashed border-slate-300 bg-slate-50 p-2">
                <div class="flex items-center gap-2"><span class="attachments-icon">+</span><p class="text-xs font-semibold text-slate-700">Soportes clínicos</p></div>
                <p class="text-[10px] text-slate-500 mt-0.5">Adjunte resultados u órdenes. Hasta 5 archivos de 10 MB.</p>
                <input class="mt-1.5 block w-full text-xs text-slate-600" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple @change="seleccionarAdjuntos" />
                <ul v-if="adjuntos.length" class="mt-1 space-y-0.5 text-[10px] text-slate-600">
                  <li v-for="archivo in adjuntos" :key="archivo.name">{{ archivo.name }}</li>
                </ul>
              </div>
            </div>
          </div>

        </el-form>

        <div class="flex gap-3 pt-1.5 border-t border-gray-100 mt-1">
          <el-button class="flex-1" @click="pasoFormulario === 1 ? drawerVisible = false : pasoFormulario--">{{ pasoFormulario === 1 ? 'Cancelar' : 'Anterior' }}</el-button>
          <el-button v-if="pasoFormulario < 3" type="primary" class="flex-1 !bg-[#0D2D6B]" @click="avanzarPaso">Continuar</el-button>
          <el-button v-else type="primary" class="flex-1 !bg-[#0D2D6B]" :loading="guardando" @click="guardar">Enviar solicitud</el-button>
        </div>
      </div>
    </el-dialog>

    <!-- ── Modal: Detalle ──────────────────────────────────────────────── -->
    <el-dialog v-model="modalDetalle" width="640px" class="detalle-dialog" :show-close="true" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <!-- Header con gradiente azul -->
          <div class="detalle-head">
            <div class="detalle-head-glow"></div>
            <div class="detalle-head-icon">
              <component
                :is="solicitudSeleccionada.estado === 'aceptado' ? CheckCircleIcon : solicitudSeleccionada.estado === 'negado' ? XCircleIcon : ClockIcon"
                class="w-7 h-7"
              />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Detalle de solicitud</p>
              <p class="detalle-head-sub">#{{ solicitudSeleccionada.id }} · {{ formatFecha(solicitudSeleccionada.created_at) }}</p>
            </div>
            <div class="detalle-head-badge" :class="'badge-' + solicitudSeleccionada.estado">
              {{ solicitudSeleccionada.estado === 'pendiente' ? 'Pendiente' : solicitudSeleccionada.estado === 'aceptado' ? 'Aceptado' : 'Negado' }}
            </div>
          </div>

          <!-- Paciente + Remisión en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5 mb-2.5">
            <div class="detalle-card">
              <div class="card-icon" style="background:#dbeafe; color:#2563eb;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Paciente</p>
                <div class="data-row"><span>Nombre</span><strong>{{ solicitudSeleccionada.primer_nombre }} {{ solicitudSeleccionada.segundo_nombre }} {{ solicitudSeleccionada.primer_apellido }} {{ solicitudSeleccionada.segundo_apellido }}</strong></div>
                <div class="data-row"><span>Documento</span><strong>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</strong></div>
                <div class="data-row"><span>Edad / Género</span><strong>{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masc.' : 'Fem.' }}</strong></div>
                <div class="data-row"><span>EPS</span><strong>{{ solicitudSeleccionada.eps }}</strong></div>
              </div>
            </div>
            <div class="detalle-card">
              <div class="card-icon" style="background:#fef3c7; color:#d97706;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Remisión</p>
                <div class="data-row"><span>Especialidad</span><strong>{{ solicitudSeleccionada.especialidad_requerida }}</strong></div>
                <div class="data-row"><span>Servicio actual</span><strong>{{ solicitudSeleccionada.servicio_ubicacion_actual }}</strong></div>
                <div class="data-row"><span>Municipio</span><strong>{{ solicitudSeleccionada.municipio_capita }}</strong></div>
                <div v-if="solicitudSeleccionada.servicio_remision" class="data-row"><span>Destino</span><strong>{{ solicitudSeleccionada.servicio_remision }}</strong></div>
              </div>
            </div>
          </div>

          <!-- Diagnóstico + Historia en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5 mb-2.5">
            <div class="detalle-card">
              <div class="card-icon" style="background:#ede9fe; color:#7c3aed;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Diagnóstico</p>
                <p class="card-text">{{ solicitudSeleccionada.diagnostico }}</p>
              </div>
            </div>
            <div class="detalle-card">
              <div class="card-icon" style="background:#e0f2fe; color:#0284c7;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Historia clínica</p>
                <p class="card-text-sm">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
              </div>
            </div>
          </div>

          <!-- Código de aceptación -->
          <div v-if="solicitudSeleccionada.codigo_aceptacion" class="detalle-code-bar">
            <component :is="CheckCircleIcon" class="w-4 h-4 text-emerald-600" />
            <span class="text-xs text-emerald-700 font-semibold">Código de aceptación</span>
            <span class="detalle-code-value">{{ solicitudSeleccionada.codigo_aceptacion }}</span>
          </div>

          <!-- Respuesta / Negación -->
          <div v-if="solicitudSeleccionada.nombre_quien_responde" class="detalle-respuesta">
            <div class="flex items-center gap-2 mb-1">
              <component :is="CheckCircleIcon" class="w-4 h-4 text-blue-600" />
              <p class="text-[10px] font-extrabold text-blue-600 uppercase tracking-wider">Respuesta del equipo</p>
            </div>
            <p class="text-xs text-blue-700">Respondió: <strong>{{ solicitudSeleccionada.nombre_quien_responde }}</strong> · {{ solicitudSeleccionada.hora_respuesta }}</p>
            <p v-if="solicitudSeleccionada.observaciones_respuesta" class="text-xs text-blue-600 mt-1">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
          </div>

          <div v-if="solicitudSeleccionada.motivo_negacion" class="detalle-negacion">
            <div class="flex items-center gap-2 mb-1">
              <component :is="XCircleIcon" class="w-4 h-4 text-red-500" />
              <p class="text-[10px] font-extrabold text-red-500 uppercase tracking-wider">Motivo de negación</p>
            </div>
            <p class="text-xs text-slate-700">{{ solicitudSeleccionada.motivo_negacion }}</p>
          </div>

          <!-- Seguimiento + Adjuntos en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5">
            <div v-if="solicitudSeleccionada.eventos?.length" class="detalle-card">
              <div class="card-icon" style="background:#f1f5f9; color:#475569;"><component :is="ClockIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Seguimiento</p>
                <div v-for="evento in solicitudSeleccionada.eventos" :key="evento.id" class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div><p class="text-[11px] font-semibold text-slate-700">{{ evento.titulo }}</p><p class="text-[9px] text-slate-400">{{ formatFecha(evento.created_at) }}</p></div>
                </div>
              </div>
            </div>
            <div class="detalle-card">
              <div class="card-icon" style="background:#fef2f2; color:#dc2626;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Soportes</p>
                <div v-if="solicitudSeleccionada.adjuntos?.length">
                  <div v-for="adj in solicitudSeleccionada.adjuntos" :key="adj.id" class="adjunto-item">
                    <a :href="`/api/externo/solicitudes/${solicitudSeleccionada.id}/adjuntos/${adj.id}/descargar`" target="_blank" class="adjunto-link">
                      <span class="adjunto-icon" :class="adj.mime_type?.includes('pdf') ? 'adjunto-pdf' : 'adjunto-img'">{{ adj.mime_type?.includes('pdf') ? 'PDF' : 'IMG' }}</span>
                      <span class="adjunto-name">{{ adj.nombre_original }}</span>
                    </a>
                  </div>
                </div>
                <p v-else class="adjunto-empty">Sin archivos adjuntos</p>
              </div>
            </div>
          </div>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus as PlusIcon,
  ClipboardList as ClipboardListIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  RefreshCw as RefreshCwIcon,
  ChevronRight as ChevronRightIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { TIPOS_DOCUMENTO, EPS_LIST, ESPECIALIDADES, SERVICIOS } from '@/data/referencia';
import { CIE10 } from '@/data/cie10';

const clinicaAuth = useClinicaAuthStore();
const router = useRouter();
const solicitudes = ref<any[]>([]);
const cargando = ref(false);
const drawerVisible = ref(false);
const guardando = ref(false);
const formRef = ref();
const modalDetalle = ref(false);
const solicitudSeleccionada = ref<any>(null);
const adjuntos = ref<File[]>([]);
const pasoFormulario = ref(1);
const cie10Filtrados = ref(CIE10.slice(0, 20));

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

function onDiagnosticoSelect(val: string) {
  if (!val) return;
  const match = CIE10.find(c => `${c.codigo} ${c.descripcion}` === val);
  if (match) {
    form.value.diagnostico = `${match.codigo} ${match.descripcion}`;
  }
}
const formSteps = [
  { number: 1, label: 'Paciente' },
  { number: 2, label: 'Remisión' },
  { number: 3, label: 'Resumen' },
];

// ── Stat cards config ───────────────────────────────────────────────────────
const statCards = [
  { iconBackground: '#dbeafe', color: '#2563eb', icon: ClipboardListIcon, label: 'Solicitudes', sub: (n: number) => n ? `${n} enviadas` : 'sin envíos' },
  { iconBackground: '#fef3c7', color: '#d97706', icon: ClockIcon, label: 'Pendientes', sub: (n: number) => n ? `${n} en espera` : 'sin pendientes' },
  { iconBackground: '#dcfce7', color: '#16a34a', icon: CheckCircleIcon, label: 'Aceptadas', sub: (n: number) => n ? `${n} aprobadas` : 'sin aprobadas' },
  { iconBackground: '#fee2e2', color: '#dc2626', icon: XCircleIcon, label: 'Negadas', sub: (n: number) => n ? `${n} rechazadas` : 'sin rechazos' },
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
const fechaHoy = new Date().toLocaleDateString('es-CO', {
  weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
});

const stats = computed(() => ({
  total: solicitudes.value.length,
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

const statPercents = computed(() => {
  const total = Math.max(1, stats.value.total);
  return [
    100,
    Math.round((stats.value.pendientes / total) * 100),
    Math.round((stats.value.aceptadas / total) * 100),
    Math.round((stats.value.negadas / total) * 100),
  ];
});

const tasaAceptacion = computed(() => {
  if (!stats.value.total) return 0;
  return Math.round((stats.value.aceptadas / stats.value.total) * 100);
});

const tiempoPromedio = computed(() => {
  const respondidas = solicitudes.value.filter(s => s.estado !== 'pendiente' && s.updated_at && s.created_at);
  if (!respondidas.length) return '—';
  let totalHoras = 0;
  respondidas.forEach(s => {
    const creado = new Date(s.created_at).getTime();
    const respondido = new Date(s.updated_at).getTime();
    totalHoras += Math.max(0, (respondido - creado) / (1000 * 60 * 60));
  });
  const promedioHoras = totalHoras / respondidas.length;
  if (promedioHoras < 1) return `${Math.round(promedioHoras * 60)} min`;
  if (promedioHoras < 24) return `${promedioHoras.toFixed(1)} h`;
  return `${(promedioHoras / 24).toFixed(1)} días`;
});

// ── Sparklines decorativas por fila ─────────────────────────────────────────
const SPARKLINE_PATHS = [
  'M2 14 C 10 6, 18 20, 26 12 S 42 4, 50 12 S 58 18, 62 10',
  'M2 12 C 10 18, 18 6, 26 14 S 42 20, 50 8 S 58 12, 62 14',
  'M2 10 C 10 14, 18 8, 26 16 S 42 6, 50 14 S 58 8, 62 12',
];

function sparklinePath(idx: number): string {
  return SPARKLINE_PATHS[idx % SPARKLINE_PATHS.length];
}

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

function abrirFormulario() { form.value = emptyForm(); adjuntos.value = []; pasoFormulario.value = 1; cie10Filtrados.value = CIE10.slice(0, 20); drawerVisible.value = true; }

function irHistorial() { router.push('/clinica/historial'); }

function formularioTieneDatos(): boolean {
  const f = form.value;
  return !!(f.primer_nombre || f.primer_apellido || f.numero_documento || f.diagnostico ||
    f.especialidad_requerida || f.resumen_historia_clinica || adjuntos.value.length > 0);
}

async function confirmarCierre(done: () => void) {
  if (!formularioTieneDatos()) { done(); return; }
  try {
    await ElMessageBox.confirm(
      'Tiene datos sin guardar. ¿Desea cerrar el formulario?',
      'Confirmar cierre',
      { confirmButtonText: 'Cerrar', cancelButtonText: 'Seguir editando', type: 'warning' },
    );
    done();
  } catch { /* cancelado */ }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'n' || e.key === 'N') {
    const tag = (e.target as HTMLElement)?.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (e.target as HTMLElement)?.isContentEditable) return;
    if (drawerVisible.value || modalDetalle.value) return;
    e.preventDefault();
    abrirFormulario();
  }
}
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

function verDetalle(sol: any) { solicitudSeleccionada.value = sol; modalDetalle.value = true; }
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

watch(drawerVisible, (isOpen) => {
  document.documentElement.style.overflow = isOpen ? 'hidden' : '';
  document.body.style.overflow = isOpen ? 'hidden' : '';
});

let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  cargar();
  window.addEventListener('keydown', onKeydown);
  pollTimer = setInterval(() => {
    if (!cargando.value && !drawerVisible.value) cargar();
  }, 30000);
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown);
  if (pollTimer) clearInterval(pollTimer);
});
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
:global(.request-dialog .el-dialog__body) { max-height: calc(100vh - 5.5rem); padding: .5rem .9rem .6rem; overflow: hidden !important; background: linear-gradient(180deg, #f7faff, #fff); display: flex; flex-direction: column; }
:global(.request-dialog .el-dialog) {
  max-height: calc(100vh - 1.5rem);
  overflow: hidden;
}
:global(html:has(.request-dialog)),
:global(body:has(.request-dialog)) {
  height: 100%;
  overflow: hidden !important;
}
:deep(.request-form .el-form-item) { margin-bottom: .25rem; }
:deep(.request-form .el-form-item__error) { padding-top: 0; font-size: .6rem; line-height: 1.1; }
:deep(.request-form .el-divider) { display: none; }
:deep(.request-form .el-textarea__inner) { min-height: 40px !important; }
:deep(.request-form .el-input__wrapper),
:deep(.request-form .el-select__wrapper) { min-height: 32px; }
:deep(.el-overlay) { background-color: rgba(8, 27, 58, .56); backdrop-filter: blur(4px); }

/* ── Modal Detalle ── */
:deep(.detalle-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.detalle-dialog .el-dialog__header) { display: none; }
:deep(.detalle-dialog .el-dialog__body) {
  padding: 0;
  max-height: calc(100vh - 3rem);
  overflow: hidden;
  background: linear-gradient(180deg, #f0f5ff 0%, #f8faff 30%, #ffffff 100%);
}
:deep(.detalle-dialog .el-dialog__headerbtn) { z-index: 10; top: 14px; right: 14px; }
:deep(.detalle-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.1rem; }
:deep(.detalle-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }

.detalle-content { padding: 0; }
.detalle-content > .grid,
.detalle-content > .detalle-code-bar,
.detalle-content > .detalle-respuesta,
.detalle-content > .detalle-negacion { padding-left: 1rem; padding-right: 1rem; margin-top: .8rem; }
.detalle-content > .grid:last-child { padding-bottom: 1rem; }

/* Header con gradiente azul institucional */
.detalle-head {
  position: relative;
  background: linear-gradient(125deg, #0d2d6b 0%, #16468e 50%, #1a3d8a 100%);
  padding: 1.1rem 1.3rem;
  display: flex;
  align-items: center;
  gap: .8rem;
  overflow: hidden;
}
.detalle-head::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
}
.detalle-head-glow {
  position: absolute;
  top: -40px; right: -30px;
  width: 140px; height: 140px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
}
.detalle-head-glow::after {
  content: '';
  position: absolute;
  top: 20px; left: 30px;
  width: 60px; height: 60px;
  border-radius: 50%;
  background: rgba(255,255,255,.04);
}
.detalle-head-icon {
  width: 2.8rem; height: 2.8rem;
  border-radius: 12px;
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.2);
  display: grid; place-items: center;
  color: #fff;
  flex-shrink: 0;
  z-index: 1;
}
.detalle-head-info { flex: 1; z-index: 1; }
.detalle-head-title { margin: 0; color: #fff; font-size: 1rem; font-weight: 800; }
.detalle-head-sub { margin: .15rem 0 0; color: rgba(255,255,255,.55); font-size: .68rem; }

.detalle-head-badge {
  padding: .3rem .7rem;
  border-radius: 999px;
  font-size: .65rem;
  font-weight: 700;
  flex-shrink: 0;
  z-index: 1;
}
.badge-pendiente { background: #f59e0b; color: #fff; }
.badge-aceptado { background: #22c55e; color: #fff; }
.badge-negado { background: #ef4444; color: #fff; }

/* Cards */
.detalle-card {
  background: #fff;
  border: 1px solid #d4deea;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(22,70,142,.07);
  transition: box-shadow .25s ease, border-color .25s ease, transform .25s ease;
  position: relative;
}
.detalle-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0d2d6b, #16468e, #2f70bb);
  opacity: .7;
}
.detalle-card:hover {
  box-shadow: 0 10px 28px rgba(22,70,142,.14);
  border-color: #b8c8de;
  transform: translateY(-1px);
}

.card-icon {
  width: 1.8rem; height: 1.8rem;
  border-radius: 8px;
  display: grid; place-items: center;
  flex-shrink: 0;
  margin: .6rem .6rem 0 .6rem;
  float: left;
}
.card-body { padding: .6rem .8rem .6rem .6rem; }
.card-title {
  margin: 0 0 .35rem;
  font-size: .72rem;
  font-weight: 800;
  color: #0d2d5e;
  text-transform: uppercase;
  letter-spacing: .04em;
  padding-top: .15rem;
}
.card-text { font-size: .72rem; color: #334e70; line-height: 1.5; margin: 0; }
.card-text-sm { font-size: .66rem; color: #64748b; line-height: 1.45; margin: 0; }

.data-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: .4rem;
  padding: .18rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.data-row:last-child { border-bottom: none; }
.data-row span { font-size: .62rem; color: #94a3b8; white-space: nowrap; }
.data-row strong { font-size: .68rem; color: #1e293b; font-weight: 600; text-align: right; }

/* Código de aceptación */
.detalle-code-bar {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  border: 1px solid #a7f3d0;
  border-radius: 12px;
  padding: .55rem .8rem;
  margin-bottom: .6rem;
}
.detalle-code-value {
  margin-left: auto;
  font-family: monospace;
  font-size: .9rem;
  font-weight: 800;
  color: #166534;
  background: #fff;
  padding: .2rem .6rem;
  border-radius: 6px;
  border: 1px solid #86efac;
}

/* Respuesta / Negación */
.detalle-respuesta {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  padding: .55rem .8rem;
  margin-bottom: .6rem;
}
.detalle-negacion {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  padding: .55rem .8rem;
  margin-bottom: .6rem;
}

/* Timeline */
.timeline-item {
  display: flex;
  gap: .5rem;
  align-items: flex-start;
  padding: .15rem 0;
}
.timeline-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #16468e;
  margin-top: 5px;
  flex-shrink: 0;
}

/* Adjuntos */
.adjunto-item { padding: .15rem 0; }
.adjunto-item:not(:last-child) { border-bottom: 1px solid #f1f5f9; }
.adjunto-link {
  display: flex;
  align-items: center;
  gap: .4rem;
  text-decoration: none;
  padding: .2rem 0;
  transition: opacity .15s ease;
}
.adjunto-link:hover { opacity: .7; }
.adjunto-icon {
  font-size: .52rem;
  font-weight: 800;
  color: #fff;
  padding: .12rem .3rem;
  border-radius: 4px;
  flex-shrink: 0;
}
.adjunto-pdf { background: #dc2626; }
.adjunto-img { background: #2563eb; }
.adjunto-name {
  font-size: .66rem;
  color: #334e70;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.adjunto-empty {
  font-size: .66rem;
  color: #cbd5e1;
  font-style: italic;
  margin: 0;
  padding: .3rem 0;
}
:deep(.request-form .el-form-item__label) { color: #334e70; font-size: .7rem; font-weight: 700; padding-bottom: .1rem; }
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
  gap: .5rem;
  align-items: center;
  padding: .5rem .7rem;
  border: 1px solid #cfe0f5;
  border-radius: 12px;
  background: linear-gradient(120deg, #eaf4ff, #f8fbff);
}
.form-hero-icon {
  display: grid;
  width: 1.6rem;
  height: 1.6rem;
  place-items: center;
  color: #fff;
  border-radius: 8px;
  background: linear-gradient(135deg, #0d2d5e, #3174c4);
  box-shadow: 0 4px 10px rgba(13, 45, 94, .2);
}
.form-hero p { margin: 0 0 .05rem; color: #0d2d5e; font-size: .75rem; font-weight: 800; }
.form-hero span { color: #61748d; font-size: .6rem; line-height: 1.2; }

.attachments-card { border-color: #bcd7f3; background: linear-gradient(135deg, #f4faff, #fbfdff); }

@media (max-height: 650px) {
  :global(.request-dialog .el-dialog__header) { margin: .45rem .65rem 0; padding-block: .7rem; }
  :global(.request-dialog .el-dialog__body) { padding: .55rem .9rem .65rem; }
  .form-hero { padding: .45rem .6rem; }
  .form-hero-icon { width: 1.8rem; height: 1.8rem; border-radius: 9px; }
  .form-hero p { font-size: .76rem; }
  .form-hero span { font-size: .62rem; }
  .stepper { gap: .25rem; padding: .25rem; }
  .stepper-item { min-height: 2rem; font-size: .64rem; }
  .stepper-number { width: 1.2rem; height: 1.2rem; font-size: .6rem; }
  :deep(.request-form .el-form-item) { margin-bottom: .3rem; }
  :deep(.request-form .el-form-item__label) { height: 15px; line-height: 15px; font-size: .66rem; }
  :deep(.request-form .el-input__wrapper),
  :deep(.request-form .el-select__wrapper) { min-height: 28px; }
  :deep(.request-form .el-button) { min-height: 30px; padding-block: .35rem; }
}
.attachments-icon { display: inline-grid; width: 1.35rem; height: 1.35rem; place-items: center; color: #fff; background: #2f70bb; border-radius: 6px; font-weight: 800; }

.review-card { background: #f8fbff; border: 1px solid #e0ecf8; border-radius: 10px; padding: .4rem .6rem; }
.review-title { margin: 0 0 .3rem; font-size: .62rem; font-weight: 800; color: #0d2d5e; text-transform: uppercase; letter-spacing: .04em; }
.review-card div { display: flex; flex-direction: column; }
.review-card span { color: #64748b; font-size: .55rem; }
.review-card strong { margin-top: .02rem; color: #1e3a5f; font-weight: 700; font-size: .62rem; }

.stepper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .25rem;
  padding: .25rem;
  border: 1px solid #dbe8f5;
  border-radius: 14px;
  background: #edf5fc;
}

.stepper-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .35rem;
  min-height: 2rem;
  padding: 0 .5rem;
  border-radius: 8px;
  font-size: .68rem;
  font-weight: 600;
  color: #61748d;
  background: #fff;
  border: 1px solid #dce7f2;
  transition: .2s ease;
}

.stepper-item p { margin: 0; }
.stepper-number {
  display: grid;
  width: 1.1rem;
  height: 1.1rem;
  place-items: center;
  border: 1px solid #cfe0f5;
  border-radius: 50%;
  font-weight: 800;
  font-size: .62rem;
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

.ph-dashboard {
  background: #f5f7fb;
}

/* ── Hero banner ── */
.hero-card {
  background: linear-gradient(115deg, #0d2d6b 0%, #16468e 55%, #1e3a7a 100%);
  border: 1px solid #1e3a7a;
  box-shadow: 0 8px 24px rgba(13, 45, 107, .25);
  backdrop-filter: blur(10px);
}

.hero-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.04) 1px, transparent 0);
  background-size: 22px 22px;
  pointer-events: none;
}

.hero-live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 8px rgba(74, 222, 128, .6);
  animation: livePulse 2s ease-in-out infinite;
}

@keyframes livePulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .5; transform: scale(1.3); }
}

.hero-glow {
  position: absolute;
  top: -40%;
  right: -10%;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126, 179, 255, .15), transparent 70%);
  pointer-events: none;
}

.hero-illustration {
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 20px rgba(0, 0, 0, .15), inset 0 1px 2px rgba(255, 255, 255, .15);
}

.hero-link {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  color: #0d2d6b;
  background: #fff;
  border: 0;
  padding: .7rem 1.3rem;
  font-size: .85rem;
  font-weight: 800;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(0, 0, 0, .18);
  transition: transform .25s ease, box-shadow .25s ease;
  position: relative;
  overflow: hidden;
}
.hero-link::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(13, 45, 107, 0.12), transparent);
  transform: translateX(-100%);
  transition: transform .6s ease;
}
.hero-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 26px rgba(0, 0, 0, .25), 0 0 22px rgba(126, 179, 255, .4);
}
.hero-link:hover::before {
  transform: translateX(100%);
}
.hero-link:active { transform: translateY(0); }

.hero-pill {
  position: absolute;
  width: 18px;
  height: 18px;
  border-radius: 999px;
  opacity: .85;
}

.clinic-name::first-letter {
  font-size: 1.6em;
  font-weight: 900;
}

/* ── Tarjetas tipo órganos ── */
.organ-card {
  background: #fff;
  border: 1px solid #d4deea;
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1), border-color .3s ease;
  min-height: 76px;
  position: relative;
  overflow: hidden;
}
.organ-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  opacity: 1;
  transition: opacity .28s ease, height .28s ease;
}
.organ-card:nth-child(1)::before { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.organ-card:nth-child(2)::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.organ-card:nth-child(3)::before { background: linear-gradient(90deg, #16a34a, #4ade80); }
.organ-card:nth-child(4)::before { background: linear-gradient(90deg, #dc2626, #f87171); }
.organ-card:hover::before { opacity: 1; height: 5px; }
.organ-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 20px 38px rgba(22, 70, 142, .16);
  border-color: #b8c8de;
}

.organ-card--active {
  background: linear-gradient(135deg, #16468e, #0d2d6b) !important;
  box-shadow: 0 14px 30px rgba(13, 45, 107, .35);
}
.organ-card--active::before { opacity: 0; }

.organ-icon {
  background: #fff;
  color: #16468e;
  box-shadow: 0 3px 8px rgba(22, 70, 142, .10);
  transition: transform .2s ease;
}
.organ-card:hover .organ-icon { transform: scale(1.1); }
.organ-icon--active {
  background: rgba(255,255,255,.18);
  color: #fff;
}

/* ── Distribución ── */
.distrib-card {
  background: #fff;
  border: 1px solid #d4deea;
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  border-radius: 14px;
  position: relative;
  overflow: hidden;
}
.distrib-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #fbbf24, #22c55e, #f87171);
  opacity: .6;
  transition: opacity .28s ease, height .28s ease;
}
.distrib-card {
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1), border-color .3s ease;
}
.distrib-card:hover {
  transform: translateY(-5px) scale(1.01);
  box-shadow: 0 18px 34px rgba(22, 70, 142, .15);
  border-color: #b8c8de;
}
.distrib-card:hover::before {
  opacity: 1;
  height: 5px;
}

/* ── Métricas ── */
.metric-card {
  background: #fff;
  border: 1px solid #d4deea;
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  border-radius: 14px;
  position: relative;
  overflow: hidden;
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1), border-color .3s ease;
}
.metric-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #0d2d6b, #16468e, #2f70bb);
  opacity: .6;
  transition: opacity .28s ease, height .28s ease;
}
.metric-card:hover {
  transform: translateY(-5px) scale(1.02);
  box-shadow: 0 18px 34px rgba(22, 70, 142, .15);
  border-color: #b8c8de;
}
.metric-card:hover::before {
  opacity: 1;
  height: 5px;
}

.metric-ring {
  position: relative;
  width: 48px;
  height: 48px;
}
.metric-ring-text {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .72rem;
  font-weight: 800;
  color: #1e2d55;
}
.metric-ring-fill {
  transition: stroke-dashoffset 1s cubic-bezier(.22,1,.36,1);
}

/* ── Filas tipo medicamento ── */
.med-row {
  background: #fff;
  border: 1px solid #edf1f7;
  border-left: 3px solid transparent;
  box-shadow: 0 3px 10px rgba(22, 70, 142, .04);
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.med-row:hover { transform: translateX(3px); box-shadow: 0 10px 22px rgba(22, 70, 142, .11); }

.med-row--active {
  background: linear-gradient(135deg, #16468e, #0d2d6b);
  border-color: transparent;
  box-shadow: 0 12px 28px rgba(13, 45, 107, .32);
}
.med-row--active:hover { transform: translateX(3px); box-shadow: 0 16px 34px rgba(13, 45, 107, .40); }

.med-icon {
  background: #eaf2fd;
  color: #16468e;
  font-size: .68rem;
  font-weight: 800;
  transition: transform .2s ease;
}
.med-row:hover .med-icon { transform: scale(1.08); }
.med-icon--active {
  background: rgba(255,255,255,.18);
  color: #fff;
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
  padding: .5rem .8rem;
  color: #fff;
  background: linear-gradient(135deg, #0d2d6b, #2563eb);
  border: 0;
  border-radius: 10px;
  font-size: .75rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .2);
  transition: transform .2s ease, box-shadow .2s ease;
}
.empty-state-action:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(13, 45, 107, .32); }

/* ── Entradas ── */
.anim-fade-down  { animation: fadeDown  0.5s cubic-bezier(.22,1,.36,1) both; }

/* ── Header solicitudes recientes ── */
.recent-header-icon {
  width: 26px; height: 26px;
  border-radius: 7px;
  background: linear-gradient(135deg, #dbeafe, #bfdbfe);
  color: #16468e;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(22, 70, 142, .12);
}
.recent-count-badge {
  font-size: 10px;
  font-weight: 700;
  color: #16468e;
  background: #e0ecff;
  padding: 1px 7px;
  border-radius: 999px;
}
.recent-refresh-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .35rem .7rem;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all .2s ease;
}
.recent-refresh-btn:hover {
  color: #16468e;
  background: #e0ecff;
  border-color: #bfdbfe;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(22, 70, 142, .1);
}
.recent-refresh-btn:active {
  transform: translateY(0);
}
.anim-slide-up   { animation: slideUp   0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-fade-left  { animation: fadeLeft  0.5s cubic-bezier(.22,1,.36,1) both; }
.anim-row-in     { animation: rowIn     0.4s cubic-bezier(.22,1,.36,1) both; }

@keyframes fadeDown  { from { opacity:0; transform:translateY(-18px); } to { opacity:1; transform:none; } }
@keyframes slideUp   { from { opacity:0; transform:translateY(20px);  } to { opacity:1; transform:none; } }
@keyframes fadeLeft  { from { opacity:0; transform:translateX(-20px); } to { opacity:1; transform:none; } }
@keyframes rowIn     { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:none; } }

/* ── Skeleton loaders con shimmer ── */
.skeleton-row {
  background: #fff;
  border: 1px solid #edf1f7;
}
.shimmer-box,
.shimmer-bar {
  position: relative;
  overflow: hidden;
  background: #e6ebf3;
}
.shimmer-box { border-radius: 6px; }
.shimmer-bar { border-radius: 4px; }
.shimmer-box::after,
.shimmer-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255,255,255,0.6) 50%,
    transparent 100%
  );
  animation: shimmer 1.8s ease-in-out infinite;
}
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* ── Tooltip en distribución ── */
.distrib-segment {
  position: relative;
  cursor: pointer;
}
.distrib-segment::after {
  content: attr(data-tooltip);
  position: absolute;
  bottom: calc(100% + 6px);
  left: 50%;
  transform: translateX(-50%);
  background: #1e2d55;
  color: #fff;
  font-size: 10px;
  font-weight: 600;
  padding: 4px 8px;
  border-radius: 6px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity .2s ease;
  z-index: 20;
}
.distrib-segment:hover::after { opacity: 1; }

/* ── Contador ── */
.counter {
  transition: all 0.1s ease;
  font-variant-numeric: tabular-nums;
}

/* ── Responsive móvil ── */
@media (max-width: 640px) {
  .hero-card { padding: .75rem !important; }
  .hero-link { padding: .5rem .9rem !important; font-size: .72rem !important; }
  .hero-link span { display: none; }
  .distrib-card { padding: .6rem !important; }
  .distrib-card .flex.items-center.gap-4 { gap: .5rem !important; flex-wrap: wrap; }
  .metric-card { padding: .6rem !important; }
  .metric-ring { width: 40px !important; height: 40px !important; }
  .metric-ring svg { width: 40px !important; height: 40px !important; }
}
</style>
