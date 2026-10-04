<template>
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
      <router-link to="/dashboard" class="flex items-center gap-2.5 shrink-0 no-underline">
        <div class="w-9 h-9 rounded-full flex items-center justify-center overflow-hidden bg-white" style="box-shadow: 0 0 0 2px rgba(255,255,255,0.35);">
          <img :src="logoAvatar" alt="Santa Bárbara" class="w-7 h-7 object-contain" />
        </div>
        <div class="hidden sm:flex flex-col leading-tight">
          <strong class="text-[14px] font-semibold text-white" style="text-shadow: 0 1px 6px rgba(0,0,0,0.25);">Santa Bárbara</strong>
          <span class="text-[9px] font-light tracking-wide" style="color:rgba(255,255,255,0.65);">Clínica de Alta Complejidad</span>
        </div>
      </router-link>

      <div class="topbar-divider hidden lg:block"></div>

      <!-- Navegación desktop -->
      <nav class="hidden lg:flex items-center gap-1 flex-1 min-w-0">
        <router-link
          v-for="item in mainItems"
          :key="item.to"
          v-show="!item.permission || can(item.permission)"
          :to="item.to"
          class="topbar-item no-underline"
          active-class="topbar-item-active"
        >
          <component :is="iconOf(item.icon)" class="w-4 h-4 shrink-0" />
          <span>{{ item.text }}</span>
        </router-link>

        <!-- Administración (dropdown) -->
        <el-dropdown v-if="adminItems.some(i => !i.permission || can(i.permission))" trigger="click" placement="bottom-start">
          <button class="topbar-item" :class="{ 'topbar-item-active': isAdminRoute }">
            <component :is="iconOf('Settings')" class="w-4 h-4 shrink-0" />
            <span>Administración</span>
            <ChevronDownIcon class="w-3.5 h-3.5" style="color:rgba(255,255,255,0.55);" />
          </button>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item
                v-for="item in adminItems"
                :key="item.to"
                v-show="!item.permission || can(item.permission)"
                @click="router.push(item.to)"
              >
                <component :is="iconOf(item.icon)" class="w-4 h-4 mr-2 inline-block" />
                {{ item.text }}
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </nav>

      <div class="flex-1 lg:hidden"></div>

      <!-- Buscador global (Ctrl+K) -->
      <div ref="searchWrap" class="hidden xl:block relative w-[220px] shrink-0">
        <SearchIcon class="tb-search-icon" />
        <input
          ref="searchInput"
          v-model="query"
          class="tb-search-input"
          type="text"
          placeholder="Buscar paciente, clínica o solicitud..."
          @input="onSearchInput"
          @focus="onSearchInput"
          @keydown.esc="cerrarBusqueda"
        />
        <kbd class="tb-search-kbd">Ctrl K</kbd>

        <div v-if="buscando && (resultados.solicitudes.length || resultados.clinicas.length || query.trim().length >= 2)" class="tb-search-panel">
          <template v-if="resultados.solicitudes.length">
            <p class="tb-search-panel-label">Solicitudes</p>
            <button v-for="s in resultados.solicitudes" :key="`s-${s.id}`" class="tb-search-result" @mousedown.prevent="irSolicitud(s.id)">
              <FileSearchIcon class="w-4 h-4 shrink-0 text-blue-500" />
              <div class="flex-1 min-w-0 text-left">
                <p class="tb-search-result-title">{{ s.paciente }}</p>
                <p class="tb-search-result-sub">{{ s.documento }} · {{ s.clinica || 'Sin clínica' }}</p>
              </div>
              <span class="tb-search-result-badge">{{ s.estado }}</span>
            </button>
          </template>
          <template v-if="resultados.clinicas.length">
            <p class="tb-search-panel-label">Clínicas</p>
            <button v-for="c in resultados.clinicas" :key="`c-${c.id}`" class="tb-search-result" @mousedown.prevent="irClinica(c.id)">
              <HospitalIcon class="w-4 h-4 shrink-0 text-emerald-500" />
              <div class="flex-1 min-w-0 text-left">
                <p class="tb-search-result-title">{{ c.nombre }}</p>
                <p class="tb-search-result-sub">{{ c.ciudad || c.nit }}</p>
              </div>
            </button>
          </template>
          <p v-if="!resultados.solicitudes.length && !resultados.clinicas.length && !buscandoCarga" class="tb-search-empty">Sin resultados para "{{ query }}"</p>
        </div>
      </div>

      <!-- Acciones -->
      <div class="flex items-center gap-1 shrink-0">
        <NotificationCenter :light="false" scope="interno" />
        <button class="topbar-action-btn" :title="layout.isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'" @click="layout.toggleDarkMode">
          <component :is="layout.isDarkMode ? SunIcon : MoonIcon" class="w-4 h-4" />
        </button>

        <!-- Usuario -->
        <el-dropdown trigger="click" placement="bottom-end" @command="handleCommand">
          <button class="topbar-user">
            <div class="topbar-user-avatar">
              <span class="topbar-user-iniciales">{{ userIniciales }}</span>
            </div>
            <div class="hidden md:flex flex-col leading-tight text-left">
              <span class="topbar-user-name">{{ auth.user?.full_name || 'Usuario' }}</span>
              <span class="topbar-user-role">{{ auth.user?.job_title || 'Personal' }}</span>
            </div>
            <ChevronDownIcon class="w-3.5 h-3.5 hidden md:block" style="color:rgba(255,255,255,0.5);" />
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
          v-for="item in allItems"
          :key="item.to"
          v-show="!item.permission || can(item.permission)"
          :to="item.to"
          class="topbar-mobile-item no-underline"
          active-class="topbar-mobile-item-active"
          @click="layout.closeMobileMenu"
        >
          <component :is="iconOf(item.icon)" class="w-4.5 h-4.5 shrink-0" style="width:18px;height:18px;" />
          <span>{{ item.text }}</span>
        </router-link>
      </nav>
    </transition>
  </header>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLayoutStore } from '@/stores/layout';
