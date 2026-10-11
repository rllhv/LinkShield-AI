<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useAppStore } from '@/stores/app';
import { storeToRefs } from 'pinia';
import { 
  Shield, Terminal, Users, Activity, Loader2, CheckCircle2, XCircle, 
  AlertTriangle, ExternalLink, ShieldAlert, BookOpen, FileText, User, Trash2
} from 'lucide-vue-next';
import axios from 'axios';
import { translations } from '../translations';

const appStore = useAppStore();
const { lang } = storeToRefs(appStore);

const isRtl = computed(() => lang.value === 'ar');
const t = computed(() => translations[lang.value]);

const stats = ref({
  totalUsers: 0,
  totalScans: 0,
  pendingPosts: 0,
  safeRatio: 100,
});

const activeTab = ref<'intel' | 'users'>('intel');
const pendingPosts = ref<any[]>([]);
const networkUsers = ref<any[]>([]);
const isLoading = ref(true);

const fetchDashboardData = async () => {
  try {
    const [statsRes, postsRes, usersRes] = await Promise.all([
      axios.get('/api/admin/stats'),
      axios.get('/api/admin/posts/pending'),
      axios.get('/api/admin/users')
    ]);
    
    stats.value = statsRes.data;
    // Map posts to include local evaluation state
    pendingPosts.value = postsRes.data.data.map((p: any) => ({
      ...p,
      evalCategory: p.category,
      evalText: '',
      isEvaluating: false
    }));
    networkUsers.value = usersRes.data.data;
  } catch (err) {
    console.error('Failed to fetch admin data', err);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchDashboardData();
});

const evaluatePost = async (index: number, status: 'approved' | 'rejected') => {
  const post = pendingPosts.value[index];
  post.isEvaluating = true;
  
  try {
    await axios.patch(`/api/admin/posts/${post.id}/evaluate`, {
      status,
      category: post.evalCategory,
      admin_evaluation: post.evalText || null
    });
    
    // Remove from queue
    pendingPosts.value.splice(index, 1);
    stats.value.pendingPosts--;
  } catch (err) {
    console.error('Evaluation failed', err);
    post.isEvaluating = false;
  }
};

const deleteOperator = async (userId: number) => {
  if (userId === 1) return; // Root admin protection
  if (!confirm('Are you sure you want to terminate this operator?')) return;
  
  try {
    await axios.delete(`/api/admin/users/${userId}`);
    networkUsers.value = networkUsers.value.filter(u => u.id !== userId);
    stats.value.totalUsers--;
  } catch (err) {
    console.error('Failed to delete operator', err);
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString(lang.value === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  });
};

const getCategoryDetails = (category: string) => {
  switch (category) {
    case 'threat': return { icon: ShieldAlert, color: 'text-red-400' };
    case 'advice': return { icon: AlertTriangle, color: 'text-amber-400' };
    case 'story': return { icon: FileText, color: 'text-indigo-400' };
    case 'knowledge':
    default: return { icon: BookOpen, color: 'text-blue-400' };
  }
};
</script>

