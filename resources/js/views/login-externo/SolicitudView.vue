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
              <div class="rf-gestante-btns">
                <button type="button" class="rf-gestante-btn" :class="{ active: form.genero === 'M' }" @click="seleccionarGenero('M')">Masculino</button>
                <button type="button" class="rf-gestante-btn" :class="{ active: form.genero === 'F' }" @click="seleccionarGenero('F')">Femenino</button>
              </div>
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
              <el-select-v2
                v-model="form.municipio_capita"
                :options="municipiosOpciones"
                filterable
                class="w-full"
                placeholder="Seleccione el municipio"
                popper-class="rf-select-popper"
              >
                <template #prefix><component :is="MapPinIcon" class="w-3.5 h-3.5 rf-input-icon" /></template>
              </el-select-v2>
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
              <el-form-item label="Servicio al que se refiere" :class="['rf-field rf-col-quarter rf-stagger-3', { 'is-success': camposValidos.servicio_remision }]">
                <el-select v-model="form.servicio_remision" filterable class="w-full" placeholder="Seleccione" clearable popper-class="rf-select-popper">
                  <el-option v-for="s in SERVICIOS_REMISION_GOMEDISYS" :key="s" :label="s" :value="s" />
                </el-select>
              </el-form-item>
              <el-form-item label="Vía de contacto" :class="['rf-field rf-col-quarter rf-stagger-4', { 'is-success': camposValidos.via_contacto }]">
                <el-select v-model="form.via_contacto" class="w-full" placeholder="Seleccione" clearable popper-class="rf-select-popper">
                  <el-option label="Email" value="EMAIL" />
                  <el-option label="Telefónica" value="TELEFONICA" />
                  <el-option label="N/A" value="N/A" />
                </el-select>
              </el-form-item>
            </div>
          </div>

          <!-- Información clínica + Diagnósticos -->
          <div class="rf-subsection">
            <div class="rf-grid">
              <el-form-item label="¿Paciente gestante?" :class="['rf-field rf-col-quarter rf-stagger-1', { 'is-success': camposValidos.gestante }]">
                <div class="rf-gestante-btns">
                  <button type="button" class="rf-gestante-btn" :class="{ active: form.gestante === true }" @click="form.gestante = true">Sí</button>
                  <button type="button" class="rf-gestante-btn" :class="{ active: form.gestante === false }" @click="form.gestante = false">No</button>
                </div>
              </el-form-item>
              <el-form-item label="Condición especial" :class="['rf-field rf-col-quarter rf-stagger-2', { 'is-success': camposValidos.condicion_especial }]">
                <el-input v-model="form.condicion_especial" placeholder="Ej: discapacidad..." autocomplete="off" @input="capitalizar('condicion_especial')" />
              </el-form-item>
              <el-form-item label="Dirección del paciente" :class="['rf-field rf-col-quarter rf-stagger-3', { 'is-success': camposValidos.direccion_paciente }]">
                <el-input v-model="form.direccion_paciente" autocomplete="off" maxlength="25" show-word-limit />
              </el-form-item>
              <el-form-item label="Teléfono del paciente" :class="['rf-field rf-col-quarter rf-stagger-4', { 'is-success': camposValidos.telefono_paciente }]">
                <el-input v-model="form.telefono_paciente" autocomplete="off" maxlength="15" @input="soloNumeros('telefono_paciente')" />
              </el-form-item>
            </div>

            <div class="rf-dx-section">
              <label class="rf-dx-label">Diagnóstico <span class="rf-req">*</span></label>
              <div v-for="(dx, i) in diagnosticos" :key="i" class="rf-dx-row-block">
                <div class="rf-dx-inline-row">
                  <el-autocomplete
                    v-model="dx.descripcion"
                    class="w-full"
                    placeholder="Escriba el diagnóstico o síntoma libremente..."
                    popper-class="rf-select-popper"
                    :fetch-suggestions="buscarSugerenciasCIE10"
                    @select="(item: any) => seleccionarSugerenciaCIE10(i, item)"
                    @blur="verificarCodigoCIE10(i)"
                  >
                    <template #default="{ item }">
                      <span class="rf-dx-suggestion-code">{{ item.codigo }}</span>
                      <span class="rf-dx-suggestion-desc">{{ item.descripcion }}</span>
                    </template>
                  </el-autocomplete>
                  <button v-if="diagnosticos.length > 1" type="button" class="rf-dx-remove" @click="quitarDiagnostico(i)">
                    <component :is="XIcon" class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
              <button type="button" class="rf-attach-btn rf-add-dx-btn" @click="agregarDiagnostico">
                <component :is="PlusIcon" class="w-4 h-4" />
                <span>Agregar diagnóstico</span>
              </button>
              <p v-if="diagnosticos.length === 0" class="rf-dx-empty">Agregue al menos un diagnóstico.</p>
            </div>

            <el-form-item label="Historia clínica" prop="resumen_historia_clinica" required :class="['rf-field rf-col-3 rf-stagger-5', { 'is-success': camposValidos.resumen_historia_clinica }]" style="margin-top: 8px;">
              <el-input v-model="form.resumen_historia_clinica" type="textarea" :rows="3"
                placeholder="Motivo de remisión, antecedentes, estado actual..." @input="capitalizar('resumen_historia_clinica')" />
            </el-form-item>
          </div>

          <!-- Sub-sección: Soportes (adjuntos, opcional) -->
          <div class="rf-subsection">
            <div class="rf-subsection-header">
              <div class="rf-subsection-icon rf-sub-icon-purple">
                <component :is="PaperclipIcon" class="w-3.5 h-3.5" />
              </div>
              <h4>Soportes</h4>
              <span class="rf-subsection-badge rf-badge-blue">Opcional</span>
            </div>
            <div class="rf-attach" :class="{ 'rf-attach-active': dragOver }" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop">
              <div v-if="adjuntos.length" class="rf-attach-header">
                <component :is="PaperclipIcon" class="w-3.5 h-3.5" />
                <span>Archivos adjuntos</span>
                <span class="rf-attach-count">{{ adjuntos.length }}</span>
              </div>
              <div class="rf-dropzone" @click="fileInput?.click()">
                <component :is="UploadCloudIcon" class="w-5 h-5 mx-auto" />
                <p>Arrastre archivos aquí o haga clic para adjuntar</p>
                <small>Máx. {{ MAX_ARCHIVOS }} archivos · PDF, imágenes, Word, Excel... {{ MAX_ARCHIVO_MB }}MB c/u</small>
              </div>
              <input ref="fileInput" class="hidden" type="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.bmp,.tiff,.svg,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar" multiple @change="seleccionarAdjuntos" />
              <transition-group v-if="adjuntos.length" name="file-list" tag="div" class="rf-file-mini-grid">
                <div v-for="(archivo, i) in adjuntos" :key="archivo.name + i" class="rf-file-mini-item">
                  <img v-if="esImagen(archivo)" :src="previewUrl(archivo)" class="rf-file-mini-thumb" :alt="archivo.name" @click.stop="abrirLightbox(archivo)" />
                  <span v-else class="rf-file-mini-badge rf-file-mini-badge-click" :class="getFileTypeClass(archivo)" title="Abrir archivo" @click.stop="abrirArchivo(archivo)">{{ getFileTypeLabel(archivo) }}</span>
                  <span class="rf-file-mini-name" :title="archivo.name">{{ archivo.name }}</span>
                  <button type="button" class="rf-file-mini-remove" @click.stop="removerAdjunto(i)">
                    <component :is="XCircleIcon" class="w-3 h-3" />
                  </button>
                </div>
              </transition-group>
            </div>
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

      <!-- Resumen en vivo -->
      <aside class="rf-summary-card">
        <div class="rf-summary-header">
          <div class="rf-summary-icon"><component :is="FileCheckIcon" class="w-4 h-4" /></div>
          <h3>Resumen</h3>
        </div>

        <div class="rf-summary-progress">
          <div class="rf-summary-progress-bar">
            <div
              class="rf-summary-progress-fill"
              :class="{ 'rf-progress-complete': progresoFormulario === 100 }"
              :style="{ width: progresoFormulario + '%' }"
            ></div>
          </div>
          <div class="rf-summary-progress-info">
            <span class="rf-summary-progress-text">{{ progresoFormulario }}% completado</span>
            <span v-if="progresoFormulario === 100" class="rf-summary-progress-done">
              <component :is="CheckCircleIcon" class="w-3 h-3" /> Listo
            </span>
          </div>
        </div>

        <div class="rf-summary-section">
          <div class="rf-summary-section-header">
            <p class="rf-summary-section-title">Paciente</p>
            <span class="rf-summary-section-dot" :class="resumenPacienteCompleto ? 'dot-green' : 'dot-amber'"></span>
          </div>
          <div class="rf-summary-field"><span>Nombre</span><strong>{{ nombreCompleto }}</strong></div>
          <div class="rf-summary-field"><span>Documento</span><strong>{{ form.tipo_documento || '—' }} {{ form.numero_documento }}</strong></div>
          <div class="rf-summary-field"><span>Edad / Género</span><strong>{{ form.edad ?? '—' }} · {{ form.genero === 'M' ? 'Masc.' : form.genero === 'F' ? 'Fem.' : '—' }}</strong></div>
          <div class="rf-summary-field"><span>EPS</span><strong>{{ form.eps || '—' }}</strong></div>
        </div>

        <div class="rf-summary-section">
          <div class="rf-summary-section-header">
            <p class="rf-summary-section-title">Remisión</p>
            <span class="rf-summary-section-dot" :class="resumenRemisionCompleto ? 'dot-green' : 'dot-amber'"></span>
          </div>
          <div class="rf-summary-field"><span>Especialidad</span><strong>{{ form.especialidad_requerida || '—' }}</strong></div>
          <div class="rf-summary-field"><span>Servicio actual</span><strong>{{ form.servicio_ubicacion_actual || '—' }}</strong></div>
          <div class="rf-summary-field"><span>Diagnóstico</span><strong>{{ diagnosticosResumen }}</strong></div>
        </div>

        <div class="rf-summary-section">
          <div class="rf-summary-section-header">
            <p class="rf-summary-section-title">Soportes</p>
          </div>
          <div class="rf-summary-field"><span>Archivos adjuntos</span><strong>{{ adjuntos.length || 'Ninguno' }}</strong></div>
        </div>
      </aside>
    </div>

    <!-- Footer -->
    <div class="rf-footer">
      <el-button class="rf-btn-back" @click="router.push('/clinica/dashboard')">Cancelar</el-button>
      <el-button type="primary" class="rf-btn-submit" @click="abrirConfirmacion">Enviar solicitud</el-button>
    </div>

    <!-- Dialog de confirmación -->
    <el-dialog
      v-model="showConfirmResumen"
      width="800px"
      class="confirm-dialog"
      :close-on-click-modal="false"
      :show-close="false"
      append-to-body
      align-center
    >
      <div class="confirm-body">
        <button type="button" class="confirm-close-btn" @click="showConfirmResumen = false">
          <component :is="XIcon" class="w-4 h-4" />
        </button>

        <div class="confirm-hero">
          <div class="confirm-icon-wrap">
            <component :is="CheckCircleIcon" class="w-6 h-6" />
          </div>
          <div class="confirm-hero-text">
            <h3 class="confirm-title">¿Confirmar envío?</h3>
            <p class="confirm-subtitle">Revisa el resumen de la solicitud antes de enviarla.</p>
          </div>
        </div>

        <div class="confirm-columns">
          <div class="confirm-main">
            <!-- Tarjeta paciente -->
            <div class="confirm-patient-card">
              <div class="confirm-patient-avatar"><component :is="UserIcon" class="w-5 h-5" /></div>
              <div class="confirm-patient-info">
                <strong>{{ nombreCompleto }}</strong>
                <span>{{ form.edad ?? '—' }} años · {{ form.genero === 'M' ? 'Masculino' : form.genero === 'F' ? 'Femenino' : '—' }} · {{ form.tipo_documento }} {{ form.numero_documento }}</span>
              </div>
            </div>

            <!-- Detalles de la solicitud -->
            <div class="confirm-detail-card">
              <p class="confirm-detail-title"><span class="confirm-detail-bar"></span>Detalles de la solicitud</p>

              <div class="confirm-mini-stats">
                <div class="confirm-mini-stat">
                  <div class="confirm-mini-stat-icon confirm-mini-stat-blue"><component :is="ShieldIcon" class="w-3.5 h-3.5" /></div>
                  <div><span>EPS</span><strong>{{ form.eps || '—' }}</strong></div>
                </div>
                <div class="confirm-mini-stat">
                  <div class="confirm-mini-stat-icon confirm-mini-stat-green"><component :is="StethoscopeIcon" class="w-3.5 h-3.5" /></div>
                  <div><span>Especialidad</span><strong>{{ form.especialidad_requerida || '—' }}</strong></div>
                </div>
                <div class="confirm-mini-stat">
                  <div class="confirm-mini-stat-icon confirm-mini-stat-blue"><component :is="MapPinIcon" class="w-3.5 h-3.5" /></div>
                  <div><span>Servicio actual</span><strong>{{ form.servicio_ubicacion_actual || '—' }}</strong></div>
                </div>
              </div>

              <div class="confirm-detail-divider"></div>

              <div class="confirm-detail-block">
                <p class="confirm-detail-label"><component :is="FileTextIcon" class="w-3.5 h-3.5" /> Diagnóstico principal</p>
                <strong class="confirm-detail-value">{{ diagnosticoPrincipalTexto }}</strong>
              </div>

              <div class="confirm-detail-row-2">
                <div class="confirm-detail-block">
                  <p class="confirm-detail-label"><component :is="CalendarIcon" class="w-3.5 h-3.5" /> Fecha de solicitud</p>
                  <strong>{{ fechaSolicitudTexto }}</strong>
                </div>
                <div class="confirm-detail-block">
                  <p class="confirm-detail-label"><component :is="BuildingIcon" class="w-3.5 h-3.5" /> Institución remitente</p>
                  <strong>{{ clinicaAuth.clinica?.nombre || '—' }}</strong>
                </div>
              </div>

              <div class="confirm-detail-block">
                <p class="confirm-detail-label"><component :is="FileTextIcon" class="w-3.5 h-3.5" /> Historia clínica</p>
                <strong class="confirm-clamp">{{ form.resumen_historia_clinica || '—' }}</strong>
              </div>
            </div>

            <!-- Quien remite -->
            <div class="confirm-remite-card">
              <div class="confirm-remite-item">
                <div class="confirm-remite-icon"><component :is="UserCheckIcon" class="w-4 h-4" /></div>
                <div><span>Quien remite</span><strong>{{ form.quien_remitente || '—' }}</strong></div>
              </div>
              <div class="confirm-remite-item">
                <div class="confirm-remite-icon"><component :is="PhoneIcon" class="w-4 h-4" /></div>
                <div><span>Teléfono</span><strong>{{ form.telefono_contacto || '—' }}</strong></div>
              </div>
              <div class="confirm-remite-item">
                <div class="confirm-remite-icon"><component :is="MailIcon" class="w-4 h-4" /></div>
                <div><span>Correo</span><strong>{{ form.correo_contacto || '—' }}</strong></div>
              </div>
            </div>
          </div>

          <aside class="confirm-side">
            <div class="confirm-adjuntos-card">
              <p class="confirm-side-title">
                <component :is="PaperclipIcon" class="w-3.5 h-3.5" />
                Adjuntos ({{ adjuntos.length }})
              </p>
              <div v-if="adjuntos.length" class="confirm-adjuntos-grid-big">
                <div v-for="(archivo, i) in adjuntos" :key="archivo.name + i" class="confirm-adjunto-item-big">
                  <img v-if="esImagen(archivo)" :src="previewUrl(archivo)" class="confirm-adjunto-thumb-big" :alt="archivo.name" @click="abrirLightbox(archivo)" />
                  <div v-else class="confirm-adjunto-badge-big" :class="getFileTypeClass(archivo)">{{ getFileTypeLabel(archivo) }}</div>
                  <span class="confirm-adjunto-name-big" :title="archivo.name">{{ archivo.name }}</span>
                </div>
              </div>
              <p v-else class="confirm-side-empty">Sin archivos adjuntos</p>
            </div>

            <div class="confirm-quick-card">
              <p class="confirm-quick-title"><component :is="ListIcon" class="w-3.5 h-3.5" /> Resumen rápido</p>
              <div class="confirm-quick-grid">
                <div class="confirm-quick-item"><span class="confirm-dot confirm-dot-1"></span><div><span>Paciente</span><strong>{{ nombreCompleto }}</strong></div></div>
                <div class="confirm-quick-item"><span class="confirm-dot confirm-dot-2"></span><div><span>Especialidad</span><strong>{{ form.especialidad_requerida || '—' }}</strong></div></div>
                <div class="confirm-quick-item"><span class="confirm-dot confirm-dot-3"></span><div><span>Documento</span><strong>{{ form.tipo_documento }} {{ form.numero_documento }}</strong></div></div>
                <div class="confirm-quick-item"><span class="confirm-dot confirm-dot-4"></span><div><span>Servicio actual</span><strong>{{ form.servicio_ubicacion_actual || '—' }}</strong></div></div>
                <div class="confirm-quick-item"><span class="confirm-dot confirm-dot-4"></span><div><span>EPS</span><strong>{{ form.eps || '—' }}</strong></div></div>
                <div class="confirm-quick-item confirm-quick-item-full"><span class="confirm-dot confirm-dot-5"></span><div><span>Diagnóstico principal</span><strong>{{ diagnosticoPrincipalTexto }}</strong></div></div>
              </div>
            </div>
          </aside>
        </div>
      </div>

      <template #footer>
        <div class="confirm-footer">
          <el-button class="rf-btn-back" @click="showConfirmResumen = false">
            <component :is="XIcon" class="w-3.5 h-3.5 mr-1" /> Cancelar
          </el-button>
          <p class="confirm-footer-note">
            <component :is="ShieldIcon" class="w-3.5 h-3.5" />
            Tu información está protegida y será tratada confidencialmente.
          </p>
          <el-button type="primary" class="rf-btn-submit confirm-submit-btn" :loading="guardando" @click="guardar">
            <component :is="SendIcon" class="w-3.5 h-3.5 mr-1" /> Confirmar y enviar
          </el-button>
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

    <!-- Lightbox: vista ampliada de imagen adjunta -->
    <Teleport to="body">
      <transition name="fade-scale">
        <div v-if="lightboxSrc" class="rf-lightbox" @click="cerrarLightbox">
          <button type="button" class="rf-lightbox-close" @click.stop="cerrarLightbox">
            <component :is="XIcon" class="w-4 h-4" />
          </button>
          <img :src="lightboxSrc" :alt="lightboxName" class="rf-lightbox-img" @click.stop />
          <p class="rf-lightbox-name">{{ lightboxName }}</p>
        </div>
      </transition>
    </Teleport>

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
  Shield as ShieldIcon,
  Calendar as CalendarIcon,
  List as ListIcon,
  Send as SendIcon,
  Building2 as BuildingIcon,
} from '@lucide/vue';
import http from '@/plugins/axios';
import { TIPOS_DOCUMENTO, EPS_LIST, ESPECIALIDADES, SERVICIOS, SERVICIOS_REMISION_GOMEDISYS } from '@/data/referencia';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const clinicaAuth = useClinicaAuthStore();
const guardando = ref(false);
const showConfirmResumen = ref(false);
const fechaSolicitud = ref<Date | null>(null);
const formRef = ref();
const adjuntos = ref<File[]>([]);
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

