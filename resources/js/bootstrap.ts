/**
 * Setup Echo for Reverb / Pusher broadcast listener if configured.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'>;
    }
}

window.Pusher = Pusher;

const isHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';
const currentHost = typeof window !== 'undefined' ? window.location.hostname : 'localhost';

try {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY || 'rd-prod-key-xyz',
        wsHost: currentHost,
        wsPort: isHttps ? 443 : (Number(import.meta.env.VITE_REVERB_PORT) || 8080),
        wssPort: isHttps ? 443 : (Number(import.meta.env.VITE_REVERB_PORT) || 8080),
        forceTLS: isHttps,
        enabledTransports: ['ws', 'wss'],
    });
} catch (e) {
    // Graceful fallback for systems without Reverb running
}
