<script setup lang="ts">
import {computed, type ComputedRef, nextTick, ref, type Ref, useTemplateRef, watch} from 'vue';
import type {User} from '@/types/user';
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import ScrollPanel from 'primevue/scrollpanel';
import type {Message as ChatMessage} from '@/types/message';
import {useCurrentUserStore} from '@/stores/current-user';
import Button from 'primevue/button';

interface MessageProps {
	userId: number,
	username: string,
	message: string,
	timestamp: number,
	isAuthenticatedPersonAuthor: boolean,
	showDate: boolean,
	showAuthor: boolean
}

const currentUser = useCurrentUserStore();

const props = defineProps<{
	messages: ChatMessage[],
	participants: User[],
}>();
const emit = defineEmits<{
	(e: 'messageCreated', body: string): void
	(e: 'close'): void
}>();

const messageBody: Ref<string> = ref('');

const bottomContent: Ref<HTMLDivElement | null> = useTemplateRef('bottomContent');

const messages: ComputedRef<MessageProps[]> = computed(() => {
	return props.messages
		.reduce((accumulator: MessageProps[], message: ChatMessage) => {
			const previousMessage: ChatMessage | undefined = accumulator[accumulator.length - 1];
			const isPreviousMessageSameMinute = previousMessage && isSameMinute(previousMessage.timestamp, message.timestamp);
			const isPreviousMessageAuthorSame = previousMessage && previousMessage.userId === message.userId;

			const transformedMessage = {
				userId: message.userId,
				username: props.participants.find((participant: User): boolean => participant.id === message.userId)!.name,
				message: message.message,
				timestamp: message.timestamp,
				isAuthenticatedPersonAuthor: message.userId === currentUser.user!.id,
				showDate: !isPreviousMessageSameMinute,
				showAuthor: !isPreviousMessageAuthorSame,
			}
			accumulator.push(transformedMessage);

			return accumulator;
		}, []);
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
		<Button @click="onCloseClick" class="close" icon="pi pi-times" size="small" severity="secondary" variant="text" rounded />
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
			<div ref="bottomContent"></div>
		</ScrollPanel>
		<FloatLabel variant="on">
			<InputText id="email" v-model="messageBody" type="text" @keydown.enter="onEnterPressMessageInput"></InputText>
			<label for="email">Message</label>
		</FloatLabel>
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

	.close {
		align-self: end;
		min-height: 40px;
	}

	:deep(.p-scrollpanel) {
		height: calc(100% - 94px);
	}

	:deep(.p-scrollpanel-content) {
		padding-bottom: 0;
	}

	.bottomContent {
		display: none;
	}

	input {
		width: 100%;
	}
}
</style>
