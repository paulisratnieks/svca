import {type Ref, ref} from 'vue'
import {defineStore} from 'pinia'
import type {Recording} from '@/types/recording';

export const useRecordingsStore = defineStore('recordings', () => {
	const ids: Ref<Record<string, string>> = ref({});
	const statuses: Ref<Record<string, boolean>> = ref({});
	const recordings: Ref<Recording[]> = ref([]);

	function $reset(): void {
		ids.value = {};
		statuses.value = {};
		recordings.value = [];
	}

	return { ids, statuses, recordings, $reset };
}, {
	persist: {
		pick: ['ids', 'statuses', 'recordings'],
	}
})
