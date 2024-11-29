<script setup lang="ts">
import {computed, type Ref} from 'vue';

const props = defineProps<{
	isOff: boolean
}>();

const iconPath: Ref<string> = computed(() => {
	return '/icons/' + (props.isOff
		? 'screen-share-off.svg'
		: 'screen-share.svg');
});
</script>

<template>
	<img alt="screen share icon" :src="iconPath">
</template>

