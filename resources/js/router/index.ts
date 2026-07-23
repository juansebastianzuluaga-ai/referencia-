import { createRouter, createWebHistory } from 'vue-router';
import { ElMessage } from 'element-plus';
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
      component: () => import('@/components/layout/AppLayout.vue'),
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
      ],
    },
  ],
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();
  const clinicaAuth = useClinicaAuthStore();

  // Rutas totalmente públicas — pasar directo sin hidratar ningún store
  if (to.meta.isPublic) {
    return next();
  }

  // Guard para rutas de clínica externa
  if (to.meta.requiresClinicaAuth) {
    if (!clinicaAuth.isHydrated) {
      await clinicaAuth.fetchClinica();
    }
    if (!clinicaAuth.isAuthenticated) {
      return next({ name: 'login-externo' });
    }
    return next();
  }

  if (!auth.isHydrated) {
    await auth.fetchUser();
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login' });
  }

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return next({ name: 'dashboard' });
  }

  if (to.meta.permissions) {
    const required = Array.isArray(to.meta.permissions)
      ? to.meta.permissions as string[]
      : [to.meta.permissions as string];
    const hasAccess = required.some((p: string) => auth.hasPermission(p));
    if (!hasAccess) {
      ElMessage.error('No tiene permisos para acceder a este módulo.');
      return next({ name: 'dashboard' });
    }
  }

  next();
});

export default router;
