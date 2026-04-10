import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const bearerToken = window.localStorage.getItem('auth_token');
const tenantId = window.localStorage.getItem('tenant_id');

const authHeaders = {};

if (bearerToken) {
    authHeaders.Authorization = `Bearer ${bearerToken}`;
}

if (tenantId) {
    authHeaders['X-Tenant-ID'] = tenantId;
}

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 6001),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 6001),
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: authHeaders,
    },
});
