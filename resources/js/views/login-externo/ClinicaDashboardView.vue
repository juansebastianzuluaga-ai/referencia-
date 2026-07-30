<template>
  <div class="ph-dashboard h-full flex overflow-hidden">

    <!-- ══ Columna principal ═══════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col gap-2 p-3 sm:p-4 min-w-0 overflow-hidden"
      v-motion
      :initial="{ opacity: 0, y: 20 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 500, ease: 'easeOut' } }">

      <!-- ── Hero banner ── -->
      <div class="hero-card rounded-2xl p-3 sm:p-4 flex items-center gap-4 relative overflow-hidden shrink-0 anim-fade-down">
        <div class="hero-glow"></div>
        <div class="hero-pattern"></div>
        <div class="hero-glow-2"></div>
        <div class="flex items-center gap-3 z-10 shrink-0">
          <div class="hero-logo">
            <component :is="ClipboardListIcon" class="w-6 h-6" />
          </div>
        </div>
        <div class="flex-1 min-w-0 z-10">
          <div class="flex items-center gap-2 mb-1">
            <span class="hero-live-dot"></span>
            <p class="text-[10px] font-semibold uppercase tracking-wider" style="color:rgba(255,255,255,0.6);">Sistema de referencia · En línea</p>
          </div>
          <h1 class="text-sm sm:text-lg font-bold leading-tight text-white">
            {{ saludoTexto }}, <span class="clinic-name font-extrabold">{{ clinicaAuth.clinica?.nombre }}</span>
          </h1>
          <p class="text-[11px] sm:text-xs mt-1" style="color:rgba(255,255,255,0.65);">{{ fechaHoy }} · Gestione sus remisiones al centro de referencia.</p>
        </div>
        <div class="hero-right z-10 shrink-0 hidden sm:flex flex-col items-end gap-2">
          <button @click="router.push('/clinica/solicitud')" class="hero-link">
            <component :is="PlusIcon" class="w-4 h-4" />
            <span>Nueva solicitud</span>
          </button>
          <div class="hero-mini-stats">
            <div class="hero-mini-stat">
              <span class="hero-mini-stat-num">{{ stats.total }}</span>
              <span class="hero-mini-stat-label">Total</span>
            </div>
            <div class="hero-mini-divider"></div>
            <div class="hero-mini-stat">
              <span class="hero-mini-stat-num">{{ stats.aceptadas }}</span>
              <span class="hero-mini-stat-label">Aceptadas</span>
            </div>
            <div class="hero-mini-divider"></div>
            <div class="hero-mini-stat">
              <span class="hero-mini-stat-num">{{ tiempoPromedio }}</span>
              <span class="hero-mini-stat-label">Respuesta</span>
            </div>
          </div>
        </div>
        <span class="hero-pill" style="top:18%; right:28%; background:rgba(255,255,255,0.12); width:8px; height:8px;"></span>
        <span class="hero-pill" style="top:62%; right:12%; background:rgba(126,179,255,0.3); width:16px; height:16px;"></span>
        <span class="hero-pill" style="bottom:20%; right:35%; background:rgba(255,255,255,0.08); width:10px; height:10px;"></span>
      </div>

      <!-- ── Stat cards ── -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 shrink-0">
        <template v-if="cargando">
          <div v-for="i in 4" :key="i" class="stat-card rounded-2xl p-3 flex flex-col gap-1.5">
            <div class="flex items-center gap-2 mb-1">
              <div class="shimmer-box" style="width:36px; height:36px; border-radius:12px;"></div>
              <div class="shimmer-bar" style="width:50px; height:12px;"></div>
            </div>
            <div class="shimmer-bar" style="width:70%; height:22px;"></div>
            <div class="shimmer-bar" style="width:40%; height:6px; border-radius:999px;"></div>
          </div>
        </template>
        <template v-else>
          <div
            v-for="(card, i) in statCards" :key="i"
            class="stat-card rounded-2xl p-3 flex flex-col gap-1 anim-slide-up"
            :style="{ animationDelay: (i * 0.08) + 's', '--accent': card.color, '--icon-bg': card.iconBg, '--icon-color': card.color, '--delta-bg': card.deltaBg, '--delta-color': card.deltaColor }"
          >
            <div class="stat-card-glow" :style="{ background: 'radial-gradient(circle at 80% 20%, ' + card.color + '15, transparent 60%)' }"></div>
            <div class="flex items-center justify-between relative z-10">
              <div class="stat-icon-wrap">
                <component :is="card.icon" class="w-4 h-4" />
              </div>
              <span class="stat-delta-badge">{{ card.delta }}</span>
            </div>
            <div class="mt-auto relative z-10">
              <p class="stat-value">{{ displayStats[i] }}</p>
              <p class="stat-label">{{ card.label }}</p>
            </div>
            <div class="stat-progress-track relative z-10">
              <div class="stat-progress-fill" :style="{ width: statPercents[i] + '%' }"></div>
            </div>
          </div>
        </template>
      </div>

      <!-- ── Distribución + Donut ── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 shrink-0">
        <div v-if="cargando" class="distrib-card rounded-2xl p-3 flex items-center gap-4 lg:col-span-2">
          <div class="flex-1">
            <div class="flex items-center justify-between mb-2">
              <div class="shimmer-bar" style="width:140px; height:12px;"></div>
              <div class="shimmer-bar" style="width:60px; height:10px;"></div>
            </div>
            <div class="shimmer-bar w-full" style="height:10px; border-radius:999px;"></div>
          </div>
        </div>
        <div v-else class="distrib-card rounded-2xl p-3 flex items-center gap-4 anim-slide-up lg:col-span-2" style="animation-delay:0.16s">
          <div class="distrib-card-glow"></div>
          <div class="flex-1 relative z-10">
            <div class="flex items-center justify-between mb-2">
              <p class="text-xs font-bold" style="color:#1e2d55;">Distribución de solicitudes</p>
              <p class="text-[10px]" style="color:#8a9ab5;">{{ stats.total }} en total</p>
            </div>
            <div class="flex h-2.5 rounded-full overflow-hidden" style="background:#edf2f7;">
              <div v-if="stats.pendientes" class="distrib-segment transition-all duration-700" :style="{ width: (stats.pendientes / Math.max(1, stats.total)) * 100 + '%', background: 'linear-gradient(90deg, #fbbf24, #f59e0b)' }"
                :data-tooltip="`${stats.pendientes} pendientes (${Math.round(stats.pendientes / Math.max(1, stats.total) * 100)}%)`"></div>
              <div v-if="stats.aceptadas" class="distrib-segment transition-all duration-700" :style="{ width: (stats.aceptadas / Math.max(1, stats.total)) * 100 + '%', background: 'linear-gradient(90deg, #4ade80, #22c55e)' }"
                :data-tooltip="`${stats.aceptadas} aceptadas (${Math.round(stats.aceptadas / Math.max(1, stats.total) * 100)}%)`"></div>
              <div v-if="stats.negadas" class="distrib-segment transition-all duration-700" :style="{ width: (stats.negadas / Math.max(1, stats.total)) * 100 + '%', background: 'linear-gradient(90deg, #f87171, #ef4444)' }"
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

        <!-- Donut chart CSS -->
        <div v-if="!cargando" class="distrib-card rounded-2xl p-3 flex items-center justify-center gap-4 anim-slide-up" style="animation-delay:0.2s">
          <div class="donut-chart relative z-10" :style="donutStyle">
            <div class="donut-center">
              <span class="donut-num">{{ stats.total }}</span>
              <span class="donut-label">Total</span>
            </div>
          </div>
          <div class="flex flex-col gap-1.5 relative z-10">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full" style="background:#fbbf24;"></span>
              <span class="text-[10px] font-semibold" style="color:#d97706;">{{ Math.round(donutPercents[0]) }}%</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full" style="background:#22c55e;"></span>
              <span class="text-[10px] font-semibold" style="color:#16a34a;">{{ Math.round(donutPercents[1]) }}%</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full" style="background:#f87171;"></span>
              <span class="text-[10px] font-semibold" style="color:#dc2626;">{{ Math.round(donutPercents[2]) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Accesos rápidos + Flujo ── -->
      <div class="operations-grid anim-slide-up" style="animation-delay:0.24s">
        <section class="operations-card">
          <div class="operations-glow"></div>
          <div class="operations-heading">
            <div>
              <p class="panel-eyebrow">Navegación operativa</p>
              <h3 class="panel-title">Accesos rápidos</h3>
            </div>
            <span class="operations-hint">Gestione sus remisiones desde un solo lugar</span>
          </div>

          <div class="quick-actions">
            <button @click="abrirFormulario" class="quick-action quick-action-blue">
              <div class="quick-action-icon">
                <component :is="PlusIcon" />
              </div>
              <div class="quick-action-copy">
                <strong>Nueva solicitud</strong>
                <span>Registrar una remisión</span>
              </div>
              <component :is="ArrowRightIcon" class="quick-action-arrow" />
            </button>

            <button @click="irHistorial" class="quick-action quick-action-green">
              <div class="quick-action-icon">
                <component :is="ClipboardListIcon" />
              </div>
              <div class="quick-action-copy">
                <strong>Historial</strong>
                <span>{{ stats.total }} solicitudes registradas</span>
              </div>
              <component :is="ArrowRightIcon" class="quick-action-arrow" />
            </button>

            <button @click="cargar" class="quick-action quick-action-purple">
              <div class="quick-action-icon">
                <component :is="RefreshCwIcon" :class="{ 'animate-spin': cargando }" />
              </div>
              <div class="quick-action-copy">
                <strong>Actualizar</strong>
                <span>Sincronizar información</span>
              </div>
              <component :is="ArrowRightIcon" class="quick-action-arrow" />
            </button>
          </div>
        </section>

        <section class="flow-card">
          <div class="flow-orbit flow-orbit-one"></div>
          <div class="flow-orbit flow-orbit-two"></div>
          <div class="flow-content">
            <div class="flow-heading">
              <div>
                <p class="flow-eyebrow">Proceso asistencial</p>
                <h3>Flujo de referencia</h3>
              </div>
              <span class="flow-live"><i></i> En línea</span>
            </div>

            <div class="flow-steps">
              <div class="flow-step">
                <div class="flow-step-icon">
                  <component :is="ClipboardListIcon" />
                </div>
                <div>
                  <strong>Recepción</strong>
                  <span>Ingreso de solicitud</span>
                </div>
              </div>
              <div class="flow-connector"><span></span></div>
              <div class="flow-step">
                <div class="flow-step-icon">
                  <component :is="StethoscopeIcon" />
                </div>
                <div>
                  <strong>Evaluación</strong>
                  <span>Revisión clínica</span>
                </div>
              </div>
              <div class="flow-connector"><span></span></div>
              <div class="flow-step">
                <div class="flow-step-icon flow-step-success">
                  <component :is="CheckCircle2Icon" />
                </div>
                <div>
                  <strong>Respuesta</strong>
                  <span>Decisión asistencial</span>
                </div>
              </div>
            </div>
          </div>
        </section>
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
          { borderLeftColor: sol.estado === 'pendiente' ? '#fbbf24' : sol.estado === 'aceptado' ? '#22c55e' : sol.estado === 'en_espera' ? '#3b82f6' : sol.estado === 'completado' ? '#6366f1' : '#f87171' },
          { animationDelay: (idx * 0.06) + 's' }
        ]"
            @click="verDetalle(sol)"
          >
            <div class="med-icon w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
              :style="{ background: sol.estado === 'pendiente' ? '#fef3c7' : sol.estado === 'aceptado' ? '#dcfce7' : sol.estado === 'en_espera' ? '#dbeafe' : sol.estado === 'completado' ? '#e0e7ff' : '#fee2e2', color: sol.estado === 'pendiente' ? '#d97706' : sol.estado === 'aceptado' ? '#16a34a' : sol.estado === 'en_espera' ? '#2563eb' : sol.estado === 'completado' ? '#4f46e5' : '#dc2626' }">
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
                :stroke="sol.estado === 'pendiente' ? '#eab308' : sol.estado === 'aceptado' ? '#22c55e' : sol.estado === 'en_espera' ? '#3b82f6' : sol.estado === 'completado' ? '#6366f1' : '#f87171'" />
            </svg>
            <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0"
              :style="{
                background: sol.estado === 'pendiente' ? '#fff3cd' : sol.estado === 'aceptado' ? '#d3f9d8' : sol.estado === 'en_espera' ? '#d0e3ff' : sol.estado === 'completado' ? '#e0e0ff' : '#ffe0e0',
                color: sol.estado === 'pendiente' ? '#b96800' : sol.estado === 'aceptado' ? '#2f9e44' : sol.estado === 'en_espera' ? '#2563eb' : sol.estado === 'completado' ? '#4f46e5' : '#c92a2a',
              }"
            >
              {{ estadoLabel(sol.estado) }}
            </span>
          </div>
        </div>
      </div>

    </div>


    <!-- ── Modales ──────────────────────────────────────────────────────── -->

    <!-- ── Modal: Detalle ──────────────────────────────────────────────── -->
    <el-dialog v-model="modalDetalle" width="640px" class="detalle-dialog" :show-close="true" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <!-- Header con gradiente azul -->
          <div class="detalle-head">
            <div class="detalle-head-glow"></div>
            <div class="detalle-head-icon">
              <component
                :is="solicitudSeleccionada.estado === 'aceptado' ? CheckCircleIcon : solicitudSeleccionada.estado === 'negado' ? XCircleIcon : solicitudSeleccionada.estado === 'en_espera' ? ClockIcon : solicitudSeleccionada.estado === 'completado' ? CheckCircleIcon : ClockIcon"
                class="w-7 h-7"
              />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Detalle de solicitud</p>
              <p class="detalle-head-sub">{{ formatFecha(solicitudSeleccionada.created_at) }}</p>
            </div>
            <div class="detalle-head-id-badge">ID #{{ solicitudSeleccionada.id }}</div>
            <div class="detalle-head-badge" :class="'badge-' + solicitudSeleccionada.estado">
              {{ estadoLabel(solicitudSeleccionada.estado) }}
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
                <div v-if="solicitudSeleccionada.quien_remitente" class="data-row"><span>Remite</span><strong>{{ solicitudSeleccionada.quien_remitente }}</strong></div>
                <div v-if="solicitudSeleccionada.telefono_contacto" class="data-row"><span>Teléfono</span><strong>{{ solicitudSeleccionada.telefono_contacto }}</strong></div>
                <div v-if="solicitudSeleccionada.correo_contacto" class="data-row"><span>Correo</span><strong>{{ solicitudSeleccionada.correo_contacto }}</strong></div>
              </div>
            </div>
          </div>

          <!-- Diagnósticos + Historia en 2 columnas -->
          <div class="grid grid-cols-2 gap-2.5 mb-2.5">
            <div class="detalle-card">
              <div class="card-icon" style="background:#ede9fe; color:#7c3aed;"><component :is="ClipboardListIcon" class="w-4 h-4" /></div>
              <div class="card-body">
                <p class="card-title">Diagnósticos</p>
                <div v-if="solicitudSeleccionada.diagnosticos?.length" class="card-dx-list">
                  <div v-for="dx in solicitudSeleccionada.diagnosticos" :key="dx.id" class="card-dx-item">
                    <strong class="card-dx-code">{{ dx.codigo_cie10 }}</strong>
                    <span class="card-dx-desc">{{ dx.descripcion }}</span>
                  </div>
                </div>
                <p v-else-if="solicitudSeleccionada.diagnostico" class="card-text">{{ solicitudSeleccionada.diagnostico }}</p>
                <p v-else class="card-text">—</p>
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
                      <span class="adjunto-icon" :class="getAdjuntoBadgeClass(adj.mime_type, adj.nombre_original)">{{ getAdjuntoBadgeLabel(adj.mime_type, adj.nombre_original) }}</span>
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

    <!-- ── Modal: Éxito envío ─────────────────────────────────────────── -->
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  Plus as PlusIcon,
  X as XIcon,
  ClipboardList as ClipboardListIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  RefreshCw as RefreshCwIcon,
  ChevronRight as ChevronRightIcon,
  ArrowRight as ArrowRightIcon,
  Paperclip as PaperclipIcon,
  UploadCloud as UploadCloudIcon,
  Stethoscope as StethoscopeIcon,
  CheckCircle2 as CheckCircle2Icon,
  User as UserIcon,
  UserCheck as UserCheckIcon,
  FileCheck as FileCheckIcon,
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
const showConfirmResumen = ref(false);
const formRef = ref();
const modalDetalle = ref(false);
const solicitudSeleccionada = ref<any>(null);
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

