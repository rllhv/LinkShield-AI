<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useAppStore } from '@/stores/app';
import { useAuthStore } from '@/stores/auth';
import { storeToRefs } from 'pinia';
import { 
  User, Shield, Key, Loader2, CheckCircle2, Activity,
  Clock, Terminal, Check, XCircle, AlertCircle, Save,
  ChevronRight, ExternalLink
} from 'lucide-vue-next';
import axios from 'axios';
import { translations } from '../translations';
import { useRouter } from 'vue-router';

const appStore = useAppStore();
const authStore = useAuthStore();
const { lang } = storeToRefs(appStore);
const { user } = storeToRefs(authStore);
const router = useRouter();

const isRtl = computed(() => lang.value === 'ar');
const t = computed(() => translations[lang.value]);

const activeTab = ref('identity');

const stats = ref({
  totalScans: 0,
  totalPosts: 0,
  approvedPosts: 0,
  pendingPosts: 0,
  rejectedPosts: 0,
});

const myPosts = ref<any[]>([]);
const isLoading = ref(true);

const fetchDashboardData = async () => {
  try {
    const [statsRes, postsRes] = await Promise.all([
      axios.get('/api/user/dashboard'),
      axios.get('/api/my-intel')
    ]);
    
    stats.value = statsRes.data;
    myPosts.value = postsRes.data.data;
  } catch (err) {
    console.error('Failed to fetch user hub data', err);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
  
  // Pre-fill profile form
  if (user.value) {
    profileForm.value.name = user.value.name;
    profileForm.value.email = user.value.email;
  }
});

// Profile Form
const profileForm = ref({ name: '', email: '' });
const profileLoading = ref(false);
const profileSuccess = ref(false);
const profileErrors = ref<any>({});

const updateProfile = async () => {
  profileLoading.value = true;
  profileSuccess.value = false;
  profileErrors.value = {};
  
  try {
    const res = await axios.put('/api/user/profile', profileForm.value);
    // Update pinia store directly so nav bar updates instantly
    authStore.user = res.data.user;
    profileSuccess.value = true;
    setTimeout(() => { profileSuccess.value = false; }, 3000);
  } catch (err: any) {
    if (err.response?.status === 422) {
      profileErrors.value = err.response.data.errors;
    }
  } finally {
    profileLoading.value = false;
  }
};

// Password Form
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });
const passwordLoading = ref(false);
const passwordSuccess = ref(false);
const passwordErrors = ref<any>({});

const updatePassword = async () => {
  passwordLoading.value = true;
  passwordSuccess.value = false;
  passwordErrors.value = {};
  
  try {
    await axios.put('/api/user/password', passwordForm.value);
    passwordSuccess.value = true;
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
    setTimeout(() => { passwordSuccess.value = false; }, 3000);
  } catch (err: any) {
    if (err.response?.status === 422) {
      passwordErrors.value = err.response.data.errors;
    }
  } finally {
    passwordLoading.value = false;
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString(lang.value === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric'
  });
};
</script>

