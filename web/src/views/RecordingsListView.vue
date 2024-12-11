<script setup lang="ts">
import {useAxios} from '@/composables/axios';
import {computed, onMounted, ref, type Ref, watch} from 'vue';
import type {AxiosResponse} from 'axios';
import type {Recording} from '@/types/recording';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import router from '@/router';
import {useRecordingsStore} from '@/stores/recordings';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import Button from 'primevue/button';
import Toolbar from 'primevue/toolbar';
import {useToast} from 'primevue/usetoast';

const store = useRecordingsStore();
const toast = useToast();

const selectedRecordings: Ref<Recording[]|Recording> = ref([]);
const isDeleteButtonDisabled: Ref<boolean> = ref(true);
const isDeleteButtonLoading: Ref<boolean> = ref(false);
const loading: Ref<boolean> = ref(true);
const rowsPerPageOptions: Ref<number[]> = ref([5, 10, 20, 50]);
const recordings: Ref<Recording[]> = ref([]);
const columns = ref([
	{header: 'File', field: 'file_name'},
	{header: 'Author', field: 'is_author'},
	{header: 'Created At', field: 'created_at'},
]);

const transformedRecordings: Ref<Recording[]> = computed(() => {
	return recordings.value.map(recording => {
		return {
			...recording,
			created_at: formattedDate(recording.created_at),
		};
	});
});

function formattedDate(timestamp: string): string {
	return timestamp.replace("T", " ").split(".")[0];
}

function onRowClick(event: {data: Recording}): void {
	router.push({path: '/recordings/' + event.data.id});
}

function onDeleteButtonClick(): void {
	isDeleteButtonLoading.value = true;
	const selectedRecordingIds: number[] = Array.isArray(selectedRecordings.value)
		? selectedRecordings.value.map(recording => recording.id)
		: [selectedRecordings.value.id];
	useAxios().delete('recordings', {params: {ids: selectedRecordingIds}})
		.then(() => {
			store.recordings.slice(store.recordings.findIndex(recording => recording.id === recording!.id));
			toast.add({ severity: 'success', summary: 'Recordings page', detail: 'Recordings deleted successfully', life: 3000 });
			fetchRecordings();
		})
		.catch(() => {
			toast.add({ severity: 'error', summary: 'Recordings page', detail: 'Recording delete failed', life: 3000 });
		})
		.finally(() => {
			isDeleteButtonLoading.value = false;
		});
}

function fetchRecordings(): void {
	useAxios().get('recordings')
		.then((response: AxiosResponse<{data: Recording[]}>): void => {
			recordings.value = response.data.data;
			store.recordings = response.data.data;
			loading.value = false;
		});
}

watch(
	selectedRecordings,
	() => {
		isDeleteButtonDisabled.value = Array.isArray(selectedRecordings.value)
			? !selectedRecordings.value.every(recording => recording.is_author)
				|| selectedRecordings.value.length === 0
			: selectedRecordings.value.is_author;
	}
);

onMounted(() => {
	fetchRecordings();
});
</script>

<template>
	<DashboardLayout>
		<div class="view">
			<Toolbar class="actions">
				<template #start>
					<Button
						:loading="isDeleteButtonLoading"
						:disabled="isDeleteButtonDisabled"
						label="Delete"
						severity="danger"
						icon="pi pi-trash"
						@click="onDeleteButtonClick"
					/>
				</template>
			</Toolbar>
			<div class="table">
				<DataTable
					:value="transformedRecordings"
					:rowsPerPageOptions="rowsPerPageOptions"
					:loading="loading"
					:rows="10"
					v-model:selection="selectedRecordings"
					paginator
					tableStyle="min-width: 50rem"
					selectionMode="single"
					dataKey="id"
					@rowClick="onRowClick"
				>
					<Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
					<Column v-for="column of columns" :key="column.field" :field="column.field" :header="column.header"></Column>
				</DataTable>
			</div>
		</div>
	</DashboardLayout>
</template>

<style lang="scss" scoped>
.view {
	.table {
		padding: 16px;
	}
}
</style>
