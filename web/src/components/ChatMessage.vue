<script setup lang="ts">
import {computed, type ComputedRef} from 'vue';

const props = defineProps<{
	username: string,
	message: string,
	timestamp: number,
	isAuthenticatedPersonAuthor: boolean,
	showAuthor: boolean,
	showDate: boolean,
}>();

const formattedTime: ComputedRef<string> = computed(() => {
	const date: Date = new Date(props.timestamp);
	const hours = date.getHours().toString().padStart(2, '0');
	const minutes = date.getMinutes().toString().padStart(2, '0');

	return `${hours}:${minutes}`;
});

</script>

<template>
	<div
		class="message"
		:class="{
			'my-message': isAuthenticatedPersonAuthor,
			'other-message': !isAuthenticatedPersonAuthor
	}">
		<div class="author" v-if="showAuthor">{{ username }}</div>
		<div class="date" v-if="showDate">{{ formattedTime }}</div>
		<div class="body">{{ message }}</div>
	</div>
</template>

<style lang="scss" scoped>
.message {
	margin: 2px 14px 2px 2px;
	display: flex;
	flex-direction: column;
	font-size: 14px;

	.body {
		padding: 8px;
		border-radius: 6px;
		overflow-wrap: break-word;
		word-break: break-word;
	}

	.message {
		display: flex;
		flex-direction: row;
		gap: 4px;
	}

	.author, .date {
		color: var(--p-surface-500);
		font-size: 12px;
	}

	&.my-message {
		align-items: flex-end;

		.body {
			background-color: var(--p-emerald-500);
		}
	}

	&.other-message {
		align-items: start;

		.body {
			background-color: var(--p-surface-700);
		}
	}
}
</style>
