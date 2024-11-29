<script setup lang="ts">
import {computed, type ComputedRef, onMounted, reactive, ref, type Ref} from 'vue';
import {useRoute} from 'vue-router';
import {
	type ChatMessage,
	LocalParticipant,
	LocalTrackPublication,
	Participant,
	RemoteParticipant,
	RemoteTrack, RemoteTrackPublication,
	Room,
	RoomEvent,
	Track,
	TrackPublication,
	VideoPresets
} from 'livekit-client';
import {useCurrentUserStore} from '@/stores/current-user';
import {useAxios} from '@/composables/axios';
import type {User} from '@/types/user';
import type {Message} from '@/types/message';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import MeetingSidebar from '@/components/MeetingSidebar.vue';
import VideoWindowLayout from '@/components/VideoWindowLayout.vue';
import router from '@/router';
import {useSettingsStore} from '@/stores/settings';
import {useToast} from 'primevue/usetoast';
import type {LocalUserWithTracks} from '@/types/local-user-with-tracks';
import {TabNames} from '@/types/tab-names';
import type {UserWithTracks} from '@/types/user-with-tracks';

const route = useRoute();
const toast = useToast();
const settings = useSettingsStore();
const currentUser = useCurrentUserStore();

const room = new Room({
	adaptiveStream: true,
	dynacast: true,
	videoCaptureDefaults: {
		resolution: VideoPresets.h720.resolution,
	},
});

const messages: Ref<Message[]> = ref([]);
const selectedTabId: Ref<TabNames> = ref(TabNames.Chat);
const isSidebarVisible: Ref<boolean> = ref(false);

const meetingId: Ref<string> = computed((): string => {
	return route.params.meetingId as string;
});

const localParticipant: LocalUserWithTracks = reactive({user: currentUser.user!});
const remoteParticipants: Map<string, UserWithTracks> = reactive(new Map());

const participantsWithTracks: ComputedRef<UserWithTracks[]> = computed(() => {
	return [...remoteParticipants.values()]
		.concat(localParticipant)
		.filter(participant => participant.user);
});

async function mediaToken(): Promise<string> {
	return (await useAxios().get('token', {
			params: {room_name: meetingId.value}
		}
	)).data.data;
}

async function connectToRoom(token: string): Promise<void> {
	return await room.connect(import.meta.env.VITE_LIVEKIT_API_URL, token);
}

function setupCurrentRoomState(): void {
	room.remoteParticipants.forEach(participant => {
		if (participant.attributes.name) {
			remoteParticipants.set(participant.identity, {
				user: {
					name: participant.attributes.name,
					id: Number(participant.attributes.id)
				}
			});
		}

		participant.trackPublications.forEach((publication) => {
			if (publication.track) {
				handleTrackSubscribed(publication.track as Track, publication, participant);
			}
		});
	});
}

function attachRoomEventHandlers() {
	room
		.on(RoomEvent.TrackSubscribed, handleTrackSubscribed)
		.on(RoomEvent.TrackUnsubscribed, handleTrackUnsubscribed)
		.on(RoomEvent.LocalTrackPublished, handleLocalTrackPublished)
		.on(RoomEvent.LocalTrackUnpublished, handleLocalTrackUnpublished)
		.on(RoomEvent.ActiveSpeakersChanged, handleActiveSpeakerChange)
		.on(RoomEvent.ChatMessage, handleChatMessage)
		.on(RoomEvent.TrackMuted, handleTrackMuted)
		.on(RoomEvent.TrackUnmuted, handleTrackUnMuted)
		.on(RoomEvent.ParticipantAttributesChanged, handleParticipantAttributesChanged)
		.on(RoomEvent.ParticipantDisconnected, handleParticipantDisconnected);
}

function handleTrackSubscribed(
	track: Track<Track.Kind>,
	publication: TrackPublication,
	participant: Participant
): void {
	const remoteParticipant = remoteParticipants.get(participant.identity);

	if (track.kind === Track.Kind.Video) {
		if (remoteParticipant && track.source === Track.Source.Camera) {
			remoteParticipant.videoTrack = track;
		} else if (remoteParticipant && track.source === Track.Source.ScreenShare) {
			[...remoteParticipants.values()]
				.forEach(participant => participant.screenVideoTrack = undefined)
			remoteParticipant.screenVideoTrack = track;
		}
	} else if (track.kind === Track.Kind.Audio) {
		if (remoteParticipant && track.source === Track.Source.Microphone) {
			remoteParticipant.audioTrack = track;
		} else if (remoteParticipant && track.source === Track.Source.ScreenShareAudio) {
			[...remoteParticipants.values()]
				.forEach(participant => participant.screenAudioTrack = undefined)
			remoteParticipant.screenAudioTrack = track;
		}
	}
}