interface DiagnosticoItem {
  codigo_cie10: string;
  descripcion: string;
}

const diagnosticos = ref<DiagnosticoItem[]>([{ codigo_cie10: '', descripcion: '' }]);

function agregarDiagnostico() {
  diagnosticos.value.push({ codigo_cie10: '', descripcion: '' });
}

function seleccionarGenero(valor: 'M' | 'F') {
  form.value.genero = valor;
  formRef.value?.validateField('genero');
}

function quitarDiagnostico(i: number) {
  diagnosticos.value.splice(i, 1);
}

/** Catálogo completo de municipios para la lista desplegable de "Municipio"
 * (~1.100 municipios de Colombia, importado en la tabla ciudades_gomedisys
 * desde la propia tabla generalPoliticalDivisions de Gomedisys) — se carga
 * una sola vez al abrir el formulario. Solo se puede elegir de esta lista
 * (no texto libre), así el valor que se envía siempre viene en el formato
 * exacto "Municipio, Departamento" que Gomedisys reconoce. */
const municipiosOpciones = ref<{ value: string; label: string }[]>([]);

async function cargarMunicipios() {
  try {
    const { data } = await http.get('/api/externo/ciudades-gomedisys');
    municipiosOpciones.value = (data.data as { nombre: string }[]).map(c => ({ value: c.nombre, label: c.nombre }));
  } catch {
    municipiosOpciones.value = [];
  }
}