// ── Stat cards config ───────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'Solicitudes totales', icon: ClipboardListIcon, color: '#2563c4', iconBg: '#dbe1ff', delta: stats.value.pendientes ? `${stats.value.pendientes} pend.` : 'sin pendientes', deltaBg: '#dbeafe', deltaColor: '#2563c4' },
  { label: 'Pendientes', icon: ClockIcon, color: '#e67700', iconBg: '#fff3cd', delta: stats.value.pendientes ? 'En espera' : 'Al día', deltaBg: '#fef3c7', deltaColor: '#e67700' },
  { label: 'Aceptadas', icon: CheckCircleIcon, color: '#15966a', iconBg: '#d3f9d8', delta: `${tasaAceptacion.value}% tasa`, deltaBg: '#dcfce7', deltaColor: '#15966a' },
  { label: 'Negadas', icon: XCircleIcon, color: '#c92a2a', iconBg: '#ffe3e3', delta: stats.value.negadas ? 'Revisar' : 'Sin rechazos', deltaBg: '#fee2e2', deltaColor: '#c92a2a' },
]);

const donutPercents = computed(() => {
  const total = Math.max(1, stats.value.total);
  return [
    (stats.value.pendientes / total) * 100,
    (stats.value.aceptadas / total) * 100,
    (stats.value.negadas / total) * 100,
  ];
});

