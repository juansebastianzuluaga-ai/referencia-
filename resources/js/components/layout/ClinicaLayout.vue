<template>
  <div class="flex flex-col h-screen overflow-hidden" :style="{ background: layout.isDarkMode ? '#0f172a' : '#e8ecf1' }">

    <!-- ── Topbar ──────────────────────────────────────────────────────────── -->
    <header
      class="h-[56px] flex items-center justify-between pr-4 z-50 shadow-md shrink-0 transition-colors duration-300"
      :style="{ background: layout.isDarkMode ? '#1e293b' : 'var(--blue-800, #1e2d55)', borderBottom: '1px solid rgba(255,255,255,0.05)' }"
    >
      <div class="flex items-center h-full">
        <!-- Logo area (integrado en topbar como el interno) -->
        <div
          class="h-full flex items-center border-r border-white/10 transition-all duration-200 shrink-0 overflow-hidden"
          :style="{ background: layout.isDarkMode ? '#0f172a' : 'var(--blue-900, #192950)' }"
          :class="layout.isSidebarCollapsed ? 'md:w-[64px] md:justify-center md:px-0 w-[52px] px-2' : 'md:w-[240px] md:justify-start md:px-4 w-[52px] px-2'"
        >
          <div class="w-9 h-9 shrink-0 bg-white rounded-full flex items-center justify-center overflow-hidden">
            <img :src="logoColor" alt="Santa Bárbara" class="w-7 h-7 object-contain" />
          </div>
          <div
            class="hidden md:flex flex-col ml-2.5 overflow-hidden whitespace-nowrap transition-all duration-200"
            :class="layout.isSidebarCollapsed ? 'opacity-0 max-w-0' : 'opacity-100 max-w-[160px]'"
          >
            <strong class="block text-sm font-semibold text-white leading-tight">Santa Bárbara</strong>
            <span class="text-[10px] font-light tracking-wide" style="color:rgba(255,255,255,0.4);">Sistema de Referencia</span>
          </div>
        </div>

        <!-- Toggle sidebar mobile -->
        <button
          class="md:hidden w-9 h-9 rounded mx-3 flex items-center justify-center transition-colors shrink-0 border-none cursor-pointer"
          style="background:rgba(255,255,255,0.1); color:#fff;"
          title="Mostrar/ocultar menu"
          @click="layout.toggleMobileMenu"
        >
          <MenuIcon style="width:18px; height:18px;" />
        </button>

        <!-- Toggle sidebar desktop -->
        <button
          class="hidden md:flex w-9 h-9 rounded mx-3 items-center justify-center transition-colors shrink-0 border-none cursor-pointer"
          style="background:rgba(255,255,255,0.1); color:#fff;"
          title="Mostrar/ocultar sidebar"
          @click="layout.toggleSidebar"
        >
          <MenuIcon style="width:18px; height:18px;" />
        </button>

        <!-- Breadcrumb -->
        <nav class="hidden md:flex items-center gap-1.5 text-[13px]" style="color:rgba(255,255,255,0.5);">
          <span class="capitalize">{{ currentRouteName }}</span>
        </nav>
      </div>

      <div class="flex items-center gap-1.5">
        <!-- Notificaciones -->
        <NotificationCenter />

        <!-- Dark mode toggle -->
        <button
          class="w-9 h-9 rounded-lg flex items-center justify-center transition-all border-none cursor-pointer hover:scale-110"
          style="background:rgba(255,255,255,0.1); color:#fff;"
          :title="layout.isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          @click="layout.toggleDarkMode"
        >
          <component :is="layout.isDarkMode ? SunIcon : MoonIcon" class="w-5 h-5" />
        </button>

        <!-- Divider -->
        <div class="w-px h-5 mx-1" style="background:rgba(255,255,255,0.1);"></div>

        <!-- User dropdown -->
        <el-dropdown trigger="click" @command="handleCommand">
          <button class="flex items-center gap-2 py-1 pr-2 pl-1 rounded transition-colors border-none cursor-pointer outline-none">
            <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden shrink-0" style="background:linear-gradient(135deg, rgba(126,179,255,0.25), rgba(22,70,142,0.4)); color:#7eb3ff; font-size:12px; font-weight:800; border:1px solid rgba(126,179,255,0.15);">
              {{ iniciales }}
            </div>
            <div class="hidden md:block text-left">
              <div class="text-[12.5px] font-medium text-white leading-tight whitespace-nowrap truncate max-w-[140px]">{{ clinicaAuth.clinica?.nombre }}</div>
              <div class="text-[10.5px] leading-none mt-0.5" style="color:rgba(255,255,255,0.4);">Clínica externa</div>
            </div>
            <ChevronDownIcon class="hidden md:block w-3.5 h-3.5 ml-0.5" style="color:rgba(255,255,255,0.4);" />
          </button>

          <template #dropdown>
            <el-dropdown-menu class="w-56">
              <div class="px-4 py-3 flex items-center gap-2.5" style="background:#f8fafc;">
                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background:linear-gradient(135deg, #dbeafe, #bfdbfe); color:#2563c4; font-size:13px; font-weight:800;">
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

    <!-- ── Body: sidebar + content ── -->
    <div class="flex flex-1 overflow-hidden">

      <!-- Overlay móvil -->
      <div
        v-if="layout.isMobileMenuOpen"
        class="fixed inset-0 bg-black/40 z-40 md:hidden"
        @click="layout.closeMobileMenu"
      ></div>

      <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
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

        <div class="sidebar-divider mt-2 mb-2" :class="{ 'md:!my-1': layout.isSidebarCollapsed }"></div>

        <!-- Info clínica -->
        <div class="pb-3 shrink-0 relative z-10" :class="layout.isSidebarCollapsed ? 'md:px-2 md:pb-2' : 'px-4'">
          <div class="clinica-badge" :class="{ 'md:!p-0 md:!justify-center md:!bg-transparent md:!border-0': layout.isSidebarCollapsed }">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold shrink-0" style="background:linear-gradient(135deg, rgba(126,179,255,0.25), rgba(22,70,142,0.4)); color:#7eb3ff; font-size:14px; border:1px solid rgba(126,179,255,0.15);">
              {{ iniciales }}
            </div>
            <div class="min-w-0 flex-1" :class="{ 'md:hidden': layout.isSidebarCollapsed }">
              <p class="text-white text-[12px] font-semibold leading-tight truncate">{{ clinicaAuth.clinica?.nombre }}</p>
              <div class="flex items-center gap-1 mt-0.5">
                <span class="online-dot"></span>
                <p style="color:#6b82a8; font-size:9.5px; line-height:1.2;">NIT: {{ clinicaAuth.clinica?.nit }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="sidebar-divider" :class="{ 'md:!hidden': layout.isSidebarCollapsed }"></div>

        <!-- Items -->
        <div class="py-1 flex-1 relative z-10 overflow-y-auto">
          <!-- Principal -->
          <div class="mb-4 mt-2">
            <div class="sidebar-label" :class="{ 'md:!h-0 md:!py-0 md:!opacity-0 md:!overflow-hidden': layout.isSidebarCollapsed }">Principal</div>
            <el-tooltip content="Inicio" placement="right" :disabled="!layout.isSidebarCollapsed">
              <router-link to="/clinica/dashboard" class="menu-item no-underline" :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }" active-class="menu-item-active" @click="layout.closeMobileMenu()">
                <span class="menu-accent-bar"></span>
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
                <span class="menu-accent-bar"></span>
                <div class="menu-icon-wrap">
                  <FilePlusIcon class="shrink-0" style="width:20px; height:20px;" />
                </div>
                <span class="menu-label" :class="layout.isSidebarCollapsed ? 'md:!hidden' : 'opacity-100 max-w-[240px]'">Nueva solicitud</span>
              </router-link>
            </el-tooltip>
            <el-tooltip content="Historial" placement="right" :disabled="!layout.isSidebarCollapsed">
              <router-link to="/clinica/historial" class="menu-item no-underline" :class="{ 'menu-item-collapsed': layout.isSidebarCollapsed }" active-class="menu-item-active" @click="layout.closeMobileMenu()">
                <span class="menu-accent-bar"></span>
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
          <div class="sidebar-divider mb-3" :class="{ 'md:!hidden': layout.isSidebarCollapsed }"></div>
          <div v-if="!layout.isSidebarCollapsed" class="flex items-center gap-2 mb-2 px-1">
            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background:rgba(255,255,255,0.1);">
              <component :is="UserIcon" class="w-3.5 h-3.5" style="color:rgba(255,255,255,0.6);" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[11px] font-medium text-white truncate leading-tight">{{ clinicaAuth.clinica?.nombre }}</p>
              <p class="text-[9px] truncate" style="color:rgba(255,255,255,0.35);">Clínica externa</p>
            </div>
            <button @click="logout" class="w-6 h-6 rounded flex items-center justify-center shrink-0 cursor-pointer border-none" style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.5);" title="Cerrar sesión">
              <LogOutIcon class="w-3 h-3" />
            </button>
          </div>
          <div v-else class="flex justify-center mb-2">
            <button @click="logout" class="w-7 h-7 rounded flex items-center justify-center cursor-pointer border-none" style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.5);" title="Cerrar sesión">
              <LogOutIcon class="w-3.5 h-3.5" />
            </button>
          </div>
          <p class="text-center" style="color:rgba(255,255,255,0.15); font-size:9px; font-weight:500;">v1.0 · CAC Santa Bárbara</p>
        </div>
      </aside>

      <!-- ── Contenido ────────────────────────────────────────────────────── -->
      <main class="flex-1 flex flex-col min-h-0 overflow-hidden p-4" :style="{ background: layout.isDarkMode ? '#0f172a' : '#e8ecf1' }">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </router-view>
      </main>

    </div><!-- /body -->

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { useLayoutStore } from '@/stores/layout';
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
  User as UserIcon,
} from '@lucide/vue';
import { ElMessageBox } from 'element-plus';

