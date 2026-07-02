import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false, layout: 'auth' },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { requiresAuth: false, layout: 'auth' },
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/DashboardView.vue'),
    meta: { requiresAuth: true, title: 'لوحة التحكم' },
  },
  {
    path: '/projects',
    name: 'Projects',
    component: () => import('@/views/ProjectsView.vue'),
    meta: { requiresAuth: true, title: 'المشاريع' },
  },
  {
    path: '/projects/:id',
    name: 'ProjectDetail',
    component: () => import('@/views/ProjectDetailView.vue'),
    meta: { requiresAuth: true, title: 'تفاصيل المشروع' },
  },
  {
    path: '/projects/:id/board',
    name: 'KanbanBoard',
    component: () => import('@/views/KanbanBoard.vue'),
    meta: { requiresAuth: true, title: 'لوحة كانبان' },
  },
  {
    path: '/tasks',
    name: 'Tasks',
    component: () => import('@/views/TasksView.vue'),
    meta: { requiresAuth: true, title: 'جميع المهام' },
  },
  {
    path: '/tasks/:id',
    name: 'TaskDetail',
    component: () => import('@/views/TaskDetailView.vue'),
    meta: { requiresAuth: true, title: 'تفاصيل المهمة' },
  },
  {
    path: '/time',
    name: 'TimeTracking',
    component: () => import('@/views/TimeTrackingView.vue'),
    meta: { requiresAuth: true, title: 'تتبع الوقت' },
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('@/views/ReportsView.vue'),
    meta: { requiresAuth: true, title: 'التقارير' },
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('@/views/SettingsView.vue'),
    meta: { requiresAuth: true, title: 'الإعدادات' },
  },
  {
    path: '/settings/members',
    name: 'Members',
    component: () => import('@/views/MembersView.vue'),
    meta: { requiresAuth: true, title: 'فريق العمل' },
  },
  {
    path: '/settings/webhooks',
    name: 'Webhooks',
    component: () => import('@/views/WebhooksView.vue'),
    meta: { requiresAuth: true, title: 'الويبهوك' },
  },
];

const router = createRouter({
  // BASE_URL is '/app/' when built/served under the Laravel /app mount,
  // and '/' for standalone vite dev.
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const uiStore = useUiStore();

  if (to.meta.title) {
    document.title = `${to.meta.title} — TaskSync Pro`;
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } });
  } else if (!to.meta.requiresAuth && authStore.isAuthenticated && to.name === 'Login') {
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;
