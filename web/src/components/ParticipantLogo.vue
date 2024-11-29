<script setup lang="ts">
import {computed, type ComputedRef} from 'vue';

const props = defineProps<{
	name: string,
}>();

const personInitials: ComputedRef<string> = computed(() => {
	const name: string[] = props.name.split(' ');

	return name[0][0].toUpperCase()
		+ (name.length > 1 ? name[name.length - 1][0].toUpperCase() : '');
});

</script>

<template>
	<div class="person-initials">
		<div>{{ personInitials }}</div>
	</div>
</template>

<style lang="scss" scoped>
.person-initials {
	background-color: var(--p-content-background);
	position: absolute;
	width: 100%;
	height: 100%;
	display: flex;
	justify-content: center;
	align-items: center;
	z-index: 2;

	div {
		display: flex;
		border: 1px solid var(--p-primary-color);
		background-color: var(--p-primary-600);
		z-index: 3;
		font-size: 24px;
		width: 64px;
		height: 64px;
		border-radius: 32px;
		justify-content: center;
		align-items: center;
	}
}
</style>
