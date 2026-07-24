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
      style="background:#1e2d55; box-shadow: 6px 0 24px rgba(0,0,0,0.25);"
    >
      <!-- Título sección -->
      <div class="px-5 pt-7 pb-4 shrink-0">
        <p style="color:#ffffff; font-size:15px; font-weight:700; letter-spacing:0.01em; margin:0;">
          Sistema de Referencia
        </p>
        <p style="color:rgba(255,255,255,0.35); font-size:10px; margin:4px 0 0; letter-spacing:0.03em;">
          Panel de clínica externa
        </p>
      </div>

      <!-- Divisor neumórfico -->
      <div style="margin: 0 14px 16px; height:1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);"></div>

      <!-- Label menú -->
      <div class="px-5 pb-2 shrink-0">
        <p style="color:rgba(255,255,255,0.3); font-size:10px; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; margin:0;">
          Menú
        </p>
      </div>

      <!-- Ítems -->
      <nav class="flex-1 overflow-y-auto" style="padding: 0 12px;">

        <!-- Inicio -->
        <router-link to="/clinica/dashboard" class="no-underline" @click="mobileOpen = false">
          <div
            class="flex items-center gap-3 rounded-2xl mb-2 transition-all duration-200"
            style="padding: 12px 16px; cursor:pointer;"
            :style="isActive
              ? 'background:#1e2d55; box-shadow: inset 4px 4px 8px #16223f, inset -4px -4px 8px #26386b; color:#7eb3ff;'
              : 'background:#1e2d55; box-shadow: 4px 4px 8px #16223f, -4px -4px 8px #26386b;'"
          >
            <HomeIcon
              class="shrink-0"
              style="width:18px; height:18px;"
              :style="isActive ? 'color:#7eb3ff;' : 'color:#6b82a8;'"
            />
            <span
              style="font-size:13.5px;"
              :style="isActive ? 'color:#ffffff; font-weight:600;' : 'color:#8294b8; font-weight:400;'"
            >
              Inicio
            </span>
          </div>
        </router-link>

        <!-- Cerrar sesión -->
        <div
          class="flex items-center gap-3 rounded-2xl mb-2 transition-all duration-200"
          style="padding: 12px 16px; cursor:pointer; background:#1e2d55; box-shadow: 4px 4px 8px #16223f, -4px -4px 8px #26386b;"
          @click="logout"
          @mouseenter="(e: MouseEvent) => {
            const el = e.currentTarget as HTMLElement;
            el.style.boxShadow='inset 4px 4px 8px #16223f, inset -4px -4px 8px #26386b';
            el.querySelectorAll('span, svg').forEach((n: Element) => (n as HTMLElement).style.color='#f87171');
          }"
          @mouseleave="(e: MouseEvent) => {
            const el = e.currentTarget as HTMLElement;
            el.style.boxShadow='4px 4px 8px #16223f, -4px -4px 8px #26386b';
            el.querySelectorAll('span, svg').forEach((n: Element) => (n as HTMLElement).style.color='#6b82a8');
          }"
        >
          <LogOutIcon class="shrink-0" style="width:18px; height:18px; color:#6b82a8;" />
          <span style="font-size:13.5px; font-weight:400; color:#6b82a8;">Cerrar sesión</span>
        </div>

      </nav>

      <!-- Footer sidebar -->
      <div class="shrink-0 px-4 py-4" style="border-top: 1px solid rgba(255,255,255,0.06);">
        <div class="rounded-xl p-3" style="background:rgba(255,255,255,.055); border:1px solid rgba(255,255,255,.07);">
          <div class="flex items-center gap-2 text-xs font-semibold text-white"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> Sistema en línea</div>
          <p style="color:rgba(255,255,255,.45); font-size:10px; margin:7px 0 0; line-height:1.45;">¿Necesita ayuda? Contacte al equipo de referencia.</p>
        </div>
        <p style="color:rgba(255,255,255,0.15); font-size:10px; margin:12px 0 0; text-align:center;">© 2026 Clínica Santa Bárbara</p>
      </div>
    </aside>

    <!-- ── Contenido ────────────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-h-0 overflow-hidden">

      <!-- Topbar -->
      <header
        class="shrink-0 flex items-center justify-between px-5"
        style="height:58px; background:#1e2d55; border-bottom:1px solid rgba(255,255,255,0.07); box-shadow: 0 2px 12px rgba(0,0,0,0.18);"
      >
        <!-- Izquierda: menú móvil + logo -->
        <div class="flex items-center gap-3">
          <button
            class="md:hidden flex items-center justify-center rounded-lg mr-1"
            style="width:32px; height:32px; background:rgba(255,255,255,0.08); color:#fff;"
            @click="mobileOpen = true"
          >
            <MenuIcon style="width:16px; height:16px;" />
          </button>
          <img :src="logoW" alt="Santa Bárbara" style="height:30px; width:auto; object-fit:contain;" />
        </div>

        <!-- Derecha: info clínica + cerrar sesión -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2.5">
            <div
              class="flex items-center justify-center rounded-full font-bold shrink-0"
              style="width:34px; height:34px; background:rgba(255,255,255,0.15); color:#ffffff; font-size:13px;"
            >
              {{ iniciales }}
            </div>
            <div class="hidden sm:block text-right">
              <p style="color:#ffffff; font-size:13px; font-weight:600; line-height:1.2; max-width:180px;" class="truncate">
                {{ clinicaAuth.clinica?.nombre }}
              </p>
              <p style="color:#8294b8; font-size:10px; line-height:1.2;">NIT: {{ clinicaAuth.clinica?.nit }}</p>
            </div>
          </div>

          <button
            @click="logout"
            class="flex items-center gap-2 rounded-xl transition-all duration-150"
            style="padding:7px 14px; border:1px solid rgba(255,255,255,0.18); background:transparent; color:#ffffff; font-size:13px; font-weight:500; cursor:pointer;"
            @mouseenter="(e: MouseEvent) => (e.currentTarget as HTMLElement).style.background='rgba(255,255,255,0.1)'"
            @mouseleave="(e: MouseEvent) => (e.currentTarget as HTMLElement).style.background='transparent'"
          >
            <LogOutIcon style="width:15px; height:15px;" />
            <span class="hidden sm:inline">Cerrar sesión</span>
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-auto">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
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
} from '@lucide/vue';

const logoW = '/images/logo-w.png';
const clinicaAuth = useClinicaAuthStore();
const route = useRoute();
const mobileOpen = ref(false);

const isActive = computed(() => route.path.startsWith('/clinica'));

const iniciales = computed(() => {
  const nombre = clinicaAuth.clinica?.nombre ?? '';
  return nombre.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase();
});

async function logout() {
  await clinicaAuth.logout();
}
</script>

<style scoped>
aside::-webkit-scrollbar { width: 4px; }
aside::-webkit-scrollbar-thumb { background: rgba(255,255,255,.07); border-radius: 4px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
