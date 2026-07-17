<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :title="`Cambiar contraseña - ${user?.full_name || user?.user_name || ''}`"
    subtitle="Ingrese la nueva contraseña para el usuario"
    :icon="KeyRoundIcon"
    :loading="loading"
    confirm-text="Actualizar contraseña"
    :confirm-icon="CheckCircleIcon"
    width="480px"
    @confirm="handleSubmit"
    @cancel="resetForm"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      @submit.prevent="handleSubmit"
    >
      <el-alert
        type="info"
        :closable="false"
        :description="passwordPolicyDescription"
        class="mb-4"
      >
        <template #title>
          <div class="flex items-center gap-2">
            <component :is="ShieldCheckIcon" class="w-5 h-5" />
            <span>Política de contraseñas</span>
          </div>
        </template>
      </el-alert>

      <el-form-item label="Nueva contraseña" prop="password">
        <el-input
          v-model="form.password"
          type="password"
          :placeholder="`Mínimo ${passwordPolicy.min_length} caracteres`"
          show-password
        />
      </el-form-item>

      <el-form-item label="Confirmar contraseña" prop="password_confirmation">
        <el-input
          v-model="form.password_confirmation"
          type="password"
          placeholder="Repita la nueva contraseña"
          show-password
        />
      </el-form-item>
    </el-form>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { ElMessage, type FormInstance, type FormRules } from 'element-plus';
import { ShieldCheck as ShieldCheckIcon, KeyRound as KeyRoundIcon, CheckCircle as CheckCircleIcon } from '@lucide/vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import http from '@/plugins/axios';

const props = defineProps<{
  modelValue: boolean;
  user: any;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  saved: [];
}>();

const formRef = ref<FormInstance>();
const loading = ref(false);

const form = reactive({
  password: '',
  password_confirmation: '',
});

const passwordPolicy = reactive({
  min_length: 8,
  require_special: true,
  require_numbers: true,
  require_uppercase: true,
});

const passwordPolicyLoaded = ref(false);

const passwordPolicyDescription = computed(() => {
  if (!passwordPolicyLoaded.value) return 'Cargando configuración de seguridad...';
  const parts: string[] = [`mínimo ${passwordPolicy.min_length} caracteres`];
  if (passwordPolicy.require_uppercase) parts.push('letras mayúsculas y minúsculas');
  if (passwordPolicy.require_numbers) parts.push('números');
  if (passwordPolicy.require_special) parts.push('símbolos especiales');
  return `La contraseña debe tener ${parts.join(', ')}.`;
});

const rules = computed<FormRules>(() => ({
  password: [
    { required: true, message: 'La nueva contraseña es requerida', trigger: 'blur' },
    { min: passwordPolicy.min_length, message: `Mínimo ${passwordPolicy.min_length} caracteres`, trigger: 'blur' },
    {
      validator: (_rule: any, value: any, callback: any) => {
        if (!value) return callback();
        if (passwordPolicy.require_uppercase && !/[A-Z]/.test(value)) {
          return callback(new Error('Debe incluir al menos una letra mayúscula'));
        }
        if (passwordPolicy.require_numbers && !/[0-9]/.test(value)) {
          return callback(new Error('Debe incluir al menos un número'));
        }
        if (passwordPolicy.require_special && !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value)) {
          return callback(new Error('Debe incluir al menos un carácter especial'));
        }
        callback();
      },
      trigger: 'blur',
    },
  ],
  password_confirmation: [
    { required: true, message: 'Confirme la nueva contraseña', trigger: 'blur' },
    {
      validator: (_rule: any, value: any, callback: any) => {
        if (value !== form.password) {
          callback(new Error('Las contraseñas no coinciden'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
}));

async function loadPasswordPolicy() {
  if (passwordPolicyLoaded.value) return;
  try {
    const { data } = await http.get('/api/user/password-policy', {
      headers: { 'X-Skip-Auth-Redirect': '1' },
    });
    if (data.data) {
      Object.assign(passwordPolicy, data.data);
    }
    passwordPolicyLoaded.value = true;
  } catch {
    // Use defaults
  }
}

function resetForm() {
  form.password = '';
  form.password_confirmation = '';
  formRef.value?.clearValidate();
}

async function handleSubmit() {
  if (!formRef.value) return;

  await formRef.value.validate(async (valid) => {
    if (!valid) return;

    loading.value = true;
    try {
      await http.put(`/api/users/${props.user.id}/reset-password`, {
        password: form.password,
        password_confirmation: form.password_confirmation,
      });
      ElMessage.success('Contraseña actualizada correctamente');
      resetForm();
      emit('update:modelValue', false);
      emit('saved');
    } catch (error: any) {
      if (error.response?.status === 422) {
        const errors = error.response.data?.data?.errors || error.response.data?.errors || {};
        const firstError = Object.values(errors)[0];
        ElMessage.error(Array.isArray(firstError) ? firstError[0] : 'Error de validación');
      } else if (error.response?.status === 403) {
        ElMessage.error(error.response.data?.message || 'No tiene permisos para esta acción');
      } else {
        ElMessage.error('Error al actualizar la contraseña');
      }
    } finally {
      loading.value = false;
    }
  });
}

watch(() => props.modelValue, (visible) => {
  if (visible) {
    resetForm();
    loadPasswordPolicy();
  }
});

onMounted(() => {
  loadPasswordPolicy();
});
</script>
