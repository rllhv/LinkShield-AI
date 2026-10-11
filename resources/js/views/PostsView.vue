<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useAppStore } from '@/stores/app';
import { storeToRefs } from 'pinia';
import { 
  Terminal, Shield, User, Loader2, Plus, ShieldAlert, AlertTriangle, 
  BookOpen, FileText, ExternalLink, Activity, Search, CheckCircle2, HelpCircle 
} from 'lucide-vue-next';
import axios from 'axios';
import { translations } from '../translations';
import { useRouter } from 'vue-router';

const appStore = useAppStore();
const { lang } = storeToRefs(appStore);
const router = useRouter();

const isRtl = computed(() => lang.value === 'ar');
const t = computed(() => translations[lang.value]);

const posts = ref<any[]>([]);
const isLoading = ref(true);
const searchQuery = ref('');
const filterCategory = ref('');
const filterEvaluation = ref('');

const fetchPosts = async () => {
  isLoading.value = true;
  try {
    const res = await axios.get('/api/intel', {
      params: { 
        search: searchQuery.value,
        category: filterCategory.value,
        evaluation: filterEvaluation.value
      }
    });
    posts.value = res.data.data;
  } catch (err) {
    console.error('Failed to fetch intel feed', err);
  } finally {
    isLoading.value = false;
  }
};

let debounceTimer: any;
watch([searchQuery, filterCategory, filterEvaluation], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchPosts();
  }, 400);
});

onMounted(() => {
  fetchPosts();
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString(lang.value === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  });
};

const getCategoryDetails = (category: string) => {
  switch (category) {
    case 'threat': return { icon: ShieldAlert, color: 'text-red-400', bg: 'bg-red-400/10 border-red-400/30', label: t.value.catThreat };
    case 'advice': return { icon: AlertTriangle, color: 'text-amber-400', bg: 'bg-amber-400/10 border-amber-400/30', label: t.value.catAdvice };
    case 'story': return { icon: FileText, color: 'text-indigo-400', bg: 'bg-indigo-400/10 border-indigo-400/30', label: t.value.catStory };
    case 'knowledge':
    default: return { icon: BookOpen, color: 'text-blue-400', bg: 'bg-blue-400/10 border-blue-400/30', label: t.value.catKnowledge };
  }
};
</script>

