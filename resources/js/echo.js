// Laravel Reverb / broadcasting bootstrap (Phase 9).
//
// Only initializes window.Echo when the person is actually logged in
// AND a Reverb app key is configured — if Reverb isn't set up/running
// yet, we skip real-time features entirely instead of crashing the
// whole page (Pusher's constructor throws if the key is missing).
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function bootEcho() {
    if (window.Echo) return window.Echo;

    const key = import.meta.env.VITE_REVERB_APP_KEY;

    if (!key) {
        // Reverb isn't configured — return a no-op stub so callers
        // (.private().notification()/.listen()) don't crash.
        return {
            private: () => ({
                notification: () => {},
                listen: () => {},
            }),
            leave: () => {},
            disconnect: () => {},
        };
    }

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch {
        return {
            private: () => ({
                notification: () => {},
                listen: () => {},
            }),
            leave: () => {},
            disconnect: () => {},
        };
    }

    return window.Echo;
}

export function teardownEcho() {
    if (window.Echo) {
        window.Echo.disconnect();
        window.Echo = null;
    }
}