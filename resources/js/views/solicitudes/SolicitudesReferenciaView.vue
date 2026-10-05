<template>
  <div class="ph-solicitudes h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Stat cards ── -->
    <div class="sol-stats-bar shrink-0">
      <StatCard
        variant="pastel" tone="info" solid-icon
        label="Solicitudes totales" :value="displayStats[0]"
        :comparacion="deltaTotal"
        :icon="ClipboardListIcon" :sparkline="ondular(serieTotal, 1)"
      />
      <StatCard
        variant="pastel" tone="warning" solid-icon
        label="Pendientes de gestión" :value="displayStats[1]"
        :comparacion="deltaPendientes"
        :icon="ClockIcon" :sparkline="ondular(seriePendientes, 3)"
      />
      <StatCard
        variant="pastel" tone="violet" solid-icon
        label="En espera" :value="displayStats[2]"
        :comparacion="deltaEnEspera"
        :icon="HourglassIcon" :sparkline="ondular(serieEnEspera, 5)"
      />
      <StatCard
        variant="pastel" tone="success" solid-icon
        label="Completadas" :value="displayStats[3]"
        :comparacion="deltaCompletadas"
        :icon="CheckCircleIcon" :sparkline="ondular(serieCompletadas, 7)"
      />
    </div>

    <!-- ── Contenido: columna izquierda (chips + filtros + tabla) + panel lateral ── -->
    <div class="flex-1 flex gap-2 min-h-0 overflow-hidden">
    <div class="flex-1 min-w-0 flex flex-col gap-2 min-h-0">

      <!-- ── Chips de estado + toggle de filtros ── -->
      <div class="sol-filter-bar shrink-0">
        <div class="sol-tabs">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            class="sol-tab"
            :class="{ 'sol-tab-active': tabActiva === tab.value }"
            @click="cambiarTab(tab.value)"
          >
            <span v-if="tab.value !== 'todas'" class="sol-tab-dot" :class="'sol-tab-dot--' + tab.value"></span>
            {{ tab.label }}
            <span class="sol-tab-count">{{ tab.count }}</span>
          </button>
          <button class="sol-tab sol-tab-link" @click="irAHistorico('completado')">
            <span class="sol-tab-dot sol-tab-dot--completado"></span>
            Completadas
            <span class="sol-tab-count">{{ statsSol.completadas }}</span>
          </button>
          <button class="sol-tab sol-tab-link" @click="irAHistorico('negado')">
            <span class="sol-tab-dot sol-tab-dot--negado"></span>
            Negadas
            <span class="sol-tab-count">{{ statsSol.negadas }}</span>
          </button>
        </div>
        <div class="sol-header-spacer"></div>
        <div class="sol-chips-actions">
          <el-tooltip content="Actualizar" placement="top">
            <button class="sol-icon-btn" @click="cargar">
              <component :is="RefreshIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': cargando }" />
            </button>
          </el-tooltip>
          <el-tooltip content="Exportar CSV" placement="top">
            <button class="sol-icon-btn" :disabled="solicitudesFiltradas.length === 0" @click="exportarExcel">
              <component :is="DownloadIcon" class="w-3.5 h-3.5" />
            </button>
          </el-tooltip>
          <el-tooltip content="Verificar ingresos en Gomedisys" placement="top">
            <button class="sol-icon-btn sol-icon-btn-primary" :disabled="!solicitudesEnEspera.length || verificandoTodosIngresos" @click="verificarTodosIngresos">
              <component :is="SearchIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': verificandoTodosIngresos }" />
            </button>
          </el-tooltip>
        </div>
        <div ref="filtrosWrap" class="sol-filtros-wrap">
          <button class="sol-filtros-btn" :class="{ 'sol-filtros-btn-active': filtrosAbiertos || conteoFiltros > 0 }" @click="filtrosAbiertos = !filtrosAbiertos">
            <component :is="SlidersIcon" class="w-3.5 h-3.5" />
            Filtros
            <span v-if="conteoFiltros > 0" class="sol-filtros-badge">{{ conteoFiltros }}</span>
          </button>

          <!-- ── Panel de filtros ── -->
          <Transition name="bulk-slide">
            <div v-if="filtrosAbiertos" class="sol-filtros-panel">
              <DateRangeFilter v-model="rangoFechas" />
              <el-select v-model="filtroClinica" placeholder="Todas las clínicas" clearable filterable size="small" class="sol-select">
                <el-option v-for="c in clinicasFiltro" :key="c.id" :label="c.nombre" :value="c.id" />
              </el-select>
              <el-select v-model="filtroEspecialidad" placeholder="Todas las especialidades" clearable filterable size="small" class="sol-select">
                <el-option v-for="e in especialidadesFiltro" :key="e" :label="e" :value="e" />
              </el-select>
              <el-select
                :model-value="tabActiva"
                placeholder="Todos los estados"
                size="small"
                class="sol-select sol-select-estado"
                @update:model-value="(v: string) => v === 'completado' || v === 'negado' ? (irAHistorico(v), filtrosAbiertos = false) : cambiarTab(v as 'todas' | 'pendiente' | 'en_espera')"
              >
                <el-option label="Todos los estados" value="todas" />
                <el-option label="Pendientes" value="pendiente" />
                <el-option label="En espera" value="en_espera" />
                <el-option label="Completadas" value="completado" />
                <el-option label="Negadas" value="negado" />
              </el-select>
              <el-input
                v-model="filtro.buscar"
                placeholder="Buscar paciente, EPS, diagnóstico..."
                class="sol-search"
                clearable
                size="small"
              >
                <template #prefix>
                  <component :is="SearchIcon" class="w-3.5 h-3.5 text-gray-400" />
                </template>
              </el-input>
              <button v-if="hayFiltros" class="sol-limpiar" @click="limpiarFiltros">Limpiar filtros</button>
            </div>
          </Transition>
        </div>
      </div>

    <!-- ── Tabla ── -->
    <div class="flex-1 min-h-0 overflow-hidden sol-table-panel">
      <!-- Loading -->
      <div v-if="cargando" class="sol-table-loading">
        <div v-for="i in 6" :key="i" class="sol-table-row-skeleton">
          <div class="shimmer-box" style="width:18px; height:18px; border-radius:4px; flex-shrink:0;"></div>
          <div class="shimmer-box" style="width:32px; height:32px; border-radius:8px; flex-shrink:0;"></div>
          <div class="flex-1 space-y-1.5">
            <div class="shimmer-bar" style="width:35%; height:13px;"></div>
            <div class="shimmer-bar" style="width:22%; height:10px;"></div>
          </div>
          <div class="shimmer-bar" style="width:12%; height:11px;"></div>
          <div class="shimmer-bar" style="width:10%; height:11px;"></div>
          <div class="shimmer-box" style="width:65px; height:22px; border-radius:999px;"></div>
          <div class="shimmer-box" style="width:90px; height:26px; border-radius:6px; flex-shrink:0;"></div>
        </div>
      </div>

      <!-- Vacío -->
      <div v-else-if="solicitudesPaginadas.length === 0" class="sol-empty-wrap">
        <div class="sol-empty-icon w-14 h-14 rounded-2xl flex items-center justify-center mb-3">
          <component :is="FileTextIcon" class="w-7 h-7" />
        </div>
        <p class="font-bold text-sm mb-1" style="color:#0d2d5e;">Sin solicitudes</p>
        <p class="text-xs max-w-[260px]" style="color:#64748b;">No se encontraron solicitudes en esta categoría.</p>
      </div>

      <!-- Tabla real -->
      <div v-else class="flex flex-col h-full overflow-hidden">
        <div class="overflow-y-auto custom-scrollbar flex-1">
          <table class="sol-table">
            <thead class="sol-table-thead">
              <tr>
                <th class="sol-th sol-th-paciente sol-th-sortable" @click="toggleSort('paciente')">
                  Paciente
                  <component :is="sortIcon('paciente')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'paciente', 'opacity-30': sortKey !== 'paciente' }" />
                </th>
                <th class="sol-th sol-th-sortable" @click="toggleSort('clinica')">
                  Clínica emisora
                  <component :is="sortIcon('clinica')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'clinica', 'opacity-30': sortKey !== 'clinica' }" />
                </th>
                <th class="sol-th sol-th-sortable" @click="toggleSort('especialidad')">
                  Especialidad
                  <component :is="sortIcon('especialidad')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'especialidad', 'opacity-30': sortKey !== 'especialidad' }" />
                </th>
                <th class="sol-th">Diagnóstico</th>
                <th class="sol-th">Estado</th>
                <th class="sol-th sol-th-actions">Acciones</th>
              </tr>
            </thead>
            <TransitionGroup name="sol-row" tag="tbody">
              <tr
                v-for="s in solicitudesPaginadas"
                :id="`sol-row-${s.id}`"
                :key="s.id"
                class="sol-table-row"
                :class="{ 'sol-table-row-resaltada': resaltarId === s.id || idsActualizados.has(s.id) }"
              >
                <td class="sol-td">
                  <div class="sol-table-paciente">
                    <div class="sol-table-avatar" :style="avatarStyle(s)">
                      {{ inicialesPaciente(s) }}
                    </div>
                    <div class="sol-table-paciente-info">
                      <p class="sol-table-name" :class="{ 'sol-table-name-resaltada': resaltarId === s.id }">{{ nombreCompleto(s) }}</p>
                      <p class="sol-table-doc">{{ s.edad }} años · {{ s.genero }} · {{ s.eps }}</p>
                    </div>
                  </div>
                </td>
                <td class="sol-td">
                  <p class="sol-table-clinica-name">{{ s.clinica?.nombre ?? '—' }}</p>
                  <p class="sol-table-remitente" v-if="s.quien_remitente || s.telefono_contacto">
                    {{ s.quien_remitente || '—' }}<span v-if="s.telefono_contacto"> · {{ s.telefono_contacto }}</span>
                  </p>
                </td>
                <td class="sol-td">{{ s.especialidad_requerida }}</td>
                <td class="sol-td">
                  <p v-if="s.diagnosticos?.length" class="sol-dx-text">
                    {{ descripcionSinCodigo(s.diagnosticos[0]) }}<span v-if="s.diagnosticos.length > 1" class="sol-dx-more"> +{{ s.diagnosticos.length - 1 }}</span>
                  </p>
                  <p v-else-if="s.diagnostico" class="sol-dx-text">{{ s.diagnostico }}</p>
                  <span v-else class="sol-dx-none">—</span>
                </td>
                <td class="sol-td">
                  <span class="sol-table-status" :class="{
                    'sol-status-pending': s.estado === 'pendiente',
                    'sol-status-waiting': s.estado === 'en_espera',
                    'sol-status-completed': s.estado === 'completado',
                    'sol-status-rejected': s.estado === 'negado',
                  }">
                    <span class="sol-status-dot"></span>
                    {{ estadoLabel(s.estado) }}
                  </span>
                </td>
                <td class="sol-td">
                  <div class="sol-table-actions">
                    <el-tooltip :key="`ver-${s.id}`" content="Ver detalle" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <el-button circle size="small" @click="verDetalle(s)">
                        <component :is="EyeIcon" class="w-3.5 h-3.5" />
                      </el-button>
                    </el-tooltip>
                    <el-dropdown v-if="s.estado !== 'completado'" trigger="click" placement="bottom-end" :popper-options="{ modifiers: [{ name: 'offset', options: { offset: [8, 8] } }] }" popper-class="sol-acciones-menu" @command="(cmd: string) => manejarAccionFila(cmd, s)">
                      <el-button circle size="small" :loading="enviandoGomedisysId === s.id || consultandoIngresoId === s.id">
                        <component :is="MoreIcon" class="w-3.5 h-3.5" />
                      </el-button>
                      <template #dropdown>
                        <el-dropdown-menu>
                          <el-dropdown-item v-if="s.estado === 'pendiente'" command="aceptar" :icon="CheckIcon" class="sol-accion-success">Aceptar</el-dropdown-item>
                          <el-dropdown-item v-if="s.estado === 'pendiente'" command="negar" :icon="XIcon" class="sol-accion-danger" divided>Negar</el-dropdown-item>
                          <el-dropdown-item v-if="s.estado === 'en_espera'" command="ingreso" :icon="SearchIcon" class="sol-accion-teal">Consultar ingreso</el-dropdown-item>
                        </el-dropdown-menu>
                      </template>
                    </el-dropdown>
                  </div>
                </td>
              </tr>
            </TransitionGroup>
          </table>
        </div>
        <!-- Pagination -->
        <div class="sol-pagination">
          <span class="sol-pagination-info">
            Mostrando {{ (paginaActual - 1) * itemsPorPagina + 1 }}-{{ Math.min(paginaActual * itemsPorPagina, solicitudesFiltradas.length) }}
            de {{ solicitudesFiltradas.length }} solicitudes
          </span>
          <div class="sol-pagination-controls">
            <button class="sol-pagination-btn" :disabled="paginaActual === 1" @click="paginaActual--">
              <component :is="ChevronLeftIcon" class="w-3.5 h-3.5" />
            </button>
            <template v-for="(p, idx) in paginasVisibles" :key="idx">
              <button
                v-if="p !== '…'"
                class="sol-pagination-btn sol-page-num"
                :class="{ 'sol-page-active': p === paginaActual }"
                @click="paginaActual = p"
              >{{ p }}</button>
              <span v-else class="sol-page-ellipsis">…</span>
            </template>
            <button class="sol-pagination-btn" :disabled="paginaActual === totalPaginas" @click="paginaActual++">
              <component :is="ChevronRightIcon" class="w-3.5 h-3.5" />
            </button>
          </div>
          <el-select v-model="itemsPorPagina" size="small" class="sol-per-page">
            <el-option v-for="n in [10, 15, 25, 50]" :key="n" :label="`${n} por página`" :value="n" />
          </el-select>
        </div>
      </div>
    </div>
    </div>

    <!-- ── Panel lateral derecho: salud de la cola ── -->
    <aside class="sol-side">
      <!-- Gauge: salud de la cola -->
      <div class="sol-side-panel">
        <div class="sol-side-head">
          <component :is="GaugeIcon" class="w-3.5 h-3.5 sol-side-icon" />
          <p class="sol-side-title">Salud de la cola</p>
          <span class="sol-side-pill sol-side-pill-live"><span class="sol-live-dot"></span>En vivo</span>
        </div>
        <!-- Resumen + barra segmentada por antigüedad de la cola -->
        <div class="cola-resumen">
          <span class="cola-resumen-num">{{ solicitudes.length }}</span>
          <div class="cola-resumen-txt">
            <span class="cola-resumen-label">activas en cola</span>
            <span class="cola-resumen-badge" :class="saludColaPct >= 50 ? 'cola-resumen-badge--ok' : 'cola-resumen-badge--warn'">
              {{ saludColaPct }}% &lt;24h
            </span>
          </div>
        </div>
        <div class="cola-bar">
          <div
            v-for="seg in colaTramos" :key="seg.id"
            class="cola-seg" :class="{ 'cola-seg--on': edadCola === seg.id }"
            :style="{ width: seg.pct + '%', background: seg.grad }"
            :title="`${seg.label}: ${seg.total} solicitudes`"
            @click="verTramo(seg.id)"
          ></div>
        </div>
        <div class="cola-rows">
          <button
            v-for="seg in colaTramos" :key="seg.id"
            type="button" class="cola-row" :class="{ 'cola-row--on': edadCola === seg.id }"
            @click="verTramo(seg.id)"
          >
            <span class="cola-row-dot" :style="{ background: seg.color }"></span>
            <span class="cola-row-label">{{ seg.label }}</span>
            <span class="cola-row-track"><i :style="{ width: seg.pct + '%', background: seg.grad }"></i></span>
            <span class="cola-row-num">{{ seg.total }}</span>
          </button>
        </div>
        <div class="sol-gauge-stats">
          <div class="sol-gauge-stat sol-gauge-stat--link" title="Ver la solicitud más antigua" @click="verMasAntigua">
            <span class="sol-gauge-stat-num">{{ masAntiguaTxt }}</span>
            <span class="sol-gauge-stat-label">Más antigua</span>
          </div>
          <div class="sol-gauge-stat sol-gauge-stat--link" title="Ver solicitudes con más de 24h en cola" @click="verCriticas">
            <span class="sol-gauge-stat-num" :class="{ 'sol-gauge-stat-warn': colaCriticas > 0 }">{{ colaCriticas }}</span>
            <span class="sol-gauge-stat-label">&gt; 24h</span>
          </div>
          <div class="sol-gauge-stat sol-gauge-stat--link" title="Ver solicitudes pendientes sin gestionar" @click="verSinGestionar">
            <span class="sol-gauge-stat-num">{{ resumen.pendientes }}</span>
            <span class="sol-gauge-stat-label">Sin gestionar</span>
          </div>
        </div>
      </div>

      <!-- Especialidades en cola -->
      <div class="sol-side-panel sol-side-panel-flex">
        <div class="sol-side-head">
          <component :is="ActivityIcon" class="w-3.5 h-3.5 sol-side-icon" />
          <p class="sol-side-title">Especialidades en cola</p>
          <span class="sol-side-pill">Más requeridas</span>
        </div>
        <div class="sol-esp-list">
          <div v-for="(e, i) in topEspecialidadesCola" :key="e.nombre" class="sol-esp-row">
            <span class="sol-esp-rank" :class="`sol-esp-rank--${i + 1}`">{{ i + 1 }}</span>
            <div class="sol-esp-info">
              <p class="sol-esp-name">{{ e.nombre }}</p>
              <div class="sol-esp-bar">
                <div class="sol-esp-fill" :style="{ width: e.pct + '%', background: espColaColors[i % espColaColors.length] }"></div>
              </div>
            </div>
            <span class="sol-esp-total">{{ e.total }}</span>
          </div>
          <p v-if="!topEspecialidadesCola.length" class="sol-esp-empty">Sin solicitudes activas</p>
        </div>
      </div>
    </aside>
    </div>

    <!-- ── Modal: Detalle ── -->
    <el-dialog v-model="modalDetalle" width="900px" class="detalle-dialog" :show-close="false" align-center @close="limpiarThumbnails">
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <button type="button" class="detalle-close-btn" @click="cerrarDetalle">
            <component :is="XIcon" class="w-4 h-4" />
          </button>

          <!-- Header claro -->
          <div class="detalle-head">
            <div class="detalle-head-pattern"></div>
            <div class="detalle-head-icon">
              <component :is="ClipboardListIcon" class="w-6 h-6" />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Detalle de solicitud</p>
              <p class="detalle-head-sub">{{ idInterno(solicitudSeleccionada.id) }} · {{ formatFecha(solicitudSeleccionada.fecha) }} · {{ formatHora(solicitudSeleccionada.hora) }}</p>
            </div>
            <span class="detalle-head-badge" :class="'estado-' + solicitudSeleccionada.estado">
              {{ estadoLabel(solicitudSeleccionada.estado) }}
            </span>
            <button class="detalle-head-pdf" @click="exportarPdf">
              <component :is="FileDownIcon" class="w-3.5 h-3.5" />
              <span>Descargar PDF</span>
            </button>
          </div>

          <div class="detalle-body">
            <!-- Paciente + Remisión + Diagnósticos en 3 columnas -->
            <div class="grid grid-cols-3 gap-2 mb-1.5">
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-blue"><component :is="UserIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-blue">Paciente</p>
                </div>
                <div class="card-body">
                  <div class="data-row"><span>Nombre</span><strong>{{ nombreCompleto(solicitudSeleccionada) }}</strong></div>
                  <div class="data-row"><span>Documento</span><strong>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</strong></div>
                  <div class="data-row"><span>Edad / Género</span><strong>{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masc.' : 'Fem.' }}</strong></div>
                  <div class="data-row"><span>EPS</span><strong>{{ solicitudSeleccionada.eps }}</strong></div>
                  <div class="data-row"><span>Municipio</span><strong>{{ solicitudSeleccionada.municipio_capita }}</strong></div>
                  <div class="data-row"><span>Especialidad</span><strong>{{ solicitudSeleccionada.especialidad_requerida }}</strong></div>
                  <div class="data-row"><span>Servicio actual</span><strong>{{ solicitudSeleccionada.servicio_ubicacion_actual }}</strong></div>
                </div>
              </div>
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-amber"><component :is="BuildingIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-amber">Remisión</p>
                </div>
                <div class="card-body">
                  <div class="data-row"><span>Institución</span><strong>{{ solicitudSeleccionada.clinica?.nombre ?? '—' }}</strong></div>
                  <div v-if="solicitudSeleccionada.quien_remitente" class="data-row"><span>Remite</span><strong>{{ solicitudSeleccionada.quien_remitente }}</strong></div>
                  <div v-if="solicitudSeleccionada.telefono_contacto" class="data-row"><span>Teléfono</span><strong>{{ solicitudSeleccionada.telefono_contacto }}</strong></div>
                  <div v-if="solicitudSeleccionada.correo_contacto" class="data-row"><span>Correo</span><strong>{{ solicitudSeleccionada.correo_contacto }}</strong></div>
                  <div v-if="solicitudSeleccionada.codigo_aceptacion" class="data-row"><span>Código Gomedisys</span><strong class="font-mono">{{ solicitudSeleccionada.codigo_aceptacion }}</strong></div>
                  <div v-if="solicitudSeleccionada.hora_llegada" class="data-row"><span>Llegada esperada</span><strong>{{ formatHora(solicitudSeleccionada.hora_llegada) }} · {{ solicitudSeleccionada.lugar_llegada }}</strong></div>
                  <p v-if="!solicitudSeleccionada.quien_remitente && !solicitudSeleccionada.telefono_contacto && !solicitudSeleccionada.correo_contacto" class="text-xs text-gray-400 italic">Sin datos adicionales de remisión</p>
                </div>
              </div>
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-violet"><component :is="ClipboardListIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-violet">Diagnósticos</p>
                </div>
                <div class="card-body">
                  <div v-if="solicitudSeleccionada.diagnosticos?.length" class="card-dx-list">
                    <div v-for="dx in solicitudSeleccionada.diagnosticos" :key="dx.id" class="card-dx-item">
                      <strong class="card-dx-code">{{ dx.codigo_cie10 }}</strong>
                      <span class="card-dx-desc">{{ descripcionSinCodigo(dx) }}</span>
                    </div>
                  </div>
                  <p v-else-if="solicitudSeleccionada.diagnostico" class="card-text">{{ solicitudSeleccionada.diagnostico }}</p>
                  <p v-else class="card-text">—</p>
                </div>
              </div>
            </div>

            <!-- Historia clínica a ancho completo -->
            <div class="detalle-card mb-1.5">
              <div class="detalle-card-head">
                <span class="detalle-card-icon detalle-card-icon-blue"><component :is="ClipboardListIcon" class="w-3.5 h-3.5" /></span>
                <p class="detalle-card-title detalle-card-title-blue">Historia clínica</p>
              </div>
              <div class="card-body">
                <p class="card-text-sm card-text-clamp">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
                <button
                  v-if="(solicitudSeleccionada.resumen_historia_clinica?.length ?? 0) > 180"
                  class="leer-mas-btn"
                  @click="abrirHistoriaClinica"
                >Leer más</button>
              </div>
            </div>

            <!-- Seguimiento + Soportes en 2 columnas -->
            <div class="grid grid-cols-2 gap-2">
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-slate"><component :is="ClockIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-slate">Seguimiento</p>
                </div>
                <div class="card-body">
                  <div v-for="(paso, i) in pasosSeguimiento" :key="i" class="timeline-item">
                    <div class="timeline-connector">
                      <div class="timeline-dot" :class="{ 'timeline-dot-done': paso.hecho, 'timeline-dot-negada': paso.tipo === 'negada', 'timeline-dot-revertido': paso.tipo === 'revertido' }">
                        <component :is="ICONO_PASO[paso.tipo]" class="w-2.5 h-2.5" />
                      </div>
                      <div v-if="i < pasosSeguimiento.length - 1" class="timeline-line" :class="{ 'timeline-line-done': paso.hecho }"></div>
                    </div>
                    <div class="timeline-text">
                      <p class="timeline-titulo" :class="{ 'timeline-titulo-pending': !paso.hecho }">{{ paso.titulo }}</p>
                      <p class="timeline-fecha">{{ paso.fecha }}</p>
                    </div>
                  </div>
                  <p v-if="solicitudSeleccionada.observaciones_respuesta" class="timeline-obs">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
                  <p v-if="solicitudSeleccionada.motivo_negacion" class="timeline-obs timeline-obs-negada">{{ solicitudSeleccionada.motivo_negacion }}</p>
                </div>
              </div>
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-rose"><component :is="PaperclipIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-rose">Soportes</p>
                </div>
                <div class="card-body">
                  <div v-if="solicitudSeleccionada.adjuntos?.length" class="adjunto-list">
                    <button
                      v-for="adj in solicitudSeleccionada.adjuntos"
                      :key="adj.id"
                      type="button"
                      class="adjunto-row"
                      @click="abrirAdjunto(adj)"
                    >
                      <img v-if="thumbnails.has(adj.id)" :src="thumbnails.get(adj.id)" class="adjunto-thumb" :alt="adj.nombre_original" />
                      <span v-else class="adjunto-icon" :class="isPdf(adj) ? 'adjunto-pdf' : 'adjunto-img'">{{ isPdf(adj) ? 'PDF' : esImagenMime(adj.mime_type) ? 'IMG' : 'DOC' }}</span>
                      <div class="adjunto-info">
                        <p class="adjunto-name">{{ adj.nombre_original }}</p>
                        <p class="adjunto-meta">{{ formatFileSize(adj.tamano) }}</p>
                      </div>
                      <component :is="isPreviewable(adj) ? EyeIcon : DownloadIcon" class="w-3.5 h-3.5 adjunto-action-icon" />
                    </button>
                  </div>
                  <p v-else class="adjunto-empty">Sin archivos adjuntos</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="detalle-footer">
            <p class="detalle-footer-note">
              <component :is="ShieldIcon" class="w-3.5 h-3.5" />
              La información está protegida y será tratada confidencialmente.
            </p>
            <button type="button" class="detalle-cerrar-btn" @click="cerrarDetalle">
              <component :is="SendIcon" class="w-3.5 h-3.5" />
              Cerrar
            </button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- ── Modal: Visor de adjunto ── -->
    <el-dialog v-model="modalPdf" width="720px" :class="['pdf-dialog', { 'pdf-fullscreen': pantallaCompleta }]" align-center :show-close="true">
      <template #header>
        <div class="pdf-dialog-header">
          <component :is="isImageAdjunto ? ImageIcon : FileTextIcon" class="w-4 h-4 flex-shrink-0" />
          <p class="pdf-dialog-title">{{ adjuntoActivo?.nombre_original ?? 'Documento' }}</p>
          <a
            v-if="adjuntoActivo"
            :href="pdfBlobUrl || adjuntoUrl(adjuntoActivo)"
            :download="adjuntoActivo.nombre_original"
            class="pdf-dialog-download"
          >
            <component :is="DownloadIcon" class="w-3.5 h-3.5" />
            Descargar
          </a>
        </div>
      </template>
      <div
        class="pdf-viewer-wrap"
        :class="{ 'pdf-viewer-fullscreen': pantallaCompleta }"
        @wheel.prevent="onWheel"
      >
        <!-- Zoom toolbar -->
        <div v-if="!cargandoPdf && adjuntoActivo && isPreviewable(adjuntoActivo)" class="pdf-zoom-toolbar">
          <button class="pdf-zoom-btn" @click="zoomOut" :disabled="zoomLevel <= 0.25" title="Alejar">
            <component :is="ZoomOutIcon" class="w-4 h-4" />
          </button>
          <span class="pdf-zoom-label">{{ Math.round(zoomLevel * 100) }}%</span>
          <button class="pdf-zoom-btn" @click="zoomIn" :disabled="zoomLevel >= 4" title="Acercar">
            <component :is="ZoomInIcon" class="w-4 h-4" />
          </button>
          <button class="pdf-zoom-btn" @click="zoomReset" title="Restablecer">
            <component :is="MaximizeIcon" class="w-4 h-4" />
          </button>
          <button class="pdf-zoom-btn" @click="togglePantallaCompleta" :title="pantallaCompleta ? 'Salir pantalla completa' : 'Pantalla completa'">
            <component :is="pantallaCompleta ? MinimizeIcon : ExpandIcon" class="w-4 h-4" />
          </button>
        </div>
        <div v-if="cargandoPdf" class="pdf-viewer-loading">
          <component :is="RefreshIcon" class="w-6 h-6 animate-spin text-blue-500" />
          <p>Cargando documento...</p>
        </div>
        <iframe
          v-else-if="adjuntoActivo && pdfBlobUrl && isPdf(adjuntoActivo)"
          :src="pdfZoomUrl"
          class="pdf-viewer-iframe"
          frameborder="0"
        ></iframe>
        <div v-else-if="adjuntoActivo && pdfBlobUrl && isImage(adjuntoActivo)" class="pdf-viewer-image-wrap">
          <img :src="pdfBlobUrl" :alt="adjuntoActivo.nombre_original" class="pdf-viewer-image" :style="{ transform: `scale(${zoomLevel})` }" />
        </div>
        <div v-else-if="adjuntoActivo && !isPreviewable(adjuntoActivo)" class="pdf-viewer-fallback">
          <component :is="FileTextIcon" class="w-10 h-10 text-gray-300" />
          <p>Este archivo no se puede previsualizar</p>
          <a :href="adjuntoUrl(adjuntoActivo)" download class="pdf-viewer-download-btn">
            <component :is="DownloadIcon" class="w-4 h-4" />
            Descargar archivo
          </a>
        </div>
      </div>
    </el-dialog>

    <el-dialog v-model="modalHistoriaClinica" width="620px" class="historia-dialog" append-to-body align-center>
      <template #header>
        <div class="historia-dialog-header">
          <component :is="FileTextIcon" class="w-5 h-5" />
          <div>
            <p>Historia clínica</p>
            <span>{{ solicitudSeleccionada ? nombreCompleto(solicitudSeleccionada) : '' }}</span>
          </div>
        </div>
      </template>
      <p class="historia-dialog-text">{{ solicitudSeleccionada?.resumen_historia_clinica }}</p>
    </el-dialog>

    <!-- ── Modal: Aceptar ── -->
    <el-dialog v-model="modalAceptar" width="520px" class="accion-dialog" :show-close="false" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <button type="button" class="detalle-close-btn" @click="modalAceptar = false">
            <component :is="XIcon" class="w-4 h-4" />
          </button>
          <div class="detalle-head">
            <div class="detalle-head-pattern"></div>
            <div class="detalle-head-icon accion-icon-success">
              <component :is="CheckIcon" class="w-6 h-6" />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Aceptar solicitud</p>
              <p class="detalle-head-sub">{{ idInterno(solicitudSeleccionada.id) }} · el paciente quedará en espera de llegada</p>
            </div>
          </div>

          <div class="detalle-body">
            <div class="accion-patient-card">
              <div class="accion-patient-avatar">{{ inicialesPaciente(solicitudSeleccionada) }}</div>
              <div class="accion-patient-info">
                <strong>{{ nombreCompleto(solicitudSeleccionada) }}</strong>
                <span>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }} · {{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.especialidad_requerida }}</span>
              </div>
            </div>

            <div class="detalle-card">
              <div class="card-body accion-form-body">
                <div class="grid grid-cols-2 gap-2 mb-2">
                  <div>
                    <label class="accion-field-label">Fecha</label>
                    <el-input :model-value="formAceptar.fecha_respuesta" disabled />
                  </div>
                  <div>
                    <label class="accion-field-label">Hora</label>
                    <el-input :model-value="formAceptar.hora_respuesta" disabled />
                  </div>
                </div>
                <div class="mb-2">
                  <label class="accion-field-label">Quien responde</label>
                  <el-input :model-value="formAceptar.nombre_quien_responde" disabled />
                </div>
                <div class="accion-gomedisys-callout mb-2">
                  <div class="accion-gomedisys-callout-text">
                    <component :is="SendIcon" class="w-3.5 h-3.5 shrink-0" />
                    <span>¿Aún no está registrado en Gomedisys? Envíalo primero para obtener el código REF.</span>
                  </div>
                  <button
                    type="button"
                    class="accion-gomedisys-btn"
                    :class="{ 'accion-gomedisys-btn-enviando': enviandoGomedisysId === solicitudSeleccionada.id }"
                    @click="abrirGomedisys(solicitudSeleccionada)"
                  >
                    <component :is="enviandoGomedisysId === solicitudSeleccionada.id ? Loader2Icon : SendIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': enviandoGomedisysId === solicitudSeleccionada.id }" />
                    Enviar a Gomedisys
                  </button>
                </div>
                <div class="mb-2">
                  <label class="accion-field-label">Código Gomedisys (REF) <span class="accion-required">*</span></label>
                  <el-input v-model="formAceptar.codigo_aceptacion" placeholder="Ej: REF00045821" />
                  <p class="accion-field-hint">El código real que generó Gomedisys al registrar al paciente allá — no se inventa acá.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 mb-2">
                  <div>
                    <label class="accion-field-label">Hora de llegada <span class="accion-required">*</span></label>
                    <el-time-select v-model="formAceptar.hora_llegada" placeholder="Seleccione" start="00:00" step="00:15" end="23:45" class="w-full" />
                  </div>
                  <div>
                    <label class="accion-field-label">Lugar <span class="accion-required">*</span></label>
                    <el-input v-model="formAceptar.lugar_llegada" placeholder="Ej: Urgencias" />
                  </div>
                </div>
                <div>
                  <label class="accion-field-label">Observaciones</label>
                  <el-input v-model="formAceptar.observaciones_respuesta" type="textarea" :rows="3" placeholder="Opcional" />
                </div>
              </div>
            </div>
          </div>

          <div class="detalle-footer">
            <button type="button" class="accion-cancelar-btn" @click="modalAceptar = false">Cancelar</button>
            <button type="button" class="accion-confirmar-btn accion-confirmar-success" :disabled="procesando" @click="aceptar">
              <component :is="procesando ? Loader2Icon : CheckIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': procesando }" />
              Confirmar aceptación
            </button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- ── Modal: Negar ── -->
    <el-dialog v-model="modalNegar" width="520px" class="accion-dialog" :show-close="false" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <button type="button" class="detalle-close-btn" @click="modalNegar = false">
            <component :is="XIcon" class="w-4 h-4" />
          </button>
          <div class="detalle-head">
            <div class="detalle-head-pattern"></div>
            <div class="detalle-head-icon accion-icon-danger">
              <component :is="XIcon" class="w-6 h-6" />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Negar solicitud</p>
              <p class="detalle-head-sub">ID #{{ solicitudSeleccionada.id }} · indica el motivo de la negación</p>
            </div>
          </div>

          <div class="detalle-body">
            <div class="accion-patient-card">
              <div class="accion-patient-avatar">{{ inicialesPaciente(solicitudSeleccionada) }}</div>
              <div class="accion-patient-info">
                <strong>{{ nombreCompleto(solicitudSeleccionada) }}</strong>
                <span>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }} · {{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.especialidad_requerida }}</span>
              </div>
            </div>

            <div class="detalle-card">
              <div class="card-body accion-form-body">
                <div class="grid grid-cols-2 gap-2 mb-2">
                  <div>
                    <label class="accion-field-label">Fecha</label>
                    <el-input :model-value="formNegar.fecha_respuesta" disabled />
                  </div>
                  <div>
                    <label class="accion-field-label">Hora</label>
                    <el-input :model-value="formNegar.hora_respuesta" disabled />
                  </div>
                </div>
                <div class="mb-2">
                  <label class="accion-field-label">Quien responde</label>
                  <el-input :model-value="formNegar.nombre_quien_responde" disabled />
                </div>
                <div class="mb-2">
                  <label class="accion-field-label">Motivo de negación <span class="accion-required">*</span></label>
                  <el-input v-model="formNegar.motivo_negacion" type="textarea" :rows="3" placeholder="Indique el motivo..." />
                </div>
                <div>
                  <label class="accion-field-label">Observaciones adicionales</label>
                  <el-input v-model="formNegar.observaciones_respuesta" type="textarea" :rows="2" placeholder="Opcional" />
                </div>
              </div>
            </div>
          </div>

          <div class="detalle-footer">
            <button type="button" class="accion-cancelar-btn" @click="modalNegar = false">Cancelar</button>
            <button type="button" class="accion-confirmar-btn accion-confirmar-danger" :disabled="procesando" @click="negar">
              <component :is="procesando ? Loader2Icon : XIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': procesando }" />
              Confirmar negación
            </button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- ── Modal: Resultado de enviar a Gomedisys ── -->
    <el-dialog v-model="modalResultadoGomedisys" width="480px" class="accion-dialog" :show-close="false" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <button type="button" class="detalle-close-btn" @click="modalResultadoGomedisys = false">
            <component :is="XIcon" class="w-4 h-4" />
          </button>
          <div class="detalle-head">
            <div class="detalle-head-pattern"></div>
            <div class="detalle-head-icon" :class="resultadoGomedisysOk ? 'accion-icon-success' : 'accion-icon-danger'">
              <component :is="resultadoGomedisysOk ? CheckCircleIcon : XCircleIcon" class="w-6 h-6" />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">{{ resultadoGomedisysOk ? 'Enviado a Gomedisys' : 'No se pudo enviar' }}</p>
              <p class="detalle-head-sub">ID #{{ solicitudSeleccionada.id }} · {{ resultadoGomedisysOk ? 'diligenciado, sin guardar' : 'revisa el detalle abajo' }}</p>
            </div>
          </div>

          <div class="detalle-body">
            <div class="accion-patient-card">
              <div class="accion-patient-avatar">{{ inicialesPaciente(solicitudSeleccionada) }}</div>
              <div class="accion-patient-info">
                <strong>{{ nombreCompleto(solicitudSeleccionada) }}</strong>
                <span>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }} · {{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.especialidad_requerida }}</span>
              </div>
            </div>

            <p class="resultado-gomedisys-mensaje">{{ resultadoGomedisysMensaje }}</p>
          </div>

          <div class="detalle-footer">
            <button
              type="button"
              class="accion-confirmar-btn"
              :class="resultadoGomedisysOk ? 'accion-confirmar-success' : 'accion-confirmar-danger'"
              @click="modalResultadoGomedisys = false"
            >
              <component :is="resultadoGomedisysOk ? CheckIcon : XIcon" class="w-3.5 h-3.5" />
              Entendido
            </button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- ── Modal: Confirmar ingreso encontrado en Gomedisys ── -->
    <el-dialog v-model="modalConfirmarIngreso" width="560px" class="accion-dialog" :show-close="false" align-center>
      <template v-if="solicitudSeleccionada && datosIngresoGomedisys">
        <div class="detalle-content">
          <button type="button" class="detalle-close-btn" @click="modalConfirmarIngreso = false">
            <component :is="XIcon" class="w-4 h-4" />
          </button>
          <div class="detalle-head">
            <div class="detalle-head-pattern"></div>
            <div class="detalle-head-icon accion-icon-teal">
              <component :is="SearchIcon" class="w-6 h-6" />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Confirmar ingreso</p>
              <p class="detalle-head-sub">ID #{{ solicitudSeleccionada.id }} · verifica que sea el paciente correcto antes de completar</p>
            </div>
          </div>

          <div class="detalle-body">
            <div class="grid grid-cols-2 gap-2">
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-blue"><component :is="UserIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-blue">Datos de la solicitud</p>
                </div>
                <div class="card-body">
                  <div class="data-row"><span>Nombre</span><strong>{{ nombreCompleto(solicitudSeleccionada) }}</strong></div>
                  <div class="data-row"><span>Documento</span><strong>{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</strong></div>
                  <div class="data-row"><span>Edad</span><strong>{{ solicitudSeleccionada.edad }} años</strong></div>
                  <div class="data-row"><span>Género</span><strong>{{ solicitudSeleccionada.genero === 'M' ? 'Masc.' : 'Fem.' }}</strong></div>
                </div>
              </div>
              <div class="detalle-card">
                <div class="detalle-card-head">
                  <span class="detalle-card-icon detalle-card-icon-violet"><component :is="SearchIcon" class="w-3.5 h-3.5" /></span>
                  <p class="detalle-card-title detalle-card-title-violet">Encontrado en Gomedisys</p>
                </div>
                <div class="card-body">
                  <div class="data-row"><span>Nombre</span><strong>{{ datosIngresoGomedisys.fullname }}</strong></div>
                  <div class="data-row"><span>Documento</span><strong>{{ datosIngresoGomedisys.document_type }} {{ datosIngresoGomedisys.identification_number }}</strong></div>
                  <div class="data-row"><span>Nacimiento</span><strong>{{ datosIngresoGomedisys.birthdate || '—' }}</strong></div>
                  <div class="data-row"><span>Sexo</span><strong>{{ datosIngresoGomedisys.sex || '—' }}</strong></div>
                  <div class="data-row"><span>N.° de ingreso</span><strong>{{ datosIngresoGomedisys.admission_number }}</strong></div>
                  <div class="data-row"><span>Fecha de ingreso</span><strong>{{ datosIngresoGomedisys.admission_date || '—' }}</strong></div>
                </div>
              </div>
            </div>
          </div>

          <div class="detalle-footer">
            <button type="button" class="accion-cancelar-btn" @click="modalConfirmarIngreso = false">Cancelar</button>
            <button type="button" class="accion-confirmar-btn accion-confirmar-violet" :disabled="procesandoIngreso" @click="confirmarIngresoPaciente">
              <component :is="procesandoIngreso ? Loader2Icon : CheckIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': procesandoIngreso }" />
              Sí, es el paciente — completar
            </button>
          </div>
        </div>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { usePolling } from '@/lib/usePolling';
import { actualizarSiCambio } from '@/lib/silentRefresh';
import { useStorage, useDebounceFn, onClickOutside } from '@vueuse/core';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
import { exportarSolicitudPdf } from '@/lib/exportarSolicitudPdf';
import {
  Search as SearchIcon,
  RefreshCw as RefreshIcon,
  Eye as EyeIcon,
  Check as CheckIcon,
  X as XIcon,
  FileText as FileTextIcon,
  Clock as ClockIcon,
  CheckCircle as CheckCircleIcon,
  XCircle as XCircleIcon,
  ClipboardList as ClipboardListIcon,
  Paperclip as PaperclipIcon,
  Download as DownloadIcon,
  Image as ImageIcon,
  ZoomIn as ZoomInIcon,
  ZoomOut as ZoomOutIcon,
  Maximize as MaximizeIcon,
  Expand as ExpandIcon,
  Minimize as MinimizeIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
  ArrowUp as ArrowUpIcon,
  ArrowDown as ArrowDownIcon,
  ArrowUpDown as ArrowUpDownIcon,
  Send as SendIcon,
  Shield as ShieldIcon,
  User as UserIcon,
  Building2 as BuildingIcon,
  Hourglass as HourglassIcon,
  Loader2 as Loader2Icon,
  FileDown as FileDownIcon,
  RotateCcw as RotateCcwIcon,
  MoreVertical as MoreIcon,
  SlidersHorizontal as SlidersIcon,
  Gauge as GaugeIcon,
  Activity as ActivityIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import StatCard from '@/components/ui/StatCard.vue';
import DateRangeFilter from '@/components/ui/DateRangeFilter.vue';
import { autocompletarEnGomedisys } from '@/lib/gomedisys';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

interface Clinica {
  id: number;
  nombre: string;
}

interface Solicitud {
  id: number;
  clinica_id: number;
  clinica?: Clinica;
  fecha: string;
  hora: string;
  primer_nombre: string;
  segundo_nombre?: string;
  primer_apellido: string;
  segundo_apellido?: string;
  genero: 'M' | 'F';
  edad: number;
  tipo_documento: string;
  numero_documento: string;
  eps: string;
  diagnostico: string;
  diagnosticos?: { id: number; codigo_cie10: string; descripcion: string }[];
  municipio_capita: string;
  direccion_paciente?: string;
  telefono_paciente?: string;
  especialidad_requerida: string;
  servicio_ubicacion_actual: string;
  servicio_remision?: string;
  quien_remitente?: string;
  telefono_contacto?: string;
  correo_contacto?: string;
  resumen_historia_clinica: string;
  via_contacto?: string;
  gestante?: boolean;
  condicion_especial?: string;
  observaciones?: string;
  estado: 'pendiente' | 'en_espera' | 'completado' | 'negado';
  codigo_aceptacion?: string;
  hora_respuesta?: string;
  hora_llegada?: string;
  lugar_llegada?: string;
  motivo_negacion?: string;
  numero_ingreso?: number;
  nombre_quien_responde?: string;
  observaciones_respuesta?: string;
  adjuntos?: { id: number; nombre_original: string; mime_type: string; tamano: number }[];
  eventos?: { id: number; tipo: string; titulo: string; created_at: string }[];
  created_at: string;
}

const solicitudes = ref<Solicitud[]>([]);
const cargando = ref(false);
const procesando = ref(false);
const filtro = useStorage('sol-filtro', { buscar: '' });
const buscarDebounced = ref(filtro.value.buscar);
const updateBuscarDebounced = useDebounceFn((val: string) => { buscarDebounced.value = val; }, 300);
watch(() => filtro.value.buscar, (val) => updateBuscarDebounced(val));
const tabActiva = useStorage<'todas' | 'pendiente' | 'en_espera'>('sol-tab', 'todas');
// 'completado'/'negado' ya no viven aquí — si quedó guardado un tab así de
// antes de mover esos estados al histórico, se reinicia para no mostrar una
// tabla vacía sin explicación.
if (tabActiva.value !== 'todas' && tabActiva.value !== 'pendiente' && tabActiva.value !== 'en_espera') {
  tabActiva.value = 'todas';
}

const sortKey = ref<'paciente' | 'clinica' | 'especialidad' | 'fecha'>('fecha');
// Más reciente arriba por defecto, igual que en Histórico (a propósito,
// pidió explícitamente invertir el orden que se dejó ascendente antes).
const sortDir = ref<'asc' | 'desc'>('desc');
const resaltarId = ref<number | null>(null);
const idsActualizados = ref<Set<number>>(new Set());
const paginaActual = ref(1);
const itemsPorPagina = ref(15);
watch(itemsPorPagina, () => { paginaActual.value = 1; });

// Filtros del botón "Filtros" (todos se aplican en cliente sobre la lista ya cargada).
const filtrosAbiertos = ref(false);
const filtrosWrap = ref<HTMLElement | null>(null);
onClickOutside(filtrosWrap, () => { filtrosAbiertos.value = false; });
const filtroClinica = ref<number | ''>('');
const filtroEspecialidad = ref('');
const rangoFechas = ref<[string, string] | null>(null);

// Datos del panel lateral (dona, barras por día, top clínicas) y de los chips
// de completadas/negadas — vienen del endpoint de stats del dashboard.
interface DashStats {
  solicitudes: { total: number; pendientes: number; en_espera: number; completadas: number; negadas: number };
  filtros: { especialidades: string[]; eps: string[]; clinicas: { id: number; nombre: string }[] };
  tendencia: { label: string; total: number }[];
  tendencia_anterior: { fecha: string; total: number }[];
  tendencia_pendientes: { fecha: string; total: number }[];
  tendencia_pendientes_anterior: { fecha: string; total: number }[];
  tendencia_en_espera: { fecha: string; total: number }[];
  tendencia_en_espera_anterior: { fecha: string; total: number }[];
  tendencia_completadas: { fecha: string; total: number }[];
  tendencia_completadas_anterior: { fecha: string; total: number }[];
  top_clinicas: { nombre: string; total: number; pct: number; serie: number[] }[];
}
const dash = ref<DashStats | null>(null);

const modalDetalle = ref(false);
const modalHistoriaClinica = ref(false);
const modalAceptar = ref(false);
const modalNegar = ref(false);
const modalPdf = ref(false);
const solicitudSeleccionada = ref<Solicitud | null>(null);
const enviandoGomedisysId = ref<number | null>(null);
const consultandoIngresoId = ref<number | null>(null);
const modalResultadoGomedisys = ref(false);
const resultadoGomedisysOk = ref(false);
const resultadoGomedisysMensaje = ref('');
const adjuntoActivo = ref<{ id: number; nombre_original: string; mime_type: string; tamano: number } | null>(null);

const formAceptar = ref({ fecha_respuesta: '', hora_respuesta: '', nombre_quien_responde: '', codigo_aceptacion: '', hora_llegada: '', lugar_llegada: '', observaciones_respuesta: '' });
const formNegar = ref({ fecha_respuesta: '', hora_respuesta: '', motivo_negacion: '', nombre_quien_responde: '', observaciones_respuesta: '' });



interface PacienteGomedisys {
  fullname: string;
  identification_number: string;
  document_type: string;
  sex: string;
  birthdate: string;
  admission_number: number;
  admission_date: string | null;
}
const modalConfirmarIngreso = ref(false);
const datosIngresoGomedisys = ref<PacienteGomedisys | null>(null);
const procesandoIngreso = ref(false);

const resumen = computed(() => ({
  total: solicitudes.value.length,
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  enEspera: solicitudes.value.filter(s => s.estado === 'en_espera').length,
}));

const solicitudesEnEspera = computed(() => solicitudes.value.filter(s => s.estado === 'en_espera'));

const tabs = computed<{ label: string; value: 'todas' | 'pendiente' | 'en_espera'; count: number }[]>(() => [
  { label: 'Todas', value: 'todas', count: resumen.value.total },
  { label: 'Pendientes', value: 'pendiente', count: resumen.value.pendientes },
  { label: 'En espera', value: 'en_espera', count: resumen.value.enEspera },
]);

/* ── Datos del panel lateral y stat cards (endpoint de stats del dashboard) ── */
const statsSol = computed(() => dash.value?.solicitudes ?? { total: 0, pendientes: 0, en_espera: 0, completadas: 0, negadas: 0 });
const clinicasFiltro = computed(() => dash.value?.filtros?.clinicas ?? []);
const especialidadesFiltro = computed(() => dash.value?.filtros?.especialidades ?? []);

const serieTotal = computed(() => (dash.value?.tendencia ?? []).map(d => d.total));
const seriePendientes = computed(() => (dash.value?.tendencia_pendientes ?? []).map(d => d.total));
const serieEnEspera = computed(() => (dash.value?.tendencia_en_espera ?? []).map(d => d.total));
const serieCompletadas = computed(() => (dash.value?.tendencia_completadas ?? []).map(d => d.total));

/** Densifica la serie real (interpola entre días y suma una ondulación
 * determinística) para que el sparkline tenga picos aunque los datos
 * sean planos. Solo decorativa — los deltas usan las series reales. */
function ondular(serie: number[], seed: number): number[] {
  if (!serie.length) return serie;
  const n = 24;
  const max = Math.max(...serie, 1);
  return Array.from({ length: n }, (_, i) => {
    const pos = (i / (n - 1)) * (serie.length - 1);
    const j = Math.min(Math.floor(pos), serie.length - 1);
    const f = pos - j;
    const base = (serie[j] ?? 0) + (((serie[j + 1] ?? serie[j]) ?? 0) - (serie[j] ?? 0)) * f;
    const onda = max * 0.16 * (0.5 + 0.5 * Math.sin(i * 1.7 + seed))
      + max * 0.07 * Math.sin(i * 3.9 + seed * 1.3);
    return Math.max(0, base * 0.7 + onda);
  });
}

function deltaTexto(actual: number[], anterior: number[]): string {
  const a = actual.reduce((x, y) => x + y, 0);
  const b = anterior.reduce((x, y) => x + y, 0);
  if (!b) return a > 0 ? '↑ Nuevo este período' : '= Sin cambio';
  const d = Math.round(((a - b) / b) * 100);
  if (d > 0) return `↑${d}% vs período anterior`;
  if (d < 0) return `↓${Math.abs(d)}% vs período anterior`;
  return '= 0% vs período anterior';
}

const deltaTotal = computed(() => deltaTexto(serieTotal.value, (dash.value?.tendencia_anterior ?? []).map(d => d.total)));
const deltaPendientes = computed(() => deltaTexto(seriePendientes.value, (dash.value?.tendencia_pendientes_anterior ?? []).map(d => d.total)));
const deltaEnEspera = computed(() => deltaTexto(serieEnEspera.value, (dash.value?.tendencia_en_espera_anterior ?? []).map(d => d.total)));
const deltaCompletadas = computed(() => deltaTexto(serieCompletadas.value, (dash.value?.tendencia_completadas_anterior ?? []).map(d => d.total)));

/* ── Salud de la cola: gauge + antigüedad + especialidades ── */
function horasDesde(fechaIso: string): number {
  return Math.abs(Date.now() - new Date(fechaIso).getTime()) / 3_600_000;
}

const saludColaPct = computed(() => {
  if (!solicitudes.value.length) return 100;
  const ok = solicitudes.value.filter(s => horasDesde(s.created_at) < 24).length;
  return Math.round((ok / solicitudes.value.length) * 100);
});

/* Antigüedad de la cola: 4 tramos de espera, de fresco a crítico.
   Cada tramo es clicable y filtra la tabla por ese rango de edad. */
const COLA_TRAMOS = [
  { id: 'fresca', label: '< 24h', color: '#22c55e', grad: 'linear-gradient(180deg,#4ade80,#16a34a)', test: (h: number) => h < 24 },
  { id: 'd1_3', label: '1–3 días', color: '#f59e0b', grad: 'linear-gradient(180deg,#fbbf24,#d97706)', test: (h: number) => h >= 24 && h < 72 },
  { id: 'd3_7', label: '3–7 días', color: '#f97316', grad: 'linear-gradient(180deg,#fb923c,#ea580c)', test: (h: number) => h >= 72 && h < 168 },
  { id: 'd7', label: '> 7 días', color: '#ef4444', grad: 'linear-gradient(180deg,#f87171,#dc2626)', test: (h: number) => h >= 168 },
];

const edadCola = ref<string | null>(null);

const colaTramos = computed(() => {
  const total = solicitudes.value.length || 1;
  return COLA_TRAMOS.map(t => {
    const n = solicitudes.value.filter(s => t.test(horasDesde(s.created_at))).length;
    return { ...t, total: n, pct: Math.round((n / total) * 100) };
  });
});

function verTramo(id: string) {
  edadCola.value = edadCola.value === id ? null : id;
  paginaActual.value = 1;
}

const masAntiguaTxt = computed(() => {
  if (!solicitudes.value.length) return '—';
  const h = Math.max(...solicitudes.value.map(s => horasDesde(s.created_at)));
  if (h < 1) return `${Math.max(1, Math.round(h * 60))}m`;
  if (h < 24) return `${Math.floor(h)}h`;
  return `${Math.floor(h / 24)}d`;
});

const colaCriticas = computed(() => solicitudes.value.filter(s => horasDesde(s.created_at) >= 24).length);

/* Accesos directos desde el gauge de salud de la cola. */
function verSinGestionar() {
  cambiarTab('pendiente');
}

/** Las más viejas primero — las que ya pasaron las 24h. */
function verCriticas() {
  cambiarTab('todas');
  sortKey.value = 'fecha';
  sortDir.value = 'asc';
}

/** Abre directamente el detalle de la solicitud más antigua de la cola. */
function verMasAntigua() {
  if (!solicitudes.value.length) return;
  const s = solicitudes.value.reduce((a, b) => (horasDesde(a.created_at) > horasDesde(b.created_at) ? a : b));
  verDetalle(s);
}

const espColaColors = ['#3b82f6', '#22c55e', '#f59e0b', '#a855f7', '#14b8a6'];
const topEspecialidadesCola = computed(() => {
  const mapa = new Map<string, number>();
  solicitudes.value.forEach(s => {
    const e = s.especialidad_requerida?.trim();
    if (e) mapa.set(e, (mapa.get(e) ?? 0) + 1);
  });
  const tot = solicitudes.value.length || 1;
  return [...mapa.entries()]
    .sort((a, b) => b[1] - a[1])
    .slice(0, 6)
    .map(([nombre, total]) => ({ nombre, total, pct: Math.round((total / tot) * 100) }));
});

const hayFiltros = computed(() =>
  !!filtro.value.buscar || tabActiva.value !== 'todas'
  || filtroClinica.value !== '' || !!filtroEspecialidad.value || !!rangoFechas.value
  || !!edadCola.value
);
/** Cuántos filtros del panel están activos (el tab de estado no cuenta — ya se ve en los chips). */
const conteoFiltros = computed(() =>
  [filtroClinica.value !== '', !!filtroEspecialidad.value, !!rangoFechas.value, !!filtro.value.buscar]
    .filter(Boolean).length
);

function irAHistorico(estado: 'completado' | 'negado') {
  router.push({ path: '/historico', query: { estado } });
}

/* ── Avatar de paciente con color determinístico por nombre ── */
const AVATAR_TONES = [
  { bg: '#dbeafe', fg: '#2563eb' }, { bg: '#dcfce7', fg: '#16a34a' },
  { bg: '#fef3c7', fg: '#d97706' }, { bg: '#ede9fe', fg: '#7c3aed' },
  { bg: '#fce7f3', fg: '#db2777' }, { bg: '#cffafe', fg: '#0891b2' },
  { bg: '#fee2e2', fg: '#dc2626' }, { bg: '#e0e7ff', fg: '#4f46e5' },
];
function avatarStyle(s: Solicitud) {
  const nombre = nombreCompleto(s);
  let h = 0;
  for (const ch of nombre) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
  const t = AVATAR_TONES[h % AVATAR_TONES.length];
  return { background: t.bg, color: t.fg };
}

async function cargarStats() {
  try {
    const { data } = await http.get('/api/dashboard/stats');
    dash.value = data.data;
  } catch {
    // El panel lateral es complementario — si falla no interrumpe la tabla.
  }
}

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

const solicitudesFiltradas = computed(() => {
  let result = solicitudes.value.filter(s => {
    if (tabActiva.value !== 'todas' && s.estado !== tabActiva.value) return false;
    if (filtroClinica.value !== '' && s.clinica_id !== filtroClinica.value) return false;
    if (filtroEspecialidad.value && s.especialidad_requerida !== filtroEspecialidad.value) return false;
    if (rangoFechas.value) {
      const dia = s.created_at?.slice(0, 10);
      if (dia && (dia < rangoFechas.value[0] || dia > rangoFechas.value[1])) return false;
    }
    if (edadCola.value) {
      const tramo = COLA_TRAMOS.find(t => t.id === edadCola.value);
      if (tramo && !tramo.test(horasDesde(s.created_at))) return false;
    }
    const texto = buscarDebounced.value.toLowerCase();
    const coincideTexto = !texto ||
      nombreCompleto(s).toLowerCase().includes(texto) ||
      s.eps.toLowerCase().includes(texto) ||
      s.especialidad_requerida.toLowerCase().includes(texto) ||
      s.clinica?.nombre.toLowerCase().includes(texto) ||
      s.numero_documento.toLowerCase().includes(texto);
    return coincideTexto;
  });
  const dir = sortDir.value === 'asc' ? 1 : -1;
  result = [...result].sort((a, b) => {
    let va = '', vb = '';
    if (sortKey.value === 'paciente') { va = nombreCompleto(a).toLowerCase(); vb = nombreCompleto(b).toLowerCase(); }
    else if (sortKey.value === 'clinica') { va = (a.clinica?.nombre ?? '').toLowerCase(); vb = (b.clinica?.nombre ?? '').toLowerCase(); }
    else if (sortKey.value === 'especialidad') { va = (a.especialidad_requerida ?? '').toLowerCase(); vb = (b.especialidad_requerida ?? '').toLowerCase(); }
    else { va = a.created_at; vb = b.created_at; }
    return va < vb ? -dir : va > vb ? dir : 0;
  });
  return result;
});

const totalPaginas = computed(() => Math.max(1, Math.ceil(solicitudesFiltradas.value.length / itemsPorPagina.value)));
const solicitudesPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * itemsPorPagina.value;
  return solicitudesFiltradas.value.slice(start, start + itemsPorPagina.value);
});

