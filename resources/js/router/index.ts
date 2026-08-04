import { createRouter, createWebHistory } from 'vue-router';
import notify from '@/plugins/toast';
import { useAuthStore } from '@/stores/auth';
import { useClinicaAuthStore } from '@/stores/clinicaAuth';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('@/views/ForgotPasswordView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('@/views/ResetPasswordView.vue'),
      meta: { isPublic: true },
    },

    // ── Login externo (redirige al login unificado) ──────────────────────────
    {
      path: '/login-externo',
      redirect: '/login',
    },
    {
      path: '/login-externo/verificar-otp',
      name: 'login-externo-otp',
      component: () => import('@/views/login-externo/VerificarOtpView.vue'),
      meta: { isPublic: true },
    },
    {
      path: '/login-externo/magic/:token',
      name: 'login-externo-magic',
      component: () => import('@/views/login-externo/MagicLinkView.vue'),
      meta: { isPublic: true },
    },
    {
      path: '/login-externo/registro',
      name: 'registro-clinica',
      component: () => import('@/views/login-externo/RegistroClinicaView.vue'),
      meta: { isPublic: true },
    },
    {
      path: '/clinica',
      component: () => import('@/components/layout/ClinicaLayout.vue'),
      meta: { requiresClinicaAuth: true },
      children: [
        {
          path: '',
          redirect: '/clinica/dashboard',
        },
        {
          path: 'dashboard',
          name: 'clinica-dashboard',
          component: () => import('@/views/login-externo/ClinicaDashboardView.vue'),
        },
        {
          path: 'historial',
          name: 'clinica-historial',
          component: () => import('@/views/login-externo/HistorialSolicitudesView.vue'),
        },
        {
          path: 'solicitud',
          name: 'clinica-solicitud',
          component: () => import('@/views/login-externo/SolicitudView.vue'),
        },
      ],
    },
    // ────────────────────────────────────────────────────────────────────────

    {
      path: '/',
      component: () => import('@/components/layout/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/dashboard',
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/DashboardView.vue'),
        },
        {
          path: 'usuarios',
          name: 'users',
          component: () => import('@/views/users/UsersView.vue'),
          meta: { permissions: ['users.view'] },
        },
        {
          path: 'roles',
          name: 'roles',
          component: () => import('@/views/roles/RolesView.vue'),
          meta: { permissions: ['roles.view'] },
        },
        {
          path: 'configuracion',
          name: 'settings',
          component: () => import('@/views/settings/SettingsView.vue'),
          meta: { permissions: ['settings.view'] },
        },
        {
          path: 'perfil',
          name: 'profile',
          component: () => import('@/views/profile/ProfileView.vue'),
        },
        {
          path: 'clinicas',
          name: 'clinicas',
          component: () => import('@/views/clinicas/ClinicasView.vue'),
          meta: { permissions: ['clinicas.view'] },
        },
        {
          path: 'solicitudes-referencia',
          name: 'solicitudes-referencia',
          component: () => import('@/views/solicitudes/SolicitudesReferenciaView.vue'),
          meta: { permissions: ['clinicas.view'] },
        },
      ],
    },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  const clinicaAuth = useClinicaAuthStore();

  const isPublic = to.matched.some((record) => record.meta.isPublic);
  const requiresClinicaAuth = to.matched.some((record) => record.meta.requiresClinicaAuth);
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
  const requiresGuest = to.matched.some((record) => record.meta.requiresGuest);
  const permissions = to.matched.flatMap((record) => {
    if (!record.meta.permissions) return [];
    return Array.isArray(record.meta.permissions) ? record.meta.permissions : [record.meta.permissions];
  });

  // Rutas totalmente públicas — pasar directo sin hidratar ningún store
  if (isPublic) {
    return true;
  }

  // Guard para rutas de clínica externa
  if (requiresClinicaAuth) {
    const token = clinicaAuth.getToken();
    if (!token) {
      return { name: 'login' };
    }
    const isOriginal = await clinicaAuth.checkDuplicate();
    if (!isOriginal) {
      return { name: 'login' };
    }
    if (!clinicaAuth.isHydrated) {
      await clinicaAuth.fetchClinica();
    }
    if (!clinicaAuth.isAuthenticated) {
      return { name: 'login' };
    }
    return true;
  }

  if (!auth.isHydrated) {
    const isOriginal = await auth.checkDuplicate();
    if (!isOriginal) {
      return { name: 'login' };
    }
    await auth.fetchUser();
  }

  if (requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' };
  }

  if (requiresGuest && auth.isAuthenticated) {
    return { name: 'dashboard' };
  }

  if (permissions.length) {
    const required = permissions as string[];
    const hasAccess = required.some((p: string) => auth.hasPermission(p));
    if (!hasAccess) {
      notify.error('No tiene permisos para acceder a este módulo.');
      return { name: 'dashboard' };
    }
  }

  return true;
});

export default router;
