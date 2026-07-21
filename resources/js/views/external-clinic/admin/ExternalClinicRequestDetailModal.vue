<template>
  <BaseModal v-model="visible" :title="clinic?.business_name || 'Detalle de solicitud'" width="720">
    <div v-if="clinic" class="space-y-6">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="Información general" name="info">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><strong>NIT:</strong> {{ clinic.nit }}</div>
            <div><strong>Razón social:</strong> {{ clinic.business_name }}</div>
            <div v-if="clinic.trade_name"><strong>Nombre comercial:</strong> {{ clinic.trade_name }}</div>
            <div><strong>Correo:</strong> {{ clinic.email }}</div>
            <div><strong>Teléfono:</strong> {{ clinic.phone }}</div>
            <div v-if="clinic.mobile"><strong>Celular:</strong> {{ clinic.mobile }}</div>
            <div class="md:col-span-2"><strong>Dirección:</strong> {{ clinic.address }}</div>
            <div><strong>Ciudad:</strong> {{ clinic.city }}</div>
            <div><strong>Departamento:</strong> {{ clinic.department }}</div>
            <div class="md:col-span-2"><strong>Representante legal:</strong> {{ clinic.legal_rep_name }}</div>
            <div><strong>Tipo de identificación:</strong> {{ clinic.legal_rep_id_type?.name }}</div>
            <div><strong>Número de identificación:</strong> {{ clinic.legal_rep_id_number }}</div>
            <div class="md:col-span-2"><strong>Estado:</strong> <StatusPill :status="clinic.status" /></div>
            <div v-if="clinic.rejection_reason" class="md:col-span-2"><strong>Motivo de rechazo:</strong> {{ clinic.rejection_reason }}</div>
          </div>
        </el-tab-pane>

        <el-tab-pane label="Documentos" name="documents">
          <div v-if="clinic.documents?.length" class="space-y-2">
            <div
              v-for="doc in clinic.documents"
              :key="doc.id"
              class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
            >
              <div>
                <p class="text-sm font-medium text-gray-800">{{ doc.file_name }}</p>
                <p class="text-xs text-gray-500">{{ doc.document_type_label }} · {{ doc.formatted_size }}</p>
              </div>
              <el-button type="primary" link size="small">
                Descargar
              </el-button>
            </div>
          </div>
          <el-empty v-else description="No hay documentos adjuntos" />
        </el-tab-pane>

        <el-tab-pane label="Historial" name="history">
          <el-timeline v-if="clinic.requests?.length">
            <el-timeline-item
              v-for="request in clinic.requests"
              :key="request.id"
              :type="getTimelineType(request.new_status)"
              :timestamp="formatDate(request.created_at)"
            >
              <p class="text-sm font-medium">{{ request.new_status_label }}</p>
              <p v-if="request.changed_by" class="text-xs text-gray-500">Por: {{ request.changed_by.full_name }}</p>
              <p v-if="request.change_reason" class="text-xs text-gray-600 mt-1">{{ request.change_reason }}</p>
            </el-timeline-item>
          </el-timeline>
          <el-empty v-else description="Sin historial de cambios" />
        </el-tab-pane>
      </el-tabs>

      <div class="flex flex-wrap gap-3 justify-end pt-4 border-t border-gray-200">
        <template v-if="clinic.status === 'pending'">
          <el-button type="danger" :icon="X" @click="openReject">Rechazar</el-button>
          <el-button type="success" :icon="Check" @click="openApprove">Aprobar</el-button>
        </template>
        <template v-else-if="clinic.status === 'active'">
          <el-button type="warning" @click="handleToggle">Desactivar cuenta</el-button>
        </template>
        <template v-else-if="clinic.status === 'inactive'">
          <el-button type="success" @click="handleToggle">Activar cuenta</el-button>
        </template>
      </div>
    </div>
  </BaseModal>

  <BaseModal v-model="approveVisible" title="Aprobar solicitud" width="480">
    <p class="text-sm text-gray-600 mb-4">
      ¿Está seguro de aprobar la solicitud de <strong>{{ clinic?.business_name }}</strong>?
    </p>
    <el-input
      v-model="approveReason"
      type="textarea"
      :rows="3"
      placeholder="Motivo de aprobación (opcional)"
      class="mb-4"
    />
    <div class="flex justify-end gap-2">
      <el-button @click="approveVisible = false">Cancelar</el-button>
      <el-button type="success" :loading="processing" @click="confirmApprove">Confirmar aprobación</el-button>
    </div>
  </BaseModal>

  <BaseModal v-model="rejectVisible" title="Rechazar solicitud" width="480">
    <p class="text-sm text-gray-600 mb-4">
      ¿Está seguro de rechazar la solicitud de <strong>{{ clinic?.business_name }}</strong>?
    </p>
    <el-form-item label="Motivo del rechazo" required>
      <el-input
        v-model="rejectReason"
        type="textarea"
        :rows="4"
        placeholder="Explique el motivo del rechazo"
      />
    </el-form-item>
    <div class="flex justify-end gap-2">
      <el-button @click="rejectVisible = false">Cancelar</el-button>
      <el-button type="danger" :loading="processing" :disabled="!rejectReason" @click="confirmReject">Confirmar rechazo</el-button>
    </div>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
import { Check, X } from '@lucide/vue';
import { useExternalClinicRequestsStore } from '@/stores/externalClinicRequests';
import BaseModal from '@/components/ui/BaseModal.vue';
import StatusPill from '@/components/ui/StatusPill.vue';
import type { ExternalClinic } from '@/stores/externalClinicAuth';

const props = defineProps<{
  modelValue: boolean;
  clinic: ExternalClinic | null;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'approved'): void;
  (e: 'rejected'): void;
  (e: 'toggled'): void;
}>();

const store = useExternalClinicRequestsStore();
const activeTab = ref('info');
const approveVisible = ref(false);
const rejectVisible = ref(false);
const approveReason = ref('');
const rejectReason = ref('');
const processing = ref(false);

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const clinic = computed(() => props.clinic);

function openApprove() {
  approveReason.value = '';
  approveVisible.value = true;
}

function openReject() {
  rejectReason.value = '';
  rejectVisible.value = true;
}

async function confirmApprove() {
  if (!clinic.value) return;
  try {
    processing.value = true;
    await store.approve(clinic.value.id, approveReason.value || undefined);
    emit('approved');
  } catch {
    ElMessage.error('Error al aprobar la solicitud');
  } finally {
    processing.value = false;
    approveVisible.value = false;
  }
}

async function confirmReject() {
  if (!clinic.value || !rejectReason.value) return;
  try {
    processing.value = true;
    await store.reject(clinic.value.id, rejectReason.value);
    emit('rejected');
  } catch {
    ElMessage.error('Error al rechazar la solicitud');
  } finally {
    processing.value = false;
    rejectVisible.value = false;
  }
}

async function handleToggle() {
  if (!clinic.value) return;
  try {
    processing.value = true;
    await store.toggleStatus(clinic.value.id);
    emit('toggled');
  } catch {
    ElMessage.error('Error al cambiar el estado');
  } finally {
    processing.value = false;
  }
}

function getTimelineType(status: string): string {
  switch (status) {
    case 'approved':
    case 'active':
      return 'success';
    case 'rejected':
      return 'danger';
    case 'inactive':
      return 'warning';
    default:
      return 'info';
  }
}

function formatDate(value: string) {
  return new Date(value).toLocaleString('es-CO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
