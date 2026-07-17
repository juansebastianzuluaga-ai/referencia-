import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { ElMessage } from 'element-plus';
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

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<Notification[]>([]);
  const unreadCount = ref(0);
  const loading = ref(false);
  const loaded = ref(false);

  const hasUnread = computed(() => unreadCount.value > 0);

  async function fetchUnreadCount() {
    try {
      const { data } = await http.get('/api/notifications/unread-count', {
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
      const { data } = await http.get('/api/notifications', {
        params,
        headers: { 'X-Skip-Auth-Redirect': '1' },
      });
      notifications.value = data.data || [];
      loaded.value = true;
    } catch {
      notifications.value = [];
    } finally {
      loading.value = false;
    }
  }

  async function markAsRead(notificationId: number) {
    try {
      await http.patch(`/api/notifications/${notificationId}/read`);
      const notification = notifications.value.find(n => n.id === notificationId);
      if (notification && !notification.is_read) {
        notification.is_read = true;
        notification.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
    } catch {
      ElMessage.error('Error al marcar la notificación como leída');
    }
  }

  async function markAllAsRead() {
    try {
      await http.patch('/api/notifications/read-all');
      notifications.value.forEach(n => {
        n.is_read = true;
        n.read_at = new Date().toISOString();
      });
      unreadCount.value = 0;
      ElMessage.success('Notificaciones marcadas como leídas');
    } catch {
      ElMessage.error('Error al marcar las notificaciones');
    }
  }

  async function deleteNotification(notificationId: number) {
    try {
      await http.delete(`/api/notifications/${notificationId}`);
      const notification = notifications.value.find(n => n.id === notificationId);
      if (notification && !notification.is_read) {
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
      notifications.value = notifications.value.filter(n => n.id !== notificationId);
    } catch {
      ElMessage.error('Error al eliminar la notificación');
    }
  }

  async function clearAll() {
    try {
      await http.delete('/api/notifications');
      notifications.value = [];
      unreadCount.value = 0;
      ElMessage.success('Notificaciones eliminadas');
    } catch {
      ElMessage.error('Error al eliminar las notificaciones');
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
