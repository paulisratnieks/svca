<script setup lang="ts">
import {useAxios} from '@/composables/axios';
import {computed, onMounted, ref, type Ref} from 'vue';
import {useRoute} from 'vue-router';
import Button from 'primevue/button';
import Toolbar from 'primevue/toolbar';
import {useToast} from 'primevue/usetoast';
import router from '@/router';
import {useRecordingsStore} from '@/stores/recordings';
import type {Recording} from '@/types/recording';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import type {AxiosResponse} from 'axios';

const route = useRoute();
const toast = useToast();
const store = useRecordingsStore();

const downloadButtonLoading: Ref<boolean> = ref(false);
const deleteButtonLoading: Ref<boolean> = ref(false);
const recordingsBaseRoute = `/recordings`;
const recordingUrl = `${import.meta.env.VITE_API_URL}${recordingsBaseRoute}/${route.params.recordingId}/`;
const recordingDownloadUrl = recordingUrl + 'download';
const recording: Ref<Recording|undefined> = computed(() => {
	return store.recordings.find(recording => recording.id === Number(route.params.recordingId));
});

function onDownloadButtonClick(): void {
	downloadButtonLoading.value = true;
	useAxios().get(recordingDownloadUrl, {responseType: 'blob'})
		.then((response: AxiosResponse) => {
			const blob = new Blob([response.data]);
			const url = window.URL.createObjectURL(blob);
			const a = document.createElement('a');
			const contentDisposition = response.headers['content-disposition'].split('=');
			a.download = contentDisposition[contentDisposition.length - 1];
			a.href = url;
			a.click();
			window.URL.revokeObjectURL(url);
			toast.add({ severity: 'success', summary: 'Recording page', detail: 'Recording downloaded successfully', life: 3000 });
		})
		.catch(() => {
			toast.add({ severity: 'error', summary: 'Recording page', detail: 'Recording download failed', life: 3000 });
		})
		.finally(() => {
			downloadButtonLoading.value = false;
		});
}

function onBackButtonClick(): void {
	router.back();
}

function onDeleteButtonClick(): void {
	deleteButtonLoading.value = true;
	useAxios().delete(recordingsBaseRoute, {params: {ids: [recording.value!.id]}})
		.then(() => {
			store.recordings.slice(store.recordings.findIndex(recording => recording.id === recording!.id));
			toast.add({ severity: 'success', summary: 'Recording page', detail: 'Recording deleted successfully', life: 3000 });
			router.push({path: '/recordings'});
		})
		.catch(() => {
			toast.add({ severity: 'error', summary: 'Recording page', detail: 'Recording deleted failed', life: 3000 });
		})
		.finally(() => {
			deleteButtonLoading.value = false;
		});
}

onMounted(() => {
	if (!recording.value) {
		toast.add({ severity: 'error', summary: 'Recording page', detail: 'Recording not found', life: 3000 });
		router.push({path: '/recordings'});
	}
});
</script>

<template>
	<DashboardLayout>
		<div class="view">
			<Toolbar class="actions">
				<template #start>
					<Button :loading="downloadButtonLoading" label="Download" icon="pi pi-download" @click="onDownloadButtonClick" />
					<Button
						v-if="recording!.is_author"
						:loading="deleteButtonLoading"
						label="Delete"
						severity="danger"
						icon="pi pi-trash"
						@click="onDeleteButtonClick"
					/>
				</template>
				<template #end>
					<Button label="Back" severity="info" icon="pi pi-arrow-left" @click="onBackButtonClick" />
				</template>
			</Toolbar>
			<video ref="video" controls :src="recordingUrl"></video>
		</div>
	</DashboardLayout>
</template>

<style lang="scss" scoped>
.view {
	:deep(.p-button) {
		margin: 0 4px ;
	}

	video {
		padding: 16px;
		border-radius: 8px;
		max-width: 700px;
		width: 100%;
	}
}
</style>