/** Páginas numeradas con elipsis: 1 2 3 4 5 … 16 */
const paginasVisibles = computed<(number | '…')[]>(() => {
  const total = totalPaginas.value;
  const cur = paginaActual.value;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  if (cur <= 3) return [1, 2, 3, 4, 5, '…', total];
  if (cur >= total - 2) return [1, '…', total - 4, total - 3, total - 2, total - 1, total];
  return [1, '…', cur - 1, cur, cur + 1, '…', total];
});

function toggleSort(key: 'paciente' | 'clinica' | 'especialidad') {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDir.value = 'asc';
  }
}

function sortIcon(key: string) {
  if (sortKey.value !== key) return ArrowUpDownIcon;
  return sortDir.value === 'asc' ? ArrowUpIcon : ArrowDownIcon;
}

function cambiarTab(tab: 'todas' | 'pendiente' | 'en_espera') {
  tabActiva.value = tab;
  paginaActual.value = 1;
}

function exportarExcel() {
  const rows = solicitudesFiltradas.value;
  const headers = ['ID', 'Paciente', 'Documento', 'EPS', 'Clínica', 'Especialidad', 'Diagnóstico', 'Estado', 'Fecha', 'Hora', 'Remitente', 'Teléfono'];
  const csv = [
    headers.join('\t'),
    ...rows.map(s => [
      s.id, nombreCompleto(s), `${s.tipo_documento} ${s.numero_documento}`, s.eps,
      s.clinica?.nombre ?? '', s.especialidad_requerida,
      s.diagnosticos?.length ? s.diagnosticos.map(d => d.codigo_cie10).join('; ') : (s.diagnostico ?? ''),
      estadoLabel(s.estado), formatFecha(s.fecha), s.hora,
      s.quien_remitente ?? '', s.telefono_contacto ?? '',
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join('\t')),
  ].join('\n');
  const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `solicitudes_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
  notify.success(`Exportadas ${rows.length} solicitudes`);
}

function inicialesPaciente(s: Solicitud): string {
  const parts = [s.primer_nombre, s.primer_apellido].filter(Boolean);
  if (parts.length === 0) return '?';
  return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2);
}

function nombreCompleto(s: Solicitud) {
  return [s.primer_nombre, s.segundo_nombre, s.primer_apellido, s.segundo_apellido]
    .filter(Boolean).join(' ');
}

// El código ya puede venir incluido al inicio de dx.descripcion (diagnósticos
// elegidos del catálogo en Nueva Solicitud) — se le quita antes de mostrarlo
// junto al badge del código, para no repetirlo dos veces.
function descripcionSinCodigo(dx: { codigo_cie10: string; descripcion: string }) {
  if (dx.codigo_cie10 && dx.descripcion.startsWith(dx.codigo_cie10)) {
    return dx.descripcion.slice(dx.codigo_cie10.length).replace(/^\s*—\s*/, '');
  }
  return dx.descripcion;
}

function estadoLabel(estado: string) {
  return { pendiente: 'Pendiente', en_espera: 'En espera', completado: 'Completada', negado: 'Negada' }[estado] ?? estado;
}

/** ID interno de la app (distinto del código REF que emite Gomedisys). */
function idInterno(id: number): string {
  return 'ID' + String(id).padStart(6, '0');
}

function formatFecha(fecha: string) {
  if (!fecha) return '—';
  const d = new Date(fecha.includes('T') ? fecha : fecha + 'T00:00:00');
  if (isNaN(d.getTime())) return fecha;
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatHora(hora?: string): string {
  if (!hora) return '';
  const [h, m] = hora.split(':').map(Number);
  if (Number.isNaN(h) || Number.isNaN(m)) return hora;
  const ampm = h >= 12 ? 'p. m.' : 'a. m.';
  const h12 = h % 12 === 0 ? 12 : h % 12;
  return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
}

function horaDeFecha(fecha: string): string {
  return new Date(fecha).toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit', hour12: true });
}

interface PasoSeguimiento {
  titulo: string;
  fecha: string;
  hecho: boolean;
  tipo: 'enviada' | 'revision' | 'aceptada' | 'negada' | 'espera' | 'completado' | 'revertido';
}

/** Línea de tiempo real de la solicitud: siempre hay un envío; el resto depende del estado y de los eventos registrados. */
const pasosSeguimiento = computed<PasoSeguimiento[]>(() => {
  const s = solicitudSeleccionada.value;
  if (!s) return [];

  const pasos: PasoSeguimiento[] = [{
    titulo: 'Solicitud enviada',
    fecha: `${formatFecha(s.fecha)} · ${formatHora(s.hora)}`,
    hecho: true,
    tipo: 'enviada',
  }];

  if (s.estado === 'pendiente') {
    // Si ya se había aceptado/negado antes y alguien lo deshizo, esa
    // decisión anterior no debe desaparecer de la vista — se muestra la
    // última reversión antes de "En revisión".
    const eventoRevertido = [...(s.eventos ?? [])].reverse().find(e => e.tipo === 'revertido');
    if (eventoRevertido) {
      pasos.push({
        titulo: 'Devuelta a pendiente',
        fecha: `${formatFecha(eventoRevertido.created_at)} · ${horaDeFecha(eventoRevertido.created_at)}`,
        hecho: true,
        tipo: 'revertido',
      });
    }
    pasos.push({ titulo: 'En revisión', fecha: '—', hecho: false, tipo: 'revision' });
    return pasos;
  }

  const respuestaFecha = s.hora_respuesta
    ? `${formatHora(s.hora_respuesta)}${s.nombre_quien_responde ? ' · ' + s.nombre_quien_responde : ''}`
    : '—';

  if (s.estado === 'negado') {
    pasos.push({ titulo: 'Solicitud negada', fecha: respuestaFecha, hecho: true, tipo: 'negada' });
    return pasos;
  }

  pasos.push({ titulo: 'Solicitud aceptada', fecha: respuestaFecha, hecho: true, tipo: 'aceptada' });

  const eventoEspera = s.eventos?.find(e => e.tipo === 'en_espera');
  if (eventoEspera || s.estado === 'en_espera' || s.estado === 'completado') {
    pasos.push({
      titulo: 'Paciente en espera',
      fecha: eventoEspera ? `${formatFecha(eventoEspera.created_at)} · ${horaDeFecha(eventoEspera.created_at)}` : '—',
      hecho: !!eventoEspera,
      tipo: 'espera',
    });
  }

  const eventoCompletado = s.eventos?.find(e => e.tipo === 'completado');
  if (eventoCompletado || s.estado === 'completado') {
    pasos.push({
      titulo: 'Paciente atendido',
      fecha: eventoCompletado ? `${formatFecha(eventoCompletado.created_at)} · ${horaDeFecha(eventoCompletado.created_at)}` : '—',
      hecho: !!eventoCompletado,
      tipo: 'completado',
    });
  }

  return pasos;
});

const ICONO_PASO: Record<PasoSeguimiento['tipo'], any> = {
  enviada: SendIcon,
  revision: HourglassIcon,
  aceptada: CheckCircleIcon,
  negada: XCircleIcon,
  espera: ClockIcon,
  completado: CheckCircleIcon,
  revertido: RotateCcwIcon,
};

async function exportarPdf() {
  const s = solicitudSeleccionada.value;
  if (!s) return;
  try {
    await exportarSolicitudPdf(s);
  } catch {
    notify.error('No se pudo generar el PDF. Intente de nuevo.');
  }
}

function formatFileSize(bytes: number): string {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  let size = bytes;
  while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
  return size.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
}

function isPdf(adj: { mime_type?: string; nombre_original?: string }): boolean {
  const isMimePdf = adj.mime_type === 'application/pdf';
  const isExtPdf = (adj.nombre_original ?? '').toLowerCase().endsWith('.pdf');
  return isMimePdf || isExtPdf;
}

function isImage(adj: { mime_type?: string; nombre_original?: string }): boolean {
  const isMimeImage = (adj.mime_type ?? '').startsWith('image/');
  const isExtImage = /\.(jpe?g|png|gif|webp|bmp|svg)$/i.test(adj.nombre_original ?? '');
  return isMimeImage || isExtImage;
}

function isPreviewable(adj: { mime_type?: string; nombre_original?: string }): boolean {
  return isPdf(adj) || isImage(adj);
}

const isImageAdjunto = computed(() => adjuntoActivo.value ? isImage(adjuntoActivo.value) : false);

function adjuntoUrl(adj: { id: number }): string {
  return `/api/solicitudes-referencia/${solicitudSeleccionada.value?.id}/adjuntos/${adj.id}/descargar`;
}

const pdfBlobUrl = ref<string | null>(null);
const cargandoPdf = ref(false);
const zoomLevel = ref(1);
const pantallaCompleta = ref(false);

const pdfZoomUrl = computed(() => {
  if (!pdfBlobUrl.value) return undefined;
  return `${pdfBlobUrl.value}#zoom=${Math.round(zoomLevel.value * 100)}`;
});