<template>
  <div class="user-hub" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="cyber-grid"></div>
    
    <div class="content-container">
      <header class="page-header">
        <div class="header-icon-wrapper">
          <Terminal :size="32" class="text-emerald-400" />
        </div>
        <div class="header-text">
          <h1 class="page-title">{{ t.personalTerminal }}</h1>
          <p class="page-subtitle text-emerald-500">{{ t.terminalTagline }}</p>
        </div>
      </header>

      <div v-if="isLoading" class="loading-state">
        <Loader2 class="animate-spin text-emerald-400" :size="48" />
      </div>

      <div v-else class="hub-layout">
        <!-- Sidebar Navigation -->
        <aside class="hub-sidebar">
          <button 
            @click="activeTab = 'identity'" 
            class="tab-btn" 
            :class="{ active: activeTab === 'identity' }"
          >
            <Shield :size="18" />
            <span>{{ t.tabIdentity }}</span>
            <ChevronRight v-if="!isRtl" :size="14" class="ml-auto opacity-50" />
            <ChevronRight v-else :size="14" class="mr-auto opacity-50 rotate-180" />
          </button>
          
          <button 
            @click="activeTab = 'intel'" 
            class="tab-btn" 
            :class="{ active: activeTab === 'intel' }"
          >
            <Activity :size="18" />
            <span>{{ t.tabIntel }}</span>
            <span class="badge">{{ stats.totalPosts }}</span>
          </button>

          <button 
            @click="activeTab = 'scans'" 
            class="tab-btn" 
            :class="{ active: activeTab === 'scans' }"
          >
            <Clock :size="18" />
            <span>{{ t.tabScans }}</span>
            <span class="badge">{{ stats.totalScans }}</span>
          </button>
        </aside>

        <!-- Main Content Area -->
        <main class="hub-content">
          <!-- IDENTITY & SECURITY TAB -->
          <div v-if="activeTab === 'identity'" class="tab-pane">
            <h2 class="pane-title"><User :size="20" class="text-emerald-400" /> {{ t.updateProfile }}</h2>
            
            <form @submit.prevent="updateProfile" class="cyber-form mb-8">
              <div class="form-group">
                <label>Name</label>
                <input type="text" v-model="profileForm.name" class="cyber-input" required />
                <span v-if="profileErrors.name" class="error-text">{{ profileErrors.name[0] }}</span>
              </div>
              
              <div class="form-group">
                <label>Email</label>
                <input type="email" v-model="profileForm.email" class="cyber-input" required />
                <span v-if="profileErrors.email" class="error-text">{{ profileErrors.email[0] }}</span>
              </div>
              
              <div class="form-actions">
                <span v-if="profileSuccess" class="text-emerald-400 text-sm font-bold flex items-center gap-1"><Check :size="14" /> SAVED</span>
                <button type="submit" class="submit-btn" :disabled="profileLoading">
                  <Loader2 v-if="profileLoading" class="animate-spin" :size="16" />
                  <Save v-else :size="16" />
                  <span>{{ t.saveChanges }}</span>
                </button>
              </div>
            </form>

            <h2 class="pane-title"><Key :size="20" class="text-emerald-400" /> {{ t.updatePassword }}</h2>
            
            <form @submit.prevent="updatePassword" class="cyber-form">
              <div class="form-group">
                <label>{{ t.currentPassword }}</label>
                <input type="password" v-model="passwordForm.current_password" class="cyber-input" required />
                <span v-if="passwordErrors.current_password" class="error-text">{{ passwordErrors.current_password[0] }}</span>
              </div>
              
              <div class="form-group">
                <label>{{ t.newPassword }}</label>
                <input type="password" v-model="passwordForm.password" class="cyber-input" required minlength="8" />
                <span v-if="passwordErrors.password" class="error-text">{{ passwordErrors.password[0] }}</span>
              </div>

              <div class="form-group">
                <label>{{ t.confirmPassword }}</label>
                <input type="password" v-model="passwordForm.password_confirmation" class="cyber-input" required minlength="8" />
              </div>
              
              <div class="form-actions">
                <span v-if="passwordSuccess" class="text-emerald-400 text-sm font-bold flex items-center gap-1"><Check :size="14" /> SECURED</span>
                <button type="submit" class="submit-btn" :disabled="passwordLoading">
                  <Loader2 v-if="passwordLoading" class="animate-spin" :size="16" />
                  <Save v-else :size="16" />
                  <span>{{ t.saveChanges }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- MY INTEL TAB -->
          <div v-if="activeTab === 'intel'" class="tab-pane">
            <h2 class="pane-title"><Activity :size="20" class="text-emerald-400" /> {{ t.tabIntel }}</h2>
            
            <div class="stats-mini-grid mb-6">
              <div class="mini-stat"><span class="val text-green-400">{{ stats.approvedPosts }}</span><span class="lbl">{{ t.statsApproved }}</span></div>
              <div class="mini-stat"><span class="val text-amber-400">{{ stats.pendingPosts }}</span><span class="lbl">{{ t.statsPending }}</span></div>
              <div class="mini-stat"><span class="val text-red-400">{{ stats.rejectedPosts }}</span><span class="lbl">{{ t.statsRejected }}</span></div>
            </div>

            <div v-if="myPosts.length === 0" class="empty-state">
              <Activity :size="48" class="text-slate-600 mb-4" />
              <p class="text-slate-400">No intel submissions found.</p>
            </div>

            <div v-else class="my-intel-grid">
              <div v-for="post in myPosts" :key="post.id" class="my-post-card">
                <div class="post-card-header">
                  <h3 class="post-title">{{ post.title }}</h3>
                  <div class="status-badge" :class="post.status">
                    <CheckCircle2 v-if="post.status === 'approved'" :size="12" />
                    <Clock v-else-if="post.status === 'pending'" :size="12" />
                    <XCircle v-else :size="12" />
                    <span>{{ post.status.toUpperCase() }}</span>
                  </div>
                </div>
                <div class="post-date">{{ formatDate(post.created_at) }}</div>
                <div v-if="post.admin_evaluation" class="admin-note">
                  <strong>SYSTEM:</strong> {{ post.admin_evaluation }}
                </div>
              </div>
            </div>
          </div>

          <!-- SCAN LOGS TAB -->
          <div v-if="activeTab === 'scans'" class="tab-pane scans-pane">
            <h2 class="pane-title"><Clock :size="20" class="text-emerald-400" /> {{ t.tabScans }}</h2>
            
            <div class="scans-overview">
              <Shield :size="64" class="text-emerald-500/20 mb-4" />
              <div class="text-4xl font-black text-f1f5f9 mb-2">{{ stats.totalScans }}</div>
              <div class="text-sm font-bold text-slate-400 tracking-widest uppercase mb-8">{{ t.statsTotalScans }}</div>
              
              <button @click="router.push('/history')" class="history-link-btn">
                <Clock :size="18" />
                <span>{{ t.goToHistory }}</span>
                <ExternalLink :size="14" class="opacity-50" />
              </button>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<style scoped>
.user-hub {
  min-height: 100vh;
  background: #050a0a;
  padding: 6rem 1rem 2rem;
  position: relative;
  font-family: var(--font-sans);
}

.cyber-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(16, 185, 129, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(16, 185, 129, 0.03) 1px, transparent 1px);
  background-size: 50px 50px;
  pointer-events: none;
}

