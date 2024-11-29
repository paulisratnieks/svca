<script setup lang="ts">
import {computed, type Ref} from 'vue';

const props = defineProps<{
	isOff: boolean
}>();

const iconPath: Ref<string> = computed(() => {
	return '/icons/' + (props.isOff
		? 'cam-off.svg'
		: 'cam.svg');
});
</script>

<template>
	<img alt="cam icon" :src="iconPath">
</template>

