<script setup lang="ts">

defineProps<{
	name: string,
	isTrackMuted: boolean,
}>();

</script>

<template>
	<div class="person-label">
		<span>{{ name }}</span>
		<i class="pi pi-microphone"></i>
		<i class="pi pi-line" v-if="isTrackMuted">
			<svg width="16" height="16">
				<line x1="0" y1="0" x2="16" y2="16"/>
			</svg>
		</i>
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

	span {
		padding-right: 4px;
	}

	.pi-line {
		position: absolute;
		right: 4px;

		svg {
			stroke: var(--p-text-color);
		}
	}
}
</style>