.content-container {
  max-width: 1000px;
  margin: 0 auto;
  position: relative;
  z-index: 10;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(16, 185, 129, 0.3);
}

.header-icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.page-title {
  font-size: 2rem;
  font-weight: 900;
  color: #f1f5f9;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.page-subtitle {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.3em;
  font-family: var(--font-mono);
}

.hub-layout {
  display: grid;
  grid-template-columns: 250px 1fr;
  gap: 2rem;
  align-items: start;
}

@media (max-width: 768px) {
  .hub-layout {
    grid-template-columns: 1fr;
  }
}

.hub-sidebar {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: rgba(15, 23, 42, 0.4);
  border: 1px solid rgba(51, 65, 85, 0.5);
  border-radius: 1rem;
  padding: 1rem;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: transparent;
  color: #94a3b8;
  border: 1px solid transparent;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

[dir="rtl"] .tab-btn {
  text-align: right;
}

.tab-btn:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #e2e8f0;
}

.tab-btn.active {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.3);
  color: #34d399;
}

.badge {
  background: rgba(15, 23, 42, 0.8);
  padding: 0.1rem 0.5rem;
  border-radius: 1rem;
  font-size: 0.7rem;
  font-weight: 800;
  margin-inline-start: auto;
}

.tab-btn.active .badge {
  background: rgba(16, 185, 129, 0.2);
}

.hub-content {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(51, 65, 85, 0.6);
  border-radius: 1rem;
  padding: 2rem;
  backdrop-filter: blur(10px);
}

.pane-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px dashed rgba(51, 65, 85, 0.5);
}

.cyber-form .form-group {
  margin-bottom: 1.5rem;
}

.cyber-form label {
  display: block;
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.5rem;
}

.cyber-input {
  width: 100%;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(51, 65, 85, 0.8);
  border-radius: 0.5rem;
  color: #f1f5f9;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.2s;
}

.cyber-input:focus {
  outline: none;
  border-color: rgba(16, 185, 129, 0.5);
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);
}

.error-text {
  display: block;
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.submit-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.5);
  color: #34d399;
  border-radius: 0.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  cursor: pointer;
  transition: all 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background: rgba(16, 185, 129, 0.2);
  box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
}

.submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* My Intel Grid */
.stats-mini-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.mini-stat {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(51, 65, 85, 0.5);
  border-radius: 0.5rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.mini-stat .val {
  font-size: 1.5rem;
  font-weight: 800;
}

.mini-stat .lbl {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
}

.my-intel-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.my-post-card {
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(51, 65, 85, 0.5);
  border-radius: 0.75rem;
  padding: 1.25rem;
}

.post-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.post-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #f1f5f9;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
}

.status-badge.approved { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.3); }
.status-badge.pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.status-badge.rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }

.post-date {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}

.admin-note {
  font-size: 0.8rem;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.05);
  border-left: 2px solid #fbbf24;
  padding: 0.5rem 0.75rem;
  font-family: var(--font-mono);
}

[dir="rtl"] .admin-note {
  border-left: none;
  border-right: 2px solid #fbbf24;
}

.scans-overview {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem 2rem;
}

.history-link-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.5);
  color: #34d399;
  border-radius: 0.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  cursor: pointer;
  transition: all 0.2s;
}

.history-link-btn:hover {
  background: rgba(16, 185, 129, 0.2);
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 5rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 3rem;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 0.5rem;
  border: 1px dashed rgba(51, 65, 85, 0.5);
}
</style>
