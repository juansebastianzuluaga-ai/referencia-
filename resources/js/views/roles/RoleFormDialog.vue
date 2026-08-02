<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="(val: boolean) => emit('update:modelValue', val)"
    :title="mode === 'create' ? 'Nuevo Rol' : 'Editar Rol'"
    :subtitle="mode === 'create' ? 'Registre un nuevo rol en el sistema' : 'Actualice la información del rol'"
    :icon="mode === 'create' ? ShieldIcon : ShieldCheckIcon"
    :width="dialogWidth"
    confirm-text="Guardar"
    @confirm="save"
    @cancel="handleCancel"
  >
    <div v-loading="loading" element-loading-text="Cargando datos...">
      <el-form :model="form" label-position="top" :rules="formRules" ref="formRef">
        <div class="grid grid-cols-1 gap-4">
          <el-form-item label="Nombre del Rol" prop="name" required>
            <el-input v-model="form.name" placeholder="Ej: medico-especialista" :disabled="mode === 'edit' && form.name === 'super-admin'" />
            <div class="text-xs text-gray-500 mt-1">Solo letras, números y guiones. Usado internamente en el sistema.</div>
          </el-form-item>
          <el-form-item label="Nombre para Mostrar" prop="display_name" required>
            <el-input v-model="form.display_name" placeholder="Ej: Médico Especialista" />
          </el-form-item>
          <el-form-item label="Descripción">
            <el-input v-model="form.description" type="textarea" :rows="3" placeholder="Descripción del rol y sus responsabilidades" />
          </el-form-item>
          <el-form-item label="Estado">
            <el-checkbox v-model="form.is_active">Rol Activo</el-checkbox>
          </el-form-item>
        </div>
      </el-form>
    </div>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useWindowSize } from '@vueuse/core';
import { Shield as ShieldIcon, ShieldCheck as ShieldCheckIcon } from '@lucide/vue';
import notify from '@/plugins/toast';
import BaseModal from '@/components/ui/BaseModal.vue';
import { useRolesStore } from '@/stores/roles';
import { storeToRefs } from 'pinia';

const props = defineProps<{
  modelValue: boolean;
  mode: 'create' | 'edit';
  role?: any;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'saved'): void;
}>();

const { width: windowWidth } = useWindowSize();
const dialogWidth = computed(() => {
  if (windowWidth.value < 640) return '95vw';
  if (windowWidth.value < 1024) return '500px';
  return '600px';
});

const rolesStore = useRolesStore();
const { permissions } = storeToRefs(rolesStore);

const loading = ref(false);
const formRef = ref();

function emptyForm() {
  return {
    id: null as number | null,
    name: '',
    display_name: '',
    description: '',
    is_active: true,
  };
}

const form = ref(emptyForm());

const formRules = {
  name: [
    { required: true, message: 'El nombre del rol es requerido', trigger: 'blur' },
    { pattern: /^[a-z0-9-]+$/, message: 'Solo letras minúsculas, números y guiones', trigger: 'blur' },
  ],
  display_name: [{ required: true, message: 'El nombre para mostrar es requerido', trigger: 'blur' }],
};

function fillFormFromRole(role: any) {
  form.value = {
    id: role.id,
    name: role.name,
    display_name: role.display_name || '',
    description: role.description || '',
    is_active: role.is_active ?? true,
  };
}

async function loadFormData() {
  if (props.mode === 'edit' && props.role) {
    loading.value = true;
    try {
      const role = await rolesStore.getRole(props.role.id);
      fillFormFromRole(role);
    } catch {
      fillFormFromRole(props.role);
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
      name: form.value.name,
      display_name: form.value.display_name,
      description: form.value.description,
      is_active: !!form.value.is_active,
    };

    if (props.mode === 'create') {
      await rolesStore.createRole(payload);
    } else if (props.mode === 'edit' && form.value.id) {
      await rolesStore.updateRole(form.value.id, payload);
    }

    notify.success(props.mode === 'create' ? 'Rol creado correctamente' : 'Rol actualizado correctamente');
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
      notify.error('Error al guardar el rol');
    }
  } finally {
    loading.value = false;
  }
}
</script>
