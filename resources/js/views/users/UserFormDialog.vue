<template>
  <el-dialog
    :model-value="modelValue"
    @update:model-value="(val: boolean) => emit('update:modelValue', val)"
    :width="dialogWidth"
    class="user-form-dialog"
    :show-close="false"
    :close-on-click-modal="false"
    append-to-body
    align-center
  >
    <div class="ufd-content">
      <button type="button" class="ufd-close-btn" @click="handleCancel">
        <component :is="XIcon" class="w-4 h-4" />
      </button>

      <!-- Header degradado -->
      <div class="ufd-head">
        <div class="ufd-head-glow ufd-head-glow-1"></div>
        <div class="ufd-head-glow ufd-head-glow-2"></div>
        <div class="ufd-head-icon">
          <component :is="mode === 'create' ? UserPlusIcon : UserCogIcon" class="w-6 h-6" />
        </div>
        <div class="ufd-head-info">
          <p class="ufd-head-title">{{ mode === 'create' ? 'Nuevo Usuario' : 'Editar Usuario' }}</p>
          <p class="ufd-head-sub">{{ mode === 'create' ? 'Registre un nuevo usuario en el sistema' : 'Actualice la información del usuario' }}</p>
        </div>
      </div>

      <div class="ufd-body" v-loading="loading" element-loading-text="Cargando datos...">
        <el-form :model="form" label-position="top" :rules="formRules" ref="formRef" class="ufd-form">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-3 gap-y-2">
            <el-form-item prop="identification_type_id" required>
              <template #label><span class="ufd-label"><component :is="IdCardIcon" class="w-3.5 h-3.5" /> Tipo de Identificación</span></template>
              <el-select v-model="form.identification_type_id" class="w-full" placeholder="Seleccione">
                <el-option v-for="t in identificationTypes" :key="t.id" :label="t.name" :value="t.id" />
              </el-select>
            </el-form-item>
            <el-form-item prop="identification_number" required>
              <template #label><span class="ufd-label"><component :is="HashIcon" class="w-3.5 h-3.5" /> Número de Identificación</span></template>
              <el-input v-model="form.identification_number" placeholder="Ingrese el número">
                <template #prefix><component :is="HashIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item prop="user_name" required>
              <template #label><span class="ufd-label"><component :is="UserIcon" class="w-3.5 h-3.5" /> Nombre de usuario</span></template>
              <el-input v-model="form.user_name" placeholder="Ej: juan.perez" :disabled="isSuperAdmin">
                <template #prefix><component :is="UserIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item prop="first_name" required>
              <template #label><span class="ufd-label"><component :is="UserIcon" class="w-3.5 h-3.5" /> Primer Nombre</span></template>
              <el-input v-model="form.first_name" placeholder="Juan">
                <template #prefix><component :is="UserIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item>
              <template #label><span class="ufd-label"><component :is="UserIcon" class="w-3.5 h-3.5" /> Segundo Nombre</span></template>
              <el-input v-model="form.middle_name" placeholder="Carlos">
                <template #prefix><component :is="UserIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item prop="last_name" required>
              <template #label><span class="ufd-label"><component :is="UserIcon" class="w-3.5 h-3.5" /> Primer Apellido</span></template>
              <el-input v-model="form.last_name" placeholder="Pérez">
                <template #prefix><component :is="UserIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item>
              <template #label><span class="ufd-label"><component :is="UserIcon" class="w-3.5 h-3.5" /> Segundo Apellido</span></template>
              <el-input v-model="form.sur_name" placeholder="García">
                <template #prefix><component :is="UserIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item prop="email" required>
              <template #label><span class="ufd-label"><component :is="MailIcon" class="w-3.5 h-3.5" /> Correo Electrónico</span></template>
              <el-input v-model="form.email" type="email" placeholder="correo@ejemplo.com">
                <template #prefix><component :is="MailIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item>
              <template #label><span class="ufd-label"><component :is="BriefcaseIcon" class="w-3.5 h-3.5" /> Cargo / Posición</span></template>
              <el-input v-model="form.job_title" placeholder="Ej: Médico, Enfermero, etc.">
                <template #prefix><component :is="BriefcaseIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="mode === 'create'" prop="password" required>
              <template #label><span class="ufd-label"><component :is="LockIcon" class="w-3.5 h-3.5" /> Contraseña</span></template>
              <el-input v-model="form.password" type="password" :placeholder="`Mínimo ${passwordPolicy.min_length} caracteres`" show-password>
                <template #prefix><component :is="LockIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
                <template #append>
                  <el-tooltip content="Generar contraseña segura" placement="top">
                    <button type="button" class="ufd-password-btn" @click="generarContrasena">
                      <component :is="WandIcon" class="w-3.5 h-3.5" />
                    </button>
                  </el-tooltip>
                  <el-tooltip content="Copiar contraseña" placement="top">
                    <button type="button" class="ufd-password-btn" @click="copiarContrasena">
                      <component :is="CopyIcon" class="w-3.5 h-3.5" />
                    </button>
                  </el-tooltip>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="mode === 'create'" prop="password_confirmation" required>
              <template #label><span class="ufd-label"><component :is="LockIcon" class="w-3.5 h-3.5" /> Confirmar Contraseña</span></template>
              <el-input v-model="form.password_confirmation" type="password" placeholder="Repita la contraseña" show-password>
                <template #prefix><component :is="LockIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
              </el-input>
            </el-form-item>
          </div>

          <!-- Roles -->
          <div class="ufd-roles-card">
            <p class="ufd-roles-title"><component :is="ShieldIcon" class="w-3.5 h-3.5" /> Roles</p>
            <el-form-item class="!mb-0">
              <el-select v-model="form.roles" multiple class="w-full" placeholder="Seleccione roles" :disabled="isSuperAdmin">
                <template #prefix><component :is="UsersRoundIcon" class="w-3.5 h-3.5 ufd-input-icon" /></template>
                <el-option v-for="r in roles" :key="r.id" :label="r.display_name || r.name" :value="r.name" />
              </el-select>
            </el-form-item>
          </div>

          <!-- Toggles -->
          <div class="ufd-toggles">
            <div class="ufd-toggle-item">
              <el-switch v-model="form.is_active" :disabled="isSuperAdmin" />
              <component :is="UserCheckIcon" class="w-3.5 h-3.5 ufd-toggle-icon" />
              <span>Usuario Activo</span>
            </div>
            <div class="ufd-toggle-sep"></div>
            <div class="ufd-toggle-item">
              <el-switch v-model="form.must_change_password" :disabled="isSuperAdmin" />
              <component :is="LockIcon" class="w-3.5 h-3.5 ufd-toggle-icon" />
              <span>Debe cambiar contraseña</span>
            </div>
            <div class="ufd-toggle-sep"></div>
            <div class="ufd-toggle-item">
              <el-switch v-model="form.must_update_profile" :disabled="isSuperAdmin" />
              <component :is="UserIcon" class="w-3.5 h-3.5 ufd-toggle-icon" />
              <span>Debe actualizar perfil</span>
            </div>
          </div>
        </el-form>
      </div>

      <div class="ufd-footer">
        <button type="button" class="ufd-cancel-btn" @click="handleCancel">
          <component :is="XIcon" class="w-3.5 h-3.5" /> Cancelar
        </button>
        <button type="button" class="ufd-save-btn" :disabled="loading" @click="save">
          <component :is="loading ? Loader2Icon : SaveIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" /> Guardar
        </button>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { useWindowSize } from '@vueuse/core';
