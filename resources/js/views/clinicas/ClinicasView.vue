<template>
  <div class="h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden clinic-page">

    <!-- ── Header ── -->
    <div class="clinic-header shrink-0"
      v-motion
      :initial="{ opacity: 0, y: 20 }"
      :enter="{ opacity: 1, y: 0, transition: { duration: 500, ease: 'easeOut' } }">
      <div class="clinic-header-icon">
        <component :is="BuildingIcon" class="w-5 h-5" />
      </div>
      <h1 class="clinic-header-title">Clínicas registradas</h1>
      <div class="clinic-header-spacer"></div>
      <el-button type="primary" size="small" @click="cargar">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" />
        Actualizar
      </el-button>
    </div>

    <!-- ── Tabs + Búsqueda ── -->
    <div class="clinic-filter-bar shrink-0">
      <div class="clinic-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="clinic-tab"
        :class="{ 'clinic-tab-active': tabActiva === tab.value }"
        @click="tabActiva = tab.value"
      >
        {{ tab.label }}
        <span class="clinic-tab-count">{{ tab.count }}</span>
      </button>
      </div>
      <div class="clinic-filter-divider"></div>
      <el-input
        v-model="filtroBuscar"
        placeholder="Buscar por NIT, ciudad, nombre..."
        class="clinic-search"
        clearable
        size="small"
      >
        <template #prefix>
          <component :is="SearchIcon" class="w-3.5 h-3.5 text-gray-400" />
        </template>
      </el-input>
      <el-button v-if="filtroBuscar || tabActiva !== 'todas'" type="danger" size="small" round @click="limpiarFiltros">
        <component :is="XIcon" class="w-3 h-3 mr-1" />
        Borrar filtros
      </el-button>
    </div>

    <!-- ── Tabla ── -->
    <div class="flex-1 overflow-hidden clinic-table-panel">
      <!-- Loading -->
      <div v-if="cargando" class="clinic-table-loading">
        <div v-for="i in 5" :key="i" class="clinic-table-row-skeleton">
          <div class="shimmer-box" style="width:32px; height:32px; border-radius:8px; flex-shrink:0;"></div>
          <div class="flex-1 space-y-1">
            <div class="shimmer-bar" style="width:30%; height:12px;"></div>
            <div class="shimmer-bar" style="width:20%; height:9px;"></div>
          </div>
          <div class="shimmer-bar" style="width:15%; height:11px;"></div>
          <div class="shimmer-box" style="width:60px; height:22px; border-radius:999px;"></div>
          <div class="shimmer-box" style="width:80px; height:26px; border-radius:6px; flex-shrink:0;"></div>
        </div>
      </div>

      <!-- Vacío -->
      <div v-else-if="clinicasFiltradas.length === 0" class="clinic-empty-wrap">
        <div class="clinic-empty-glow"></div>
        <div class="clinic-empty-icon w-16 h-16 rounded-2xl flex items-center justify-center mb-3 relative z-10">
          <component :is="BuildingIcon" class="w-8 h-8" />
        </div>
        <p class="font-bold text-base mb-1 relative z-10" style="color:#0d2d5e;">Sin clínicas</p>
        <p class="text-xs max-w-[280px] relative z-10" style="color:#64748b;">No hay instituciones en esta categoría.</p>
      </div>

      <!-- Tabla real -->
      <div v-else class="flex flex-col h-full overflow-hidden">
        <div class="overflow-y-auto overflow-x-auto custom-scrollbar flex-1">
          <table class="clinic-table">
            <thead class="clinic-table-thead">
              <tr>
                <th class="clinic-table-th clinic-table-th-clinica">Clínica</th>
                <th class="clinic-table-th">NIT</th>
                <th class="clinic-table-th">Ciudad</th>
                <th class="clinic-table-th">Estado</th>
                <th class="clinic-table-th clinic-table-th-actions">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(clinica, idx) in clinicasFiltradas"
                :key="clinica.id"
                class="clinic-table-row anim-card-in"
                :style="{ animationDelay: (idx * 0.02) + 's' }"
              >
                <td class="clinic-table-td">
                  <div class="clinic-table-clinica">
                    <div class="clinic-table-avatar" :style="avatarStyle(clinica.estado)">
                      <component :is="BuildingIcon" class="w-3.5 h-3.5" />
                    </div>
                    <div>
                      <p class="clinic-table-name">{{ clinica.nombre }}</p>
                      <p class="clinic-table-email">{{ clinica.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="clinic-table-td font-mono">{{ clinica.nit }}</td>
                <td class="clinic-table-td">{{ clinica.ciudad }}</td>
                <td class="clinic-table-td">
                  <span class="clinic-table-status" :class="{
                    'clinic-status-pending': clinica.estado === 'pendiente',
                    'clinic-status-active': clinica.estado === 'activa',
                    'clinic-status-rejected': clinica.estado === 'rechazada',
                  }">
                    <span class="clinic-status-dot"></span>
                    {{ estadoLabel(clinica.estado) }}
                  </span>
                </td>
                <td class="clinic-table-td">
                  <div class="clinic-table-actions">
                    <el-button
                      v-if="clinica.estado !== 'activa'"
                      type="success"
                      size="small"
                      :loading="procesando === clinica.id + '_aprobar'"
                      @click="aprobar(clinica)"
                    >
                      <component :is="CheckIcon" class="w-3 h-3 mr-0.5" />
                      {{ clinica.estado === 'rechazada' ? 'Reactivar' : 'Aprobar' }}
                    </el-button>
                    <el-button
                      v-if="clinica.estado !== 'rechazada'"
                      type="danger"
                      size="small"
                      :loading="procesando === clinica.id + '_rechazar'"
                      @click="abrirRechazo(clinica)"
                    >
                      <component :is="XIcon" class="w-3 h-3 mr-0.5" />
                      Rechazar
                    </el-button>
                    <el-button size="small" @click="verDetalle(clinica)">
                      <component :is="EyeIcon" class="w-3 h-3 mr-0.5" />
                      Ver
                    </el-button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal: Detalle -->
    <BaseModal v-model="modalDetalle" width="620px" class="detalle-clinica-dialog" :show-close="true" align-center :title="'Detalle de clínica'" :subtitle="clinicaSeleccionada ? clinicaSeleccionada.nit + ' · ' + clinicaSeleccionada.nombre : ''">
      <template v-if="clinicaSeleccionada">
        <div class="detalle-clinica-content">
          <!-- Header azul -->
          <div class="detalle-head">
            <div class="detalle-head-glow"></div>
            <div class="detalle-head-icon">
              <component :is="clinicaSeleccionada.estado === 'activa' ? ShieldCheck : clinicaSeleccionada.estado === 'rechazada' ? AlertTriangle : ClockIcon" class="w-6 h-6" />
            </div>
            <div class="z-10 flex-1 min-w-0">
              <p class="detalle-head-title">Detalle de clínica</p>
              <p class="detalle-head-sub">{{ clinicaSeleccionada.nit }} · {{ clinicaSeleccionada.nombre }}</p>
            </div>
            <span class="detalle-head-badge" :class="{
              'detalle-badge-pending': clinicaSeleccionada.estado === 'pendiente',
              'detalle-badge-active': clinicaSeleccionada.estado === 'activa',
              'detalle-badge-rejected': clinicaSeleccionada.estado === 'rechazada',
            }">
              {{ estadoLabel(clinicaSeleccionada.estado) }}
            </span>
          </div>

          <!-- Body con cards -->
          <div class="detalle-body">
            <div class="detalle-cards-grid">
              <!-- Card: Información General -->
              <div class="detalle-card detalle-card-blue">
                <div class="detalle-card-header">
                  <component :is="BuildingIcon" class="w-4 h-4" />
                  <span>INFORMACIÓN GENERAL</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Nombre</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.nombre }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">NIT</span>
                    <span class="detalle-card-row-value font-mono">{{ clinicaSeleccionada.nit }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Razón social</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.razon_social || '—' }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Fecha</span>
                    <span class="detalle-card-row-value">{{ formatFecha(clinicaSeleccionada.created_at) }}</span>
                  </div>
                </div>
              </div>

              <!-- Card: Ubicación -->
              <div class="detalle-card detalle-card-green">
                <div class="detalle-card-header">
                  <component :is="HospitalIcon" class="w-4 h-4" />
                  <span>UBICACIÓN</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Ciudad</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.ciudad }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Departamento</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.departamento }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Dirección</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.direccion }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Teléfono</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.telefono }}</span>
                  </div>
                </div>
              </div>

              <!-- Card: Representante -->
              <div class="detalle-card detalle-card-purple">
                <div class="detalle-card-header">
                  <component :is="ShieldCheck" class="w-4 h-4" />
                  <span>REPRESENTANTE LEGAL</span>
                </div>
                <div class="detalle-card-rows">
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Nombre</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.representante_legal }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Cédula</span>
                    <span class="detalle-card-row-value font-mono">{{ clinicaSeleccionada.cedula_representante }}</span>
                  </div>
                  <div class="detalle-card-row">
                    <span class="detalle-card-row-label">Correo</span>
                    <span class="detalle-card-row-value">{{ clinicaSeleccionada.email }}</span>
                  </div>
                </div>
              </div>

              <!-- Card: Especialidades -->
              <div class="detalle-card detalle-card-amber">
                <div class="detalle-card-header">
                  <component :is="HospitalIcon" class="w-4 h-4" />
                  <span>ESPECIALIDADES</span>
                </div>
                <div class="detalle-card-rows">
                  <div v-if="clinicaSeleccionada.especialidades && clinicaSeleccionada.especialidades.length" class="flex flex-wrap gap-1.5 pt-1">
                    <span v-for="esp in clinicaSeleccionada.especialidades" :key="esp" class="esp-tag esp-tag-detail">{{ esp }}</span>
                  </div>
                  <p v-else class="text-xs text-gray-400 italic">Sin especialidades registradas</p>
                </div>
              </div>
            </div>

            <!-- Observaciones -->
            <div v-if="clinicaSeleccionada.observaciones" class="detalle-card detalle-card-blue mt-3">
              <div class="detalle-card-header">
                <component :is="EyeIcon" class="w-4 h-4" />
                <span>OBSERVACIONES</span>
              </div>
              <p class="text-xs text-gray-600 leading-relaxed mt-2">{{ clinicaSeleccionada.observaciones }}</p>
            </div>

            <!-- Motivo rechazo -->
            <div v-if="clinicaSeleccionada.motivo_rechazo" class="detalle-card detalle-card-red mt-3">
              <div class="detalle-card-header detalle-card-header-red">
                <component :is="AlertTriangle" class="w-4 h-4" />
                <span>MOTIVO DE RECHAZO</span>
              </div>
              <p class="text-xs text-red-600 leading-relaxed mt-2">{{ clinicaSeleccionada.motivo_rechazo }}</p>
            </div>
          </div>
        </div>
      </template>
    </BaseModal>

    <!-- Modal: Motivo de rechazo -->
    <el-dialog v-model="modalRechazo" title="Rechazar clínica" width="420px" :close-on-click-modal="false" class="rounded-2xl">
      <div class="mb-2">
        <p class="text-sm text-gray-600 mb-1">
          Indique el motivo de rechazo para <strong>{{ clinicaSeleccionada?.nombre }}</strong>:
        </p>
        <el-input
          v-model="motivoRechazo"
          type="textarea"
          :rows="3"
          placeholder="Ej: La institución no cuenta con convenio vigente con la Clínica Santa Bárbara..."
        />
      </div>
      <template #footer>
        <el-button @click="modalRechazo = false">Cancelar</el-button>
        <el-button type="danger" :loading="procesando !== null" @click="rechazar">
          Confirmar rechazo
        </el-button>
      </template>
    </el-dialog>

  </div>
