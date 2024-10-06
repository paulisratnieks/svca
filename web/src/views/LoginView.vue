<script setup lang="ts">
import {type Ref, ref} from 'vue';
import type {AxiosResponse} from 'axios';

const email: Ref<string> = ref('');
const password: Ref<string> = ref('');

function onLoginButtonClick(): void {
	window.axios.get(import.meta.env.VITE_API_URL + '/sanctum/csrf-cookie')
		.then((): Promise<AxiosResponse<{message: string}>> => {
			return window.axios.post(
				import.meta.env.VITE_API_URL + '/login',
				{email: email.value, password: password.value}
			);
		}).then((response: AxiosResponse<{message: string}>): void => {
			console.log(response.data.message);
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
