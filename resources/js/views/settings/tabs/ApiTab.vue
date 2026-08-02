<template>
  <div class="space-y-4">
    <!-- Info Card con Base URL -->
    <el-card shadow="never" class="mb-4">
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <el-icon><InfoIcon /></el-icon>
        <span>Base URL: <code class="text-blue-600 font-medium">{{ baseUrl }}/api</code></span>
      </div>
    </el-card>

    <!-- Filtros y Botón Nueva API -->
    <div class="flex items-center gap-3 mb-4">
      <el-input
        v-model="store.filters.search"
        placeholder="Buscar por nombre, descripción o API key..."
        :prefix-icon="SearchIcon"
        clearable
        style="flex: 1 1 auto; max-width: 420px;"
        @input="debouncedSearch"
      />
      <el-select
        v-model="store.filters.status"
        placeholder="Estado"
        clearable
        style="width: 160px; flex: 0 0 auto;"
        @change="handleFilterChange"
      >
        <el-option label="Activo" value="active" />
        <el-option label="Inactivo" value="inactive" />
        <el-option label="Expirado" value="expired" />
        <el-option label="Revocado" value="revoked" />
      </el-select>
      <el-button
        v-permission="'api-keys.create'"
        type="primary"
        :icon="PlusIcon"
        style="flex: 0 0 auto;"
        @click="openCreateDrawer"
      >
        Nueva API
      </el-button>
    </div>

    <!-- Tabla -->
    <el-table :data="store.credentials" v-loading="store.loading" stripe class="w-full">
      <el-table-column prop="name" label="Nombre" min-width="160">
        <template #default="{ row }">
          <div class="flex items-center gap-2">
            <el-icon class="text-gray-400"><KeyIcon /></el-icon>
            <div>
              <strong>{{ row.name }}</strong>
              <div class="text-xs text-gray-400">{{ row.description }}</div>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column prop="api_key" label="API Key" min-width="180">
        <template #default="{ row }">
          <div class="flex items-center gap-1">
            <code class="text-xs text-gray-600">{{ row.api_key.substring(0, 20) }}...</code>
            <el-tooltip content="Copiar" placement="top">
              <el-button :icon="CopyIcon" size="small" text link @click="copyToClipboard(row.api_key)" />
            </el-tooltip>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Activo" width="80" align="center">
        <template #default="{ row }">
          <el-switch
            :model-value="row.status === 'active'"
            :disabled="row.status === 'revoked' || row.status === 'expired'"
            @change="handleToggleStatus(row)"
          />
        </template>
      </el-table-column>

      <el-table-column prop="status" label="Estado" width="120" align="center">
        <template #default="{ row }">
          <el-tag :type="getStatusType(row.status)" size="small">
            {{ getStatusLabel(row.status) }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column prop="last_used_at" label="Último uso" width="160">
        <template #default="{ row }">
          <span class="text-sm text-gray-500">{{ row.last_used_at ? formatDate(row.last_used_at) : 'Nunca' }}</span>
        </template>
      </el-table-column>

      <el-table-column prop="expires_at" label="Expira" width="160">
        <template #default="{ row }">
          <span class="text-sm text-gray-500">{{ row.expires_at ? formatDate(row.expires_at) : 'Nunca' }}</span>
        </template>
      </el-table-column>

      <el-table-column label="Acciones" width="80" fixed="right">
        <template #default="{ row }">
          <el-dropdown trigger="click" @command="(cmd: string) => handleAction(cmd, row)">
            <el-button type="primary" :icon="MoreIcon" circle size="small" />
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-if="row.status !== 'revoked'" command="edit" :icon="EditIcon">Editar</el-dropdown-item>
                <el-dropdown-item command="logs" :icon="DocumentIcon">Ver Logs</el-dropdown-item>
                <el-dropdown-item command="stats" :icon="StatsIcon">Estadísticas</el-dropdown-item>
                <el-dropdown-item v-if="row.status !== 'revoked'" command="regenerate" :icon="RefreshIcon">Regenerar</el-dropdown-item>
                <el-dropdown-item v-if="row.status !== 'revoked'" command="revoke" :icon="RevokeIcon" divided>Revocar</el-dropdown-item>
                <el-dropdown-item command="delete" :icon="DeleteIcon" divided>Eliminar</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </el-table>

    <!-- Paginación -->
    <el-pagination
      v-model:current-page="store.pagination.current_page"
      v-model:page-size="store.pagination.per_page"
      :page-sizes="[10, 25, 50, 100]"
      :total="store.pagination.total"
      layout="total, sizes, prev, pager, next"
      @size-change="(val: number) => { store.setPagination(1, val); store.loadCredentials(); }"
      @current-change="(val: number) => { store.setPagination(val); store.loadCredentials(); }"
      class="mt-4 justify-end"
    />

    <!-- Drawer: Crear/Editar -->
    <BaseDrawer
      v-model="drawer.visible"
      :title="drawer.isEdit ? 'Editar API' : 'Nueva API'"
      :subtitle="drawer.isEdit ? 'Actualice la configuración de la credencial' : 'Cree una nueva credencial para aplicaciones externas'"
      :icon="KeyIcon"
      size="500px"
      :confirm-text="drawer.isEdit ? 'Guardar' : 'Crear'"
      :confirm-icon="drawer.isEdit ? SaveIcon : PlusIcon"
      :loading="store.saving"
      :close-on-press-escape="false"
      @confirm="saveApi"
      @cancel="drawer.visible = false"
    >
      <el-form ref="apiFormRef" :model="apiForm" :rules="formRules" label-position="top" @submit.prevent="saveApi">
        <el-form-item label="Nombre" prop="name">
          <el-input v-model="apiForm.name" placeholder="Ej: API App Móvil" maxlength="100" show-word-limit />
        </el-form-item>
        <el-form-item label="Descripción" prop="description">
          <el-input v-model="apiForm.description" type="textarea" :rows="3" placeholder="Propósito de la API" />
        </el-form-item>

        <el-divider content-position="center">Configuración Avanzada</el-divider>

        <!-- Vigencia -->
        <el-form-item label="Vigencia">
          <el-radio-group v-model="expirationOption" @change="handleExpirationChange" class="flex flex-wrap gap-2">
            <el-radio value="never" border>Nunca</el-radio>
            <el-radio value="30d" border>30 días</el-radio>
            <el-radio value="90d" border>90 días</el-radio>
            <el-radio value="1y" border>1 año</el-radio>
            <el-radio value="custom" border>Personalizada</el-radio>
          </el-radio-group>
          <el-date-picker
            v-if="expirationOption === 'custom'"
            v-model="apiForm.expires_at"
            type="datetime"
            placeholder="Seleccionar fecha"
            class="w-full mt-3"
            :disabled-date="disabledDate"
          />
        </el-form-item>

        <!-- IPs Permitidas -->
        <el-form-item label="IPs Permitidas (Opcional)">
          <el-select
            v-model="apiForm.allowed_ips"
            multiple
            filterable
            allow-create
            default-first-option
            placeholder="Ej: 192.168.1.100"
            class="w-full"
          >
            <template #footer>
              <div class="text-xs text-gray-400 px-3 py-2">
                Deja vacío para permitir todas las IPs
              </div>
            </template>
          </el-select>
        </el-form-item>

        <!-- Abilities -->
        <el-form-item label="Permisos (Abilities)">
          <el-select
            v-model="apiForm.abilities"
            multiple
            filterable
            allow-create
            default-first-option
            placeholder="Ej: users:read, census:write"
            class="w-full"
          >
            <template #footer>
              <div class="text-xs text-gray-400 px-3 py-2">
                Usa * para acceso total, o especifica recursos:users:read, roles:write, etc.
              </div>
            </template>
          </el-select>
        </el-form-item>

        <!-- Rate Limit -->
        <el-form-item label="Rate Limit (req/hora)">
          <el-input-number v-model="apiForm.rate_limit" :min="1" :max="10000" :step="100" controls-position="right" class="w-full" />
        </el-form-item>
      </el-form>
    </BaseDrawer>

    <!-- Modal: Credenciales Generadas -->
    <el-dialog
      v-model="credentialsDialog.visible"
      title="✅ API Creada Exitosamente"
      width="700px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="5vh"
      append-to-body
    >
      <el-alert
        type="warning"
        :closable="false"
        title="⚠️ IMPORTANTE"
        description="Guarda estas credenciales ahora. No podrás verlas nuevamente por seguridad."
        class="mb-4"
      />

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">API Key:</label>
        <div class="flex items-center gap-2">
          <code class="flex-1 text-sm bg-gray-50 p-2 rounded border">{{ credentialsDialog.apiKey }}</code>
          <el-button type="primary" :icon="CopyIcon" size="small" @click="copyToClipboard(credentialsDialog.apiKey)">Copiar</el-button>
        </div>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">API Secret:</label>
        <div class="flex items-center gap-2">
          <code class="flex-1 text-sm bg-gray-50 p-2 rounded border">{{ credentialsDialog.apiSecret }}</code>
          <el-button type="primary" :icon="CopyIcon" size="small" @click="copyToClipboard(credentialsDialog.apiSecret)">Copiar</el-button>
        </div>
      </div>

      <el-divider />

      <div>
        <strong class="text-sm">Ejemplo de uso:</strong>
        <el-tabs v-model="credentialsDialog.exampleTab" class="mt-2">
          <el-tab-pane label="cURL" name="curl">
            <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code>curl -X GET {{ baseUrl }}/api/v1/health \
  -H "X-API-Key: {{ credentialsDialog.apiKey }}" \
  -H "X-API-Secret: {{ credentialsDialog.apiSecret }}" \
  -H "Content-Type: application/json"</code></pre>
          </el-tab-pane>
          <el-tab-pane label="JSON" name="json">
            <pre class="bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code>{
  "method": "GET",
  "url": "{{ baseUrl }}/api/v1/health",
  "headers": {
    "X-API-Key": "{{ credentialsDialog.apiKey }}",
    "X-API-Secret": "{{ credentialsDialog.apiSecret }}",
    "Content-Type": "application/json"
  }
}</code></pre>
          </el-tab-pane>
        </el-tabs>
      </div>

      <template #footer>
        <el-button :icon="DownloadIcon" type="primary" @click="downloadCredentialsTxt">Descargar .txt</el-button>
        <el-button @click="credentialsDialog.visible = false">Cerrar</el-button>
      </template>
    </el-dialog>

    <!-- Dialog: Logs -->
    <el-dialog v-model="logsDialog.visible" title="Logs de peticiones" width="800px" append-to-body>
      <el-table :data="logsDialog.data" v-loading="logsDialog.loading" stripe size="small">
        <el-table-column prop="endpoint" label="Endpoint" min-width="160" />
        <el-table-column prop="method" label="Método" width="80" />
        <el-table-column prop="ip_address" label="IP" width="130" />
        <el-table-column prop="response_status" label="Status" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.response_status >= 200 && row.response_status < 300 ? 'success' : 'danger'" size="small">
              {{ row.response_status }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="response_time" label="Tiempo (ms)" width="100" />
        <el-table-column prop="created_at" label="Fecha" width="160">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
      </el-table>
    </el-dialog>

    <!-- Dialog: Stats -->
    <el-dialog v-model="statsDialog.visible" title="Estadísticas de uso" width="600px" append-to-body>
      <div v-loading="statsDialog.loading" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600">{{ statsDialog.data?.total_requests || 0 }}</div>
            <div class="text-xs text-gray-500">Total peticiones</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600">{{ statsDialog.data?.requests_today || 0 }}</div>
            <div class="text-xs text-gray-500">Hoy</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600">{{ statsDialog.data?.successful_requests || 0 }}</div>
            <div class="text-xs text-gray-500">Exitosas</div>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-red-600">{{ statsDialog.data?.failed_requests || 0 }}</div>
            <div class="text-xs text-gray-500">Fallidas</div>
          </div>
        </div>
        <div v-if="statsDialog.data?.most_used_endpoints?.length" class="mt-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-2">Endpoints más usados</h4>
          <el-table :data="statsDialog.data.most_used_endpoints" size="small">
            <el-table-column prop="endpoint" label="Endpoint" />
            <el-table-column prop="total" label="Peticiones" width="120" align="center" />
          </el-table>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useClipboard } from '@vueuse/core';
