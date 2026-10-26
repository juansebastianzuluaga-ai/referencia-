<template>
  <div class="h-screen flex items-center justify-center px-4" style="background: radial-gradient(ellipse at 60% 0%, #16468E 0%, #0D2D6B 55%, #071a42 100%);">

    <!-- Glow decorativos de fondo -->
    <div class="fixed top-0 left-1/4 w-[500px] h-[500px] rounded-full opacity-[0.07] pointer-events-none"
      style="background: radial-gradient(circle, #3b82f6, transparent 70%)" />
    <div class="fixed bottom-0 right-1/4 w-[400px] h-[400px] rounded-full opacity-[0.05] pointer-events-none"
      style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />

    <div class="w-full max-w-sm relative z-10 animate-fade-in-up"
      style="animation-duration: 0.5s; animation-fill-mode: both;">

      <!-- Card -->
      <div class="rounded-3xl overflow-hidden card-enter" style="box-shadow: 12px 12px 24px rgba(7,26,66,0.4), -12px -12px 24px rgba(255,255,255,0.15), 0 0 60px rgba(255,255,255,0.12);">

        <!-- Header azul -->
        <div class="relative px-6 pt-6 pb-5 flex flex-col items-center text-center overflow-hidden"
          style="background: linear-gradient(135deg, #16468E 0%, #0D2D6B 50%, #0a2150 100%);">
          <!-- Círculos decorativos -->
          <div class="absolute -top-8 -right-8 w-36 h-36 rounded-full opacity-[0.12]" style="background: radial-gradient(circle, #60a5fa, transparent 70%)" />
          <div class="absolute -bottom-6 -left-6 w-24 h-24 rounded-full opacity-[0.08]" style="background: radial-gradient(circle, #93c5fd, transparent 70%)" />
          <div class="absolute inset-0 opacity-[0.03]" style="background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 1px, transparent 12px)" />

          <img :src="logoBlanco" alt="logo" class="h-11 object-contain mb-3 relative z-10 drop-shadow-lg" />
          <h1 class="text-xl font-extrabold text-white tracking-wide relative z-10">Sistema de Referencia</h1>
          <div class="flex items-center gap-2 mt-3 relative z-10">
            <div class="w-6 h-px bg-blue-300/40" />
            <div class="w-1.5 h-1.5 rounded-full bg-blue-300/60" />
            <div class="w-6 h-px bg-blue-300/40" />
          </div>
        </div>

        <!-- Cuerpo -->
        <div class="px-6 pt-5 pb-6 relative" style="background: #eef1f6;">
          <div class="absolute inset-0 opacity-[0.02] pointer-events-none" style="background-image: radial-gradient(circle, #0D2D6B 1px, transparent 1px); background-size: 16px 16px;" />

          <!-- ── Personal interno ── -->
          <div class="relative z-10">
            <div class="mb-3">
              <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all neu-input">
                <component :is="UserIcon" class="w-4 h-4 text-[#16468E] flex-shrink-0" />
                <input
                  v-model="form.username"
                  type="text"
                  placeholder="Usuario"
                  autocomplete="off"
                  class="flex-1 bg-transparent outline-none text-gray-800 text-sm placeholder:text-gray-400"
                  @keyup.enter="handleLogin"
                />
              </div>
            </div>
            <div class="mb-2">
              <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all neu-input">
                <component :is="LockIcon" class="w-4 h-4 text-[#16468E] flex-shrink-0" />
                <input
                  v-model="form.password"
                  :type="mostrarPassword ? 'text' : 'password'"
                  placeholder="Contraseña"
                  autocomplete="off"
                  class="flex-1 bg-transparent outline-none text-gray-800 text-sm placeholder:text-gray-400"
                  @keyup.enter="handleLogin"
                />
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="mostrarPassword = !mostrarPassword">
                  <component :is="mostrarPassword ? EyeOffIcon : EyeIcon" class="w-4 h-4" />
                </button>
              </div>
            </div>
            <div class="flex justify-end mb-5">
              <router-link :to="{ name: 'forgot-password' }" class="text-xs text-[#0D2D6B] hover:underline">
                ¿Olvidó su contraseña?
              </router-link>
            </div>
            <p v-if="errorInterno" class="text-xs text-red-500 mb-3 text-center">{{ errorInterno }}</p>
            <button
              class="w-full py-2.5 rounded-xl text-white font-semibold text-sm transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-2"
              style="background: linear-gradient(135deg, #0D2D6B 0%, #16468E 100%); box-shadow: 4px 4px 10px rgba(163,177,198,0.5), -4px -4px 10px rgba(255,255,255,0.8);"
              :disabled="loadingInterno"
              @click="handleLogin"
            >
              <span v-if="loadingInterno" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
              Ingresar
            </button>
            <div class="mt-4 text-center pt-3" style="border-top: 1px solid #d4deea;">
              <p class="text-xs text-gray-400 mb-1">¿Es una institución externa?</p>
              <router-link :to="{ name: 'clinica-inicio' }" class="text-xs font-semibold text-[#0D2D6B] hover:underline">
                Diligenciar solicitud de referencia →
              </router-link>
            </div>
          </div>

        </div><!-- /cuerpo -->
      </div>

      <p class="text-center text-xs text-white/30 mt-5 tracking-wide flex items-center justify-center gap-1">
        <component :is="LockIcon" class="w-3 h-3" />
        © {{ new Date().getFullYear() }} · Clínica Santa Bárbara · Todos los derechos reservados
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import {
  User as UserIcon,
  Lock as LockIcon,
  Eye as EyeIcon,
  EyeOff as EyeOffIcon,
} from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const auth = useAuthStore();

const logoBlanco = '/images/logo-w.png';

// ── Personal interno ─────────────────────────────────────────────────────────
const form = reactive({ username: '', password: '' });
const loadingInterno = ref(false);
const errorInterno = ref('');
const mostrarPassword = ref(false);

async function handleLogin() {
  if (!form.username || !form.password) { errorInterno.value = 'Ingrese usuario y contraseña'; return; }
  try {
    loadingInterno.value = true;
    errorInterno.value = '';
    await auth.login({ username: form.username, password: form.password });
    // La ruta raíz redirige al primer módulo que el rol del usuario permita —
    // no siempre es el dashboard.
    router.push('/');
  } catch (error: any) {
    if (error?.code === 'session_not_persisted') {
      errorInterno.value = 'La sesión no se pudo establecer. Borra las cookies del sitio (Ctrl+Shift+Supr) e inténtalo de nuevo.';
    } else if (axios.isAxiosError(error)) {
      const status = error.response?.status;
      if (status === 422) {
        errorInterno.value = 'Usuario o contraseña incorrectos';
      } else if (status === 429) {
        errorInterno.value = 'Demasiados intentos. Espere un momento e intente de nuevo.';
      } else {
        errorInterno.value = error.response?.data?.message || 'Error al intentar iniciar sesión';
      }
    } else {
      errorInterno.value = 'Error de conexión. Verifique que el servidor esté activo.';
    }
  } finally {
    loadingInterno.value = false;
  }
}
</script>

<style scoped>
.card-enter {
  animation: cardEnter 0.5s ease-out;
}

@keyframes cardEnter {
  from {
    opacity: 0;
    transform: translateY(16px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.neu-input {
  background: #eef1f6;
  box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.8);
}

.neu-input:focus-within {
  box-shadow: inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.95), 0 0 0 1.5px rgba(13, 45, 107, 0.15);
}
</style>
