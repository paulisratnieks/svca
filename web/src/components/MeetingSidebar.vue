<script setup lang="ts">
import {computed, type ComputedRef, nextTick, ref, type Ref, useTemplateRef, watch} from 'vue';
import type {User} from '@/types/user';
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import ScrollPanel from 'primevue/scrollpanel';
import type {Message} from '@/types/message';
import {useAuth} from '@/stores/auth';
import Button from 'primevue/button';
import ChatMessage from '@/components/ChatMessage.vue';
import type {Track} from 'livekit-client';
import {TabNames} from '@/types/tab-names';
import type {UserWithTracks} from '@/types/user-with-tracks';
import MicrophoneIcon from '@/components/icons/MicrophoneIcon.vue';
import CameraIcon from '@/components/icons/CameraIcon.vue';
import ChatIcon from '@/components/icons/ChatIcon.vue';
import UserIcon from '@/components/icons/UserIcon.vue';

interface MessageProps {
	userId: number,
	username: string,
	message: string,
	timestamp: number,
	isAuthenticatedPersonAuthor: boolean,
	showDate: boolean,
	showAuthor: boolean,
}

const selectedTabIdModel = defineModel();
const auth = useAuth();

const props = defineProps<{
	messages: Message[],
	participants: UserWithTracks[],
}>();
const emit = defineEmits<{
	(e: 'messageCreated', body: string): void
	(e: 'close'): void
}>();

const messageBody: Ref<string> = ref('');
const participantsSearch: Ref<string> = ref('');

const bottomContent: Ref<HTMLDivElement | null> = useTemplateRef('bottom-content');

const participants: ComputedRef<User[]> = computed(() => {
	return props.participants
		.map((participant): User => participant.user);
});

const messages: ComputedRef<MessageProps[]> = computed(() => {
	return props.messages
		.reduce((accumulator: MessageProps[], message: Message) => {
			const previousMessage: Message | undefined = accumulator[accumulator.length - 1];
			const isPreviousMessageSameMinute = previousMessage && isSameMinute(previousMessage.timestamp, message.timestamp);
			const isPreviousMessageAuthorSame = previousMessage && previousMessage.userId === message.userId;

			const transformedMessage = {
				userId: message.userId,
				username: participants.value.find((participant: User): boolean => participant.id === message.userId)!.name,
				message: message.message,
				timestamp: message.timestamp,
				isAuthenticatedPersonAuthor: message.userId === auth.user!.id,
				showDate: !isPreviousMessageSameMinute,
				showAuthor: !isPreviousMessageAuthorSame,
			}
			accumulator.push(transformedMessage);

			return accumulator;
		}, []);
});

const searchMatchingParticipants: ComputedRef<UserWithTracks[]> = computed(() => {
	return props.participants
		.filter((participant: { audioTrack?: Track, videoTrack?: Track, user: User }): boolean =>
			participantsSearch.value === ''
			|| participant.user.name.toLowerCase().includes(participantsSearch.value.toLowerCase())
		);
});

function isSameMinute(timestamp1: number, timestamp2: number): boolean {
	const date1 = new Date(timestamp1);
	const date2 = new Date(timestamp2);

	return date1.getFullYear() === date2.getFullYear() &&
		date1.getMonth() === date2.getMonth() &&
		date1.getDate() === date2.getDate() &&
		date1.getHours() === date2.getHours() &&
		date1.getMinutes() === date2.getMinutes();
}

function isTabActive(tab: TabNames): boolean {
	return selectedTabIdModel.value === tab;
}

function scrollToBottom(): void {
	nextTick(() => {
		bottomContent.value?.scrollIntoView({block: 'end'});
	});
}

function onEnterPressMessageInput(): void {
	if (messageBody.value.length) {
		emit('messageCreated', messageBody.value);
		messageBody.value = '';
	}
}

function onTabChange(tab: TabNames): void {
	selectedTabIdModel.value = tab;
}

function onCloseClick(): void {
	emit('close');
}

watch(
	props.messages,
	() => {
		scrollToBottom();
	}
)

</script>