const donutStyle = computed(() => {
  const [p, a, n] = donutPercents.value;
  return {
    background: `conic-gradient(
      #fbbf24 0% ${p}%,
      #22c55e ${p}% ${p + a}%,
      #f87171 ${p + a}% ${p + a + n}%,
      #edf2f7 ${p + a + n}% 100%
    )`,
  };
});

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
  enEspera: solicitudes.value.filter(s => s.estado === 'en_espera').length,
  completadas: solicitudes.value.filter(s => s.estado === 'completado').length,
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

async function cargar() {
  cargando.value = true;
  try {
    const { data } = await http.get('/api/externo/solicitudes');
    solicitudes.value = data.data;
  } catch { ElMessage.error('Error al cargar solicitudes'); }
  finally { cargando.value = false; }
}

function abrirFormulario() { router.push('/clinica/solicitud'); }

function irHistorial() { router.push('/clinica/historial'); }

function formularioTieneDatos(): boolean {
  const f = form.value;
  return !!(f.primer_nombre || f.primer_apellido || f.numero_documento ||
    diagnosticos.value.some(d => d.codigo_cie10 || d.descripcion) ||
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
    if (modalDetalle.value) return;
    e.preventDefault();
    abrirFormulario();
  }
}
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

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function getAdjuntoBadgeLabel(mime: string, nombre: string): string {
  const ext = nombre.split('.').pop()?.toLowerCase() ?? '';
  if (ext === 'pdf' || mime?.includes('pdf')) return 'PDF';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'].includes(ext) || mime?.startsWith('image/')) return 'IMG';
  if (['doc', 'docx'].includes(ext)) return 'DOC';
  if (['xls', 'xlsx', 'csv'].includes(ext)) return 'XLS';
  if (['ppt', 'pptx'].includes(ext)) return 'PPT';
  if (['zip', 'rar'].includes(ext)) return 'ZIP';
  if (['txt'].includes(ext)) return 'TXT';
  return ext.toUpperCase().slice(0, 3) || 'FILE';
}