function zoomIn() {
  zoomLevel.value = Math.min(parseFloat((zoomLevel.value + 0.25).toFixed(2)), 4);
}
function zoomOut() {
  zoomLevel.value = Math.max(parseFloat((zoomLevel.value - 0.25).toFixed(2)), 0.25);
}
function zoomReset() {
  zoomLevel.value = 1;
}
function onWheel(e: WheelEvent) {
  if (e.deltaY < 0) zoomIn();
  else zoomOut();
}
function togglePantallaCompleta() {
  pantallaCompleta.value = !pantallaCompleta.value;
}

async function abrirAdjunto(adj: { id: number; nombre_original: string; mime_type: string; tamano: number }) {
  adjuntoActivo.value = adj;
  modalPdf.value = true;
  zoomReset();
  pantallaCompleta.value = false;
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value);
    pdfBlobUrl.value = null;
  }
  if (!isPreviewable(adj)) return;
  cargandoPdf.value = true;
  try {
    const response = await http.get(adjuntoUrl(adj), { responseType: 'blob' });
    pdfBlobUrl.value = URL.createObjectURL(response.data);
  } catch {
    notify.error('Error al cargar el archivo');
    adjuntoActivo.value = null;
  } finally {
    cargandoPdf.value = false;
  }
}

function limpiarFiltros() {
  filtro.value.buscar = '';
  tabActiva.value = 'todas';
  filtroClinica.value = '';
  filtroEspecialidad.value = '';
  rangoFechas.value = null;
  edadCola.value = null;
}

