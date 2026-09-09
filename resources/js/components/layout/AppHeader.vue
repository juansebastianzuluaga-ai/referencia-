<template>
  <header
    class="fixed top-0 left-0 right-0 h-[56px] flex items-center justify-between pr-4 z-50 transition-colors duration-300"
    :style="{ background: layout.isDarkMode ? '#11151f' : 'linear-gradient(165deg, var(--rf-primary) 0%, var(--rf-primary-2) 100%)', borderBottom: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : 'none' }"
  >
    <div class="flex items-center h-full">
      <div
        class="h-full flex items-center transition-all duration-200 shrink-0 overflow-hidden w-[52px] md:px-4 px-2"
        :class="layout.isSidebarCollapsed ? 'md:w-[64px] md:justify-center md:px-0' : 'md:w-[240px] md:justify-start md:px-4'"
      >
        <div class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center overflow-hidden bg-white" :style="{ boxShadow: layout.isDarkMode ? 'none' : '0 0 0 2px rgba(255,255,255,0.4)' }">
          <img :src="logoAvatar" alt="Santa Bárbara" class="w-7 h-7 object-contain" />
        </div>
        <div
          class="hidden md:flex flex-col ml-2.5 overflow-hidden whitespace-nowrap transition-all duration-200"
          :class="layout.isSidebarCollapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[160px]'"
        >
          <strong class="block text-sm font-semibold leading-tight" style="color:#ffffff;">Santa Bárbara</strong>
          <span class="text-[10px] font-light tracking-wide" style="color:rgba(255,255,255,0.65);">Clínica de Alta Complejidad</span>
        </div>
      </div>

      <button
        class="md:hidden w-9 h-9 rounded-lg flex items-center justify-center mx-3 transition-colors shrink-0 border-none cursor-pointer hover:bg-white/10"
        style="color:#fff;"
        title="Mostrar/ocultar menu"
        @click="layout.toggleMobileMenu"
      >
        <MenuIcon class="w-4.5 h-4.5" />
      </button>

      <button
        class="hidden md:flex w-9 h-9 rounded-lg items-center justify-center mx-3 transition-colors shrink-0 border-none cursor-pointer hover:bg-white/10"
        style="color:#fff;"
        title="Mostrar/ocultar sidebar"
        @click="layout.toggleSidebar"
      >
        <MenuIcon class="w-4.5 h-4.5" />
      </button>

      <nav class="hidden md:flex items-center gap-1.5 text-[13px] font-medium" style="color:rgba(255,255,255,0.75);">
        <span class="capitalize">{{ $route.name }}</span>
      </nav>
    </div>

    <div class="flex items-center gap-1.5">
      <NotificationCenter :light="false" />

      <button
        class="w-9 h-9 rounded-lg flex items-center justify-center transition-all border-none cursor-pointer hover:scale-110 hover:bg-white/10"
        style="color:#fff;"
        :title="layout.isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        @click="layout.toggleDarkMode"
      >
        <component :is="layout.isDarkMode ? SunIcon : MoonIcon" class="w-5 h-5" />
      </button>

      <div class="w-px h-5 mx-1" style="background:rgba(255,255,255,0.18);"></div>

      <el-dropdown trigger="click" @command="handleCommand">
        <button class="flex items-center gap-2 py-1 pr-2 pl-1 rounded-lg transition-colors border-none cursor-pointer outline-none md:gap-2 gap-0 hover:bg-white/10">
          <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden text-white" :style="{ background: layout.isDarkMode ? 'linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2))' : 'rgba(255,255,255,0.2)' }">
            <img :src="logoAvatarWhite" alt="Usuario" class="w-7 h-8 object-contain" />
          </div>
          <div class="hidden md:block text-left">
            <div class="text-[12.5px] font-medium leading-tight whitespace-nowrap" style="color:#fff;">{{ auth.user?.full_name || 'Usuario' }}</div>
            <div class="text-[10.5px] leading-none mt-0.5" style="color:rgba(255,255,255,0.55);">{{ auth.user?.job_title || 'Personal' }}</div>
          </div>
          <ChevronDownIcon class="hidden md:block w-3.5 h-3.5 ml-0.5" style="color:rgba(255,255,255,0.55);" />
        </button>

        <template #dropdown>
          <el-dropdown-menu class="w-56">
            <div class="px-4 py-3 bg-gray-50 flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center overflow-hidden">
                <img :src="logoAvatar" alt="Usuario" class="w-8 h-8 object-contain" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-[13px] font-semibold text-gray-900 truncate">{{ auth.user?.full_name }}</div>
                <div class="text-[11.5px] text-gray-500 truncate">{{ auth.user?.user_name }}</div>
              </div>
            </div>
            <el-dropdown-item divided command="profile" :icon="UserIcon">Mi perfil</el-dropdown-item>
            <el-dropdown-item command="password" :icon="KeyIcon">Cambiar contrasena</el-dropdown-item>
            <el-dropdown-item divided command="logout" :icon="LogOutIcon" class="!text-red-600 hover:!bg-red-50 hover:!text-red-700">Cerrar sesion</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useLayoutStore } from '@/stores/layout';
import { useAuthStore } from '@/stores/auth';
import NotificationCenter from './NotificationCenter.vue';
import {
  ChevronDown as ChevronDownIcon,
  Key as KeyIcon,
  LogOut as LogOutIcon,
  Menu as MenuIcon,
  Moon as MoonIcon,
  Sun as SunIcon,
  User as UserIcon,
} from '@lucide/vue';

const layout = useLayoutStore();
const auth = useAuthStore();
const router = useRouter();

// Public images served from /public
const logoLight = '/images/logo.png';
const logoWhite = '/images/logo-w.png';
const logoAvatar = '/images/logo-avatar.png';
const logoAvatarWhite = '/images/logo-avatar-w.png';

const userInitials = computed(() => {
  const name = auth.user?.first_name || 'U';
  const lastName = auth.user?.last_name || '';

  return `${name.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
});

function handleCommand(command: string): void {
  if (command === 'logout') {
    void auth.logout();
  } else if (command === 'profile') {
    void router.push({ name: 'profile', query: { tab: 'profile' } });
  } else if (command === 'password') {
    void router.push({ name: 'profile', query: { tab: 'password' } });
  }
}
</script>
