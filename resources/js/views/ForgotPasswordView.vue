<template>
  <div class="h-screen overflow-y-auto flex items-center justify-center bg-gradient-to-b from-blue-50 to-white py-12">
    <div class="w-full max-w-lg mx-4 my-auto"
      v-motion
      :initial="{ opacity: 0, y: 30, scale: 0.96 }"
      :enter="{ opacity: 1, y: 0, scale: 1, transition: { duration: 600, ease: 'easeOut' } }">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8">
          <div class="flex flex-col items-center mb-6">
            <div class="bg-white rounded-full p-3 shadow-inner mb-4">
              <img :src="logoAvatar" alt="logo" class="w-16 h-16 object-contain" />
            </div>
            <div class="flex items-center gap-2 mb-1">
              <component :is="MailQuestionIcon" class="w-7 h-7 text-[var(--blue-600)]" />
              <h1 class="text-2xl font-bold text-gray-800">Recuperar Contraseña</h1>
            </div>
            <p class="text-sm text-gray-500 mt-2">Ingresa tu correo y te enviaremos un enlace</p>
          </div>

          <el-form :model="form" @submit.prevent="handleSendEmail" label-position="top">
            <el-form-item label="Correo electrónico">
              <el-input
                v-model="form.email"
                type="email"
                placeholder="Ingrese su correo electrónico"
                :prefix-icon="MailIcon"
                class="rounded-md"
                clearable
              />
            </el-form-item>

            <el-alert
              v-if="successMessage"
              :title="successMessage"
              type="success"
              show-icon
              :closable="false"
              class="mb-4"
            />

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
              <component :is="SendIcon" class="w-4 h-4 mr-1" />
              Enviar enlace de recuperación
            </el-button>

            <div class="text-center mt-4">
              <router-link :to="{ name: 'login' }" class="inline-flex items-center gap-1 text-sm text-[var(--blue-700)] hover:underline">
                <component :is="ArrowLeftIcon" class="w-4 h-4" />
                Volver a iniciar sesión
              </router-link>
            </div>
          </el-form>

          <p class="text-xs text-center text-gray-400 mt-6">© {{ new Date().getFullYear() }} Santa Bárbara</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Mail as MailIcon, MailQuestion as MailQuestionIcon, ArrowLeft as ArrowLeftIcon, Send as SendIcon } from '@lucide/vue';
import http from '@/plugins/axios';

const logoAvatar = '/images/logo-avatar.png';

const form = reactive({
  email: '',
});

const loading = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

async function handleSendEmail() {
  if (!form.email) {
    errorMessage.value = 'Por favor, ingrese su correo electrónico';
    return;
  }

  try {
    loading.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    await http.post('/api/forgot-password', { email: form.email });

    successMessage.value = 'Se ha enviado un enlace de recuperación a su correo electrónico. Revisa tu bandeja de entrada.';
    form.email = '';
  } catch (error: any) {
    if (error.response?.status === 422) {
      const errors = error.response.data?.data?.errors || error.response.data?.errors || {};
      const firstError = Object.values(errors)[0];
      errorMessage.value = Array.isArray(firstError) ? firstError[0] : (error.response.data?.message || 'Los datos enviados no son válidos');
    } else {
      errorMessage.value = 'Error al enviar el correo. Intente nuevamente.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