<template>
  <div class="admin-dashboard" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="overseer-grid"></div>
    
    <div class="content-container">
      <header class="page-header">
        <div class="header-icon-wrapper">
          <Shield :size="32" class="text-amber-400" />
        </div>
        <div class="header-text">
          <h1 class="page-title">{{ t.adminDashboard }}</h1>
          <p class="page-subtitle text-red-500">RESTRICTED ACCESS LEVEL</p>
        </div>
      </header>

      <div v-if="isLoading" class="loading-state">
        <Loader2 class="animate-spin text-red-500" :size="48" />
      </div>

      <template v-else>
        <div class="tabs-container">
          <button 
            @click="activeTab = 'intel'" 
            class="tab-btn" 
            :class="{ active: activeTab === 'intel' }"
          >
            <Terminal :size="18" />
            <span>{{ t.adminTabIntel }}</span>
          </button>
          <button 
            @click="activeTab = 'users'" 
            class="tab-btn" 
            :class="{ active: activeTab === 'users' }"
          >
            <Users :size="18" />
            <span>{{ t.adminTabUsers }}</span>
          </button>
        </div>

        <!-- Telemetry Section -->
        <section class="telemetry-section">
          <h2 class="section-title"><Activity :size="18" /> {{ t.telemetry }}</h2>
          <div class="telemetry-grid">
            <div class="telemetry-card">
              <div class="stat-icon"><Users :size="24" class="text-cyan-400" /></div>
              <div class="stat-info">
                <span class="stat-value">{{ stats.totalUsers }}</span>
                <span class="stat-label">{{ t.totalUsers }}</span>
              </div>
            </div>
            
            <div class="telemetry-card">
              <div class="stat-icon"><Activity :size="24" class="text-green-400" /></div>
              <div class="stat-info">
                <span class="stat-value">{{ stats.totalScans }}</span>
                <span class="stat-label">{{ t.totalScans }}</span>
              </div>
            </div>
            
            <div class="telemetry-card warning">
              <div class="stat-icon"><Terminal :size="24" class="text-amber-400" /></div>
              <div class="stat-info">
                <span class="stat-value">{{ stats.pendingPosts }}</span>
                <span class="stat-label">{{ t.pendingQueue }}</span>
              </div>
            </div>
            
            <div class="telemetry-card safe-ratio">
              <div class="stat-icon"><Shield :size="24" class="text-indigo-400" /></div>
              <div class="stat-info">
                <span class="stat-value">{{ stats.safeRatio }}%</span>
                <span class="stat-label">{{ t.safeRatio }}</span>
              </div>
              <div class="ratio-bar">
                <div class="ratio-fill" :style="{ width: stats.safeRatio + '%' }"></div>
              </div>
            </div>
          </div>
        </section>

        <!-- Pending Queue Section -->
        <section v-if="activeTab === 'intel'" class="queue-section">
          <h2 class="section-title text-amber-400"><Terminal :size="18" /> {{ t.pendingQueue }}</h2>
          
          <div v-if="pendingPosts.length === 0" class="empty-queue">
            <CheckCircle2 :size="48" class="text-green-500 mb-4" />
            <p>{{ t.noPending }}</p>
          </div>

          <div class="queue-grid">
            <div v-for="(post, index) in pendingPosts" :key="post.id" class="eval-card">
              <div class="eval-card-border"></div>
              
              <!-- Original Post Preview -->
              <div class="post-preview">
                <div class="preview-header">
                  <div class="user-meta">
                    <User :size="14" class="text-slate-400" />
                    <span>{{ post.user?.name || (isRtl ? 'عميل مجهول' : 'Anonymous Operator') }} ({{ post.user?.email || 'N/A' }})</span>
                    <span v-if="post.is_anonymous" class="text-red-400 text-xs">[GHOST MODE]</span>
                  </div>
                  <div class="time-meta">{{ formatDate(post.created_at) }}</div>
                </div>
                
                <h3 class="preview-title">{{ post.title }}</h3>
                <p class="preview-content">{{ post.content }}</p>
                
                <div v-if="post.image_url" class="preview-image">
                  <img :src="post.image_url" alt="Attached" />
                </div>
                
                <a v-if="post.link" :href="post.link" target="_blank" class="preview-link">
                  <ExternalLink :size="14" /> {{ post.link }}
                </a>
              </div>

              <!-- Admin Controls -->
              <div class="admin-controls">
                <div class="control-group">
                  <label class="control-label">{{ t.postCategory }} (Override)</label>
                  <select v-model="post.evalCategory" class="admin-select">
                    <option value="threat">{{ t.catThreat }}</option>
                    <option value="advice">{{ t.catAdvice }}</option>
                    <option value="knowledge">{{ t.catKnowledge }}</option>
                    <option value="story">{{ t.catStory }}</option>
                  </select>
                </div>
                
                <div class="control-group">
                  <label class="control-label">System Evaluation</label>
                  <input type="text" v-model="post.evalText" class="admin-input" :placeholder="t.evalPlaceholder" />
                </div>
                
                <div class="action-buttons">
                  <button @click="evaluatePost(index, 'rejected')" class="action-btn btn-reject" :disabled="post.isEvaluating">
                    <XCircle :size="16" />
                    <span>{{ post.isEvaluating ? t.evaluating : t.reject }}</span>
                  </button>
                  <button @click="evaluatePost(index, 'approved')" class="action-btn btn-approve" :disabled="post.isEvaluating">
                    <CheckCircle2 :size="16" />
                    <span>{{ post.isEvaluating ? t.evaluating : t.approve }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Operators Directory Section -->
        <section v-if="activeTab === 'users'" class="users-section">
          <h2 class="section-title text-cyan-400"><Users :size="18" /> {{ t.operatorsList }}</h2>
          
          <div v-if="networkUsers.length === 0" class="empty-queue">
            <User :size="48" class="text-slate-500 mb-4" />
            <p>{{ t.noUsers }}</p>
          </div>

          <div class="users-grid">
            <div v-for="user in networkUsers" :key="user.id" class="user-card">
              <div class="user-card-header">
                <div class="user-identity">
                  <User :size="24" class="text-cyan-400" />
                  <div>
                    <h3 class="user-name">{{ user.name }}</h3>
                    <p class="user-email">{{ user.email }}</p>
                  </div>
                </div>
                <div class="user-role-badge" :class="user.role === 'admin' ? 'admin-badge' : 'user-badge'">
                  {{ user.role.toUpperCase() }}
                </div>
              </div>
              
              <div class="user-stats-grid">
                <div class="user-stat">
                  <span class="stat-lbl">{{ t.operatorScans }}</span>
                  <span class="stat-val text-green-400">{{ user.scans_count || 0 }}</span>
                </div>
                <div class="user-stat">
                  <span class="stat-lbl">{{ t.operatorPosts }}</span>
                  <span class="stat-val text-blue-400">{{ user.posts_count || 0 }}</span>
                </div>
                <div class="user-stat date-stat">
                  <span class="stat-lbl">Joined</span>
                  <span class="stat-val text-slate-400">{{ formatDate(user.created_at) }}</span>
                </div>
              </div>

              <div class="user-actions">
                <button 
                  v-if="user.id !== 1"
                  @click="deleteOperator(user.id)" 
                  class="action-btn btn-reject w-full justify-center"
                >
                  <Trash2 :size="16" />
                  <span>{{ t.deleteOperator }}</span>
                </button>
                <div v-else class="text-xs text-red-500 text-center font-bold tracking-widest uppercase p-2">
                  [ ROOT PROTECTED ]
                </div>
              </div>
            </div>
          </div>
        </section>
      </template>
    </div>
  </div>
</template>

<style scoped>
.admin-dashboard {
  min-height: 100vh;
  background: #050505;
  padding: 6rem 1rem 2rem;
  position: relative;
  font-family: var(--font-sans);
}

.overseer-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(239, 68, 68, 0.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(239, 68, 68, 0.03) 1px, transparent 1px);
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
  margin-bottom: 3rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(239, 68, 68, 0.3);
}

