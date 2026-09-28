<template>
  <aside
    class="h-full overflow-y-auto overflow-x-hidden flex flex-col shrink-0 transition-all duration-300 z-50 fixed md:static top-0 bottom-0"
    :class="[
      layout.isMobileMenuOpen ? 'w-[240px] translate-x-0 shadow-2xl' : 'w-[240px] -translate-x-full md:translate-x-0',
      layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
    ]"
    :style="{ background: layout.isDarkMode ? '#11151f' : 'linear-gradient(165deg, var(--rf-primary) 0%, var(--rf-primary-2) 100%)', borderRight: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : 'none', boxShadow: layout.isDarkMode ? 'none' : '2px 0 16px rgba(13,45,107,0.25)' }"
  >
    <!-- ── Branding ── -->
    <div class="shrink-0 flex items-center gap-2.5 px-4 pt-4 pb-3 relative z-10" :class="{ 'md:justify-center md:px-2': layout.isSidebarCollapsed }">
      <div class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center overflow-hidden bg-white" style="box-shadow: 0 0 0 2px rgba(255,255,255,0.35);">
        <img :src="logoAvatar" alt="Santa Bárbara" class="w-8 h-8 object-contain" />
      </div>
      <div class="flex flex-col overflow-hidden whitespace-nowrap transition-all duration-200" :class="layout.isSidebarCollapsed ? 'md:opacity-0 md:max-w-0' : 'md:opacity-100 md:max-w-[180px]'">
        <strong class="block text-[15px] font-semibold leading-tight" style="color:#fff;">Santa Bárbara</strong>
        <span class="text-[10px] font-light tracking-wide" style="color:rgba(255,255,255,0.65);">Clínica de Alta Complejidad</span>
        <span class="text-[9px] italic mt-0.5" style="color:rgba(255,255,255,0.45);">Salud que nos une</span>
      </div>
    </div>

    <div class="sidebar-divider mb-3"></div>

    <div class="py-1 flex-1 relative z-10 overflow-y-auto sidebar-scroll">

      <!-- Gestión -->
      <div class="mb-4">
        <div class="sidebar-label" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Gestión</div>
        <AppSidebarItem
          to="/dashboard"
          icon="LayoutDashboard"
          text="Panel principal"
        />
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
        <AppSidebarItem
          to="/historico"
          icon="History"
          text="Historial"
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
      </div>

      <div class="sidebar-divider"></div>

      <!-- Administración -->
      <div class="mb-4 mt-4">
        <div class="sidebar-label" :class="{ 'opacity-0': layout.isSidebarCollapsed }">Administración</div>
        <AppSidebarItem
          to="/usuarios"
          icon="Users"
          text="Usuarios"
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

    </div>

    <!-- Promo + Footer -->
    <div class="shrink-0 px-3 pb-3 relative z-10" :class="{ 'md:px-2': layout.isSidebarCollapsed }">
      <div v-if="!layout.isSidebarCollapsed" class="promo-card">
        <div class="promo-card-glow"></div>
        <img :src="logoAvatarWhite" alt="" class="promo-card-logo" />
        <p class="promo-card-title">Juntos por una atención oportuna y segura</p>
        <p class="promo-card-sub">Comprometidos con la vida</p>
      </div>
      <div class="sidebar-divider mt-3 mb-3"></div>
      <p v-if="!layout.isSidebarCollapsed" class="text-center leading-tight" style="color:rgba(255,255,255,0.35); font-size:9px; font-weight:500;">
        v1.0 · Referencia de Paciente<br />CAC Santa Bárbara
      </p>
      <p v-else class="text-center hidden md:block" style="color:rgba(255,255,255,0.35); font-size:9px; font-weight:500;">v1.0</p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { useLayoutStore } from '@/stores/layout';
import AppSidebarItem from './AppSidebarItem.vue';

const layout = useLayoutStore();

// Public images served from /public — binding dinámico para que Vite no
// intente resolverlas como imports en build.
const logoAvatar = '/images/logo-avatar.png';
const logoAvatarWhite = '/images/logo-avatar-w.png';
</script>

<style scoped>
aside { position: relative; }
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

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

/* ── Tarjeta promo ── */
.promo-card {
  position: relative;
  overflow: hidden;
  border-radius: 14px;
  padding: 14px 12px;
  background: linear-gradient(160deg, rgba(255,255,255,0.14), rgba(255,255,255,0.05));
  border: 1px solid rgba(255,255,255,0.16);
  text-align: center;
}
.promo-card-glow {
  position: absolute;
  top: -40px; right: -40px;
  width: 120px; height: 120px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(126,179,255,0.35), transparent 70%);
  pointer-events: none;
}
.promo-card-logo {
  width: 44px;
  height: 44px;
  object-fit: contain;
  margin: 0 auto 8px;
  display: block;
  filter: drop-shadow(0 4px 10px rgba(0,0,0,0.25));
}
.promo-card-title {
  font-size: 11.5px;
  font-weight: 700;
  color: #fff;
  line-height: 1.35;
  margin: 0;
}
.promo-card-sub {
  font-size: 9.5px;
  color: rgba(255,255,255,0.55);
  margin: 4px 0 0;
}
</style>
