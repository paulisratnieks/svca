<script setup lang="ts">
import {reactive, ref, type Ref} from 'vue';
import {type AxiosError, type AxiosResponse, HttpStatusCode} from 'axios';
import {useAuth} from '@/stores/auth';
import router from '@/router';
import {useAxios} from '@/composables/axios';
import InputText from 'primevue/inputtext';
import Card from 'primevue/card';
import Button from 'primevue/button';
import FloatLabel from 'primevue/floatlabel';
import {useToast} from 'primevue/usetoast';

const auth = useAuth();
const toast = useToast();

const loading: Ref<boolean> = ref(false);
const form: Record<string, { value: string, error?: string }> = reactive({
	email: {
		value: '',
		error: '',
	},
	password: {
		value: '',
		error: '',
	},
});

function loginRequestBody(): Record<string, string> {
	return Object.keys(form).reduce((accumulator: Record<string, string>, field: string): Record<string, string> => {
		accumulator[field] = form[field].value;

		return accumulator;
	}, {})
}

function onSignUpButtonClick(): void {
	router.push({path: '/signup'});
}

function onLoginButtonClick(): void {
	loading.value = true;
	Object.keys(form).forEach((field) => form[field].error = '');
	useAxios().get('sanctum/csrf-cookie')
		.then((): Promise<AxiosResponse<{ message: string }>> => useAxios().post('login', loginRequestBody()))
		.then((): Promise<void> => auth.fetch())
		.then((): void => {
			toast.add({ severity: 'success', summary: 'Login page', detail: 'Login successful', life: 3000 });
			router.push({path: '/'})
		})
		.catch((response: AxiosError<{ errors?: Record<string, string[]>, message?: string }>): void => {
			if (response.status === HttpStatusCode.UnprocessableEntity && response.response?.data.errors) {
				Object.entries(response.response.data.errors).forEach(([field, errors]): void => {
					form[field].error = errors[0];
				});
			} else if (response.status === HttpStatusCode.Unauthorized && response.response?.data.message) {
				form.password.error = response.response?.data.message;
			}
		})
		.finally(() => {
			loading.value = false;
		});
}
</script>

<template>
	<main>
		<Card>
			<template #title>Log In</template>
			<template #content>
				<div class="card-content">
					<div class="form-field">
						<FloatLabel variant="on">
							<InputText id="email" v-model="form.email.value" type="email"></InputText>
							<label for="email">Email</label>
						</FloatLabel>
						<small v-if="form.email.error">{{ form.email.error }}</small>
					</div>
					<div class="form-field">
						<FloatLabel variant="on">
							<InputText id="password" v-model="form.password.value" type="password"></InputText>
							<label for="password">Password</label>
						</FloatLabel>
						<small v-if="form.password.error">{{ form.password.error }}</small>
					</div>
					<Button
						:loading="loading"
						:label="'Log in'"
						@click="onLoginButtonClick">
					</Button>
					<div class="sign-up">Don't have an acoount?
						<Button variant="link" label="Sign Up" @click="onSignUpButtonClick"></Button>
					</div>
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
		padding-top: 16px;
		display: flex;
		gap: 20px;
		flex-direction: column;

		.form-field {
			display: flex;
			flex-direction: column;
			gap: 8px;

			:deep(.p-inputtext) {
				width: 100%;
			}

			small {
				color: $color-red;
			}
		}

		.sign-up {
			margin: 0 auto;

			:deep(.p-button) {
				padding: 0;
			}
		}
	}
}
</style>