/** Resalta y hace scroll hasta la solicitud referenciada por la notificación de la campana (?resaltar=ID). */
async function aplicarResaltado() {
  const id = Number(route.query.resaltar);
  if (!id) return;

  resaltarId.value = id;
  tabActiva.value = 'todas';
  filtro.value.buscar = '';
  buscarDebounced.value = '';
  paginaActual.value = 1;

  await nextTick();
  document.getElementById(`sol-row-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });

  const { resaltar: _resaltar, ...resto } = route.query;
  router.replace({ query: resto });
}

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/solicitudes-referencia');
    solicitudes.value = data.data;
    animateCounters([resumen.value.total, resumen.value.pendientes, resumen.value.enEspera, statsSol.value.completadas]);
  } catch {
    notify.error('Error al cargar las solicitudes');
  } finally {
    cargando.value = false;
  }
}

/** Refresco automático de fondo: no muestra el esqueleto de carga ni toca
 * nada si el servidor devuelve exactamente lo mismo. Si algo cambió, resalta
 * solo esas filas en vez de refrescar toda la tabla. */
async function cargarSilencioso() {
  try {
    const statsAntes = JSON.stringify(resumen.value);
    const { data } = await http.get('/api/solicitudes-referencia');
    const cambiados = actualizarSiCambio(solicitudes, data.data);
    if (cambiados.length === 0) return;

    idsActualizados.value = new Set(cambiados);
    setTimeout(() => { idsActualizados.value = new Set(); }, 3000);

    if (JSON.stringify(resumen.value) !== statsAntes) {
      animateCounters([resumen.value.total, resumen.value.pendientes, resumen.value.enEspera, statsSol.value.completadas]);
    }
  } catch {
    // Refresco de fondo: si falla, se reintenta en el siguiente ciclo sin interrumpir al usuario.
  }
}

const thumbnails = ref<Map<number, string>>(new Map());

function esImagenMime(mime?: string): boolean {
  return !!mime && mime.startsWith('image/');
}

function limpiarThumbnails() {
  thumbnails.value.forEach(url => URL.revokeObjectURL(url));
  thumbnails.value = new Map();
}

async function cargarThumbnails(s: Solicitud) {
  const imagenes = (s.adjuntos ?? []).filter(a => esImagenMime(a.mime_type));
  for (const adj of imagenes) {
    try {
      const { data } = await http.get(adjuntoUrl(adj), { responseType: 'blob' });
      thumbnails.value.set(adj.id, URL.createObjectURL(data));
      thumbnails.value = new Map(thumbnails.value);
    } catch {
      // Si falla la miniatura, se muestra solo la insignia de tipo — no es crítico.
    }
  }
}

function verDetalle(s: Solicitud) {
  limpiarThumbnails();
  solicitudSeleccionada.value = s;
  adjuntoActivo.value = null;
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value);
    pdfBlobUrl.value = null;
  }
  modalHistoriaClinica.value = false;
  modalDetalle.value = true;
  cargarThumbnails(s);
}

function cerrarDetalle() {
  modalDetalle.value = false;
  limpiarThumbnails();
}

function abrirHistoriaClinica() {
  modalHistoriaClinica.value = true;
}

function fechaActual(): string {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

function horaActual(): string {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(Math.floor(now.getMinutes() / 5) * 5).padStart(2, '0');
  return `${h}:${m}`;
}

function manejarAccionFila(comando: string, s: Solicitud) {
  const acciones: Record<string, (s: Solicitud) => void> = {
    aceptar: abrirAceptar,
    ingreso: consultarIngreso,
    negar: abrirNegar,
  };
  acciones[comando]?.(s);
}

async function consultarIngreso(s: Solicitud) {
  if (consultandoIngresoId.value !== null) return;
  consultandoIngresoId.value = s.id;
  try {
    const { data } = await http.post(`/api/solicitudes-referencia/${s.id}/consultar-ingreso`);
    if (data.ingreso) {
      solicitudSeleccionada.value = s;
      datosIngresoGomedisys.value = data.paciente;
      modalConfirmarIngreso.value = true;
    } else {
      notify.info(data.message);
    }
  } catch (e: any) {
    notify.error(e.response?.data?.message || 'No se pudo consultar el ingreso en Gomedisys');
  } finally {
    consultandoIngresoId.value = null;
  }
}

/** Confirma que el paciente encontrado en Gomedisys es el correcto y completa la solicitud con ese número de ingreso. */
async function confirmarIngresoPaciente() {
  const s = solicitudSeleccionada.value;
  const datos = datosIngresoGomedisys.value;
  if (!s || !datos) return;
  try {
    procesandoIngreso.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${s.id}/completado`, {
      numero_ingreso: datos.admission_number,
    });
    notify.success('Solicitud completada');
    const idx = solicitudes.value.findIndex(x => x.id === s.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
    modalConfirmarIngreso.value = false;
    datosIngresoGomedisys.value = null;
  } catch (e: any) {
    notify.error(e.response?.data?.message || 'Error al completar la solicitud');
  } finally {
    procesandoIngreso.value = false;
  }
}

