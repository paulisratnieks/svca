import {type Ref, ref} from 'vue'
import {defineStore} from 'pinia'

export const useRecordingsStore = defineStore('recordings', () => {
	const ids: Ref<Record<string, string>> = ref({});
	const statuses: Ref<Record<string, boolean>> = ref({});

	return { ids, statuses };
}, {
	persist: {
		pick: ['ids', 'statuses'],
	}
})