import { ElMessageBox, type FormInstance } from 'element-plus';
import notify from '@/plugins/toast';
import {
  Plus as PlusIcon, Search as SearchIcon, Key as KeyIcon, Copy as CopyIcon,
  MoreVertical as MoreIcon, Pencil as EditIcon, Trash as DeleteIcon,
  FileText as DocumentIcon, BarChart3 as StatsIcon, RefreshCw as RefreshIcon,
  XCircle as RevokeIcon, Info as InfoIcon, Save as SaveIcon, Check as CheckIcon,
  Download as DownloadIcon,
} from '@lucide/vue';
import BaseDrawer from '@/components/ui/BaseDrawer.vue';
import { useApiCredentialsStore } from '@/stores/apiCredentials';

const store = useApiCredentialsStore();
const baseUrl = window.location.origin;

const apiFormRef = ref<FormInstance>();

const drawer = reactive({
  visible: false,
  isEdit: false,
  editId: null as number | null,
});

const credentialsDialog = reactive({
  visible: false,
  apiKey: '',
  apiSecret: '',
  exampleTab: 'curl' as string,
});

const logsDialog = reactive({
  visible: false,
  loading: false,
  data: [] as any[],
});

const statsDialog = reactive({
  visible: false,
  loading: false,
  data: null as any,
});

