<template>
  <div class="h-full flex flex-col gap-2 p-3 sm:p-4 overflow-hidden clinic-page">

    <!-- ── Header ── -->
    <div class="clinic-header shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="clinic-header-icon">
        <component :is="BuildingIcon" class="w-5 h-5" />
      </div>
      <h1 class="clinic-header-title">Clínicas registradas</h1>
      <div class="clinic-header-spacer"></div>
      <el-button type="primary" size="small" @click="cargar">
        <component :is="RefreshIcon" class="w-3.5 h-3.5 mr-1" />
        Actualizar
      </el-button>
      <el-button type="success" size="small" @click="abrirCargaMasiva">
        <component :is="UploadIcon" class="w-3.5 h-3.5 mr-1" />
        Carga masiva
      </el-button>
      <el-button type="warning" size="small" @click="abrirNuevaClinica">
        <component :is="PlusIcon" class="w-3.5 h-3.5 mr-1" />
        Nueva clínica
      </el-button>
      <el-button size="small" @click="exportarExcel" :disabled="clinicasFiltradas.length === 0">
        <component :is="DownloadIcon" class="w-3.5 h-3.5 mr-1" />
        Exportar
      </el-button>
    </div>

    <!-- ── Stat cards ── -->
    <div class="clinic-stats-bar shrink-0">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="clinic-stat-card"
        :style="{ '--stat-color': card.color, '--stat-bg': card.iconBg }"
      >
        <div class="clinic-stat-icon" :style="{ background: card.iconBg, color: card.color }">
          <component :is="card.icon" class="w-4 h-4" />
        </div>
        <div class="clinic-stat-body">
          <p class="clinic-stat-label">{{ card.label }}</p>
          <p class="clinic-stat-value" :style="{ color: card.color }">{{ card.value }}</p>
          <div class="clinic-stat-bar-track">
            <div class="clinic-stat-bar-fill" :style="{ width: card.percent + '%', background: card.color }"></div>
          </div>
        </div>
        <span class="clinic-stat-delta" :style="{ background: card.deltaBg, color: card.deltaColor }">{{ card.delta }}</span>
      </div>
    </div>

    <!-- ── Tabs + Búsqueda ── -->
    <div class="clinic-filter-bar shrink-0">
      <div class="clinic-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        class="clinic-tab"
        :class="{ 'clinic-tab-active': tabActiva === tab.value }"
        @click="cambiarTab(tab.value)"
      >
        <span v-if="tab.value !== 'todas'" class="clinic-tab-dot" :class="'clinic-tab-dot--' + tab.value"></span>
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

    <!-- ── Bulk actions bar ── -->
    <Transition name="bulk-slide">
      <div v-if="seleccionadas.size > 0" class="clinic-bulk-bar shrink-0">
        <span class="text-xs font-bold" style="color:#0D2D6B;">{{ seleccionadas.size }} seleccionada(s)</span>
        <el-button type="success" size="small" @click="aprobarLote">
          <component :is="CheckIcon" class="w-3 h-3 mr-0.5" /> Aprobar
        </el-button>
        <el-button type="danger" size="small" @click="rechazarLote">
          <component :is="XIcon" class="w-3 h-3 mr-0.5" /> Rechazar
        </el-button>
        <el-button size="small" text @click="seleccionadas.clear()">Limpiar</el-button>
      </div>
    </Transition>

    <!-- ── Tabla ── -->
    <div class="flex-1 overflow-hidden clinic-table-panel">
      <!-- Loading -->
      <div v-if="cargando" class="clinic-table-loading">
        <div v-for="i in 6" :key="i" class="clinic-table-row-skeleton">
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
      <div v-else-if="clinicasPaginadas.length === 0" class="clinic-empty-wrap">
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
                <th class="clinic-table-th clinic-table-th-check">
                  <input type="checkbox" :checked="todasSeleccionadas" @change="toggleSeleccionTodas" class="clinic-checkbox" />
                </th>
                <th class="clinic-table-th clinic-table-th-clinica clinic-th-sortable" @click="toggleSort('nombre')">
                  Clínica
                  <component :is="sortIcon('nombre')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'nombre', 'opacity-30': sortKey !== 'nombre' }" />
                </th>
                <th class="clinic-table-th clinic-th-sortable" @click="toggleSort('nit')">
                  NIT
                  <component :is="sortIcon('nit')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'nit', 'opacity-30': sortKey !== 'nit' }" />
                </th>
                <th class="clinic-table-th clinic-th-sortable" @click="toggleSort('ciudad')">
                  Ciudad
                  <component :is="sortIcon('ciudad')" class="w-3 h-3 inline-block ml-0.5" :class="{ 'opacity-100': sortKey === 'ciudad', 'opacity-30': sortKey !== 'ciudad' }" />
                </th>
                <th class="clinic-table-th">Estado</th>
                <th class="clinic-table-th clinic-table-th-actions">Acciones</th>
              </tr>
            </thead>
            <TransitionGroup name="clinic-row" tag="tbody">
              <tr
                v-for="(clinica, idx) in clinicasPaginadas"
                :key="clinica.id"
                class="clinic-table-row"
                :class="{ 'clinic-table-row-selected': seleccionadas.has(clinica.id) }"
              >
                <td class="clinic-table-td clinic-table-td-check">
                  <input type="checkbox" :checked="seleccionadas.has(clinica.id)" @change="toggleSeleccion(clinica.id)" class="clinic-checkbox" />
                </td>
                <td class="clinic-table-td">
                  <div class="clinic-table-clinica">
                    <div class="clinic-table-avatar" :style="avatarStyle(clinica.estado)">
                      {{ iniciales(clinica.nombre) }}
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
                    <el-button size="small" type="warning" plain @click="abrirEditar(clinica)">
                      <component :is="EditIcon" class="w-3 h-3 mr-0.5" />
                      Editar
                    </el-button>
                  </div>
                </td>
              </tr>
            </TransitionGroup>
          </table>
        </div>
        <!-- Pagination -->
        <div class="clinic-pagination">
          <span class="clinic-pagination-info">
            {{ (paginaActual - 1) * itemsPorPagina + 1 }}–{{ Math.min(paginaActual * itemsPorPagina, clinicasFiltradas.length) }}
            de {{ clinicasFiltradas.length }}
          </span>
          <div class="clinic-pagination-controls">
            <button class="clinic-pagination-btn" :disabled="paginaActual === 1" @click="paginaActual--">
              <component :is="ChevronLeftIcon" class="w-3.5 h-3.5" />
            </button>
            <span class="clinic-pagination-page">{{ paginaActual }} / {{ totalPaginas }}</span>
            <button class="clinic-pagination-btn" :disabled="paginaActual === totalPaginas" @click="paginaActual++">
              <component :is="ChevronRightIcon" class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Detalle -->
    <BaseModal v-model="modalDetalle" width="620px" class="detalle-clinica-dialog" align-center :title="'Detalle de clínica'" :subtitle="clinicaSeleccionada ? clinicaSeleccionada.nit + ' · ' + clinicaSeleccionada.nombre : ''">
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

    <!-- Modal: Editar clínica -->
    <el-dialog v-model="modalEditar" width="680px" :close-on-click-modal="false" class="nueva-clinica-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(59,130,246,0.2); color:#60a5fa;">
            <component :is="EditIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Editar clínica</p>
            <p class="accion-head-sub">{{ formEditar.nit }} · {{ formEditar.nombre }}</p>
          </div>
        </div>
      </template>

      <div class="px-5 py-4">
        <el-form ref="formEditarRef" :model="formEditar" :rules="rulesEditar" label-position="top" size="small" autocomplete="off">
          <div class="flex items-center gap-2 mb-2">
            <div class="nueva-clinica-step-num">1</div>
            <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Datos de la institución</p>
            <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);"></div>
          </div>
          <div class="grid grid-cols-3 gap-x-3 mb-1">
            <el-form-item prop="nit" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> NIT</span></template>
              <el-input v-model="formEditar.nit" placeholder="900123456" clearable />
            </el-form-item>
            <el-form-item prop="nombre" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> Nombre</span></template>
              <el-input v-model="formEditar.nombre" placeholder="Nombre de la clínica" clearable />
            </el-form-item>
            <el-form-item prop="telefono" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="PhoneIcon" class="w-3 h-3 text-[#16468E]" /> Teléfono</span></template>
              <el-input v-model="formEditar.telefono" placeholder="3101234567" clearable />
            </el-form-item>
          </div>
          <div class="grid grid-cols-3 gap-x-3 mb-1">
            <el-form-item prop="direccion" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Dirección</span></template>
              <el-input v-model="formEditar.direccion" placeholder="Dirección completa" clearable />
            </el-form-item>
            <el-form-item prop="email" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MailIcon" class="w-3 h-3 text-[#16468E]" /> Correo</span></template>
              <el-input v-model="formEditar.email" type="email" placeholder="correo@clinica.com" clearable />
            </el-form-item>
            <el-form-item class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Departamento</span></template>
              <el-select v-model="formEditar.departamento" placeholder="Seleccione" filterable class="w-full" @change="formEditar.ciudad = ''">
                <el-option v-for="dep in departamentos" :key="dep" :label="dep" :value="dep" />
              </el-select>
            </el-form-item>
          </div>
          <div class="grid grid-cols-2 gap-x-3 mb-1">
            <el-form-item class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Ciudad</span></template>
              <el-select v-model="formEditar.ciudad" placeholder="Seleccione" filterable class="w-full" :disabled="!formEditar.departamento">
                <el-option v-for="c in ciudadesEditar" :key="c" :label="c" :value="c" />
              </el-select>
            </el-form-item>
          </div>

          <div class="flex items-center gap-2 mb-2 mt-3">
            <div class="nueva-clinica-step-num">2</div>
            <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Representante legal</p>
            <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);"></div>
          </div>
          <div class="grid grid-cols-2 gap-x-3 mb-1">
            <el-form-item prop="representante_legal" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="UserIcon" class="w-3 h-3 text-[#16468E]" /> Nombre</span></template>
              <el-input v-model="formEditar.representante_legal" placeholder="Nombre del representante" clearable />
            </el-form-item>
            <el-form-item prop="cedula_representante" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="IdCardIcon" class="w-3 h-3 text-[#16468E]" /> Cédula</span></template>
              <el-input v-model="formEditar.cedula_representante" placeholder="Número de cédula" clearable />
            </el-form-item>
          </div>
        </el-form>
      </div>

      <template #footer>
        <el-button @click="modalEditar = false">Cancelar</el-button>
        <el-button type="primary" :loading="procesandoEditar" @click="guardarEdicion">
          <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
          Guardar cambios
        </el-button>
      </template>
    </el-dialog>

    <!-- Modal: Nueva clínica -->
    <el-dialog v-model="modalNuevaClinica" width="680px" :close-on-click-modal="false" class="nueva-clinica-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(245,158,11,0.2); color:#fbbf24;">
            <component :is="PlusIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Nueva clínica</p>
            <p class="accion-head-sub">Registra una institución individual</p>
          </div>
        </div>
      </template>

      <div class="px-5 py-4">
        <el-form ref="formNuevaRef" :model="formNueva" :rules="rulesNueva" label-position="top" size="small" autocomplete="off">
          <!-- Sección institución -->
          <div class="flex items-center gap-2 mb-2">
            <div class="nueva-clinica-step-num">1</div>
            <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Datos de la institución</p>
            <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);"></div>
          </div>
          <div class="grid grid-cols-3 gap-x-3 mb-1">
            <el-form-item prop="nit" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> NIT</span></template>
              <el-input v-model="formNueva.nit" placeholder="900123456" clearable autocomplete="off" />
            </el-form-item>
            <el-form-item prop="nombre" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="BuildingIcon" class="w-3 h-3 text-[#16468E]" /> Nombre / Razón social</span></template>
              <el-input v-model="formNueva.nombre" placeholder="Nombre de la clínica" clearable autocomplete="off" />
            </el-form-item>
            <el-form-item prop="telefono" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="PhoneIcon" class="w-3 h-3 text-[#16468E]" /> Teléfono</span></template>
              <el-input v-model="formNueva.telefono" placeholder="3101234567" clearable autocomplete="off" />
            </el-form-item>
          </div>
          <div class="grid grid-cols-3 gap-x-3 mb-1">
            <el-form-item prop="direccion" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Dirección</span></template>
              <el-input v-model="formNueva.direccion" placeholder="Dirección completa" clearable autocomplete="off" />
            </el-form-item>
            <el-form-item prop="email" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MailIcon" class="w-3 h-3 text-[#16468E]" /> Correo</span></template>
              <el-input v-model="formNueva.email" type="email" placeholder="correo@clinica.com" clearable autocomplete="off" />
            </el-form-item>
            <el-form-item prop="email_confirmacion" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MailCheckIcon" class="w-3 h-3 text-[#16468E]" /> Confirmar correo</span></template>
              <el-input v-model="formNueva.email_confirmacion" type="email" placeholder="Repita el correo" clearable autocomplete="off" />
            </el-form-item>
          </div>

          <!-- Sección ubicación -->
          <div class="flex items-center gap-2 mb-2 mt-3">
            <div class="nueva-clinica-step-num">2</div>
            <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Ubicación</p>
            <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);"></div>
          </div>
          <div class="grid grid-cols-2 gap-x-3 mb-1">
            <el-form-item prop="departamento" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Departamento</span></template>
              <el-select v-model="formNueva.departamento" placeholder="Seleccione" filterable class="w-full" @change="formNueva.ciudad = ''">
                <el-option v-for="dep in departamentos" :key="dep" :label="dep" :value="dep" />
              </el-select>
            </el-form-item>
            <el-form-item prop="ciudad" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="MapPinIcon" class="w-3 h-3 text-[#16468E]" /> Ciudad / Municipio</span></template>
              <el-select v-model="formNueva.ciudad" placeholder="Seleccione" filterable class="w-full" :disabled="!formNueva.departamento">
                <el-option v-for="c in ciudadesDelDepartamento" :key="c" :label="c" :value="c" />
              </el-select>
            </el-form-item>
          </div>

          <!-- Sección representante -->
          <div class="flex items-center gap-2 mb-2 mt-3">
            <div class="nueva-clinica-step-num">3</div>
            <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Representante legal</p>
            <div class="flex-1 h-px" style="background: linear-gradient(90deg, #c5cfdb, transparent);"></div>
          </div>
          <div class="grid grid-cols-2 gap-x-3 mb-1">
            <el-form-item prop="representante_legal" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="UserIcon" class="w-3 h-3 text-[#16468E]" /> Nombre completo</span></template>
              <el-input v-model="formNueva.representante_legal" placeholder="Nombre del representante" clearable autocomplete="off" />
            </el-form-item>
            <el-form-item prop="cedula_representante" class="mb-1 form-item-custom">
              <template #label><span class="flex items-center gap-1"><component :is="IdCardIcon" class="w-3 h-3 text-[#16468E]" /> Cédula</span></template>
              <el-input v-model="formNueva.cedula_representante" placeholder="Número de cédula" clearable autocomplete="off" />
            </el-form-item>
          </div>
        </el-form>
      </div>

      <template #footer>
        <el-button @click="modalNuevaClinica = false">Cancelar</el-button>
        <el-button type="warning" :loading="procesandoNueva" @click="guardarNuevaClinica">
          <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
          Crear clínica
        </el-button>
      </template>
    </el-dialog>

    <!-- Modal: Carga masiva -->
    <el-dialog v-model="modalCargaMasiva" width="820px" :close-on-click-modal="false" class="carga-masiva-dialog" align-center>
      <template #header>
        <div class="accion-head">
          <div class="accion-head-glow"></div>
          <div class="accion-head-icon" style="background:rgba(34,197,94,0.2); color:#4ade80;">
            <component :is="UploadIcon" class="w-5 h-5" />
          </div>
          <div class="accion-head-info">
            <p class="accion-head-title">Carga masiva de clínicas</p>
            <p class="accion-head-sub">Registra múltiples clínicas a la vez</p>
          </div>
        </div>
      </template>

      <div class="px-5 py-4 space-y-4">
        <!-- Zona de pegado -->
        <div class="carga-masiva-paste-zone">
          <div class="flex items-center gap-2 mb-2">
            <component :is="ClipboardListIcon" class="w-4 h-4 text-blue-600" />
            <p class="text-xs font-bold" style="color:#334e70;">Pegar datos desde Excel</p>
          </div>
          <p class="text-xs text-gray-500 mb-2">Pega celdas copiadas desde Excel. Columnas: <strong>NIT | Nombre | Email | Teléfono | Ciudad | Departamento | Dirección | Representante | Cédula</strong></p>
          <el-input
            v-model="textoPegado"
            type="textarea"
            :rows="4"
            placeholder="900123456\tClínica Ejemplo\tinfo@ejemplo.com\t3001234567\tBogotá\tCundinamarca\tCalle 123\tJuan Pérez\t12345678"
          />
          <el-button type="primary" size="small" class="mt-2" @click="procesarPegado" :disabled="!textoPegado.trim()">
            <component :is="CheckIcon" class="w-3.5 h-3.5 mr-1" />
            Procesar datos
          </el-button>
        </div>

        <!-- Tabla de revisión -->
        <div v-if="filasCarga.length > 0" class="carga-masiva-table-wrap">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold" style="color:#334e70;">Clínicas a registrar ({{ filasCarga.length }})</p>
            <el-button type="danger" size="small" text @click="filasCarga = []">
              <component :is="XIcon" class="w-3 h-3 mr-0.5" />
              Limpiar
            </el-button>
          </div>
          <div class="overflow-auto carga-masiva-scroll" style="max-height: 280px;">
            <table class="carga-masiva-table">
              <thead>
                <tr>
                  <th class="carga-masiva-th">NIT</th>
                  <th class="carga-masiva-th">Nombre</th>
                  <th class="carga-masiva-th">Email</th>
                  <th class="carga-masiva-th">Teléfono</th>
                  <th class="carga-masiva-th">Ciudad</th>
                  <th class="carga-masiva-th">Depto</th>
                  <th class="carga-masiva-th">Dirección</th>
                  <th class="carga-masiva-th">Representante</th>
                  <th class="carga-masiva-th">Cédula</th>
                  <th class="carga-masiva-th"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(fila, idx) in filasCarga" :key="idx" class="carga-masiva-row">
                  <td class="carga-masiva-td"><input v-model="fila.nit" class="carga-masiva-input" placeholder="NIT" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.nombre" class="carga-masiva-input" placeholder="Nombre" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.email" class="carga-masiva-input" placeholder="Email" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.telefono" class="carga-masiva-input" placeholder="Teléfono" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.ciudad" class="carga-masiva-input" placeholder="Ciudad" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.departamento" class="carga-masiva-input" placeholder="Depto" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.direccion" class="carga-masiva-input" placeholder="Dirección" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.representante_legal" class="carga-masiva-input" placeholder="Representante" /></td>
                  <td class="carga-masiva-td"><input v-model="fila.cedula_representante" class="carga-masiva-input" placeholder="Cédula" /></td>
                  <td class="carga-masiva-td">
                    <button class="carga-masiva-remove" @click="filasCarga.splice(idx, 1)">
                      <component :is="XIcon" class="w-3.5 h-3.5" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Fila vacía para añadir manualmente -->
        <el-button v-if="filasCarga.length > 0" size="small" @click="agregarFila">
          <component :is="PlusIcon" class="w-3.5 h-3.5 mr-1" />
          Agregar fila
        </el-button>
      </div>

      <template #footer>
        <el-button @click="modalCargaMasiva = false">Cancelar</el-button>
        <el-button type="success" :loading="procesandoCarga" :disabled="filasCarga.length === 0" @click="enviarCargaMasiva">
          <component :is="UploadIcon" class="w-3.5 h-3.5 mr-1" />
          Registrar {{ filasCarga.length }} clínica(s)
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

/* ── Stat cards ── */
.clinic-stats-bar {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: .5rem;
}
.clinic-stat-card {
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
.clinic-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(13,45,107,.08);
}
.clinic-stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--stat-color);
  opacity: .8;
}
.clinic-stat-icon {
  width: 2.2rem; height: 2.2rem;
  border-radius: 10px;
  display: grid; place-items: center;
  flex-shrink: 0;
}
.clinic-stat-body { flex: 1; min-width: 0; }
.clinic-stat-label {
  font-size: 10px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: .03em;
  margin: 0;
}
.clinic-stat-value {
  font-size: 1.3rem;
  font-weight: 800;
  line-height: 1.1;
  margin: 0;
}
.clinic-stat-bar-track {
  height: 3px;
  border-radius: 2px;
  background: #f1f5f9;
  margin-top: .25rem;
  overflow: hidden;
}
.clinic-stat-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width .4s ease;
}
.clinic-stat-delta {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 6px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Tab dots ── */
.clinic-tab-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  display: inline-block;
  margin-right: .3rem;
}
.clinic-tab-dot--pendiente { background: #f59e0b; }
.clinic-tab-dot--activa { background: #22c55e; }
.clinic-tab-dot--rechazada { background: #ef4444; }

/* ── Bulk actions bar ── */
.clinic-bulk-bar {
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
.clinic-checkbox {
  width: 16px; height: 16px;
  border-radius: 4px;
  border: 1.5px solid #cbd5e1;
  cursor: pointer;
  accent-color: #16468E;
}
.clinic-table-th-check { width: 36px; text-align: center; }
.clinic-table-td-check { text-align: center; }

/* ── Sortable headers ── */
.clinic-th-sortable {
  cursor: pointer;
  user-select: none;
  transition: color .15s ease;
}
.clinic-th-sortable:hover { color: #16468E; }

/* ── Selected row ── */
.clinic-table-row-selected {
  background: rgba(22,70,142,.04) !important;
}

/* ── Row transitions ── */
.clinic-row-enter-active, .clinic-row-leave-active {
  transition: all .3s ease;
}
.clinic-row-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}
.clinic-row-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

/* ── Pagination ── */
.clinic-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: .5rem .8rem;
  border-top: 1px solid #f1f5f9;
  background: #fafbfc;
}
.clinic-pagination-info {
  font-size: 11px;
  color: #64748b;
  font-weight: 500;
}
.clinic-pagination-controls {
  display: flex;
  align-items: center;
  gap: .4rem;
}
.clinic-pagination-btn {
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
.clinic-pagination-btn:hover:not(:disabled) {
  border-color: #16468E;
  color: #16468E;
  background: #f0f5ff;
}
.clinic-pagination-btn:disabled {
  opacity: .4;
  cursor: not-allowed;
}
.clinic-pagination-page {
  font-size: 12px;
  font-weight: 600;
  color: #334e70;
  min-width: 60px;
  text-align: center;
}

/* ── Nueva clínica ── */
:deep(.nueva-clinica-dialog) { border-radius: 16px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.35); }
:deep(.nueva-clinica-dialog .el-dialog__header) { margin: 0; padding: 0; border: none; }
:deep(.nueva-clinica-dialog .el-dialog__body) { padding: 0; }
:deep(.nueva-clinica-dialog .el-dialog__headerbtn) { top: 12px; right: 14px; z-index: 30; }
:deep(.nueva-clinica-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.4rem; font-weight: 700; }

.nueva-clinica-step-num {
  width: 24px; height: 24px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 800; color: #0D2D6B;
  background: #f1f5f9; border: 1px solid #e2e8f0;
}

.form-item-custom :deep(.el-input__wrapper),
.form-item-custom :deep(.el-select__wrapper) {
  border-radius: 10px;
  background: #f8fafc !important;
  box-shadow: inset 2px 2px 4px rgba(163,177,198,0.3), inset -2px -2px 4px rgba(255,255,255,0.7);
  transition: all 0.2s ease;
}
.form-item-custom :deep(.el-input__wrapper:hover),
.form-item-custom :deep(.el-select__wrapper:hover) {
  box-shadow: inset 2px 2px 4px rgba(163,177,198,0.4), inset -2px -2px 4px rgba(255,255,255,0.8);
}
.form-item-custom :deep(.el-input__wrapper.is-focus),
.form-item-custom :deep(.el-select__wrapper.is-focus) {
  box-shadow: inset 3px 3px 5px rgba(163,177,198,0.4), inset -3px -3px 5px rgba(255,255,255,0.85), 0 0 0 1.5px rgba(13,45,107,0.12);
}
.form-item-custom :deep(.el-input__prefix-inner) { color: #16468E; }
.form-item-custom :deep(.el-input__inner) { background: transparent !important; }
.form-item-custom :deep(.el-form-item__label)::before { display: none !important; content: '' !important; }

/* ── Carga masiva ── */
:deep(.carga-masiva-dialog) { border-radius: 16px; overflow: hidden; box-shadow: 0 32px 80px rgba(11,35,73,.35); }
:deep(.carga-masiva-dialog .el-dialog__header) { margin: 0; padding: 0; border: none; }
:deep(.carga-masiva-dialog .el-dialog__body) { padding: 0; }
:deep(.carga-masiva-dialog .el-dialog__headerbtn) { top: 12px; right: 14px; z-index: 30; }
:deep(.carga-masiva-dialog .el-dialog__headerbtn .el-dialog__close) { color: #fff; font-size: 1.4rem; font-weight: 700; }

.carga-masiva-paste-zone {
  background: #f8fafc;
  border: 1px dashed #c4d4e8;
  border-radius: 12px;
  padding: 14px 16px;
}

.carga-masiva-table-wrap {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
}

.carga-masiva-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
.carga-masiva-scroll::-webkit-scrollbar-thumb { background: #c5c9d0; border-radius: 4px; }
.carga-masiva-scroll::-webkit-scrollbar-track { background: transparent; }

.carga-masiva-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 12px;
}
.carga-masiva-th {
  padding: 6px 8px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 5;
}
.carga-masiva-row { transition: background .15s ease; }
.carga-masiva-row:hover { background: #f8fafc; }

.carga-masiva-td {
  padding: 4px 6px;
  border-bottom: 1px solid #f1f5f9;
  white-space: nowrap;
}

.carga-masiva-input {
  width: 100%;
  min-width: 80px;
  padding: 5px 8px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 12px;
  color: #1e293b;
  background: #fff;
  transition: border-color .2s ease, box-shadow .2s ease;
  outline: none;
}
.carga-masiva-input:focus {
  border-color: #16468E;
  box-shadow: 0 0 0 2px rgba(22,70,142,.1);
}
.carga-masiva-input::placeholder { color: #cbd5e1; }

.carga-masiva-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 6px;
  background: #fee2e2;
  color: #dc2626;
  cursor: pointer;
  transition: background .2s ease;
}
.carga-masiva-remove:hover { background: #fecaca; }

/* ── Shared accion-head (if not already defined elsewhere) ── */
.accion-head {
  position: relative;
  background: linear-gradient(125deg, #0d2d6b 0%, #16468e 50%, #1a3d8a 100%);
  padding: 1.1rem 1.3rem;
  display: flex;
  align-items: center;
  gap: .8rem;
  overflow: hidden;
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
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { useStorage, useDebounceFn } from '@vueuse/core';
import { ElMessageBox } from 'element-plus';
import notify from '@/plugins/toast';
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
  Upload as UploadIcon,
  ClipboardList as ClipboardListIcon,
  Plus as PlusIcon,
  Mail as MailIcon,
  MailCheck as MailCheckIcon,
  Phone as PhoneIcon,
  User as UserIcon,
  MapPin as MapPinIcon,
  IdCard as IdCardIcon,
  Download as DownloadIcon,
  Edit as EditIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
  ArrowUp as ArrowUpIcon,
  ArrowDown as ArrowDownIcon,
  ArrowUpDown as ArrowUpDownIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import type { FormInstance, FormRules } from 'element-plus';

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
const modalCargaMasiva = ref(false);
const clinicaSeleccionada = ref<Clinica | null>(null);
const textoPegado = ref('');
const filasCarga = ref<Array<{ nit: string; nombre: string; email: string; telefono: string; ciudad: string; departamento: string; direccion: string; representante_legal: string; cedula_representante: string }>>([]);
const procesandoCarga = ref(false);

const modalNuevaClinica = ref(false);
const procesandoNueva = ref(false);
const formNuevaRef = ref<FormInstance>();
const formNueva = reactive({
  nit: '',
  nombre: '',
  email: '',
  email_confirmacion: '',
  telefono: '',
  departamento: '',
  ciudad: '',
  direccion: '',
  representante_legal: '',
  cedula_representante: '',
});

const modalEditar = ref(false);
const procesandoEditar = ref(false);
const formEditarRef = ref<FormInstance>();
const formEditar = reactive({
  id: 0,
  nit: '',
  nombre: '',
  email: '',
  telefono: '',
  departamento: '',
  ciudad: '',
  direccion: '',
  representante_legal: '',
  cedula_representante: '',
});
const rulesEditar: FormRules = {
  nit: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  nombre: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  email: [{ required: true, message: 'Obligatorio', trigger: 'blur' }, { type: 'email', message: 'Correo inválido', trigger: 'blur' }],
};

const seleccionadas = ref<Set<number>>(new Set());
const sortKey = ref<'nombre' | 'nit' | 'ciudad'>('nombre');
const sortDir = ref<'asc' | 'desc'>('asc');
const paginaActual = ref(1);
const itemsPorPagina = 15;

const colombiaDatos: Record<string, string[]> = {
  'Amazonas': ['Leticia', 'Puerto Nariño'],
  'Antioquia': ['Medellín', 'Bello', 'Itagüí', 'Envigado', 'Apartadó', 'Turbo', 'Rionegro', 'Caucasia', 'Marinilla', 'El Carmen de Viboral', 'Sabaneta', 'Copacabana', 'Girardota', 'Barbosa', 'La Estrella', 'Caldas'],
  'Arauca': ['Arauca', 'Saravena', 'Tame', 'Arauquita', 'Fortul'],
  'Atlántico': ['Barranquilla', 'Soledad', 'Malambo', 'Sabanalarga', 'Baranoa', 'Puerto Colombia'],
  'Bolívar': ['Cartagena', 'Magangué', 'Turbaco', 'El Carmen de Bolívar', 'Mompós'],
  'Boyacá': ['Tunja', 'Duitama', 'Sogamoso', 'Chiquinquirá', 'Paipa', 'Moniquirá'],
  'Caldas': ['Manizales', 'Villamaría', 'Chinchiná', 'Riosucio', 'Salamina', 'Aguadas'],
  'Caquetá': ['Florencia', 'San Vicente del Caguán', 'Puerto Rico', 'Belén de los Andaquíes'],
  'Casanare': ['Yopal', 'Aguazul', 'Villanueva', 'Tauramena', 'Paz de Ariporo'],
  'Cauca': ['Popayán', 'Santander de Quilichao', 'Puerto Tejada', 'Patía', 'El Tambo'],
  'Cesar': ['Valledupar', 'Aguachica', 'Bosconia', 'Codazzi', 'La Jagua de Ibirico'],
  'Chocó': ['Quibdó', 'Istmina', 'Tadó', 'Condoto', 'Bagadó'],
  'Córdoba': ['Montería', 'Cereté', 'Lorica', 'Sahagún', 'Montelíbano', 'Tierralta'],
  'Cundinamarca': ['Bogotá D.C.', 'Soacha', 'Zipaquirá', 'Facatativá', 'Chía', 'Mosquera', 'Funza', 'Madrid', 'Fusagasugá', 'Girardot', 'Cajicá'],
  'Guainía': ['Inírida'],
  'Guaviare': ['San José del Guaviare', 'Calamar'],
  'Huila': ['Neiva', 'Pitalito', 'Garzón', 'La Plata', 'Campoalegre'],
  'La Guajira': ['Riohacha', 'Maicao', 'Uribia', 'Manaure', 'Fonseca'],
  'Magdalena': ['Santa Marta', 'Ciénaga', 'Fundación', 'El Banco', 'Plato'],
  'Meta': ['Villavicencio', 'Acacías', 'Granada', 'San Martín', 'Puerto López'],
  'Nariño': ['Pasto', 'Tumaco', 'Ipiales', 'Túquerres', 'La Unión'],
  'Norte de Santander': ['Cúcuta', 'Ocaña', 'Pamplona', 'Villa del Rosario', 'Los Patios', 'Tibú'],
  'Putumayo': ['Mocoa', 'Puerto Asís', 'Orito', 'Valle del Guamuez', 'Sibundoy'],
  'Quindío': ['Armenia', 'Calarcá', 'Montenegro', 'Quimbaya', 'La Tebaida'],
  'Risaralda': ['Pereira', 'Dosquebradas', 'Santa Rosa de Cabal', 'La Virginia', 'Quinchía'],
  'San Andrés': ['San Andrés', 'Providencia'],
  'Santander': ['Bucaramanga', 'Floridablanca', 'Girón', 'Piedecuesta', 'Barrancabermeja', 'San Gil', 'Socorro'],
  'Sucre': ['Sincelejo', 'Corozal', 'Sampués', 'San Marcos', 'Tolú'],
  'Tolima': ['Ibagué', 'Espinal', 'Girardot', 'Honda', 'Melgar', 'Chaparral'],
  'Valle del Cauca': ['Cali', 'Buenaventura', 'Palmira', 'Tuluá', 'Buga', 'Cartago', 'Jamundí', 'Yumbo', 'Florida', 'Pradera'],
  'Vaupés': ['Mitú'],
  'Vichada': ['Puerto Carreño'],
};

const departamentos = Object.keys(colombiaDatos).sort();
const ciudadesDelDepartamento = computed(() => formNueva.departamento ? colombiaDatos[formNueva.departamento] ?? [] : []);
const ciudadesEditar = computed(() => formEditar.departamento ? colombiaDatos[formEditar.departamento] ?? [] : []);

const rulesNueva: FormRules = {
  nit: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  nombre: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  email: [{ required: true, message: 'Obligatorio', trigger: 'blur' }, { type: 'email', message: 'Correo inválido', trigger: 'blur' }],
  email_confirmacion: [
    { required: true, message: 'Obligatorio', trigger: 'blur' },
    { type: 'email', message: 'Correo inválido', trigger: 'blur' },
    {
      validator: (_rule: any, value: string, callback: any) => {
        if (value && value !== formNueva.email) callback(new Error('Los correos no coinciden'));
        else callback();
      },
      trigger: 'blur',
    },
  ],
  telefono: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  direccion: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  departamento: [{ required: true, message: 'Obligatorio', trigger: 'change' }],
  ciudad: [{ required: true, message: 'Obligatorio', trigger: 'change' }],
  representante_legal: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
  cedula_representante: [{ required: true, message: 'Obligatorio', trigger: 'blur' }],
};
const tabActiva = useStorage<'todas' | 'pendiente' | 'activa' | 'rechazada'>('clinica-tab', 'todas');
const motivoRechazo = ref('');
const filtroBuscar = useStorage('clinica-buscar', '');
const filtroBuscarDebounced = ref(filtroBuscar.value);
const updateDebounced = useDebounceFn((val: string) => { filtroBuscarDebounced.value = val; }, 300);
watch(filtroBuscar, (val) => updateDebounced(val));

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
      icon: ShieldCheck, color: '#15966a', iconBg: '#dcfce7',
      delta: 'Aprobadas', deltaBg: '#dcfce7', deltaColor: '#15966a',
      percent: Math.round((resumen.value.activas / total) * 100),
    },
    {
      label: 'Rechazadas', value: String(resumen.value.rechazadas),
      icon: AlertTriangle, color: '#dc2626', iconBg: '#fee2e2',
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
  if (filtroBuscarDebounced.value.trim()) {
    const q = filtroBuscarDebounced.value.toLowerCase().trim();
    result = result.filter(c =>
      c.nit.toLowerCase().includes(q) ||
      c.ciudad.toLowerCase().includes(q) ||
      c.nombre.toLowerCase().includes(q) ||
      c.estado.toLowerCase().includes(q)
    );
  }
  const dir = sortDir.value === 'asc' ? 1 : -1;
  result = [...result].sort((a, b) => {
    const va = (a[sortKey.value] ?? '').toString().toLowerCase();
    const vb = (b[sortKey.value] ?? '').toString().toLowerCase();
    return va < vb ? -dir : va > vb ? dir : 0;
  });
  return result;
});

const totalPaginas = computed(() => Math.max(1, Math.ceil(clinicasFiltradas.value.length / itemsPorPagina)));
const clinicasPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * itemsPorPagina;
  return clinicasFiltradas.value.slice(start, start + itemsPorPagina);
});

const todasSeleccionadas = computed(() => {
  if (clinicasPaginadas.value.length === 0) return false;
  return clinicasPaginadas.value.every(c => seleccionadas.value.has(c.id));
});

function toggleSort(key: 'nombre' | 'nit' | 'ciudad') {
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

function cambiarTab(tab: 'todas' | 'pendiente' | 'activa' | 'rechazada') {
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
    clinicasPaginadas.value.forEach(c => s.delete(c.id));
  } else {
    clinicasPaginadas.value.forEach(c => s.add(c.id));
  }
  seleccionadas.value = s;
}

function iniciales(nombre: string) {
  const palabras = nombre.trim().split(/\s+/);
  if (palabras.length === 0) return '?';
  if (palabras.length === 1) return palabras[0].slice(0, 2).toUpperCase();
  return (palabras[0][0] + palabras[1][0]).toUpperCase();
}

async function aprobarLote() {
  const ids = [...seleccionadas.value];
  const clinicasLote = clinicas.value.filter(c => ids.includes(c.id) && c.estado !== 'activa');
  if (clinicasLote.length === 0) {
    notify.warning('No hay clínicas pendientes o rechazadas para aprobar');
    return;
  }
  try {
    await ElMessageBox.confirm(`¿Aprobar ${clinicasLote.length} clínica(s)?`, 'Confirmar aprobación', { confirmButtonText: 'Aprobar', cancelButtonText: 'Cancelar', type: 'success' });
    for (const c of clinicasLote) {
      const endpoint = c.estado === 'rechazada' ? 'reactivar' : 'aprobar';
      await http.post(`/api/clinicas/${c.id}/${endpoint}`);
    }
    notify.success(`${clinicasLote.length} clínica(s) aprobada(s)`);
    seleccionadas.value = new Set();
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al aprobar en lote');
  }
}

async function rechazarLote() {
  const ids = [...seleccionadas.value];
  const clinicasLote = clinicas.value.filter(c => ids.includes(c.id) && c.estado !== 'rechazada');
  if (clinicasLote.length === 0) {
    notify.warning('No hay clínicas para rechazar');
    return;
  }
  try {
    await ElMessageBox.confirm(`¿Rechazar ${clinicasLote.length} clínica(s)?`, 'Confirmar rechazo', { confirmButtonText: 'Rechazar', cancelButtonText: 'Cancelar', type: 'warning' });
    for (const c of clinicasLote) {
      await http.post(`/api/clinicas/${c.id}/rechazar`, { motivo: 'Rechazo masivo' });
    }
    notify.success(`${clinicasLote.length} clínica(s) rechazada(s)`);
    seleccionadas.value = new Set();
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al rechazar en lote');
  }
}

function exportarExcel() {
  const rows = clinicasFiltradas.value;
  const headers = ['NIT', 'Nombre', 'Email', 'Teléfono', 'Ciudad', 'Departamento', 'Dirección', 'Representante', 'Cédula', 'Estado', 'Fecha registro'];
  const csv = [
    headers.join('\t'),
    ...rows.map(c => [
      c.nit, c.nombre, c.email, c.telefono ?? '', c.ciudad ?? '', c.departamento ?? '',
      c.direccion ?? '', c.representante_legal ?? '', c.cedula_representante ?? '',
      estadoLabel(c.estado), formatFecha(c.created_at),
    ].map(v => `"${String(v).replace(/"/g, '""')}"`).join('\t')),
  ].join('\n');
  const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `clinicas_${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
  notify.success(`Exportadas ${rows.length} clínicas`);
}

function abrirEditar(clinica: Clinica) {
  Object.assign(formEditar, {
    id: clinica.id,
    nit: clinica.nit,
    nombre: clinica.nombre,
    email: clinica.email,
    telefono: clinica.telefono ?? '',
    departamento: clinica.departamento ?? '',
    ciudad: clinica.ciudad ?? '',
    direccion: clinica.direccion ?? '',
    representante_legal: clinica.representante_legal ?? '',
    cedula_representante: clinica.cedula_representante ?? '',
  });
  modalEditar.value = true;
}

async function guardarEdicion() {
  if (!formEditarRef.value) return;
  const esValido = await formEditarRef.value.validate().catch(() => false);
  if (!esValido) return;
  try {
    procesandoEditar.value = true;
    await http.put(`/api/clinicas/${formEditar.id}`, {
      nit: formEditar.nit,
      nombre: formEditar.nombre,
      razon_social: formEditar.nombre,
      email: formEditar.email,
      telefono: formEditar.telefono,
      ciudad: formEditar.ciudad,
      departamento: formEditar.departamento,
      direccion: formEditar.direccion,
      representante_legal: formEditar.representante_legal,
      cedula_representante: formEditar.cedula_representante,
    });
    notify.success('Clínica actualizada correctamente');
    modalEditar.value = false;
    await cargar();
  } catch (e: any) {
    const msg = e?.response?.data?.message ?? 'Error al actualizar la clínica';
    notify.error(msg);
  } finally {
    procesandoEditar.value = false;
  }
}

async function cargar() {
  try {
    cargando.value = true;
    const { data } = await http.get('/api/clinicas');
    clinicas.value = data.data;
  } catch {
    notify.error('Error al cargar las clínicas');
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
    notify.success(esReactivar ? 'Clínica reactivada correctamente' : 'Clínica aprobada correctamente');
    await cargar();
  } catch (e: any) {
    if (e !== 'cancel') notify.error('Error al procesar la clínica');
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
    notify.warning('Ingrese el motivo de rechazo');
    return;
  }
  try {
    procesando.value = clinicaSeleccionada.value!.id + '_rechazar';
    await http.post(`/api/clinicas/${clinicaSeleccionada.value!.id}/rechazar`, {
      motivo: motivoRechazo.value,
    });
    notify.success('Clínica rechazada');
    modalRechazo.value = false;
    await cargar();
  } catch {
    notify.error('Error al rechazar la clínica');
  } finally {
    procesando.value = null;
  }
}

function formatFecha(fecha: string) {
  return new Date(fecha).toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function abrirCargaMasiva() {
  textoPegado.value = '';
  filasCarga.value = [];
  modalCargaMasiva.value = true;
}

function abrirNuevaClinica() {
  Object.assign(formNueva, { nit: '', nombre: '', email: '', email_confirmacion: '', telefono: '', departamento: '', ciudad: '', direccion: '', representante_legal: '', cedula_representante: '' });
  modalNuevaClinica.value = true;
}

async function guardarNuevaClinica() {
  if (!formNuevaRef.value) return;
  const esValido = await formNuevaRef.value.validate().catch(() => false);
  if (!esValido) return;
  try {
    procesandoNueva.value = true;
    await http.post('/api/clinicas', {
      nit: formNueva.nit,
      nombre: formNueva.nombre,
      razon_social: formNueva.nombre,
      email: formNueva.email,
      telefono: formNueva.telefono,
      ciudad: formNueva.ciudad,
      departamento: formNueva.departamento,
      direccion: formNueva.direccion,
      representante_legal: formNueva.representante_legal,
      cedula_representante: formNueva.cedula_representante,
    });
    notify.success('Clínica creada correctamente');
    modalNuevaClinica.value = false;
    await cargar();
  } catch (e: any) {
    const msg = e?.response?.data?.message ?? 'Error al crear la clínica';
    notify.error(msg);
  } finally {
    procesandoNueva.value = false;
  }
}

function agregarFila() {
  filasCarga.value.push({ nit: '', nombre: '', email: '', telefono: '', ciudad: '', departamento: '', direccion: '', representante_legal: '', cedula_representante: '' });
}

function procesarPegado() {
  const lineas = textoPegado.value.trim().split(/\n/).filter(l => l.trim());
  const nuevas: typeof filasCarga.value = [];
  for (const linea of lineas) {
    const cols = linea.split(/\t/).map(c => c.trim());
    if (cols.length < 2) continue;
    nuevas.push({
      nit: cols[0] ?? '',
      nombre: cols[1] ?? '',
      email: cols[2] ?? '',
      telefono: cols[3] ?? '',
      ciudad: cols[4] ?? '',
      departamento: cols[5] ?? '',
      direccion: cols[6] ?? '',
      representante_legal: cols[7] ?? '',
      cedula_representante: cols[8] ?? '',
    });
  }
  if (nuevas.length === 0) {
    notify.warning('No se pudieron extraer datos. Asegúrate de copiar desde Excel con columnas separadas por tabulaciones.');
    return;
  }
  filasCarga.value = [...filasCarga.value, ...nuevas];
  textoPegado.value = '';
}

async function enviarCargaMasiva() {
  const validas = filasCarga.value.filter(f => f.nit.trim() && f.nombre.trim() && f.email.trim());
  if (validas.length === 0) {
    notify.warning('Debe haber al menos una fila con NIT, nombre y email');
    return;
  }
  try {
    procesandoCarga.value = true;
    const { data } = await http.post('/api/clinicas/carga-masiva', { clinicas: validas });
    notify.success(data.message);
    modalCargaMasiva.value = false;
    await cargar();
  } catch (e: any) {
    const msg = e?.response?.data?.message ?? 'Error al cargar las clínicas';
    notify.error(msg);
  } finally {
    procesandoCarga.value = false;
  }
}

let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  cargar();
  pollTimer = setInterval(() => {
    if (!cargando.value && !modalRechazo.value && !modalDetalle.value && !modalCargaMasiva.value && !modalNuevaClinica.value && !modalEditar.value) cargar();
  }, 30000);
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
});
</script>