.header-icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.tabs-container {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 1px solid rgba(51, 65, 85, 0.5);
  padding-bottom: 1rem;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #94a3b8;
  border: 1px solid transparent;
  border-radius: 0.5rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: rgba(15, 23, 42, 0.5);
  color: #f1f5f9;
}

.tab-btn.active {
  background: rgba(34, 211, 238, 0.1);
  color: #22d3ee;
  border-color: rgba(34, 211, 238, 0.3);
  box-shadow: 0 0 15px rgba(34, 211, 238, 0.1);
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

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 1.5rem;
}

.telemetry-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-bottom: 4rem;
}

.telemetry-card {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(51, 65, 85, 0.8);
  border-radius: 0.75rem;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.telemetry-card.warning {
  border-color: rgba(245, 158, 11, 0.5);
  background: rgba(245, 158, 11, 0.05);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #f1f5f9;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.safe-ratio {
  flex-direction: column;
  align-items: flex-start;
}

.safe-ratio .stat-info {
  flex-direction: row;
  align-items: baseline;
  gap: 0.5rem;
}

.ratio-bar {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  margin-top: 0.5rem;
  overflow: hidden;
}

.ratio-fill {
  height: 100%;
  background: #22d3ee;
  transition: width 1s ease-in-out;
}

.empty-queue {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem;
  background: rgba(15, 23, 42, 0.4);
  border-radius: 1rem;
  border: 1px dashed rgba(74, 222, 128, 0.3);
  color: #4ade80;
  font-weight: 600;
  letter-spacing: 0.05em;
}

.queue-grid {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.eval-card {
  background: #0a0e17;
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 0.75rem;
  position: relative;
  overflow: hidden;
}

.eval-card-border {
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  width: 3px;
  background: #ef4444;
}

[dir="rtl"] .eval-card-border {
  left: auto;
  right: 0;
}

.post-preview {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(51, 65, 85, 0.5);
}

.preview-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  font-size: 0.8rem;
  color: #94a3b8;
}

.user-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.preview-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 0.5rem;
}