/** Sugerencias CIE-10 para el autocompletado del diagnóstico. El campo
 * siempre acepta texto libre (v-model directo) — esto solo ofrece atajos.
 * Busca contra el catálogo real de Gomedisys (~12.500 códigos específicos,
 * importado en la tabla diagnosticos_cie10) en vez de una lista fija en el
 * cliente — así el código que se elija siempre existe allá. */
async function buscarSugerenciasCIE10(query: string, callback: (results: any[]) => void) {
  const q = query.trim();
  if (!q) { callback([]); return; }
  try {
    const { data } = await http.get('/api/externo/diagnosticos-cie10', { params: { buscar: q } });
    const resultados = (data.data as { codigo: string; descripcion: string }[])
      .map(c => ({ value: `${c.codigo} — ${c.descripcion}`, codigo: c.codigo, descripcion: c.descripcion }));
    callback(resultados);
  } catch {
    callback([]);
  }
}

function seleccionarSugerenciaCIE10(i: number, item: { codigo: string; descripcion: string }) {
  diagnosticos.value[i].codigo_cie10 = item.codigo;
  // El código queda dentro del mismo texto, al inicio — así se ve en la
  // misma barra sin necesitar una insignia aparte al lado.
  diagnosticos.value[i].descripcion = `${item.codigo} — ${item.descripcion}`;
}

