<template>
  <aside
    class="h-full overflow-y-auto overflow-x-hidden flex flex-col shrink-0 transition-all duration-300 z-50 fixed md:static top-0 bottom-0"
    :class="[
      layout.isMobileMenuOpen ? 'w-[240px] translate-x-0 shadow-2xl' : 'w-[240px] -translate-x-full md:translate-x-0',
      layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
    ]"
    style="background:linear-gradient(180deg, #1e2d55 0%, #192950 100%); box-shadow: 6px 0 24px rgba(0,0,0,0.25);"
  >
    <!-- Glow decorativo -->
    <div class="sidebar-glow-1"></div>
    <div class="sidebar-glow-2"></div>
    <div class="sidebar-glow-3"></div>
    <div class="sidebar-pattern"></div>

    <div class="sidebar-divider mt-2 mb-2"></div>

    <div class="py-1 flex-1 relative z-10 overflow-y-auto">

      <!-- Principal -->
      <div class="mb-4">
        <div class="sidebar-label" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Principal</div>
        <AppSidebarItem
          to="/dashboard"
          icon="LayoutDashboard"
          text="Panel principal"
        />
      </div>

      <div class="sidebar-divider"></div>

      <!-- Clínicas externas -->
      <div class="mb-4 mt-4">
        <div class="sidebar-label" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Clínicas externas</div>
        <AppSidebarItem
          to="/solicitudes-referencia"
          icon="ClipboardList"
          text="Solicitudes de referencia"
          permission="clinicas.view"
        />
        <AppSidebarItem
          to="/clinicas"
          icon="Hospital"
          text="Clínicas registradas"
          permission="clinicas.view"
        />
      </div>

      <div class="sidebar-divider"></div>

      <!-- Administración -->
      <div class="mb-4 mt-4">
        <div class="sidebar-label" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Administración</div>
        <AppSidebarItem
          to="/roles"
          icon="ShieldCheck"
          text="Roles y permisos"
          permission="roles.view"
        />
        <AppSidebarItem
          to="/configuracion"
          icon="Settings"
          text="Configuración"
          permission="settings.view"
        />
        <AppSidebarItem
          to="/usuarios"
          icon="Users"
          text="Gestión de usuarios"
          permission="users.view"
        />
      </div>

    </div>

    <!-- Footer -->
    <div class="shrink-0 px-4 pb-3 relative z-10">
      <div class="sidebar-divider mb-3"></div>
      <!-- User info + logout -->
      <div v-if="!layout.isSidebarCollapsed" class="flex items-center gap-2 mb-2 px-1">
        <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background:rgba(255,255,255,0.1);">
          <component :is="UserIcon" class="w-3.5 h-3.5" style="color:rgba(255,255,255,0.6);" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[11px] font-medium text-white truncate leading-tight">{{ auth.user?.full_name || 'Usuario' }}</p>
          <p class="text-[9px] truncate" style="color:rgba(255,255,255,0.35);">{{ auth.user?.job_title || 'Personal' }}</p>
        </div>
        <button @click="logout" class="w-6 h-6 rounded flex items-center justify-center shrink-0 cursor-pointer border-none" style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.5);" title="Cerrar sesión">
          <component :is="LogOutIcon" class="w-3 h-3" />
        </button>
      </div>
      <div v-else class="flex justify-center mb-2">
        <button @click="logout" class="w-7 h-7 rounded flex items-center justify-center cursor-pointer border-none" style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.5);" title="Cerrar sesión">
          <component :is="LogOutIcon" class="w-3.5 h-3.5" />
        </button>
      </div>
      <p class="text-center" style="color:rgba(255,255,255,0.15); font-size:9px; font-weight:500;">v1.0 · CAC Santa Bárbara</p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useLayoutStore } from '@/stores/layout';
import { useAuthStore } from '@/stores/auth';
import AppSidebarItem from './AppSidebarItem.vue';
import { User as UserIcon, LogOut as LogOutIcon } from '@lucide/vue';

const layout = useLayoutStore();
const auth = useAuthStore();
const logoAvatar = '/images/logo-avatar.png';

function logout() {
  void auth.logout();
}
</script>

<style scoped>
aside { position: relative; }
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,.07); border-radius: 4px; }

.sidebar-glow-1 {
  position: absolute;
  top: -30px; right: -50px;
  width: 150px; height: 150px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.18), transparent 70%);
  pointer-events: none;
}
.sidebar-glow-2 {
  position: absolute;
  top: 40%; left: -40px;
  width: 120px; height: 120px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(22,70,142,0.25), transparent 70%);
  pointer-events: none;
}
.sidebar-glow-3 {
  position: absolute;
  bottom: 60px; right: -30px;
  width: 100px; height: 100px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.12), transparent 70%);
  pointer-events: none;
}
.sidebar-pattern {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.045) 1px, transparent 0);
  background-size: 22px 22px;
  pointer-events: none;
}

.sidebar-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.3);
  padding: 0 16px 6px;
  white-space: nowrap;
  overflow: hidden;
  transition: opacity 0.2s ease;
  max-height: 14px;
}

.sidebar-divider {
  margin: 0 16px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
}
</style>
