import type {AxiosInstance, AxiosResponse} from 'axios';
import router from '@/router';
import {HttpStatusCode} from 'axios';

const httpStatusCodeTokenMismatch: number = 419;

export function useAxios(): AxiosInstance {
	const axiosInstance: AxiosInstance = window.axios.create({
		baseURL: import.meta.env.VITE_API_URL
	});

	axiosInstance.interceptors.response.use(
		(response: AxiosResponse<unknown>) => response,
		(error: AxiosResponse<unknown>) => {
			if (error.status === HttpStatusCode.Unauthorized || error.status === httpStatusCodeTokenMismatch) {
				router.push({path: 'login'});
			}

			return Promise.reject(error);
		}
	)

	return axiosInstance;
}