/** Si se escribió el texto exacto de un diagnóstico del catálogo pero no se
 * le dio clic a la sugerencia (por ejemplo, tecleando Enter), el código
 * quedaría sin asociar — al salir del campo se busca una coincidencia
 * exacta (sin importar mayúsculas) y se completa igual, anteponiendo el
 * código al texto. */
async function verificarCodigoCIE10(i: number) {
  const dx = diagnosticos.value[i];
  if (dx.codigo_cie10 || !dx.descripcion.trim()) return;
  const texto = dx.descripcion.trim().toLowerCase();
  try {
    const { data } = await http.get('/api/externo/diagnosticos-cie10', { params: { buscar: dx.descripcion.trim() } });
    const match = (data.data as { codigo: string; descripcion: string }[]).find(c => c.descripcion.toLowerCase() === texto);
    if (match) {
      dx.codigo_cie10 = match.codigo;
      dx.descripcion = `${match.codigo} — ${match.descripcion}`;
    }
  } catch { /* deja el texto libre tal cual si la búsqueda falla */ }
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
    eps: '', municipio_capita: '', direccion_paciente: '', telefono_paciente: '', especialidad_requerida: '',
    servicio_ubicacion_actual: '', servicio_remision: '', quien_remitente: '', telefono_contacto: '', correo_contacto: '', via_contacto: '',
    gestante: null as boolean | null, condicion_especial: '',
    resumen_historia_clinica: '', observaciones: '',
  };
}

const form = ref(emptyForm());

onMounted(() => {
  cargarMunicipios();
});

function capitalizar(campo: 'primer_nombre' | 'segundo_nombre' | 'primer_apellido' | 'segundo_apellido' | 'resumen_historia_clinica' | 'condicion_especial' | 'quien_remitente' | 'observaciones') {
  const val = form.value[campo];
  if (val && val.length === 1) {
    form.value[campo] = val.charAt(0).toUpperCase();
  } else if (val && val.length > 1) {
    form.value[campo] = val.charAt(0).toUpperCase() + val.slice(1);
  }
}

