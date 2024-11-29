<script setup lang="ts">
import {computed, type Ref} from 'vue';

const props = defineProps<{
	isOff: boolean
}>();

const iconPath: Ref<string> = computed(() => {
	return '/icons/' + (props.isOff
		? 'mic-off.svg'
		: 'mic.svg');
});

</script>

<template>
	<img alt="mic icon" :src="iconPath">
</template>

