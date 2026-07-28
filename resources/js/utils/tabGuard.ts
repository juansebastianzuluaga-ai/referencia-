/**
 * Per-tab authentication guard.
 *
 * sessionStorage is scoped to a single tab, but it IS copied when a tab
 * is duplicated. BroadcastChannel lets us detect the duplicate: the new
 * tab broadcasts a "query" with its tabId; if another tab responds, the
 * new tab is a duplicate and its auth is cleared.
 */

let channel: BroadcastChannel | null = null;

try {
  channel = new BroadcastChannel('tab_guard');
} catch {
  channel = null;
}

const pendingChecks = new Map<string, Promise<boolean>>();

function generateTabId(): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    return crypto.randomUUID();
  }
  return Math.random().toString(36).slice(2) + Date.now().toString(36);
}

function tabKey(key: string): string {
  return `${key}_tab_id`;
}

/** Mark the current tab as authenticated for the given auth key. */
export function markTab(key: string): void {
  sessionStorage.setItem(tabKey(key), generateTabId());
  pendingChecks.delete(key);
}

/** Returns true if this tab has been marked as authenticated. */
export function isTabMarked(key: string): boolean {
  return sessionStorage.getItem(tabKey(key)) !== null;
}

/** Remove the tab auth marker. */
export function clearTab(key: string): void {
  sessionStorage.removeItem(tabKey(key));
  pendingChecks.delete(key);
}

/**
 * Returns true if this tab is the original (not a duplicate).
 * If another tab responds with the same tabId, this tab is a duplicate
 * and its auth is cleared.
 */
export async function checkDuplicate(key: string): Promise<boolean> {
  if (pendingChecks.has(key)) return pendingChecks.get(key)!;

  const tabId = sessionStorage.getItem(tabKey(key));
  if (!tabId || !channel) {
    return true;
  }

  const promise = new Promise<boolean>((resolve) => {
    const handler = (event: MessageEvent) => {
      if (
        event.data?.type === 'response' &&
        event.data?.key === key &&
        event.data?.tabId === tabId
      ) {
        cleanup();
        clearTab(key);
        resolve(false);
      }
    };

    const timeout = setTimeout(() => {
      cleanup();
      resolve(true);
    }, 400);

    function cleanup() {
      clearTimeout(timeout);
      channel?.removeEventListener('message', handler);
    }

    channel?.addEventListener('message', handler);
    channel?.postMessage({ type: 'query', key, tabId });
  });

  pendingChecks.set(key, promise);
  return promise;
}

// Permanent listener: respond to queries from other tabs.
if (channel) {
  channel.onmessage = (event: MessageEvent) => {
    const data = event.data;
    if (data?.type !== 'query') return;

    const myTabId = sessionStorage.getItem(tabKey(data.key));
    if (myTabId && myTabId === data.tabId) {
      channel?.postMessage({ type: 'response', key: data.key, tabId: myTabId });
    }
  };
}
