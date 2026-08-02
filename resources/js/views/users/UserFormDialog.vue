<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="(val: boolean) => emit('update:modelValue', val)"
    :title="mode === 'create' ? 'Nuevo Usuario' : 'Editar Usuario'"
    :subtitle="mode === 'create' ? 'Registre un nuevo usuario en el sistema' : 'Actualice la información del usuario'"
    :icon="mode === 'create' ? UserPlusIcon : UserCogIcon"
    :width="dialogWidth"
    confirm-text="Guardar"
    @confirm="save"
    @cancel="handleCancel"
  >
    <div v-loading="loading" element-loading-text="Cargando datos...">
      <el-form :model="form" label-position="top" :rules="formRules" ref="formRef">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1">
        <el-form-item label="Tipo de Identificación" prop="identification_type_id" required>
          <el-select v-model="form.identification_type_id" class="w-full" placeholder="Seleccione">
            <el-option v-for="t in identificationTypes" :key="t.id" :label="t.name" :value="t.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="Número de Identificación" prop="identification_number" required>
          <el-input v-model="form.identification_number" placeholder="Ingrese el número" />
        </el-form-item>
        <el-form-item label="Nombre de usuario" prop="user_name" required>
          <el-input v-model="form.user_name" placeholder="Ej: juan.perez" :disabled="isSuperAdmin" />
        </el-form-item>
        <el-form-item label="Primer Nombre" prop="first_name" required>
          <el-input v-model="form.first_name" placeholder="Juan" />
        </el-form-item>
        <el-form-item label="Segundo Nombre">
          <el-input v-model="form.middle_name" placeholder="Carlos" />
        </el-form-item>
        <el-form-item label="Primer Apellido" prop="last_name" required>
          <el-input v-model="form.last_name" placeholder="Pérez" />
        </el-form-item>
        <el-form-item label="Segundo Apellido">
          <el-input v-model="form.sur_name" placeholder="García" />
        </el-form-item>
        <el-form-item label="Correo Electrónico" prop="email" required>
          <el-input v-model="form.email" type="email" placeholder="correo@ejemplo.com" />
        </el-form-item>
        <el-form-item label="Cargo / Posición">
          <el-input v-model="form.job_title" placeholder="Ej: Médico, Enfermero, etc." />
        </el-form-item>
        <el-form-item v-if="mode === 'create'" label="Contraseña" prop="password" required>
          <el-input v-model="form.password" type="password" placeholder="Mínimo 10 caracteres" show-password />
        </el-form-item>
        <el-form-item v-if="mode === 'create'" label="Confirmar Contraseña" prop="password_confirmation" required>
          <el-input v-model="form.password_confirmation" type="password" placeholder="Repita la contraseña" show-password />
        </el-form-item>
        <el-form-item label="Roles" class="sm:col-span-2 lg:col-span-1">
          <el-select v-model="form.roles" multiple class="w-full" placeholder="Seleccione roles" :disabled="isSuperAdmin">
            <el-option v-for="r in roles" :key="r.id" :label="r.display_name || r.name" :value="r.name" />
          </el-select>
        </el-form-item>
      </div>
      <div class="flex flex-wrap gap-4 pt-1 pb-2">
        <el-form-item class="!mb-0">
          <el-checkbox v-model="form.is_active" :disabled="isSuperAdmin">Usuario Activo</el-checkbox>
        </el-form-item>
        <el-form-item class="!mb-0">
          <el-checkbox v-model="form.must_change_password" :disabled="isSuperAdmin">Debe cambiar contraseña</el-checkbox>
        </el-form-item>
        <el-form-item class="!mb-0">
          <el-checkbox v-model="form.must_update_profile" :disabled="isSuperAdmin">Debe actualizar perfil</el-checkbox>
        </el-form-item>
      </div>
    </el-form>
    </div>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useWindowSize } from '@vueuse/core';
import { UserPlus as UserPlusIcon, UserCog as UserCogIcon } from '@lucide/vue';
import notify from '@/plugins/toast';
import BaseModal from '@/components/ui/BaseModal.vue';
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
  return '900px';
});

const usersStore = useUsersStore();
const { roles, identificationTypes } = storeToRefs(usersStore);

const loading = ref(false);
const formRef = ref();

const isSuperAdmin = computed(() => props.user?.user_name === 'superadmin');

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

const formRules = {
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
    { min: 10, message: 'Mínimo 10 caracteres', trigger: 'blur' }
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
};

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