.preview-content {
  color: #cbd5e1;
  font-size: 0.9rem;
  line-height: 1.6;
  white-space: pre-wrap;
  margin-bottom: 1rem;
}

.preview-image {
  margin-bottom: 1rem;
  max-height: 200px;
  overflow: hidden;
  border-radius: 0.5rem;
  border: 1px solid rgba(51, 65, 85, 0.5);
}

.preview-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: #22d3ee;
  background: rgba(34, 211, 238, 0.1);
  padding: 0.25rem 0.75rem;
  border-radius: 0.25rem;
}

.admin-controls {
  padding: 1.5rem;
  background: rgba(15, 23, 42, 0.5);
  display: grid;
  grid-template-columns: 1fr 2fr auto;
  gap: 1.5rem;
  align-items: end;
}

@media (max-width: 768px) {
  .admin-controls {
    grid-template-columns: 1fr;
  }
}

.control-label {
  display: block;
  font-size: 0.7rem;
  font-weight: 700;
  color: #fcd34d;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.5rem;
}

.admin-select, .admin-input {
  width: 100%;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 0.25rem;
  color: #f1f5f9;
  padding: 0.5rem 0.75rem;
  font-family: var(--font-mono);
  font-size: 0.85rem;
}

.admin-select:focus, .admin-input:focus {
  outline: none;
  border-color: #fcd34d;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-reject {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.5);
  color: #ef4444;
}

.btn-reject:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
}

.btn-approve {
  background: rgba(34, 211, 238, 0.1);
  border: 1px solid rgba(34, 211, 238, 0.5);
  color: #22d3ee;
}

.btn-approve:hover:not(:disabled) {
  background: rgba(34, 211, 238, 0.2);
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.3);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 5rem;
}

.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.user-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(51, 65, 85, 0.5);
  border-radius: 0.75rem;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  transition: transform 0.2s, border-color 0.2s;
}

.user-card:hover {
  border-color: rgba(34, 211, 238, 0.4);
  transform: translateY(-2px);
}

.user-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.user-identity {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.user-name {
  font-weight: 700;
  color: #f1f5f9;
  font-size: 1.1rem;
  margin-bottom: 0.25rem;
}

.user-email {
  font-size: 0.8rem;
  color: #94a3b8;
}

.user-role-badge {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  letter-spacing: 0.1em;
}

.admin-badge {
  background: rgba(245, 158, 11, 0.1);
  color: #fcd34d;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.user-badge {
  background: rgba(34, 211, 238, 0.1);
  color: #67e8f9;
  border: 1px solid rgba(34, 211, 238, 0.3);
}

.user-stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  background: rgba(0, 0, 0, 0.3);
  padding: 1rem;
  border-radius: 0.5rem;
}

.user-stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-stat {
  grid-column: span 2;
  border-top: 1px solid rgba(51, 65, 85, 0.5);
  padding-top: 0.75rem;
  margin-top: 0.25rem;
}

.stat-lbl {
  font-size: 0.65rem;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.stat-val {
  font-size: 1.2rem;
  font-weight: 800;
}

.date-stat .stat-val {
  font-size: 0.8rem;
  font-weight: 600;
  font-family: var(--font-mono);
}
</style>
