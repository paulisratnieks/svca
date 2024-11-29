<script setup lang="ts">
import {computed, type Ref} from 'vue';

const props = defineProps<{
	isOff: boolean
}>();

const iconPath: Ref<string> = computed(() => {
	return '/icons/' + (props.isOff
		? 'record-off.svg'
		: 'record.svg');
});
</script>

<template>
	<img alt="chat icon" :src="iconPath">
</template>

