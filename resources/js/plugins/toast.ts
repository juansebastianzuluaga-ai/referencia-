import { reactive } from 'vue';

export type NotifyType = 'success' | 'error' | 'warning' | 'info';

export interface NotifyItem {
  id: number;
  type: NotifyType;
  message: string;
  duration: number;
}

let idCounter = 0;

export const notifyState = reactive<{ queue: NotifyItem[]; current: NotifyItem | null }>({
  queue: [],
  current: null,
});

function processQueue() {
  if (notifyState.current || notifyState.queue.length === 0) return;
  notifyState.current = notifyState.queue.shift() ?? null;
}

function push(type: NotifyType, message: string, duration: number) {
  notifyState.queue.push({ id: ++idCounter, type, message, duration });
  processQueue();
}

export function dismissCurrent() {
  notifyState.current = null;
  processQueue();
}

export const notify = {
  success(msg: string) {
    push('success', msg, 3000);
  },
  error(msg: string) {
    push('error', msg, 4000);
  },
  info(msg: string) {
    push('info', msg, 3000);
  },
  warning(msg: string) {
    push('warning', msg, 3500);
  },
};

export default notify;
