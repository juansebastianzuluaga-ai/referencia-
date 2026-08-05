<template>
  <div class="ph-solicitudes h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden">

    <!-- ── Header ── -->
    <div class="sol-header shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="sol-header-icon">
        <component :is="ClipboardListIcon" class="w-5 h-5" />
      </div>
      <h1 class="sol-header-title">Solicitudes de referencia</h1>
      <div class="sol-header-spacer"></div>
      <el-button type="primary" size="small" @click="cargar">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" :class="{ 'animate-spin': cargando }" />
        Actualizar
      </el-button>
      <el-button size="small" @click="exportarExcel" :disabled="solicitudesFiltradas.length === 0">
        <component :is="DownloadIcon" class="w-3.5 h-3.5 mr-1" />
        Exportar
      </el-button>
    </div>

    <!-- ── Stat cards ── -->
    <div class="sol-stats-bar shrink-0">
      <div
        v-for="(card, i) in statCards"
        :key="card.label"
        class="sol-stat-card"
        :style="{ '--stat-color': card.color, '--stat-bg': card.iconBackground }"
      >
        <div class="sol-stat-icon" :style="{ background: card.iconBackground, color: card.color }">
          <component :is="card.icon" class="w-4 h-4" />
        </div>
        <div class="sol-stat-body">
          <p class="sol-stat-label">{{ card.label }}</p>
          <p class="sol-stat-value" :style="{ color: card.color }">{{ displayStats[i] }}</p>
          <div class="sol-stat-bar-track">
            <div class="sol-stat-bar-fill" :style="{ width: statPercents[i] + '%', background: card.color }"></div>
          </div>
        </div>
        <span class="sol-stat-delta" :style="{ background: card.iconBackground, color: card.color }">{{ card.sub(cardValue(i)) }}</span>
      </div>
    </div>

    <!-- ── Tabs + Búsqueda ── -->
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
      </div>
      <div class="sol-filter-divider"></div>
      <el-input
        v-model="filtro.buscar"
        placeholder="Buscar paciente, EPS, especialidad..."
        class="sol-search"
        clearable
        size="small"
      >
        <template #prefix>
          <component :is="SearchIcon" class="w-3.5 h-3.5 text-gray-400" />
        </template>
      </el-input>
      <el-button v-if="filtro.buscar || tabActiva !== 'todas'" type="danger" size="small" round @click="limpiarFiltros">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" />
        Borrar filtros
      </el-button>
    </div>

    <!-- ── Bulk actions bar ── -->
    <Transition name="bulk-slide">
      <div v-if="seleccionadas.size > 0" class="sol-bulk-bar shrink-0">
        <span class="text-xs font-bold" style="color:#0D2D6B;">{{ seleccionadas.size }} seleccionada(s)</span>
        <el-button type="success" size="small" @click="aceptarLote">
          <component :is="CheckIcon" class="w-3 h-3 mr-0.5" /> Aceptar
        </el-button>
        <el-button type="danger" size="small" @click="negarLote">
          <component :is="XIcon" class="w-3 h-3 mr-0.5" /> Negar
        </el-button>
        <el-button size="small" text @click="seleccionadas.clear()">Limpiar</el-button>
      </div>
    </Transition>

    <!-- ── Tabla ── -->
    <div class="flex-1 overflow-hidden sol-table-panel">
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
                <th class="sol-th sol-th-check">
                  <input type="checkbox" :checked="todasSeleccionadas" @change="toggleSeleccionTodas" class="sol-checkbox" />
                </th>
                <th class="sol-th sol-th-paciente sol-th-sortable" @click="toggleSort('paciente')">
                  Paciente
                  <component :is="sortIcon('paciente')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'paciente', 'opacity-30': sortKey !== 'paciente' }" />
                </th>
                <th class="sol-th sol-th-sortable" @click="toggleSort('clinica')">
                  Clínica
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
                v-for="(s, idx) in solicitudesPaginadas"
                :key="s.id"
                class="sol-table-row"
                :class="{ 'sol-table-row-selected': seleccionadas.has(s.id) }"
              >
                <td class="sol-td sol-td-check">
                  <input type="checkbox" :checked="seleccionadas.has(s.id)" @change="toggleSeleccion(s.id)" class="sol-checkbox" />
                </td>
                <td class="sol-td">
                  <div class="sol-table-paciente">
                    <div class="sol-table-avatar"
                      :style="{
                        background: s.estado === 'pendiente' ? '#fef3c7' : s.estado === 'aceptado' ? '#dcfce7' : s.estado === 'en_espera' ? '#dbeafe' : s.estado === 'completado' ? '#e0e7ff' : '#fee2e2',
                        color: s.estado === 'pendiente' ? '#d97706' : s.estado === 'aceptado' ? '#16a34a' : s.estado === 'en_espera' ? '#2563eb' : s.estado === 'completado' ? '#4f46e5' : '#dc2626'
                      }">
                      {{ inicialesPaciente(s) }}
                    </div>
                    <div class="sol-table-paciente-info">
                      <p class="sol-table-name">{{ nombreCompleto(s) }}</p>
                      <p class="sol-table-doc">{{ formatFecha(s.created_at) }} · {{ s.hora }}</p>
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
                  <div v-if="s.diagnosticos?.length" class="sol-dx-cell">
                    <span
                      v-for="dx in s.diagnosticos.slice(0, 2)"
                      :key="dx.id"
                      class="sol-dx-tag"
                    >
                      <strong>{{ dx.codigo_cie10 }}</strong>
                      <span class="sol-dx-desc">{{ dx.descripcion }}</span>
                    </span>
                    <span v-if="s.diagnosticos.length > 2" class="sol-dx-more">+{{ s.diagnosticos.length - 2 }}</span>
                  </div>
                  <p v-else-if="s.diagnostico" class="sol-dx-text">{{ s.diagnostico }}</p>
                  <span v-else class="sol-dx-none">—</span>
                </td>
                <td class="sol-td">
                  <span class="sol-table-status" :class="{
                    'sol-status-pending': s.estado === 'pendiente',
                    'sol-status-accepted': s.estado === 'aceptado',
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
                    <el-tooltip v-if="s.estado === 'pendiente'" :key="`aceptar-${s.id}`" content="Aceptar" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <el-button type="success" circle size="small" @click="abrirAceptar(s)">
                        <component :is="CheckIcon" class="w-3.5 h-3.5" />
                      </el-button>
                    </el-tooltip>
                    <el-tooltip v-if="s.estado === 'aceptado'" :key="`espera-${s.id}`" content="Marcar en espera" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <el-button type="primary" circle size="small" @click="marcarEnEspera(s)">
                        <component :is="ClockIcon" class="w-3.5 h-3.5" />
                      </el-button>
                    </el-tooltip>
                    <el-tooltip v-if="s.estado === 'en_espera'" :key="`completar-${s.id}`" content="Completar" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <el-button type="primary" circle size="small" @click="marcarCompletado(s)">
                        <component :is="CheckCircleIcon" class="w-3.5 h-3.5" />
                      </el-button>
                    </el-tooltip>
                    <el-tooltip v-if="s.estado !== 'negado' && s.estado !== 'completado'" :key="`negar-${s.id}`" content="Negar" placement="top" :popper-options="{ strategy: 'fixed' }">
                      <el-button type="danger" circle size="small" @click="abrirNegar(s)">
                        <component :is="XIcon" class="w-3.5 h-3.5" />
                      </el-button>
                    </el-tooltip>
                  </div>
                </td>
              </tr>
            </TransitionGroup>
          </table>
        </div>
        <!-- Pagination -->
        <div class="sol-pagination">
          <span class="sol-pagination-info">
            {{ (paginaActual - 1) * itemsPorPagina + 1 }}–{{ Math.min(paginaActual * itemsPorPagina, solicitudesFiltradas.length) }}
            de {{ solicitudesFiltradas.length }}
          </span>
          <div class="sol-pagination-controls">
            <button class="sol-pagination-btn" :disabled="paginaActual === 1" @click="paginaActual--">
              <component :is="ChevronLeftIcon" class="w-3.5 h-3.5" />
            </button>
            <span class="sol-pagination-page">{{ paginaActual }} / {{ totalPaginas }}</span>
            <button class="sol-pagination-btn" :disabled="paginaActual === totalPaginas" @click="paginaActual++">
              <component :is="ChevronRightIcon" class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Modal: Detalle ── -->
    <el-dialog v-model="modalDetalle" width="620px" class="detalle-dialog" :show-close="true" align-center>
      <template v-if="solicitudSeleccionada">
        <div class="detalle-content">
          <!-- Header azul -->
          <div class="detalle-head">
            <div class="detalle-head-glow"></div>
            <div class="detalle-head-icon">
              <component
                :is="solicitudSeleccionada.estado === 'aceptado' ? CheckCircleIcon : solicitudSeleccionada.estado === 'negado' ? XCircleIcon : solicitudSeleccionada.estado === 'en_espera' ? ClockIcon : solicitudSeleccionada.estado === 'completado' ? CheckCircleIcon : ClockIcon"
                class="w-6 h-6"
              />
            </div>
            <div class="detalle-head-info">
              <p class="detalle-head-title">Detalle de solicitud</p>
              <p class="detalle-head-sub">{{ nombreCompleto(solicitudSeleccionada) }}</p>
            </div>
            <div class="detalle-head-id-badge">ID #{{ solicitudSeleccionada.id }}</div>
            <div class="detalle-head-badge" :class="'badge-' + solicitudSeleccionada.estado">
              {{ estadoLabel(solicitudSeleccionada.estado) }}
            </div>
          </div>

          <!-- Body con cards -->
          <div class="detalle-body">
            <div class="detalle-cards-grid">
              <!-- Card: Paciente -->
              <div class="detalle-card detalle-card-blue">
                <div class="detalle-card-header">
                  <component :is="FileTextIcon" class="w-4 h-4" />
                  <span>PACIENTE</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row"><span class="detalle-row-label">Nombre</span><span class="detalle-row-value">{{ nombreCompleto(solicitudSeleccionada) }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Documento</span><span class="detalle-row-value font-mono">{{ solicitudSeleccionada.tipo_documento }} {{ solicitudSeleccionada.numero_documento }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Edad / Género</span><span class="detalle-row-value">{{ solicitudSeleccionada.edad }} años · {{ solicitudSeleccionada.genero === 'M' ? 'Masc.' : 'Fem.' }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">EPS</span><span class="detalle-row-value">{{ solicitudSeleccionada.eps }}</span></div>
                </div>
              </div>

              <!-- Card: Remisión -->
              <div class="detalle-card detalle-card-amber">
                <div class="detalle-card-header">
                  <component :is="FileTextIcon" class="w-4 h-4" />
                  <span>REMISIÓN</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row"><span class="detalle-row-label">Institución</span><span class="detalle-row-value">{{ solicitudSeleccionada.clinica?.nombre ?? '—' }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Especialidad</span><span class="detalle-row-value">{{ solicitudSeleccionada.especialidad_requerida }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Municipio</span><span class="detalle-row-value">{{ solicitudSeleccionada.municipio_capita }}</span></div>
                  <div class="detalle-card-row"><span class="detalle-row-label">Fecha</span><span class="detalle-row-value">{{ formatFecha(solicitudSeleccionada.fecha) }} · {{ solicitudSeleccionada.hora }}</span></div>
                  <div v-if="solicitudSeleccionada.quien_remitente" class="detalle-card-row"><span class="detalle-row-label">Remite</span><span class="detalle-row-value">{{ solicitudSeleccionada.quien_remitente }}</span></div>
                  <div v-if="solicitudSeleccionada.telefono_contacto" class="detalle-card-row"><span class="detalle-row-label">Teléfono</span><span class="detalle-row-value font-mono">{{ solicitudSeleccionada.telefono_contacto }}</span></div>
                  <div v-if="solicitudSeleccionada.correo_contacto" class="detalle-card-row"><span class="detalle-row-label">Correo</span><span class="detalle-row-value">{{ solicitudSeleccionada.correo_contacto }}</span></div>
                </div>
              </div>

              <!-- Card: Diagnósticos -->
              <div class="detalle-card detalle-card-purple">
                <div class="detalle-card-header">
                  <component :is="FileTextIcon" class="w-4 h-4" />
                  <span>DIAGNÓSTICOS</span>
                </div>
                <div v-if="solicitudSeleccionada.diagnosticos?.length" class="detalle-dx-list">
                  <div v-for="dx in solicitudSeleccionada.diagnosticos" :key="dx.id" class="detalle-dx-item">
                    <strong class="detalle-dx-code">{{ dx.codigo_cie10 }}</strong>
                    <span class="detalle-dx-desc">{{ dx.descripcion }}</span>
                  </div>
                </div>
                <p v-else-if="solicitudSeleccionada.diagnostico" class="detalle-card-text">{{ solicitudSeleccionada.diagnostico }}</p>
                <p v-else class="detalle-card-text">—</p>
              </div>

              <div v-if="solicitudSeleccionada.adjuntos?.length" class="detalle-card detalle-card-blue">
                <div class="detalle-card-header">
                  <component :is="PaperclipIcon" class="w-4 h-4" />
                  <span>ARCHIVOS ADJUNTOS ({{ solicitudSeleccionada.adjuntos.length }})</span>
                </div>
                <div class="detalle-adjuntos-list">
                  <button
                    v-for="adj in solicitudSeleccionada.adjuntos"
                    :key="adj.id"
                    type="button"
                    class="detalle-adjunto-item"
                    @click="abrirAdjunto(adj)"
                  >
                    <component :is="FileTextIcon" class="w-4 h-4 text-blue-500 flex-shrink-0" />
                    <span class="detalle-adjunto-name">{{ adj.nombre_original }}</span>
                    <span class="detalle-adjunto-size">{{ formatFileSize(adj.tamano) }}</span>
                    <component :is="isPreviewable(adj) ? EyeIcon : DownloadIcon" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                  </button>
                </div>
              </div>
            </div>

            <div class="detalle-card detalle-card-cyan mt-3">
              <div class="detalle-card-header">
                <component :is="FileTextIcon" class="w-4 h-4" />
                <span>HISTORIA CLÍNICA</span>
              </div>
              <p class="detalle-card-text detalle-historia-preview">{{ solicitudSeleccionada.resumen_historia_clinica }}</p>
              <button
                v-if="solicitudSeleccionada.resumen_historia_clinica?.length > 280"
                type="button"
                class="detalle-historia-button"
                @click="abrirHistoriaClinica"
              >
                Mostrar más
              </button>
            </div>

            <!-- Código de aceptación -->
            <div v-if="solicitudSeleccionada.codigo_aceptacion" class="detalle-code-bar mt-3">
              <component :is="CheckCircleIcon" class="w-4 h-4 text-emerald-600" />
              <span class="text-xs text-emerald-700 font-semibold">Código de aceptación</span>
              <span class="detalle-code-value">{{ solicitudSeleccionada.codigo_aceptacion }}</span>
            </div>

            <!-- Respuesta -->
            <div v-if="solicitudSeleccionada.nombre_quien_responde" class="detalle-card detalle-card-blue mt-3">
              <div class="detalle-card-header">
                <component :is="CheckCircleIcon" class="w-4 h-4" />
                <span>RESPUESTA DEL EQUIPO</span>
              </div>
              <p class="text-xs text-blue-700">Respondió: <strong>{{ solicitudSeleccionada.nombre_quien_responde }}</strong> · {{ solicitudSeleccionada.hora_respuesta }}</p>
              <p v-if="solicitudSeleccionada.observaciones_respuesta" class="text-xs text-blue-600 mt-1">{{ solicitudSeleccionada.observaciones_respuesta }}</p>
            </div>

            <!-- Motivo negación -->
            <div v-if="solicitudSeleccionada.motivo_negacion" class="detalle-card detalle-card-red mt-3">
              <div class="detalle-card-header detalle-card-header-red">
                <component :is="XCircleIcon" class="w-4 h-4" />
                <span>MOTIVO DE NEGACIÓN</span>
              </div>
              <p class="text-xs text-red-600 mt-1">{{ solicitudSeleccionada.motivo_negacion }}</p>
            </div>

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
    <el-dialog v-model="modalAceptar" width="480px" :close-on-click-modal="false" class="accion-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(34,197,94,0.2); color:#4ade80;">
            <component :is="CheckIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Aceptar solicitud</p>
            <p class="accion-head-sub">{{ solicitudSeleccionada ? nombreCompleto(solicitudSeleccionada) : '' }}</p>
          </div>
          <div v-if="solicitudSeleccionada" class="detalle-head-id-badge">ID #{{ solicitudSeleccionada.id }}</div>
        </div>
      </template>
      <div class="space-y-3 px-4 py-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Fecha</label>
            <el-input :model-value="formAceptar.fecha_respuesta" disabled />
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Hora</label>
            <el-input :model-value="formAceptar.hora_respuesta" disabled />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Nombre de quien responde</label>
          <el-input :model-value="formAceptar.nombre_quien_responde" disabled />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Observaciones</label>
          <el-input v-model="formAceptar.observaciones_respuesta" type="textarea" :rows="3" placeholder="Opcional" />
        </div>
      </div>
      <template #footer>
        <el-button @click="modalAceptar = false">Cancelar</el-button>
        <el-button type="success" :loading="procesando" @click="aceptar">
          <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
          Confirmar
        </el-button>
      </template>
    </el-dialog>

    <!-- ── Modal: Negar ── -->
    <el-dialog v-model="modalNegar" width="480px" :close-on-click-modal="false" class="accion-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(239,68,68,0.2); color:#f87171;">
            <component :is="XIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Negar solicitud</p>
            <p class="accion-head-sub">{{ solicitudSeleccionada ? nombreCompleto(solicitudSeleccionada) : '' }}</p>
          </div>
          <div v-if="solicitudSeleccionada" class="detalle-head-id-badge">ID #{{ solicitudSeleccionada.id }}</div>
        </div>
      </template>
      <div class="space-y-3 px-4 py-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Fecha</label>
            <el-input :model-value="formNegar.fecha_respuesta" disabled />
          </div>
          <div>
            <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Hora</label>
            <el-input :model-value="formNegar.hora_respuesta" disabled />
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Nombre de quien responde</label>
          <el-input :model-value="formNegar.nombre_quien_responde" disabled />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Motivo de negación <span class="text-red-400">*</span></label>
          <el-input v-model="formNegar.motivo_negacion" type="textarea" :rows="3" placeholder="Indique el motivo..." />
        </div>
        <div>
          <label class="block text-xs font-semibold mb-1" style="color:#334e70;">Observaciones adicionales</label>
          <el-input v-model="formNegar.observaciones_respuesta" type="textarea" :rows="2" placeholder="Opcional" />
        </div>
      </div>
      <template #footer>
        <el-button @click="modalNegar = false">Cancelar</el-button>
        <el-button type="danger" :loading="procesando" @click="negar">
          <component :is="XIcon" class="w-3.5 h-3.5 mr-1" />
          Confirmar
        </el-button>
      </template>
    </el-dialog>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useStorage, useDebounceFn } from '@vueuse/core';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
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
} from '@lucide/vue';
import http from '@/plugins/axios';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

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
  estado: 'pendiente' | 'aceptado' | 'en_espera' | 'completado' | 'negado';
  codigo_aceptacion?: string;
  hora_respuesta?: string;
  motivo_negacion?: string;
  numero_ingreso?: number;
  nombre_quien_responde?: string;
  observaciones_respuesta?: string;
  adjuntos?: { id: number; nombre_original: string; mime_type: string; tamano: number }[];
  created_at: string;
}

