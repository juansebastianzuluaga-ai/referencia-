import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import notify from '@/plugins/toast';
import http from '@/plugins/axios';

export interface Notification {
  id: number;
  type: 'info' | 'success' | 'warning' | 'error';
  title: string;
  message: string;
  link: string | null;
  read_at: string | null;
  is_read: boolean;
  created_at: string;
  updated_at: string;
}

/**
 * Fábrica de store de notificaciones: el personal interno y las clínicas
 * externas tienen bandejas separadas (tablas/guardias de auth distintos),
 * así que cada contexto instancia su propio store apuntando a su propio
 * prefijo de API en vez de compartir un único store global.
 */
function createNotificationsStore(id: string, basePath: string) {
  return defineStore(id, () => {
    const notifications = ref<Notification[]>([]);
    const unreadCount = ref(0);
    const loading = ref(false);
    const loaded = ref(false);

    const hasUnread = computed(() => unreadCount.value > 0);

    async function fetchUnreadCount() {
      try {
        const { data } = await http.get(`${basePath}/unread-count`, {
          headers: { 'X-Skip-Auth-Redirect': '1' },
        });
        unreadCount.value = data.data.count;
      } catch {
        // Silently fail - notifications are non-critical
      }
    }

    async function fetchNotifications(options: { unreadOnly?: boolean; force?: boolean } = {}) {
      if (loaded.value && !options.force) {
        return;
      }
      loading.value = true;
      try {
        const params: Record<string, any> = {};
        if (options.unreadOnly) {
          params.unread_only = true;
        }
        const { data } = await http.get(basePath, {
          params,
          headers: { 'X-Skip-Auth-Redirect': '1' },
        });
        const raw = data.data;
        notifications.value = Array.isArray(raw) ? raw : (raw?.data ?? []);
        loaded.value = true;
      } catch {
        notifications.value = [];
      } finally {
        loading.value = false;
      }
    }

    async function markAsRead(notificationId: number) {
      try {
        await http.patch(`${basePath}/${notificationId}/read`);
        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification && !notification.is_read) {
          notification.is_read = true;
          notification.read_at = new Date().toISOString();
          unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
      } catch {
        notify.error('Error al marcar la notificación como leída');
      }
    }

    async function markAllAsRead() {
      try {
        await http.patch(`${basePath}/read-all`);
        notifications.value.forEach(n => {
          n.is_read = true;
          n.read_at = new Date().toISOString();
        });
        unreadCount.value = 0;
        notify.success('Notificaciones marcadas como leídas');
      } catch {
        notify.error('Error al marcar las notificaciones');
      }
    }

    async function deleteNotification(notificationId: number) {
      try {
        await http.delete(`${basePath}/${notificationId}`);
        const notification = notifications.value.find(n => n.id === notificationId);
        if (notification && !notification.is_read) {
          unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
        notifications.value = notifications.value.filter(n => n.id !== notificationId);
      } catch {
        notify.error('Error al eliminar la notificación');
      }
    }

    async function clearAll() {
      try {
        await http.delete(basePath);
        notifications.value = [];
        unreadCount.value = 0;
        notify.success('Notificaciones eliminadas');
      } catch {
        notify.error('Error al eliminar las notificaciones');
      }
    }

    function reset() {
      notifications.value = [];
      unreadCount.value = 0;
      loaded.value = false;
    }

    return {
      notifications,
      unreadCount,
      loading,
      loaded,
      hasUnread,
      fetchUnreadCount,
      fetchNotifications,
      markAsRead,
      markAllAsRead,
      deleteNotification,
      clearAll,
      reset,
    };
  });
}

export const useNotificationsStore = createNotificationsStore('notifications', '/api/notifications');
export const useClinicaNotificationsStore = createNotificationsStore('clinicaNotifications', '/api/externo/notificaciones');
