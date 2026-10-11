import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/views/HomeView.vue'),
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/history',
    name: 'history',
    component: () => import('@/views/HistoryView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/intel',
    name: 'intel',
    component: () => import('@/views/PostsView.vue'),
  },
  {
    path: '/hub',
    name: 'user-hub',
    component: () => import('@/views/UserHubView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/intel/create',
    name: 'create-intel',
    component: () => import('@/views/CreatePostView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('@/views/AdminDashboardView.vue'),
    meta: { requiresAuth: true, requiresRole: 'admin' },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, _from, next) => {
  const auth = useAuthStore();

  // Ensure auth state is initialized before guarding
  if (!auth.isInitialized) {
    await auth.init();
  }

  // Guest-only routes (login, register) — redirect to home if already authenticated
  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return next({ name: 'home' });
  }

  // Auth-required routes — redirect to login if not authenticated
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } });
  }

  // Role-required routes — redirect to home if role doesn't match
  if (to.meta.requiresRole && auth.user?.role !== to.meta.requiresRole) {
    return next({ name: 'home' });
  }

  next();
});

export default router;
