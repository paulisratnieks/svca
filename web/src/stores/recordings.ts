import {type Ref, ref} from 'vue'
import {defineStore} from 'pinia'
import type {Recording} from '@/types/recording';

export const useRecordingsStore = defineStore('recordings', () => {
	const ids: Ref<Record<string, string>> = ref({});
	const recordings: Ref<Recording[]> = ref([]);

	function $reset(): void {
		ids.value = {};
		recordings.value = [];
	}

	return { ids, recordings, $reset };
}, {
	persist: {
		pick: ['ids', 'recordings'],
	}
})