/** Igual que la restricción `isNumber` del campo real en Gomedisys — solo deja dígitos. */
function soloNumeros(campo: 'telefono_paciente') {
  form.value[campo] = form.value[campo].replace(/\D/g, '');
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
  const dxCount = diagnosticos.value.filter(d => d.descripcion.trim()).length;
  const dxBonus = dxCount > 0 ? 1 : 0;
  return Math.min(100, Math.round(((llenos + dxBonus) / (campos.length + 1)) * 100));
});

const camposValidos = computed(() => {
  const required = ['tipo_documento', 'numero_documento', 'primer_nombre', 'primer_apellido',
    'genero', 'edad', 'municipio_capita', 'eps',
    'especialidad_requerida', 'servicio_ubicacion_actual', 'resumen_historia_clinica',
    'servicio_remision', 'via_contacto', 'gestante', 'condicion_especial', 'direccion_paciente', 'telefono_paciente'];
  const valid: Record<string, boolean> = {};
  for (const c of required) {
    const v = form.value[c as keyof typeof form.value];
    valid[c] = v !== null && v !== '' && v !== undefined;
  }
  return valid;
});

/** Texto de los diagnósticos válidos (con descripción), para el panel de resumen. */
const diagnosticosResumen = computed(() => {
  const validos = diagnosticos.value.filter(d => d.descripcion.trim());
  return validos.length ? validos.map(d => d.descripcion).join(', ') : '—';
});

/** Diagnóstico principal (el primero) para el modal de confirmación, con conteo de adicionales. */
const diagnosticoPrincipalTexto = computed(() => {
  const validos = diagnosticos.value.filter(d => d.descripcion.trim());
  if (!validos.length) return '—';
  // El código ya viene incluido dentro de d.descripcion (ver
  // seleccionarSugerenciaCIE10/verificarCodigoCIE10) — no se antepone otra vez.
  const primero = validos[0].descripcion;
  return validos.length > 1 ? `${primero} (y ${validos.length - 1} más)` : primero;
});

const fechaSolicitudTexto = computed(() => {
  if (!fechaSolicitud.value) return '—';
  const fecha = fechaSolicitud.value.toLocaleDateString('es-CO', { day: 'numeric', month: 'long', year: 'numeric' });
  const hora = fechaSolicitud.value.toLocaleTimeString('es-CO', { hour: 'numeric', minute: '2-digit', hour12: true });
  return `${fecha} - ${hora}`;
});

const resumenPacienteCompleto = computed(() =>
  ['tipo_documento', 'numero_documento', 'primer_nombre', 'primer_apellido', 'genero', 'edad', 'municipio_capita', 'eps']
    .every(c => camposValidos.value[c]),
);

const resumenRemisionCompleto = computed(() =>
  camposValidos.value.especialidad_requerida
  && camposValidos.value.servicio_ubicacion_actual
  && camposValidos.value.resumen_historia_clinica
  && diagnosticos.value.some(d => d.descripcion.trim()),
);

const rules = {
  primer_nombre: [{ required: true, message: 'El primer nombre es requerido', trigger: 'blur' }],
  primer_apellido: [{ required: true, message: 'El primer apellido es requerido', trigger: 'blur' }],
  genero: [{ required: true, message: 'Seleccione el género del paciente', trigger: 'change' }],
  edad: [{ required: true, message: 'La edad es requerida', trigger: 'blur' }],
  tipo_documento: [{ required: true, message: 'Seleccione el tipo de documento', trigger: 'change' }],
  numero_documento: [{ required: true, message: 'El número de documento es requerido', trigger: 'blur' }],
  eps: [{ required: true, message: 'Seleccione la EPS / aseguradora', trigger: 'change' }],
  municipio_capita: [{ required: true, message: 'Seleccione el municipio', trigger: 'change' }],
  especialidad_requerida: [{ required: true, message: 'Seleccione la especialidad requerida', trigger: 'change' }],
  servicio_ubicacion_actual: [{ required: true, message: 'Seleccione el servicio / ubicación actual', trigger: 'change' }],
  resumen_historia_clinica: [{ required: true, message: 'La historia clínica es requerida', trigger: 'blur' }],
  quien_remitente: [{ required: true, message: 'Indique quién remite al paciente', trigger: 'blur' }],
  telefono_contacto: [{ required: true, message: 'El teléfono de contacto es obligatorio', trigger: 'blur' }],
  correo_contacto: [
    { required: true, message: 'El correo de contacto es obligatorio', trigger: 'blur' },
    { type: 'email', message: 'El correo de contacto no es válido', trigger: 'blur' },
  ],
};

/** Etiquetas legibles para mostrar en mensajes de error específicos. */
const CAMPO_LABELS: Record<string, string> = {
  primer_nombre: 'Primer nombre',
  primer_apellido: 'Primer apellido',
  genero: 'Género',
  edad: 'Edad',
  tipo_documento: 'Tipo de documento',
  numero_documento: 'Número de documento',
  eps: 'EPS / Aseguradora',
  municipio_capita: 'Municipio',
  especialidad_requerida: 'Especialidad requerida',
  servicio_ubicacion_actual: 'Servicio / Ubicación actual',
  resumen_historia_clinica: 'Historia clínica',
  quien_remitente: 'Quien remite',
  telefono_contacto: 'Teléfono de contacto',
  correo_contacto: 'Correo de contacto',
};

const MAX_ARCHIVO_MB = 10;
const MAX_ARCHIVOS = 2;

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
  const restantes = MAX_ARCHIVOS - adjuntos.value.length;
  if (restantes <= 0) {
    notify.warning(`Máximo ${MAX_ARCHIVOS} archivos permitidos. Elimine alguno para adjuntar otro.`);
    return;
  }

  const maxBytes = MAX_ARCHIVO_MB * 1024 * 1024;
  const demasiadoPesados = files.filter(f => f.size > maxBytes);
  const validos = files.filter(f => f.size <= maxBytes);

  if (demasiadoPesados.length) {
    notify.error(
      `${demasiadoPesados.length === 1 ? 'Este archivo supera' : 'Estos archivos superan'} el máximo de ${MAX_ARCHIVO_MB}MB: `
      + demasiadoPesados.map(f => `${f.name} (${(f.size / 1024 / 1024).toFixed(1)}MB)`).join(', '),
    );
  }

  const nuevos = validos.slice(0, restantes);
  if (validos.length > restantes) {
    notify.warning(`Solo se agregaron ${restantes} de ${validos.length} archivos válidos (límite ${MAX_ARCHIVOS} en total)`);
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

const IMAGENES_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'svg'];

