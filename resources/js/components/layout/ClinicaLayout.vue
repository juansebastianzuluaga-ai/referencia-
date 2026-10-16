<template>
  <div class="flex flex-col h-screen overflow-hidden" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">

    <!-- ── Topbar ─────────────────────────────────────────────────────────── -->
    <header
      class="shrink-0 relative z-50"
      :style="{
        background: layout.isDarkMode ? '#11151f' : 'linear-gradient(90deg, var(--rf-primary) 0%, var(--rf-primary-2) 100%)',
        borderBottom: layout.isDarkMode ? '1px solid rgba(255,255,255,0.06)' : 'none',
        boxShadow: layout.isDarkMode ? 'none' : '0 2px 14px rgba(13,45,107,0.28)',
      }"
    >
      <div class="flex items-center gap-3 h-14 px-4">
        <!-- Branding -->
        <router-link to="/clinica/dashboard" class="flex items-center gap-2.5 shrink-0 no-underline">
          <div class="w-9 h-9 rounded-full flex items-center justify-center overflow-hidden bg-white" style="box-shadow: 0 0 0 2px rgba(255,255,255,0.35);">
            <img :src="logoColor" alt="Santa Bárbara" class="w-7 h-7 object-contain" />
          </div>
          <div class="hidden sm:flex flex-col leading-tight">
            <strong class="text-[14px] font-semibold text-white" style="text-shadow: 0 1px 6px rgba(0,0,0,0.25);">Santa Bárbara</strong>
            <span class="text-[9px] font-light tracking-wide" style="color:rgba(255,255,255,0.65);">Portal de clínicas</span>
          </div>
        </router-link>

        <div class="topbar-divider hidden lg:block"></div>

        <!-- Navegación desktop -->
        <nav class="hidden lg:flex items-center gap-1 flex-1 min-w-0">
          <router-link
            v-for="item in mainItems"
            :key="item.to"
            :to="item.to"
            class="topbar-item no-underline"
            active-class="topbar-item-active"
          >
            <component :is="iconOf(item.icon)" class="w-4 h-4 shrink-0" />
            <span>{{ item.text }}</span>
          </router-link>
        </nav>

        <div class="flex-1 lg:hidden"></div>

        <!-- Acciones -->
        <div class="flex items-center gap-1 shrink-0">
          <NotificationCenter :light="false" scope="externo" />
          <button class="topbar-action-btn" :title="layout.isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'" @click="layout.toggleDarkMode">
            <component :is="layout.isDarkMode ? SunIcon : MoonIcon" class="w-4 h-4" />
          </button>

          <!-- Clínica -->
          <el-dropdown trigger="click" placement="bottom-end" @command="handleCommand">
            <button class="topbar-user">
              <div class="topbar-user-avatar">
                <span class="topbar-user-iniciales">{{ iniciales }}</span>
              </div>
              <div class="hidden md:flex flex-col leading-tight text-left">
                <span class="topbar-user-name">{{ clinicaAuth.clinica?.nombre }}</span>
                <span class="topbar-user-role">Clínica externa</span>
              </div>
              <ChevronDownIcon class="w-3.5 h-3.5 hidden md:block" style="color:rgba(255,255,255,0.5);" />
            </button>
            <template #dropdown>
              <el-dropdown-menu class="w-56">
                <div class="px-4 py-3 bg-gray-50 flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-white" style="background:linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)); font-size:12px; font-weight:800;">
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

          <!-- Hamburguesa móvil -->
          <button class="topbar-action-btn lg:hidden" title="Menú" @click="layout.toggleMobileMenu">
            <component :is="layout.isMobileMenuOpen ? XIcon : MenuIcon" class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Menú móvil desplegable -->
      <transition name="topbar-menu">
        <nav v-if="layout.isMobileMenuOpen" class="lg:hidden border-t px-3 py-2" style="border-color:rgba(255,255,255,0.15);">
          <router-link
            v-for="item in mainItems"
            :key="item.to"
            :to="item.to"
            class="topbar-mobile-item no-underline"
            active-class="topbar-mobile-item-active"
            @click="layout.closeMobileMenu"
          >
            <component :is="iconOf(item.icon)" class="shrink-0" style="width:18px;height:18px;" />
            <span>{{ item.text }}</span>
          </router-link>
        </nav>
      </transition>
    </header>

    <!-- ── Contenido ──────────────────────────────────────────────────────── -->
    <main class="flex-1 flex flex-col min-h-0 overflow-hidden p-4" :style="{ background: layout.isDarkMode ? '#0b0e17' : 'var(--rf-bg)' }">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" :key="route.path" />
        </transition>
      </router-view>
    </main>

  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import { useClinicaLayoutStore } from '@/stores/clinicaLayout';