import http from '@/plugins/axios';
import {
  UserPlus as UserPlusIcon,
  UserCog as UserCogIcon,
  X as XIcon,
  IdCard as IdCardIcon,
  Hash as HashIcon,
  User as UserIcon,
  Mail as MailIcon,
  Briefcase as BriefcaseIcon,
  Lock as LockIcon,
  Wand2 as WandIcon,
  Copy as CopyIcon,
  Shield as ShieldIcon,
  UsersRound as UsersRoundIcon,
  UserCheck as UserCheckIcon,
  Save as SaveIcon,
  Loader2 as Loader2Icon,
} from '@lucide/vue';
import notify from '@/plugins/toast';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';

const props = defineProps<{
  modelValue: boolean;
  mode: 'create' | 'edit';
  user?: any;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved'): void;
}>();

const { width: windowWidth } = useWindowSize();
const dialogWidth = computed(() => {
  if (windowWidth.value < 640) return '95vw';
  if (windowWidth.value < 1024) return '600px';
  if (windowWidth.value < 1280) return '760px';
  return '1040px';
});

const usersStore = useUsersStore();
const { roles, identificationTypes } = storeToRefs(usersStore);

const loading = ref(false);
const formRef = ref();

const isSuperAdmin = computed(() => props.user?.is_protected ?? false);