<template>
  <div class="intel-page" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="cyber-grid"></div>
    
    <div class="content-container">
      <header class="page-header">
        <div class="header-icon-wrapper">
          <Terminal :size="32" class="text-cyan-400" />
        </div>
        <div class="header-text">
          <h1 class="page-title">{{ t.intelFeed }}</h1>
          <p class="page-subtitle">{{ t.intelTagline }}</p>
        </div>
        <div class="header-actions">
          <button @click="router.push('/intel/create')" class="create-btn">
            <Plus :size="20" />
            <span>{{ t.shareIntel }}</span>
          </button>
        </div>
      </header>

      <div class="search-container">
        <div class="search-box">
          <Search :size="18" class="search-icon" />
          <input 
            type="text" 
            v-model="searchQuery" 
            :placeholder="t.searchPlaceholder" 
            class="search-input"
          />
        </div>
        <div class="filter-group">
          <button class="icon-btn" :class="{ active: filterCategory === 'threat' }" @click="filterCategory = filterCategory === 'threat' ? '' : 'threat'" :title="t.catThreat">
            <ShieldAlert :size="18" />
          </button>
          <button class="icon-btn" :class="{ active: filterCategory === 'advice' }" @click="filterCategory = filterCategory === 'advice' ? '' : 'advice'" :title="t.catAdvice">
            <AlertTriangle :size="18" />
          </button>
          <button class="icon-btn" :class="{ active: filterCategory === 'story' }" @click="filterCategory = filterCategory === 'story' ? '' : 'story'" :title="t.catStory">
            <FileText :size="18" />
          </button>
          <button class="icon-btn" :class="{ active: filterCategory === 'knowledge' }" @click="filterCategory = filterCategory === 'knowledge' ? '' : 'knowledge'" :title="t.catKnowledge">
            <BookOpen :size="18" />
          </button>
          
          <div class="filter-divider"></div>

          <button class="icon-btn eval-btn" :class="{ active: filterEvaluation === 'evaluated' }" @click="filterEvaluation = filterEvaluation === 'evaluated' ? '' : 'evaluated'" :title="t.evalEvaluated">
            <CheckCircle2 :size="18" />
          </button>
          <button class="icon-btn eval-btn" :class="{ active: filterEvaluation === 'unevaluated' }" @click="filterEvaluation = filterEvaluation === 'unevaluated' ? '' : 'unevaluated'" :title="t.evalUnevaluated">
            <HelpCircle :size="18" />
          </button>
        </div>
      </div>

      <div v-if="isLoading" class="loading-state">
        <Loader2 class="animate-spin text-cyan-400" :size="32" />
      </div>

      <div v-else-if="posts.length === 0" class="empty-state">
        <Shield :size="48" class="text-slate-600 mb-4" />
        <p class="text-slate-400">{{ t.noIntel }}</p>
      </div>

      <div v-else class="posts-feed">
        <div v-for="post in posts" :key="post.id" class="post-card">
          <div class="post-card-border"></div>
          
          <div class="post-header">
            <div class="author-info">
              <div class="author-avatar" :class="{ 'anonymous': post.is_anonymous }">
                <User v-if="!post.is_anonymous" :size="16" class="text-cyan-400" />
                <Activity v-else :size="16" class="text-slate-400" />
              </div>
              <div>
                <span class="author-name" :class="{ 'text-slate-400 italic': post.is_anonymous }">
                  {{ post.user?.name || (isRtl ? 'عميل مجهول' : 'Anonymous Operator') }}
                </span>
                <span class="post-date">{{ formatDate(post.created_at) }}</span>
              </div>
            </div>

            <div class="badges-container">
              <div class="category-badge" :class="getCategoryDetails(post.category).bg">
                <component :is="getCategoryDetails(post.category).icon" :size="14" :class="getCategoryDetails(post.category).color" />
                <span :class="getCategoryDetails(post.category).color">{{ getCategoryDetails(post.category).label }}</span>
              </div>
            </div>
          </div>
          
          <h2 class="post-title">{{ post.title }}</h2>

          <div v-if="post.admin_evaluation" class="admin-eval-stamp">
            <div class="stamp-border"></div>
            <span class="stamp-label">[{{ t.systemStamp }}]</span>
            <span class="stamp-value">{{ post.admin_evaluation }}</span>
          </div>
          
          <div v-if="post.image_url" class="post-image-container">
            <img :src="post.image_url" alt="Intel Attachment" class="post-image" />
          </div>
          
          <div class="post-content prose prose-invert max-w-none">
            {{ post.content }}
          </div>

          <div v-if="post.link" class="post-footer">
            <a :href="post.link" target="_blank" rel="noopener noreferrer" class="reference-link">
              <ExternalLink :size="16" />
              <span>{{ t.viewLink }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.intel-page {
  min-height: 100vh;
  background: #0a0a0a;
  padding: 6rem 1rem 2rem;
  position: relative;
}

.cyber-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(34, 211, 238, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(34, 211, 238, 0.02) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}

.content-container {
  max-width: 800px;
  margin: 0 auto;
  position: relative;
  z-index: 10;
}

.page-header {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 3rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(34, 211, 238, 0.2);
}

.header-icon-wrapper {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  background: rgba(34, 211, 238, 0.1);
  border: 1px solid rgba(34, 211, 238, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.header-text {
  flex: 1;
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: #f1f5f9;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.page-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  letter-spacing: 0.2em;
}

.create-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: rgba(34, 211, 238, 0.1);
  border: 1px solid rgba(34, 211, 238, 0.5);
  color: #22d3ee;
  border-radius: 0.5rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.create-btn:hover {
  background: rgba(34, 211, 238, 0.2);
  box-shadow: 0 0 15px rgba(34, 211, 238, 0.3);
}

/* Moved search-container and search-box to the bottom for consolidation */


.search-icon {
  position: absolute;
  left: 1rem;
  color: #64748b;
}

[dir="rtl"] .search-icon {
  left: auto;
  right: 1rem;
}

.search-input {
  width: 100%;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(51, 65, 85, 0.6);
  border-radius: 0.75rem;
  padding: 1rem 1rem 1rem 3rem;
  color: #f1f5f9;
  font-size: 1rem;
  backdrop-filter: blur(10px);
  transition: all 0.2s;
}

[dir="rtl"] .search-input {
  padding: 1rem 3rem 1rem 1rem;
}

.search-input:focus {
  outline: none;
  border-color: rgba(34, 211, 238, 0.5);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.1);
}

.filter-group {
  display: flex;
  gap: 0.25rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(51, 65, 85, 0.6);
  border-radius: 0.75rem;
  padding: 0.5rem;
  backdrop-filter: blur(10px);
}

.icon-btn {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  color: #64748b;
  transition: all 0.2s;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
}

.icon-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #f1f5f9;
}

.icon-btn.active {
  background: rgba(34, 211, 238, 0.1);
  color: #22d3ee;
  border-color: rgba(34, 211, 238, 0.3);
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.1);
}

