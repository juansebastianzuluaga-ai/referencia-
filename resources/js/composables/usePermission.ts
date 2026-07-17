import { useAuthStore } from '@/stores/auth';

export function usePermission() {
  const auth = useAuthStore();

  function can(permission: string): boolean {
    return auth.hasPermission(permission);
  }

  function canAny(permissions: string[]): boolean {
    return permissions.some(p => auth.hasPermission(p));
  }

  function canAll(permissions: string[]): boolean {
    return permissions.every(p => auth.hasPermission(p));
  }

  return {
    can,
    canAny,
    canAll,
  };
}
