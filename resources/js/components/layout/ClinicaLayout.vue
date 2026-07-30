<template>
  <div class="flex h-screen overflow-hidden bg-[#f0f2f8]">

    <!-- Overlay móvil -->
    <div
      v-if="mobileOpen"
      class="fixed inset-0 bg-black/50 z-40 md:hidden"
      @click="mobileOpen = false"
    ></div>

    <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
    <aside
      class="flex flex-col shrink-0 h-full z-50 transition-all duration-300 fixed md:static top-0 bottom-0 w-[240px]"
      :class="mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
      style="background:linear-gradient(180deg, #1e2d55 0%, #192950 100%); box-shadow: 6px 0 24px rgba(0,0,0,0.25);"
    >
      <!-- Glow decorativo -->
      <div class="sidebar-glow-1"></div>
      <div class="sidebar-glow-2"></div>
      <div class="sidebar-glow-3"></div>
      <div class="sidebar-pattern"></div>

      <!-- Logo -->
      <div class="px-5 pt-7 pb-4 shrink-0 flex flex-col items-center gap-2 relative z-10">
        <img :src="logoW" alt="Santa Bárbara" style="height:38px; width:auto; object-fit:contain;" />
        <p style="color:rgba(255,255,255,0.35); font-size:9px; font-weight:600; letter-spacing:0.15em; text-transform:uppercase; margin:0;">Sistema de Referencia</p>
        <button
          class="md:hidden flex items-center justify-center rounded-lg"
          style="width:28px; height:28px; background:rgba(255,255,255,0.08); color:#fff;"
          @click="mobileOpen = false"
        >
          <MenuIcon style="width:14px; height:14px;" />
        </button>
      </div>

      <!-- Info clínica -->
      <div class="px-4 pb-4 shrink-0 flex justify-center relative z-10">
        <div class="clinica-badge">
          <div
            class="flex items-center justify-center rounded-xl font-bold shrink-0"
            style="width:36px; height:36px; background:linear-gradient(135deg, rgba(126,179,255,0.25), rgba(22,70,142,0.4)); color:#7eb3ff; font-size:14px; border:1px solid rgba(126,179,255,0.15);"
          >
            {{ iniciales }}
          </div>
          <div class="min-w-0">
            <p style="color:#ffffff; font-size:12px; font-weight:600; line-height:1.2;" class="truncate">
              {{ clinicaAuth.clinica?.nombre }}
            </p>
            <div class="flex items-center gap-1 mt-0.5">
              <span class="online-dot"></span>
              <p style="color:#6b82a8; font-size:9.5px; line-height:1.2;">NIT: {{ clinicaAuth.clinica?.nit }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Divisor neumórfico -->
      <div class="sidebar-divider relative z-10"></div>

      <!-- Label menú -->
      <div class="px-5 pb-3 shrink-0 flex items-center justify-center gap-2 relative z-10">
        <span class="divider-line"></span>
        <p style="color:rgba(255,255,255,0.3); font-size:9px; font-weight:600; letter-spacing:0.12em; text-transform:uppercase; margin:0;">Menú</p>
        <span class="divider-line"></span>
      </div>

      <!-- Ítems -->
      <nav class="flex-1 overflow-y-auto relative z-10" style="padding: 0 16px;">

        <!-- Inicio -->
        <router-link to="/clinica/dashboard" class="no-underline" @click="mobileOpen = false">
          <div
            class="menu-item"
            :class="{ 'menu-item-active': isDashboard }"
          >
            <span v-if="isDashboard" class="menu-accent-bar"></span>
            <div class="menu-icon-wrap" :class="{ 'menu-icon-active': isDashboard }">
              <HomeIcon class="shrink-0" style="width:20px; height:20px;" />
            </div>
            <span class="menu-label" :class="{ 'menu-label-active': isDashboard }">Inicio</span>
            <span v-if="isDashboard" class="menu-dot"></span>
          </div>
        </router-link>

        <!-- Nueva solicitud -->
        <router-link to="/clinica/solicitud" class="no-underline" @click="mobileOpen = false">
          <div
            class="menu-item"
            :class="{ 'menu-item-active': isSolicitud }"
          >
            <span v-if="isSolicitud" class="menu-accent-bar"></span>
            <div class="menu-icon-wrap" :class="{ 'menu-icon-active': isSolicitud }">
              <FilePlusIcon class="shrink-0" style="width:20px; height:20px;" />
            </div>
            <span class="menu-label" :class="{ 'menu-label-active': isSolicitud }">Nueva solicitud</span>
            <span v-if="isSolicitud" class="menu-dot"></span>
          </div>
        </router-link>

        <!-- Historial -->
        <router-link to="/clinica/historial" class="no-underline" @click="mobileOpen = false">
          <div
            class="menu-item"
            :class="{ 'menu-item-active': isHistorial }"
          >
            <span v-if="isHistorial" class="menu-accent-bar"></span>
            <div class="menu-icon-wrap" :class="{ 'menu-icon-active': isHistorial }">
              <HistoryIcon class="shrink-0" style="width:20px; height:20px;" />
            </div>
            <span class="menu-label" :class="{ 'menu-label-active': isHistorial }">Historial</span>
            <span v-if="isHistorial" class="menu-dot"></span>
          </div>
        </router-link>

      </nav>

      <!-- Cerrar sesión (abajo del todo) -->
      <div class="shrink-0 px-4 pb-3 relative z-10">
        <div class="sidebar-divider mb-3"></div>
        <div class="logout-btn" @click="logout">
          <LogOutIcon class="shrink-0" style="width:18px; height:18px;" />
          <span>Cerrar sesión</span>
        </div>
        <p class="text-center mt-2" style="color:rgba(255,255,255,0.15); font-size:9px; font-weight:500;">v1.0 · CAC Santa Bárbara</p>
      </div>

    </aside>

    <!-- ── Contenido ────────────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">

      <!-- Botón menú móvil (fuera del sidebar) -->
      <button
        v-if="!mobileOpen"
        class="md:hidden fixed top-3 left-3 z-30 flex items-center justify-center rounded-lg"
        style="width:36px; height:36px; background:#1e2d55; color:#fff; box-shadow: 0 2px 8px rgba(0,0,0,0.2);"
        @click="mobileOpen = true"
      >
        <MenuIcon style="width:18px; height:18px;" />
      </button>

      <main class="flex-1 overflow-auto">
        <router-view v-slot="{ Component }">
          <transition name="slide-fade" mode="out-in">
            <component :is="Component" :key="route.path" />
          </transition>
        </router-view>
      </main>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';
import {
  Home as HomeIcon,
  LogOut as LogOutIcon,
  Menu as MenuIcon,
  History as HistoryIcon,
  FilePlus as FilePlusIcon,
} from '@lucide/vue';

const logoW = '/images/logo-w.png';
const clinicaAuth = useClinicaAuthStore();
const route = useRoute();
const mobileOpen = ref(false);

const isDashboard = computed(() => route.path === '/clinica/dashboard');
const isSolicitud = computed(() => route.path === '/clinica/solicitud');
const isHistorial = computed(() => route.path === '/clinica/historial');

const iniciales = computed(() => {
  const nombre = clinicaAuth.clinica?.nombre ?? '';
  return nombre.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase();
});

async function logout() {
  await clinicaAuth.logout();
}
</script>

<style scoped>
aside { position: relative; }
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,.07); border-radius: 4px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-fade-enter-active { transition: all 0.3s cubic-bezier(.22,1,.36,1); }
.slide-fade-leave-active { transition: all 0.2s cubic-bezier(.22,1,.36,1); }
.slide-fade-enter-from { opacity: 0; transform: translateY(12px); }
.slide-fade-leave-to { opacity: 0; transform: translateY(-8px); }

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

