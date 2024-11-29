import {reactive} from 'vue'
import {defineStore} from 'pinia'

export const useSettingsStore = defineStore('settings', () => {
	const mediaPreferences: {audio: boolean, video: boolean} = reactive({
		audio: true,
		video: true,
	});

	return { mediaPreferences };
}, {
	persist: {
		pick: ['mediaPreferences'],
	}
});
