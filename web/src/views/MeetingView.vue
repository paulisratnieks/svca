<script setup lang="ts">
import {computed, type ComputedRef, onMounted, onUnmounted, reactive, ref, type Ref} from 'vue';
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
import {useAuth} from '@/stores/auth';
import {useAxios} from '@/composables/axios';
import type {User} from '@/types/user';
import type {Message} from '@/types/message';
import Toolbar from 'primevue/toolbar';
import ProgressSpinner from 'primevue/progressspinner';
import Button from 'primevue/button';
import MeetingSidebar from '@/components/MeetingSidebar.vue';
import VideoWindowLayout from '@/components/VideoWindowLayout.vue';
import router from '@/router';
import {useSettingsStore} from '@/stores/settings';
import {useToast} from 'primevue/usetoast';
import type {LocalUserWithTracks} from '@/types/local-user-with-tracks';
import {TabNames} from '@/types/tab-names';
import type {UserWithTracks} from '@/types/user-with-tracks';
import MicrophoneIcon from '@/components/icons/MicrophoneIcon.vue';
import CameraIcon from '@/components/icons/CameraIcon.vue';
import ScreenShareIcon from '@/components/icons/ScreenShareIcon.vue';
import RecordIcon from '@/components/icons/RecordIcon.vue';
import ChatIcon from '@/components/icons/ChatIcon.vue';
import UserIcon from '@/components/icons/UserIcon.vue';
import {useRecordingsStore} from '@/stores/recordings';
import axios, {HttpStatusCode} from 'axios';

const route = useRoute();
const toast = useToast();
const settings = useSettingsStore();
const recordings = useRecordingsStore();
const auth = useAuth();

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
const loading: Ref<boolean> = ref(true);

const meetingId: Ref<string> = computed((): string => {
	return route.params.meetingId as string;
});

const isRecording: Ref<boolean> = computed((): boolean => {
	return recordings.statuses[meetingId.value];
});

const localParticipant: LocalUserWithTracks = reactive({user: auth.user!});
const remoteParticipants: Map<string, UserWithTracks> = reactive(new Map());

const participantsWithTracks: ComputedRef<UserWithTracks[]> = computed(() => {
	return [...remoteParticipants.values()]
		.concat(localParticipant)
		.filter(participant => participant.user);
});

async function mediaToken(): Promise<string> {
	let token = '';
	try {
		token = (await useAxios().get('token', {
				params: {room_name: meetingId.value}
			}
		)).data.data;
	} catch {
		router.push({path: '/meetings-start'});
		toast.add({ severity: 'error', summary: 'Meeting page', detail: 'Meeting join failed', life: 3000 });
	}

	return token;
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

	if (room.isRecording) {
		handleRecordingStatusChanged(room.isRecording)
	}
}

function attachRoomEventHandlers(): void {
	room
		.on(RoomEvent.TrackSubscribed, handleTrackSubscribed)
		.on(RoomEvent.TrackUnsubscribed, handleTrackUnsubscribed)
		.on(RoomEvent.LocalTrackPublished, handleLocalTrackPublished)
		.on(RoomEvent.LocalTrackUnpublished, handleLocalTrackUnpublished)
		.on(RoomEvent.ActiveSpeakersChanged, handleActiveSpeakerChange)
		.on(RoomEvent.ChatMessage, handleChatMessage)
		.on(RoomEvent.TrackMuted, handleTrackMuted)
		.on(RoomEvent.TrackUnmuted, handleTrackUnMuted)
		.on(RoomEvent.RecordingStatusChanged, handleRecordingStatusChanged)
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

function handleRecordingStatusChanged(recordingStatus: boolean): void {
	if (recordingStatus) {
		toast.add({ severity: 'info', summary: 'Meeting page', detail: 'Recording has started', life: 3000 });
	} else {
		toast.add({ severity: 'info', summary: 'Meeting page', detail: 'Recording has stopped', life: 3000 });
	}
	recordings.statuses[meetingId.value] = recordingStatus;
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
		name: auth.user!.name,
		id: String(auth.user!.id)
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

async function onRecordControlClick(): Promise<void> {
	if (isRecording.value) {
		const recordingIds = recordings.ids;
		const recordingId = recordingIds[meetingId.value];
		try {
			await useAxios().patch(`recordings/${recordingId}/stop` , {
				room_name: meetingId.value
			});
			delete recordingIds[meetingId.value];
		} catch (error: unknown) {
			if (axios.isAxiosError(error) && error.status === HttpStatusCode.NotFound) {
				toast.add({ severity: 'error', summary: 'Meeting page', detail: 'You are not authorized to cancel the recordings. Only the recording author can stop it', life: 3000 });
			}
		}
	} else {
		const recordingId = (await useAxios().post('recordings', {
			room_name: meetingId.value
		})).data.id;
		const recordingIds = recordings.ids;
		recordingIds[meetingId.value] = recordingId;
	}
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

function onMeetingIdButtonClick(): void {
	navigator.clipboard.writeText(meetingId.value)
		.then(() => {
			toast.add({ severity: 'info', summary: 'Meeting page', detail: 'Meeting ID successfully copied to clipboard', life: 3000 });
		})
		.catch(() => {
			toast.add({ severity: 'error', summary: 'Meeting page', detail: 'Meeting ID copy to clipboard failed', life: 3000 });
		});
}

function onLeaveControlClick(): void {
	router.push({path: '/meetings-start'})
}

onMounted(async (): Promise<void> => {
	const token: string = await mediaToken();
	await connectToRoom(token);

	setupCurrentRoomState();
	attachRoomEventHandlers();

	await updateRoomLocalParticipant();
	loading.value = false;
});

onUnmounted(() => {
	room.disconnect();
});
</script>

<template>
	<main>
		<div class="row" :class="{loading: loading}">
			<template v-if="loading">
				<ProgressSpinner></ProgressSpinner>
			</template>
			<template v-else>
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
			</template>
		</div>
		<Toolbar class="controls">
			<template #start>
				<Button @click="onMeetingIdButtonClick" severity="info" label="Copy ID"/>
			</template>
			<template #center>
				<Button @click="onAudioControlClick" icon="pi pi-microphone" severity="secondary" rounded aria-label="Filter">
					<template #icon><MicrophoneIcon :is-off="localParticipant.audioTrackMuted ?? false"></MicrophoneIcon></template>
				</Button>
				<Button @click="onCameraControlClick" icon="pi pi-camera" severity="secondary" rounded aria-label="Filter">
					<template #icon><CameraIcon :is-off="localParticipant.videoTrackMuted ?? false"></CameraIcon></template>
				</Button>
				<Button @click="onMessageControlClick" icon="pi pi-th-large" severity="secondary" rounded aria-label="Filter">
					<template #icon><ChatIcon /></template>
				</Button>
				<Button @click="onParticipantControlClick" icon="pi pi-users" severity="secondary" rounded aria-label="Filter">
					<template #icon><UserIcon /></template>
				</Button>
				<Button @click="onScreenShareControlClick" icon="pi pi-desktop" severity="secondary" rounded aria-label="Filter">
					<template #icon><ScreenShareIcon :is-off="localParticipant.screenVideoTrack === undefined"></ScreenShareIcon></template>
				</Button>
				<Button @click="onRecordControlClick" icon="pi pi-video" severity="secondary" rounded aria-label="Filter">
					<template #icon><RecordIcon :is-off="!isRecording"></RecordIcon></template>
				</Button>
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

		&.loading {
			align-items: center;
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
