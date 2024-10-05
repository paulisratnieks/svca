<script setup lang="ts">
import {RouterLink, RouterView} from 'vue-router'
import router from '@/router';

function onCreateMeetingButtonClick(): void {
	axios.post('https://' + import.meta.env.VITE_API_HOST + '/meetings', {user_id: 1})
		.then((response: any): void => {
			const id = response.data.id;
			router.push({path: '/meetings/' + id});
		});
}
</script>

<template>
	<button
		@click="onCreateMeetingButtonClick"
	>Create meeting</button>

	<RouterView/>
</template>

<style scoped>
</style>
