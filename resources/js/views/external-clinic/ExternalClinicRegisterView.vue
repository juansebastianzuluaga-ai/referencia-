<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white py-8 px-4">
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="bg-[#0D2D6B] p-6 text-center">
          <div class="bg-white/10 rounded-full w-14 h-14 flex items-center justify-center mx-auto mb-3">
            <Stethoscope class="w-7 h-7 text-white" />
          </div>
          <h1 class="text-xl font-bold text-white">Registro de Clínicas Externas</h1>
          <p class="text-sm text-white/80 mt-1">Clínica Santa Bárbara</p>
        </div>

        <div class="p-6 md:p-8">
          <el-steps :active="step" finish-status="success" class="mb-8">
            <el-step title="Clínica" />
            <el-step title="Ubicación" />
            <el-step title="Representante" />
            <el-step title="Credenciales" />
          </el-steps>

          <el-form
            ref="formRef"
            :model="form"
            :rules="rules"
            label-position="top"
            @submit.prevent="handleNext"
          >
            <!-- Paso 1: Datos de la clínica -->
            <div v-if="step === 0">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <el-form-item label="NIT" prop="nit">
                  <el-input v-model="form.nit" placeholder="Ej: 901234567-8" clearable />
                </el-form-item>
                <el-form-item label="Razón social" prop="business_name">
                  <el-input v-model="form.business_name" placeholder="Nombre legal de la clínica" clearable />
                </el-form-item>
                <el-form-item label="Nombre comercial" prop="trade_name">
                  <el-input v-model="form.trade_name" placeholder="Opcional" clearable />
                </el-form-item>
                <el-form-item label="Correo electrónico" prop="email">
                  <el-input v-model="form.email" placeholder="correo@clinica.com" clearable />
                </el-form-item>
                <el-form-item label="Teléfono" prop="phone">
                  <el-input v-model="form.phone" placeholder="Ej: (601) 123 4567" clearable />
                </el-form-item>
                <el-form-item label="Celular" prop="mobile">
                  <el-input v-model="form.mobile" placeholder="Opcional" clearable />
                </el-form-item>
              </div>
            </div>

            <!-- Paso 2: Ubicación -->
            <div v-if="step === 1">
              <el-form-item label="Dirección" prop="address">
                <el-input v-model="form.address" type="textarea" :rows="3" placeholder="Dirección completa" />
              </el-form-item>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <el-form-item label="Ciudad" prop="city">
                  <el-input v-model="form.city" placeholder="Ciudad" clearable />
                </el-form-item>
                <el-form-item label="Departamento" prop="department">
                  <el-select v-model="form.department" placeholder="Seleccione" class="w-full">
                    <el-option
                      v-for="dept in departments"
                      :key="dept"
                      :label="dept"
                      :value="dept"
                    />
                  </el-select>
                </el-form-item>
              </div>
            </div>

            <!-- Paso 3: Representante legal -->
            <div v-if="step === 2">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <el-form-item label="Nombre del representante legal" prop="legal_rep_name">
                  <el-input v-model="form.legal_rep_name" placeholder="Nombre completo" clearable />
                </el-form-item>
                <el-form-item label="Tipo de identificación" prop="legal_rep_id_type_id">
                  <el-select-v2
                    v-model="form.legal_rep_id_type_id"
                    :options="identificationTypes"
                    placeholder="Seleccione"
                    class="w-full"
                    value-key="value"
                  />
                </el-form-item>
                <el-form-item label="Número de identificación" prop="legal_rep_id_number">
                  <el-input v-model="form.legal_rep_id_number" placeholder="Número de documento" clearable />
                </el-form-item>
              </div>

              <el-form-item label="Documentos de soporte" prop="documents">
                <el-upload
                  v-model:file-list="form.documents"
                  action="#"
                  :auto-upload="false"
                  :limit="5"
                  accept=".pdf,.jpg,.jpeg,.png"
                  :on-exceed="handleExceed"
                  class="w-full"
                >
                  <el-button type="primary">Adjuntar documentos</el-button>
                  <template #tip>
                    <div class="text-xs text-gray-500 mt-2">
                      Puede adjuntar RUT, Cámara de Comercio y cédula del representante legal. Máximo 5 archivos de 5MB cada uno (PDF, JPG, PNG).
                    </div>
                  </template>
                </el-upload>
              </el-form-item>
            </div>

            <!-- Paso 4: Credenciales -->
            <div v-if="step === 3">
              <el-form-item label="Contraseña" prop="password">
                <el-input v-model="form.password" type="password" placeholder="Mínimo 8 caracteres" show-password />
              </el-form-item>
              <el-form-item label="Confirmar contraseña" prop="password_confirmation">
                <el-input v-model="form.password_confirmation" type="password" placeholder="Repita la contraseña" show-password />
              </el-form-item>
              <el-form-item prop="terms_accepted">
                <el-checkbox v-model="form.terms_accepted">
                  Acepto los términos y condiciones de uso del sistema
                </el-checkbox>
              </el-form-item>
            </div>

            <el-alert
              v-if="errorMessage"
              :title="errorMessage"
              type="error"
              show-icon
              :closable="false"
              class="mb-4"
            />

            <el-alert
              v-if="Object.keys(validationErrors).length > 0"
              type="error"
              :closable="false"
              class="mb-4"
            >
              <template #title>
                <p class="font-medium">Por favor corrija los siguientes errores:</p>
              </template>
              <ul class="list-disc pl-5 mt-1 space-y-1">
                <li v-for="(errors, field) in validationErrors" :key="field">
                  <span class="font-medium capitalize">{{ field }}:</span> {{ errors[0] }}
                </li>
              </ul>
            </el-alert>

            <el-alert
              v-if="errorDebug"
              title="Detalles técnicos del error (para soporte)"
              type="info"
              :closable="false"
              class="mb-4"
            >
              <pre class="text-xs overflow-auto max-h-40 bg-gray-100 p-2 rounded">{{ errorDebug }}</pre>
            </el-alert>

            <div class="flex justify-between mt-6">
              <el-button v-if="step > 0" @click="prevStep">Anterior</el-button>
              <div class="ml-auto">
                <el-button v-if="step < 3" type="primary" @click="handleNext">Siguiente</el-button>
                <el-button v-else type="success" :loading="loading" native-type="submit" @click="handleSubmit">
                  Enviar solicitud
                </el-button>
              </div>
            </div>
          </el-form>

          <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
              ¿Ya tiene cuenta?
              <router-link :to="{ name: 'external-clinic-login' }" class="text-[#0D2D6B] font-medium hover:underline">
                Inicie sesión aquí
              </router-link>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, type FormInstance, type FormRules, type UploadUserFile } from 'element-plus';
