<script setup lang="ts">
import MicrophoneIcon from '@/components/icons/MicrophoneIcon.vue';

defineProps<{
	name: string,
	isTrackMuted: boolean,
}>();

</script>

<template>
	<div class="person-label">
		<span>{{ name }}</span>
		<MicrophoneIcon :is-off="isTrackMuted"></MicrophoneIcon>
	</div>
</template>

<style lang="scss" scoped>
.person-label {
	display: flex;
	background-color: var(--p-content-background);
	padding: 4px;
	position: absolute;
	text-align: center;
	font-size: 14px;
	top: 0;
	left: 0;
	border-bottom-right-radius: 8px;
	z-index: 3;
	align-items: center;

	span {
		padding-right: 4px;
	}
}
</style>