import NotificationCenter from '@/components/layout/NotificationCenter.vue';
import * as icons from '@lucide/vue';
import {
  ChevronDown as ChevronDownIcon,
  LogOut as LogOutIcon,
  Menu as MenuIcon,
  Moon as MoonIcon,
  Sun as SunIcon,
  X as XIcon,
} from '@lucide/vue';
import { ElMessageBox } from 'element-plus';

interface NavItem {
  to: string;
  icon: string;
  text: string;
}

const mainItems: NavItem[] = [
  { to: '/clinica/dashboard', icon: 'Home', text: 'Inicio' },
  { to: '/clinica/solicitud', icon: 'FilePlus', text: 'Nueva solicitud' },
  { to: '/clinica/historial', icon: 'History', text: 'Historial' },
];

const logoColor = '/images/logo-avatar.png';
const clinicaAuth = useClinicaAuthStore();
const layout = useClinicaLayoutStore();
const route = useRoute();

function iconOf(name: string) {
  return (icons as any)[name] || icons.Circle;
}

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
header {
  position: relative;
}
header::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.55), transparent);
  pointer-events: none;
}
header::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, #38bdf8, #2563eb, #16468e);
  background-size: 220% 100%;
  animation: topbar-accent 8s linear infinite;
  opacity: 0.9;
  pointer-events: none;
}
@keyframes topbar-accent {
  from { background-position: 0% 0; }
  to { background-position: 220% 0; }
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.topbar-divider {
  width: 1px;
  height: 26px;
  background: linear-gradient(180deg, transparent, rgba(255,255,255,0.3), transparent);
  flex-shrink: 0;
}

.topbar-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 7px 12px;
  border-radius: 10px;
  border: none;
  background: transparent;
  font-size: 12.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.78);
  cursor: pointer;
  white-space: nowrap;
  transition: background .15s ease, color .15s ease, transform .15s ease;
}
.topbar-item :deep(svg) { opacity: .75; }
.topbar-item:hover {
  background: rgba(255,255,255,0.14);
  color: #fff;
  transform: translateY(-1px);
}
.topbar-item:hover :deep(svg) { opacity: 1; }
.topbar-item-active {
  background: #ffffff !important;
  color: var(--rf-primary) !important;
  box-shadow: 0 6px 16px rgba(15,23,42,0.22), inset 0 -2px 0 rgba(37,99,235,0.15);
}
.topbar-item-active :deep(svg) { opacity: 1; color: var(--rf-primary); }
.dark .topbar-item-active {
  background: linear-gradient(135deg, var(--rf-primary), var(--rf-primary-2)) !important;
  color: #fff !important;
}

.topbar-action-btn {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  background: transparent;
  color: rgba(255,255,255,0.7);
  cursor: pointer;
  transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.topbar-action-btn:hover {
  background: rgba(255,255,255,0.12);
  border-color: rgba(255,255,255,0.18);
  color: #fff;
}

.topbar-user {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 8px 4px 4px;
  margin-left: 4px;
  border: 1px solid rgba(255,255,255,0.14);
  border-radius: 11px;
  background: rgba(255,255,255,0.1);
  cursor: pointer;
  transition: background .15s ease, border-color .15s ease;
}
.topbar-user:hover { background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.25); }
.topbar-user-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(255,255,255,0.28), rgba(255,255,255,0.1));
  border: 1px solid rgba(255,255,255,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
}
.topbar-user-iniciales {
  font-size: 10px;
  font-weight: 800;
  color: #fff;
  letter-spacing: 0.03em;
}
.topbar-user-name {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  max-width: 160px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.topbar-user-role {
  font-size: 9px;
  color: rgba(255,255,255,0.55);
}

.topbar-mobile-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  margin-bottom: 2px;
}
.topbar-mobile-item:hover { background: rgba(255,255,255,0.12); color:#fff; }
.topbar-mobile-item-active {
  background: #fff;
  color: var(--rf-primary);
}

.topbar-menu-enter-active { transition: opacity .2s ease, transform .2s ease; }
.topbar-menu-leave-active { transition: opacity .15s ease, transform .15s ease; }
.topbar-menu-enter-from, .topbar-menu-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