const logoW = '/images/logo-w.png';
const logoColor = '/images/logo-avatar.png';
const clinicaAuth = useClinicaAuthStore();
const layout = useLayoutStore();
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
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,.07); border-radius: 4px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* ── Glow decorativo ── */
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

/* ── Badge clínica ── */
.clinica-badge {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .6rem .7rem;
  border-radius: 14px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.06);
  width: 100%;
}
.online-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px rgba(34,197,94,0.5);
  flex-shrink: 0;
}

/* ── Menu items (mismo estilo que AppSidebarItem) ── */
.menu-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 12px 14px;
  border-radius: 14px;
  margin: 0 16px 8px;
  cursor: pointer;
  transition: all .2s ease;
  position: relative;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.04);
  white-space: nowrap;
  overflow: hidden;
}
.menu-item:hover {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.08);
}

.menu-accent-bar {
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 3px; height: 60%;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #7eb3ff, #16468e);
  box-shadow: 0 0 8px rgba(126,179,255,0.4);
  opacity: 0;
  transition: opacity .2s ease;
}

.menu-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px; height: 32px;
  border-radius: 10px;
  background: rgba(255,255,255,0.05);
  color: #6b82a8;
  transition: all .2s ease;
  flex-shrink: 0;
}
.menu-item:hover .menu-icon-wrap {
  color: #8294b8;
  background: rgba(255,255,255,0.08);
}

