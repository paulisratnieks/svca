<script setup lang="ts">
import type {User} from '@/types/user';
import {Track} from 'livekit-client';
import {computed, onMounted, onUnmounted, type Ref, useTemplateRef, watch} from 'vue';
import ParticipantLogo from '@/components/ParticipantLogo.vue';
import ParticipantLabel from '@/components/ParticipantLabel.vue';
import {useAuth} from '@/stores/auth';

const auth = useAuth();

const video: Ref<HTMLVideoElement | null> = useTemplateRef('video');

const props = defineProps<{
	user: User,
	audioTrack?: Track,
	audioTrackMuted?: boolean,
	videoTrack?: Track,
	videoTrackMuted?: boolean,
	size?: number,
	isSizeWidth?: boolean,
}>();

const videoWindowStyle: Ref<Record<string, string>> = computed(() => {
	const style: Record<string, string> = {};
	if (props.isSizeWidth !== undefined) {
		style[props.isSizeWidth ? 'width': 'height'] = props.size + 'px';
	}

	return style;
});

watch(
	() => props.audioTrack,
	trackAvailableHandler(),
)

watch(
	() => props.videoTrack,
	trackAvailableHandler(),
)

function shouldAttachTrack(track: Track|undefined): boolean {
	return track !== undefined
		&& !(track?.kind === Track.Kind.Audio && props.user.id === auth.user!.id);
}

function trackAvailableHandler(): (newValue: Track | undefined, oldValue: Track | undefined) => void {
	return (newValue: Track | undefined, oldValue: Track | undefined) => {
		if (newValue !== undefined && oldValue === undefined && video.value && shouldAttachTrack(newValue)) {
			attachTrackToVideo(newValue);
		}
	}
}

function attachTrackToVideo(track: Track): void {
	if (video.value) {
		track.attach(video.value);
	}
}

onMounted(() => {
	if (shouldAttachTrack(props.audioTrack)) {
		attachTrackToVideo(props.audioTrack!)
	}
	if (shouldAttachTrack(props.videoTrack)) {
		attachTrackToVideo(props.videoTrack!)
	}
});

onUnmounted(() => {
	if (props.audioTrack && video.value) {
		props.audioTrack.detach(video.value);
	}
	if (props.videoTrack && video.value) {
		props.videoTrack.detach(video.value);
	}
})
</script>

<template>
	<div class="video-window"
		:class="{'active-speaker': user.isSpeaking}"
		:style="videoWindowStyle">
		<video ref="video" class="active-speaker" autoplay playsinline></video>
		<ParticipantLabel
			:name="user.name"
			:isTrackMuted="audioTrackMuted ?? audioTrack?.isMuted ?? true"
		></ParticipantLabel>
		<ParticipantLogo
			:name="user.name"
			v-if="videoTrackMuted ?? videoTrack?.isMuted ?? true"
		></ParticipantLogo>
	</div>
</template>

<style lang="scss" scoped>

.video-window {
	position: relative;
	border-radius: var(--p-content-border-radius);
	background: var(--p-content-background);
	border: 1px solid var(--p-content-border-color);
	aspect-ratio: 16 / 9;
	overflow: hidden;
	display: flex;
	justify-content: center;
	align-items: center;
	flex-direction: column;

	video {
		border-radius: 4px;
		width: 100%;
		height: inherit;
		display: inline-block;
		object-fit: scale-down;
	}

	&.active-speaker {
		padding: 3px;
		outline: 3px solid var(--p-primary-color);
	}
}
</style>
