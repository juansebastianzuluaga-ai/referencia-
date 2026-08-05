<template>
  <div class="profile-page h-full flex flex-col gap-2 p-3 sm:p-4 overflow-auto">

    <!-- ── Hero ── -->
    <div class="profile-hero shrink-0 animate-fade-in-up"
      style="animation-duration: 0.4s; animation-fill-mode: both;">
      <div class="profile-hero-glow"></div>
      <div class="profile-hero-pattern"></div>
      <div class="profile-hero-content">
        <div class="profile-hero-avatar">
          {{ userInitials }}
        </div>
        <div class="profile-hero-info">
          <h1 class="profile-hero-name">{{ auth.user?.full_name || 'Usuario' }}</h1>
          <p class="profile-hero-role">{{ auth.user?.job_title || 'Personal interno' }}</p>
          <div class="profile-hero-tags">
            <span v-for="role in auth.user?.roles" :key="role.name" class="profile-hero-tag">
              {{ role.display_name || role.name }}
            </span>
          </div>
        </div>
        <div class="profile-hero-spacer"></div>
        <div class="profile-hero-stats">
          <div class="profile-hero-stat">
            <span class="profile-hero-stat-num">{{ auth.user?.user_name }}</span>
            <span class="profile-hero-stat-label">Usuario</span>
          </div>
          <div class="profile-hero-divider"></div>
          <div class="profile-hero-stat">
            <span class="profile-hero-stat-num">{{ auth.user?.email }}</span>
            <span class="profile-hero-stat-label">Correo</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Content ── -->
    <div class="profile-content-panel flex-1 overflow-auto">
      <el-tabs v-model="activeTab" class="profile-tabs">
        <!-- Tab: Información Personal -->
        <el-tab-pane name="profile">
          <template #label>
            <span class="flex items-center gap-1.5">
              <UserIcon class="w-4 h-4" />
              Información Personal
            </span>
          </template>
          <div class="max-w-3xl mx-auto" v-loading="loading">
            <el-form
              ref="profileFormRef"
              :model="profileForm"
              :rules="profileRules"
              label-position="top"
            >
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1">
                <el-form-item label="Tipo de Identificación" prop="identification_type_id" required>
                  <el-select v-model="profileForm.identification_type_id" class="w-full" placeholder="Seleccione">
                    <el-option v-for="t in identificationTypes" :key="t.id" :label="t.name" :value="t.id" />
                  </el-select>
                </el-form-item>
                <el-form-item label="Número de Identificación" prop="identification_number" required>
                  <el-input v-model="profileForm.identification_number" placeholder="Ingrese el número" />
                </el-form-item>
                <el-form-item label="Nombre de Usuario" prop="user_name" required>
                  <el-input v-model="profileForm.user_name" placeholder="Ej: juan.perez" />
                </el-form-item>
                <el-form-item label="Primer Nombre" prop="first_name" required>
                  <el-input v-model="profileForm.first_name" placeholder="Juan" />
                </el-form-item>
                <el-form-item label="Segundo Nombre">
                  <el-input v-model="profileForm.middle_name" placeholder="Carlos" />
                </el-form-item>
                <el-form-item label="Primer Apellido" prop="last_name" required>
                  <el-input v-model="profileForm.last_name" placeholder="Pérez" />
                </el-form-item>
                <el-form-item label="Segundo Apellido">
                  <el-input v-model="profileForm.sur_name" placeholder="García" />
                </el-form-item>
                <el-form-item label="Correo Electrónico" prop="email" required>
                  <el-input v-model="profileForm.email" type="email" placeholder="correo@ejemplo.com" />
                </el-form-item>
                <el-form-item label="Cargo / Posición">
                  <el-input v-model="profileForm.job_title" placeholder="Ej: Médico, Enfermero, etc." />
                </el-form-item>
              </div>

              <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 mt-4">
                <el-button :icon="RotateCcwIcon" @click="fillProfileForm">Cancelar</el-button>
                <el-button type="primary" :icon="SaveIcon" :loading="savingProfile" @click="saveProfile">
                  Guardar Cambios
                </el-button>
              </div>
            </el-form>
          </div>
        </el-tab-pane>

        <!-- Tab: Cambiar Contraseña -->
        <el-tab-pane name="password">
          <template #label>
            <span class="flex items-center gap-1.5">
              <KeyIcon class="w-4 h-4" />
              Cambiar Contraseña
            </span>
          </template>
          <div class="max-w-xl mx-auto">
            <el-alert
              type="info"
              :closable="false"
              :description="passwordPolicyDescription"
              class="mb-6"
            >
              <template #title>
                <div class="flex items-center gap-2">
                  <ShieldIcon class="w-5 h-5" />
                  <span>Seguridad de la contraseña</span>
                </div>
              </template>
            </el-alert>

            <el-form
              ref="passwordFormRef"
              :model="passwordForm"
              :rules="passwordRules"
              label-position="top"
            >
              <el-form-item label="Contraseña Actual" prop="current_password" required>
                <el-input
                  v-model="passwordForm.current_password"
                  type="password"
                  placeholder="Ingrese su contraseña actual"
                  show-password
                />
              </el-form-item>
              <el-form-item label="Nueva Contraseña" prop="password" required>
                <el-input
                  v-model="passwordForm.password"
                  type="password"
                  :placeholder="`Mínimo ${passwordPolicy.min_length} caracteres`"
                  show-password
                />
              </el-form-item>
              <el-form-item label="Confirmar Nueva Contraseña" prop="password_confirmation" required>
                <el-input
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  placeholder="Repita la nueva contraseña"
                  show-password
                />
              </el-form-item>

              <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 mt-4">
                <el-button :icon="RotateCcwIcon" @click="resetPasswordForm">Limpiar</el-button>
                <el-button type="primary" :icon="KeyIcon" :loading="savingPassword" @click="savePassword">
                  Actualizar Contraseña
                </el-button>
              </div>
            </el-form>
          </div>
        </el-tab-pane>
      </el-tabs>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { type FormInstance } from 'element-plus';
