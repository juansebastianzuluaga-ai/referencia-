<template>
  <aside 
    class="bg-gray-900 h-full overflow-y-auto overflow-x-hidden flex flex-col shrink-0 transition-all duration-300 z-50 fixed md:static top-[56px] bottom-[36px]"
    :class="[
      layout.isMobileMenuOpen ? 'w-[240px] translate-x-0 shadow-2xl' : 'w-[240px] -translate-x-full md:translate-x-0',
      layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
    ]"
  >
    <div class="py-3 border-b border-white/5 flex-1">
      
      <!-- Principal -->
      <div class="mb-4">
        <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 px-4 pb-1.5 whitespace-nowrap overflow-hidden transition-opacity" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Principal</div>
        <AppSidebarItem
          to="/dashboard"
          icon="LayoutDashboard"
          text="Panel principal"
        />
      </div>

      <!-- Administración -->
      <div class="mb-4">
        <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 px-4 pb-1.5 whitespace-nowrap overflow-hidden transition-opacity" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Administración</div>
        <AppSidebarItem 
          to="/usuarios" 
          icon="Users" 
          text="Gestión de usuarios"
          permission="users.view"
        />
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
          to="/solicitudes-clinicas" 
          icon="Building2" 
          text="Clínicas externas"
          permission="external-clinics.view"
        />
      </div>
      
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useLayoutStore } from '@/stores/layout';
import AppSidebarItem from './AppSidebarItem.vue';

const layout = useLayoutStore();
</script>

<style scoped>
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }
</style>
