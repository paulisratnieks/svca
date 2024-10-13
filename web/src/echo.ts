import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type {AxiosError, AxiosResponse} from 'axios';

declare global {
	interface Window {
		Pusher: any;
		Echo: Echo;
	}
}

window.Pusher = Pusher;
window.Echo = new Echo({
	broadcaster: 'reverb',
	key: import.meta.env.VITE_REVERB_APP_KEY,
	wsHost: import.meta.env.VITE_REVERB_HOST,
	wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
	wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
	forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
	enabledTransports: ['ws', 'wss'],
	authorizer: (channel: { name: string }) => {
		return {
			authorize: (socketId: string, callback: (error: boolean, data: any) => void) => {
				window.axios.post(import.meta.env.VITE_API_URL + '/broadcasting/auth', {
					socket_id: socketId,
					channel_name: channel.name
				})
					.then((response: AxiosResponse<any>): void => {
						callback(false, response.data);
					})
					.catch((error: AxiosError<any>): void => {
						callback(true, error);
					});
			}
		};
	},
});
