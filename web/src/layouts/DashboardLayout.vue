<script setup lang="ts">
import {ref} from 'vue';
import Menu from 'primevue/menu';
import {useAxios} from '@/composables/axios';
import {useToast} from 'primevue/usetoast';
import router from '@/router';
import {useAuth} from '@/stores/auth';
import {useRecordingsStore} from '@/stores/recordings';

const toast = useToast();

const menuItems = ref([
	{
		label: 'Meetings',
		items: [
			{
				label: 'Create / Join',
				icon: 'pi pi-phone',
				command: () => router.push({path: '/meetings-start'})
			},
		],
	},
	{
		label: 'Recordings',
		items: [
			{
				label: 'List',
				icon: 'pi pi-video',
				command: () => router.push({path: '/recordings'})
			},
		]
	},
	{
		label: 'Profile',
		items: [
			{label: 'Settings', icon: 'pi pi-cog'},
			{
				label: 'Logout',
				icon: 'pi pi-sign-out',
				command: () => onLogoutButtonClick()
			}
		]
	},
]);

function onLogoutButtonClick(): void {
	useAxios().get('logout')
		.then(() => {
			useAuth().$reset();
			useRecordingsStore().$reset();
			toast.add({ severity: 'success', summary: 'Logout', detail: 'User logged out successfully', life: 3000 });
			router.push({path: '/'});
		})
		.catch(() => {
			toast.add({ severity: 'error', summary: 'Logout', detail: 'User logout failed', life: 3000 });
		});
}
</script>

<template>
	<div class="layout">
		<Menu :model="menuItems">
			<template #end>
				<div class="user">
					Logged in as <span>{{ useAuth().user?.name }}</span>
				</div>
			</template>
		</Menu>
		<main>
			<slot></slot>
		</main>
	</div>
</template>

<style lang="scss" scoped>
.layout {
	:deep(.p-menu) {
		width: 15%;
		height: 100%;
		position: fixed;
	}

	main {
		margin-left: 15%;
	}

	:deep(.p-menu) {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
	}

	.user {
		font-size: 14px;
		padding: 8px 12px;

		span {
			font-weight: 700;
		}
	}
}
</style>