const solicitudes = ref<Solicitud[]>([]);
const cargando = ref(false);
const procesando = ref(false);
const filtro = useStorage('sol-filtro', { buscar: '' });
const buscarDebounced = ref(filtro.value.buscar);
const updateBuscarDebounced = useDebounceFn((val: string) => { buscarDebounced.value = val; }, 300);
watch(() => filtro.value.buscar, (val) => updateBuscarDebounced(val));
const tabActiva = useStorage<'todas' | 'pendiente' | 'aceptado' | 'en_espera' | 'completado' | 'negado'>('sol-tab', 'todas');

const seleccionadas = ref<Set<number>>(new Set());
const sortKey = ref<'paciente' | 'clinica' | 'especialidad'>('paciente');
const sortDir = ref<'asc' | 'desc'>('asc');
const paginaActual = ref(1);
const itemsPorPagina = 15;

const modalDetalle = ref(false);
const modalHistoriaClinica = ref(false);
const modalAceptar = ref(false);
const modalNegar = ref(false);
const modalPdf = ref(false);
const solicitudSeleccionada = ref<Solicitud | null>(null);
const adjuntoActivo = ref<{ id: number; nombre_original: string; mime_type: string; tamano: number } | null>(null);

const formAceptar = ref({ fecha_respuesta: '', hora_respuesta: '', nombre_quien_responde: '', observaciones_respuesta: '' });
const formNegar = ref({ fecha_respuesta: '', hora_respuesta: '', motivo_negacion: '', nombre_quien_responde: '', observaciones_respuesta: '' });