.eval-btn.active {
  background: rgba(245, 158, 11, 0.1);
  color: #fcd34d;
  border-color: rgba(245, 158, 11, 0.3);
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.1);
}

.filter-divider {
  width: 1px;
  background: rgba(51, 65, 85, 0.6);
  margin: 0 0.25rem;
}

.search-container {
  margin-bottom: 2rem;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
  min-width: 250px;
}

@media (min-width: 768px) {
  .search-container {
    gap: 1.5rem;
    flex-wrap: nowrap;
  }
  .filter-group {
    flex-shrink: 0;
  }
}

.posts-feed {
  display: flex;
  flex-direction: column;
  gap: 2.5rem;
}

.post-card {
  position: relative;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(51, 65, 85, 0.6);
  border-radius: 1rem;
  padding: 2rem;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.post-card-border {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #22d3ee, transparent);
  opacity: 0.5;
}

.post-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.author-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.author-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(34, 211, 238, 0.1);
  border: 1px solid rgba(34, 211, 238, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.author-avatar.anonymous {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
}

.author-name {
  display: block;
  font-weight: 600;
  color: #f1f5f9;
}

.post-date {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
}

.badges-container {
  display: flex;
  gap: 0.5rem;
}

.category-badge {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.75rem;
  border-radius: 2rem;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  border-width: 1px;
}

.post-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  margin-bottom: 1rem;
}

.admin-eval-stamp {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.25rem;
  margin-bottom: 1.5rem;
}

.stamp-border {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: #fcd34d;
}

[dir="rtl"] .stamp-border {
  left: auto;
  right: 0;
}

.stamp-label {
  font-size: 0.7rem;
  color: #fcd34d;
  font-family: var(--font-mono);
  letter-spacing: 0.1em;
}

.stamp-value {
  font-weight: 700;
  color: #f1f5f9;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.post-image-container {
  margin-bottom: 1.5rem;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid rgba(51, 65, 85, 0.8);
  background: rgba(0, 0, 0, 0.2);
}

.post-image {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  display: block;
}

.post-content {
  color: #cbd5e1;
  line-height: 1.7;
  white-space: pre-wrap;
  margin-bottom: 1.5rem;
}

.post-footer {
  padding-top: 1rem;
  border-top: 1px solid rgba(51, 65, 85, 0.4);
}

.reference-link {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #22d3ee;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
  padding: 0.5rem 1rem;
  background: rgba(34, 211, 238, 0.05);
  border-radius: 0.5rem;
  border: 1px solid rgba(34, 211, 238, 0.2);
}

.reference-link:hover {
  background: rgba(34, 211, 238, 0.15);
  text-decoration: none;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem;
  background: rgba(15, 23, 42, 0.4);
  border-radius: 1rem;
  border: 1px dashed rgba(51, 65, 85, 0.5);
}
</style>
