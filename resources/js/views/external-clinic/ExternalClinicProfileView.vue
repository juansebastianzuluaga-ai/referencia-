<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <ContentCard title="Datos de la clínica">
      <el-form :model="profileForm" label-position="top" @submit.prevent="updateProfile">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <el-form-item label="Razón social">
            <el-input v-model="profileForm.business_name" disabled />
          </el-form-item>
          <el-form-item label="NIT">
            <el-input v-model="profileForm.nit" disabled />
          </el-form-item>
          <el-form-item label="Nombre comercial">
            <el-input v-model="profileForm.trade_name" placeholder="Opcional" clearable />
          </el-form-item>
          <el-form-item label="Correo electrónico">
            <el-input v-model="profileForm.email" placeholder="correo@clinica.com" clearable />
          </el-form-item>
          <el-form-item label="Teléfono">
            <el-input v-model="profileForm.phone" placeholder="Ej: (601) 123 4567" clearable />
          </el-form-item>
          <el-form-item label="Celular">
            <el-input v-model="profileForm.mobile" placeholder="Opcional" clearable />
          </el-form-item>
        </div>

        <el-form-item label="Dirección">
          <el-input v-model="profileForm.address" type="textarea" :rows="2" placeholder="Dirección completa" />
        </el-form-item>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <el-form-item label="Ciudad">
            <el-input v-model="profileForm.city" placeholder="Ciudad" clearable />
          </el-form-item>
          <el-form-item label="Departamento">
            <el-select v-model="profileForm.department" placeholder="Seleccione" class="w-full">
              <el-option v-for="dept in departments" :key="dept" :label="dept" :value="dept" />
            </el-select>
          </el-form-item>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <el-form-item label="Representante legal">
            <el-input v-model="profileForm.legal_rep_name" placeholder="Nombre completo" clearable />
          </el-form-item>
          <el-form-item label="Número de identificación">
            <el-input v-model="profileForm.legal_rep_id_number" placeholder="Número de documento" clearable />
          </el-form-item>
        </div>

        <div class="flex justify-end">
          <el-button type="primary" :loading="updating" native-type="submit">
            Guardar cambios
          </el-button>
        </div>
      </el-form>
    </ContentCard>

    <ContentCard title="Cambiar contraseña">
      <el-form :model="passwordForm" label-position="top" @submit.prevent="changePassword">
        <el-form-item label="Contraseña actual">
          <el-input v-model="passwordForm.current_password" type="password" placeholder="Contraseña actual" show-password />
        </el-form-item>
        <el-form-item label="Nueva contraseña">
          <el-input v-model="passwordForm.password" type="password" placeholder="Mínimo 8 caracteres" show-password />
        </el-form-item>
        <el-form-item label="Confirmar nueva contraseña">
          <el-input v-model="passwordForm.password_confirmation" type="password" placeholder="Repita la contraseña" show-password />
        </el-form-item>

        <div class="flex justify-end">
          <el-button type="primary" :loading="changingPassword" native-type="submit">
            Cambiar contraseña
          </el-button>
        </div>
      </el-form>
    </ContentCard>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { useExternalClinicAuthStore } from '@/stores/externalClinicAuth';
import ContentCard from '@/components/ui/ContentCard.vue';

const auth = useExternalClinicAuthStore();
const clinic = computed(() => auth.clinic);

const departments = [
  'Amazonas', 'Antioquia', 'Arauca', 'Atlántico', 'Bogotá D.C.', 'Bolívar', 'Boyacá', 'Caldas',
  'Caquetá', 'Casanare', 'Cauca', 'Cesar', 'Chocó', 'Córdoba', 'Cundinamarca', 'Guainía',
  'Guaviare', 'Huila', 'La Guajira', 'Magdalena', 'Meta', 'Nariño', 'Norte de Santander',
  'Putumayo', 'Quindío', 'Risaralda', 'San Andrés y Providencia', 'Santander', 'Sucre',
  'Tolima', 'Valle del Cauca', 'Vaupés', 'Vichada',
];

const profileForm = reactive({
  nit: '',
  business_name: '',
  trade_name: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  city: '',
  department: '',
  legal_rep_name: '',
  legal_rep_id_number: '',
});

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updating = ref(false);
const changingPassword = ref(false);

watch(
  () => clinic.value,
  (clinicData) => {
    if (clinicData) {
      Object.assign(profileForm, {
        nit: clinicData.nit,
        business_name: clinicData.business_name,
        trade_name: clinicData.trade_name || '',
        email: clinicData.email,
        phone: clinicData.phone,
        mobile: clinicData.mobile || '',
        address: clinicData.address,
        city: clinicData.city,
        department: clinicData.department,
        legal_rep_name: clinicData.legal_rep_name,
        legal_rep_id_number: clinicData.legal_rep_id_number,
      });
    }
  },
  { immediate: true, deep: true },
);

async function updateProfile() {
  try {
    updating.value = true;
    await auth.updateProfile(profileForm);
    ElMessage.success('Perfil actualizado correctamente');
  } catch {
    ElMessage.error('Error al actualizar el perfil');
  } finally {
    updating.value = false;
  }
}

async function changePassword() {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    ElMessage.error('Las contraseñas no coinciden');
    return;
  }

  try {
    changingPassword.value = true;
    await auth.updatePassword(passwordForm);
    ElMessage.success('Contraseña actualizada. Inicie sesión nuevamente.');
    Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' });
  } catch {
    ElMessage.error('Error al cambiar la contraseña');
  } finally {
    changingPassword.value = false;
  }
}
</script>