const resumen = computed(() => ({
  total: solicitudes.value.length,
  pendientes: solicitudes.value.filter(s => s.estado === 'pendiente').length,
  aceptadas: solicitudes.value.filter(s => s.estado === 'aceptado').length,
  enEspera: solicitudes.value.filter(s => s.estado === 'en_espera').length,
  completadas: solicitudes.value.filter(s => s.estado === 'completado').length,
  negadas: solicitudes.value.filter(s => s.estado === 'negado').length,
}));

const tabs = computed(() => [
  { label: 'Todas', value: 'todas', count: resumen.value.total },
  { label: 'Pendientes', value: 'pendiente', count: resumen.value.pendientes },
  { label: 'Aceptadas', value: 'aceptado', count: resumen.value.aceptadas },
  { label: 'En espera', value: 'en_espera', count: resumen.value.enEspera },
  { label: 'Completadas', value: 'completado', count: resumen.value.completadas },
  { label: 'Negadas', value: 'negado', count: resumen.value.negadas },
]);

const statCards = [
  { iconBackground: '#dbeafe', color: '#2563eb', icon: ClipboardListIcon, label: 'Solicitudes', sub: (n: number) => n ? `${n} registradas` : 'sin registros' },
  { iconBackground: '#fef3c7', color: '#d97706', icon: ClockIcon, label: 'Pendientes', sub: (n: number) => n ? `${n} en espera` : 'sin pendientes' },
  { iconBackground: '#dcfce7', color: '#16a34a', icon: CheckCircleIcon, label: 'Aceptadas', sub: (n: number) => n ? `${n} aprobadas` : 'sin aprobadas' },
  { iconBackground: '#fee2e2', color: '#dc2626', icon: XCircleIcon, label: 'Negadas', sub: (n: number) => n ? `${n} rechazadas` : 'sin rechazos' },
];

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