const verificandoTodosIngresos = ref(false);

/** Revisa el ingreso en Gomedisys de todas las solicitudes en espera de una vez, en vez de una por una. */
async function verificarTodosIngresos() {
  if (verificandoTodosIngresos.value) return;
  const lote = solicitudesEnEspera.value;
  if (lote.length === 0) return;

  verificandoTodosIngresos.value = true;
  try {
    const resultados = await Promise.allSettled(
      lote.map(s => http.post(`/api/solicitudes-referencia/${s.id}/consultar-ingreso`)),
    );
    const ingresaron = resultados.filter(r => r.status === 'fulfilled' && r.value.data.ingreso).length;
    const fallidas = resultados.filter(r => r.status === 'rejected').length;

    if (ingresaron > 0) {
      notify.success(`${ingresaron} paciente(s) ingresaron y se marcaron como completadas`);
    } else {
      notify.info('Ningún paciente en espera tiene ingreso registrado todavía en Gomedisys');
    }
    if (fallidas > 0) {
      notify.error(`${fallidas} consulta(s) fallaron y no se pudieron verificar`);
    }
    await cargar();
  } finally {
    verificandoTodosIngresos.value = false;
  }
}

function abrirAceptar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formAceptar.value = {
    fecha_respuesta: fechaActual(),
    hora_respuesta: horaActual(),
    nombre_quien_responde: authStore.user?.full_name ?? '',
    codigo_aceptacion: '',
    hora_llegada: '',
    lugar_llegada: '',
    observaciones_respuesta: '',
  };
  modalAceptar.value = true;
}

