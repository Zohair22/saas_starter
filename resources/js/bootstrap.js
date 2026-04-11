import axios from 'axios';
import { getAuthHeaders, localClearSession } from './session';
import { triggerToast } from './globalToast';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common.Accept = 'application/json';

const authHeaders = getAuthHeaders();

if (authHeaders.Authorization) {
	window.axios.defaults.headers.common.Authorization = authHeaders.Authorization;
}

if (authHeaders['X-Tenant-ID']) {
	window.axios.defaults.headers.common['X-Tenant-ID'] = authHeaders['X-Tenant-ID'];
}

window.axios.interceptors.response.use(
	(response) => response,
	(error) => {
		const status = error.response?.status;
		const url = error.config?.url ?? '';

		if (status === 401 && !url.includes('/logout')) {
			// Local-only cleanup to avoid re-triggering this interceptor via the logout API call.
			localClearSession();
			window.location.href = '/login';
		} else if (status === 403) {
			triggerToast('error', 'You do not have permission to perform this action.');
		} else if (status === 404) {
			triggerToast('error', 'The requested resource was not found.');
		} else if (status === 429) {
			triggerToast('error', 'Too many requests. Please slow down.');
		} else if (status >= 500) {
			triggerToast('error', 'A server error occurred. Please try again.');
		}

		return Promise.reject(error);
	},
);
