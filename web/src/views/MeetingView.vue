<script setup lang="ts">
import {computed, type ComputedRef, onMounted, reactive, ref, type Ref} from 'vue';
import {useRoute} from 'vue-router';
import {
	type ChatMessage,
	LocalParticipant, type LocalTrack,
	LocalTrackPublication,
	Participant,
	RemoteParticipant,
	RemoteTrack,
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

const route = useRoute();
const currentUser = useCurrentUserStore();

const room = new Room({
	adaptiveStream: true,
	dynacast: true,
	videoCaptureDefaults: {
		resolution: VideoPresets.h720.resolution,
	},
});

const messages: Ref<Message[]> = ref([]);
const isSidebarVisible: Ref<boolean> = ref(false);

const meetingId: Ref<string> = computed((): string => {
	return route.params.meetingId as string;
});

const localParticipant: { audioTrack?: LocalTrack, videoTrack?: LocalTrack, user?: User } = reactive({});
const remoteParticipants: Ref<Map<string, { audioTrack?: Track, videoTrack?: Track, user?: User }>> = ref(new Map);

const participantsWithTracks: ComputedRef<{ audioTrack?: Track, videoTrack?: Track, user: User }[]> = computed(() => {
	return [...remoteParticipants.value]
		.map(([_, participant]) => participant)
		.concat(localParticipant)
		.filter(participant => participant.user !== undefined) as { audioTrack?: Track, videoTrack?: Track, user: User }[];
});

const participants: ComputedRef<User[]> = computed(() => {
	return [
		localParticipant.user!,
		...([...remoteParticipants.value]
			.map(([_, participant]) => participant)
			.filter(participant => participant.user !== undefined)
			.map((participant: { audioTrack?: Track, videoTrack?: Track, user?: User }) => participant.user!))
	];
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
	localParticipant.user = currentUser.user!;

	room.remoteParticipants.forEach((participant: Participant): void => {
		const remoteParticipant: { user?: User } = {};
		if (participant.attributes.name) {
			remoteParticipant.user = {
				name: participant.attributes.name,
				id: Number(participant.attributes.id)
			};
		}
		remoteParticipants.value.set(participant.identity, remoteParticipant);

		participant.trackPublications.forEach((publication) => {
			if (publication.track) {
				handleTrackSubscribed(publication.track, publication, participant);
			}
		});
	});
}

function attachRoomEventHandlers() {
	room
		.on(RoomEvent.TrackSubscribed, handleTrackSubscribed)
		.on(RoomEvent.TrackUnsubscribed, handleTrackUnsubscribedSubscribed)
		.on(RoomEvent.LocalTrackPublished, handleLocalTrackPublished)
		.on(RoomEvent.LocalTrackUnpublished, handleLocalTrackUnpublished)
		.on(RoomEvent.ActiveSpeakersChanged, handleActiveSpeakerChange)
		.on(RoomEvent.ChatMessage, handleChatMessage)
		.on(RoomEvent.ParticipantConnected, handleParticipantConnected)
		.on(RoomEvent.ParticipantAttributesChanged, handleParticipantAttributesChanged);
}

function handleTrackSubscribed(
	track: Track<Track.Kind>,
	publication: TrackPublication,
	participant: Participant
): void {
	if (track.kind === Track.Kind.Video) {
		const remoteParticipant = remoteParticipants.value.get(participant.identity);
		remoteParticipant!.videoTrack = track;
	} else if (track.kind === Track.Kind.Audio) {
		const remoteParticipant = remoteParticipants.value.get(participant.identity);
		remoteParticipant!.audioTrack = track;
	}
}

function handleTrackUnsubscribedSubscribed(
	track: RemoteTrack,
): void {
	track.detach();
}

function handleLocalTrackPublished(
	publication: LocalTrackPublication,
): void {
	if (publication.track?.kind === Track.Kind.Video) {
		localParticipant.videoTrack = publication.track;
	} else if (publication.track?.kind === Track.Kind.Audio) {
		localParticipant.audioTrack = publication.track;
	}
}

function handleLocalTrackUnpublished(
	publication: LocalTrackPublication,
): void {
	publication.track?.detach();
}

function handleActiveSpeakerChange(
	participants: Participant[]
): void {
	const activeSpeakingParticipantsIdentities: string[] = participants.map((participant: Participant): string => participant.identity);

	[...room.remoteParticipants.keys()]
		.concat(room.localParticipant.identity)
		.forEach((identity: string) => {
			const author: User|undefined = userFromRoomIdentity(identity ?? '');
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

function userFromRoomIdentity(identity: string): User|undefined {
	const isLocalParticipantAuthor = identity === room.localParticipant.identity;

	return isLocalParticipantAuthor
		? localParticipant.user
		: remoteParticipants.value.get(identity)?.user;
}

function handleParticipantConnected(participant: RemoteParticipant): void {
	remoteParticipants.value.set(participant.identity, {});
}

function handleParticipantAttributesChanged(changedAttributes: Record<string, string>, participant: RemoteParticipant | LocalParticipant): void {
	if (participant.identity !== room.localParticipant.identity) {
		const remoteParticipant = remoteParticipants.value.get(participant.identity);
		remoteParticipant!.user = {
			name: participant.attributes.name,
			id: Number(participant.attributes.id),
		};
	}
}

async function updateRoomLocalParticipant(): Promise<void> {
	await room.localParticipant.setAttributes({
		name: currentUser.user!.name,
		id: String(currentUser.user!.id)
	});
	await room.localParticipant.enableCameraAndMicrophone();
}

async function onMessageCreated(body: string): Promise<void> {
	await room.localParticipant.sendChatMessage(body);
}

function onSidebarClose(): void {
	isSidebarVisible.value = false;
}

function onAudioControlClick(): void {
	if (!localParticipant.audioTrack) return;

	if (localParticipant.audioTrack?.isMuted) {
		localParticipant.audioTrack.unmute()
	} else {
		localParticipant.audioTrack.mute()
	}
}

function onCameraControlClick(): void {
	if (!localParticipant.videoTrack) return;

	if (localParticipant.videoTrack?.isMuted) {
		localParticipant.videoTrack.unmute()
	} else {
		localParticipant.videoTrack.mute()
	}
}

function onRecordControlClick(): void {

}

function onMessageControlClick(): void {
	isSidebarVisible.value = !isSidebarVisible.value;
}

function onParticipantControlClick(): void {

}

function onScreenShareControlClick(): void {
	room.localParticipant.setScreenShareEnabled(true);
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
				v-if="localParticipant.user"
				:participants="participantsWithTracks"
			></VideoWindowLayout>
			<section class="sidebar" v-if="isSidebarVisible">
				<MeetingSidebar
					:messages="messages"
					:participants="participants"
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
