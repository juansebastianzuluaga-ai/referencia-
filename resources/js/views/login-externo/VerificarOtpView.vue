<template>
  <div class="h-screen overflow-y-auto flex items-center justify-center bg-gradient-to-b from-blue-50 to-white py-12">
    <div class="w-full max-w-md mx-4 my-auto">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8">

          <!-- Encabezado -->
          <div class="flex flex-col items-center mb-8">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
              <component :is="SmartphoneIcon" class="w-8 h-8 text-green-600" />
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Verificar código</h1>
            <p class="text-sm text-gray-500 mt-2 text-center">
              Ingrese el código de 6 dígitos enviado al número<br>
              registrado para la clínica con NIT <strong>{{ nit }}</strong>
            </p>
          </div>

          <!-- Inputs de 6 dígitos -->
          <div class="flex justify-center gap-3 mb-8">
            <input
              v-for="(_, i) in 6"
              :key="i"
              :ref="el => { if (el) inputs[i] = el as HTMLInputElement }"
              v-model="digitos[i]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="w-12 h-14 text-center text-xl font-bold border-2 rounded-lg outline-none transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
              :class="[
                error ? 'border-red-400' : 'border-gray-300',
                digitos[i] ? 'bg-blue-50 border-blue-400' : ''
              ]"
              :disabled="verificando"
              @input="onDigitoInput(i, $event)"
              @keydown="onKeyDown(i, $event)"
              @paste.prevent="onPaste($event)"
            />
          </div>

          <el-alert
            v-if="error"
            :title="error"
            type="error"
            show-icon
            :closable="false"
            class="mb-4"
          />

          <el-alert
            v-if="mensajeExito"
            :title="mensajeExito"
            type="success"
            show-icon
            :closable="false"
            class="mb-4"
          />

          <!-- Botón verificar -->
          <el-button
            type="primary"
            class="w-full"
            size="large"
            :loading="verificando"
            :disabled="codigoCompleto === false"
            @click="verificarCodigo"
          >
            <component :is="ShieldCheckIcon" class="w-4 h-4 mr-2" />
            Verificar código
          </el-button>

          <!-- Reenviar código -->
          <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">¿No recibió el código?</p>
            <button
              class="text-sm text-blue-600 hover:underline mt-1 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="tiempoReenvio > 0 || reEnviando"
              @click="reenviarCodigo"
            >
              <span v-if="tiempoReenvio > 0">
                Reenviar en {{ tiempoReenvio }}s
              </span>
              <span v-else>
                <component :is="RefreshCwIcon" class="w-3 h-3 inline mr-1" />
                Reenviar código
              </span>
            </button>
          </div>

          <div class="mt-4 text-center">
            <router-link
              :to="{ name: 'login-externo' }"
              class="inline-flex items-center text-sm text-gray-400 hover:text-gray-600"
            >
              <component :is="ArrowLeftIcon" class="w-4 h-4 mr-1" />
              Volver al inicio
            </router-link>
          </div>

          <p class="text-xs text-center text-gray-400 mt-8">
            © {{ new Date().getFullYear() }} Santa Bárbara
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import {
  Smartphone as SmartphoneIcon,
  ShieldCheck as ShieldCheckIcon,
  ArrowLeft as ArrowLeftIcon,
  RefreshCw as RefreshCwIcon,
} from '@lucide/vue';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const route = useRoute();
const clinicaAuthStore = useClinicaAuthStore();

const nit = computed(() => (route.query.nit as string) ?? '');
const digitos = ref<string[]>(Array(6).fill(''));
const inputs = ref<HTMLInputElement[]>([]);
const error = ref('');
const mensajeExito = ref('');
const verificando = ref(false);
const reEnviando = ref(false);
const tiempoReenvio = ref(30);

const codigoCompleto = computed(() => digitos.value.every(d => d !== ''));
const codigoString = computed(() => digitos.value.join(''));

let intervalo: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  if (!nit.value) {
    router.replace({ name: 'login-externo' });
    return;
  }
  inputs.value[0]?.focus();
  iniciarContadorReenvio();
});

function iniciarContadorReenvio() {
  tiempoReenvio.value = 30;
  if (intervalo) clearInterval(intervalo);
  intervalo = setInterval(() => {
    if (tiempoReenvio.value > 0) {
      tiempoReenvio.value--;
    } else {
      clearInterval(intervalo!);
    }
  }, 1000);
}

function onDigitoInput(index: number, event: Event) {
  const input = event.target as HTMLInputElement;
  const valor = input.value.replace(/\D/g, '').slice(-1);
  digitos.value[index] = valor;
  error.value = '';

  if (valor && index < 5) {
    inputs.value[index + 1]?.focus();
  }

  if (codigoCompleto.value) {
    verificarCodigo();
  }
}

function onKeyDown(index: number, event: KeyboardEvent) {
  if (event.key === 'Backspace' && !digitos.value[index] && index > 0) {
    inputs.value[index - 1]?.focus();
  }
}

function onPaste(event: ClipboardEvent) {
  const texto = event.clipboardData?.getData('text') ?? '';
  const numeros = texto.replace(/\D/g, '').slice(0, 6).split('');
  numeros.forEach((n, i) => { digitos.value[i] = n; });
  const nextIndex = Math.min(numeros.length, 5);
  inputs.value[nextIndex]?.focus();

  if (numeros.length === 6) {
    verificarCodigo();
  }
}

async function verificarCodigo() {
  if (!codigoCompleto.value) return;

  try {
    verificando.value = true;
    error.value = '';
    await clinicaAuthStore.verificarOtp(nit.value, codigoString.value);
    router.push({ name: 'clinica-dashboard' });
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Código incorrecto o expirado';
    digitos.value = Array(6).fill('');
    inputs.value[0]?.focus();
  } finally {
    verificando.value = false;
  }
}

async function reenviarCodigo() {
  try {
    reEnviando.value = true;
    error.value = '';
    await clinicaAuthStore.solicitarAcceso(nit.value, 'sms');
    mensajeExito.value = 'Código reenviado exitosamente';
    digitos.value = Array(6).fill('');
    inputs.value[0]?.focus();
    iniciarContadorReenvio();
    setTimeout(() => { mensajeExito.value = ''; }, 4000);
  } catch (e: any) {
    error.value = e?.response?.data?.message ?? 'Error al reenviar el código';
  } finally {
    reEnviando.value = false;
  }
}
</script>
