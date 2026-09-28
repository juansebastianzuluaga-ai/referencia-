<template>
  <header
    class="h-[56px] flex items-center justify-between pr-4 pl-2 md:pl-3 shrink-0 z-40 transition-colors duration-300 relative"
    :style="{ background: layout.isDarkMode ? '#11151f' : '#ffffff', borderBottom: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : '1px solid #e8edf4' }"
  >
    <div class="flex items-center gap-2 flex-1 min-w-0">
      <!-- Hamburguesa móvil -->
      <button
        class="flex md:hidden header-icon-btn"
        :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
        title="Mostrar/ocultar menu"
        @click="layout.toggleMobileMenu"
      >
        <MenuIcon class="w-4.5 h-4.5" />
      </button>

      <!-- Colapsar sidebar (desktop) -->
      <button
        class="hidden md:flex header-icon-btn"
        :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
        title="Mostrar/ocultar sidebar"
        @click="layout.toggleSidebar"
      >
        <MenuIcon class="w-4.5 h-4.5" />
      </button>

      <!-- Logo móvil -->
      <div class="md:hidden flex items-center gap-2 shrink-0">
        <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden bg-white border border-slate-200">
          <img :src="logoAvatar" alt="Santa Bárbara" class="w-6 h-6 object-contain" />
        </div>
      </div>
    </div>

    <div class="flex items-center gap-1">
      <div class="header-action">
        <NotificationCenter :light="!layout.isDarkMode" />
      </div>

      <button
        class="flex header-icon-btn"
        :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
        :title="layout.isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        @click="layout.toggleDarkMode"
      >
        <component :is="layout.isDarkMode ? SunIcon : MoonIcon" class="w-[18px] h-[18px]" />
      </button>

      <div class="w-px h-5 mx-1.5" :style="{ background: layout.isDarkMode ? 'rgba(255,255,255,0.12)' : '#e2e8f0' }"></div>

      <el-dropdown trigger="click" @command="handleCommand">
        <button class="header-user border-none cursor-pointer outline-none">
          <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden shrink-0" style="background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2));">
            <img :src="logoAvatarWhite" alt="Usuario" class="w-7 h-8 object-contain" />
          </div>
          <div class="hidden md:block text-left">
            <div class="text-[12.5px] font-semibold leading-tight whitespace-nowrap" :style="{ color: layout.isDarkMode ? '#e2e8f0' : '#1e293b' }">{{ auth.user?.full_name || 'Usuario' }}</div>
            <div class="text-[10.5px] leading-none mt-0.5" :style="{ color: layout.isDarkMode ? '#64748b' : '#94a3b8' }">{{ auth.user?.job_title || 'Personal' }}</div>
          </div>
          <ChevronDownIcon class="hidden md:block w-3.5 h-3.5 ml-0.5 header-user-caret" :style="{ color: layout.isDarkMode ? '#64748b' : '#94a3b8' }" />
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

// Public images served from /public — binding dinámico para que Vite no
// intente resolverlas como imports en build.
const logoAvatar = '/images/logo-avatar.png';
const logoAvatarWhite = '/images/logo-avatar-w.png';

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

<style scoped>
/* Solo estilos visuales — el display lo controlan las clases de Tailwind
   (flex/hidden md:flex) para que la variante responsive funcione. */
.header-icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: background .15s ease, color .15s ease, transform .15s ease;
}
.header-icon-btn:hover {
  background: #f1f5f9;
}
.header-icon-btn:active {
  transform: scale(0.92);
}
.dark .header-icon-btn:hover {
  background: rgba(255,255,255,0.06);
}

.header-action {
  border-radius: 10px;
  transition: background .15s ease;
}
.header-action:hover {
  background: #f1f5f9;
}
.dark .header-action:hover {
  background: rgba(255,255,255,0.06);
}

.header-user {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 8px 4px 4px;
  border-radius: 12px;
  transition: background .15s ease;
}
.header-user:hover {
  background: #f1f5f9;
}
.dark .header-user:hover {
  background: rgba(255,255,255,0.06);
}
.header-user-caret {
  transition: transform .2s ease;
}
.header-user:hover .header-user-caret {
  transform: translateY(1px);
}
</style>
