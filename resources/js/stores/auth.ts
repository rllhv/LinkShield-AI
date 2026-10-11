import { defineStore } from 'pinia';
import axios from 'axios';
import { ref, computed } from 'vue';

// Configure axios for cookie-based auth
axios.defaults.withCredentials = true;

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'user';
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null);
  const isLoading = ref(false);
  const isInitialized = ref(false);

  // HUD Notification state
  const hudMessage = ref('');
  const hudType = ref<'success'|'warning'|'error'|'info'>('info');
  const showHud = ref(false);

  const triggerHud = (msg: string, type: 'success'|'warning'|'error'|'info' = 'info') => {
    hudMessage.value = msg;
    hudType.value = type;
    showHud.value = true;
    setTimeout(() => { showHud.value = false; }, 5000);
  };

  const isAuthenticated = computed(() => !!user.value);
  const isAdmin = computed(() => user.value?.role === 'admin');
  const userRole = computed(() => user.value?.role ?? null);

  /**
   * Fetch the CSRF cookie from Sanctum before login/register.
   */
  async function getCsrfCookie(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie');
  }

  /**
   * Attempt to fetch the current user profile.
   * Used on app init to restore session from cookie.
   */
  async function fetchUser(): Promise<void> {
    try {
      const res = await axios.get('/api/user');
      user.value = res.data;
    } catch {
      user.value = null;
    }
  }

  /**
   * Initialize auth state — called once on app mount.
   */
  async function init(): Promise<void> {
    if (isInitialized.value) return;
    isLoading.value = true;
    try {
      await fetchUser();
    } finally {
      isLoading.value = false;
      isInitialized.value = true;
    }
  }

  /**
   * Register a new user.
   */
  async function register(
    name: string,
    email: string,
    password: string,
    password_confirmation: string
  ): Promise<void> {
    await getCsrfCookie();
    const res = await axios.post('/api/register', {
      name,
      email,
      password,
      password_confirmation,
    });
    user.value = res.data.user;
  }

  /**
   * Log in with email and password.
   */
  async function login(email: string, password: string): Promise<void> {
    await getCsrfCookie();
    const res = await axios.post('/api/login', { email, password });
    user.value = res.data.user;

    // Link anonymous scans to the new session
    const localScans = JSON.parse(localStorage.getItem('local_scans') || '[]');
    if (localScans.length > 0) {
      try {
        await axios.post('/api/user/link-scans', { scans: localScans });
        localStorage.removeItem('local_scans');
      } catch (e) {}
    }

    // Check for post status updates
    try {
      const dashRes = await axios.get('/api/user/dashboard');
      if (dashRes.data.approvedPosts > 0) {
        triggerHud(`[ STATUS UPDATE: ${dashRes.data.approvedPosts} INTEL FEED APPROVED ]`, 'success');
      }
    } catch (e) {
      // Ignore dashboard fetch errors during login
    }
  }

  /**
   * Log out the current user.
   */
  async function logout(): Promise<void> {
    await axios.post('/api/logout');
    user.value = null;
  }

  return {
    user,
    isLoading,
    isInitialized,
    isAuthenticated,
    isAdmin,
    userRole,
    init,
    fetchUser,
    login,
    register,
    logout,
    triggerHud,
    hudMessage,
    hudType,
    showHud,
  };
});
