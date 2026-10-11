<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useAppStore } from '@/stores/app';
import { useAuthStore } from '@/stores/auth';
import { storeToRefs } from 'pinia';
import { Shield, ShieldAlert, Loader2, Calendar, FileText, ArrowRight, Download } from 'lucide-vue-next';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { translations } from '../translations';

const appStore = useAppStore();
const authStore = useAuthStore();
const { lang } = storeToRefs(appStore);
const router = useRouter();

const isRtl = computed(() => lang.value === 'ar');
const t = computed(() => translations[lang.value]);

const scans = ref<any[]>([]);
const isLoading = ref(true);
const error = ref<string | null>(null);

const fetchMyScans = async () => {
  try {
    isLoading.value = true;
    const res = await axios.get('/api/my-scans');
    scans.value = res.data.data;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load history';
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  if (!authStore.isAuthenticated) {
    router.push('/login');
    return;
  }
  fetchMyScans();
});

const getScoreColor = (score: number) => {
  if (score >= 80) return 'text-green-400';
  if (score >= 50) return 'text-amber-400';
  return 'text-red-400';
};

const getScoreBg = (score: number) => {
  if (score >= 80) return 'bg-green-400/10 border-green-400/20';
  if (score >= 50) return 'bg-amber-400/10 border-amber-400/20';
  return 'bg-red-400/10 border-red-400/20';
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleString(lang.value === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const viewReport = (url: string) => {
  router.push({ path: '/', query: { scan: url } });
};

const downloadPDF = (id: number) => {
  // Direct download via window.open using the web route
  window.open(`/report/download/${id}?lang=${lang.value}`, '_blank');
};
</script>

<template>
  <div class="history-page" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="cyber-grid"></div>
    <div class="scan-line"></div>
    
    <div class="content-container">
      <header class="page-header">
        <div class="header-icon-wrapper">
          <FileText :size="32" class="text-cyan-400" />
        </div>
        <div>
          <h1 class="page-title">{{ t.encryptedReports }}</h1>
          <p class="page-subtitle">{{ t.operatorHistory }}</p>
        </div>
      </header>

      <div v-if="isLoading" class="loading-state">
        <Loader2 class="animate-spin text-cyan-400" :size="32" />
        <span class="loading-text">DECRYPTING ARCHIVES...</span>
      </div>

      <div v-else-if="error" class="error-state">
        <ShieldAlert :size="32" class="text-red-400" />
        <p>{{ error }}</p>
      </div>

      <div v-else-if="scans.length === 0" class="empty-state">
        <Shield :size="48" class="text-slate-600 mb-4" />
        <p class="text-slate-400 text-lg">{{ t.noHistory }}</p>
      </div>

      <div v-else class="scans-list">
        <div v-for="scan in scans" :key="scan.id" class="scan-card">
          <div class="scan-card-border"></div>
          
          <div class="scan-info">
            <div class="scan-url">
              {{ scan.url }}
            </div>
            <div class="scan-synthesis-label" v-if="scan.aiSummary && scan.aiSummary[lang]">
              {{ scan.aiSummary[lang] }}
            </div>
            <div class="scan-meta">
              <div class="meta-item">
                <Calendar :size="14" />
                <span>{{ formatDate(scan.updated_at) }}</span>
              </div>
            </div>
          </div>

          <div class="scan-actions">
            <div class="score-badge" :class="[getScoreColor(scan.score), getScoreBg(scan.score)]">
              <span class="score-label">{{ t.trustScore }}</span>
              <span class="score-value">{{ scan.score }}</span>
            </div>
            
            <button @click="viewReport(scan.url)" class="action-btn">
              <span>{{ t.viewReport }}</span>
              <ArrowRight :size="16" :class="{ 'rotate-180': isRtl }" />
            </button>

            <button @click="downloadPDF(scan.id)" class="action-btn action-btn--secondary">
              <Download :size="16" />
              <span>{{ t.downloadReport }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.history-page {
  min-height: 100vh;
  background: #0a0a0a;
  padding: 6rem 1rem 2rem;
  position: relative;
  overflow-x: hidden;
}

.cyber-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(34, 211, 238, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(34, 211, 238, 0.02) 1px, transparent 1px);
  background-size: 40px 40px;
  z-index: 0;
  pointer-events: none;
}

.scan-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(34, 211, 238, 0.3), transparent);
  animation: scan 6s linear infinite;
  z-index: 1;
  pointer-events: none;
}

@keyframes scan {
  0% { top: -2px; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 100vh; opacity: 0; }
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
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.15);
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: #f1f5f9;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  text-shadow: 0 0 10px rgba(34, 211, 238, 0.3);
}

.page-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  margin-top: 0.25rem;
}

.loading-state, .error-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px dashed rgba(51, 65, 85, 0.5);
  border-radius: 1rem;
  text-align: center;
}

.loading-text {
  margin-top: 1rem;
  color: #22d3ee;
  font-weight: 700;
  letter-spacing: 0.2em;
  animation: pulse 2s infinite;
}

.scans-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.scan-card {
  position: relative;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(51, 65, 85, 0.6);
  border-radius: 1rem;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  overflow: hidden;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.scan-card:hover {
  transform: translateY(-2px);
  border-color: rgba(34, 211, 238, 0.4);
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5), 0 0 20px rgba(34, 211, 238, 0.1);
}

.scan-card-border {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  background: #22d3ee;
  opacity: 0.5;
  transition: opacity 0.3s;
}

.scan-card:hover .scan-card-border {
  opacity: 1;
  box-shadow: 0 0 10px #22d3ee;
}

[dir="rtl"] .scan-card-border {
  left: auto;
  right: 0;
}

.scan-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.scan-url {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
  word-break: break-all;
  font-family: var(--font-mono);
}

.scan-synthesis-label {
  font-size: 0.85rem;
  color: #cbd5e1;
  margin-top: 0.5rem;
  margin-bottom: 0.75rem;
  line-height: 1.5;
  white-space: pre-wrap;
}

.scan-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  color: #94a3b8;
  font-size: 0.875rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.scan-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(51, 65, 85, 0.4);
}

.score-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  border-radius: 0.5rem;
  border: 1px solid transparent;
  font-weight: 700;
}

.score-label {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  opacity: 0.8;
}

.score-value {
  font-size: 1.125rem;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(34, 211, 238, 0.1);
  border: 1px solid rgba(34, 211, 238, 0.3);
  color: #22d3ee;
  border-radius: 0.5rem;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  background: rgba(34, 211, 238, 0.2);
  box-shadow: 0 0 15px rgba(34, 211, 238, 0.2);
  transform: translateX(2px);
}

.action-btn--secondary {
  flex: 1;
  justify-content: center;
  background: rgba(15, 23, 42, 0.6);
  border-color: rgba(51, 65, 85, 0.5);
  color: #94a3b8;
}

.action-btn--secondary:hover {
  color: #22d3ee !important;
  border-color: rgba(34, 211, 238, 0.3) !important;
  background: rgba(34, 211, 238, 0.1) !important;
}


[dir="rtl"] .action-btn:hover {
  transform: translateX(-2px);
}

.rotate-180 {
  transform: rotate(180deg);
}

@media (min-width: 640px) {
  .scan-card {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
  
  .scan-actions {
    padding-top: 0;
    border-top: none;
    flex-direction: column;
    align-items: flex-end;
  }

  [dir="rtl"] .scan-actions {
    align-items: flex-start;
  }
}
</style>
