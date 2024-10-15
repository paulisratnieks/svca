<script setup lang="ts">
import {onMounted, type Ref, useTemplateRef} from 'vue';
import type {User} from '@/types/user';

const props = defineProps<{
	user: User,
	stream: MediaStream
}>();

const video: Ref<HTMLVideoElement|null> = useTemplateRef('video');

onMounted(() => {
	if (video.value) {
		video.value.srcObject = props.stream;
	}
});
</script>

<template>
	<section>
		<video ref="video" autoplay playsinline></video>
		<div>{{ user.name }}</div>
	</section>
</template>