<template>
	<section class="meeting-sidebar">
		<div class="tab-list">
			<div class="tab-select">
				<Button @click="onTabChange(TabNames.Chat)"
					:severity="isTabActive(TabNames.Chat) ? 'success' : 'secondary'"
					:class="{'active': isTabActive(TabNames.Chat)}"
					label="Chat"
					class="chat"
					size="small"
					variant="text">
					<template #icon><ChatIcon /></template>
				</Button>
				<Button @click="onTabChange(TabNames.Participants)"
					:severity="selectedTabIdModel === TabNames.Participants ? 'success' : 'secondary'"
					:class="{'active': isTabActive(TabNames.Participants)}"
					label="Participants"
					class="participants"
					size="small"
					variant="text">
					<template #icon><UserIcon /></template>
				</Button>
				<Button @click="onCloseClick" class="close" icon="pi pi-times" size="small" severity="secondary" variant="text" rounded />
			</div>
			<div class="tab-content">
				<div class="tab-panel" v-if="selectedTabIdModel === TabNames.Chat">
					<ScrollPanel>
						<ChatMessage
							v-for="(message, index) in messages"
							:key="index"
							:username="message.username"
							:message="message.message"
							:timestamp="message.timestamp"
							:is-authenticated-person-author="message.isAuthenticatedPersonAuthor"
							:show-date="message.showDate"
							:show-author="message.showAuthor"
						></ChatMessage>
						<div ref="bottom-content"></div>
					</ScrollPanel>
					<FloatLabel variant="on">
						<InputText id="message" v-model="messageBody" type="text" @keydown.enter="onEnterPressMessageInput"></InputText>
						<label for="message">Message</label>
					</FloatLabel>
				</div>
				<div class="tab-panel" v-if="selectedTabIdModel === TabNames.Participants">
					<FloatLabel variant="on">
						<InputText id="search" v-model="participantsSearch" type="text"></InputText>
						<label for="search">Search</label>
					</FloatLabel>
					<ScrollPanel>
						<div class="participants-list">
							<div
								v-for="(participant, index) in searchMatchingParticipants"
								class="participant-block"
								:key="index"
							>
								<span>{{ participant.user.name }}</span>
								<span class="media">
									<MicrophoneIcon :is-off="participant.audioTrackMuted ?? false"></MicrophoneIcon>
									<CameraIcon :is-off="participant.videoTrackMuted ?? false"></CameraIcon>
								</span>
							</div>
						</div>
					</ScrollPanel>
				</div>
			</div>
		</div>
	</section>
</template>

<style lang="scss" scoped>
.meeting-sidebar {
	padding: 12px;
	background: var(--p-content-background);
	border: 1px solid var(--p-content-border-color);
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	height: calc(100vh - 74px);
	gap: 12px;

	.tab-list {
		.tab-select {
			padding-bottom: 8px;
			justify-content: space-between;
			display: flex;
			gap: 8px;
			align-items: center;

			.active {
				border-bottom-left-radius: 0;
				border-bottom-right-radius: 0;
				border-bottom: 1px solid var(--p-primary-color);

				img {
					filter: brightness(0) saturate(100%) invert(99%) sepia(70%) saturate(1755%) hue-rotate(76deg) brightness(86%) contrast(90%);
				}
			}

			.close {
				min-height: 40px;
			}
		}
	}

	.tab-content {
		.tab-panel {
			height: calc(100vh - 94px);

			:deep(.p-scrollpanel) {
				height: calc(100% - 94px);
			}

			:deep(.p-scrollpanel-content) {
				padding-bottom: 0;
			}

			.bottom-content {
				display: none;
			}

			input {
				width: 100%;
			}

			.participants-list {
				margin-top: 8px;
				display: flex;
				flex-direction: column;
				gap: 8px;


				.participant-block {
					padding: 12px;
					width: 100%;
					border-radius: 6px;
					background-color: var(--p-surface-700);
					display: flex;
					justify-content: space-between;

					.media {
						display: flex;
						gap: 8px;
					}
				}

			}
		}
	}
}
</style>