async function abrirGomedisys(s: Solicitud) {
  // Seguro extra además del :loading del botón — si por algo llega un
  // segundo clic mientras el primero sigue en curso (doble clic rápido,
  // otra fila), se ignora en vez de mandar un segundo intento que choque
  // con el primero en la misma pestaña de Gomedisys.
  if (enviandoGomedisysId.value !== null) return;
  enviandoGomedisysId.value = s.id;
  solicitudSeleccionada.value = s;
  try {
    const resultado = await autocompletarEnGomedisys(s);
    resultadoGomedisysOk.value = resultado.ok;
    resultadoGomedisysMensaje.value = resultado.ok
      ? 'El formulario quedó diligenciado en Gomedisys, sin guardar. Revisa esa pestaña, completa lo que falte y decide tú si guardar.'
      : (resultado.error || 'No se pudo diligenciar el formulario en Gomedisys.');
    modalResultadoGomedisys.value = true;
  } finally {
    enviandoGomedisysId.value = null;
  }
}

function abrirNegar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formNegar.value = {
    fecha_respuesta: fechaActual(),
    hora_respuesta: horaActual(),
    motivo_negacion: '',
    nombre_quien_responde: authStore.user?.full_name ?? '',
    observaciones_respuesta: '',
  };
  modalNegar.value = true;
}

async function aceptar() {
  if (!formAceptar.value.hora_respuesta || !formAceptar.value.nombre_quien_responde.trim()
    || !formAceptar.value.codigo_aceptacion.trim() || !formAceptar.value.hora_llegada || !formAceptar.value.lugar_llegada.trim()) {
    notify.warning('Complete los campos obligatorios');
    return;
  }
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${solicitudSeleccionada.value!.id}/aceptar`, formAceptar.value);
    notify.success('Solicitud aceptada: el paciente quedó en espera de llegada');
    const idx = solicitudes.value.findIndex(s => s.id === solicitudSeleccionada.value!.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
    modalAceptar.value = false;
  } catch (e: any) {
    notify.error(e.response?.data?.message || 'Error al aceptar la solicitud');
  } finally {
    procesando.value = false;
  }
}

async function negar() {
  if (!formNegar.value.hora_respuesta || !formNegar.value.motivo_negacion.trim() || !formNegar.value.nombre_quien_responde.trim()) {
    notify.warning('Complete los campos obligatorios');
    return;
  }
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${solicitudSeleccionada.value!.id}/negar`, formNegar.value);
    notify.success('Solicitud negada');
    const idx = solicitudes.value.findIndex(s => s.id === solicitudSeleccionada.value!.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
    modalNegar.value = false;
  } catch (e: any) {
    notify.error(e.response?.data?.message || 'Error al negar la solicitud');
  } finally {
    procesando.value = false;
  }
}


onMounted(async () => {
  await Promise.all([cargar(), cargarStats()]);
  await aplicarResaltado();
});

// Re-aplica el resaltado cada vez que llega un nuevo ?resaltar=ID por la
// campana de notificaciones, aunque ya estemos en esta misma ruta (un
// cambio de query no vuelve a montar el componente, así que onMounted solo
// no alcanza para notificaciones repetidas).
watch(() => route.query.resaltar, (nuevo) => {
  if (nuevo) aplicarResaltado();
});

usePolling(() => {
  if (!cargando.value && !modalDetalle.value && !modalAceptar.value && !modalNegar.value && !modalPdf.value && !modalHistoriaClinica.value && !modalResultadoGomedisys.value && !modalConfirmarIngreso.value) cargarSilencioso();
}, 30000);
</script>

<style scoped>
.ph-solicitudes {
  background: linear-gradient(160deg, #eef4fc 0%, #e3edf8 40%, #f0f5fa 100%);
}

/* ── Stat cards ── */
.sol-stats-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .75rem;
}
@media (max-width: 900px) {
  .sol-stats-bar { grid-template-columns: repeat(2, 1fr); }
}

