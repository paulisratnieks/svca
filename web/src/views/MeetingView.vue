<script setup lang="ts">
import {computed, onMounted, ref, type Ref} from 'vue';
import {useRoute} from 'vue-router';
import VideoWindow from '@/components/VideoWindow.vue';
import type {User} from '@/types/user';
import {useCurrentUserStore} from '@/stores/current-user';
import {useRTCConnection} from '@/composables/rtc-connection';
import {ChannelEvents} from '@/enums/channel-events';
import type {PresenceChannel} from 'laravel-echo';
import MeetingControls from '@/components/MeetingControls.vue';

const route = useRoute();
const currentUser = useCurrentUserStore();
const {addConnectionEventListeners, createPeerConnection, addTracks, createOffer, createAnswer, addIceCandidate, setRemoteDescription} = useRTCConnection();

const userMediaConstraints: Record<string, boolean> = {
	audio: true,
	video: true
};

const messageBody: Ref<string> = ref('');
const users: Ref<User[]> = ref([]);
const localStream: Ref<MediaStream|null>  = ref(null);
const remoteStream: Ref<MediaStream|null>  = ref(null);
const channel: Ref<PresenceChannel|null> = ref(null);

const meetingId: Ref<string> = computed((): string => route.params.meetingId as string);
const otherUsers: Ref<User> = computed((): User => {
	return users.value.filter((user: User): boolean => user.id !== currentUser.user?.id)[0]!;
});

function onCreateMessageClick(): void {
	window.axios.post(
		import.meta.env.VITE_API_URL + '/meetings/' + meetingId.value + '/messages',
		{
			body: messageBody.value,
			user_id: currentUser.user!.id
		}
	);
}

function onIceCandidate(event: RTCPeerConnectionIceEvent): void {
	channel.value!.whisper('candidate', event.candidate)
}

function onReceiveRemoteStream(event: RTCTrackEvent): void {
	remoteStream.value = event.streams[0];
}

function getUserMedia(): Promise<void> {
	return navigator.mediaDevices.getUserMedia(userMediaConstraints)
		.then((mediaStream: MediaStream) => {
			localStream.value = mediaStream;
		});
}

onMounted(async (): Promise<void> => {
	createPeerConnection();
	addConnectionEventListeners({
		onTrack: onReceiveRemoteStream,
		onIceCandidate: onIceCandidate
	});
	await getUserMedia();
	addTracks(localStream.value!);

	channel.value = window.Echo.join(`meetings.${meetingId.value}`);

	channel.value
		.here((currentUser: User[]): void => {
			users.value = currentUser;
		})
		.joining((user: User): void => {
			users.value.push(user);

			createOffer()
				.then((offer: RTCSessionDescriptionInit) => channel.value!.whisper(ChannelEvents.Offer, offer))
		})
		.leaving((user: User): void => {
			users.value = users.value.filter((currentUser: User): boolean => currentUser.id !== user.id);

			remoteStream.value = null;
		})
		.listenForWhisper(ChannelEvents.Offer, (offer: RTCSessionDescriptionInit): void => {
			createAnswer(offer)
				.then((answer: RTCSessionDescriptionInit): void => {
					channel.value!.whisper(ChannelEvents.Answer, answer);
				});
		})
		.listenForWhisper(ChannelEvents.Answer, (answer: RTCSessionDescriptionInit): void => {
			setRemoteDescription(answer);
		})
		.listenForWhisper(ChannelEvents.Candidate, (candidate: RTCIceCandidate): void => {
			addIceCandidate(candidate);
		})
		// .listen('MessageCreated', (event: Message): void => {
		// });
});

</script>

<template>
	<main>
		<video-window v-if="localStream"
			:user="currentUser.user!"
			:stream="localStream"
		></video-window>
		<video-window v-if="remoteStream"
			:user="otherUsers"
			:stream="remoteStream"
		></video-window>
		<ul v-if="users.length">
			<li v-for="(user, index) in users" :key="index">{{ user.name }}</li>
		</ul>
		<meeting-controls
			v-if="localStream"
			:stream="localStream"
		></meeting-controls>
		<input v-model="messageBody">
		<button @click="onCreateMessageClick">Create message</button>
	</main>
</template>
