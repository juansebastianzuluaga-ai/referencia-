import { createRouter, createWebHistory } from 'vue-router';
import { ElMessage } from 'element-plus';
import { useAuthStore } from '@/stores/auth';
import { useExternalClinicAuthStore } from '@/stores/externalClinicAuth';

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
      path: '/login-clinica',
      name: 'external-clinic-login',
      component: () => import('@/views/external-clinic/ExternalClinicLoginView.vue'),
      meta: { isPublic: true },
    },
    {
      path: '/registro-clinica',
      name: 'external-clinic-register',
      component: () => import('@/views/external-clinic/ExternalClinicRegisterView.vue'),
      meta: { isPublic: true },
    },
    {
      path: '/solicitud-enviada',
      name: 'external-clinic-request-sent',
      component: () => import('@/views/external-clinic/ExternalClinicRequestSentView.vue'),
      meta: { isPublic: true },
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
          path: 'solicitudes-clinicas',
          name: 'external-clinic-requests',
          component: () => import('@/views/external-clinic/admin/ExternalClinicRequestsView.vue'),
          meta: { permissions: ['external-clinics.view'] },
        },
      ],
    },
    {
      path: '/panel-clinica',
      component: () => import('@/components/layout/ExternalClinicLayout.vue'),
      meta: { requiresExternalClinicAuth: true },
      children: [
        {
          path: '',
          redirect: '/panel-clinica/dashboard',
        },
        {
          path: 'dashboard',
          name: 'external-clinic-dashboard',
          component: () => import('@/views/external-clinic/ExternalClinicDashboardView.vue'),
        },
        {
          path: 'perfil',
          name: 'external-clinic-profile',
          component: () => import('@/views/external-clinic/ExternalClinicProfileView.vue'),
        },
      ],
    },
  ],
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();
  const externalClinicAuth = useExternalClinicAuthStore();

  if (to.meta.isPublic) {
    return next();
  }

  if (!auth.isHydrated) {
    await auth.fetchUser();
  }

  if (!externalClinicAuth.isHydrated) {
    await externalClinicAuth.fetchClinic();
  }

  if (to.meta.requiresExternalClinicAuth) {
    if (!externalClinicAuth.isAuthenticated) {
      return next({ name: 'external-clinic-login' });
    }

    return next();
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