function handleTrackUnsubscribed(
	track: RemoteTrack,
	publication: RemoteTrackPublication,
	participant: RemoteParticipant,
): void {
	const remoteParticipant = remoteParticipants.get(participant.identity);
	if (!remoteParticipant) return;
	if (track.source === Track.Source.Camera) {
		remoteParticipant.videoTrack = undefined;
	} else if (track.source === Track.Source.ScreenShare) {
		remoteParticipant.screenVideoTrack = undefined;
	} else if (track.source === Track.Source.Microphone) {
		remoteParticipant.audioTrack = undefined;
	} else if (track.source === Track.Source.ScreenShareAudio) {
		remoteParticipant.screenAudioTrack = undefined;
	}

}

function handleLocalTrackPublished(
	publication: LocalTrackPublication,
): void {
	if (publication.track?.kind === Track.Kind.Video) {
		if (publication.track.source === Track.Source.Camera) {
			localParticipant.videoTrack = publication.track;
		} else if (publication.track.source === Track.Source.ScreenShare) {
			localParticipant.screenVideoTrack = publication.track;

			[...remoteParticipants.values()]
				.forEach(participant => participant.screenVideoTrack = undefined)
		}
	} else if (publication.track?.kind === Track.Kind.Audio) {
		if (publication.track.source === Track.Source.Microphone) {
			localParticipant.audioTrack = publication.track;
		} else if (publication.track.source === Track.Source.ScreenShareAudio) {
			localParticipant.screenAudioTrack = publication.track;

			[...remoteParticipants.values()]
				.forEach(participant => participant.screenAudioTrack = undefined)
		}
	}
}

function handleLocalTrackUnpublished(
	publication: LocalTrackPublication,
): void {
	if (publication.track?.source === Track.Source.Camera) {
		localParticipant.videoTrack = undefined;
	} else if (publication.track?.source === Track.Source.ScreenShare) {
		localParticipant.screenVideoTrack = undefined;
	} else if (publication.track?.source === Track.Source.Microphone) {
		localParticipant.audioTrack = undefined;
	} else if (publication.track?.source === Track.Source.ScreenShareAudio) {
		localParticipant.screenAudioTrack = undefined;
	}
}

function handleActiveSpeakerChange(
	participants: Participant[]
): void {
	const activeSpeakingParticipantsIdentities: string[] = participants.map((participant: Participant): string => participant.identity);

	[...room.remoteParticipants.keys()]
		.concat(room.localParticipant.identity)
		.forEach((identity: string) => {
			const author: User|undefined = userFromRoomIdentity(identity);
			if (author && activeSpeakingParticipantsIdentities.includes(identity)) {
				author.isSpeaking = true;
			} else if (author) {
				author.isSpeaking = false;
			}
		});
}

function handleChatMessage(message: ChatMessage, participant?: Participant): void {
	const author: User|undefined = userFromRoomIdentity(participant?.identity ?? '')
	if (author) {
		messages.value.push({
			timestamp: message.timestamp,
			message: message.message,
			userId: author.id,
		});
	}
}

function handleTrackMuted(publication: TrackPublication, participant: Participant): void {
	const remoteParticipant = remoteParticipants.get(participant.identity)
	if (remoteParticipant) {
		if (publication.track?.source === Track.Source.Camera) {
			remoteParticipant.videoTrackMuted = true;
		} else if (publication.track?.source === Track.Source.Microphone) {
			remoteParticipant.audioTrackMuted = true;
		}
	}
}

function handleTrackUnMuted(publication: TrackPublication, participant: Participant): void {
	const remoteParticipant = remoteParticipants.get(participant.identity)
	if (remoteParticipant) {
		if (publication.track?.source === Track.Source.Camera) {
			remoteParticipant.videoTrackMuted = false;
		} else if (publication.track?.source === Track.Source.Microphone) {
			remoteParticipant.audioTrackMuted = false;
		}
	}
}

function userFromRoomIdentity(identity: string): User|undefined {
	const isLocalParticipantAuthor = identity === room.localParticipant.identity;

	return isLocalParticipantAuthor
		? localParticipant.user
		: remoteParticipants.get(identity)?.user;
}

function handleParticipantAttributesChanged(changedAttributes: Record<string, string>, participant: RemoteParticipant | LocalParticipant): void {
	if (participant.identity !== room.localParticipant.identity) {
		remoteParticipants.set(participant.identity, {
			user: {
				name: participant.attributes.name,
				id: Number(participant.attributes.id),
			}
		});
	}
}

function handleParticipantDisconnected(participant: RemoteParticipant): void {
	remoteParticipants.delete(participant.identity);
}

