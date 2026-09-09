<template>
  <aside
    class="h-full overflow-y-auto overflow-x-hidden flex flex-col shrink-0 transition-all duration-300 z-50 fixed md:static top-0 bottom-0"
    :class="[
      layout.isMobileMenuOpen ? 'w-[240px] translate-x-0 shadow-2xl' : 'w-[240px] -translate-x-full md:translate-x-0',
      layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
    ]"
    :style="{ background: layout.isDarkMode ? '#11151f' : 'linear-gradient(165deg, var(--rf-primary) 0%, var(--rf-primary-2) 100%)', borderRight: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : 'none', boxShadow: layout.isDarkMode ? 'none' : '2px 0 16px rgba(13,45,107,0.25)' }"
  >
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

      <!-- Análisis -->
      <div class="mb-4 mt-4">
        <div class="sidebar-label" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Análisis</div>
        <AppSidebarItem
          to="/reportes"
          icon="BarChart3"
          text="Reportes"
          permission="clinicas.view"
        />
        <AppSidebarItem
          to="/historico"
          icon="History"
          text="Histórico"
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
      <p class="text-center" style="color:rgba(255,255,255,0.35); font-size:9px; font-weight:500;">v1.0 · CAC Santa Bárbara</p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useLayoutStore } from '@/stores/layout';
import AppSidebarItem from './AppSidebarItem.vue';

const layout = useLayoutStore();
</script>

<style scoped>
aside { position: relative; }
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.sidebar-label {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(255,255,255,0.55);
  padding: 0 16px 6px;
  white-space: nowrap;
  overflow: hidden;
  transition: opacity 0.2s ease;
  max-height: 14px;
}
.dark .sidebar-label { color: #64748b; }

.sidebar-divider {
  margin: 0 16px;
  height: 1px;
  background: rgba(255,255,255,0.18);
}
.dark .sidebar-divider { background: rgba(255,255,255,0.08); }
</style>
