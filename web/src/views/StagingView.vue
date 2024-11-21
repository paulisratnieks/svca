<script setup lang="ts">
import ToggleSwitch from 'primevue/toggleswitch';
import Panel from 'primevue/panel';
import Button from 'primevue/button';
import {computed, type ComputedRef, onMounted, reactive, ref, type Ref} from 'vue';
import {createLocalTracks, LocalTrack, Track} from 'livekit-client';
import router from '@/router';
import {useCurrentUserStore} from '@/stores/current-user';
import VideoWindow from '@/components/VideoWindow.vue';
import {useRoute} from 'vue-router';

const route = useRoute();
const currentUser = useCurrentUserStore();

const tracks: Ref<LocalTrack[]> = ref([]);

const meetingId: Ref<string> = computed((): string => {
	return route.query.meetingId as string;
});

const enabledMedia: Record<string, boolean> = reactive({
	audio: false,
	video: false,
});

const videoTrackLabel: ComputedRef<string | undefined> = computed(() => {
	return trackByKind(Track.Kind.Video)?.mediaStreamTrack.label;
});

const audioTrackLabel: ComputedRef<string | undefined> = computed(() => {
	return trackByKind(Track.Kind.Audio)?.mediaStreamTrack.label;
});

function trackByKind(kind: Track.Kind): Track | undefined {
	return tracks.value.find((track: Track): boolean => track.kind === kind);
}

function onVideoSwitchUpdate(): void {
	const videoTrack: LocalTrack = tracks.value.find((track: LocalTrack): boolean => track.kind === Track.Kind.Video)!;
	if (enabledMedia.video) {
		videoTrack.unmute();
	} else {
		videoTrack.mute();
	}
}

function onAudioSwitchUpdate(): void {
	const videoTrack: LocalTrack = tracks.value.find((track: LocalTrack): boolean => track.kind === Track.Kind.Audio)!;
	if (enabledMedia.audio) {
		videoTrack.unmute();
	} else {
		videoTrack.mute();
	}
}

function onCancelButtonClick(): void {
	router.back();
}

function onJoinButtonClick(): void {
	router.push({path: '/meetings/' + meetingId.value});
}

onMounted(() => {
	createLocalTracks({audio: true, video: true})
		.then((enabledTracks: LocalTrack[]) => {
			tracks.value = enabledTracks;
			enabledMedia.audio = true;
			enabledMedia.video = true;
		});
});

</script>

<template>
	<main>
		<div class="row">
			<div class="video-window">
				<VideoWindow
					v-if="currentUser.user"
					:audio-track="trackByKind(Track.Kind.Audio)"
					:video-track="trackByKind(Track.Kind.Video)"
					:user="currentUser.user"
				></VideoWindow>
			</div>
			<div class="media-controls">
				<Panel :header="'Video'">
					<p>{{ videoTrackLabel }}</p>
					<ToggleSwitch v-model="enabledMedia.video" @change="onVideoSwitchUpdate"></ToggleSwitch>
				</Panel>
				<Panel :header="'Audio'">
					<p>{{ audioTrackLabel }}</p>
					<ToggleSwitch v-model="enabledMedia.audio" @change="onAudioSwitchUpdate"></ToggleSwitch>
				</Panel>
			</div>
		</div>
		<div class="row actions">
			<Button severity="secondary" @click="onCancelButtonClick">Cancel</Button>
			<Button @click="onJoinButtonClick">Join now</Button>
		</div>
	</main>
</template>

<style lang="scss" scoped>
main {
	max-width: 1000px;
	width: auto;
	padding: 64px 16px 0 16px;
	margin: 0 auto;

	.row {
		align-self: flex-start;
		display: flex;
		gap: 8px;
		flex-direction: column;

		@include respond-above(md) {
			flex-direction: row;
		}

		.video-window {
			align-self: flex-start;
		}

		.media-controls {
			display: flex;
			flex-direction: column;
			gap: 8px;
			width: 100%;

			:deep(.p-panel-content) {
				display: flex;
				flex-direction: column;
				gap: 8px;
			}
		}

		&.actions {
			padding-top: 16px;
			margin: 0 auto;
		}
	}
}

</style>