/* ── Tab dots ── */
.sol-tab-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  display: inline-block;
  margin-right: .3rem;
}
.sol-tab-dot--pendiente { background: #f59e0b; }
.sol-tab-dot--en_espera { background: #3b82f6; }
.sol-tab-dot--completado { background: #22c55e; }
.sol-tab-dot--negado { background: #ef4444; }

/* ── Transición del panel de filtros ── */
.bulk-slide-enter-active, .bulk-slide-leave-active {
  transition: all .25s ease;
}
.bulk-slide-enter-from, .bulk-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Sortable headers ── */
.sol-th-sortable {
  cursor: pointer;
  user-select: none;
  transition: color .15s ease;
}
.sol-th-sortable:hover { color: #16468E; }

/* ── Fila resaltada (llegó desde una notificación) ── */
.sol-table-row-resaltada {
  animation: sol-row-glow 2.2s ease-in-out 2;
  box-shadow: inset 3px 0 0 #D97706;
}
@keyframes sol-row-glow {
  0%, 100% { background: transparent; }
  50% { background: rgba(217, 119, 6, .12); }
}
.sol-table-name-resaltada {
  text-decoration: underline;
  text-decoration-color: #D97706;
  text-decoration-thickness: 2px;
  text-underline-offset: 3px;
}

/* ── Row transitions ── */
.sol-row-enter-active, .sol-row-leave-active {
  transition: all .3s ease;
}
.sol-row-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}
.sol-row-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* ── Pagination ── */
.sol-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .5rem .8rem;
  border-top: 1px solid #f1f5f9;
  background: #fafbfc;
}
.sol-pagination-info {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}
.sol-pagination-controls {
  display: flex;
  align-items: center;
  gap: .4rem;
}
.sol-pagination-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px; height: 28px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: all .15s ease;
}
.sol-pagination-btn:hover:not(:disabled) {
  border-color: #16468E;
  color: #16468E;
  background: #f0f5ff;
}
.sol-pagination-btn:disabled {
  opacity: .4;
  cursor: not-allowed;
}
.sol-pagination-page {
  font-size: 12px;
  font-weight: 600;
  color: #334e70;
  min-width: 60px;
  text-align: center;
}
.sol-page-num { font-size: 11px; font-weight: 700; font-family: inherit; }
.sol-page-active {
  background: linear-gradient(135deg, #0D2D6B, #16468E) !important;
  border-color: #16468E !important;
  color: #fff !important;
  box-shadow: 0 3px 8px rgba(13,45,107,.3);
}
.sol-page-ellipsis { font-size: 11px; color: #94a3b8; padding: 0 2px; }
.sol-per-page { width: 110px; }
.sol-per-page :deep(.el-select__wrapper) { min-height: 26px; font-size: 11px; }

/* ── Panel lateral derecho (todo cabe sin scroll: top clínicas toma el resto) ── */
.sol-side {
  width: 300px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  overflow: hidden;
  min-height: 0;
  padding-bottom: 2px;
}
@media (max-width: 1050px) { .sol-side { display: none; } }
.sol-side-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(22,70,142,.08);
  padding: 9px 12px;
  flex-shrink: 0;
}
.sol-side-panel-flex {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
.sol-side-panel-flex .sol-esp-list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  scrollbar-width: thin;
}
.dark .sol-side-panel { background: #161b28; border-color: rgba(255,255,255,0.07); }
.sol-side-head {
  display: flex; align-items: center; gap: 6px;
  margin: -2px -2px 8px;
  padding: 7px 10px;
  border-radius: 9px;
  background: #16468E;
  border: 1px solid #123a78;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.12), 0 3px 12px rgba(13,45,107,.22);
}
.sol-side-icon { color: rgba(255,255,255,.9); flex-shrink: 0; }
.sol-side-title {
  font-size: 11.5px; font-weight: 800; color: #fff;
  margin: 0; flex: 1; min-width: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  letter-spacing: .02em; text-shadow: 0 1px 2px rgba(0,0,0,.25);
}
.sol-side-pill {
  display: inline-flex; align-items: center; gap: 3px;
  font-size: 9px; font-weight: 700; color: rgba(255,255,255,.8);
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.16);
  padding: 2px 7px; border-radius: 999px; white-space: nowrap;
}

/* ── Gauge salud de la cola ── */
.sol-side-pill-live { color: #86efac; background: rgba(34,197,94,.15); border-color: rgba(74,222,128,.25); }
.sol-live-dot {
  width: 5px; height: 5px; border-radius: 50%;
  background: #22c55e;
  animation: solLivePulse 1.6s ease-in-out infinite;
}
@keyframes solLivePulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34,197,94,.5); }
  50% { opacity: .7; box-shadow: 0 0 0 3px rgba(34,197,94,0); }
}
/* ── Salud de la cola: resumen + barra segmentada por antigüedad ── */
.cola-resumen {
  display: flex; align-items: center; gap: 9px;
  margin-bottom: 9px;
}
.cola-resumen-num {
  font-size: 26px; font-weight: 800; color: #1e293b; line-height: 1;
  background: linear-gradient(135deg, #16468e, #2563eb);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.dark .cola-resumen-num { background: linear-gradient(135deg, #93c5fd, #60a5fa); -webkit-background-clip: text; background-clip: text; }
.cola-resumen-txt { display: flex; flex-direction: column; gap: 3px; }
.cola-resumen-label { font-size: 8.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
.cola-resumen-badge {
  align-self: flex-start;
  font-size: 8.5px; font-weight: 800; padding: 2px 7px; border-radius: 999px;
}
.cola-resumen-badge--ok { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.cola-resumen-badge--warn { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.dark .cola-resumen-badge--ok { background: rgba(34,197,94,.14); color: #4ade80; border-color: rgba(34,197,94,.3); }
.dark .cola-resumen-badge--warn { background: rgba(245,158,11,.14); color: #fbbf24; border-color: rgba(245,158,11,.3); }

/* Barra segmentada verde→roja según cuánto lleva cada solicitud esperando */
.cola-bar {
  display: flex; gap: 3px;
  height: 12px; width: 100%;
  margin-bottom: 10px;
}
.cola-seg {
  height: 100%; min-width: 3px;
  border-radius: 4px;
  cursor: pointer;
  transition: width .6s cubic-bezier(.22,1,.36,1), transform .15s ease, filter .15s ease, opacity .2s ease;
}
.cola-seg:hover { transform: scaleY(1.25); filter: brightness(1.05); }
.cola-seg--on { outline: 2px solid rgba(22,70,142,.35); outline-offset: 1px; }
.dark .cola-seg--on { outline-color: rgba(147,197,253,.5); }

/* Filas-leyenda clicables por tramo */
.cola-rows { display: flex; flex-direction: column; gap: 5px; margin-bottom: 8px; }
.cola-row {
  display: flex; align-items: center; gap: 7px;
  width: 100%;
  background: transparent; border: 1px solid transparent; border-radius: 7px;
  padding: 3px 5px; cursor: pointer;
  transition: background .15s ease, border-color .15s ease;
}
.cola-row:hover { background: #f1f5f9; }
.dark .cola-row:hover { background: rgba(255,255,255,0.05); }
.cola-row--on { background: #eef4ff; border-color: #bfdbfe; }
.dark .cola-row--on { background: rgba(59,130,246,0.12); border-color: rgba(96,165,250,0.35); }
.cola-row-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.cola-row-label { font-size: 9.5px; font-weight: 700; color: #475569; min-width: 44px; text-align: left; }
.dark .cola-row-label { color: #cbd5e1; }
.cola-row-track {
  flex: 1; height: 5px; border-radius: 3px;
  background: #eef2f7; overflow: hidden;
}
.dark .cola-row-track { background: rgba(255,255,255,0.08); }
.cola-row-track i {
  display: block; height: 100%; border-radius: 3px;
  transition: width .6s cubic-bezier(.22,1,.36,1);
}
.cola-row-num { font-size: 10.5px; font-weight: 800; color: #1e293b; min-width: 22px; text-align: right; }
.dark .cola-row-num { color: #e2e8f0; }

.sol-gauge-stats {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 6px; margin-top: 4px;
}
.sol-gauge-stat {
  text-align: center; padding: 5px 2px;
  background: #f8fafc; border: 1px solid #eef2f7; border-radius: 8px;
}
.dark .sol-gauge-stat { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.07); }
.sol-gauge-stat--link { cursor: pointer; transition: background .15s ease, border-color .15s ease, transform .15s ease; }
.sol-gauge-stat--link:hover { background: #eef4ff; border-color: #bfdbfe; transform: translateY(-1px); }
.dark .sol-gauge-stat--link:hover { background: rgba(59,130,246,0.12); border-color: rgba(96,165,250,0.35); }
.sol-gauge-stat-num { display: block; font-size: 12px; font-weight: 800; color: #1e293b; line-height: 1; }
.dark .sol-gauge-stat-num { color: #e2e8f0; }
.sol-gauge-stat-warn { color: #dc2626; }
.dark .sol-gauge-stat-warn { color: #f87171; }
.sol-gauge-stat-label { font-size: 8px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; }

/* ── Especialidades en cola ── */
.sol-esp-list { display: flex; flex-direction: column; gap: 12px; justify-content: space-evenly; }
.sol-esp-row { display: flex; align-items: center; gap: 9px; }
.sol-esp-rank {
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 9.5px; font-weight: 800;
  background: #f1f5f9; color: #64748b; flex-shrink: 0;
}
.sol-esp-rank--1 { background: #fef3c7; color: #b45309; }
.sol-esp-rank--2 { background: #e2e8f0; color: #475569; }
.sol-esp-rank--3 { background: #ffedd5; color: #c2410c; }
.sol-esp-info { flex: 1; min-width: 0; }
.sol-esp-name {
  font-size: 11px; font-weight: 700; color: #334155;
  margin: 0 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dark .sol-esp-name { color: #cbd5e1; }
.sol-esp-bar {
  height: 7px; border-radius: 999px; background: #f1f5f9; overflow: hidden;
}
.dark .sol-esp-bar { background: rgba(255,255,255,0.07); }
.sol-esp-fill {
  height: 100%; border-radius: 999px;
  transition: width .6s cubic-bezier(.22,1,.36,1);
  box-shadow: 0 1px 4px rgba(0,0,0,.15);
}
.sol-esp-total {
  flex-shrink: 0; min-width: 24px; text-align: right;
  font-size: 12px; font-weight: 800; color: #1e293b;
}
.dark .sol-esp-total { color: #e2e8f0; }
.sol-esp-empty { font-size: 10px; color: #94a3b8; text-align: center; margin: 12px 0; }

/* ── Modal: Visor de adjunto ── */
:deep(.pdf-dialog) { border-radius: 16px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.35); transition: width .3s ease, max-width .3s ease; }
:deep(.pdf-dialog.pdf-fullscreen) { width: 100% !important; max-width: 100vw !important; margin: 0 !important; height: 100vh; border-radius: 0; }
:deep(.pdf-dialog.pdf-fullscreen .el-dialog__body) { height: calc(100vh - 52px); }
:deep(.pdf-dialog .el-dialog__header) { margin: 0; padding: .75rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
:deep(.pdf-dialog .el-dialog__body) {
  padding: 0;
}
:deep(.pdf-dialog .el-dialog__headerbtn) { top: 10px; right: 12px; width: 28px; height: 28px; }
.pdf-dialog-header {
  display: flex;
  align-items: center;
  gap: 8px;
}
.pdf-dialog-header > svg {
  color: #64748b;
}
.pdf-dialog-title {
  flex: 1;
  min-width: 0;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pdf-dialog-download {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
  background: #dbeafe;
  padding: 6px 12px;
  border-radius: 8px;
  text-decoration: none;
  transition: background .2s ease;
}
.pdf-dialog-download:hover {
  background: #bfdbfe;
}
.pdf-viewer-wrap {
  height: 75vh;
  min-height: 420px;
  background: #f1f5f9;
  position: relative;
  overflow: auto;
}
.pdf-viewer-wrap.pdf-viewer-fullscreen {
  height: calc(100vh - 52px);
}
.pdf-zoom-toolbar {
  position: absolute;
  bottom: 16px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(13, 45, 107, .9);
  backdrop-filter: blur(8px);
  padding: 6px 8px;
  border-radius: 999px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, .25);
}
.pdf-zoom-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: #fff;
  cursor: pointer;
  transition: background .2s ease;
}
.pdf-zoom-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, .2);
}
.pdf-zoom-btn:disabled {
  opacity: .35;
  cursor: not-allowed;
}
.pdf-zoom-label {
  min-width: 48px;
  text-align: center;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
  user-select: none;
}
.pdf-viewer-loading {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #5b7aa8;
  font-size: 13px;
  font-weight: 500;
}
.pdf-viewer-iframe {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
}
.pdf-viewer-image-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: auto;
  padding: 24px;
}
.pdf-viewer-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 10px;
  box-shadow: 0 12px 32px rgba(13, 45, 107, .2);
  border: 4px solid #fff;
}
.pdf-viewer-fallback {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  color: #7c93b8;
  font-size: 13px;
  font-weight: 500;
}
.pdf-viewer-fallback svg {
  color: #b6c6e0;
}
.pdf-viewer-download-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%);
  padding: 9px 20px;
  border-radius: 999px;
  text-decoration: none;
  box-shadow: 0 6px 18px rgba(13,45,107,.25);
  transition: transform .2s ease, box-shadow .2s ease;
}
.pdf-viewer-download-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 22px rgba(13,45,107,.32);
}

/* ── Diagnóstico column ── */
.sol-dx-cell {
  display: flex;
  flex-direction: column;
  gap: 3px;
  max-width: 220px;
}
.sol-dx-tag {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  line-height: 1.4;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sol-dx-tag strong {
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 800;
  color: #6d28d9;
  background: #ede9fe;
  padding: 1px 5px;
  border-radius: 4px;
  letter-spacing: .02em;
}
.sol-dx-desc {
  color: #64748b;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sol-dx-more {
  font-size: 10px;
  font-weight: 700;
  color: #7c3aed;
  padding-left: 2px;
}
.sol-dx-text {
  font-size: 11px;
  color: #64748b;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sol-dx-none {
  color: #cbd5e1;
  font-size: 12px;
}

.sol-header-spacer { flex: 1; }

/* ── Acciones rápidas junto a los chips ── */
.sol-chips-actions { display: flex; align-items: center; gap: 4px; margin-right: 4px; }
.sol-icon-btn {
  width: 30px; height: 30px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid #e2e8f0; color: #475569;
  cursor: pointer; transition: all .15s ease;
}
.sol-icon-btn:hover:not(:disabled) { border-color: #bfdbfe; color: var(--rf-primary); background: #eff6ff; }
.sol-icon-btn:disabled { opacity: .45; cursor: not-allowed; }
.sol-icon-btn-primary {
  background: var(--rf-primary); border-color: var(--rf-primary); color: #fff;
}
.sol-icon-btn-primary:hover:not(:disabled) { background: #1d56b4; border-color: #1d56b4; color: #fff; }
.dark .sol-icon-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
.dark .sol-icon-btn-primary { background: var(--rf-primary); border-color: var(--rf-primary); color: #fff; }

/* ── Filter bar ── */
.sol-filter-bar {
  display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
  padding: .55rem .9rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0f6ff 0%, #e6efff 50%, #f0f9ff 100%);
  border: 1px solid #b8c8e0;
  box-shadow: 0 3px 16px rgba(13, 45, 107, 0.07), inset 0 1px 0 rgba(255,255,255,0.6);
  position: relative;
  /* Stacking context: el panel de filtros queda siempre sobre el thead sticky */
  z-index: 30;
  transition: box-shadow .3s ease, transform .3s ease;
}
.sol-filter-bar:hover {
  box-shadow: 0 5px 24px rgba(13, 45, 107, 0.11), inset 0 1px 0 rgba(255,255,255,0.6);
  transform: translateY(-1px);
}
.sol-filter-divider {
  width: 1px; height: 24px;
  background: linear-gradient(180deg, transparent, #b8c8e0, transparent);
  flex-shrink: 0;
}

/* ── Tabs ── */
.sol-tabs {
  display: flex; gap: 4px;
  padding: 3px;
  background: rgba(255,255,255,0.7);
  border: 1px solid #d4deea;
  border-radius: 10px;
}
.sol-tab {
  display: flex; align-items: center; gap: 6px;
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 12px; font-weight: 600; color: #64748b;
  background: transparent;
  border: none; cursor: pointer;
  transition: all .2s ease;
}
.sol-tab:hover { color: #1e2d55; background: rgba(13,45,107,.06); transform: translateY(-1px); }
.sol-tab-active {
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  box-shadow: 0 2px 10px rgba(13,45,107,.28);
}
.sol-tab-count {
  padding: 1px 6px; border-radius: 999px;
  font-size: 9px; font-weight: 700;
  background: rgba(13,45,107,.08); color: #1e2d55;
}
.sol-tab-active .sol-tab-count { background: rgba(255,255,255,.2); color: #fff; }

/* Chips que navegan al histórico (completadas / negadas) */
.sol-tab-link:hover { color: #16468E; }
.sol-tab-link .sol-tab-count { background: rgba(13,45,107,.08); }

/* Botón Filtros */
.sol-filtros-btn {
  display: inline-flex; align-items: center; gap: 5px;
  height: 30px; padding: 0 12px;
  border-radius: 9px; border: 1px solid #d4deea;
  background: rgba(255,255,255,0.7);
  font-size: 11.5px; font-weight: 700; color: #475569;
  cursor: pointer; transition: all .15s ease;
  font-family: inherit;
}
.sol-filtros-btn:hover, .sol-filtros-btn-active {
  border-color: #16468E; color: #16468E; background: #eef4ff;
}
.dark .sol-filtros-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #cbd5e1; }

/* ── Panel desplegable de filtros (abre el botón "Filtros") ── */
.sol-filtros-wrap { position: relative; }
.sol-filtros-badge {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 15px; height: 15px; padding: 0 4px;
  border-radius: 999px;
  background: #16468E; color: #fff;
  font-size: 8.5px; font-weight: 800;
}
.sol-filtros-btn-active .sol-filtros-badge { background: #fff; color: #16468E; }
.sol-filtros-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 230px;
  display: flex; flex-direction: column; gap: 8px;
  padding: 10px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.14);
  z-index: 60;
  max-height: min(420px, calc(100vh - 240px));
  overflow-y: auto;
}
.dark .sol-filtros-panel { background: #161b28; border-color: rgba(255,255,255,0.08); }
.sol-filtros-panel .sol-select { width: 100%; }
.sol-filtros-panel .sol-search { min-width: 0; max-width: none; width: 100%; }
.sol-filtros-panel .drf { width: 100%; }
.sol-filtros-panel :deep(.drf-trigger) { width: 100%; justify-content: flex-start; }
.sol-filtros-panel :deep(.el-select__wrapper),
.sol-filtros-panel :deep(.el-input__wrapper) {
  border-radius: 9px !important;
  min-height: 30px;
}
.sol-limpiar {
  font-size: 11.5px; font-weight: 700; color: #dc2626;
  background: none; border: none; cursor: pointer;
  font-family: inherit; padding: 4px 8px; border-radius: 7px;
  transition: background .15s ease;
}
.sol-limpiar:hover { background: #fef2f2; }

/* ── Search ── */
.sol-search { flex: 1; min-width: 200px; max-width: 400px; }
.sol-filter-bar :deep(.el-input__wrapper) {
  background: rgba(255,255,255,0.75) !important;
  border: 1px solid #d4deea !important;
  border-radius: 10px !important;
  transition: all .2s ease;
}
.sol-filter-bar :deep(.el-input__wrapper:hover) {
  border-color: #16468E !important;
  box-shadow: 0 0 0 2px rgba(22,70,142,0.08) !important;
}

/* ── Table panel ── */
.sol-table-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(22,70,142,.08);
  padding: 4px;
  display: flex;
  flex-direction: column;
}
.dark .sol-table-panel { background: #161b28; border-color: rgba(255,255,255,0.07); }

/* ── Table loading ── */
.sol-table-loading {
  display: flex; flex-direction: column; gap: 6px;
  padding: 4px;
}
.sol-table-row-skeleton {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: 8px;
  background: rgba(255,255,255,0.8);
  border: 1px solid #e2e8f0;
}

/* ── Table ── */
.sol-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.sol-th {
  padding: 8px 12px;
  text-align: left;
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em;
  color: rgba(255,255,255,.85);
  background: #16468E;
  border-bottom: 1px solid #123a78;
  text-shadow: 0 1px 2px rgba(0,0,0,.25);
}
.sol-th:first-child { border-radius: 8px 0 0 8px; }
.sol-th:last-child { border-radius: 0 8px 8px 0; }
.sol-table-thead th { position: sticky; top: 0; z-index: 10; }
.sol-td {
  padding: 9px 12px;
  font-size: 12px; color: #334155;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  white-space: nowrap;
}
.sol-table-row { transition: background .15s ease, box-shadow .15s ease; }
.sol-table-row:hover { background: #f8fafc; box-shadow: inset 3px 0 0 var(--rf-primary); }
.sol-table-row:hover .sol-table-status { transform: scale(1.06); }
.sol-table-row:last-child td { border-bottom: none; }
.sol-th-paciente { min-width: 200px; }
.sol-th-actions { text-align: right; }

.sol-table-paciente { display: flex; align-items: center; gap: 10px; }
.sol-table-paciente-info { min-width: 0; }
.sol-table-avatar {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 11px; font-weight: 800;
}
.sol-table-name {
  font-size: 13px; font-weight: 700; color: #1e2d55;
  margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 200px;
}
.sol-table-clinica-name { font-size: 12px; font-weight: 700; color: #1e40af; margin: 0; }
.sol-table-remitente { font-size: 10px; color: #94a3b8; margin: 2px 0 0; }
.sol-table-doc { font-size: 10px; color: #94a3b8; margin: 1px 0 0; }
.sol-table-status {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 10px; font-weight: 700;
  transition: transform .2s ease;
}
.sol-status-dot { width: 5px; height: 5px; border-radius: 50%; }
.sol-status-pending { background: #fef3c7; color: #d97706; }
.sol-status-pending .sol-status-dot { background: #fbbf24; }
.sol-status-waiting { background: #dbeafe; color: #1d4ed8; }
.sol-status-waiting .sol-status-dot { background: #3b82f6; }
.sol-status-completed { background: #dcfce7; color: #16a34a; }
.sol-status-completed .sol-status-dot { background: #22c55e; }
.sol-status-rejected { background: #fee2e2; color: #dc2626; }
.sol-status-rejected .sol-status-dot { background: #ef4444; }
.sol-table-actions { display: flex; justify-content: flex-end; gap: 6px; }
.sol-table-actions .el-button {
  margin-left: 0 !important;
  transition: transform .2s cubic-bezier(.22,1,.36,1), box-shadow .2s ease, filter .2s ease;
}
.sol-table-actions .el-button:hover {
  filter: brightness(1.08);
}
.sol-table-actions .el-button:active {
  filter: brightness(0.95);
}
.sol-table-actions .el-button--success:hover {
  box-shadow: 0 6px 16px rgba(22,163,74,.30);
}
.sol-table-actions .el-button--primary:hover {
  box-shadow: 0 6px 16px rgba(37,99,235,.30);
}
.sol-table-actions .el-button--danger:hover {
  box-shadow: 0 6px 16px rgba(220,38,38,.30);
}
.sol-table-actions .el-button--default:hover {
  box-shadow: 0 6px 16px rgba(13,45,107,.15);
}

/* ── Empty state ── */
.sol-empty-wrap {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  text-align: center; padding: 2rem 1rem;
  height: 100%;
}
.sol-empty-icon {
  color: #2f70bb;
  background: linear-gradient(135deg, #e4f0ff, #dbeafe);
  box-shadow: 0 8px 20px rgba(47, 112, 187, .15);
}

/* ── Scrollbar ── */
/* El estilo del scroll es global en tailwind.css (invisible hasta hover, tipo Element Plus) */

/* ── Animaciones ── */
.anim-row-in { animation: rowIn 0.4s cubic-bezier(.22,1,.36,1) both; }
@keyframes rowIn { from { opacity:0; transform:translateX(-12px); } to { opacity:1; transform:none; } }

/* ── Shimmer ── */
.shimmer-box, .shimmer-bar { position: relative; overflow: hidden; background: #e6ebf3; }
.shimmer-box { border-radius: 6px; }
.shimmer-bar { border-radius: 4px; }
.shimmer-box::after, .shimmer-bar::after {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.6) 50%, transparent 100%);
  animation: shimmer 1.8s ease-in-out infinite;
}
@keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

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
  overflow-y: auto;
  overflow-x: hidden;
}

:deep(.el-overlay) { background-color: rgba(8, 27, 58, .56); backdrop-filter: blur(4px); }

.detalle-content { padding: 0; position: relative; background: #F8FAFC; }

.detalle-close-btn {
  position: absolute;
  top: .8rem; right: .9rem;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 30px; height: 30px;
  border-radius: 50%;
  border: 1px solid #E2E8F0;
  background: #fff;
  color: #94A3B8;
  cursor: pointer;
  transition: all .2s ease;
}
.detalle-close-btn:hover { color: #DC2626; border-color: #FCA5A5; background: #FEF2F2; }

/* Header claro */
.detalle-head {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .7rem 3.5rem .7rem 1.1rem;
  background: #fff;
  border-bottom: 1px solid #EEF2F9;
}
.detalle-head-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(79,70,229,.06) 1.4px, transparent 1.4px);
  background-size: 15px 15px;
  -webkit-mask-image: linear-gradient(115deg, rgba(0,0,0,.9), transparent 70%);
  mask-image: linear-gradient(115deg, rgba(0,0,0,.9), transparent 70%);
  pointer-events: none;
}
.detalle-head-icon {
  position: relative; z-index: 1;
  width: 36px; height: 36px;
  border-radius: 11px;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  display: grid; place-items: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 6px 16px rgba(79, 70, 229, .3);
}
.detalle-head-info { position: relative; z-index: 1; flex: 1; min-width: 0; }
.detalle-head-title { margin: 0; color: #0F172A; font-size: 1rem; font-weight: 800; }
.detalle-head-sub { margin: .15rem 0 0; color: #64748b; font-size: .7rem; }

.detalle-head-badge {
  position: relative; z-index: 1;
  padding: .3rem .7rem;
  border-radius: 999px;
  font-size: .65rem;
  font-weight: 700;
  flex-shrink: 0;
  display: inline-flex; align-items: center; gap: .3rem;
}
.detalle-head-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.detalle-head-badge.estado-pendiente { background: #fef3c7; color: #b45309; }
.detalle-head-badge.estado-en_espera { background: #dbeafe; color: #1d4ed8; }
.detalle-head-badge.estado-completado { background: #dcfce7; color: #16a34a; }
.detalle-head-badge.estado-negado { background: #fee2e2; color: #b91c1c; }

.detalle-head-pdf {
  position: relative; z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .4rem .75rem;
  border-radius: 9px;
  background: #fff;
  border: 1px solid #dce7f2;
  color: #16468e;
  font-size: .68rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s ease;
  flex-shrink: 0;
}
.detalle-head-pdf:hover { background: #eff6ff; border-color: #86b4e8; }

.detalle-body {
  padding: .7rem 1.1rem;
}

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
.detalle-card:hover {
  box-shadow: 0 10px 28px rgba(22,70,142,.14);
  border-color: #b8c8de;
  transform: translateY(-1px);
}
.detalle-card-head {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .45rem .6rem .25rem;
}
.detalle-card-icon {
  width: 1.6rem; height: 1.6rem;
  border-radius: 8px;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.detalle-card-icon-blue { background: #dbeafe; color: #2563eb; }
.detalle-card-icon-amber { background: #fef3c7; color: #d97706; }
.detalle-card-icon-violet { background: #ede9fe; color: #7c3aed; }
.detalle-card-icon-slate { background: #f1f5f9; color: #475569; }
.detalle-card-icon-rose { background: #fee2e2; color: #dc2626; }
.detalle-card-title {
  margin: 0;
  font-size: .74rem;
  font-weight: 800;
}
.detalle-card-title-blue { color: #1e40af; }
.detalle-card-title-amber { color: #b45309; }
.detalle-card-title-violet { color: #6d28d9; }
.detalle-card-title-slate { color: #334155; }
.detalle-card-title-rose { color: #b91c1c; }

.card-body { padding: 0 .6rem .45rem; }
.card-text { font-size: .72rem; color: #334e70; line-height: 1.5; margin: 0; }
.card-text-sm { font-size: .66rem; color: #64748b; line-height: 1.45; margin: 0; }
.card-text-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.leer-mas-btn {
  margin-top: .3rem;
  font-size: .62rem;
  font-weight: 700;
  color: #2563eb;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
}
.leer-mas-btn:hover { text-decoration: underline; }

:deep(.historia-dialog) { border-radius: 18px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.35); }
:deep(.historia-dialog .el-dialog__header) { margin: 0; padding: 1rem 1.25rem; background: linear-gradient(135deg, #0D2D6B, #16468E); }
:deep(.historia-dialog .el-dialog__body) { padding: 1.25rem; }
:deep(.historia-dialog .el-dialog__headerbtn) { top: 14px; right: 14px; }
:deep(.historia-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; }
.historia-dialog-header { display: flex; align-items: center; gap: .7rem; color: #fff; }
.historia-dialog-header > svg { color: #7dd3fc; flex-shrink: 0; }
.historia-dialog-header p { margin: 0; font-size: 14px; font-weight: 800; }
.historia-dialog-header span { display: block; margin-top: 2px; color: rgba(255,255,255,.65); font-size: 11px; }
.historia-dialog-text { max-height: min(55vh, 520px); margin: 0; overflow-y: auto; white-space: pre-wrap; word-break: break-word; color: #334155; font-size: 13px; line-height: 1.7; }

.card-dx-list { display: flex; flex-direction: column; gap: 5px; }
.card-dx-item {
  display: flex; align-items: baseline; gap: 6px;
  padding: 5px 8px; border-radius: 8px;
  background: #f5f3ff;
}
.card-dx-code {
  font-size: 11px; font-weight: 800; color: #7c3aed;
  font-family: monospace; flex-shrink: 0;
}
.card-dx-desc { font-size: 11px; color: #334155; }

.data-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: .4rem;
  padding: .12rem 0;
  border-bottom: 1px solid #f1f5f9;
}
.data-row:last-child { border-bottom: none; }
.data-row span { font-size: .62rem; color: #94a3b8; white-space: nowrap; }
.data-row strong { font-size: .68rem; color: #1e293b; font-weight: 600; text-align: right; }

/* Timeline (Seguimiento) */
.timeline-item {
  display: flex;
  gap: .5rem;
  align-items: flex-start;
}
.timeline-connector {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}
.timeline-dot {
  width: 17px; height: 17px;
  border-radius: 50%;
  display: grid; place-items: center;
  background: #f1f5f9;
  color: #94a3b8;
  border: 2px solid #e2e8f0;
  transition: all .2s ease;
}
.timeline-dot-done {
  background: #dbeafe;
  color: #2563eb;
  border-color: #bfdbfe;
}
.timeline-dot-negada {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}
.timeline-dot-revertido {
  background: #fef3c7;
  color: #b45309;
  border-color: #fde68a;
}
.timeline-line {
  width: 2px;
  flex: 1;
  min-height: 10px;
  background: #e2e8f0;
  margin: 1px 0;
}
.timeline-line-done { background: #bfdbfe; }
.timeline-text { padding-bottom: .4rem; padding-top: 0; min-width: 0; }
.timeline-titulo { font-size: .68rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2; }
.timeline-titulo-pending { color: #94a3b8; }
.timeline-fecha { font-size: .58rem; color: #94a3b8; margin: .05rem 0 0; }
.timeline-obs {
  font-size: .62rem; color: #475569; line-height: 1.4;
  margin: .2rem 0 0 1.55rem;
  background: #f8fafc; border-radius: 6px; padding: .3rem .5rem;
}
.timeline-obs-negada { background: #fef2f2; color: #b91c1c; }

/* Adjuntos (Soportes) */
.adjunto-list { display: flex; flex-direction: column; gap: .3rem; }
.adjunto-row {
  display: flex;
  align-items: center;
  gap: .45rem;
  padding: .25rem;
  border-radius: 10px;
  transition: background .15s ease;
  width: 100%;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
}
.adjunto-row:hover { background: #f8fafc; }
.adjunto-thumb {
  width: 28px; height: 28px;
  border-radius: 6px;
  object-fit: cover;
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
}
.adjunto-icon {
  width: 28px; height: 28px;
  display: grid; place-items: center;
  font-size: .5rem;
  font-weight: 800;
  color: #fff;
  border-radius: 6px;
  flex-shrink: 0;
}
.adjunto-pdf { background: #dc2626; }
.adjunto-img { background: #2563eb; }
.adjunto-info { min-width: 0; flex: 1; }
.adjunto-name {
  font-size: .68rem;
  color: #334e70;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.adjunto-meta { font-size: .58rem; color: #94a3b8; margin: .05rem 0 0; }
.adjunto-action-icon { color: #94a3b8; flex-shrink: 0; }
.adjunto-row:hover .adjunto-action-icon { color: #16468e; }
.adjunto-empty {
  font-size: .66rem;
  color: #cbd5e1;
  font-style: italic;
  margin: 0;
  padding: .3rem 0;
}

/* Footer */
.detalle-footer {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .6rem 1.1rem;
  background: #fff;
  border-top: 1px solid #F1F5F9;
}
.detalle-footer-note {
  flex: 1;
  display: flex;
  align-items: center;
  gap: .35rem;
  margin: 0;
  font-size: .66rem;
  font-weight: 500;
  color: #94A3B8;
}
.detalle-cerrar-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .55rem 1.1rem;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(124, 58, 237, .3);
  transition: all .2s ease;
  flex-shrink: 0;
}
.detalle-cerrar-btn:hover {
  background: linear-gradient(135deg, #5B52F0, #8B47E8);
  box-shadow: 0 6px 20px rgba(124, 58, 237, .4);
}

/* ── Modales de acción (Aceptar/Negar/En espera/Completar) ── */
:deep(.accion-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.accion-dialog .el-dialog__header) { display: none; }
:deep(.accion-dialog .el-dialog__body) { padding: 0; overflow: hidden; }

.accion-icon-success { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 6px 16px rgba(22,163,74,.3); }
.accion-icon-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 6px 16px rgba(220,38,38,.3); }
.accion-icon-info { background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 6px 16px rgba(37,99,235,.3); }
.accion-icon-violet { background: linear-gradient(135deg, #818cf8, #6366f1); box-shadow: 0 6px 16px rgba(99,102,241,.3); }
.accion-icon-amber { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 6px 16px rgba(217,119,6,.3); }
.accion-icon-teal { background: linear-gradient(135deg, #2dd4bf, #0d9488); box-shadow: 0 6px 16px rgba(13,148,136,.3); }

.accion-patient-card {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .65rem .8rem;
  border-radius: 12px;
  background: linear-gradient(120deg, #0D2D6B 0%, #16468E 100%);
  box-shadow: 0 6px 16px rgba(13,45,107,.22);
  margin-bottom: .6rem;
}
.accion-patient-avatar {
  display: grid;
  place-items: center;
  width: 34px; height: 34px;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(255,255,255,.18);
  color: #fff;
  font-size: .68rem;
  font-weight: 800;
}
.accion-patient-info { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.accion-patient-info strong { font-size: .82rem; font-weight: 800; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.accion-patient-info span { font-size: .66rem; color: rgba(255,255,255,.75); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.accion-form-body { padding: .7rem .7rem .6rem; }
.accion-field-label {
  display: block;
  font-size: .66rem;
  font-weight: 700;
  color: #475569;
  margin-bottom: .3rem;
}
.accion-required { color: #dc2626; }
.accion-field-hint { font-size: .62rem; color: #94a3b8; margin: .25rem 0 0; line-height: 1.4; }
.accion-gomedisys-callout {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .55rem .65rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #f5f3ff, #ede9fe);
  border: 1px solid #ddd6fe;
}
.accion-gomedisys-callout-text {
  display: flex;
  align-items: center;
  gap: .4rem;
  flex: 1;
  font-size: .66rem;
  font-weight: 600;
  color: #6d28d9;
  line-height: 1.35;
}
.accion-gomedisys-btn {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .4rem .7rem;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #a78bfa, #7c3aed);
  color: #fff;
  font-size: .68rem;
  font-weight: 700;
  cursor: pointer;
  flex-shrink: 0;
  white-space: nowrap;
  transition: all .2s ease;
  box-shadow: 0 3px 10px rgba(124,58,237,.3);
}
.accion-gomedisys-btn:hover:not(.accion-gomedisys-btn-enviando) { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(124,58,237,.4); }
/* No se usa :disabled a propósito — un botón enfocado que se deshabilita
   pierde el foco de golpe, y eso hacía que la página saltara de scroll al
   dar clic en "Enviar a Gomedisys". Este estado se ve igual mientras envía,
   pero sin quitarlo de verdad del flujo de foco (el doble clic ya está
   bloqueado aparte, en abrirGomedisys). */
.accion-gomedisys-btn-enviando { opacity: .6; cursor: default; pointer-events: none; }
.accion-confirm-text {
  font-size: .78rem;
  color: #475569;
  line-height: 1.6;
  margin: 0;
  padding: 0 .1rem;
}

.accion-cancelar-btn {
  padding: .55rem 1.1rem;
  border-radius: 10px;
  border: 1px solid #dce7f2;
  background: #fff;
  color: #475569;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s ease;
}
.accion-cancelar-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }

.accion-confirmar-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .55rem 1.1rem;
  border-radius: 10px;
  border: none;
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s ease;
  flex-shrink: 0;
  margin-left: auto;
}
.accion-confirmar-btn:disabled { opacity: .65; cursor: default; }
.accion-confirmar-success { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 4px 14px rgba(22,163,74,.3); }
.accion-confirmar-success:hover:not(:disabled) { background: linear-gradient(135deg, #34d399, #22c55e); box-shadow: 0 6px 20px rgba(22,163,74,.4); }
.accion-confirmar-danger { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 14px rgba(220,38,38,.3); }
.accion-confirmar-danger:hover:not(:disabled) { background: linear-gradient(135deg, #f87171, #ef4444); box-shadow: 0 6px 20px rgba(220,38,38,.4); }
.accion-confirmar-info { background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 4px 14px rgba(37,99,235,.3); }
.accion-confirmar-info:hover:not(:disabled) { background: linear-gradient(135deg, #60a5fa, #3b82f6); box-shadow: 0 6px 20px rgba(37,99,235,.4); }
.accion-confirmar-violet { background: linear-gradient(135deg, #818cf8, #6366f1); box-shadow: 0 4px 14px rgba(99,102,241,.3); }
.resultado-gomedisys-mensaje {
  margin: 0;
  padding: .85rem 1rem;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  font-size: .82rem;
  line-height: 1.5;
}
.accion-confirmar-violet:hover:not(:disabled) { background: linear-gradient(135deg, #a5b4fc, #818cf8); box-shadow: 0 6px 20px rgba(99,102,241,.4); }
.accion-confirmar-amber { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 14px rgba(217,119,6,.3); }
.accion-confirmar-amber:hover:not(:disabled) { background: linear-gradient(135deg, #fbbf24, #f59e0b); box-shadow: 0 6px 20px rgba(217,119,6,.4); }
</style>

<style>
/* ── Menú de acciones de la fila (teleportado al body por Element Plus,
   por eso va sin "scoped" — el estilo normal no le llegaría) ── */
.sol-acciones-menu {
  min-width: 190px;
  border-radius: 14px !important;
  border: 1.5px solid #d7dde6 !important;
  box-shadow: 0 14px 38px rgba(15, 23, 42, .18) !important;
  overflow: hidden;
}
.sol-acciones-menu .el-dropdown-menu {
  padding: 6px !important;
}
.sol-acciones-menu .el-dropdown-menu__item {
  border-radius: 8px;
  margin: 1px 0;
  padding: .4rem .6rem;
  font-size: .78rem;
  font-weight: 500;
  line-height: 1.3;
  gap: .45rem;
}
.sol-acciones-menu .el-dropdown-menu__item i,
.sol-acciones-menu .el-dropdown-menu__item svg {
  font-size: .85rem;
  margin-right: 0;
}
.sol-acciones-menu .el-dropdown-menu__item--divided {
  margin-top: 5px;
  border-top-color: #eef1f5;
}
.sol-acciones-menu .el-dropdown-menu__item--divided::before {
  margin-bottom: 5px;
}
.sol-acciones-menu .sol-accion-success { color: #16a34a; }
.sol-acciones-menu .sol-accion-success:hover { background: #f0fdf4; color: #15803d; }
.sol-acciones-menu .sol-accion-info { color: #2563eb; }
.sol-acciones-menu .sol-accion-info:hover { background: #eff6ff; color: #1d4ed8; }
.sol-acciones-menu .sol-accion-violet { color: #6366f1; }
.sol-acciones-menu .sol-accion-violet:hover { background: #eef2ff; color: #4f46e5; }
.sol-acciones-menu .sol-accion-teal { color: #0d9488; }
.sol-acciones-menu .sol-accion-teal:hover { background: #f0fdfa; color: #0f766e; }
.sol-acciones-menu .sol-accion-danger { color: #dc2626; }
.sol-acciones-menu .sol-accion-danger:hover { background: #fef2f2; color: #b91c1c; }
.sol-acciones-menu .sol-accion-neutral { color: #64748b; }
.sol-acciones-menu .sol-accion-neutral:hover { background: #f8fafc; color: #334155; }
</style>
