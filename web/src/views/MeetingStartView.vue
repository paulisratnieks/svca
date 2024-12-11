<script setup lang="ts">
import router from '@/router';
import {type AxiosError, type AxiosResponse, HttpStatusCode} from 'axios';
import {useAxios} from '@/composables/axios';
import {computed, type ComputedRef, ref, type Ref} from 'vue';
import Button from "primevue/button"
import Card from "primevue/card"
import InputText from 'primevue/inputtext';
import Divider from 'primevue/divider';
import FloatLabel from 'primevue/floatlabel';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

const createButtonLoading: Ref<boolean> = ref(false);
const joinButtonLoading: Ref<boolean> = ref(false);
const meetingId: Ref<string> = ref('');
const showMeetingIdError: Ref<boolean> = ref(false);

const isMeetingIdFieldFilled: ComputedRef<boolean> = computed((): boolean => {
	return meetingId.value.length >= 1;
});

function onCreateMeetingButtonClick(): void {
	createButtonLoading.value = true;

	useAxios().post('meetings')
		.then((response: AxiosResponse<{ id: string }>): void => {
			redirectToMeeting(response.data.id)
		})
		.finally(() => {
			createButtonLoading.value = false;
		});
}

function onJoinMeetingButtonClick(): void {
	showMeetingIdError.value = false;
	joinButtonLoading.value = true;

	useAxios().get('meetings/' + meetingId.value)
		.then((response: AxiosResponse): void => {
			if (response.status === HttpStatusCode.NoContent) {
				redirectToMeeting(meetingId.value)
			}
		})
		.catch((response: AxiosError) => {
			if (response.status === HttpStatusCode.NotFound) {
				showMeetingIdError.value = true;
			}
		})
		.finally(() => {
			joinButtonLoading.value = false;
		});
}

function redirectToMeeting(id: string): void {
	router.push({path: '/staging', query: {meetingId: id}});
}
</script>

<template>
	<DashboardLayout>
		<div class="view">
			<Card>
				<template #content>
					<div class="card-content">
						<div class="form-field">
							<FloatLabel variant="on">
								<InputText id="meeting-id" v-model="meetingId"></InputText>
								<label for="meeting-id">Meeting ID</label>
							</FloatLabel>
							<small v-if="showMeetingIdError">A meeting with the provided ID does not exist</small>
						</div>
						<Button
							:loading="joinButtonLoading"
							:disabled="!isMeetingIdFieldFilled"
							:label="'Join meeting'"
							@click="onJoinMeetingButtonClick">
						</Button>
						<Divider layout="horizontal" class=""><b>OR</b></Divider>
						<Button
							:loading="createButtonLoading"
							:label="'Create meeting'"
							@click="onCreateMeetingButtonClick">
						</Button>
					</div>
				</template>
			</Card>
		</div>
	</DashboardLayout>
</template>

<style lang="scss" scoped>
.view {
	padding-top: 5%;
	margin: 0 auto;
	max-width: 400px;
	width: 100%;

	.card-content {
		display: flex;
		flex-direction: column;
		gap: 20px;

		.form-field {
			display: flex;
			flex-direction: column;
			gap: 8px;

			small {
				color: $color-red;
			}

			:deep(.p-inputtext) {
				width: 100%;
			}
		}

		:deep(.p-divider) {
			margin: 0;
		}
	}
}
</style>
