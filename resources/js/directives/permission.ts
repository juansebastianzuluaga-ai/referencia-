import type { Directive, DirectiveBinding } from 'vue';
import { useAuthStore } from '@/stores/auth';

function evaluate(binding: DirectiveBinding): boolean {
  const auth = useAuthStore();
  const value = binding.value;
  const modifier = binding.modifiers;

  if (value == null) return true;

  const permissions: string[] = Array.isArray(value)
    ? value
    : String(value).split(',').map((s: string) => s.trim()).filter(Boolean);

  if (permissions.length === 0) return true;

  if (modifier.any) {
    return permissions.some(p => auth.hasPermission(p));
  }

  return permissions.every(p => auth.hasPermission(p));
}

export const PermissionDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    if (!evaluate(binding)) {
      el.parentNode?.removeChild(el);
    }
  },

  updated(el: HTMLElement, binding: DirectiveBinding) {
    if (!evaluate(binding)) {
      el.parentNode?.removeChild(el);
    }
  },
};

export default PermissionDirective;
