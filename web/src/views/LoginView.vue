<script setup lang="ts">
import {type Ref, ref} from 'vue';
import type {AxiosResponse} from 'axios';
import {useCurrentUserStore} from '@/stores/current-user';
import router from '@/router';

const currentUser = useCurrentUserStore();

const email: Ref<string> = ref('');
const password: Ref<string> = ref('');

function onLoginButtonClick(): void {
	window.axios.get(import.meta.env.VITE_API_URL + '/sanctum/csrf-cookie')
		.then((): Promise<AxiosResponse<{ message: string }>> => {
			return window.axios.post(
				import.meta.env.VITE_API_URL + '/login',
				{email: email.value, password: password.value}
			);
		})
		.then((response: AxiosResponse<{ message: string }>): Promise<void> => {
			console.log(response.data.message);

			return currentUser.fetch();
		})
		.then((): void => {
			router.push({path: '/'})
		});
}
</script>

<template>
	<main>
		<input v-model="email">
		<input type="password" v-model="password">
		<button @click="onLoginButtonClick">Login</button>
	</main>
</template>