function cardValue(i: number): number {
  if (i === 0) return resumen.value.total;
  if (i === 1) return resumen.value.pendientes;
  if (i === 2) return resumen.value.aceptadas;
  return resumen.value.negadas;
}

const statPercents = computed(() => {
  const total = Math.max(1, resumen.value.total);
  return [
    100,
    Math.round((resumen.value.pendientes / total) * 100),
    Math.round((resumen.value.aceptadas / total) * 100),
    Math.round((resumen.value.negadas / total) * 100),
  ];
});

const solicitudesFiltradas = computed(() => {
  let result = solicitudes.value.filter(s => {
    if (tabActiva.value !== 'todas' && s.estado !== tabActiva.value) return false;
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
    else { va = (a.especialidad_requerida ?? '').toLowerCase(); vb = (b.especialidad_requerida ?? '').toLowerCase(); }
    return va < vb ? -dir : va > vb ? dir : 0;
  });
  return result;
});

const totalPaginas = computed(() => Math.max(1, Math.ceil(solicitudesFiltradas.value.length / itemsPorPagina)));
const solicitudesPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * itemsPorPagina;
  return solicitudesFiltradas.value.slice(start, start + itemsPorPagina);
});

const todasSeleccionadas = computed(() => {
  if (solicitudesPaginadas.value.length === 0) return false;
  return solicitudesPaginadas.value.every(s => seleccionadas.value.has(s.id));
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

function cambiarTab(tab: 'todas' | 'pendiente' | 'aceptado' | 'en_espera' | 'completado' | 'negado') {
  tabActiva.value = tab;
  paginaActual.value = 1;
}

function toggleSeleccion(id: number) {
  const s = new Set(seleccionadas.value);
  if (s.has(id)) s.delete(id);
  else s.add(id);
  seleccionadas.value = s;
}

function toggleSeleccionTodas() {
  const s = new Set(seleccionadas.value);
  if (todasSeleccionadas.value) {
    solicitudesPaginadas.value.forEach(x => s.delete(x.id));
  } else {
    solicitudesPaginadas.value.forEach(x => s.add(x.id));
  }
  seleccionadas.value = s;
}

async function aceptarLote() {
  const ids = [...seleccionadas.value];
  const lote = solicitudes.value.filter(s => ids.includes(s.id) && s.estado === 'pendiente');
  if (lote.length === 0) {
    notify.warning('No hay solicitudes pendientes para aceptar');
    return;
  }
  try {
    await ElMessageBox.confirm(`¿Aceptar ${lote.length} solicitud(es)?`, 'Confirmar', { confirmButtonText: 'Aceptar', cancelButtonText: 'Cancelar', type: 'success' });
    for (const s of lote) {
      await http.post(`/api/solicitudes-referencia/${s.id}/aceptar`, {
        fecha_respuesta: fechaActual(),
        hora_respuesta: horaActual(),
        nombre_quien_responde: authStore.user?.full_name ?? '',
        observaciones_respuesta: 'Aceptación masiva',
      });
    }
    notify.success(`${lote.length} solicitud(es) aceptada(s)`);
    seleccionadas.value = new Set();
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al aceptar en lote');
  }
}

async function negarLote() {
  const ids = [...seleccionadas.value];
  const lote = solicitudes.value.filter(s => ids.includes(s.id) && s.estado !== 'negado' && s.estado !== 'completado');
  if (lote.length === 0) {
    notify.warning('No hay solicitudes para negar');
    return;
  }
  try {
    await ElMessageBox.confirm(`¿Negar ${lote.length} solicitud(es)?`, 'Confirmar', { confirmButtonText: 'Negar', cancelButtonText: 'Cancelar', type: 'warning' });
    for (const s of lote) {
      await http.post(`/api/solicitudes-referencia/${s.id}/negar`, {
        fecha_respuesta: fechaActual(),
        hora_respuesta: horaActual(),
        motivo_negacion: 'Negación masiva',
        nombre_quien_responde: authStore.user?.full_name ?? '',
        observaciones_respuesta: '',
      });
    }
    notify.success(`${lote.length} solicitud(es) negada(s)`);
    seleccionadas.value = new Set();
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al negar en lote');
  }
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

function estadoLabel(estado: string) {
  return { pendiente: 'Pendiente', aceptado: 'Aceptado', en_espera: 'En espera', completado: 'Completado', negado: 'Negado' }[estado] ?? estado;
}

function formatFecha(fecha: string) {
  if (!fecha) return '—';
  const d = new Date(fecha.includes('T') ? fecha : fecha + 'T00:00:00');
  if (isNaN(d.getTime())) return fecha;
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
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
  if (!pdfBlobUrl.value) return null;
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
}

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/solicitudes-referencia');
    solicitudes.value = data.data;
    animateCounters([resumen.value.total, resumen.value.pendientes, resumen.value.aceptadas, resumen.value.negadas]);
  } catch {
    notify.error('Error al cargar las solicitudes');
  } finally {
    cargando.value = false;
  }
}

function verDetalle(s: Solicitud) {
  solicitudSeleccionada.value = s;
  adjuntoActivo.value = null;
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value);
    pdfBlobUrl.value = null;
  }
  modalHistoriaClinica.value = false;
  modalDetalle.value = true;
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