function emptyForm() {
  return {
    id: null as number | null,
    identification_type_id: null,
    identification_number: '',
    user_name: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    sur_name: '',
    email: '',
    job_title: '',
    password: '',
    password_confirmation: '',
    is_active: true,
    must_change_password: false,
    must_update_profile: false,
    roles: [] as string[],
  };
}

const form = ref(emptyForm());

// Misma política real que usa el reseteo de contraseña (UserPasswordDialog) —
// antes esta creaba usuarios con solo "mínimo 10 caracteres" mientras que
// resetear sí exigía mayúsculas/números/símbolos.
const passwordPolicy = reactive({
  min_length: 8,
  require_special: true,
  require_numbers: true,
  require_uppercase: true,
});
const passwordPolicyLoaded = ref(false);

async function loadPasswordPolicy() {
  if (passwordPolicyLoaded.value) return;
  try {
    const { data } = await http.get('/api/user/password-policy', { headers: { 'X-Skip-Auth-Redirect': '1' } });
    if (data.data) Object.assign(passwordPolicy, data.data);
    passwordPolicyLoaded.value = true;
  } catch {
    // se usan los valores por defecto
  }
}

/** Genera una contraseña que ya cumple la política activa, para no depender de que el admin invente una a mano. */
function generarContrasena() {
  const mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  const minus = 'abcdefghijkmnpqrstuvwxyz';
  const numeros = '23456789';
  const simbolos = '!@#$%&*-_+=';

  let alfabeto = minus;
  const obligatorios: string[] = [minus[Math.floor(Math.random() * minus.length)]];
  if (passwordPolicy.require_uppercase) {
    alfabeto += mayus;
    obligatorios.push(mayus[Math.floor(Math.random() * mayus.length)]);
  }
  if (passwordPolicy.require_numbers) {
    alfabeto += numeros;
    obligatorios.push(numeros[Math.floor(Math.random() * numeros.length)]);
  }
  if (passwordPolicy.require_special) {
    alfabeto += simbolos;
    obligatorios.push(simbolos[Math.floor(Math.random() * simbolos.length)]);
  }

  const largo = Math.max(passwordPolicy.min_length, 12);
  const resto = Array.from({ length: largo - obligatorios.length }, () => alfabeto[Math.floor(Math.random() * alfabeto.length)]);
  const combinado = [...obligatorios, ...resto].sort(() => Math.random() - 0.5).join('');

  form.value.password = combinado;
  form.value.password_confirmation = combinado;
}

async function copiarContrasena() {
  if (!form.value.password) {
    notify.warning('Primero genere o escriba una contraseña');
    return;
  }
  try {
    await navigator.clipboard.writeText(form.value.password);
    notify.success('Contraseña copiada al portapapeles');
  } catch {
    notify.error('No se pudo copiar la contraseña');
  }
}

