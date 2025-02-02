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
	name: {
		value: '',
		error: '',
	},
	email: {
		value: '',
		error: '',
	},
	password: {
		value: '',
		error: '',
	}
});

function registerRequestBody(): Record<string, string> {
	return Object.keys(form).reduce((accumulator: Record<string, string>, field: string): Record<string, string> => {
		accumulator[field] = form[field].value;

		return accumulator;
	}, {})
}

function onLogInButtonClick(): void {
	router.push({path: '/login'});
}

function onSignUpButtonClick(): void {
	loading.value = true;
	Object.keys(form).forEach((field) => form[field].error = '');
	useAxios().get('sanctum/csrf-cookie')
		.then((): Promise<AxiosResponse<{ message: string }>> => useAxios().post('register', registerRequestBody()))
		.then((): Promise<void> => auth.fetch())
		.then((): void => {
			toast.add({ severity: 'success', summary: 'Signup page', detail: 'Signup successful', life: 3000 });
			router.push({path: '/'})
		})
		.catch((response: AxiosError<{ errors?: Record<string, string[]> }>): void => {
			if (response.status === HttpStatusCode.UnprocessableEntity && response.response?.data.errors) {
				Object.entries(response.response.data.errors).forEach(([field, errors]): void => {
					form[field].error = errors[0];
				});
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
			<template #title>Sign Up</template>
			<template #content>
				<div class="card-content">
					<div class="form-field">
						<FloatLabel variant="on">
							<InputText id="name" v-model="form.name.value" @keydown.enter="onSignUpButtonClick"></InputText>
							<label for="name">Username</label>
						</FloatLabel>
						<small v-if="form.name.error">{{ form.name.error }}</small>
					</div>
					<div class="form-field">
						<FloatLabel variant="on">
							<InputText id="email" v-model="form.email.value" @keydown.enter="onSignUpButtonClick" type="email"></InputText>
							<label for="email">Email</label>
						</FloatLabel>
						<small v-if="form.email.error">{{ form.email.error }}</small>
					</div>
					<div class="form-field">
						<FloatLabel variant="on">
							<InputText id="password" v-model="form.password.value" @keydown.enter="onSignUpButtonClick" type="password"></InputText>
							<label for="password">Password</label>
						</FloatLabel>
						<small v-if="form.password.error">{{ form.password.error }}</small>
					</div>
					<Button
						:loading="loading"
						:label="'Sign up'"
						@click="onSignUpButtonClick">
					</Button>
					<div class="log-in">Already have an account?
						<Button variant="link" label="Log In" @click="onLogInButtonClick"></Button>
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

		.log-in {
			margin: 0 auto;

			:deep(.p-button) {
				padding: 0;
			}
		}
	}
}
</style>