import http from '@/plugins/axios';
import { Stethoscope } from '@lucide/vue';
import axios from 'axios';

interface IdentificationTypeOption {
  value: number;
  label: string;
}

const router = useRouter();
const formRef = ref<FormInstance>();
const step = ref(0);
const loading = ref(false);
const errorMessage = ref('');
const validationErrors = ref<Record<string, string[]>>({});
const errorDebug = ref('');
const identificationTypes = ref<IdentificationTypeOption[]>([]);

const form = reactive({
  nit: '',
  business_name: '',
  trade_name: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  city: '',
  department: '',
  legal_rep_name: '',
  legal_rep_id_type_id: undefined as number | undefined,
  legal_rep_id_number: '',
  password: '',
  password_confirmation: '',
  documents: [] as UploadUserFile[],
  terms_accepted: false,
});

const departments = [
  'Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas',
  'Caquetá', 'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía',
  'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander',
  'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre',
  'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada',
];

function validatePassword(_rule: any, value: string, callback: (error?: Error) => void): void {
  if (!value) {
    callback();
    return;
  }

  if (!/[a-z]/.test(value) || !/[A-Z]/.test(value)) {
    callback(new Error('Debe contener al menos una mayúscula y una minúscula'));
    return;
  }

  if (!/[0-9]/.test(value)) {
    callback(new Error('Debe contener al menos un número'));
    return;
  }

  if (!/[^a-zA-Z0-9]/.test(value)) {
    callback(new Error('Debe contener al menos un símbolo'));
    return;
  }

  callback();
}

