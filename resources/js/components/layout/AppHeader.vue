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

    <!-- Buscador global -->
    <div ref="searchWrap" class="hidden md:block relative flex-1 max-w-[420px] mx-4">
      <SearchIcon class="search-icon" />
      <input
        ref="searchInput"
        v-model="query"
        class="search-input"
        :class="{ 'search-input-dark': layout.isDarkMode }"
        type="text"
        placeholder="Buscar paciente, clínica o número de solicitud..."
        @input="onSearchInput"
        @focus="onSearchInput"
        @keydown.esc="cerrarBusqueda"
      />
      <kbd class="search-kbd" :class="{ 'search-kbd-dark': layout.isDarkMode }">Ctrl K</kbd>

      <div v-if="buscando && (resultados.solicitudes.length || resultados.clinicas.length || query.trim().length >= 2)" class="search-panel">
        <template v-if="resultados.solicitudes.length">
          <p class="search-panel-label">Solicitudes</p>
          <button v-for="s in resultados.solicitudes" :key="`s-${s.id}`" class="search-result" @mousedown.prevent="irSolicitud(s.id)">
            <FileSearchIcon class="w-4 h-4 shrink-0 text-blue-500" />
            <div class="flex-1 min-w-0 text-left">
              <p class="search-result-title">{{ s.paciente }}</p>
              <p class="search-result-sub">{{ s.documento }} · {{ s.clinica || 'Sin clínica' }}</p>
            </div>
            <span class="search-result-badge">{{ s.estado }}</span>
          </button>
        </template>
        <template v-if="resultados.clinicas.length">
          <p class="search-panel-label">Clínicas</p>
          <button v-for="c in resultados.clinicas" :key="`c-${c.id}`" class="search-result" @mousedown.prevent="irClinica(c.id)">
            <HospitalIcon class="w-4 h-4 shrink-0 text-emerald-500" />
            <div class="flex-1 min-w-0 text-left">
              <p class="search-result-title">{{ c.nombre }}</p>
              <p class="search-result-sub">{{ c.ciudad || c.nit }}</p>
            </div>
          </button>
        </template>
        <p v-if="!resultados.solicitudes.length && !resultados.clinicas.length && !buscandoCarga" class="search-empty">Sin resultados para "{{ query }}"</p>
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
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useLayoutStore } from '@/stores/layout';
import { useAuthStore } from '@/stores/auth';
import NotificationCenter from './NotificationCenter.vue';
import http from '@/plugins/axios';
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
} from '@lucide/vue';

const layout = useLayoutStore();
const auth = useAuthStore();
const router = useRouter();

// Public images served from /public — binding dinámico para que Vite no
// intente resolverlas como imports en build.
const logoAvatar = '/images/logo-avatar.png';
const logoAvatarWhite = '/images/logo-avatar-w.png';

// ── Buscador global (Ctrl+K) ────────────────────────────────────
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
  if (buscandoTimer.value) clearTimeout(buscandoTimer.value);
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

/* ── Buscador global ── */
.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  color: #94a3b8;
  pointer-events: none;
}
.search-input {
  width: 100%;
  height: 36px;
  padding: 0 44px 0 34px;
  font-size: 12.5px;
  border-radius: 11px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #334155;
  outline: none;
  transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.search-input::placeholder { color: #94a3b8; }
.search-input:focus {
  background: #fff;
  border-color: #16468e;
  box-shadow: 0 0 0 3px rgba(22, 70, 142, 0.08);
}
.search-input-dark {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
  color: #e2e8f0;
}
.search-input-dark:focus {
  background: rgba(255,255,255,0.08);
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.search-kbd {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-family: inherit;
  font-size: 9.5px;
  font-weight: 600;
  color: #94a3b8;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 5px;
  padding: 2px 6px;
  pointer-events: none;
}
.search-kbd-dark {
  background: rgba(255,255,255,0.08);
  border-color: rgba(255,255,255,0.12);
  color: #64748b;
}

.search-panel {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #e8edf4;
  border-radius: 12px;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
  padding: 6px;
  z-index: 50;
  max-height: 380px;
  overflow-y: auto;
}
.dark .search-panel {
  background: #161b28;
  border-color: rgba(255,255,255,0.08);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
}
.search-panel-label {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #94a3b8;
  padding: 6px 8px 3px;
}
.search-result {
  display: flex;
  align-items: center;
  gap: 9px;
  width: 100%;
  padding: 7px 8px;
  border: none;
  background: transparent;
  border-radius: 9px;
  cursor: pointer;
  transition: background .12s ease;
}
.search-result:hover { background: #f1f5f9; }
.dark .search-result:hover { background: rgba(255,255,255,0.06); }
.search-result-title {
  font-size: 12px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dark .search-result-title { color: #e2e8f0; }
.search-result-sub {
  font-size: 10.5px;
  color: #94a3b8;
  margin: 1px 0 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.search-result-badge {
  font-size: 9.5px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 999px;
  background: #eff6ff;
  color: #16468e;
  flex-shrink: 0;
}
.search-empty {
  font-size: 11.5px;
  color: #94a3b8;
  text-align: center;
  padding: 14px 8px;
}
</style>
