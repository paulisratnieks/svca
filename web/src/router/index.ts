import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/login',
      component: () => import('../views/LoginView.vue')
    },
    {
      path: '/meetings/:meetingId',
      component: () => import('../views/MeetingView.vue')
    }
  ]
})

export default router
