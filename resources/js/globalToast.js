/**
 * Dispatches a toast notification through a custom DOM event.
 *
 * This allows modules that execute before React mounts (e.g. bootstrap.js)
 * to queue toast messages that ToastProvider will pick up once mounted.
 *
 * @param {'success'|'error'} type
 * @param {string} message
 */
export function triggerToast(type, message) {
    window.dispatchEvent(
        new CustomEvent('app:toast', { detail: { type, message } }),
    );
}
