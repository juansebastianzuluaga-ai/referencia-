<template>
  <el-dialog
    :model-value="modelValue"
    @update:model-value="(val: boolean) => emit('update:modelValue', val)"
    :width="dialogWidth"
    class="role-form-dialog"
    :show-close="false"
    :close-on-click-modal="false"
    append-to-body
    align-center
  >
    <div class="rfd-content">
      <button type="button" class="rfd-close-btn" @click="handleCancel">
        <component :is="XIcon" class="w-4 h-4" />
      </button>

      <div class="rfd-head">
        <div class="rfd-head-glow"></div>
        <div class="rfd-head-icon">
          <component :is="mode === 'create' ? ShieldIcon : ShieldCheckIcon" class="w-6 h-6" />
        </div>
        <div class="rfd-head-info">
          <p class="rfd-head-title">{{ mode === 'create' ? 'Nuevo Rol' : 'Editar Rol' }}</p>
          <p class="rfd-head-sub">{{ mode === 'create' ? 'Registre un nuevo rol en el sistema' : 'Actualice la información del rol' }}</p>
        </div>
      </div>

      <div class="rfd-body" v-loading="loading" element-loading-text="Cargando datos...">
        <el-form :model="form" label-position="top" :rules="formRules" ref="formRef" class="rfd-form">
          <el-form-item prop="name" required>
            <template #label><span class="rfd-label"><component :is="TagIcon" class="w-3.5 h-3.5" /> Nombre del Rol</span></template>
            <el-input v-model="form.name" placeholder="Ej: medico-especialista" :disabled="mode === 'edit' && form.name === 'super-admin'">
              <template #prefix><component :is="TagIcon" class="w-3.5 h-3.5 rfd-input-icon" /></template>
            </el-input>
            <div class="rfd-hint">Solo letras, números y guiones. Usado internamente en el sistema.</div>
          </el-form-item>
          <el-form-item prop="display_name" required>
            <template #label><span class="rfd-label"><component :is="ShieldIcon" class="w-3.5 h-3.5" /> Nombre para Mostrar</span></template>
            <el-input v-model="form.display_name" placeholder="Ej: Médico Especialista">
              <template #prefix><component :is="ShieldIcon" class="w-3.5 h-3.5 rfd-input-icon" /></template>
            </el-input>
          </el-form-item>
          <el-form-item>
            <template #label><span class="rfd-label"><component :is="FileTextIcon" class="w-3.5 h-3.5" /> Descripción</span></template>
            <el-input v-model="form.description" type="textarea" :rows="3" placeholder="Descripción del rol y sus responsabilidades" />
          </el-form-item>

          <div class="rfd-toggles">
            <div class="rfd-toggle-item">
              <el-switch v-model="form.is_active" />
              <component :is="ShieldCheckIcon" class="w-3.5 h-3.5 rfd-toggle-icon" />
              <span>Rol Activo</span>
            </div>
          </div>
        </el-form>
      </div>

      <div class="rfd-footer">
        <button type="button" class="rfd-cancel-btn" @click="handleCancel">
          <component :is="XIcon" class="w-3.5 h-3.5" /> Cancelar
        </button>
        <button type="button" class="rfd-save-btn" :disabled="loading" @click="save">
          <component :is="loading ? Loader2Icon : SaveIcon" class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" /> Guardar
        </button>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useWindowSize } from '@vueuse/core';
import {
  Shield as ShieldIcon,
  ShieldCheck as ShieldCheckIcon,
  X as XIcon,
  Tag as TagIcon,
  FileText as FileTextIcon,
  Save as SaveIcon,
  Loader2 as Loader2Icon,
} from '@lucide/vue';
import notify from '@/plugins/toast';
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

<style scoped>
:deep(.role-form-dialog) {
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 32px 80px rgba(11, 35, 73, .4), 0 0 0 1px rgba(255,255,255,.08);
}
:deep(.role-form-dialog .el-dialog__header) { display: none; }
:deep(.role-form-dialog .el-dialog__body) { padding: 0; overflow: hidden; }

.rfd-content { position: relative; background: #fff; }

.rfd-close-btn {
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
.rfd-close-btn:hover { color: #DC2626; border-color: #FCA5A5; background: #FEF2F2; }

.rfd-head {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: 1.3rem 3rem 1.3rem 1.4rem;
  background: linear-gradient(120deg, #dbeafe 0%, #e0e7ff 55%, #ede9fe 100%);
}
.rfd-head-glow {
  position: absolute;
  top: -50px; right: 10%;
  width: 160px; height: 160px;
  border-radius: 50%;
  background: rgba(255,255,255,.5);
  filter: blur(20px);
  pointer-events: none;
}
.rfd-head-icon {
  position: relative; z-index: 1;
  width: 46px; height: 46px;
  border-radius: 14px;
  background: linear-gradient(135deg, #4F46E5, #7C3AED);
  display: grid; place-items: center;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 6px 16px rgba(79, 70, 229, .35);
}
.rfd-head-info { position: relative; z-index: 1; min-width: 0; }
.rfd-head-title { margin: 0; color: #1E1B4B; font-size: 1.1rem; font-weight: 800; }
.rfd-head-sub { margin: .2rem 0 0; color: #4c4a6e; font-size: .78rem; }

.rfd-body {
  padding: 1rem 1.3rem;
  max-height: 62vh;
  overflow-y: auto;
}
.rfd-label {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  color: #475569;
  font-size: .78rem;
  font-weight: 600;
}
.rfd-input-icon { color: #94a3b8; }
.rfd-hint { font-size: .68rem; color: #94a3b8; margin-top: .25rem; }

.rfd-toggles {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: .5rem;
  padding-top: .8rem;
  border-top: 1px solid #f1f5f9;
}
.rfd-toggle-item {
  display: flex;
  align-items: center;
  gap: .45rem;
  font-size: .78rem;
  font-weight: 600;
  color: #334155;
}
.rfd-toggle-icon { color: #94a3b8; }

.rfd-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: .6rem;
  padding: .8rem 1.3rem;
  background: #fff;
  border-top: 1px solid #F1F5F9;
}
.rfd-cancel-btn {
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
.rfd-cancel-btn:hover { background: #f1f5f9; border-color: #cbd5e1; }
.rfd-save-btn {
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
.rfd-save-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #5B52F0, #8B47E8);
  box-shadow: 0 6px 20px rgba(124, 58, 237, .4);
}
.rfd-save-btn:disabled { opacity: .65; cursor: default; }

:deep(.rfd-form .el-form-item) { margin-bottom: 1rem; }
:deep(.rfd-form .el-form-item__label) { padding-bottom: .25rem; }
:deep(.rfd-form .el-input__wrapper) {
  box-shadow: 0 0 0 1px #dce7f2 inset;
  border-radius: 9px;
}
:deep(.rfd-form .el-input__wrapper.is-focus) { box-shadow: 0 0 0 2px #6366f1 inset; }
</style>