async function updateRoomLocalParticipant(): Promise<void> {
	await room.localParticipant.setAttributes({
		name: currentUser.user!.name,
		id: String(currentUser.user!.id)
	});
	if (Object.values(settings.mediaPreferences).every(preference => preference)) {
		await room.localParticipant.enableCameraAndMicrophone();
	} else {
		await room.localParticipant.setCameraEnabled(settings.mediaPreferences.video);
		await room.localParticipant.setMicrophoneEnabled(settings.mediaPreferences.audio);
	}
}

async function onMessageCreated(body: string): Promise<void> {
	await room.localParticipant.sendChatMessage(body);
}

function onSidebarClose(): void {
	isSidebarVisible.value = false;
}

function onCameraControlClick(): void {
	updateMedia(Track.Kind.Video);
}

function onAudioControlClick(): void {
	updateMedia(Track.Kind.Audio);
}

async function updateMedia(kind: Track.Kind.Video|Track.Kind.Audio): Promise<void> {
	const track = kind === Track.Kind.Video ? localParticipant.videoTrack : localParticipant.audioTrack;
	if (!track) {
		try {
			await (kind === Track.Kind.Video
				? room.localParticipant.setCameraEnabled(true)
				: room.localParticipant.setMicrophoneEnabled(true))
		} catch {
			toast.add({ severity: 'error', summary: 'Staging page', detail: 'Please enable browser access to ' + kind, life: 3000 });
			settings.mediaPreferences[kind] = false;
		}
	} else {
		if (track.isMuted) {
			await track.unmute();
			if (kind === Track.Kind.Video) {
				localParticipant.videoTrackMuted = false;
			} else {
				localParticipant.audioTrackMuted = false;
			}
		} else {
			await track.mute();
			if (kind === Track.Kind.Video) {
				localParticipant.videoTrackMuted = true;
			} else {
				localParticipant.audioTrackMuted = true;
			}
		}
	}
}

function onRecordControlClick(): void {

}

function onMessageControlClick(): void {
	if (!isSidebarVisible.value) {
		isSidebarVisible.value = true;
		selectedTabId.value = TabNames.Chat;
	} else if (selectedTabId.value !== TabNames.Chat) {
		selectedTabId.value = TabNames.Chat;
	} else {
		isSidebarVisible.value = false;
	}
}

function onParticipantControlClick(): void {
	if (!isSidebarVisible.value) {
		isSidebarVisible.value = true;
		selectedTabId.value = TabNames.Participants;
	} else if (selectedTabId.value !== TabNames.Participants) {
		selectedTabId.value = TabNames.Participants;
	} else {
		isSidebarVisible.value = false;
	}
}

function onScreenShareControlClick(): void {
	room.localParticipant.setScreenShareEnabled(!room.localParticipant.isScreenShareEnabled);
}

function onLeaveControlClick(): void {
	router.push({path: '/'})
}

onMounted(async (): Promise<void> => {
	const token: string = await mediaToken();
	await connectToRoom(token);

	setupCurrentRoomState();
	attachRoomEventHandlers();

	await updateRoomLocalParticipant();
});
</script>

<template>
	<main>
		<div class="row">
			<VideoWindowLayout
				:participants="participantsWithTracks"
			></VideoWindowLayout>
			<section class="sidebar" v-if="isSidebarVisible">
				<MeetingSidebar
					:messages="messages"
					:participants="participantsWithTracks"
					v-model="selectedTabId"
					@message-created="onMessageCreated"
					@close="onSidebarClose"
				></MeetingSidebar>
			</section>
		</div>
		<Toolbar class="controls">
			<template #center>
				<Button @click="onAudioControlClick" icon="pi pi-microphone" severity="secondary" rounded aria-label="Filter"/>
				<Button @click="onCameraControlClick" icon="pi pi-camera" severity="secondary" rounded aria-label="Filter"/>
				<Button @click="onRecordControlClick" icon="pi pi-video" severity="secondary" rounded aria-label="Filter"/>
				<Button @click="onMessageControlClick" icon="pi pi-th-large" severity="secondary" rounded aria-label="Filter"/>
				<Button @click="onParticipantControlClick" icon="pi pi-users" severity="secondary" rounded aria-label="Filter"/>
				<Button @click="onScreenShareControlClick" icon="pi pi-desktop" severity="secondary" rounded aria-label="Filter"/>
			</template>
			<template #end>
				<Button @click="onLeaveControlClick" severity="danger" label="Leave"/>
			</template>
		</Toolbar>
	</main>
</template>

<style lang="scss" scoped>
main {
	height: 100%;
	display: flex;
	flex-direction: column;
	justify-content: space-between;

	.row {
		display: flex;
		padding-bottom: 74px;
		height: 100vh;

		section.sidebar {
			width: 25%;
		}
	}

	.controls {
		position: fixed;
		width: 100%;
		bottom: 0;

		:deep(.p-button-icon-only) {
			margin: 4px;
		}
	}
}
</style>