const expirationOption = ref('never');

const apiForm = reactive({
  name: '',
  description: '',
  expires_at: null as string | null,
  allowed_ips: [] as string[],
  abilities: ['*'] as string[],
  rate_limit: 1000,
});

const formRules = {
  name: [{ required: true, message: 'El nombre es obligatorio', trigger: 'blur' }],
};

let searchTimeout: ReturnType<typeof setTimeout>;

function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    store.setPagination(1);
    store.loadCredentials();
  }, 300);
}

function handleFilterChange() {
  store.setPagination(1);
  store.loadCredentials();
}

function openCreateDrawer() {
  drawer.isEdit = false;
  drawer.editId = null;
  Object.assign(apiForm, {
    name: '',
    description: '',
    expires_at: null,
    allowed_ips: [],
    abilities: ['*'],
    rate_limit: 1000,
  });
  expirationOption.value = 'never';
  drawer.visible = true;
}

function openEditDrawer(row: any) {
  drawer.isEdit = true;
  drawer.editId = row.id;
  Object.assign(apiForm, {
    name: row.name,
    description: row.description || '',
    expires_at: row.expires_at,
    allowed_ips: row.allowed_ips || [],
    abilities: row.abilities || ['*'],
    rate_limit: row.rate_limit,
  });
  expirationOption.value = row.expires_at ? 'custom' : 'never';
  drawer.visible = true;
}