function esImagen(file: File): boolean {
  const ext = file.name.split('.').pop()?.toLowerCase() ?? '';
  return IMAGENES_EXT.includes(ext);
}

const previewUrlCache = new WeakMap<File, string>();

/** Genera (y reutiliza) una URL local para previsualizar una imagen adjunta sin subirla todavía. */
function previewUrl(file: File): string {
  let url = previewUrlCache.get(file);
  if (!url) {
    url = URL.createObjectURL(file);
    previewUrlCache.set(file, url);
  }
  return url;
}

const lightboxSrc = ref<string | null>(null);
const lightboxName = ref('');

function abrirLightbox(file: File): void {
  if (!esImagen(file)) return;
  lightboxSrc.value = previewUrl(file);
  lightboxName.value = file.name;
}

/** Abre PDF/Word/Excel/otros en una pestaña nueva usando el archivo local
 * (aún no subido). El navegador renderiza los PDF de forma nativa; el resto
 * se abre o descarga con la app asociada del sistema — es el máximo de
 * "previsualización" posible antes de enviar el formulario. */
function abrirArchivo(file: File): void {
  window.open(previewUrl(file), '_blank', 'noopener');
}

function cerrarLightbox(): void {
  lightboxSrc.value = null;
  lightboxName.value = '';
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
  const validDx = diagnosticos.value.filter(d => d.descripcion.trim());
  if (validDx.length === 0) {
    notify.error('Agregue al menos un diagnóstico: busque por código (ej: J18) o escriba directamente el síntoma o nombre del diagnóstico.');
    return;
  }

  formRef.value?.validate((valid: boolean, invalidFields?: Record<string, Array<{ message?: string }>>) => {
    if (valid) {
      fechaSolicitud.value = new Date();
      showConfirmResumen.value = true;
      return;
    }
    const nombres = Object.keys(invalidFields ?? {}).map(campo => CAMPO_LABELS[campo] ?? campo);
    notify.error(
      nombres.length
        ? `Faltan campos por completar: ${nombres.join(', ')}.`
        : 'Por favor complete todos los campos requeridos.',
    );
  });
}

const modalExito = ref(false);
const ultimoEnviado = ref({
  paciente: '', documento: '', especialidad: '', diagnosticos: '', servicio: '', adjuntos: '',
});

function onCerrarExito() {
  ultimoEnviado.value = { paciente: '', documento: '', especialidad: '', diagnosticos: '', servicio: '', adjuntos: '' };
}

async function guardar() {
  const invalidFields = await new Promise<Record<string, Array<{ message?: string }>> | undefined>((resolve) => {
    formRef.value?.validate((valid: boolean, fields?: Record<string, Array<{ message?: string }>>) => resolve(valid ? undefined : fields));
  });
  if (invalidFields) {
    const nombres = Object.keys(invalidFields).map(campo => CAMPO_LABELS[campo] ?? campo);
    notify.error(nombres.length ? `Faltan campos por completar: ${nombres.join(', ')}.` : 'Por favor complete todos los campos requeridos.');
    return;
  }
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
    const validDx = diagnosticos.value.filter(d => d.descripcion.trim());
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
      // El código ya puede venir dentro de d.descripcion (diagnósticos elegidos
      // del catálogo) — solo se antepone si todavía no está ahí.
      diagnosticos: validDx.map(d => (d.codigo_cie10 && !d.descripcion.startsWith(d.codigo_cie10)) ? `${d.codigo_cie10} — ${d.descripcion}` : d.descripcion).join('\n') || '—',
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
    if (errors) {
      notify.error(Object.values(errors).flat().join('\n'));
    } else if (e.response?.status === 413) {
      notify.error('Los archivos adjuntos superan el tamaño máximo permitido por el servidor. Reduzca el tamaño o la cantidad e intente de nuevo.');
    } else if (e.response?.status >= 500) {
      notify.error('El servidor tuvo un problema al procesar la solicitud. Intente de nuevo en unos minutos.');
    } else if (!e.response) {
      notify.error('No se pudo conectar con el servidor. Verifique su conexión a internet e intente de nuevo.');
    } else {
      notify.error(e.response?.data?.message || 'Error al enviar la solicitud');
    }
  } finally { guardando.value = false; }
}
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

.rf-summary-card {
  flex-shrink: 0;
  width: 260px;
  height: 100%;
  overflow-y: auto;
}
@media (max-width: 1150px) {
  .rf-summary-card { display: none; }
}

