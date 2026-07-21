<template>
  <div class="space-y-4">
    <ContentCard title="Solicitudes de Clínicas Externas">
      <div class="flex flex-wrap gap-3 mb-4">
        <el-input
          v-model="filters.general"
          placeholder="Buscar por NIT, razón social, ciudad..."
          clearable
          class="w-full md:w-80"
        />
        <el-select v-model="filters.status" placeholder="Estado" clearable class="w-40">
          <el-option label="Pendiente" value="pending" />
          <el-option label="Aprobada" value="approved" />
          <el-option label="Rechazada" value="rejected" />
          <el-option label="Activa" value="active" />
          <el-option label="Inactiva" value="inactive" />
        </el-select>
        <el-button type="primary" :icon="Search" @click="loadRequests">Buscar</el-button>
        <el-button :icon="RefreshCcw" @click="resetFilters">Limpiar</el-button>
      </div>

      <div class="w-full overflow-x-auto">
        <el-table
          v-loading="store.isLoading"
          :data="store.requests"
          stripe
          class="w-full min-w-[640px]"
          @row-click="openDetail"
        >
          <el-table-column prop="nit" label="NIT" width="120" />
          <el-table-column prop="business_name" label="Razón social" min-width="160" />
          <el-table-column prop="city" label="Ciudad" min-width="100" />
          <el-table-column prop="legal_rep_name" label="Representante" min-width="140" />
          <el-table-column label="Estado" width="110">
            <template #default="{ row }">
              <StatusPill :status="row.status" />
            </template>
          </el-table-column>
          <el-table-column label="Acciones" width="120" align="center">
            <template #default="{ row }">
              <el-button type="primary" size="small" link @click.stop="openDetail(row)">
                Ver
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div class="mt-4 flex justify-end">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :total="store.meta.total"
          :page-sizes="[10, 25, 50]"
          layout="total, sizes, prev, pager, next"
          @size-change="loadRequests"
          @current-change="loadRequests"
        />
      </div>
    </ContentCard>

    <ExternalClinicRequestDetailModal
      v-model="detailVisible"
      :clinic="selectedClinic"
      @approved="handleApproved"
      @rejected="handleRejected"
      @toggled="handleToggled"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Search, RefreshCcw } from '@lucide/vue';
import { useExternalClinicRequestsStore } from '@/stores/externalClinicRequests';
import ContentCard from '@/components/ui/ContentCard.vue';
import StatusPill from '@/components/ui/StatusPill.vue';
import ExternalClinicRequestDetailModal from './ExternalClinicRequestDetailModal.vue';
import type { ExternalClinic } from '@/stores/externalClinicAuth';

const store = useExternalClinicRequestsStore();

const filters = reactive({
  general: '',
  status: '',
});

const currentPage = ref(1);
const pageSize = ref(10);
const detailVisible = ref(false);
const selectedClinic = ref<ExternalClinic | null>(null);

onMounted(() => {
  loadRequests();
});

watch(detailVisible, (visible) => {
  if (!visible) {
    selectedClinic.value = null;
  }
});

async function loadRequests() {
  const payload: any = {};
  if (filters.general) payload.general = filters.general;
  if (filters.status) payload.status = filters.status;

  await store.getAll(payload, currentPage.value, pageSize.value);
}

function resetFilters() {
  filters.general = '';
  filters.status = '';
  currentPage.value = 1;
  loadRequests();
}

async function openDetail(row: ExternalClinic) {
  try {
    const detail = await store.show(row.id);
    selectedClinic.value = detail;
    detailVisible.value = true;
  } catch {
    ElMessage.error('Error al cargar el detalle');
  }
}

function handleApproved() {
  detailVisible.value = false;
  loadRequests();
  ElMessage.success('Solicitud aprobada');
}

function handleRejected() {
  detailVisible.value = false;
  loadRequests();
  ElMessage.success('Solicitud rechazada');
}

function handleToggled() {
  detailVisible.value = false;
  loadRequests();
  ElMessage.success('Estado actualizado');
}
</script>