function abrirAceptar(s: Solicitud) {
  solicitudSeleccionada.value = s;
  formAceptar.value = {
    fecha_respuesta: fechaActual(),
    hora_respuesta: horaActual(),
    nombre_quien_responde: authStore.user?.full_name ?? '',
    observaciones_respuesta: '',
  };
  modalAceptar.value = true;
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
  if (!formAceptar.value.hora_respuesta || !formAceptar.value.nombre_quien_responde.trim()) {
    notify.warning('Complete los campos obligatorios');
    return;
  }
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${solicitudSeleccionada.value!.id}/aceptar`, formAceptar.value);
    notify.success('Solicitud aceptada correctamente');
    const idx = solicitudes.value.findIndex(s => s.id === solicitudSeleccionada.value!.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
    modalAceptar.value = false;
  } catch {
    notify.error('Error al aceptar la solicitud');
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
  } catch {
    notify.error('Error al negar la solicitud');
  } finally {
    procesando.value = false;
  }
}

async function marcarEnEspera(s: Solicitud) {
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${s.id}/en-espera`, {});
    notify.success('Solicitud marcada en espera de llegada del paciente');
    const idx = solicitudes.value.findIndex(x => x.id === s.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
  } catch {
    notify.error('Error al marcar en espera');
  } finally {
    procesando.value = false;
  }
}