const formRules = computed(() => ({
  identification_type_id: [{ required: true, message: 'El tipo de identificación es requerido', trigger: 'change' }],
  identification_number: [{ required: true, message: 'El número de identificación es requerido', trigger: 'blur' }],
  user_name: [{ required: true, message: 'El nombre de usuario es requerido', trigger: 'blur' }],
  first_name: [{ required: true, message: 'El primer nombre es requerido', trigger: 'blur' }],
  last_name: [{ required: true, message: 'El primer apellido es requerido', trigger: 'blur' }],
  email: [
    { required: true, message: 'El correo electrónico es requerido', trigger: 'blur' },
    { type: 'email', message: 'Ingrese un correo válido', trigger: 'blur' }
  ],
  password: [
    { required: true, message: 'La contraseña es requerida', trigger: 'blur' },
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
    { required: true, message: 'Confirme la contraseña', trigger: 'blur' },
    {
      validator: (rule: any, value: any, callback: any) => {
        if (value !== form.value.password) {
          callback(new Error('Las contraseñas no coinciden'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
}));

function fillFormFromUser(u: any) {
  form.value = {
    id: u.id,
    identification_type_id: u.identification_type?.id ?? null,
    identification_number: u.identification_number,
    user_name: u.user_name,
    first_name: u.first_name,
    middle_name: u.middle_name || '',
    last_name: u.last_name,
    sur_name: u.sur_name || '',
    email: u.email,
    job_title: u.job_title || '',
    password: '',
    password_confirmation: '',
    is_active: u.is_active,
    must_change_password: u.must_change_password || false,
    must_update_profile: u.must_update_profile || false,
    roles: (u.roles || []).map((r: any) => r.name),
  };
}

async function loadFormData() {
  if (props.mode === 'edit' && props.user) {
    loading.value = true;
    try {
      const u = await usersStore.getUser(props.user.id);
      fillFormFromUser(u);
    } catch {
      fillFormFromUser(props.user);
    } finally {
      loading.value = false;
    }
  } else {
    form.value = emptyForm();
    loadPasswordPolicy();
  }
}

watch(() => props.modelValue, (visible) => {
  if (visible) {
    loadFormData();
  }
});

function handleCancel() {
  emit('update:modelValue', false);
}

async function save() {
  try {
    if (formRef.value) {
      await formRef.value.validate();
    }
  } catch {
    notify.error('Por favor, corrija los errores en el formulario');
    return;
  }

  loading.value = true;
  try {
    const payload: any = {
      identification_type_id: form.value.identification_type_id,
      identification_number: form.value.identification_number,
      user_name: form.value.user_name,
      first_name: form.value.first_name,
      middle_name: form.value.middle_name,
      last_name: form.value.last_name,
      sur_name: form.value.sur_name,
      email: form.value.email,
      job_title: form.value.job_title,
      is_active: !!form.value.is_active,
      must_change_password: !!form.value.must_change_password,
      must_update_profile: !!form.value.must_update_profile,
      roles: form.value.roles || [],
    };

    if (props.mode === 'create') {
      payload.password = form.value.password;
      payload.password_confirmation = form.value.password_confirmation;
      await usersStore.createUser(payload);
    } else if (props.mode === 'edit' && form.value.id) {
      if (form.value.password) {
        payload.password = form.value.password;
        payload.password_confirmation = form.value.password_confirmation;
      }
      await usersStore.updateUser(form.value.id, payload);
    }

    notify.success(props.mode === 'create' ? 'Usuario creado correctamente' : 'Usuario actualizado correctamente');
    emit('update:modelValue', false);
    emit('saved');
  } catch (e: any) {
    console.error(e);
    if (e.response?.status === 422) {
      const errors = e.response.data?.data?.errors || e.response.data?.errors;
      if (errors) {
        const errorMessages = Object.values(errors).flat().join('\n');
        notify.error(errorMessages || 'Error de validación');
      } else {
        notify.error(e.response.data?.message || 'Error de validación');
      }
    } else {
      notify.error('Error al guardar el usuario');
    }
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
:deep(.user-form-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.user-form-dialog .el-dialog__header) { display: none; }
:deep(.user-form-dialog .el-dialog__body) { padding: 0; overflow: hidden; }

.ufd-content { position: relative; background: #fff; }

.ufd-close-btn {
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
.ufd-close-btn:hover { color: #DC2626; border-color: #FCA5A5; background: #FEF2F2; }

/* Header degradado */
.ufd-head {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: 1.3rem 3rem 1.3rem 1.4rem;
  background: linear-gradient(120deg, #dbeafe 0%, #e0e7ff 55%, #ede9fe 100%);
}
.ufd-head-glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(20px);
  pointer-events: none;
}
.ufd-head-glow-1 { top: -50px; right: 10%; width: 160px; height: 160px; background: rgba(255,255,255,.5); }
.ufd-head-glow-2 { bottom: -60px; left: 20%; width: 140px; height: 140px; background: rgba(129,140,248,.25); }
.ufd-head-icon {
  position: relative; z-index: 1;
  width: 46px; height: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  display: grid; place-items: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 6px 16px rgba(79, 70, 229, .35);
}
.ufd-head-info { position: relative; z-index: 1; min-width: 0; }
.ufd-head-title { margin: 0; color: #1E1B4B; font-size: 1.1rem; font-weight: 800; }
.ufd-head-sub { margin: .2rem 0 0; color: #4c4a6e; font-size: .78rem; }

.ufd-body {
  padding: 1rem 1.3rem;
  max-height: 72vh;
  overflow-y: auto;
}
.ufd-label {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  color: #475569;
  font-size: .78rem;
  font-weight: 600;
}
.ufd-input-icon { color: #94a3b8; }
.ufd-password-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border: none; background: transparent;
  color: #64748b; cursor: pointer; border-radius: 6px; transition: all .15s ease;
}
.ufd-password-btn:hover { color: var(--rf-primary); background: #eef2ff; }

.ufd-roles-card {
  margin-top: .5rem;
  padding: .8rem .9rem;
  border-radius: 12px;
  border: 1px solid #e0e7ff;
  background: #f8faff;
}
.ufd-roles-title {
  display: flex; align-items: center; gap: .35rem;
  margin: 0 0 .5rem;
  font-size: .78rem;
  font-weight: 800;
  color: #4338CA;
}

.ufd-toggles {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: .9rem;
  padding-top: .8rem;
  border-top: 1px solid #f1f5f9;
}
.ufd-toggle-item {
  display: flex;
  align-items: center;
  gap: .45rem;
  font-size: .78rem;
  font-weight: 600;
  color: #334155;
}
.ufd-toggle-icon { color: #94a3b8; }
.ufd-toggle-sep {
  width: 1px;
  align-self: stretch;
  background: #e2e8f0;
}

.ufd-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: .6rem;
  padding: .8rem 1.3rem;
  background: #fff;
  border-top: 1px solid #F1F5F9;
}
.ufd-cancel-btn {
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
.ufd-cancel-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.ufd-save-btn {
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
.ufd-save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #5B52F0, #8B47E8);
  box-shadow: 0 6px 20px rgba(124, 58, 237, .4);
}
.ufd-save-btn:disabled { opacity: .65; cursor: default; }

:deep(.ufd-form .el-form-item) { margin-bottom: 0; }
:deep(.ufd-form .el-form-item__label) { padding-bottom: .25rem; }
:deep(.ufd-form .el-input__wrapper),
:deep(.ufd-form .el-select__wrapper) {
  box-shadow: 0 0 0 1px #dce7f2 inset;
  border-radius: 9px;
}
:deep(.ufd-form .el-input__wrapper.is-focus),
:deep(.ufd-form .el-select__wrapper.is-focus) { box-shadow: 0 0 0 2px #6366f1 inset; }
</style>