import notify from '@/plugins/toast';
import {
  Save as SaveIcon,
  Key as KeyIcon,
  RotateCcw as RotateCcwIcon,
  User as UserIcon,
  Shield as ShieldIcon,
} from '@lucide/vue';
import ContentCard from '@/components/ui/ContentCard.vue';
import { useAuthStore } from '@/stores/auth';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';
import http from '@/plugins/axios';

const auth = useAuthStore();
const usersStore = useUsersStore();
const route = useRoute();
const { identificationTypes } = storeToRefs(usersStore);

const activeTab = ref((route.query.tab as string) || 'profile');
const loading = ref(false);
const savingProfile = ref(false);
const savingPassword = ref(false);

const userInitials = computed(() => {
  const name = auth.user?.first_name || 'U';
  const lastName = auth.user?.last_name || '';
  return `${name.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
});

const profileFormRef = ref<FormInstance>();
const passwordFormRef = ref<FormInstance>();

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

const profileForm = reactive({
  identification_type_id: null as number | null,
  identification_number: '',
  user_name: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  sur_name: '',
  email: '',
  job_title: '',
});

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const profileRules = {
  identification_type_id: [{ required: true, message: 'El tipo de identificación es requerido', trigger: 'change' }],
  identification_number: [{ required: true, message: 'El número de identificación es requerido', trigger: 'blur' }],
  user_name: [{ required: true, message: 'El nombre de usuario es requerido', trigger: 'blur' }],
  first_name: [{ required: true, message: 'El primer nombre es requerido', trigger: 'blur' }],
  last_name: [{ required: true, message: 'El primer apellido es requerido', trigger: 'blur' }],
  email: [
    { required: true, message: 'El correo electrónico es requerido', trigger: 'blur' },
    { type: 'email', message: 'Ingrese un correo válido', trigger: 'blur' },
  ],
};

const passwordRules = computed(() => ({
  current_password: [{ required: true, message: 'Ingrese su contraseña actual', trigger: 'blur' }],
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
        if (value !== passwordForm.password) {
          callback(new Error('Las contraseñas no coinciden'));
        } else {
          callback();
        }
      },
      trigger: 'blur',
    },
  ],
}));

function fillProfileForm() {
  const u = auth.user;
  if (!u) return;
  profileForm.identification_type_id = (u as any).identification_type?.id ?? null;
  profileForm.identification_number = (u as any).identification_number || '';
  profileForm.user_name = u.user_name || '';
  profileForm.first_name = u.first_name || '';
  profileForm.middle_name = (u as any).middle_name || '';
  profileForm.last_name = (u as any).last_name || '';
  profileForm.sur_name = (u as any).sur_name || '';
  profileForm.email = (u as any).email || '';
  profileForm.job_title = u.job_title || '';
}

function resetPasswordForm() {
  passwordForm.current_password = '';
  passwordForm.password = '';
  passwordForm.password_confirmation = '';
  passwordFormRef.value?.clearValidate();
}

async function saveProfile() {
  try {
    await profileFormRef.value?.validate();
  } catch {
    return;
  }

  savingProfile.value = true;
  try {
    const { data } = await http.put('/api/user/profile', { ...profileForm });
    auth.user = data.data;
    notify.success('Perfil actualizado correctamente');
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data?.data?.errors || e.response.data?.errors;
      if (errors) {
        const errorMessages = Object.values(errors).flat().join('\n');
        notify.error(errorMessages || 'Error de validación');
      } else {
        notify.error(e.response.data?.message || 'Error de validación');
      }
    } else {
      notify.error('Error al actualizar el perfil');
    }
  } finally {
    savingProfile.value = false;
  }
}

async function savePassword() {
  try {
    await passwordFormRef.value?.validate();
  } catch {
    return;
  }

  savingPassword.value = true;
  try {
    const { data } = await http.put('/api/user/password', { ...passwordForm });
    auth.user = data.data;
    notify.success('Contraseña actualizada correctamente');
    resetPasswordForm();
  } catch (e: any) {
    if (e.response?.status === 422) {
      const errors = e.response.data?.data?.errors || e.response.data?.errors;
      if (errors) {
        const errorMessages = Object.values(errors).flat().join('\n');
        notify.error(errorMessages || 'Error de validación');
      } else {
        notify.error(e.response.data?.message || 'Error de validación');
      }
    } else {
      notify.error('Error al actualizar la contraseña');
    }
  } finally {
    savingPassword.value = false;
  }
}

onMounted(async () => {
  loading.value = true;
  try {
    await auth.fetchUser();
    fillProfileForm();
    await usersStore.loadIdentificationTypes();
    const { data } = await http.get('/api/user/password-policy', { headers: { 'X-Skip-Auth-Redirect': '1' } });
    Object.assign(passwordPolicy, data.data);
    passwordPolicyLoaded.value = true;
  } finally {
    loading.value = false;
  }
});

watch(activeTab, async (newTab) => {
  if (newTab === 'password' && !passwordPolicyLoaded.value) {
    const { data } = await http.get('/api/user/password-policy', { headers: { 'X-Skip-Auth-Redirect': '1' } });
    Object.assign(passwordPolicy, data.data);
    passwordPolicyLoaded.value = true;
  }
});
</script>

<style scoped>
.profile-page {
  background: linear-gradient(160deg, #eef4fc 0%, #e3edf8 40%, #f0f5fa 100%);
}

/* ── Hero ── */
.profile-hero {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #0D2D6B 0%, #16468E 50%, #1a5290 100%);
  box-shadow: 0 8px 32px rgba(13,45,107,.25);
}
.profile-hero-glow {
  position: absolute;
  top: -40px; right: -30px;
  width: 180px; height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.2), transparent 70%);
  pointer-events: none;
}
.profile-hero-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.04) 1px, transparent 0);
  background-size: 22px 22px;
  pointer-events: none;
}
.profile-hero-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
  z-index: 1;
}
.profile-hero-avatar {
  width: 56px; height: 56px;
  border-radius: 16px;
  background: rgba(255,255,255,0.15);
  border: 2px solid rgba(255,255,255,0.2);
  color: #fff;
  font-size: 20px;
  font-weight: 800;
  display: grid; place-items: center;
  flex-shrink: 0;
  backdrop-filter: blur(10px);
}
.profile-hero-info {
  flex: 1;
  min-width: 0;
}
.profile-hero-name {
  font-size: 1.1rem;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.profile-hero-role {
  font-size: 12px;
  color: rgba(255,255,255,0.6);
  margin: .15rem 0 0;
}
.profile-hero-tags {
  display: flex;
  flex-wrap: wrap;
  gap: .25rem;
  margin-top: .35rem;
}
.profile-hero-tag {
  font-size: 10px;
  font-weight: 600;
  padding: .15rem .5rem;
  border-radius: 6px;
  background: rgba(255,255,255,0.12);
  color: rgba(255,255,255,0.85);
}
.profile-hero-spacer { flex-shrink: 0; }
.profile-hero-stats {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}
.profile-hero-stat {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}
.profile-hero-stat-num {
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.profile-hero-stat-label {
  font-size: 9px;
  color: rgba(255,255,255,0.45);
  text-transform: uppercase;
  letter-spacing: .05em;
}
.profile-hero-divider {
  width: 1px;
  height: 28px;
  background: rgba(255,255,255,0.15);
}

/* ── Content panel ── */
.profile-content-panel {
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(212,222,234,0.65);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(22,70,142,.07);
  backdrop-filter: blur(10px);
  padding: 1rem 1.5rem;
}

.profile-tabs :deep(.el-tabs__header) {
  margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
  .profile-hero-content { flex-wrap: wrap; }
  .profile-hero-stats { display: none; }
}
</style>