function handleExpirationChange(val: string | number | boolean | undefined) {
  const now = new Date();
  if (val === '30d') {
    apiForm.expires_at = new Date(now.getTime() + 30 * 86400000).toISOString();
  } else if (val === '90d') {
    apiForm.expires_at = new Date(now.getTime() + 90 * 86400000).toISOString();
  } else if (val === '1y') {
    apiForm.expires_at = new Date(now.getTime() + 365 * 86400000).toISOString();
  } else if (val === 'never') {
    apiForm.expires_at = null;
  }
}

function disabledDate(date: Date) {
  return date.getTime() < Date.now();
}

async function saveApi() {
  if (!apiFormRef.value) return;
  await apiFormRef.value.validate(async (valid) => {
    if (!valid) return;
    try {
      if (drawer.isEdit && drawer.editId) {
        await store.updateCredential(drawer.editId, apiForm);
      } else {
        const result = await store.createCredential(apiForm);
        if (result?.api_secret_plain) {
          credentialsDialog.apiKey = result.api_key;
          credentialsDialog.apiSecret = result.api_secret_plain;
          credentialsDialog.visible = true;
        }
      }
      drawer.visible = false;
    } catch {
      // error handled in store
    }
  });
}

async function handleToggleStatus(row: any) {
  try {
    await store.toggleStatus(row.id);
  } catch {
    // error handled in store
  }
}

