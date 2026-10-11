<script setup lang="ts">
import { useAuthStore } from '@/stores/auth';
import { useAppStore } from '@/stores/app';
import { storeToRefs } from 'pinia';
import { useRouter, useRoute } from 'vue-router';
import { 
  Shield, Menu, X, LogIn, UserPlus, LogOut, Clock, Globe, 
  Crown, User, Languages, Check, ArrowLeft, ArrowRight, Terminal, Home 
} from 'lucide-vue-next';
import { computed } from 'vue';
import Toast from '@/components/Toast.vue';
import ScanHistorySidebar from '@/components/ScanHistorySidebar.vue';
import { translations } from './translations';

const auth = useAuthStore();
const app = useAppStore();
const { lang } = storeToRefs(app);
const router = useRouter();
const route = useRoute();

const isAuthenticated = computed(() => auth.isAuthenticated);
const isAdmin = computed(() => auth.user?.role === 'admin');
const userName = computed(() => auth.user?.name ?? '');
const userRole = computed(() => auth.user?.role ?? '');
const isHomePage = computed(() => route.name === 'home');
const isRtl = computed(() => lang.value === 'ar');
const t = computed(() => translations[lang.value]);

const handleLogout = async () => {
  try {
    await auth.logout();
    router.push('/');
  } catch {
    auth.user = null;
    router.push('/');
  }
};
</script>

<template>
  <div class="app-shell" :dir="isRtl ? 'rtl' : 'ltr'">
    <!-- Nav Bar -->
    <nav class="auth-nav">
      <div class="nav-left">
        <router-link to="/" class="nav-brand">
          <Shield :size="28" class="brand-icon" />
          <span class="brand-text">LinkShield<span class="brand-accent">AI</span></span>
        </router-link>

        <div class="nav-divider hidden md:block"></div>

        <router-link to="/" class="nav-btn">
          <Home :size="16" />
          <span class="nav-btn-label">{{ isRtl ? 'الرئيسية' : 'Home' }}</span>
        </router-link>

        <router-link to="/intel" class="nav-btn">
          <Terminal :size="16" />
          <span class="nav-btn-label">{{ isRtl ? 'استخبارات المجتمع' : 'Intel Feed' }}</span>
        </router-link>
      </div>

      <div class="nav-actions">
        <!-- Utility icon buttons -->
        <button @click="app.openSidebar()" class="nav-icon-btn" :title="isRtl ? 'سجل الفحص' : 'Scan History'">
          <Clock class="w-5 h-5 text-cyan-400" />
        </button>
        <button @click="app.toggleLang()" class="nav-icon-btn" :title="isRtl ? 'English' : 'العربية'">
          <Languages :size="18" />
        </button>

        <div class="nav-divider hidden sm:block"></div>

        <!-- Authenticated State -->
        <template v-if="isAuthenticated">
          <router-link v-if="isAdmin" to="/admin" class="nav-btn">
            <Shield :size="16" class="text-amber-400" />
            <span class="nav-btn-label text-amber-400 font-bold">{{ isRtl ? 'مركز القيادة' : 'Command Center' }}</span>
          </router-link>

          <router-link to="/history" class="nav-btn">
            <Clock :size="16" />
            <span class="nav-btn-label">{{ isRtl ? 'سجلي' : 'My History' }}</span>
          </router-link>
          <router-link to="/hub" class="user-badge" style="text-decoration: none;">
            <div class="user-avatar">
              <Crown v-if="userRole === 'admin'" :size="14" class="text-amber-400" />
              <User v-else :size="14" class="text-cyan-400" />
            </div>
            <div class="user-info">
              <span class="user-name">{{ userName }}</span>
              <span class="user-role">{{ userRole.toUpperCase() }}</span>
            </div>
          </router-link>
          <button @click="handleLogout" class="nav-btn nav-btn--danger">
            <LogOut :size="16" />
            <span class="nav-btn-label">{{ isRtl ? 'خروج' : 'Logout' }}</span>
          </button>
        </template>

        <!-- Guest State -->
        <template v-else>
          <router-link to="/login" class="nav-btn">
            <LogIn :size="16" />
            <span class="nav-btn-label">{{ isRtl ? 'دخول' : 'Login' }}</span>
          </router-link>
          <router-link to="/register" class="nav-btn nav-btn--primary">
            <UserPlus :size="16" />
            <span class="nav-btn-label">{{ isRtl ? 'تسجيل' : 'Register' }}</span>
          </router-link>
        </template>
      </div>
    </nav>

    <!-- Page Content -->
    <router-view />

    <!-- Global HUD Notification -->
    <transition name="fade">
      <Toast v-if="auth.showHud" :message="auth.hudMessage" :type="auth.hudType" />
    </transition>

    <ScanHistorySidebar 
      :history="app.history" 
      :t="t"
      :isRtl="isRtl"
      :isOpen="app.isSidebarOpen"
      :hasMore="app.hasMoreHistory"
      :isLoadingMore="app.isLoadingHistory"
      @close="app.closeSidebar()"
      @select="url => { $router.push({ path: '/', query: { scan: url } }); app.closeSidebar(); }"
      @load-more="app.loadMoreHistory()"
    />
  </div>
