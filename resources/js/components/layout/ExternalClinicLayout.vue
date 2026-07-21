<template>
  <div class="flex flex-col h-screen overflow-hidden bg-blue-50 text-gray-700">
    <header class="bg-[#0D2D6B] text-white h-14 flex items-center px-4 shrink-0 shadow-md">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
          <Stethoscope class="w-5 h-5 text-white" />
        </div>
        <div>
          <h1 class="text-sm font-semibold">Clínica Santa Bárbara</h1>
          <p class="text-[10px] text-white/80">Portal de Clínicas Externas</p>
        </div>
      </div>
      <div class="ml-auto flex items-center gap-3">
        <span class="text-xs text-white/90 hidden sm:inline">
          {{ clinic?.business_name }}
        </span>
        <el-dropdown trigger="click" @command="handleCommand">
          <button class="flex items-center gap-2 text-white hover:bg-white/10 rounded-lg px-2 py-1.5 transition-colors">
            <UserCircle class="w-5 h-5" />
            <ChevronDown class="w-4 h-4" />
          </button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="profile">Mi perfil</el-dropdown-item>
              <el-dropdown-item command="password">Cambiar contraseña</el-dropdown-item>
              <el-dropdown-item divided command="logout">Cerrar sesión</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
      <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shrink-0 hidden md:flex">
        <nav class="flex-1 p-3">
          <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 px-3 pb-2">Menú</div>
          <router-link
            v-for="item in menuItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 no-underline transition-colors hover:bg-blue-50 hover:text-[#0D2D6B]"
            active-class="bg-blue-50 text-[#0D2D6B] font-medium"
          >
            <component :is="item.icon" class="w-4.5 h-4.5" />
            <span class="text-[13.5px]">{{ item.text }}</span>
          </router-link>
        </nav>
      </aside>

      <main class="flex-1 flex flex-col min-h-0 p-4 md:p-6 overflow-y-auto">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import { useExternalClinicAuthStore } from '@/stores/externalClinicAuth';
import { Home, FileText, Lock, UserCircle, ChevronDown, Stethoscope } from '@lucide/vue';

const router = useRouter();
const auth = useExternalClinicAuthStore();
const clinic = computed(() => auth.clinic);

const menuItems = [
  { to: '/panel-clinica/dashboard', icon: Home, text: 'Inicio' },
  { to: '/panel-clinica/perfil', icon: FileText, text: 'Mis datos' },
];

async function handleCommand(command: string) {
  if (command === 'logout') {
    try {
      await auth.logout();
    } catch {
      ElMessage.error('Error al cerrar sesión.');
    }
  } else if (command === 'profile') {
    router.push({ name: 'external-clinic-profile' });
  } else if (command === 'password') {
    ElMessage.info('Función de cambio de contraseña disponible en "Mis datos".');
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
