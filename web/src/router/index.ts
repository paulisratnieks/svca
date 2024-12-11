import {createRouter, createWebHistory} from 'vue-router'
import {useAuth} from '@/stores/auth';

const router = createRouter({
	history: createWebHistory(import.meta.env.BASE_URL),
	routes: [
		{
			path: '/',
			name: 'home',
			redirect: () => {
				return useAuth().isAuthenticated()
					? 'meetings-start'
					: 'login'
			},
		},
		{
			path: '/login',
			name: 'login',
			component: () => import('../views/LoginView.vue'),
		},
		{
			name: 'signup',
			path: '/signup',
			component: () => import('../views/RegisterView.vue')
		},
		{
			path: '/staging',
			component: () => import('../views/StagingView.vue')
		},
		{
			path: '/meetings/:meetingId',
			component: () => import('../views/MeetingView.vue')
		},
		{
			path: '/meetings-start',
			component: () => import('../views/MeetingStartView.vue')
		},
		{
			path: '/recordings',
			component: () => import('../views/RecordingsListView.vue')
		},
		{
			path: '/recordings/:recordingId',
			component: () => import('../views/RecordingView.vue')
		},
		{
			path: '/:pathMatch(.*)*',
			redirect: '/'
		},
	]
})

router.beforeEach(async (to) => {
	const loginRouteName = 'login';
	const unguardedRouteNames = ['home', 'signup', loginRouteName];

	if (
		!useAuth().isAuthenticated()
		&& !unguardedRouteNames.includes(String(to.name))
		&& to.name !== loginRouteName
	) {
		return {name: 'login'}
	}
})

export default router
