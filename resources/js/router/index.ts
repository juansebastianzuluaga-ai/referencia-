import { createRouter, createWebHistory } from 'vue-router';
import { ElMessage } from 'element-plus';
import { useAuthStore } from '@/stores/auth';

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
      ],
    },
  ],
});

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore();

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
