<template>
  <div class="flex h-screen overflow-hidden" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">

    <!-- Overlay móvil -->
    <div
      v-if="layout.isMobileMenuOpen"
      class="fixed inset-0 bg-black/40 z-40 md:hidden"
      @click="layout.closeMobileMenu"
    ></div>

    <!-- ── Sidebar ────────────────────────────────────────────────────────── -->
    <aside
      class="h-full overflow-y-auto overflow-x-hidden flex flex-col shrink-0 transition-all duration-300 z-50 fixed md:static top-0 bottom-0"
      :class="[
        layout.isMobileMenuOpen ? 'w-[240px] translate-x-0 shadow-2xl' : 'w-[240px] -translate-x-full md:translate-x-0',
        layout.isSidebarCollapsed ? 'md:w-[64px]' : 'md:w-[240px]',
      ]"
      :style="{ background: layout.isDarkMode ? '#11151f' : 'linear-gradient(165deg, var(--rf-primary) 0%, var(--rf-primary-2) 100%)', borderRight: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : 'none', boxShadow: layout.isDarkMode ? 'none' : '2px 0 16px rgba(13,45,107,0.25)' }"
    >
      <!-- Branding -->
      <div class="shrink-0 flex items-center gap-2.5 px-4 pt-4 pb-3 relative z-10" :class="{ 'md:justify-center md:px-2': layout.isSidebarCollapsed }">
        <div class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center overflow-hidden bg-white" style="box-shadow: 0 0 0 2px rgba(255,255,255,0.35);">
          <img :src="logoColor" alt="Santa Bárbara" class="w-8 h-8 object-contain" />
        </div>
        <div class="flex flex-col overflow-hidden whitespace-nowrap transition-all duration-200" :class="layout.isSidebarCollapsed ? 'md:opacity-0 md:max-w-0' : 'md:opacity-100 md:max-w-[180px]'">
          <strong class="block text-[15px] font-semibold leading-tight" style="color:#fff;">Santa Bárbara</strong>
          <span class="text-[10px] font-light tracking-wide" style="color:rgba(255,255,255,0.65);">Sistema de Referencia</span>
        </div>
      </div>

      <div class="sidebar-divider mb-3"></div>

      <!-- Items -->
      <div class="py-1 flex-1 relative z-10 overflow-y-auto sidebar-scroll">
        <!-- Principal -->
        <div class="mb-4">
          <div class="sidebar-label" :class="{ 'md:!h-0 md:!py-0 md:!opacity-0 md:!overflow-hidden': layout.isSidebarCollapsed }">Principal</div>
          <el-tooltip content="Inicio" placement="right" :disabled="!layout.isSidebarCollapsed">
            <router-link to="/clinica/dashboard" class="menu-item no-underline" :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }" active-class="menu-item-active" @click="layout.closeMobileMenu()">
              <div class="menu-icon-wrap">
                <HomeIcon class="shrink-0" style="width:20px; height:20px;" />
              </div>
              <span class="menu-label" :class="layout.isSidebarCollapsed ? 'md:!hidden' : 'opacity-100 max-w-[240px]'">Inicio</span>
            </router-link>
          </el-tooltip>
        </div>

        <div class="sidebar-divider"></div>

        <!-- Solicitudes -->
        <div class="mb-4 mt-4">
          <div class="sidebar-label" :class="{ 'md:!h-0 md:!py-0 md:!opacity-0 md:!overflow-hidden': layout.isSidebarCollapsed }">Solicitudes</div>
          <el-tooltip content="Nueva solicitud" placement="right" :disabled="!layout.isSidebarCollapsed">
            <router-link to="/clinica/solicitud" class="menu-item no-underline" :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }" active-class="menu-item-active" @click="layout.closeMobileMenu()">
              <div class="menu-icon-wrap">
                <FilePlusIcon class="shrink-0" style="width:20px; height:20px;" />
              </div>
              <span class="menu-label" :class="layout.isSidebarCollapsed ? 'md:!hidden' : 'opacity-100 max-w-[240px]'">Nueva solicitud</span>
            </router-link>
          </el-tooltip>
          <el-tooltip content="Historial" placement="right" :disabled="!layout.isSidebarCollapsed">
            <router-link to="/clinica/historial" class="menu-item no-underline" :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }" active-class="menu-item-active" @click="layout.closeMobileMenu()">
              <div class="menu-icon-wrap">
                <HistoryIcon class="shrink-0" style="width:20px; height:20px;" />
              </div>
              <span class="menu-label" :class="layout.isSidebarCollapsed ? 'md:!hidden' : 'opacity-100 max-w-[240px]'">Historial</span>
            </router-link>
          </el-tooltip>
        </div>

      </div>

      <!-- Footer -->
      <div class="shrink-0 px-4 pb-3 relative z-10" :class="{ 'md:!px-2': layout.isSidebarCollapsed }">
        <div class="sidebar-divider mb-3"></div>
        <div v-if="!layout.isSidebarCollapsed" class="clinic-card mb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 relative text-white" :style="{ background: layout.isDarkMode ? 'linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2))' : 'rgba(255,255,255,0.22)', fontSize: '12px', fontWeight: 800 }">
              {{ iniciales }}
              <span class="online-dot" style="position:absolute; right:-1px; bottom:-1px; border:1.5px solid var(--rf-primary);"></span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[11.5px] font-semibold truncate leading-tight" style="color:#fff;">{{ clinicaAuth.clinica?.nombre }}</p>
              <p class="text-[9.5px] truncate" style="color:rgba(255,255,255,0.6);">{{ clinicaAuth.clinica?.ciudad || 'Clínica externa' }}</p>
            </div>
          </div>
          <div class="clinic-card-divider"></div>
          <div class="flex items-center justify-between">
            <span class="clinic-card-status" :class="clinicaAuth.clinica?.is_active ? 'clinic-card-status--active' : 'clinic-card-status--inactive'">
              <span class="clinic-card-status-dot"></span>
              {{ clinicaAuth.clinica?.is_active ? 'Cuenta activa' : 'Cuenta inactiva' }}
            </span>
            <span class="clinic-card-nit">NIT {{ clinicaAuth.clinica?.nit }}</span>
          </div>
        </div>
        <p class="text-center" style="color:rgba(255,255,255,0.35); font-size:9px; font-weight:500;">v1.0 · CAC Santa Bárbara</p>
      </div>
    </aside>

    <!-- ── Columna derecha: header + contenido ────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 min-h-0">

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
            <MenuIcon style="width:18px; height:18px;" />
          </button>

          <!-- Colapsar sidebar (desktop) -->
          <button
            class="hidden md:flex header-icon-btn"
            :style="{ color: layout.isDarkMode ? '#cbd5e1' : '#475569' }"
            title="Mostrar/ocultar sidebar"
            @click="layout.toggleSidebar"
          >
            <MenuIcon style="width:18px; height:18px;" />
          </button>

          <!-- Logo móvil -->
          <div class="md:hidden flex items-center gap-2 shrink-0">
            <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden bg-white border border-slate-200">
              <img :src="logoColor" alt="Santa Bárbara" class="w-6 h-6 object-contain" />
            </div>
          </div>

          <!-- Breadcrumb -->
          <nav class="hidden md:flex items-center gap-1.5 text-[13px] font-semibold" :style="{ color: layout.isDarkMode ? '#94a3b8' : '#475569' }">
            <span class="capitalize">{{ currentRouteName }}</span>
          </nav>
        </div>

        <div class="flex items-center gap-1">
          <div class="header-action">
            <NotificationCenter :light="!layout.isDarkMode" scope="externo" />
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
              <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden shrink-0 text-white" style="background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)); font-size:12px; font-weight:800;">
                {{ iniciales }}
              </div>
              <div class="hidden md:block text-left">
                <div class="text-[12.5px] font-semibold leading-tight whitespace-nowrap truncate max-w-[160px]" :style="{ color: layout.isDarkMode ? '#e2e8f0' : '#1e293b' }">{{ clinicaAuth.clinica?.nombre }}</div>
                <div class="text-[10.5px] leading-none mt-0.5" :style="{ color: layout.isDarkMode ? '#64748b' : '#94a3b8' }">Clínica externa</div>
              </div>
              <ChevronDownIcon class="hidden md:block w-3.5 h-3.5 ml-0.5 header-user-caret" :style="{ color: layout.isDarkMode ? '#64748b' : '#94a3b8' }" />
            </button>

            <template #dropdown>
              <el-dropdown-menu class="w-56">
                <div class="px-4 py-3 flex items-center gap-2.5" style="background:#f8fafc;">
                  <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-white" style="background:linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)); font-size:13px; font-weight:800;">
                    {{ iniciales }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-semibold text-gray-900 truncate">{{ clinicaAuth.clinica?.nombre }}</div>
                    <div class="text-[11.5px] text-gray-500 truncate">NIT: {{ clinicaAuth.clinica?.nit }}</div>
                  </div>
                </div>
                <el-dropdown-item divided command="logout" :icon="LogOutIcon" class="!text-red-600 hover:!bg-red-50 hover:!text-red-700">Cerrar sesión</el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>

      <main class="flex-1 flex flex-col min-h-0 overflow-hidden p-4" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </router-view>
      </main>

    </div>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { useClinicaLayoutStore } from '@/stores/clinicaLayout';
import NotificationCenter from '@/components/layout/NotificationCenter.vue';
import {
  Home as HomeIcon,
  LogOut as LogOutIcon,
  Menu as MenuIcon,
  History as HistoryIcon,
  FilePlus as FilePlusIcon,
  ChevronDown as ChevronDownIcon,
  Sun as SunIcon,
  Moon as MoonIcon,
} from '@lucide/vue';
import { ElMessageBox } from 'element-plus';

const logoColor = '/images/logo-avatar.png';
const clinicaAuth = useClinicaAuthStore();
const layout = useClinicaLayoutStore();
const route = useRoute();

const currentRouteName = computed(() => {
  const map: Record<string, string> = {
    '/clinica/dashboard': 'Inicio',
    '/clinica/solicitud': 'Nueva solicitud',
    '/clinica/historial': 'Historial',
  };
  return map[route.path] ?? 'Inicio';
});

const iniciales = computed(() => {
  const nombre = clinicaAuth.clinica?.nombre ?? '';
  return nombre.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase();
});

async function logout() {
  try {
    await ElMessageBox.confirm('¿Cerrar sesión?', 'Confirmar', {
      type: 'warning',
      confirmButtonText: 'Sí, cerrar',
      cancelButtonText: 'Cancelar',
    });
    await clinicaAuth.logout();
  } catch { /* cancelado */ }
}

function handleCommand(cmd: string) {
  if (cmd === 'logout') logout();
}
</script>

<style scoped>
aside { position: relative; }
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

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

.online-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #22c55e;
  flex-shrink: 0;
}

