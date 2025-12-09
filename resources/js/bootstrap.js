import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo & Reverb Setup
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Debug logs
console.log('🔧 Echo Configuration:', {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME,
});

// Connection status
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Laravel Echo connected to Reverb!');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('⚠️ Laravel Echo disconnected from Reverb');
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Laravel Echo connection error:', err);
});

window.Echo.connector.pusher.connection.bind('state_change', (states) => {
    console.log('🔄 Connection state changed:', states.current);
});
