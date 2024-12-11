import {type Ref, ref} from 'vue'
import {defineStore} from 'pinia'
import type {User} from '@/types/user';
import type {AxiosResponse} from 'axios';
import {useAxios} from '@/composables/axios';

export const useAuth = defineStore('auth', () => {
	const user: Ref<User | null> = ref(null);

	function fetch(): Promise<void> {
		return useAxios().get('user')
			.then((response: AxiosResponse<User>): void => {
				user.value = response.data;
			});
	}

	function isAuthenticated(): boolean {
		return user.value !== null;
	}

	function $reset(): void {
		user.value = null;
	}

	return { user, fetch, isAuthenticated, $reset };
}, {
	persist: {
		pick: ['user'],
	}
})