/* ── Divisor ── */
.sidebar-divider {
  margin: 0 16px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
}
.divider-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08));
}
.divider-line:last-child {
  background: linear-gradient(90deg, rgba(255,255,255,0.08), transparent);
}

/* ── Menu items ── */
.menu-item {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 12px 14px;
  border-radius: 14px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all .2s ease;
  position: relative;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.04);
}
.menu-item:hover {
  background: rgba(255,255,255,0.06);
  border-color: rgba(255,255,255,0.08);
}
.menu-item-active {
  background: linear-gradient(135deg, rgba(22,70,142,0.5), rgba(13,45,107,0.6));
  border-color: rgba(126,179,255,0.15);
  box-shadow: 0 4px 14px rgba(22,70,142,0.3), inset 0 1px 0 rgba(255,255,255,0.06);
}
.menu-item-active:hover {
  background: linear-gradient(135deg, rgba(22,70,142,0.55), rgba(13,45,107,0.65));
}

.menu-accent-bar {
  position: absolute;
  left: 0; top: 50%;
  transform: translateY(-50%);
  width: 3px; height: 60%;
  border-radius: 0 3px 3px 0;
  background: linear-gradient(180deg, #7eb3ff, #16468e);
  box-shadow: 0 0 8px rgba(126,179,255,0.4);
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
.menu-icon-active {
  background: linear-gradient(135deg, rgba(126,179,255,0.2), rgba(22,70,142,0.3));
  color: #7eb3ff;
  box-shadow: 0 2px 8px rgba(126,179,255,0.15);
}

.menu-label {
  font-size: 13.5px;
  font-weight: 500;
  color: #8294b8;
  transition: color .2s ease;
}
.menu-item:hover .menu-label {
  color: #a0b3d0;
}
.menu-label-active {
  color: #ffffff;
  font-weight: 700;
}

.menu-dot {
  margin-left: auto;
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #7eb3ff;
  box-shadow: 0 0 8px #7eb3ff;
  flex-shrink: 0;
}

/* ── Logout ── */
.logout-btn {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: 11px 14px;
  border-radius: 14px;
  cursor: pointer;
  transition: all .2s ease;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.04);
  color: #6b82a8;
  font-size: 13px;
  font-weight: 500;
}
.logout-btn:hover {
  background: rgba(248,113,113,0.08);
  border-color: rgba(248,113,113,0.15);
  color: #f87171;
}
</style>
