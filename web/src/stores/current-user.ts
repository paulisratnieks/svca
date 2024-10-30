import {type Ref, ref} from 'vue'
import {defineStore} from 'pinia'
import type {User} from '@/types/user';
import type {AxiosResponse} from 'axios';
import {useAxios} from '@/composables/axios';

export const useCurrentUserStore = defineStore('current-user', () => {
	const user: Ref<User | null> = ref(null);

	function fetch(): Promise<void> {
		return useAxios().get('user')
			.then((response: AxiosResponse<User>): void => {
				user.value = response.data;
			})
			.catch((response: AxiosResponse): void => {
				console.log(response);
			});
	}

	return { user, fetch };
}, {
	persist: {
		pick: ['user'],
	}
})
