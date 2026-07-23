<template>
  <div class="h-screen overflow-y-auto flex items-center justify-center bg-gradient-to-b from-blue-50 to-white py-12">
    <div class="w-full max-w-md mx-4 my-auto">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8">

          <!-- Encabezado -->
          <div class="flex flex-col items-center mb-8">
            <div class="bg-white rounded-full p-3 shadow-inner mb-4">
              <img :src="logoAvatar" alt="logo" class="w-16 h-16 object-contain" />
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Acceso Clínicas</h1>
            <p class="text-sm text-gray-500 mt-1 text-center">
              Sistema de Referencia y Contrarreferencia
            </p>
          </div>

          <!-- Paso 1: Ingresar NIT -->
          <div v-if="paso === 1">
            <p class="text-sm text-gray-600 mb-6 text-center">
              Ingrese el <strong>NIT</strong> o <strong>cédula</strong> de la clínica para continuar.
            </p>

            <el-form @submit.prevent="buscarClinica" label-position="top">
              <el-form-item label="NIT o Cédula de la clínica">
                <el-input
                  v-model="nit"
                  placeholder="Ej: 900123456"
                  :prefix-icon="BuildingIcon"
                  clearable
                  size="large"
                  :disabled="buscando"
                  @keyup.enter="buscarClinica"
                />
              </el-form-item>

              <el-alert
                v-if="error"
                :title="error"
                type="error"
                show-icon
                :closable="false"
                class="mb-4"
              />

              <el-button
                type="primary"
                class="w-full"
                size="large"
                :loading="buscando"
                @click="buscarClinica"
              >
                <component :is="SearchIcon" class="w-4 h-4 mr-2" />
                Continuar
              </el-button>
            </el-form>
          </div>

          <!-- Paso 2: Elegir método de acceso -->
          <div v-if="paso === 2">
            <div class="bg-blue-50 rounded-lg p-4 mb-6">
              <p class="text-sm text-blue-800 font-medium">
                <component :is="BuildingIcon" class="w-4 h-4 inline mr-1" />
                {{ clinicaNombre }}
              </p>
              <p class="text-xs text-blue-600 mt-1">NIT: {{ nit }}</p>
            </div>

            <p class="text-sm text-gray-600 mb-5 text-center">
              ¿Cómo desea recibir su código de acceso?
            </p>

            <div class="space-y-3">
              <!-- Opción: Correo electrónico -->
              <button
                class="w-full flex items-center gap-4 p-4 border-2 rounded-xl transition-all hover:border-blue-500 hover:bg-blue-50"
                :class="metodo === 'email' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                :disabled="enviando"
                @click="seleccionarMetodo('email')"
              >
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                  <component :is="MailIcon" class="w-5 h-5 text-blue-600" />
                </div>
                <div class="text-left">
                  <p class="text-sm font-semibold text-gray-800">Correo electrónico</p>
                  <p class="text-xs text-gray-500">Recibirá un enlace mágico en su correo</p>
                </div>
                <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-400 ml-auto" />
              </button>

              <!-- Opción: SMS -->
              <button
                class="w-full flex items-center gap-4 p-4 border-2 rounded-xl transition-all hover:border-blue-500 hover:bg-blue-50"
                :class="metodo === 'sms' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'"
                :disabled="enviando"
                @click="seleccionarMetodo('sms')"
              >
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                  <component :is="SmartphoneIcon" class="w-5 h-5 text-green-600" />
                </div>
                <div class="text-left">
                  <p class="text-sm font-semibold text-gray-800">Mensaje de texto (SMS)</p>
                  <p class="text-xs text-gray-500">Recibirá un código de 6 dígitos</p>
                </div>
                <component :is="ChevronRightIcon" class="w-4 h-4 text-gray-400 ml-auto" />
              </button>
            </div>

            <el-alert
              v-if="error"
              :title="error"
              type="error"
              show-icon
              :closable="false"
              class="mt-4"
            />

            <div class="mt-6 flex items-center justify-between">
              <el-button text @click="volverPaso1">
                <component :is="ArrowLeftIcon" class="w-4 h-4 mr-1" />
                Volver
              </el-button>
            </div>
          </div>

          <!-- Paso 3: Correo enviado -->
          <div v-if="paso === 3" class="text-center">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <component :is="MailCheckIcon" class="w-8 h-8 text-green-600" />
            </div>
            <h2 class="text-lg font-bold text-gray-800 mb-2">¡Revise su correo!</h2>
            <p class="text-sm text-gray-500 mb-6">
              Enviamos un enlace de acceso a la dirección de correo registrada para esta clínica.
              El enlace es válido por <strong>15 minutos</strong>.
            </p>
            <p class="text-xs text-gray-400 mb-6">
              Si no lo encuentra, revise la carpeta de spam.
            </p>
            <el-button text @click="volverPaso1" class="text-sm">
              Intentar con otro NIT
            </el-button>
          </div>

          <!-- Enlace a registro -->
          <div v-if="paso === 1" class="mt-6 text-center border-t border-gray-100 pt-6">
            <p class="text-sm text-gray-500">¿Su clínica aún no está registrada?</p>
            <router-link
              :to="{ name: 'registro-clinica' }"
              class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mt-1 font-medium"
            >
              <component :is="PlusCircleIcon" class="w-4 h-4" />
              Solicitar registro de clínica
            </router-link>
          </div>

          <p class="text-xs text-center text-gray-400 mt-8">
            © {{ new Date().getFullYear() }} Santa Bárbara — Uso exclusivo clínicas externas
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  Building2 as BuildingIcon,
  Search as SearchIcon,
  Mail as MailIcon,
  Smartphone as SmartphoneIcon,
  ChevronRight as ChevronRightIcon,
  ArrowLeft as ArrowLeftIcon,
  MailCheck as MailCheckIcon,
  PlusCircle as PlusCircleIcon,
} from '@lucide/vue';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const clinicaAuthStore = useClinicaAuthStore();

const logoAvatar = '/images/logo-avatar.png';

const paso = ref<1 | 2 | 3>(1);
const nit = ref('');
const metodo = ref<'email' | 'sms' | null>(null);
const clinicaNombre = ref('');
const error = ref('');
const buscando = ref(false);
const enviando = ref(false);

async function buscarClinica() {
  if (!nit.value.trim()) {
    error.value = 'Por favor ingrese el NIT o cédula de la clínica';
    return;
  }

  try {
    buscando.value = true;
    error.value = '';
    const resultado = await clinicaAuthStore.buscarClinica(nit.value.trim());
    clinicaNombre.value = resultado.nombre;
    paso.value = 2;
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Clínica no encontrada o no activa';
  } finally {
    buscando.value = false;
  }
}

async function seleccionarMetodo(m: 'email' | 'sms') {
  metodo.value = m;

  try {
    enviando.value = true;
    error.value = '';
    await clinicaAuthStore.solicitarAcceso(nit.value.trim(), m);

    if (m === 'email') {
      paso.value = 3;
    } else {
      router.push({ name: 'login-externo-otp', query: { nit: nit.value.trim() } });
    }
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Error al enviar el código. Intente de nuevo.';
  } finally {
    enviando.value = false;
  }
}

function volverPaso1() {
  paso.value = 1;
  metodo.value = null;
  error.value = '';
  clinicaNombre.value = '';
}
</script>