</template>

<style scoped>
/* ── Shell ─────────────────────────────────────────────────────── */
.app-shell {
  min-height: 100vh;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Nav Bar ───────────────────────────────────────────────────── */
.auth-nav {
  position: fixed;
  top: 0;
  inset-inline: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1.5rem;
  background: rgba(10, 10, 10, 0.7);
  backdrop-filter: blur(20px) saturate(180%);
  border-bottom: 1px solid rgba(34, 211, 238, 0.15);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
  gap: 1rem;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
}

/* ── Brand ─────────────────────────────────────────────────────── */
.nav-brand {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  transition: opacity 0.2s;
  flex-shrink: 0;
}
.nav-brand:hover { opacity: 0.8; }

.brand-icon { 
  color: #22d3ee;
  filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.4));
}

.brand-text {
  font-size: 1.25rem;
  font-weight: 800;
  color: #f1f5f9;
  letter-spacing: -0.03em;
  text-shadow: 0 0 20px rgba(34, 211, 238, 0.2);
}

.brand-accent { color: #22d3ee; }

/* ── Actions Container ─────────────────────────────────────────── */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: nowrap;
}

/* ── Icon Buttons ──────────────────────────────────────────────── */
.nav-icon-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 0.5rem;
  color: #94a3b8;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(51, 65, 85, 0.4);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.nav-icon-btn:hover {
  color: #22d3ee;
  border-color: rgba(34, 211, 238, 0.3);
  background: rgba(34, 211, 238, 0.08);
  box-shadow: 0 0 12px rgba(34, 211, 238, 0.1);
}

/* ── Divider ───────────────────────────────────────────────────── */
.nav-divider {
  width: 1px;
  height: 24px;
  background: rgba(51, 65, 85, 0.4);
  flex-shrink: 0;
}

/* ── User Badge ────────────────────────────────────────────────── */
.user-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.325rem 0.625rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(51, 65, 85, 0.4);
  border-radius: 0.625rem;
  flex-shrink: 0;
}

.user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background: rgba(34, 211, 238, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.user-name {
  font-size: 0.75rem;
  font-weight: 600;
  color: #e2e8f0;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100px;
}

.user-role {
  font-size: 0.55rem;
  font-weight: 700;
  color: #64748b;
  letter-spacing: 0.15em;
}

/* ── Nav Buttons ───────────────────────────────────────────────── */
.nav-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(51, 65, 85, 0.4);
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
  font-family: inherit;
  white-space: nowrap;
  flex-shrink: 0;
}

.nav-btn:hover {
  color: #e2e8f0;
  border-color: rgba(51, 65, 85, 0.6);
  background: rgba(15, 23, 42, 0.8);
}

.nav-btn--primary {
  background: rgba(34, 211, 238, 0.1);
  border-color: rgba(34, 211, 238, 0.2);
  color: #22d3ee;
}

.nav-btn--primary:hover {
  background: rgba(34, 211, 238, 0.15);
  border-color: rgba(34, 211, 238, 0.3);
  color: #67e8f9;
}

.nav-btn--danger {
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.2);
}

.nav-btn--danger:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}

/* ── Responsive ────────────────────────────────────────────────── */

/* Tablets & small desktops */
@media (max-width: 768px) {
  .auth-nav { padding: 0.5rem 1rem; }
  .nav-btn-label { display: none; }
  .user-info { display: none; }
  .nav-btn { padding: 0.5rem; }
  .nav-actions { gap: 0.375rem; }
  .nav-divider { height: 20px; }
}

/* Small phones */
@media (max-width: 380px) {
  .auth-nav { padding: 0.5rem 0.625rem; }
  .brand-text { font-size: 0.875rem; }
  .nav-icon-btn { width: 32px; height: 32px; }
  .nav-actions { gap: 0.25rem; }
  .nav-divider { display: none; }
  .user-badge { padding: 0.25rem 0.375rem; }
}
</style>
