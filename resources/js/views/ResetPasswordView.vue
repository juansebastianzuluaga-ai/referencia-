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
              <component :is="KeyRoundIcon" class="w-7 h-7 text-[var(--blue-600)]" />
              <h1 class="text-2xl font-bold text-gray-800">Nueva Contraseña</h1>
            </div>
            <p class="text-sm text-gray-500 mt-2">Ingresa tu nueva contraseña</p>
          </div>

          <el-form :model="form" :rules="rules" ref="formRef" @submit.prevent="handleResetPassword" label-position="top">
            <el-form-item label="Correo electrónico" prop="email">
              <el-input
                v-model="form.email"
                type="email"
                placeholder="Ingrese su correo electrónico"
                :prefix-icon="MailIcon"
                class="rounded-md"
                readonly
              />
            </el-form-item>

            <el-form-item label="Nueva contraseña" prop="password">
              <el-input
                v-model="form.password"
                type="password"
                placeholder="Ingrese su nueva contraseña"
                :prefix-icon="LockIcon"
                show-password
                class="rounded-md"
              />
            </el-form-item>

            <el-form-item label="Confirmar contraseña" prop="password_confirmation">
              <el-input
                v-model="form.password_confirmation"
                type="password"
                placeholder="Confirme su nueva contraseña"
                :prefix-icon="LockIcon"
                show-password
                class="rounded-md"
              />
            </el-form-item>

            <div class="mb-4 p-3 bg-blue-50 rounded-md text-xs text-gray-600">
              <div class="flex items-center gap-1.5 font-semibold mb-1.5">
                <component :is="ShieldCheckIcon" class="w-4 h-4 text-[var(--blue-600)]" />
                <span>La contraseña debe cumplir:</span>
              </div>
              <ul class="list-disc list-inside space-y-0.5">
                <li>Mínimo {{ passwordPolicy.min_length }} caracteres</li>
                <li v-if="passwordPolicy.require_uppercase">Al menos una mayúscula</li>
                <li v-if="passwordPolicy.require_numbers">Al menos un número</li>
                <li v-if="passwordPolicy.require_special">Al menos un carácter especial</li>
              </ul>
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
              <component :is="CheckCircleIcon" class="w-4 h-4 mr-1" />
              Restablecer contraseña
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
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import type { FormInstance, FormRules } from 'element-plus';
import { Mail as MailIcon, Lock as LockIcon, KeyRound as KeyRoundIcon, ArrowLeft as ArrowLeftIcon, CheckCircle as CheckCircleIcon, ShieldCheck as ShieldCheckIcon } from '@lucide/vue';
import http from '@/plugins/axios';

const route = useRoute();
const router = useRouter();
const logoAvatar = '/images/logo-avatar.png';

const formRef = ref<FormInstance>();
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const passwordPolicy = reactive({
  min_length: 8,
  require_uppercase: true,
  require_numbers: true,
  require_special: true,
});

const rules: FormRules = {
  password: [
    { required: true, message: 'La contraseña es requerida', trigger: 'blur' },
    { min: passwordPolicy.min_length, message: `Mínimo ${passwordPolicy.min_length} caracteres`, trigger: 'blur' },
  ],
  password_confirmation: [
    { required: true, message: 'Debe confirmar la contraseña', trigger: 'blur' },
    {
      validator: (_rule: any, value: string, callback: any) => {
        if (value !== form.password) {
          callback(new Error('Las contraseñas no coinciden'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
};

onMounted(async () => {
  form.token = (route.query.token as string) || '';
  form.email = (route.query.email as string) || '';

  if (!form.token || !form.email) {
    ElMessage.error('Enlace inválido. Solicite un nuevo enlace de recuperación.');
    router.push({ name: 'forgot-password' });
    return;
  }

  try {
    const { data } = await http.get('/api/user/password-policy', {
      headers: { 'X-Skip-Auth-Redirect': '1' },
    });
    if (data.data) {
      Object.assign(passwordPolicy, data.data);
    }
  } catch {
    // Use defaults
  }
});

async function handleResetPassword() {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    try {
      loading.value = true;
      errorMessage.value = '';

      await http.post('/api/reset-password', {
        token: form.token,
        email: form.email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      });

      ElMessage.success('Contraseña restablecida correctamente');
      router.push({ name: 'login' });
    } catch (error: any) {
      if (error.response?.status === 422) {
        const errors = error.response.data?.data?.errors || error.response.data?.errors || {};
        const firstError = Object.values(errors)[0];
        errorMessage.value = Array.isArray(firstError) ? firstError[0] : (error.response.data?.message || 'Datos inválidos');
      } else {
        errorMessage.value = 'Error al restablecer la contraseña. El enlace puede haber expirado.';
      }
    } finally {
      loading.value = false;
    }
  });
}
</script>