async function marcarCompletado(s: Solicitud) {
  try {
    procesando.value = true;
    const { data } = await http.post(`/api/solicitudes-referencia/${s.id}/completado`, {});
    notify.success('Solicitud completada');
    const idx = solicitudes.value.findIndex(x => x.id === s.id);
    if (idx !== -1) solicitudes.value[idx] = data.data;
  } catch {
    notify.error('Error al completar la solicitud');
  } finally {
    procesando.value = false;
  }
}

onMounted(cargar);

let pollTimer: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
  pollTimer = setInterval(() => {
    if (!cargando.value && !modalDetalle.value && !modalAceptar.value && !modalNegar.value && !modalPdf.value && !modalHistoriaClinica.value) cargar();
  }, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>

<style scoped>
.ph-solicitudes {
  background: linear-gradient(160deg, #eef4fc 0%, #e3edf8 40%, #f0f5fa 100%);
}

/* ── Stat cards ── */
.sol-stats-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .5rem;
}
.sol-stat-card {
  display: flex;
  align-items: center;
  gap: .6rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: .65rem .8rem;
  position: relative;
  overflow: hidden;
  transition: transform .2s ease, box-shadow .2s ease;
}
.sol-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(13,45,107,.08);
}
.sol-stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--stat-color);
  opacity: .8;
}
.sol-stat-icon {
  width: 2.2rem; height: 2.2rem;
  border-radius: 10px;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.sol-stat-body { flex: 1; min-width: 0; }
.sol-stat-label {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin: 0;
}
.sol-stat-value {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1.1;
  margin: 0;
}
.sol-stat-bar-track {
  height: 3px;
  border-radius: 2px;
  background: #f1f5f9;
  margin-top: .25rem;
  overflow: hidden;
}
.sol-stat-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width .4s ease;
}
.sol-stat-delta {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 6px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Tab dots ── */
.sol-tab-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  display: inline-block;
  margin-right: .3rem;
}
.sol-tab-dot--pendiente { background: #f59e0b; }
.sol-tab-dot--aceptado { background: #22c55e; }
.sol-tab-dot--en_espera { background: #3b82f6; }
.sol-tab-dot--completado { background: #6366f1; }
.sol-tab-dot--negado { background: #ef4444; }

/* ── Bulk actions bar ── */
.sol-bulk-bar {
  display: flex;
  align-items: center;
  gap: .5rem;
  background: linear-gradient(135deg, #eef2f9, #e0e8f5);
  border: 1px solid #c4d4e8;
  border-radius: 10px;
  padding: .4rem .8rem;
}
.bulk-slide-enter-active, .bulk-slide-leave-active {
  transition: all .25s ease;
}
.bulk-slide-enter-from, .bulk-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Checkbox ── */
.sol-checkbox {
  width: 16px; height: 16px;
  border-radius: 4px;
  border: 1.5px solid #cbd5e1;
  cursor: pointer;
  accent-color: #16468E;
}
.sol-th-check { width: 36px; text-align: center; }
.sol-td-check { text-align: center; }

/* ── Sortable headers ── */
.sol-th-sortable {
  cursor: pointer;
  user-select: none;
  transition: color .15s ease;
}
.sol-th-sortable:hover { color: #16468E; }

/* ── Selected row ── */
.sol-table-row-selected {
  background: rgba(22,70,142,.04) !important;
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

/* ── Header ── */
.sol-header {
  display: flex; align-items: center; gap: .75rem;
  padding: .75rem 1rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 60%, #1e3a7a 100%);
  box-shadow: 0 6px 24px rgba(13, 45, 107, .25), inset 0 1px 0 rgba(255,255,255,0.08);
  position: relative; overflow: hidden;
}
.sol-header::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #2563eb, #60a5fa, #2563eb);
  background-size: 200% 100%;
  animation: solHeaderShimmer 3s linear infinite;
}
@keyframes solHeaderShimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.sol-header-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15);
  color: #fff; flex-shrink: 0;
}
.sol-header-title {
  font-size: 16px; font-weight: 800; color: #fff;
  letter-spacing: 0.01em; white-space: nowrap;
}
.sol-header-spacer { flex: 1; }

/* ── Filter bar ── */
.sol-filter-bar {
  display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
  padding: .65rem .9rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0f6ff 0%, #e6efff 50%, #f0f9ff 100%);
  border: 1px solid #b8c8e0;
  box-shadow: 0 3px 16px rgba(13, 45, 107, 0.07), inset 0 1px 0 rgba(255,255,255,0.6);
  position: relative; overflow: hidden;
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
}

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
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
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
.sol-table-row { transition: background .15s ease; }
.sol-table-row:hover { background: #f8fafc; }
.sol-table-row:last-child td { border-bottom: none; }
.sol-th-paciente { min-width: 220px; }
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
.sol-table-clinica { font-size: 10px; color: #3b82f6; margin: 1px 0 0; font-weight: 600; }
.sol-table-clinica-name { font-size: 12px; font-weight: 700; color: #1e40af; margin: 0; }
.sol-table-remitente { font-size: 10px; color: #94a3b8; margin: 2px 0 0; }
.sol-table-doc { font-size: 10px; color: #94a3b8; margin: 1px 0 0; }
.sol-table-doc-inline { font-size: 10px; color: #94a3b8; font-weight: 400; }
.sol-table-status {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 10px; font-weight: 700;
}
.sol-status-dot { width: 5px; height: 5px; border-radius: 50%; }
.sol-status-pending { background: #fef3c7; color: #d97706; }
.sol-status-pending .sol-status-dot { background: #fbbf24; }
.sol-status-accepted { background: #dcfce7; color: #15966a; }
.sol-status-accepted .sol-status-dot { background: #22c55e; }
.sol-status-waiting { background: #dbeafe; color: #1d4ed8; }
.sol-status-waiting .sol-status-dot { background: #3b82f6; }
.sol-status-completed { background: #e0e7ff; color: #4338ca; }
.sol-status-completed .sol-status-dot { background: #6366f1; }
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
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

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
:deep(.detalle-dialog) { border-radius: 22px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.4), 0 0 0 1px rgba(255,255,255,.08); }
:deep(.detalle-dialog .el-dialog__header) { position: absolute; top: 0; right: 0; z-index: 30; padding: 0; margin: 0; background: transparent; border: none; }
:deep(.detalle-dialog .el-dialog__title) { display: none; }
:deep(.detalle-dialog .el-dialog__body) { padding: 0; }
:deep(.detalle-dialog .el-dialog__headerbtn) { position: relative; top: auto; right: auto; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }
:deep(.detalle-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.4rem; font-weight: 700; }
:deep(.detalle-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }
:deep(.el-overlay) { background-color: rgba(8,27,58,.56); backdrop-filter: blur(4px); }

.detalle-content { overflow: hidden; }

/* Header */
.detalle-head {
  position: relative;
  background: linear-gradient(135deg, #0a1f4d 0%, #0D2D6B 48%, #16468E 100%);
  padding: 22px 24px;
  display: flex; align-items: center; gap: 14px;
  overflow: hidden;
}
.detalle-head-glow {
  position: absolute; top: -30px; right: -30px;
  width: 120px; height: 120px; border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.2), transparent 70%);
  pointer-events: none;
}
.detalle-head-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; z-index: 10;
  background: rgba(255,255,255,0.12); color: #fff;
  border: 1px solid rgba(255,255,255,0.15);
}
.detalle-head-info { flex: 1; z-index: 10; }
.detalle-head-title { margin: 0; color: #fff; font-size: 17px; font-weight: 800; }
.detalle-head-sub {
  margin: 2px 0 0; color: rgba(255,255,255,0.55); font-size: 11px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.detalle-head-id-badge {
  flex-shrink: 0; z-index: 10;
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px; font-weight: 800;
  color: #fff;
  letter-spacing: 0.03em;
  font-family: monospace;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.detalle-head-badge {
  padding: 5px 14px; border-radius: 999px;
  font-size: 11px; font-weight: 700; flex-shrink: 0; z-index: 10;
}
.badge-pendiente { background: #f59e0b; color: #fff; }
.badge-aceptado { background: #22c55e; color: #fff; }
.badge-en_espera { background: #3b82f6; color: #fff; }
.badge-completado { background: #6366f1; color: #fff; }
.badge-negado { background: #ef4444; color: #fff; }

/* Body */
.detalle-body { padding: 20px 24px; background: #f1f5f9; }
.detalle-cards-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }

/* Card base */
.detalle-card {
  min-width: 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
  border-left: 4px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(22,70,142,.05);
}
.detalle-card-blue { border-left-color: #3b82f6; }
.detalle-card-amber { border-left-color: #f59e0b; }
.detalle-card-purple { border-left-color: #8b5cf6; }
.detalle-card-cyan { border-left-color: #06b6d4; }
.detalle-card-red { border-left-color: #ef4444; background: #fef2f2; border-color: #fecaca; }

.detalle-card-header {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 800; color: #1e2d55;
  letter-spacing: .04em; margin-bottom: 10px;
}
.detalle-card-header svg { color: #3b82f6; }
.detalle-card-blue .detalle-card-header svg { color: #3b82f6; }
.detalle-card-amber .detalle-card-header svg { color: #f59e0b; }
.detalle-card-purple .detalle-card-header svg { color: #8b5cf6; }
.detalle-card-cyan .detalle-card-header svg { color: #06b6d4; }
.detalle-card-header-red svg { color: #ef4444; }
.detalle-card-header-red { color: #dc2626; }

.detalle-card-rows { display: flex; flex-direction: column; gap: 6px; }
.detalle-card-row {
  display: flex; align-items: baseline; justify-content: space-between; gap: 8px;
}
.detalle-row-label { font-size: 11px; color: #94a3b8; flex-shrink: 0; }
.detalle-row-value {
  min-width: 0;
  font-size: 12px; font-weight: 700; color: #1e293b;
  text-align: right; overflow-wrap: break-word;
}
.detalle-card-text {
  font-size: 12px; color: #475569; line-height: 1.5; margin: 0;
  word-break: break-word; white-space: normal;
}
.detalle-dx-list { display: flex; flex-direction: column; gap: 6px; }
.detalle-dx-item {
  display: flex; align-items: baseline; gap: 8px;
  padding: 6px 10px; border-radius: 8px;
  background: #f5f3ff;
}
.detalle-dx-code {
  font-size: 12px; font-weight: 800; color: #7c3aed;
  font-family: monospace; flex-shrink: 0;
}
.detalle-dx-desc { font-size: 12px; color: #334155; }
.detalle-historia-preview {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 4;
  overflow: hidden;
}
.detalle-historia-button {
  margin-top: .55rem;
  padding: 0;
  color: #0284c7;
  font-size: 11px;
  font-weight: 700;
  background: transparent;
  border: 0;
  cursor: pointer;
}
.detalle-historia-button:hover { color: #0369a1; text-decoration: underline; }

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

.detalle-code-bar {
  display: flex; align-items: center; gap: .5rem;
  background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0;
  border-radius: 12px; padding: .55rem .8rem;
}
.detalle-code-value { margin-left: auto; font-family: monospace; font-size: .9rem; font-weight: 800; color: #166534; background: #fff; padding: .2rem .6rem; border-radius: 6px; border: 1px solid #86efac; }

.detalle-actions-bar {
  display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center;
  padding: 1rem 1.25rem; border-top: 1px solid #e2e8f0;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.detalle-footer-actions {
  display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center;
}

.detalle-adjuntos-list { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; }
.detalle-adjunto-item {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px; border-radius: 8px;
  background: #f8fafc; border: 1px solid #e2e8f0;
  text-decoration: none; transition: all .2s;
}
.detalle-adjunto-item:hover { background: #eff6ff; border-color: #bfdbfe; }
.detalle-adjunto-name { flex: 1; font-size: 12px; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.detalle-adjunto-size { font-size: 11px; color: #94a3b8; flex-shrink: 0; }

/* ── Modal Acción (Aceptar/Negar) ── */
:deep(.accion-dialog) { border-radius: 22px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.4); }
:deep(.accion-dialog .el-dialog__header) { padding: 0; margin: 0; }
:deep(.accion-dialog .el-dialog__body) { padding: 0; }
:deep(.accion-dialog .el-dialog__headerbtn) { z-index: 10; top: 14px; right: 14px; }
:deep(.accion-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.1rem; }
:deep(.accion-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }
:deep(.accion-dialog .el-input__wrapper),
:deep(.accion-dialog .el-select__wrapper) { box-shadow: 0 0 0 1px #dce7f2 inset; border-radius: 10px; background: #fff; }
:deep(.accion-dialog .el-input__wrapper:hover),
:deep(.accion-dialog .el-select__wrapper:hover) { box-shadow: 0 0 0 1px #86b4e8 inset; }

.accion-head {
  position: relative; background: linear-gradient(125deg, #0d2d6b 0%, #16468e 50%, #1a3d8a 100%);
  padding: 1.1rem 1.3rem; display: flex; align-items: center; gap: .8rem; overflow: hidden;
}
.accion-head-glow { position: absolute; top: -40px; right: -30px; width: 140px; height: 140px; border-radius: 50%; background: rgba(255,255,255,.06); }
.accion-head-icon {
  width: 2.4rem; height: 2.4rem; border-radius: 12px;
  display: grid; place-items: center; flex-shrink: 0; z-index: 1;
  border: 1px solid rgba(255,255,255,.2);
}
.accion-head-info { flex: 1; z-index: 1; }
.accion-head-title { margin: 0; color: #fff; font-size: .9rem; font-weight: 800; }
.accion-head-sub { margin: .1rem 0 0; color: rgba(255,255,255,.55); font-size: .65rem; }
</style>
