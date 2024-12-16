<script setup lang="ts">
import ToggleSwitch from 'primevue/toggleswitch';
import Panel from 'primevue/panel';
import Button from 'primevue/button';
import {computed, nextTick, onMounted, ref, type Ref} from 'vue';
import {createLocalAudioTrack, createLocalTracks, createLocalVideoTrack, LocalTrack, Track} from 'livekit-client';
import router from '@/router';
import {useAuth} from '@/stores/auth';
import VideoWindow from '@/components/VideoWindow.vue';
import {useRoute} from 'vue-router';
import {useSettingsStore} from '@/stores/settings';
import {useToast} from 'primevue/usetoast';

const route = useRoute();
const toast = useToast();
const settings = useSettingsStore();
const auth = useAuth();

const tracks: Ref<LocalTrack[]> = ref([]);

const meetingId: Ref<string> = computed((): string => {
	return route.query.meetingId as string;
});

function trackLabelByKind(kind: Track.Kind): string|undefined {
	return trackByKind(kind)?.mediaStreamTrack.label;
}

function trackByKind(kind: Track.Kind): LocalTrack | undefined {
	return tracks.value.find((track: LocalTrack): boolean => track.kind === kind);
}

async function onVideoSwitchUpdate(): Promise<void> {
	await mediaUpdate(Track.Kind.Video);
}

async function onAudioSwitchUpdate(): Promise<void> {
	await mediaUpdate(Track.Kind.Audio);
}

async function mediaUpdate(kind: Track.Kind.Video|Track.Kind.Audio): Promise<void> {
	const track: LocalTrack|undefined = trackByKind(kind);
	if (!track) {
		try {
			tracks.value.push(
				await (kind === Track.Kind.Video ? createLocalVideoTrack() : createLocalAudioTrack())
			);
		} catch {
			toast.add({ severity: 'error', summary: 'Staging page', detail: 'Please enable browser access to ' + kind, life: 3000 });
			settings.mediaPreferences[kind] = false;
		}
	} else {
		await nextTick(() => {
			if (settings.mediaPreferences[kind]) {
				track.unmute();
			} else {
				track.mute();
			}
		})
	}
}

function onCancelButtonClick(): void {
	router.back();
}

function onJoinButtonClick(): void {
	router.replace({path: '/meetings/' + meetingId.value});
}

onMounted(() => {
	if (Object.values(settings.mediaPreferences).some(enabledMedia => enabledMedia)) {
		createLocalTracks(Object.assign({}, settings.mediaPreferences))
			.then((enabledTracks: LocalTrack[]) => {
				tracks.value = enabledTracks;
			});
	}
});

</script>

<template>
	<main>
		<div class="row">
			<div class="video-window">
				<VideoWindow
					v-if="auth.user"
					:audio-track="trackByKind(Track.Kind.Audio)"
					:audio-track-muted="trackByKind(Track.Kind.Audio)?.isMuted ?? true"
					:video-track="trackByKind(Track.Kind.Video)"
					:video-track-muted="trackByKind(Track.Kind.Video)?.isMuted ?? true"
					:user="auth.user"
				></VideoWindow>
			</div>
			<div class="media-controls">
				<Panel :header="'Video'">
					<p>{{ trackLabelByKind(Track.Kind.Video) }}</p>
					<ToggleSwitch ref="video-switch" v-model="settings.mediaPreferences.video" @change="onVideoSwitchUpdate"></ToggleSwitch>
				</Panel>
				<Panel :header="'Audio'">
					<p>{{ trackLabelByKind(Track.Kind.Audio) }}</p>
					<ToggleSwitch ref="audio-switch" v-model="settings.mediaPreferences.audio" @change="onAudioSwitchUpdate"></ToggleSwitch>
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
			flex: 5;
			align-self: flex-start;
		}

		.media-controls {
			flex: 3;
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