import { useAuthStore } from '@/stores/auth';
import { usePermission } from '@/composables/usePermission';
import NotificationCenter from './NotificationCenter.vue';
import http from '@/plugins/axios';
import * as icons from '@lucide/vue';
import {
  ChevronDown as ChevronDownIcon,
  FileSearch as FileSearchIcon,
  Hospital as HospitalIcon,
  Key as KeyIcon,
  LogOut as LogOutIcon,
  Menu as MenuIcon,
  Moon as MoonIcon,
  Search as SearchIcon,
  Sun as SunIcon,
  User as UserIcon,
  X as XIcon,
} from '@lucide/vue';

interface NavItem {
  to: string;
  icon: string;
  text: string;
  permission?: string;
}

const mainItems: NavItem[] = [
  { to: '/dashboard', icon: 'LayoutDashboard', text: 'Panel principal' },
  { to: '/solicitudes-referencia', icon: 'ClipboardList', text: 'Solicitudes', permission: 'clinicas.view' },
  { to: '/clinicas', icon: 'Hospital', text: 'Clínicas', permission: 'clinicas.view' },
  { to: '/historico', icon: 'History', text: 'Historial', permission: 'clinicas.view' },
  { to: '/reportes', icon: 'BarChart3', text: 'Reportes', permission: 'clinicas.view' },
];

const adminItems: NavItem[] = [
  { to: '/usuarios', icon: 'Users', text: 'Usuarios', permission: 'users.view' },
  { to: '/roles', icon: 'ShieldCheck', text: 'Roles y permisos', permission: 'roles.view' },
  { to: '/configuracion', icon: 'Settings', text: 'Configuración', permission: 'settings.view' },
];

const allItems = [...mainItems, ...adminItems];

const layout = useLayoutStore();
const auth = useAuthStore();
const router = useRouter();
const route = useRoute();
const { can } = usePermission();

const logoAvatar = '/images/logo-avatar.png';

const isAdminRoute = computed(() => adminItems.some(i => route.path.startsWith(i.to)));

function iconOf(name: string) {
  return (icons as any)[name] || icons.Circle;
}

const userIniciales = computed(() =>
  (auth.user?.full_name || 'U').trim().split(/\s+/).slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join(''),
);