</template>

<style scoped>
.clinic-page {
  background: linear-gradient(160deg, #eef4fc 0%, #e3edf8 40%, #f0f5fa 100%);
}

/* ── Header ── */
.clinic-header {
  display: flex; align-items: center; gap: .75rem;
  padding: .75rem 1rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 60%, #1e3a7a 100%);
  box-shadow: 0 6px 24px rgba(13, 45, 107, .25), inset 0 1px 0 rgba(255,255,255,0.08);
  position: relative; overflow: hidden;
}
.clinic-header::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #2563eb, #60a5fa, #2563eb);
  background-size: 200% 100%;
  animation: clinicHeaderShimmer 3s linear infinite;
}
@keyframes clinicHeaderShimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
.clinic-header-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15);
  color: #fff; flex-shrink: 0;
}
.clinic-header-title {
  font-size: 16px; font-weight: 800; color: #fff;
  letter-spacing: 0.01em; white-space: nowrap;
}
.clinic-header-spacer { flex: 1; }

/* ── Filter bar ── */
.clinic-filter-bar {
  display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
  padding: .65rem .9rem;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0f6ff 0%, #e6efff 50%, #f0f9ff 100%);
  border: 1px solid #b8c8e0;
  box-shadow: 0 3px 16px rgba(13, 45, 107, 0.07), inset 0 1px 0 rgba(255,255,255,0.6);
  transition: box-shadow .3s ease, transform .3s ease;
}
.clinic-filter-bar:hover {
  box-shadow: 0 5px 24px rgba(13, 45, 107, 0.11), inset 0 1px 0 rgba(255,255,255,0.6);
  transform: translateY(-1px);
}
.clinic-filter-divider {
  width: 1px; height: 24px;
  background: linear-gradient(180deg, transparent, #b8c8e0, transparent);
  flex-shrink: 0;
}

/* ── Search ── */
.clinic-search { flex: 1; min-width: 200px; max-width: 400px; }
.clinic-filter-bar :deep(.el-input__wrapper) {
  background: rgba(255,255,255,0.75) !important;
  border: 1px solid #d4deea !important;
  border-radius: 10px !important;
  transition: all .2s ease;
}
.clinic-filter-bar :deep(.el-input__wrapper:hover) {
  border-color: #16468E !important;
  box-shadow: 0 0 0 2px rgba(22,70,142,0.08) !important;
}

/* ── Tabs ── */
.clinic-tabs {
  display: flex; gap: 4px;
  padding: 3px;
  background: rgba(255,255,255,0.7);
  border: 1px solid #d4deea;
  border-radius: 10px;
  width: fit-content;
}
.clinic-tab {
  display: flex; align-items: center; gap: 6px;
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 12px; font-weight: 600; color: #64748b;
  background: transparent;
  border: none; cursor: pointer;
  transition: all .2s ease;
}
.clinic-tab:hover { color: #1e2d55; background: rgba(13,45,107,.06); transform: translateY(-1px); }
.clinic-tab-active {
  background: linear-gradient(135deg, #0D2D6B, #16468E);
  color: #fff;
  box-shadow: 0 2px 10px rgba(13,45,107,.28);
}
.clinic-tab-count {
  padding: 1px 6px; border-radius: 999px;
  font-size: 9px; font-weight: 700;
  background: rgba(13,45,107,.08); color: #1e2d55;
}
.clinic-tab-active .clinic-tab-count { background: rgba(255,255,255,.2); color: #fff; }

/* ── Table panel ── */
.clinic-table-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(22,70,142,.08);
  padding: 4px;
}

/* ── Table loading ── */
.clinic-table-loading {
  display: flex; flex-direction: column; gap: 6px;
  padding: 4px;
}
.clinic-table-row-skeleton {
  display: flex; align-items: center; gap: 12px;
  padding: 10px 12px; border-radius: 8px;
  background: rgba(255,255,255,0.8);
  border: 1px solid #e2e8f0;
}

/* ── Table ── */
.clinic-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.clinic-table th {
  padding: 8px 12px;
  text-align: left;
  font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.clinic-table th:first-child { border-radius: 8px 0 0 8px; }
.clinic-table th:last-child { border-radius: 0 8px 8px 0; }
.clinic-table td {
  padding: 9px 12px;
  font-size: 12px; color: #334155;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
  white-space: nowrap;
}
.clinic-table-row { transition: background .15s ease; }
.clinic-table-row:hover { background: #f8fafc; }
.clinic-table-row:last-child td { border-bottom: none; }
.clinic-table-th-clinica { min-width: 200px; }
.clinic-table-th-actions { text-align: right; }
.clinic-table-thead th { position: sticky; top: 0; z-index: 10; }

.clinic-table-clinica {
  display: flex; align-items: center; gap: 10px;
}
.clinic-table-avatar {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.clinic-table-name {
  font-size: 13px; font-weight: 700; color: #1e2d55;
  margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 180px;
}
.clinic-table-email {
  font-size: 10px; color: #94a3b8; margin: 1px 0 0;
}
.clinic-table-status {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 10px; font-weight: 700;
}
.clinic-status-dot { width: 5px; height: 5px; border-radius: 50%; }
.clinic-status-pending { background: #fef3c7; color: #d97706; }
.clinic-status-pending .clinic-status-dot { background: #fbbf24; }
.clinic-status-active { background: #dcfce7; color: #15966a; }
.clinic-status-active .clinic-status-dot { background: #22c55e; }
.clinic-status-rejected { background: #fee2e2; color: #dc2626; }
.clinic-status-rejected .clinic-status-dot { background: #ef4444; }
.clinic-table-actions { display: flex; justify-content: flex-end; gap: 4px; }

/* ── Modal detalle ── */
:deep(.detalle-clinica-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.detalle-clinica-dialog .el-dialog__header) { position: absolute; top: 0; right: 0; z-index: 30; padding: 0; margin: 0; background: transparent; border: none; }
:deep(.detalle-clinica-dialog .el-dialog__title) { display: none; }
:deep(.detalle-clinica-dialog .el-dialog__body) { padding: 0; }
:deep(.detalle-clinica-dialog .el-dialog__headerbtn) { position: absolute; top: 14px; right: 14px; z-index: 40; }
:deep(.detalle-clinica-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.5rem; font-weight: 700; }
:deep(.detalle-clinica-dialog .el-dialog__headerbtn:hover .el-dialog__close) { color: #e1f7ff; }
.detalle-clinica-content { overflow: hidden; }

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
.detalle-head-title {
  margin: 0; color: #fff; font-size: 17px; font-weight: 800;
}
.detalle-head-sub {
  margin: 2px 0 0; color: rgba(255,255,255,0.55); font-size: 11px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.detalle-head-badge {
  padding: 5px 14px; border-radius: 999px;
  font-size: 11px; font-weight: 700; flex-shrink: 0; z-index: 10;
  white-space: nowrap;
}
.detalle-badge-pending { background: #f59e0b; color: #fff; }
.detalle-badge-active { background: #22c55e; color: #fff; }
.detalle-badge-rejected { background: #ef4444; color: #fff; }

/* Body */
.detalle-body { padding: 20px 24px; background: #f1f5f9; }
.detalle-cards-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
}

/* Card base */
.detalle-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
  border-left: 4px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(22,70,142,.05);
}
.detalle-card-blue { border-left-color: #3b82f6; }
.detalle-card-green { border-left-color: #22c55e; }
.detalle-card-purple { border-left-color: #8b5cf6; }
.detalle-card-amber { border-left-color: #f59e0b; }
.detalle-card-red { border-left-color: #ef4444; background: #fef2f2; border-color: #fecaca; }

.detalle-card-header {
  display: flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 800; color: #1e2d55;
  letter-spacing: .04em; margin-bottom: 10px;
}
.detalle-card-header svg { color: #3b82f6; }
.detalle-card-blue .detalle-card-header svg { color: #3b82f6; }
.detalle-card-green .detalle-card-header svg { color: #22c55e; }
.detalle-card-purple .detalle-card-header svg { color: #8b5cf6; }
.detalle-card-amber .detalle-card-header svg { color: #f59e0b; }
.detalle-card-header-red svg { color: #ef4444; }
.detalle-card-header-red { color: #dc2626; }

.detalle-card-rows { display: flex; flex-direction: column; gap: 6px; }
.detalle-card-row {
  display: flex; align-items: baseline; justify-content: space-between; gap: 8px;
}
.detalle-card-row-label {
  font-size: 11px; color: #94a3b8; flex-shrink: 0;
}
.detalle-card-row-value {
  font-size: 12px; font-weight: 700; color: #1e293b;
  text-align: right; word-break: break-word;
}

/* ── Esp tags ── */
.esp-tag {
  font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 999px;
  background: #e7efff; color: #2563c4; border: 1px solid rgba(37,99,196,.12);
}
.esp-tag-more { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
.esp-tag-detail { font-size: 11px; padding: 4px 12px; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

/* ── Animaciones ── */
.anim-slide-up { animation: slideUp .5s cubic-bezier(.22,1,.36,1) both; }
@keyframes slideUp { from { opacity:0; transform: translateY(14px); } to { opacity:1; transform:none; } }
.anim-card-in { animation: cardIn 0.5s cubic-bezier(.22,1,.36,1) both; }
@keyframes cardIn {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: none; }
}

/* ── Shimmer ── */
.shimmer-box, .shimmer-bar {
  position: relative;
  overflow: hidden;
  background: #e6ebf3;
}
.shimmer-box { border-radius: 6px; }
.shimmer-bar { border-radius: 4px; }
.shimmer-box::after, .shimmer-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.6) 50%, transparent 100%);
  animation: shimmer 1.8s ease-in-out infinite;
}
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* ── Empty state ── */
.clinic-empty-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem 1rem;
  position: relative;
  background: #fff;
  border: 1px dashed #d4deea;
  border-radius: 12px;
  overflow: hidden;
  height: 100%;
}
.clinic-empty-glow {
  position: absolute; top: -40px; left: 50%;
  transform: translateX(-50%);
  width: 200px; height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.1), transparent 70%);
  pointer-events: none;
}
.clinic-empty-icon {
  color: #2f70bb;
  background: linear-gradient(135deg, #e4f0ff, #dbeafe);
  box-shadow: 0 8px 20px rgba(47, 112, 187, .15);
}
.clinic-empty-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .5rem 1rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #16468e, #0d2d6b);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: transform .2s ease, box-shadow .2s ease;
  box-shadow: 0 4px 12px rgba(13, 45, 107, .25);
}
.clinic-empty-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(13, 45, 107, .35);
}

</style>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import {
  RefreshCw as RefreshIcon,
  Check as CheckIcon,
  X as XIcon,
  Eye as EyeIcon,
  Building2 as BuildingIcon,
  Clock as ClockIcon,
  Hospital as HospitalIcon,
  ShieldCheck,
  AlertTriangle,
  Search as SearchIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';

interface Clinica {
  id: number;
  nit: string;
  nombre: string;
  razon_social: string;
  email: string;
  telefono: string;
  ciudad: string;
  departamento: string;
  direccion: string;
  representante_legal: string;
  cedula_representante: string;
  observaciones?: string;
  especialidades?: string[];
  estado: 'pendiente' | 'activa' | 'rechazada';
  motivo_rechazo?: string;
  created_at: string;
}

const clinicas = ref<Clinica[]>([]);
const cargando = ref(false);
const procesando = ref<string | null>(null);
const modalRechazo = ref(false);
const modalDetalle = ref(false);
const clinicaSeleccionada = ref<Clinica | null>(null);
const tabActiva = ref<'todas' | 'pendiente' | 'activa' | 'rechazada'>('todas');
const motivoRechazo = ref('');
const filtroBuscar = ref('');

function limpiarFiltros() {
  filtroBuscar.value = '';
  tabActiva.value = 'todas';
}

const resumen = computed(() => ({
  pendientes: clinicas.value.filter(c => c.estado === 'pendiente').length,
  activas: clinicas.value.filter(c => c.estado === 'activa').length,
  rechazadas: clinicas.value.filter(c => c.estado === 'rechazada').length,
}));

const tabs = computed(() => [
  { label: 'Todas', value: 'todas', count: clinicas.value.length },
  { label: 'Pendientes', value: 'pendiente', count: resumen.value.pendientes },
  { label: 'Activas', value: 'activa', count: resumen.value.activas },
  { label: 'Rechazadas', value: 'rechazada', count: resumen.value.rechazadas },
]);

const statCards = computed(() => {
  const total = clinicas.value.length || 1;
  return [
    {
      label: 'Total clínicas', value: String(clinicas.value.length),
      icon: HospitalIcon, color: '#2563c4', iconBg: '#e7efff',
      delta: 'Registradas', deltaBg: '#e7efff', deltaColor: '#2563c4',
      percent: 100,
    },
    {
      label: 'Pendientes', value: String(resumen.value.pendientes),
      icon: ClockIcon, color: '#e67700', iconBg: '#fff3cd',
      delta: 'En revisión', deltaBg: '#fff3cd', deltaColor: '#e67700',
      percent: Math.round((resumen.value.pendientes / total) * 100),
    },
    {
      label: 'Activas', value: String(resumen.value.activas),
      icon: ShieldIcon, color: '#15966a', iconBg: '#dcfce7',
      delta: 'Aprobadas', deltaBg: '#dcfce7', deltaColor: '#15966a',
      percent: Math.round((resumen.value.activas / total) * 100),
    },
    {
      label: 'Rechazadas', value: String(resumen.value.rechazadas),
      icon: AlertIcon, color: '#dc2626', iconBg: '#fee2e2',
      delta: 'Rechazadas', deltaBg: '#fee2e2', deltaColor: '#dc2626',
      percent: Math.round((resumen.value.rechazadas / total) * 100),
    },
  ];
});

function estadoLabel(estado: string) {
  return estado === 'pendiente' ? 'Pendiente' : estado === 'activa' ? 'Activa' : 'Rechazada';
}

function estadoColor(estado: string) {
  if (estado === 'pendiente') return { bg: 'rgba(245,158,11,0.2)', color: '#fbbf24' };
  if (estado === 'activa') return { bg: 'rgba(34,197,94,0.2)', color: '#22c55e' };
  return { bg: 'rgba(239,68,68,0.2)', color: '#ef4444' };
}

function avatarStyle(estado: string) {
  if (estado === 'pendiente') return { background: 'linear-gradient(135deg, #fef3c7, #fde68a)', color: '#d97706', border: '1px solid #fde68a' };
  if (estado === 'activa') return { background: 'linear-gradient(135deg, #dcfce7, #bbf7d0)', color: '#15966a', border: '1px solid #bbf7d0' };
  return { background: 'linear-gradient(135deg, #fee2e2, #fecaca)', color: '#dc2626', border: '1px solid #fecaca' };
}

const clinicasFiltradas = computed(() => {
  let result = clinicas.value;
  if (tabActiva.value !== 'todas') {
    result = result.filter(c => c.estado === tabActiva.value);
  }
  if (filtroBuscar.value.trim()) {
    const q = filtroBuscar.value.toLowerCase().trim();
    result = result.filter(c =>
      c.nit.toLowerCase().includes(q) ||
      c.ciudad.toLowerCase().includes(q) ||
      c.nombre.toLowerCase().includes(q) ||
      c.estado.toLowerCase().includes(q)
    );
  }
  return result;
});

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/clinicas');
    clinicas.value = data.data;
  } catch {
    ElMessage.error('Error al cargar las clínicas');
  } finally {
    cargando.value = false;
  }
}

async function aprobar(clinica: Clinica) {
  const esReactivar = clinica.estado === 'rechazada';
  try {
    await ElMessageBox.confirm(
      esReactivar
        ? `¿Reactivar la clínica ${clinica.nombre}?`
        : `¿Aprobar y activar la clínica ${clinica.nombre}? Se le notificará por correo.`,
      esReactivar ? 'Confirmar reactivación' : 'Confirmar aprobación',
      { confirmButtonText: esReactivar ? 'Reactivar' : 'Aprobar', cancelButtonText: 'Cancelar', type: 'success' }
    );
    procesando.value = clinica.id + '_aprobar';
    const endpoint = esReactivar ? 'reactivar' : 'aprobar';
    await http.post(`/api/clinicas/${clinica.id}/${endpoint}`);
    ElMessage.success(esReactivar ? 'Clínica reactivada correctamente' : 'Clínica aprobada correctamente');
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') ElMessage.error('Error al procesar la clínica');
  } finally {
    procesando.value = null;
  }
}

function verDetalle(clinica: Clinica) {
  clinicaSeleccionada.value = clinica;
  modalDetalle.value = true;
}

function abrirRechazo(clinica: Clinica) {
  clinicaSeleccionada.value = clinica;
  motivoRechazo.value = '';
  modalDetalle.value = false;
  modalRechazo.value = true;
}

async function rechazar() {
  if (!motivoRechazo.value.trim()) {
    ElMessage.warning('Ingrese el motivo de rechazo');
    return;
  }
  try {
    procesando.value = clinicaSeleccionada.value!.id + '_rechazar';
    await http.post(`/api/clinicas/${clinicaSeleccionada.value!.id}/rechazar`, {
      motivo: motivoRechazo.value,
    });
    ElMessage.success('Clínica rechazada');
    modalRechazo.value = false;
    await cargar();
  } catch {
    ElMessage.error('Error al rechazar la clínica');
  } finally {
    procesando.value = null;
  }
}

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  cargar();
  pollTimer = setInterval(() => {
    if (!cargando.value && !modalRechazo.value && !modalDetalle.value) cargar();
  }, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>