const stepRules: Record<number, FormRules> = {
  0: {
    nit: [
      { required: true, message: 'El NIT es obligatorio', trigger: 'blur' },
      { pattern: /^[0-9-]+$/, message: 'El NIT solo debe contener números y guiones', trigger: 'blur' },
    ],
    business_name: [{ required: true, message: 'La razón social es obligatoria', trigger: 'blur' }],
    email: [
      { required: true, message: 'El correo es obligatorio', trigger: 'blur' },
      { type: 'email', message: 'Ingrese un correo válido', trigger: 'blur' },
    ],
    phone: [{ required: true, message: 'El teléfono es obligatorio', trigger: 'blur' }],
  },
  1: {
    address: [{ required: true, message: 'La dirección es obligatoria', trigger: 'blur' }],
    city: [{ required: true, message: 'La ciudad es obligatoria', trigger: 'blur' }],
    department: [{ required: true, message: 'El departamento es obligatorio', trigger: 'change' }],
  },
  2: {
    legal_rep_name: [{ required: true, message: 'El nombre es obligatorio', trigger: 'blur' }],
    legal_rep_id_type_id: [{ required: true, message: 'Seleccione el tipo de identificación', trigger: 'change', type: 'number' }],
    legal_rep_id_number: [{ required: true, message: 'El número de identificación es obligatorio', trigger: 'blur' }],
  },
  3: {
    password: [
      { required: true, message: 'La contraseña es obligatoria', trigger: 'blur' },
      { min: 8, message: 'Mínimo 8 caracteres', trigger: 'blur' },
      { validator: validatePassword, trigger: 'blur' },
    ],
    password_confirmation: [{ required: true, message: 'Confirme la contraseña', trigger: 'blur' }],
    terms_accepted: [{ required: true, message: 'Debe aceptar los términos', trigger: 'change' }],
  },
};

const rules = computed(() => stepRules[step.value]);

onMounted(async () => {
  try {
    const { data } = await http.get('/api/public/identification-types');
    identificationTypes.value = data.data.map((item: any) => ({ value: item.id, label: item.name }));
  } catch {
    ElMessage.error('Error al cargar tipos de identificación');
  }
});

async function handleNext() {
  if (!formRef.value) return;

  const valid = await formRef.value.validate().catch(() => false);
  if (!valid) return;

  if (step.value < 3) {
    step.value++;
  }
}

function prevStep() {
  if (step.value > 0) step.value--;
}

async function handleSubmit() {
  if (!formRef.value) return;

  const valid = await formRef.value.validate().catch(() => false);
  if (!valid) return;

  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Las contraseñas no coinciden';
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  try {
    errorMessage.value = '';
    validationErrors.value = {};
    errorDebug.value = '';

    const formData = new FormData();
    Object.entries(form).forEach(([key, value]) => {
      if (value === undefined || value === null || key === 'documents' || key === 'terms_accepted') return;
      formData.append(key, value as string);
    });

    formData.append('terms_accepted', form.terms_accepted ? '1' : '0');

    form.documents.forEach((file) => {
      if (file.raw) {
        formData.append('documents[]', file.raw);
      }
    });

    await http.post('/api/external-clinics/register', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    router.push({
      name: 'external-clinic-request-sent',
      query: { nit: form.nit, email: form.email, phone: form.phone },
    });
  } catch (error: any) {
    if (axios.isAxiosError(error) && error.response?.data) {
      errorMessage.value = error.response.data.message || 'Error al enviar la solicitud. Intente nuevamente.';
      validationErrors.value = error.response.data.data?.errors || error.response.data.errors || {};
      errorDebug.value = JSON.stringify(
        {
          status: error.response.status,
          data: error.response.data,
        },
        null,
        2,
      );
    } else {
      errorMessage.value = 'Error al enviar la solicitud. Intente nuevamente.';
      errorDebug.value = JSON.stringify(error, Object.getOwnPropertyNames(error), 2);
    }
  } finally {
    loading.value = false;
  }
}

function handleExceed() {
  ElMessage.warning('Máximo 5 documentos permitidos');
}
</script>
