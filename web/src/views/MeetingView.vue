<script setup lang="ts">
import {computed, onMounted, ref, type Ref} from 'vue';
import {useRoute} from 'vue-router';

const route = useRoute();

const messageBody: Ref<string> = ref('');

const meetingId: Ref<string> = computed((): string => {
	return route.params.meetingId as string;
});

onMounted((): void => {
	window.axios.get('https://' + import.meta.env.VITE_API_HOST + '/meetings/' + meetingId.value)
		.then((): void => {
		});

	window.Echo.private(`meetings.${meetingId.value}`)
		.listen('MessageCreated', (e): void => {
			console.log(e);
		});
});

function onCreateMessageClick(): void {
	window.axios.post('https://' + import.meta.env.VITE_API_HOST + '/meetings/' + meetingId.value + '/messages',
		{
			body: messageBody.value,
			user_id: 1
		}
	).then((res: any): void => {
		console.log(res);
		});
}

</script>

<template>
  <main>
	  <input v-model="messageBody">
	  <button @click="onCreateMessageClick">Create message</button>
  </main>
</template>
