/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Reverb uses the Pusher protocol. We keep this resilient so the app still
// runs even if BROADCAST_DRIVER is not enabled locally.
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY;
if (reverbKey) {
	const scheme = import.meta.env.VITE_REVERB_SCHEME ?? import.meta.env.VITE_PUSHER_SCHEME ?? 'http';
	const forceTLS = scheme === 'https';

	window.Echo = new Echo({
		broadcaster: 'pusher',
		key: reverbKey,
		cluster: import.meta.env.VITE_REVERB_APP_CLUSTER ?? import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
		wsHost: import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
		wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? import.meta.env.VITE_PUSHER_PORT ?? (forceTLS ? 443 : 80)),
		wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? import.meta.env.VITE_PUSHER_PORT ?? 443),
		forceTLS,
		enabledTransports: ['ws', 'wss'],
	});
}
