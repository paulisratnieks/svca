<script setup lang="ts">
import {computed, onMounted, type Ref, ref} from 'vue';
import router from '@/router';

const props = defineProps<{
	stream: MediaStream
}>();

type mediaKind = 'audio' | 'video';

const audioEnabled: Ref<boolean> = ref(false);
const videoEnabled: Ref<boolean> = ref(false);
const ad = computed((): boolean => {
	return props.stream.getTracks().find((track: MediaStreamTrack): boolean => track.kind === 'audio')?.enabled === true;
});

function streamMediaByKind(kind: mediaKind): MediaStreamTrack|undefined {
	return props.stream.getTracks().find((track: MediaStreamTrack): boolean => track.kind === kind);
}

function hasStreamEnabledMediaKind(kind: mediaKind): boolean {
	return streamMediaByKind(kind)?.enabled === true;
}

function onMuteButtonClick(): void {
	const audioTrack: MediaStreamTrack|undefined = streamMediaByKind('audio');
	if (audioTrack) {
		audioTrack.enabled = !audioTrack.enabled;
		audioEnabled.value = audioTrack.enabled;
	}
}

function onCameraButtonClick(): void {
	const videoTrack: MediaStreamTrack|undefined = streamMediaByKind('video');
	if (videoTrack) {
		videoTrack.enabled = !videoTrack.enabled;
		videoEnabled.value = videoTrack.enabled;
	}
}

function onLeaveButtonClick(): void {
	router.push({path: '/'});
}

onMounted(() => {
	audioEnabled.value = hasStreamEnabledMediaKind('audio');
	videoEnabled.value = hasStreamEnabledMediaKind('video');
});
</script>

<template>
	<section class="controls">
		<button @click="onCameraButtonClick"
			:class="{'disabled': !videoEnabled}">Camera</button>
		<button @click="onMuteButtonClick"
				:class="{'disabled': !ad}">Mute</button>
		<button @click="onLeaveButtonClick">Leave</button>
	</section>
</template>

<style>
.controls .disabled {
	background-color: red;
}
</style>
