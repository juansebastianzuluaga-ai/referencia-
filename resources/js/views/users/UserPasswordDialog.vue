<template>
  <el-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    width="480px"
    class="upd-dialog"
    :show-close="false"
    :close-on-click-modal="false"
    append-to-body
    align-center
  >
    <div class="upd-content">
      <button type="button" class="upd-close-btn" @click="resetForm(); $emit('update:modelValue', false)">
        <component :is="XIcon" class="w-4 h-4" />
      </button>

      <div class="upd-head">
        <div class="upd-head-glow"></div>
        <div class="upd-head-icon">
          <component :is="KeyRoundIcon" class="w-6 h-6" />
        </div>
        <div class="upd-head-info">
          <p class="upd-head-title">Cambiar contraseña</p>
          <p class="upd-head-sub">{{ user?.full_name || user?.user_name || '' }}</p>
        </div>
      </div>

      <div class="upd-body">
        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="top"
          class="upd-form"
          @submit.prevent="handleSubmit"
        >
          <div class="upd-policy">
            <component :is="ShieldCheckIcon" class="w-4 h-4 upd-policy-icon" />
            <p>{{ passwordPolicyDescription }}</p>
          </div>

          <el-form-item prop="password">
            <template #label><span class="upd-label"><component :is="LockIcon" class="w-3.5 h-3.5" /> Nueva contraseña</span></template>
            <el-input
              v-model="form.password"
              type="password"
              :placeholder="`Mínimo ${passwordPolicy.min_length} caracteres`"
              show-password
            >
              <template #prefix><component :is="LockIcon" class="w-3.5 h-3.5 upd-input-icon" /></template>
            </el-input>
          </el-form-item>

          <el-form-item prop="password_confirmation">
            <template #label><span class="upd-label"><component :is="LockIcon" class="w-3.5 h-3.5" /> Confirmar contraseña</span></template>
            <el-input
              v-model="form.password_confirmation"
              type="password"
              placeholder="Repita la nueva contraseña"
              show-password
            >
              <template #prefix><component :is="LockIcon" class="w-3.5 h-3.5 upd-input-icon" /></template>
            </el-input>
          </el-form-item>
        </el-form>
      </div>

      <div class="upd-footer">
        <button type="button" class="upd-cancel-btn" @click="resetForm(); $emit('update:modelValue', false)">
          <component :is="XIcon" class="w-3.5 h-3.5" /> Cancelar
        </button>
        <button type="button" class="upd-save-btn" :disabled="loading" @click="handleSubmit">
          <component :is="loading ? Loader2Icon : CheckCircleIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" /> Actualizar contraseña
        </button>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { type FormInstance, type FormRules } from 'element-plus';
import notify from '@/plugins/toast';
import {
  ShieldCheck as ShieldCheckIcon,
  KeyRound as KeyRoundIcon,
  CheckCircle as CheckCircleIcon,
  X as XIcon,
  Lock as LockIcon,
  Loader2 as Loader2Icon,
} from '@lucide/vue';
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
      notify.success('Contraseña actualizada correctamente');
      resetForm();
      emit('update:modelValue', false);
      emit('saved');
    } catch (error: any) {
      if (error.response?.status === 422) {
        const errors = error.response.data?.data?.errors || error.response.data?.errors || {};
        const firstError = Object.values(errors)[0];
        notify.error(Array.isArray(firstError) ? firstError[0] : 'Error de validación');
      } else if (error.response?.status === 403) {
        notify.error(error.response.data?.message || 'No tiene permisos para esta acción');
      } else {
        notify.error('Error al actualizar la contraseña');
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

<style scoped>
:deep(.upd-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.upd-dialog .el-dialog__header) { display: none; }
:deep(.upd-dialog .el-dialog__body) { padding: 0; overflow: hidden; }

.upd-content { position: relative; background: #fff; }

.upd-close-btn {
  position: absolute;
  top: .9rem; right: .9rem;
  z-index: 2;
  display: grid;
  place-items: center;
  width: 32px; height: 32px;
  border-radius: 50%;
  border: 1px solid rgba(15,23,42,.08);
  background: #fff;
  color: #94A3B8;
  cursor: pointer;
  transition: all .2s ease;
  box-shadow: 0 2px 8px rgba(15,23,42,.08);
}
.upd-close-btn:hover { color: #DC2626; border-color: #FCA5A5; background: #FEF2F2; }

.upd-head {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: 1.3rem 3rem 1.3rem 1.4rem;
  background: linear-gradient(120deg, #dbeafe 0%, #e0e7ff 55%, #ede9fe 100%);
}
.upd-head-glow {
  position: absolute;
  top: -50px; right: 10%;
  width: 160px; height: 160px;
  border-radius: 50%;
  background: rgba(255,255,255,.5);
  filter: blur(20px);
  pointer-events: none;
}
.upd-head-icon {
  position: relative; z-index: 1;
  width: 46px; height: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  display: grid; place-items: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 6px 16px rgba(79, 70, 229, .35);
}
.upd-head-info { position: relative; z-index: 1; min-width: 0; }
.upd-head-title { margin: 0; color: #1E1B4B; font-size: 1.05rem; font-weight: 800; }
.upd-head-sub { margin: .2rem 0 0; color: #4c4a6e; font-size: .78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.upd-body { padding: 1rem 1.3rem; }
.upd-policy {
  display: flex;
  align-items: flex-start;
  gap: .5rem;
  padding: .6rem .75rem;
  border-radius: 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  margin-bottom: 1rem;
}
.upd-policy-icon { color: #2563eb; flex-shrink: 0; margin-top: .1rem; }
.upd-policy p { margin: 0; font-size: .72rem; color: #1e40af; line-height: 1.5; }

.upd-label {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  color: #475569;
  font-size: .78rem;
  font-weight: 600;
}
.upd-input-icon { color: #94a3b8; }

.upd-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: .6rem;
  padding: .8rem 1.3rem;
  background: #fff;
  border-top: 1px solid #F1F5F9;
}
.upd-cancel-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .55rem 1.1rem;
  border-radius: 10px;
  border: 1px solid #dce7f2;
  background: #fff;
  color: #475569;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s ease;
}
.upd-cancel-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.upd-save-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .55rem 1.2rem;
  border-radius: 10px;
  border: none;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(124, 58, 237, .3);
  transition: all .2s ease;
}
.upd-save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #5B52F0, #8B47E8);
  box-shadow: 0 6px 20px rgba(124, 58, 237, .4);
}
.upd-save-btn:disabled { opacity: .65; cursor: default; }

:deep(.upd-form .el-form-item__label) { padding-bottom: .25rem; }
:deep(.upd-form .el-input__wrapper) {
  box-shadow: 0 0 0 1px #dce7f2 inset;
  border-radius: 9px;
}
:deep(.upd-form .el-input__wrapper.is-focus) { box-shadow: 0 0 0 2px #6366f1 inset; }
</style>
