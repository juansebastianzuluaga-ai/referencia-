<template>
  <div class="min-h-screen flex items-center justify-center px-4 py-8"
    style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-0 w-96 h-96 rounded-full opacity-5 pointer-events-none"
      style="background: radial-gradient(circle, #fff, transparent)" />
    <div class="fixed bottom-0 right-0 w-80 h-80 rounded-full opacity-5 pointer-events-none"
      style="background: radial-gradient(circle, #fff, transparent)" />

    <div class="w-full max-w-md relative z-10">

      <!-- Card -->
      <div class="rounded-2xl overflow-hidden"
        style="box-shadow: 0 32px 64px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.06);">

        <!-- Header con gradiente azul -->
        <div class="relative px-6 pt-6 pb-5 flex flex-col items-center overflow-hidden"
          style="background: linear-gradient(160deg, #16468E 0%, #0D2D6B 100%);">
          <div class="absolute -top-8 -right-8 w-32 h-32 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <div class="absolute -bottom-4 -left-4 w-24 h-24 rounded-full opacity-10"
            style="background: radial-gradient(circle, #fff, transparent)" />
          <img :src="logoBlanco" alt="logo" class="h-10 object-contain relative z-10 drop-shadow-lg mb-3" />
          <h1 class="text-xl font-extrabold text-white tracking-wide relative z-10">Verificar código</h1>
          <p class="text-blue-300 text-xs relative z-10 mt-1 text-center">
            Ingrese el código de 6 dígitos enviado al número<br>
            registrado para la clínica con NIT <strong>{{ nit }}</strong>
          </p>
        </div>

        <!-- Cuerpo -->
        <div class="bg-white px-8 py-7">

          <div class="flex items-center gap-2 mb-4">
            <div class="w-1 h-4 rounded-full bg-[#0D2D6B]" />
            <p class="text-xs font-bold text-[#0D2D6B] uppercase tracking-widest">Código de seguridad</p>
          </div>

          <!-- Inputs de 6 dígitos -->
          <div class="flex justify-center gap-2.5 mb-6">
            <input
              v-for="(_, i) in 6"
              :key="i"
              :ref="el => { if (el) inputs[i] = el as HTMLInputElement }"
              v-model="digitos[i]"
              type="text"
              inputmode="numeric"
              maxlength="1"
              class="w-11 h-13 text-center text-xl font-bold border-2 rounded-xl outline-none transition-all focus:border-[#16468E] focus:ring-2 focus:ring-blue-100"
              :class="[
                error ? 'border-red-400' : 'border-gray-200',
                digitos[i] ? 'bg-blue-50 border-[#16468E]' : ''
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
          <button
            type="button"
            class="w-full py-3 rounded-xl text-white font-bold text-sm transition-all flex items-center justify-center gap-2 disabled:opacity-60"
            style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 0 4px 16px rgba(13,45,107,0.25);"
            :disabled="verificando || !codigoCompleto"
            @click="verificarCodigo"
          >
            <span v-if="verificando" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <component v-else :is="ShieldCheckIcon" class="w-4 h-4" />
            {{ verificando ? 'Verificando...' : 'Verificar código' }}
          </button>

          <!-- Reenviar código -->
          <div class="mt-5 text-center">
            <p class="text-sm text-gray-500">¿No recibió el código?</p>
            <button
              class="text-sm text-[#0D2D6B] hover:text-[#16468E] font-semibold mt-1 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
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
              class="inline-flex items-center text-sm text-gray-400 hover:text-[#0D2D6B] transition-colors"
            >
              <component :is="ArrowLeftIcon" class="w-4 h-4 mr-1" />
              Volver al inicio
            </router-link>
          </div>

          <p class="text-xs text-center text-gray-300 mt-6">
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
  ShieldCheck as ShieldCheckIcon,
  ArrowLeft as ArrowLeftIcon,
  RefreshCw as RefreshCwIcon,
} from '@lucide/vue';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = useRouter();
const route = useRoute();
const clinicaAuthStore = useClinicaAuthStore();

const logoBlanco = '/images/logo-w.png';

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
