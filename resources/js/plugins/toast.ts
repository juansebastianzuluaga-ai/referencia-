import { reactive } from 'vue';

export type NotifyType = 'success' | 'error' | 'warning' | 'info';

export interface NotifyItem {
  id: number;
  type: NotifyType;
  message: string;
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

function push(type: NotifyType, message: string) {
  notifyState.queue.push({ id: ++idCounter, type, message });
  processQueue();
}

export function dismissCurrent() {
  notifyState.current = null;
  processQueue();
}

export const notify = {
  success(msg: string) {
    push('success', msg);
  },
  error(msg: string) {
    push('error', msg);
  },
  info(msg: string) {
    push('info', msg);
  },
  warning(msg: string) {
    push('warning', msg);
  },
};

export default notify;