/* ── Header buttons (display lo controla Tailwind: flex/hidden md:flex) ── */
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
.header-icon-btn:hover { background: #f1f5f9; }
.header-icon-btn:active { transform: scale(0.92); }
.dark .header-icon-btn:hover { background: rgba(255,255,255,0.06); }

.header-action {
  border-radius: 10px;
  transition: background .15s ease;
}
.header-action:hover { background: #f1f5f9; }
.dark .header-action:hover { background: rgba(255,255,255,0.06); }

.header-user {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 8px 4px 4px;
  border-radius: 12px;
  transition: background .15s ease;
}
.header-user:hover { background: #f1f5f9; }
.dark .header-user:hover { background: rgba(255,255,255,0.06); }
.header-user-caret { transition: transform .2s ease; }
.header-user:hover .header-user-caret { transform: translateY(1px); }

/* ── Footer clinic card ── */
.clinic-card {
  background: rgba(255,255,255,0.09);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 12px;
  padding: 10px 10px 8px;
}
.dark .clinic-card {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.06);
}
.clinic-card-divider {
  height: 1px;
  background: rgba(255,255,255,0.14);
  margin: 8px 0 6px;
}
.dark .clinic-card-divider { background: rgba(255,255,255,0.06); }
.clinic-card-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 9px;
  font-weight: 600;
  color: rgba(255,255,255,0.75);
}
.clinic-card-status-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  flex-shrink: 0;
}
.clinic-card-status--active .clinic-card-status-dot { background: #22c55e; }
.clinic-card-status--inactive .clinic-card-status-dot { background: #f59e0b; }
.clinic-card-nit {
  font-size: 9px;
  font-weight: 500;
  color: rgba(255,255,255,0.4);
}

/* ── Menu items ── */
.menu-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 10px 14px;
  border-radius: 12px;
  margin: 0 12px 4px;
  cursor: pointer;
  transition: all .2s ease;
  position: relative;
  white-space: nowrap;
  overflow: hidden;
}
.menu-item:hover {
  background: rgba(255,255,255,0.14);
}
.dark .menu-item:hover {
  background: rgba(255,255,255,0.06);
}

.menu-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px; height: 30px;
  border-radius: 9px;
  color: rgba(255,255,255,0.75);
  transition: all .2s ease;
  flex-shrink: 0;
}
.menu-item:hover .menu-icon-wrap {
  color: #ffffff;
}
.dark .menu-icon-wrap { color: #64748b; }
.dark .menu-item:hover .menu-icon-wrap { color: #a5b4fc; }

.menu-label {
  font-size: 13.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  transition: all .2s ease;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.menu-item:hover .menu-label {
  color: #ffffff;
}
.dark .menu-label { color: #94a3b8; }
.dark .menu-item:hover .menu-label { color: #e2e8f0; }

/* Active state: sobre fondo azul se invierte a pastilla blanca; en modo
   oscuro (fondo casi negro) sigue siendo la pastilla con degradado. */
.menu-item-active {
  background: #ffffff !important;
  box-shadow: 0 4px 14px rgba(15,23,42,0.18) !important;
}
.menu-item-active:hover {
  background: #ffffff !important;
}
.menu-item-active .menu-icon-wrap {
  color: var(--rf-primary) !important;
}
.menu-item-active .menu-label {
  color: var(--rf-primary) !important;
  font-weight: 700;
}
.dark .menu-item-active {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)) !important;
  box-shadow: 0 6px 16px rgba(22,70,142,0.35) !important;
}
.dark .menu-item-active:hover {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)) !important;
}
.dark .menu-item-active .menu-icon-wrap {
  color: #ffffff;
}
.dark .menu-item-active .menu-label {
  color: #ffffff;
  font-weight: 700;
}

/* Collapsed state */
.menu-item-collapsed {
  margin: 0 8px 4px;
  padding: 0;
  justify-content: center;
  gap: 0;
  height: 42px;
}
.menu-item-collapsed .menu-icon-wrap {
  width: 34px;
  height: 34px;
}

</style>