async function handleAction(cmd: string, row: any) {
  switch (cmd) {
    case 'edit':
      openEditDrawer(row);
      break;
    case 'logs':
      await openLogsDialog(row.id);
      break;
    case 'stats':
      await openStatsDialog(row.id);
      break;
    case 'regenerate':
      await handleRegenerate(row);
      break;
    case 'revoke':
      await handleRevoke(row);
      break;
    case 'delete':
      await handleDelete(row);
      break;
  }
}

async function handleRegenerate(row: any) {
  try {
    await ElMessageBox.confirm(
      '¿Estás seguro de regenerar las credenciales? La API Key y Secret actuales dejarán de funcionar.',
      'Confirmar regeneración',
      { type: 'warning' },
    );
    const result = await store.regenerateCredential(row.id);
    if (result?.api_secret_plain) {
      credentialsDialog.apiKey = result.api_key;
      credentialsDialog.apiSecret = result.api_secret_plain;
      credentialsDialog.visible = true;
    }
  } catch {
    // cancelled or error
  }
}

async function handleRevoke(row: any) {
  try {
    await ElMessageBox.confirm(
      '¿Revocar esta credencial? No se podrá reactivar.',
      'Confirmar revocación',
      { type: 'warning' },
    );
    await store.revokeCredential(row.id);
  } catch {
    // cancelled or error
  }
}

async function handleDelete(row: any) {
  try {
    await ElMessageBox.confirm('¿Eliminar esta credencial API?', 'Confirmar', { type: 'warning' });
    await store.deleteCredential(row.id);
  } catch {
    // cancelled or error
  }
}

async function openLogsDialog(id: number) {
  logsDialog.visible = true;
  logsDialog.loading = true;
  try {
    const data = await store.getLogs(id);
    logsDialog.data = data?.data || [];
  } catch {
    logsDialog.data = [];
  } finally {
    logsDialog.loading = false;
  }
}

async function openStatsDialog(id: number) {
  statsDialog.visible = true;
  statsDialog.loading = true;
  try {
    statsDialog.data = await store.getStats(id);
  } catch {
    statsDialog.data = null;
  } finally {
    statsDialog.loading = false;
  }
}

function getStatusType(status: string): 'primary' | 'success' | 'warning' | 'info' | 'danger' {
  const types: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    active: 'success',
    inactive: 'info',
    expired: 'warning',
    revoked: 'danger',
  };
  return types[status] || 'info';
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    active: 'Activo',
    inactive: 'Inactivo',
    expired: 'Expirado',
    revoked: 'Revocado',
  };
  return labels[status] || status;
}

function formatDate(date: string): string {
  return new Date(date).toLocaleString('es-CO', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function downloadCredentialsTxt() {
  const content = `========================================
  CREDENCIALES API
========================================

API Key:    ${credentialsDialog.apiKey}
API Secret: ${credentialsDialog.apiSecret}

========================================
  EJEMPLO cURL
========================================

curl -X GET ${baseUrl}/api/v1/health \\
  -H "X-API-Key: ${credentialsDialog.apiKey}" \\
  -H "X-API-Secret: ${credentialsDialog.apiSecret}" \\
  -H "Content-Type: application/json"

========================================
  EJEMPLO JSON
========================================

{
  "method": "GET",
  "url": "${baseUrl}/api/v1/health",
  "headers": {
    "X-API-Key": "${credentialsDialog.apiKey}",
    "X-API-Secret": "${credentialsDialog.apiSecret}",
    "Content-Type": "application/json"
  }
}

========================================
  NOTAS
========================================
- Guarda este archivo en un lugar seguro.
- El API Secret no se puede recuperar.
- Generado: ${new Date().toLocaleString()}
`;

  const blob = new Blob([content], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `api-credentials-${Date.now()}.txt`;
  a.click();
  URL.revokeObjectURL(url);
  notify.success('Archivo descargado correctamente');
}

const { copy, isSupported: clipboardSupported } = useClipboard();

async function copyToClipboard(text: string) {
  if (!clipboardSupported) {
    notify.error('No se pudo copiar');
    return;
  }
  await copy(text);
  notify.success('Copiado al portapapeles');
}

onMounted(() => {
  store.loadCredentials();
});

defineExpose({ group: 'api' });
</script>
