<script setup lang="ts">
import {computed, onMounted, ref, type Ref} from 'vue';
import {useRoute} from 'vue-router';
import type {Message} from '@/types/message';

const route = useRoute();

const messageBody: Ref<string> = ref('');
const meetingId: Ref<string> = computed((): string => {
	return route.params.meetingId as string;
});

onMounted((): void => {
	window.Echo.private(`meetings.${meetingId.value}`)
		.listen('MessageCreated', (event: Message): void => {
			console.log(event);
		});
});

function onCreateMessageClick(): void {
	window.axios.post(
		import.meta.env.VITE_API_URL + '/meetings/' + meetingId.value + '/messages',
		{body: messageBody.value, user_id: 1}
	);
}

</script>

<template>
  <main>
	  <input v-model="messageBody">
	  <button @click="onCreateMessageClick">Create message</button>
  </main>
</template>
