<script setup lang="ts">
import {onMounted} from 'vue';
import Echo from 'laravel-echo';

onMounted((): void => {
	const id = 1;
	Echo.private(`App.Models.User.${id}`)
		.listen('Event', (e: any): void => {
			console.log(e);
		});
});
</script>

<template>
</template>

<style scoped>
</style>
