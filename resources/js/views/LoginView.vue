<template>
  <div class="h-screen overflow-y-auto flex items-center justify-center bg-gradient-to-b from-blue-50 to-white py-12">
    <div class="w-full max-w-lg mx-4 my-auto">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8">
          <div class="flex flex-col items-center mb-6">
            <div class="bg-white rounded-full p-3 shadow-inner mb-4">
              <img :src="logoAvatar" alt="logo" class="w-16 h-16 object-contain" />
            </div>
            <div class="flex items-center gap-2 mb-1">
              <component :is="LogInIcon" class="w-7 h-7 text-[var(--blue-600)]" />
              <h1 class="text-2xl font-bold text-gray-800">Iniciar sesión</h1>
            </div>
            <p class="text-sm text-gray-500 mt-2">Bienvenido al Sistema de la Clínica</p>
          </div>

          <el-form :model="form" @submit.prevent="handleLogin" label-position="top">
            <el-form-item label="Usuario">
              <el-input
                v-model="form.username"
                placeholder="Ingrese su usuario"
                :prefix-icon="UserIcon"
                class="rounded-md"
                clearable
              />
            </el-form-item>

            <el-form-item label="Contraseña">
              <el-input
                v-model="form.password"
                type="password"
                placeholder="Ingrese su contraseña"
                :prefix-icon="LockIcon"
                show-password
                class="rounded-md"
              />
            </el-form-item>

            <div class="flex items-center justify-between mb-4">
              <label class="inline-flex items-center text-sm text-gray-600">
                <input type="checkbox" class="form-checkbox h-4 w-4 text-[var(--blue-700)]" />
                <span class="ml-2">Recordarme</span>
              </label>
              <router-link :to="{ name: 'forgot-password' }" class="inline-flex items-center gap-1 text-sm text-[var(--blue-700)] hover:underline">
                <component :is="HelpCircleIcon" class="w-4 h-4" />
                ¿Olvidó su contraseña?
              </router-link>
            </div>

            <el-alert
              v-if="errorMessage"
              :title="errorMessage"
              type="error"
              show-icon
              :closable="false"
              class="mb-4"
            />

            <el-button
              type="primary"
              native-type="submit"
              class="w-full py-3 rounded-md bg-gradient-to-r from-[var(--blue-600)] to-[var(--blue-800)] hover:from-[var(--blue-700)] hover:to-[var(--blue-900)] transition-transform transform hover:-translate-y-0.5"
              :loading="loading"
            >
              <component :is="LogInIcon" class="w-4 h-4 mr-1" />
              Iniciar sesión
            </el-button>
          </el-form>

          <p class="text-xs text-center text-gray-400 mt-6">© {{ new Date().getFullYear() }} Santa Bárbara</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { User as UserIcon, Lock as LockIcon, LogIn as LogInIcon, HelpCircle as HelpCircleIcon } from '@lucide/vue';
import axios from 'axios';

const auth = useAuthStore();
const router = useRouter();

// Public images served from /public
const logoAvatar = '/images/logo-avatar.png';
const logoLight = '/images/logo.png';

const form = reactive({
  username: '',
  password: '',
});

const loading = ref(false);
const errorMessage = ref('');

async function handleLogin() {
  if (!form.username || !form.password) {
    errorMessage.value = 'Por favor, ingrese usuario y contraseña';
    return;
  }

  try {
    loading.value = true;
    errorMessage.value = '';
    await auth.login({ username: form.username, password: form.password });
    router.push({ name: 'dashboard' });
  } catch (error: any) {
    if (axios.isAxiosError(error) && error.response?.status === 422) {
      errorMessage.value = 'Usuario o contraseña incorrectos';
    } else {
      errorMessage.value = 'Error al intentar iniciar sesión';
    }
  } finally {
    loading.value = false;
  }
}
</script>