.menu-label {
  font-size: 13.5px;
  font-weight: 500;
  color: #8294b8;
  transition: all .2s ease;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.menu-item:hover .menu-label {
  color: #a0b3d0;
}

/* Active state */
.menu-item-active {
  background: linear-gradient(135deg, rgba(22,70,142,0.5), rgba(13,45,107,0.6)) !important;
  border-color: rgba(126,179,255,0.15) !important;
  box-shadow: 0 4px 14px rgba(22,70,142,0.3), inset 0 1px 0 rgba(255,255,255,0.06) !important;
}
.menu-item-active:hover {
  background: linear-gradient(135deg, rgba(22,70,142,0.55), rgba(13,45,107,0.65)) !important;
}
.menu-item-active .menu-accent-bar {
  opacity: 1;
}
.menu-item-active .menu-icon-wrap {
  background: linear-gradient(135deg, rgba(126,179,255,0.2), rgba(22,70,142,0.3));
  color: #7eb3ff;
  box-shadow: 0 2px 8px rgba(126,179,255,0.15);
}
.menu-item-active .menu-label {
  color: #ffffff;
  font-weight: 700;
}

/* Collapsed state */
.menu-item-collapsed {
  margin: 0 8px 8px;
  padding: 0;
  justify-content: center;
  gap: 0;
  height: 44px;
}
.menu-item-collapsed .menu-icon-wrap {
  width: 36px;
  height: 36px;
}
.menu-item-collapsed .menu-accent-bar {
  left: -8px;
}
</style>
