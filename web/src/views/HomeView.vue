<script setup lang="ts">
import router from '@/router';
import type {AxiosResponse} from 'axios';

function onCreateMeetingButtonClick(): void {
	window.axios.post(import.meta.env.VITE_API_URL + '/meetings', {user_id: 1})
		.then((response: AxiosResponse<{ id: string }>): void => {
			const id = response.data.id;
			router.push({path: '/meetings/' + id});
		});
}
</script>

<template>
	<main>
		<button
			@click="onCreateMeetingButtonClick"
		>Create meeting
		</button>
	</main>
</template>