// ── Buscador global (Ctrl+K) — portado del antiguo header ──
const query = ref('');
const buscando = ref(false);
const buscandoCarga = ref(false);
const buscandoTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const searchWrap = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const resultados = ref<{ solicitudes: any[]; clinicas: any[] }>({ solicitudes: [], clinicas: [] });

function onSearchInput() {
  buscando.value = true;
  if (buscandoTimer.value) clearTimeout(buscandoTimer.value);
  buscandoTimer.value = setTimeout(async () => {
    const q = query.value.trim();
    if (q.length < 2) {
      resultados.value = { solicitudes: [], clinicas: [] };
      return;
    }
    try {
      buscandoCarga.value = true;
      const { data } = await http.get('/api/search', { params: { q } });
      resultados.value = data.data;
    } catch {
      resultados.value = { solicitudes: [], clinicas: [] };
    } finally {
      buscandoCarga.value = false;
    }
  }, 250);
}

function cerrarBusqueda() {
  buscando.value = false;
  query.value = '';
}

function irSolicitud(id: number) {
  cerrarBusqueda();
  router.push({ path: '/solicitudes-referencia', query: { resaltar: id } });
}

function irClinica(id: number) {
  cerrarBusqueda();
  router.push({ path: '/clinicas', query: { resaltar: id } });
}

function onGlobalKey(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    searchInput.value?.focus();
  }
}

function onClickOutside(e: MouseEvent) {
  if (searchWrap.value && !searchWrap.value.contains(e.target as Node)) {
    buscando.value = false;
  }
}

onMounted(() => {
  window.addEventListener('keydown', onGlobalKey);
  document.addEventListener('mousedown', onClickOutside);
});
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onGlobalKey);
  document.removeEventListener('mousedown', onClickOutside);
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

/* ── Buscador global en la topbar (claro sobre azul) ── */
.tb-search-icon {
  position: absolute;
  left: 11px;
  top: 50%;
  transform: translateY(-50%);
  width: 14px; height: 14px;
  color: rgba(255,255,255,0.6);
  pointer-events: none;
}
.tb-search-input {
  width: 100%;
  height: 32px;
  padding: 0 52px 0 32px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.18);
  background: rgba(255,255,255,0.10);
  color: #fff;
  font-size: 11.5px;
  font-weight: 500;
  outline: none;
  transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.tb-search-input::placeholder { color: rgba(255,255,255,0.5); }
.tb-search-input:focus {
  background: rgba(255,255,255,0.16);
  border-color: rgba(255,255,255,0.4);
  box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
}
.tb-search-kbd {
  position: absolute;
  right: 9px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 8.5px;
  font-weight: 700;
  font-family: inherit;
  color: rgba(255,255,255,0.7);
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.2);
  border-radius: 5px;
  padding: 1.5px 5px;
  pointer-events: none;
}
.tb-search-panel {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 320px;
  max-height: 320px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 16px 40px rgba(15,23,42,0.18);
  padding: 6px;
  z-index: 100;
}
.dark .tb-search-panel { background: #1e2536; border-color: rgba(255,255,255,0.1); }
.tb-search-panel-label {
  font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
  color: #94a3b8; padding: 6px 8px 3px; margin: 0;
}
.tb-search-result {
  display: flex; align-items: center; gap: 9px;
  width: 100%; padding: 7px 8px;
  border: none; background: transparent; border-radius: 8px;
  cursor: pointer; transition: background .12s ease;
}
.tb-search-result:hover { background: #f1f5f9; }
.dark .tb-search-result:hover { background: rgba(255,255,255,0.06); }
.tb-search-result-title {
  font-size: 11.5px; font-weight: 700; color: #1e293b; margin: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dark .tb-search-result-title { color: #e2e8f0; }
.tb-search-result-sub {
  font-size: 9.5px; color: #94a3b8; margin: 0;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.tb-search-result-badge {
  font-size: 8.5px; font-weight: 700; text-transform: capitalize;
  background: #eff6ff; color: #1d4ed8;
  border-radius: 999px; padding: 2px 7px; flex-shrink: 0;
}
.tb-search-empty {
  font-size: 11px; color: #94a3b8; text-align: center; padding: 14px 8px; margin: 0;
}
.topbar-user-name {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  max-width: 130px;
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