/* ── Form overrides ── */
:deep(.rf-form-col .el-form) { display: flex; flex-direction: column; gap: .75rem; }
:deep(.rf-form-col .el-form-item) { margin-bottom: 0; display: flex; flex-direction: column; }
:deep(.rf-form-col .el-form-item__label) {
  color: #475569; font-size: .74rem; font-weight: 600;
  padding-bottom: .2rem; line-height: 1.2;
  min-height: calc(1.2em * 2 + .2rem);
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
.dark .rf-dx-block { background: linear-gradient(135deg, #1e293b, #172033); border-color: #334155; }
.dark .rf-dx-title { color: #93c5fd; }
.dark .rf-dx-add { border-color: #3b82f6 !important; background: #1e3a5f !important; color: #93c5fd !important; }
.dark .rf-dx-label { color: #94a3b8; }
.dark .rf-dx-suggestion-code { color: #93c5fd; }
.dark .rf-dx-suggestion-desc { color: #94a3b8; }
.dark .rf-dx-empty { color: #64748b; }
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

/* ── Diagnósticos: lista vertical ── */
.rf-dx-inline-row { display: flex; align-items: center; gap: .3rem; width: 100%; }
.rf-dx-inline-row .el-input { flex: 1; }
.rf-dx-section { margin-top: .5rem; }
.rf-dx-label {
  display: block;
  color: #475569; font-size: .74rem; font-weight: 600;
  padding-bottom: .3rem;
}
.rf-dx-row-block { margin-bottom: .45rem; }
.rf-dx-row-block:last-of-type { margin-bottom: .55rem; }
.rf-dx-suggestion-code { font-weight: 700; color: #0D2D6B; margin-right: .4rem; }
.rf-dx-suggestion-desc { color: #475569; }

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
.rf-add-dx-btn { width: auto; padding: 0 16px; }
.dark .rf-attach-btn { background: #1e293b; border-color: #334155; color: #93c5fd; }
.dark .rf-attach-btn:hover { background: #253449; border-color: #3b82f6; }

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
.dark .rf-gestante-btn { background: #1e293b; border-color: #334155; color: #94a3b8; }
.dark .rf-gestante-btn:hover { border-color: #60a5fa; color: #93c5fd; }
.dark .rf-gestante-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }

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
.ft-pdf { background: #FEE2E2; color: #DC2626; }
.ft-img { background: #DBEAFE; color: #2563EB; }
.ft-doc { background: #DBEAFE; color: #1E40AF; }
.ft-xls { background: #DCFCE7; color: #15803D; }
.ft-ppt { background: #FED7AA; color: #C2410C; }
.ft-zip { background: #F3E8FF; color: #7C3AED; }
.ft-default { background: #F1F5F9; color: #475569; }

/* ── Vista previa pequeña de adjuntos ── */
.rf-file-mini-grid {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-top: .5rem;
}
.rf-file-mini-item {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .2rem;
  width: 56px;
}
.rf-file-mini-thumb {
  width: 48px; height: 48px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #BFDBFE;
  background: #fff;
  cursor: zoom-in;
  transition: transform .15s ease;
}
.rf-file-mini-thumb:hover { transform: scale(1.08); }
.rf-file-mini-badge {
  width: 48px; height: 48px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 8px;
  font-size: .56rem; font-weight: 800;
}
.rf-file-mini-badge-click { cursor: pointer; transition: transform .15s ease; }
.rf-file-mini-badge-click:hover { transform: scale(1.08); }
.rf-file-mini-name {
  font-size: .52rem;
  font-weight: 600;
  color: #475569;
  max-width: 56px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: center;
}
.rf-file-mini-remove {
  position: absolute;
  top: -5px; right: -5px;
  display: grid;
  place-items: center;
  width: 16px; height: 16px;
  border-radius: 50%;
  background: #fff;
  color: #94A3B8;
  border: 1px solid #E2E8F0;
  cursor: pointer;
  padding: 0;
  transition: all .2s ease;
}
.rf-file-mini-remove:hover { color: #DC2626; border-color: #FCA5A5; background: #FEF2F2; }

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
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(13, 45, 107, .25);
}
:deep(.confirm-dialog .el-dialog__header) { display: none; }
:deep(.confirm-dialog .el-dialog__body) { padding: 0; overflow: hidden; }
:deep(.confirm-dialog .el-dialog__footer) { padding: 0; }
.confirm-body { padding: 0; position: relative; background: #F8FAFC; overflow: hidden; }

.confirm-close-btn {
  position: absolute;
  top: .8rem; right: .9rem;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 28px; height: 28px;
  border-radius: 50%;
  border: 1px solid #E2E8F0;
  background: #fff;
  color: #94A3B8;
  cursor: pointer;
  transition: all .2s ease;
}
.confirm-close-btn:hover { color: #DC2626; border-color: #FCA5A5; background: #FEF2F2; }

.confirm-hero {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .9rem 2.6rem .7rem 1.1rem;
  background: #fff;
}
.confirm-icon-wrap {
  display: grid;
  place-items: center;
  width: 38px; height: 38px;
  flex-shrink: 0;
  border-radius: 50%;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  color: #fff;
  box-shadow: 0 4px 12px rgba(79, 70, 229, .35);
}
.confirm-hero-text { min-width: 0; }
.confirm-title { margin: 0; font-size: .92rem; font-weight: 800; color: #0F172A; }
.confirm-subtitle { margin: 0; font-size: .68rem; color: #64748B; }

.confirm-columns {
  display: flex;
  align-items: flex-start;
  gap: .65rem;
  padding: 0 1.1rem .85rem;
  min-width: 0;
}
.confirm-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: .45rem;
}

/* Tarjeta paciente */
.confirm-patient-card {
  display: flex;
  align-items: center;
  gap: .55rem;
  padding: .55rem .75rem;
  border-radius: 12px;
  background: linear-gradient(120deg, #4338CA 0%, #6D28D9 100%);
  box-shadow: 0 6px 16px rgba(79, 70, 229, .22);
  min-width: 0;
}
.confirm-patient-avatar {
  display: grid;
  place-items: center;
  width: 32px; height: 32px;
  flex-shrink: 0;
  border-radius: 50%;
  background: rgba(255,255,255,.18);
  color: #fff;
}
.confirm-patient-info { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.confirm-patient-info strong { font-size: .78rem; font-weight: 800; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.confirm-patient-info span { font-size: .62rem; color: rgba(255,255,255,.78); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Tarjeta detalles */
.confirm-detail-card {
  background: #fff;
  border: 1px solid #E2E8F0;
  border-radius: 12px;
  padding: .65rem .75rem;
  min-width: 0;
}
.confirm-detail-title {
  display: flex;
  align-items: center;
  gap: .4rem;
  margin: 0 0 .5rem;
  font-size: .72rem;
  font-weight: 800;
  color: #0F172A;
}
.confirm-detail-bar {
  width: 3px; height: 12px;
  border-radius: 2px;
  background: linear-gradient(180deg, #4F46E5, #7C3AED);
  flex-shrink: 0;
}
.confirm-mini-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .4rem;
}
.confirm-mini-stat {
  display: flex;
  align-items: center;
  gap: .3rem;
  min-width: 0;
}
.confirm-mini-stat-icon {
  display: grid;
  place-items: center;
  width: 24px; height: 24px;
  flex-shrink: 0;
  border-radius: 50%;
}
.confirm-mini-stat-blue { background: #DBEAFE; color: #2563EB; }
.confirm-mini-stat-green { background: #DCFCE7; color: #16A34A; }
.confirm-mini-stat > div { min-width: 0; display: flex; flex-direction: column; }
.confirm-mini-stat span { font-size: .52rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: .02em; }
.confirm-mini-stat strong { font-size: .64rem; font-weight: 700; color: #1E293B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.confirm-detail-divider { height: 1px; background: #F1F5F9; margin: .55rem 0; }
.confirm-detail-block { margin-bottom: .45rem; min-width: 0; }
.confirm-detail-block:last-child { margin-bottom: 0; }
.confirm-detail-label {
  display: flex; align-items: center; gap: .25rem;
  margin: 0 0 .15rem;
  font-size: .56rem; font-weight: 700; color: #94A3B8;
  text-transform: uppercase; letter-spacing: .02em;
}
.confirm-detail-value, .confirm-detail-block strong { font-size: .68rem; font-weight: 700; color: #1E293B; word-break: break-word; }
.confirm-detail-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .4rem .6rem;
  margin-bottom: .45rem;
  min-width: 0;
}
.confirm-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Quien remite */
.confirm-remite-card {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .4rem;
  padding: .55rem .7rem;
  border-radius: 10px;
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  min-width: 0;
}
.confirm-remite-item {
  display: flex;
  align-items: center;
  gap: .4rem;
  min-width: 0;
}
.confirm-remite-icon {
  display: grid; place-items: center;
  width: 26px; height: 26px;
  border-radius: 50%;
  background: #FEF3C7; color: #D97706;
  flex-shrink: 0;
}
.confirm-remite-item > div { min-width: 0; }
.confirm-remite-card span { display: block; font-size: .52rem; font-weight: 700; color: #B45309; text-transform: uppercase; letter-spacing: .02em; }
.confirm-remite-card strong { font-size: .66rem; font-weight: 800; color: #92400E; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }

/* Columna derecha */
.confirm-side {
  width: 190px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: .45rem;
  min-width: 0;
}
.confirm-adjuntos-card, .confirm-quick-card { border-radius: 12px; padding: .6rem .65rem; min-width: 0; }
.confirm-adjuntos-card { background: #fff; border: 1px solid #E2E8F0; }
.confirm-side-title {
  display: flex;
  align-items: center;
  gap: .3rem;
  margin: 0 0 .45rem;
  font-size: .66rem;
  font-weight: 800;
  color: #0F172A;
}
.confirm-side-empty { margin: 0; font-size: .6rem; color: #94A3B8; font-weight: 500; }
.confirm-adjuntos-grid-big {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: .35rem;
  min-width: 0;
}
.confirm-adjunto-item-big {
  display: flex; flex-direction: column; gap: .2rem;
  min-width: 0;
}
.confirm-adjunto-thumb-big, .confirm-adjunto-badge-big {
  width: 100%; aspect-ratio: 1 / 1;
  border-radius: 8px;
  object-fit: cover;
}
.confirm-adjunto-thumb-big {
  border: 1px solid #E2E8F0;
  background: #F8FAFC;
  cursor: zoom-in;
  transition: transform .15s ease;
}
.confirm-adjunto-thumb-big:hover { transform: scale(1.04); }
.confirm-adjunto-badge-big {
  display: flex; align-items: center; justify-content: center;
  font-size: .6rem; font-weight: 800;
}
.confirm-adjunto-name-big {
  font-size: .52rem; font-weight: 600; color: #64748B;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  min-width: 0;
}
.confirm-quick-card {
  background: linear-gradient(160deg, #111827 0%, #1E293B 100%);
  color: #fff;
}
.confirm-quick-title {
  display: flex; align-items: center; gap: .3rem;
  margin: 0 0 .5rem;
  font-size: .66rem; font-weight: 800;
  color: #fff;
}
.confirm-quick-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: .4rem;
  min-width: 0;
}
.confirm-quick-item {
  display: flex; align-items: flex-start; gap: .3rem;
  min-width: 0;
}
.confirm-quick-item-full { grid-column: 1; }
.confirm-quick-item > div { min-width: 0; display: flex; flex-direction: column; gap: .05rem; flex: 1; }
.confirm-quick-item span { font-size: .5rem; font-weight: 600; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .02em; }
.confirm-quick-item strong {
  font-size: .62rem; font-weight: 700; color: #fff; line-height: 1.25;
  display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;
}
.confirm-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: .28rem;
}
.confirm-dot-1 { background: #60A5FA; }
.confirm-dot-2 { background: #FB923C; }
.confirm-dot-3 { background: #C084FC; }
.confirm-dot-4 { background: #4ADE80; }
.confirm-dot-5 { background: #22D3EE; }

.confirm-footer {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .7rem 1.1rem;
  background: #fff;
  border-top: 1px solid #F1F5F9;
}
.confirm-footer-note {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: .3rem;
  margin: 0;
  font-size: .62rem;
  font-weight: 500;
  color: #94A3B8;
  text-align: center;
}
.confirm-submit-btn {
  background: linear-gradient(135deg, #4F46E5, #7C3AED) !important;
  box-shadow: 0 4px 14px rgba(124, 58, 237, .3) !important;
}
.confirm-submit-btn:hover {
  background: linear-gradient(135deg, #5B52F0, #8B47E8) !important;
  box-shadow: 0 6px 20px rgba(124, 58, 237, .4) !important;
}

/* ── Lightbox: vista ampliada de imagen ── */
.rf-lightbox {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .6rem;
  background: rgba(11, 20, 40, .82);
  backdrop-filter: blur(3px);
  cursor: zoom-out;
  padding: 2rem;
}
.rf-lightbox-img {
  max-width: min(90vw, 900px);
  max-height: 80vh;
  object-fit: contain;
  border-radius: 10px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, .4);
  cursor: default;
}
.rf-lightbox-name {
  color: rgba(255, 255, 255, .85);
  font-size: .78rem;
  font-weight: 600;
  margin: 0;
}
.rf-lightbox-close {
  position: absolute;
  top: 1.2rem; right: 1.2rem;
  display: grid;
  place-items: center;
  width: 38px; height: 38px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, .25);
  background: rgba(255, 255, 255, .1);
  color: #fff;
  cursor: pointer;
  transition: all .2s ease;
}
.rf-lightbox-close:hover { background: rgba(255, 255, 255, .2); }

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
