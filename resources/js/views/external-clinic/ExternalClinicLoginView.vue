<template>
  <div class="h-screen overflow-y-auto flex items-center justify-center bg-gradient-to-b from-[#0D2D6B] to-[#16468E] py-12">
    <div class="w-full max-w-lg mx-4 my-auto">
      <div class="bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="bg-[#0D2D6B] p-6 text-center">
          <div class="bg-white/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
            <Stethoscope class="w-8 h-8 text-white" />
          </div>
          <h1 class="text-xl font-bold text-white">Acceso para Clínicas Externas</h1>
          <p class="text-sm text-white/80 mt-1">Clínica Santa Bárbara</p>
        </div>

        <div class="p-8">
          <el-form :model="form" @submit.prevent="handleLogin" label-position="top">
            <el-form-item label="NIT">
              <el-input
                v-model="form.nit"
                placeholder="Ingrese el NIT de la clínica"
                :prefix-icon="BuildingIcon"
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
                <input v-model="form.remember" type="checkbox" class="form-checkbox h-4 w-4 text-[#0D2D6B]" />
                <span class="ml-2">Recordarme</span>
              </label>
              <router-link :to="{ name: 'forgot-password' }" class="text-sm text-[#0D2D6B] hover:underline">
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
              class="w-full py-3 rounded-md bg-gradient-to-r from-[#0D2D6B] to-[#16468E] hover:from-[#16468E] hover:to-[#0D2D6B] transition-transform transform hover:-translate-y-0.5"
              :loading="loading"
            >
              <LogInIcon class="w-4 h-4 mr-1" />
              Ingresar
            </el-button>
          </el-form>

          <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
              ¿Aún no tiene cuenta?
              <router-link :to="{ name: 'external-clinic-register' }" class="text-[#0D2D6B] font-medium hover:underline">
                Regístrese aquí
              </router-link>
            </p>
          </div>

          <p class="text-xs text-center text-gray-400 mt-6">© {{ new Date().getFullYear() }} Santa Bárbara</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { useExternalClinicAuthStore } from '@/stores/externalClinicAuth';
import { Building2 as BuildingIcon, Lock as LockIcon, LogIn as LogInIcon, Stethoscope } from '@lucide/vue';
import axios from 'axios';

const router = useRouter();
const auth = useExternalClinicAuthStore();

const form = reactive({
  nit: '',
  password: '',
  remember: false,
});

const loading = ref(false);
const errorMessage = ref('');

async function handleLogin() {
  if (!form.nit || !form.password) {
    errorMessage.value = 'Por favor, ingrese NIT y contraseña';
    return;
  }

  try {
    loading.value = true;
    errorMessage.value = '';
    await auth.login({
      nit: form.nit,
      password: form.password,
      remember: form.remember,
    });
    ElMessage.success('Bienvenido');
    router.push({ name: 'external-clinic-dashboard' });
  } catch (error: any) {
    if (axios.isAxiosError(error) && error.response?.data?.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Error al intentar iniciar sesión';
    }
  } finally {
    loading.value = false;
  }
}
</script>