function getAdjuntoBadgeClass(mime: string, nombre: string): string {
  const ext = nombre.split('.').pop()?.toLowerCase() ?? '';
  if (ext === 'pdf' || mime?.includes('pdf')) return 'adjunto-pdf';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'].includes(ext) || mime?.startsWith('image/')) return 'adjunto-img';
  if (['doc', 'docx'].includes(ext)) return 'adjunto-doc';
  if (['xls', 'xlsx', 'csv'].includes(ext)) return 'adjunto-xls';
  if (['ppt', 'pptx'].includes(ext)) return 'adjunto-ppt';
  if (['zip', 'rar'].includes(ext)) return 'adjunto-zip';
  return 'adjunto-default';
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

function verDetalle(sol: any) { solicitudSeleccionada.value = sol; modalDetalle.value = true; }
function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function initialesPaciente(solicitud: any): string {
  return `${solicitud.primer_nombre?.[0] ?? ''}${solicitud.primer_apellido?.[0] ?? ''}`.toUpperCase();
}

function estadoLabel(estado: string): string {
  return { pendiente: 'Pendiente', aceptado: 'Aceptada', en_espera: 'En espera', completado: 'Completada', negado: 'Negada' }[estado] ?? estado;
}

function tiempoRelativo(fecha: string): string {
  const minutos = Math.max(1, Math.round((Date.now() - new Date(fecha).getTime()) / 60000));
  if (minutos < 60) return `Hace ${minutos} min`;
  const horas = Math.round(minutos / 60);
  if (horas < 24) return `Hace ${horas} h`;
  return `Hace ${Math.round(horas / 24)} días`;
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
    drawerVisible.value = false;
    modalExito.value = true;
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
  if (isOpen) {
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    document.body.style.height = '100vh';
    document.querySelectorAll('main').forEach(el => { (el as HTMLElement).style.overflow = 'hidden'; });
  } else {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    document.body.style.height = '';
    document.querySelectorAll('main').forEach(el => { (el as HTMLElement).style.overflow = ''; });
  }
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
/* ════════════════════════════════════════════════════════════════
   NUEVO FORMULARIO PROFESIONAL — Sistema de Referencia
   Paleta: #0D2D6B (azul institucional), #1E293B (slate), #F8FAFC (fondo)
   ════════════════════════════════════════════════════════════════ */

/* ── Dialog base ── */
:deep(.request-dialog) {
  max-width: calc(100vw - 2rem);
  overflow: hidden;
  border-radius: 16px;
  box-shadow: 0 24px 64px rgba(13, 45, 107, .3);
}
:deep(.request-dialog .el-dialog__header) {
  margin: 0;
  padding: .6rem 1.25rem;
  border-bottom: 2px solid #0D2D6B;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%);
  border-radius: 16px 16px 0 0;
}
:deep(.request-dialog .el-dialog__title) {
  color: #fff;
  font-size: .95rem;
  font-weight: 700;
}
:deep(.request-dialog .el-dialog__headerbtn) {
  position: fixed;
  top: calc(50vh - 50vh + 12px);
  right: calc(50vw - 550px + 12px);
  z-index: 9999;
  width: 30px;
  height: 30px;
  border: 1.5px solid #DC2626;
  border-radius: 8px;
  background: #fff;
  display: grid;
  place-items: center;
  transition: all .2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
}
:deep(.request-dialog .el-dialog__headerbtn .el-dialog__close) {
  color: #DC2626;
  font-size: .9rem;
  font-weight: 700;
}
:deep(.request-dialog .el-dialog__headerbtn:hover) {
  background: #DC2626;
  border-color: #DC2626;
  box-shadow: 0 4px 14px rgba(220, 38, 38, .35);
}
:deep(.request-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #fff; }
:global(.request-dialog .el-dialog__body) {
  max-height: calc(100vh - 3.5rem);
  padding: .5rem 1rem .75rem;
  overflow: hidden;
  background: #F8FAFC;
  display: flex;
  flex-direction: column;
}
:global(.request-dialog .el-dialog) {
  max-height: calc(100vh - .5rem);
  overflow: hidden;
  margin-top: .25rem !important;
  margin-bottom: .25rem !important;
}
:global(html:has(.request-dialog)),
:global(body:has(.request-dialog)) {
  height: 100%;
  overflow: hidden !important;
}
:global(.el-overlay) {
  background-color: rgba(15, 23, 42, .5);
  backdrop-filter: blur(3px);
  overflow: hidden !important;
}

/* ── Form overrides ── */
:deep(.rf-form-col .el-form) { flex: 1; display: flex; flex-direction: column; min-height: 0; }
:deep(.rf-form-col .el-form-item) { margin-bottom: 0; display: flex; flex-direction: column; }
:deep(.rf-form-col .el-form-item__label) {
  color: #475569; font-size: .68rem; font-weight: 600;
  padding-bottom: .1rem; line-height: 1.1;
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
:deep(.rf-form-col .el-select__wrapper) { min-height: 30px; }
:deep(.rf-form-col .el-input-number) { width: 100%; }
:deep(.rf-form-col .el-input-number .el-input__wrapper) { min-height: 30px; }
:deep(.rf-form-col .el-textarea__inner) { min-height: 28px !important; border-radius: 8px; }

/* ── Stepper ── */
.rf-stepper {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: .5rem;
  padding: 0;
}
.rf-step {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex: 1;
  position: relative;
}
.rf-step:not(:last-child)::after {
  content: '';
  flex: 1;
  height: 2px;
  margin: 0 .75rem;
  background: #E2E8F0;
  transition: background .3s ease;
}
.rf-step.active:not(:last-child)::after { background: linear-gradient(90deg, #0D2D6B, #3B82F6); }
.rf-step-num {
  display: grid;
  width: 1.4rem;
  height: 1.4rem;
  place-items: center;
  border-radius: 50%;
  font-size: .68rem;
  font-weight: 700;
  background: #E2E8F0;
  color: #94A3B8;
  flex-shrink: 0;
  transition: all .3s ease;
}
.rf-step.active .rf-step-num {
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  box-shadow: 0 3px 10px rgba(13, 45, 107, .3);
}
.rf-step.current .rf-step-num {
  box-shadow: 0 0 0 4px rgba(13, 45, 107, .15);
}
.rf-step-label {
  font-size: .72rem;
  font-weight: 600;
  color: #94A3B8;
  white-space: nowrap;
  transition: color .3s ease;
}
.rf-step.active .rf-step-label { color: #0D2D6B; }

/* ── Sections ── */
.rf-section {
  background: #fff;
  border: 1px solid #E2E8F0;
  border-top: 3px solid #0D2D6B;
  border-radius: 10px;
  padding: .6rem .75rem;
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 12px rgba(13, 45, 107, .06);
}
.rf-section-header {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: .4rem;
  padding-bottom: .3rem;
  border-bottom: 1px solid #F1F5F9;
}
.rf-section-header svg { color: #0D2D6B; }
.rf-section-header h3 {
  margin: 0;
  font-size: .78rem;
  font-weight: 700;
  color: #0D2D6B;
}

/* ── Grid ── */
.rf-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .35rem .75rem;
}
.rf-col-1 { grid-column: span 1; }
.rf-col-2 { grid-column: span 2; }
.rf-col-3 { grid-column: span 3; }
.rf-mt { margin-top: .4rem; }

/* ── Subsection ── */
.rf-subsection {
  margin-top: .4rem;
  padding-top: .4rem;
  border-top: 1px solid #F1F5F9;
}
.rf-subsection-header {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: .3rem;
}
.rf-subsection-header svg { color: #0D2D6B; }
.rf-subsection-header h4 {
  margin: 0;
  font-size: .78rem;
  font-weight: 600;
  color: #0D2D6B;
  text-transform: uppercase;
  letter-spacing: .03em;
}

/* ── Diagnósticos ── */
.rf-dx-block {
  background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
  border: 1px solid #93C5FD;
  border-radius: 8px;
  padding: .4rem .6rem;
}
.rf-dx-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .25rem;
}
.rf-dx-title {
  font-size: .78rem;
  font-weight: 600;
  color: #1E40AF;
}
.rf-req { color: #DC2626; }
.rf-dx-add {
  font-size: .72rem;
  font-weight: 600;
  color: #0D2D6B;
  border-color: #0D2D6B;
  background: #DBEAFE;
}
.rf-dx-row {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin-bottom: .25rem;
}
.rf-dx-row:last-child { margin-bottom: 0; }
.rf-dx-code { width: 140px; flex-shrink: 0; }
.rf-dx-desc { flex: 1; }
.rf-dx-remove {
  display: grid;
  width: 28px;
  height: 28px;
  place-items: center;
  border: 1px solid #FECACA;
  border-radius: 8px;
  background: #FEF2F2;
  color: #DC2626;
  cursor: pointer;
  flex-shrink: 0;
  transition: all .2s ease;
}
.rf-dx-remove:hover { background: #DC2626; color: #fff; border-color: #DC2626; }
.rf-dx-empty { font-size: .72rem; color: #94A3B8; margin: .25rem 0 0; }

/* ── Attachments ── */
.rf-attach {
  border: 1px solid #BFDBFE;
  border-radius: 8px;
  background: linear-gradient(135deg, #EFF6FF, #F0F9FF);
  padding: .4rem;
  display: flex;
  flex-direction: column;
}
.rf-attach-active { border-color: #0D2D6B; box-shadow: 0 0 0 3px rgba(13, 45, 107, .08); }
.rf-attach-header {
  display: flex;
  align-items: center;
  gap: .3rem;
  margin-bottom: .25rem;
  font-size: .68rem;
  font-weight: 600;
  color: #475569;
}
.rf-attach-header svg { color: #2563EB; }
.rf-attach-count {
  margin-left: auto;
  font-size: .62rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  background: #E0E7FF;
  color: #3730A3;
}
.rf-dropzone {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .1rem;
  min-height: 48px;
  border: 2px dashed #60A5FA;
  border-radius: 6px;
  cursor: pointer;
  transition: all .2s ease;
  text-align: center;
  background: rgba(219, 234, 254, .3);
}
.rf-dropzone:hover { border-color: #0D2D6B; background: #DBEAFE; }
.rf-dropzone svg { color: #2563EB; }
.rf-dropzone p { margin: 0; font-size: .72rem; font-weight: 600; color: #1E40AF; }
.rf-dropzone small { font-size: .62rem; color: #94A3B8; }
.rf-file-list { margin-top: .25rem; display: flex; flex-direction: column; gap: .15rem; }
.rf-file-chip {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .5rem;
  border-radius: 6px;
  background: #F1F5F9;
  border: 1px solid #E2E8F0;
}
.rf-file-badge {
  font-size: .58rem;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  background: #DBEAFE;
  color: #1D4ED8;
  flex-shrink: 0;
}
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

/* ── Layout 2 columnas: formulario + panel lateral ── */
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
  overflow: hidden;
}

/* ── Panel lateral: resumen en tiempo real ── */
.rf-summary-panel {
  width: 260px;
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
  padding: .5rem .65rem;
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  font-size: .72rem;
  font-weight: 700;
  border-radius: 10px 10px 0 0;
}
.rf-summary-panel-header svg { color: #fff; }
.rf-summary-panel-body {
  padding: .5rem .65rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: .4rem;
}
.rf-sp-block {
  display: flex;
  flex-direction: column;
  gap: .05rem;
  padding-bottom: .35rem;
  border-bottom: 1px solid #DBEAFE;
}
.rf-sp-block:last-child { border-bottom: none; padding-bottom: 0; }
.rf-sp-label {
  font-size: .58rem;
  font-weight: 700;
  color: #64748B;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.rf-sp-value {
  font-size: .72rem;
  font-weight: 700;
  color: #0D2D6B;
  word-break: break-word;
}
.rf-sp-sub {
  font-size: .62rem;
  color: #3B82F6;
  font-weight: 500;
}
.rf-sp-dx-list { display: flex; flex-direction: column; gap: .15rem; margin-top: .1rem; }
.rf-sp-dx-item {
  display: flex;
  align-items: baseline;
  gap: .3rem;
  padding: .15rem .3rem;
  border-radius: 4px;
  background: rgba(255, 255, 255, .7);
}
.rf-sp-dx-code {
  font-size: .62rem;
  font-weight: 800;
  color: #15803D;
  font-family: monospace;
  flex-shrink: 0;
}
.rf-sp-dx-desc { font-size: .62rem; color: #166534; }
.rf-sp-empty { font-size: .62rem; color: #94A3B8; font-style: italic; }

/* ── Dialog de confirmación ── */
:deep(.confirm-dialog) {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(13, 45, 107, .25);
}
:deep(.confirm-dialog .el-dialog__header) {
  display: none;
}
:deep(.confirm-dialog .el-dialog__body) {
  padding: 0;
}
.confirm-body {
  padding: 1.5rem 1.5rem 1rem;
  text-align: center;
}
.confirm-icon-wrap {
  display: inline-grid;
  place-items: center;
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
  color: #0D2D6B;
  margin-bottom: .75rem;
}
.confirm-title {
  margin: 0 0 .25rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0D2D6B;
}
.confirm-subtitle {
  margin: 0 0 1rem;
  font-size: .78rem;
  color: #64748B;
}
.confirm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .5rem .75rem;
  text-align: left;
}
.confirm-item {
  display: flex;
  flex-direction: column;
  gap: .1rem;
  padding: .4rem .6rem;
  background: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 8px;
}
.confirm-item-full {
  grid-column: span 2;
}
.confirm-item span {
  font-size: .62rem;
  font-weight: 600;
  color: #94A3B8;
  text-transform: uppercase;
  letter-spacing: .03em;
}
.confirm-item strong {
  font-size: .75rem;
  font-weight: 700;
  color: #0D2D6B;
  word-break: break-word;
}
.confirm-dx-list {
  display: flex;
  flex-direction: column;
  gap: .2rem;
}
.confirm-dx-item {
  display: flex;
  align-items: baseline;
  gap: .4rem;
  padding: .2rem .4rem;
  border-radius: 6px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
}
.confirm-dx-code {
  font-size: .68rem;
  font-weight: 800;
  color: #15803D;
  font-family: monospace;
  flex-shrink: 0;
}
.confirm-dx-desc { font-size: .68rem; color: #166534; }
.confirm-footer {
  display: flex;
  gap: .75rem;
  padding: 0 1.5rem 1.25rem;
}

/* ── Footer buttons ── */
.rf-footer {
  display: flex;
  gap: .5rem;
  margin-top: .4rem;
  padding-top: .4rem;
  border-top: 1px solid #E2E8F0;
  flex-shrink: 0;
}
.rf-btn-back {
  flex: 1;
  border-radius: 8px;
  font-weight: 600;
  color: #475569;
  border-color: #CBD5E1;
  background: #fff;
}
.rf-btn-back:hover { color: #0D2D6B; border-color: #0D2D6B; background: #EFF6FF; }
.rf-btn-next {
  flex: 1;
  border-radius: 8px;
  font-weight: 600;
  background: linear-gradient(135deg, #0D2D6B, #16468E) !important;
  border-color: #0D2D6B !important;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .25);
}
.rf-btn-next:hover { background: linear-gradient(135deg, #16468E, #1A4A9E) !important; border-color: #16468E !important; box-shadow: 0 6px 16px rgba(13, 45, 107, .35); }
.rf-btn-submit {
  flex: 1;
  border-radius: 8px;
  font-weight: 600;
  background: linear-gradient(135deg, #0D2D6B, #16468E) !important;
  border-color: #0D2D6B !important;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .25);
}
.rf-btn-submit:hover { background: linear-gradient(135deg, #16468E, #1A4A9E) !important; border-color: #16468E !important; box-shadow: 0 6px 16px rgba(13, 45, 107, .35); }

/* ── Card dx (detalle modal) ── */
.card-dx-list { display: flex; flex-direction: column; gap: 6px; margin-top: 4px; }
.card-dx-item {
  display: flex; align-items: baseline; gap: 6px;
  padding: 4px 8px; border-radius: 6px;
  background: #f5f3ff;
}
.card-dx-code {
  font-size: 11px; font-weight: 800; color: #7c3aed;
  font-family: monospace; flex-shrink: 0;
}
.card-dx-desc { font-size: 11px; color: #334155; }

@media (max-height: 750px) {
  :global(.request-dialog .el-dialog__body) { padding: .35rem .75rem .5rem; }
  .rf-section { padding: .4rem .5rem; }
  .rf-grid { gap: .25rem .5rem; }
  .rf-stepper { margin-bottom: .35rem; }
  .rf-step-num { width: 1.25rem; height: 1.25rem; font-size: .62rem; }
  .rf-step-label { font-size: .68rem; }
  :deep(.rf-form-col .el-input__wrapper),
  :deep(.rf-form-col .el-select__wrapper) { min-height: 28px; }
}

/* ── Modal Éxito ── */
:deep(.exito-dialog) { border-radius: 20px; overflow: hidden; box-shadow: 0 28px 70px rgba(11, 35, 73, .35); }
:deep(.exito-dialog .el-dialog__header) { display: none; }
:deep(.exito-dialog .el-dialog__body) { padding: 0; }
.exito-content {
  padding: 2rem 1.8rem 1.6rem;
  text-align: center;
  background: linear-gradient(180deg, #f0f5ff 0%, #f8faff 40%, #ffffff 100%);
}
.exito-icon-circle {
  width: 64px; height: 64px;
  margin: 0 auto 1rem;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
  box-shadow: 0 8px 24px rgba(34, 197, 94, .3);
  animation: exito-pop .4s cubic-bezier(.22, 1, .36, 1);
}
@keyframes exito-pop {
  0% { transform: scale(0); opacity: 0; }
  60% { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}
.exito-title { font-size: 1.3rem; font-weight: 800; color: #0d2d5e; margin: 0 0 .3rem; }
.exito-subtitle { font-size: .8rem; color: #61748d; margin: 0 0 1.3rem; }
.exito-details {
  text-align: left;
  background: #fff;
  border: 1px solid #e0e8f5;
  border-radius: 14px;
  padding: .8rem 1rem;
  margin-bottom: 1.3rem;
}
.exito-detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: .35rem 0;
  border-bottom: 1px solid #f0f4fa;
  font-size: .78rem;
}
.exito-detail-row:last-child { border-bottom: none; }
.exito-detail-row span { color: #61748d; font-weight: 600; }
.exito-detail-row strong { color: #0d2d5e; font-weight: 700; text-align: right; max-width: 60%; word-break: break-word; }
.exito-btn { border-radius: 8px; font-weight: 600; height: 42px; background: #0D2D6B !important; border-color: #0D2D6B !important; }
.exito-btn:hover { background: #16468E !important; border-color: #16468E !important; }

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
.detalle-head-id-badge {
  flex-shrink: 0; z-index: 1;
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 8px;
  padding: .35rem .7rem;
  font-size: .72rem; font-weight: 800;
  color: #fff;
  letter-spacing: 0.03em;
  font-family: monospace;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

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
.badge-en_espera { background: #3b82f6; color: #fff; }
.badge-completado { background: #6366f1; color: #fff; }
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
.attachments-card {
  border: 2px dashed #cbd5e1;
  background: #f8fafc;
  transition: all 0.2s ease;
}
.attachments-card.dropzone-active {
  border-color: #16468e;
  background: #eff6ff;
  box-shadow: 0 0 0 3px rgba(22, 70, 142, 0.1);
}
.attach-header-icon {
  width: 24px; height: 24px;
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  background: #e0e8f5;
  color: #16468e;
  flex-shrink: 0;
}
.dropzone {
  border: 2px dashed #cbd5e1;
  background: #fff;
  transition: all 0.2s ease;
}
.dropzone:hover {
  border-color: #16468e;
  background: #f0f7ff;
}
.dropzone-circle {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #e0e8f5 0%, #cdd9ee 100%);
  color: #16468e;
  transition: all 0.2s ease;
}
.dropzone:hover .dropzone-circle {
  background: linear-gradient(135deg, #16468e 0%, #0D2D6B 100%);
  color: #fff;
  transform: scale(1.08);
}
.file-chip {
  background: #fff;
  border: 1px solid #e2e8f0;
  transition: all 0.15s ease;
}
.file-chip:hover {
  border-color: #94a3b8;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.file-type-badge {
  font-size: 8px;
  font-weight: 800;
  color: #fff;
  padding: 3px 6px;
  border-radius: 5px;
  flex-shrink: 0;
  letter-spacing: 0.5px;
}
.ft-pdf { background: #dc2626; }
.ft-img { background: #2563eb; }
.ft-doc { background: #1d4ed8; }
.ft-xls { background: #16a34a; }
.ft-ppt { background: #ea580c; }
.ft-zip { background: #7c3aed; }
.ft-default { background: #64748b; }
.file-remove-btn {
  color: #cbd5e1;
  transition: color 0.15s ease;
  flex-shrink: 0;
  display: flex; align-items: center;
}
.file-remove-btn:hover { color: #ef4444; }

/* File list transition */
.file-list-enter-active, .file-list-leave-active {
  transition: all 0.25s ease;
}
.file-list-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}
.file-list-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

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
.adjunto-doc { background: #1d4ed8; }
.adjunto-xls { background: #16a34a; }
.adjunto-ppt { background: #ea580c; }
.adjunto-zip { background: #7c3aed; }
.adjunto-default { background: #64748b; }
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

.ph-dashboard {
  background:
    radial-gradient(ellipse at 90% 0%, rgba(188, 218, 255, 0.5), transparent 30rem),
    radial-gradient(ellipse at 10% 100%, rgba(208, 230, 255, 0.4), transparent 28rem),
    linear-gradient(160deg, #e8f0fc 0%, #dbe7f6 40%, #eef4fc 100%);
}

/* ── Hero banner ── */
.hero-card {
  background: linear-gradient(115deg, #0d2d6b 0%, #16468e 55%, #1e3a7a 100%);
  border: 1px solid #1e3a7a;
  box-shadow: 0 8px 24px rgba(13, 45, 107, .25);
  position: relative;
  overflow: hidden;
}
.hero-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #2563eb, #60a5fa, #2563eb);
  background-size: 200% 100%;
  animation: heroShimmer 3s linear infinite;
  z-index: 20;
}
@keyframes heroShimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
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

.hero-logo {
  width: 44px; height: 44px;
  border-radius: 12px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.15);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(8px);
}
.hero-mini-stats {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .4rem .75rem;
  border-radius: 10px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.08);
}
.hero-mini-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.hero-mini-stat-num {
  font-size: 13px;
  font-weight: 800;
  color: #fff;
  line-height: 1;
}
.hero-mini-stat-label {
  font-size: 8px;
  color: rgba(255,255,255,0.5);
  margin-top: 2px;
  text-transform: uppercase;
  letter-spacing: .05em;
  white-space: nowrap;
}
.hero-mini-divider {
  width: 1px; height: 20px;
  background: rgba(255,255,255,0.12);
}

.clinic-name::first-letter {
  font-size: 1.6em;
  font-weight: 900;
}

/* ── Stat cards ── */
.stat-card {
  background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, color-mix(in srgb, var(--accent) 12%, white) 100%);
  border: 2px solid color-mix(in srgb, var(--accent) 35%, transparent);
  box-shadow: 0 10px 28px color-mix(in srgb, var(--accent) 18%, transparent), 0 0 0 1px rgba(255,255,255,0.5) inset;
  transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1), border-color .3s ease;
  position: relative;
  overflow: hidden;
}
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 5px; height: 100%;
  background: var(--accent);
  opacity: .9;
}
.stat-card-glow {
  content: '';
  position: absolute;
  top: -30px; right: -30px;
  width: 90px; height: 90px;
  border-radius: 50%;
  filter: blur(20px);
  pointer-events: none;
}
.stat-card:hover {
  transform: translateY(-8px) scale(1.03);
  box-shadow: 0 24px 48px color-mix(in srgb, var(--accent) 26%, transparent);
  border-color: var(--accent);
}
.stat-icon-wrap {
  width: 36px; height: 36px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: var(--icon-bg);
  color: var(--icon-color);
  box-shadow: 0 4px 14px color-mix(in srgb, var(--icon-color) 25%, transparent);
  transition: transform .25s ease, box-shadow .25s ease;
}
.stat-card:hover .stat-icon-wrap {
  transform: scale(1.15) rotate(-6deg);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--icon-color) 40%, transparent);
}
.stat-delta-badge {
  font-size: 9px;
  font-weight: 800;
  padding: 3px 9px;
  border-radius: 999px;
  white-space: nowrap;
  background: var(--delta-bg);
  color: var(--delta-color);
  box-shadow: 0 2px 6px rgba(0,0,0,.06);
}
.stat-value {
  font-size: 24px;
  font-weight: 900;
  line-height: 1;
  letter-spacing: -.02em;
  color: #1e293b;
  text-shadow: 0 1px 0 rgba(255,255,255,0.8);
}
.stat-label {
  font-size: 9px;
  font-weight: 800;
  color: #475569;
  margin-top: 2px;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.stat-progress-track {
  height: 5px;
  border-radius: 999px;
  background: rgba(255,255,255,0.6);
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(0,0,0,.08);
}
.stat-progress-fill {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--accent), color-mix(in srgb, var(--accent) 60%, white));
  box-shadow: 0 0 8px color-mix(in srgb, var(--accent) 50%, transparent);
  transition: width .8s cubic-bezier(.22,1,.36,1);
}

/* ── Distribución + Donut ── */
.distrib-card {
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
  border-radius: 14px;
  position: relative;
  overflow: hidden;
  transition: transform .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1);
}
.distrib-card-glow {
  position: absolute; top: -40px; right: -40px; width: 140px; height: 140px;
  border-radius: 50%; pointer-events: none;
  background: radial-gradient(circle, rgba(126,179,255,0.12), transparent 70%);
}
.distrib-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(22,70,142,.14); }
.distrib-segment { position: relative; cursor: pointer; transition: opacity .2s ease; }
.distrib-segment:hover { opacity: .85; }
.distrib-segment::after {
  content: attr(data-tooltip); position: absolute; bottom: calc(100% + 6px); left: 50%;
  transform: translateX(-50%); background: #1e2d55; color: #fff;
  font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 6px;
  white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity .2s ease; z-index: 20;
}
.distrib-segment:hover::after { opacity: 1; }

.donut-chart {
  width: 64px; height: 64px; border-radius: 50%;
  position: relative;
  transition: transform .3s ease;
  box-shadow: 0 4px 14px rgba(22,70,142,.14);
}
.donut-chart:hover { transform: scale(1.08) rotate(5deg); }
.donut-center {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  width: 46px; height: 46px; border-radius: 50%; background: #fff;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  box-shadow: inset 0 2px 6px rgba(22,70,142,.08);
}
.donut-num { font-size: 14px; font-weight: 800; color: #1e2d55; line-height: 1; }
.donut-label { font-size: 7px; color: #8a9ab5; margin-top: 2px; text-transform: uppercase; letter-spacing: .05em; }

/* ── Operations / Accesos rápidos ── */
.operations-grid {
  display: grid;
  grid-template-columns: minmax(0, 3fr) minmax(280px, 2fr);
  gap: .5rem;
  flex-shrink: 0;
}
.operations-card,
.flow-card {
  position: relative;
  overflow: hidden;
  border-radius: 1rem;
}
.operations-card {
  padding: .85rem;
  background: rgba(255, 255, 255, .92);
  border: 1px solid rgba(212, 222, 234, .65);
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
}
.operations-card::before {
  content: '';
  position: absolute;
  inset: 0 0 auto;
  height: 3px;
  background: linear-gradient(90deg, #0D2D6B, #3978cf, #8dbdff);
}
.operations-glow {
  position: absolute;
  right: -55px;
  bottom: -70px;
  width: 160px;
  height: 160px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126, 179, 255, .16), transparent 70%);
  pointer-events: none;
}
.operations-heading,
.flow-heading {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .75rem;
  margin-bottom: .6rem;
}
.operations-hint {
  color: #94a3b8;
  font-size: 9px;
  font-weight: 500;
  white-space: nowrap;
}
.quick-actions {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .5rem;
}
.quick-action {
  --quick-color: #2563c4;
  --quick-bg: #e7efff;
  position: relative;
  display: flex;
  align-items: center;
  min-width: 0;
  gap: .5rem;
  padding: .6rem;
  border: 1px solid rgba(226, 232, 240, .85);
  border-radius: .75rem;
  background: linear-gradient(145deg, #fff, #f8fafc);
  color: inherit;
  cursor: pointer;
  text-decoration: none;
  transition: transform .25s cubic-bezier(.22,1,.36,1), border-color .25s ease, box-shadow .25s ease;
}
.quick-action::after {
  content: '';
  position: absolute;
  inset: auto .8rem 0;
  height: 2px;
  border-radius: 999px 999px 0 0;
  background: var(--quick-color);
  opacity: 0;
  transform: scaleX(.4);
  transition: opacity .25s ease, transform .25s ease;
}
.quick-action:hover {
  transform: translateY(-3px);
  border-color: color-mix(in srgb, var(--quick-color) 30%, white);
  box-shadow: 0 10px 22px rgba(22, 70, 142, .11);
}
.quick-action:hover::after {
  opacity: 1;
  transform: scaleX(1);
}
.quick-action-green { --quick-color: #15966a; --quick-bg: #dcfce7; }
.quick-action-purple { --quick-color: #7048e8; --quick-bg: #ede9fe; }
.quick-action-icon {
  width: 30px;
  height: 30px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 9px;
  color: var(--quick-color);
  background: var(--quick-bg);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
}
.quick-action-icon svg { width: 14px; height: 14px; }
.quick-action-copy {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
  text-align: left;
}
.quick-action-copy strong {
  color: #1e2d55;
  font-size: 10px;
  font-weight: 800;
}
.quick-action-copy span {
  overflow: hidden;
  margin-top: 1px;
  color: #8a9ab5;
  font-size: 8px;
  line-height: 1.3;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.quick-action-arrow {
  width: 12px;
  height: 12px;
  flex: 0 0 auto;
  color: #c3ccda;
  transition: color .2s ease, transform .2s ease;
}
.quick-action:hover .quick-action-arrow {
  color: var(--quick-color);
  transform: translateX(3px);
}
.flow-card {
  padding: .85rem;
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212, 222, 234, 0.6);
  box-shadow: 0 4px 16px rgba(22, 70, 142, .08);
}
.flow-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, #15966a, #4ade80, #86efac);
}
.flow-orbit {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
}
.flow-orbit-one {
  top: -60px;
  right: -40px;
  width: 140px;
  height: 140px;
  background: radial-gradient(circle, rgba(74,222,128,.10), transparent 70%);
}
.flow-orbit-two {
  right: 30px;
  bottom: -70px;
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(126,179,255,.08), transparent 70%);
}
.flow-content { position: relative; z-index: 1; }
.flow-eyebrow {
  margin: 0;
  color: #8a9ab5;
  font-size: 8px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
}
.flow-heading h3 {
  margin: 2px 0 0;
  color: #1e2d55;
  font-size: 13px;
  font-weight: 800;
}
.flow-live {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .22rem .5rem;
  border: 1px solid rgba(74,222,128,.2);
  border-radius: 999px;
  background: #dcfce7;
  color: #15966a;
  font-size: 8px;
  font-weight: 600;
  white-space: nowrap;
}
.flow-live i {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 0 3px rgba(74,222,128,.16);
  animation: livePulse 2s ease-in-out infinite;
}
.flow-steps {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .4rem;
}
.flow-step {
  display: flex;
  min-width: 0;
  align-items: center;
  gap: .4rem;
}
.flow-step-icon {
  width: 28px;
  height: 28px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 9px;
  background: #e7efff;
  color: #2563c4;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
  transition: transform .25s ease, box-shadow .25s ease;
}
.flow-step:hover .flow-step-icon {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(37,99,196,.15);
}
.flow-step-icon svg { width: 13px; height: 13px; }
.flow-step-success {
  background: #dcfce7;
  color: #15966a;
}
.flow-step div:last-child {
  display: flex;
  min-width: 0;
  flex-direction: column;
}
.flow-step strong {
  color: #1e2d55;
  font-size: 9px;
  font-weight: 700;
}
.flow-step span {
  overflow: hidden;
  margin-top: 1px;
  color: #8a9ab5;
  font-size: 7px;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.flow-connector {
  position: relative;
  min-width: 14px;
  height: 2px;
  flex: 1;
  overflow: hidden;
  border-radius: 999px;
  background: #e2e8f0;
}
.flow-connector span {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, #2563c4, transparent);
  animation: flowMove 2.6s linear infinite;
}
@keyframes flowMove {
  from { transform: translateX(-100%); }
  to { transform: translateX(100%); }
}
@media (max-width: 1100px) {
  .operations-grid { grid-template-columns: 1fr; }
}
@media (max-width: 720px) {
  .quick-actions { grid-template-columns: 1fr; }
  .operations-hint { display: none; }
  .flow-steps { align-items: stretch; flex-direction: column; }
  .flow-connector { width: 1px; min-width: 1px; height: 10px; margin-left: 13px; flex: 0 0 auto; }
}

/* ── Filas tipo medicamento ── */
.med-row {
  background: linear-gradient(135deg, #ffffff 0%, #f0f6ff 100%);
  border: 1px solid #c5d5e8;
  border-left: 3px solid transparent;
  box-shadow: 0 3px 10px rgba(22, 70, 142, .06);
  transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.med-row:hover { transform: translateX(3px); box-shadow: 0 10px 22px rgba(22, 70, 142, .13); border-color: #94a3c4; }

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
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 3px 10px rgba(13, 45, 107, 0.25);
  animation: recentIconPulse 2.5s ease-in-out infinite;
}
@keyframes recentIconPulse {
  0%, 100% { box-shadow: 0 3px 10px rgba(13, 45, 107, 0.25); }
  50% { box-shadow: 0 3px 18px rgba(13, 45, 107, 0.40); }
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
