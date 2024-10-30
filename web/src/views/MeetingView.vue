<script setup lang="ts">
import {computed, onMounted, ref, type Ref, useTemplateRef} from 'vue';
import {useRoute} from 'vue-router';
import {
	type ChatMessage,
	LocalTrackPublication, Participant, RemoteParticipant,
	RemoteTrack, RemoteTrackPublication,
	Room,
	RoomEvent, Track,
	VideoPresets
} from 'livekit-client';
import {useCurrentUserStore} from '@/stores/current-user';
import {useAxios} from '@/composables/axios';

const route = useRoute();
const currentUser = useCurrentUserStore();

const messageBody: Ref<string> = ref('');
const meetingId: Ref<string> = computed((): string => {
	return route.params.meetingId as string;
});

const subscribedRemoteParticipants: Ref<Set<string>> = ref(new Set);

const localVideo: Ref<HTMLVideoElement | null> = useTemplateRef('video');
// @ts-ignore
const remoteVideos: Ref<HTMLVideoElement[] | null> = useTemplateRef('remoteVideos');

const room = new Room({
	adaptiveStream: true,
	dynacast: true,
	videoCaptureDefaults: {
		resolution: VideoPresets.h720.resolution,
	},
});

onMounted(async (): Promise<void> => {
	const response = await useAxios().get('token', {params: {room_name: meetingId.value}})
	const token = response.data.data;

	await room.connect(import.meta.env.VITE_LIVEKIT_API_URL, token);
	room
		.on(RoomEvent.TrackSubscribed, handleTrackSubscribed)
		.on(RoomEvent.TrackUnsubscribed, handleTrackUnsubscribedSubscribed)
		.on(RoomEvent.LocalTrackPublished, handleLocalTrackPublished)
		.on(RoomEvent.LocalTrackUnpublished, handleLocalTrackUnpublished)
		.on(RoomEvent.ActiveSpeakersChanged, handleActiveSpeakerChange)
		.on(RoomEvent.ChatMessage, handleChatMessage);

	room.remoteParticipants.forEach((participant) => {
		participant.trackPublications.forEach((publication) => {
			if (publication.track) {
				handleTrackSubscribed(publication.track, publication, participant);
			}
		});
	});

	await room.localParticipant.setAttributes({
		name: currentUser.user!.name,
	});
	await room.localParticipant.enableCameraAndMicrophone();
});

function handleTrackSubscribed(
	track: RemoteTrack,
	publication: RemoteTrackPublication,
	participant: RemoteParticipant
): void {
	subscribedRemoteParticipants.value.add(participant.identity);
	setTimeout(() => {
		const video = remoteVideos.value?.find((video: HTMLVideoElement): boolean => {
			const id = video.getAttribute('data-id');

			return id === participant.identity;
		});
		if (video) {
			track.attach(video);
		}
	})
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
		publication.track.attach(localVideo.value!);
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
	console.log(participants);
}

function handleChatMessage(message: ChatMessage): void {
	console.log(message);
}

async function onCreateMessageClick(): Promise<void> {
	await room.localParticipant.sendChatMessage(messageBody.value);
	messageBody.value = '';
}

async function onScreenShareClick(): Promise<void> {
	await room.localParticipant.setScreenShareEnabled(true);
}

</script>

<template>
	<main>
		<section class="users">
			<video ref="video" autoplay playsinline></video>
			<video v-for="(participant, index) in subscribedRemoteParticipants"
				ref="remoteVideos"
				:key="index"
				:data-id="participant"
				autoplay playsinline muted>
			</video>
		</section>
		<input v-model="messageBody">
		<button @click="onCreateMessageClick">Create message</button>
		<button @click="onScreenShareClick">Share screen</button>
	</main>
</template>

<style lang="scss" scoped>
</style>
