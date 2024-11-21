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

const meetingId: Ref<string> = ref('');
const showMeetingIdError: Ref<boolean> = ref(false);

const isMeetingIdFieldFilled: ComputedRef<boolean> = computed((): boolean => {
	return meetingId.value.length >= 1;
});

function onCreateMeetingButtonClick(): void {
	useAxios().post('meetings')
		.then((response: AxiosResponse<{ id: string }>): void => {
			redirectToMeeting(response.data.id)
		});
}

function onJoinMeetingButtonClick(): void {
	showMeetingIdError.value = false;

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
}

function redirectToMeeting(id: string): void {
	router.push({path: '/staging', query: {meetingId: id}});
}
</script>

<template>
	<main>
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
						:disabled="!isMeetingIdFieldFilled"
						:label="'Join meeting'"
						@click="onJoinMeetingButtonClick">
					</Button>
					<Divider layout="horizontal" class=""><b>OR</b></Divider>
					<Button
						:label="'Create meeting'"
						@click="onCreateMeetingButtonClick">
					</Button>
				</div>
			</template>
		</Card>
	</main>
</template>

<style lang="scss" scoped>
main {
	padding-top: 64px;
	margin: 0 auto;
	max-width: 400px;

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
