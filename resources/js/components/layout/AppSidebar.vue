<template>
  <aside
    class="h-full overflow-y-auto overflow-x-hidden flex flex-col shrink-0 transition-all duration-300 z-50 fixed md:static top-[56px] bottom-0"
    style="background:#1e2d55; box-shadow: 6px 0 20px rgba(0,0,0,0.2);"
    :class="[
      layout.isMobileMenuOpen ? 'w-[240px] translate-x-0 shadow-2xl' : 'w-[240px] -translate-x-full md:translate-x-0',
      layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
    ]"
  >
    <div class="py-3 flex-1">
      
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
      </div>

      <!-- Clínicas externas -->
      <div class="mb-4">
        <div class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 px-4 pb-1.5 whitespace-nowrap overflow-hidden transition-opacity" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Clínicas externas</div>
        <AppSidebarItem
          to="/clinicas"
          icon="Hospital"
          text="Clínicas registradas"
          permission="clinicas.view"
        />
        <AppSidebarItem
          to="/solicitudes-referencia"
          icon="ClipboardList"
          text="Solicitudes de referencia"
          permission="clinicas.view"
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
