import axios from 'axios';

declare global {
	interface Window {
		axios: typeof axios;
	}
}
window.axios = axios;
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
