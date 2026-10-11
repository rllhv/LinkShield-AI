<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/stores/auth';
import { Search, Loader2, Shield, Activity, Terminal as TerminalIcon, ShieldCheck, ShieldAlert, Copy, Check, X, Download } from 'lucide-vue-next';
import gsap from 'gsap';
import { translations } from '../translations';
import { useAppStore } from '@/stores/app';
import { useRoute } from 'vue-router';
import NetworkBackground from '../components/NetworkBackground.vue';
import TrustScoreCard from '../components/TrustScoreCard.vue';
import VisualPreview from '../components/VisualPreview.vue';
import InfraItem from '../components/InfraItem.vue';
import EngineItem from '../components/EngineItem.vue';
import StatBox from '../components/StatBox.vue';
import VendorModal from '../components/VendorModal.vue';
import FullScreenOverlay from '../components/FullScreenOverlay.vue';
import axios from 'axios';

const appStore = useAppStore();
const authStore = useAuthStore();
const { lang, isSidebarOpen } = storeToRefs(appStore);
const route = useRoute();

const url = ref('');
const isScanning = ref(false);
const scanStage = ref<'idle' | 'processing' | 'completed'>('idle');
const logs = ref<string[]>([]);
const results = ref<any>(null);
const isMaximized = ref(false);
const showAllVendors = ref(false);

const stats = ref({
  totalAnalyses: 0,
  safeAnalyses: 0,
  maliciousAnalyses: 0,
  aiAnalyses: 0,
  activeNodes: 0
});

const aiError = ref<string | null>(null);
const backendError = ref<string | null>(null);
const isCopied = ref(false);

const t = computed(() => translations[lang.value]);

const copyReport = () => {
  const report = results.value.aiSummary?.[lang.value] || '';
  if (!report) return;
  navigator.clipboard.writeText(report);
  isCopied.value = true;
  setTimeout(() => { isCopied.value = false; }, 2000);
};

const downloadPDF = (id: number) => {
  window.open(`/report/download/${id}?lang=${lang.value}`, '_blank');
};

const containerRef = ref<HTMLDivElement | null>(null);
const inputRef = ref<HTMLDivElement | null>(null);
const logoRef = ref<HTMLHeadingElement | null>(null);

// Auto-dismiss errors after 10 seconds
watch(backendError, (val) => {
  if (val) setTimeout(() => { backendError.value = null; }, 10000);
});
watch(aiError, (val) => {
  if (val) setTimeout(() => { aiError.value = null; }, 10000);
});

const fetchStats = async () => {
  try {
    const res = await axios.get('/api/stats');
    stats.value = res.data;
  } catch (err) {
    console.error('Failed to fetch stats', err);
  }
};

onMounted(() => {
  fetchStats();

  gsap.from(logoRef.value, { y: -50, opacity: 0, duration: 1, ease: 'power3.out' });
  gsap.from(inputRef.value, { scale: 0.8, opacity: 0, duration: 1.2, ease: 'elastic.out(1, 0.75)', delay: 0.2 });

  if (route.query.scan) {
    url.value = route.query.scan as string;
    setTimeout(() => {
      handleScan();
    }, 500);
  }
});

watch(() => route.query.scan, (newScanUrl) => {
  if (newScanUrl && typeof newScanUrl === 'string') {
    url.value = newScanUrl;
    if (!isScanning.value) {
      handleScan();
    }
  }
});

const getHostname = (urlStr: string) => {
  try {
    return new URL(/^https?:\/\//i.test(urlStr) ? urlStr : `https://${urlStr}`).hostname;
  } catch { return urlStr; }
};

const addLog = (msg: string) => {
  logs.value = [...logs.value.slice(-14), `> ${msg}`];
};

const calculateTrustScore = (data: any) => {
  if (data.engines.infra?.status === 'unresolved' || data.engines.urlScan?.isUnreachable) return -1;
  let score = 100;
  if (data.engines.virusTotal && data.engines.virusTotal.status !== 'not_scanned') {
    let mInd = 0, sInd = 0;
    if (data.engines.virusTotalResults) {
      const vals = Object.values(data.engines.virusTotalResults);
      mInd = vals.filter((r: any) => r.result === 'malicious' || r.category === 'malicious').length;
      sInd = vals.filter((r: any) => r.result === 'suspicious' || r.category === 'suspicious').length;
    }
    const tMal = Math.max(data.engines.virusTotal.malicious || 0, mInd);
    const tSus = Math.max(data.engines.virusTotal.suspicious || 0, sInd);
    if (tMal > 0) score -= (tMal <= 3) ? (tMal * 10) : (30 + (tMal - 3) * 5);
    if (tSus > 0) score -= (tSus * 2);
    if (score >= 100 && (tMal > 0 || tSus > 0)) score = 98;
  }
  if (data.engines.safeBrowsing?.length > 0) score -= 50;
  return Math.max(0, Math.min(100, Math.round(score)));
};

const handleScan = async (e?: Event) => {
  if (e) e.preventDefault();
  if (!url.value || isScanning.value) return;

  let targetUrl = url.value.trim().replace(/\s+/g, '');
  if (!/^https?:\/\//i.test(targetUrl)) targetUrl = `https://${targetUrl}`;

  url.value = targetUrl;
  isScanning.value = true;
  scanStage.value = 'processing';
  logs.value = [];
  results.value = null;

  try {
    const urlObj = new URL(targetUrl);
    const host = urlObj.hostname;
    const protocolMatches = targetUrl.match(/https?:\/\//gi);
    if (protocolMatches && protocolMatches.length > 1) {
       addLog(t.value.invalidUrl);
       isScanning.value = false;
       return;
    }
    if (!host.includes('.') || host.split('.').pop()!.length < 2) {
      addLog(t.value.invalidUrl);
      isScanning.value = false;
      return;
    }
  } catch {
    addLog(t.value.invalidUrl);
    isScanning.value = false;
    return;
  }

  gsap.to(inputRef.value, { scale: 0.98, duration: 0.1, yoyo: true, repeat: 1 });

  const steps = [
    { msg: t.value.initializing, delay: 1200 },
    { msg: t.value.vtConnect, delay: 1800 },
    { msg: t.value.urlScanFetch, delay: 2500 },
    { msg: t.value.gsbQuery, delay: 1500 },
    { msg: t.value.headerAnalytic, delay: 1200 },
    { msg: t.value.aiProcess, delay: 3000 }
  ];

  let logsRunning = true;
  const playLogs = async () => {
    for (const step of steps) {
      if (!logsRunning) break;
      addLog(step.msg);
      await new Promise(r => setTimeout(r, step.delay));
    }
  };
  playLogs();

  try {
    const startTime = Date.now();
    const res = await axios.post('/api/scan', { url: targetUrl });
    logsRunning = false;
    
    if (Date.now() - startTime < 1500) {
      addLog(t.value.recentCache || 'Loaded from Cache...');
      await new Promise(r => setTimeout(r, 600));
    }
    results.value = res.data;
    scanStage.value = 'completed';

    if (results.value.aiSummary?.error) {
       aiError.value = results.value.aiSummary.error;
    }

    const score = calculateTrustScore(results.value);
    
    if (!authStore.isAuthenticated) {
       const localScans = JSON.parse(localStorage.getItem('local_scans') || '[]');
       if (!localScans.includes(targetUrl)) {
           localScans.push(targetUrl);
           localStorage.setItem('local_scans', JSON.stringify(localScans));
       }
    }

    appStore.fetchHistory(1, true);
    fetchStats();

    setTimeout(() => {
      gsap.from('.result-card', { y: 50, opacity: 0, stagger: 0.1, duration: 0.8, ease: 'power3.out' });
      const scoreObj = { val: 0 };
      gsap.to(scoreObj, {
        val: score === -1 ? 0 : score,
        duration: 1.5,
        onUpdate: () => {
          const el = document.getElementById('trust-score-val');
          if (el) el.innerText = Math.round(scoreObj.val).toString();
        }
      });
    }, 100);
  } catch (err: any) {
    console.error('Scan Error:', err);
    addLog(t.value.connFailed);
    
    let errorMsg = err.response?.data?.message || err.message || 'Unknown network error';
    if (err.response?.status === 500) {
       errorMsg = 'Backend System Failure (500). Please check server logs.';
    } else if (err.response?.status === 504) {
       errorMsg = 'Gateway Timeout (504). The scan is likely still processing in the background. Please wait a moment and check your Scan History sidebar.';
    }
    backendError.value = errorMsg;
  } finally {
    isScanning.value = false;
  }
};
</script>

<template>
  <div ref="containerRef" :dir="lang === 'ar' ? 'rtl' : 'ltr'" class="min-h-screen flex flex-col items-center pt-20 pb-12 px-4 sm:px-6 font-sans relative">
    <NetworkBackground />

    <header class="mb-8 sm:mb-12 text-center w-full mt-12 sm:mt-16 z-10 max-w-6xl">
      <h1 ref="logoRef" class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tighter text-white flex items-center justify-center gap-2 sm:gap-3">
        <Shield class="w-8 h-8 sm:w-10 sm:h-10 text-cyan-500" />
        {{ t.name }}
      </h1>
      <p class="mt-2 text-slate-400 text-base sm:text-lg max-w-lg mx-auto leading-relaxed px-4">{{ t.tagline }}</p>
    </header>

    <section class="w-full max-w-3xl flex flex-col items-center gap-6 sm:gap-8">
      <div ref="inputRef" class="w-full px-2 sm:px-0">
        <form @submit="handleScan" class="flex flex-col sm:relative gap-3 sm:gap-0">
          <input v-model="url" type="text" :placeholder="t.placeholder" :disabled="isScanning" :class="['w-full bg-[#1e293b] border-2 border-slate-700 rounded-xl sm:rounded-2xl py-4 sm:py-6 text-lg sm:text-xl text-white focus:outline-none focus:border-cyan-500/50 transition-all shadow-2xl placeholder:text-slate-500 disabled:opacity-50 disabled:cursor-not-allowed', lang === 'ar' ? (scanStage === 'completed' ? 'sm:pl-80 pr-6' : 'sm:pl-44 pr-6') : (scanStage === 'completed' ? 'pl-6 sm:pr-80' : 'pl-6 sm:pr-44')]" required />
          
          <div :class="['sm:absolute sm:top-3 sm:bottom-3 flex flex-col sm:flex-row gap-2', lang === 'ar' ? 'sm:left-3' : 'sm:right-3']">
            <button 
              v-if="scanStage === 'completed' && results?.id"
              type="button"
              @click="downloadPDF(results.id)"
              class="px-4 sm:px-6 py-4 sm:py-0 bg-slate-800 hover:bg-slate-700 text-cyan-400 rounded-xl font-bold flex items-center justify-center gap-2 transition-all border border-slate-700 hover:border-cyan-500/30 active:scale-95"
            >
              <Download :size="18" />
              <span class="text-xs sm:text-sm uppercase tracking-wider">{{ t.downloadReport }}</span>
            </button>

            <button type="submit" :disabled="isScanning" class="px-8 py-4 sm:py-0 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl font-bold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50">
              <Loader2 v-if="isScanning" class="animate-spin" />
              <Search v-else />
              {{ isScanning ? t.scanning : t.investigate }}
            </button>
          </div>
        </form>
      </div>

      <div v-if="scanStage === 'processing'" class="w-full max-w-2xl neu-card p-6 border-cyan-500/20 bg-slate-900/50 backdrop-blur-sm">
        <div class="flex items-center gap-2 mb-4 text-cyan-400">
          <TerminalIcon :size="18" />
          <span class="text-xs uppercase tracking-widest">{{ t.terminalFeed }}</span>
        </div>
        <div class="space-y-1 overflow-hidden h-40">
          <div v-for="(log, i) in logs" :key="i" class="terminal-text text-sm text-slate-300">{{ log }}</div>
          <div class="terminal-text text-sm text-cyan-400 animate-pulse">{{ lang === 'ar' ? '<' : '>' }} <span class="bg-cyan-400 w-2 h-4 inline-block align-middle" /></div>
        </div>
      </div>
    </section>

    <section v-if="scanStage === 'completed' && results" class="w-full max-w-6xl mt-16 grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-min">
      <TrustScoreCard :score="calculateTrustScore(results)" :t="t" />
      <VisualPreview :results="results" :url="url" :t="t" :isRtl="lang === 'ar'" @maximize="isMaximized = true" />

      <div :class="['md:col-span-12 neu-card p-6 sm:p-8 result-card border-l-4 border-l-cyan-500', lang === 'ar' && 'border-l-0 border-r-4 border-r-cyan-500']">
        <div class="flex items-center gap-3 mb-4">
          <div class="p-2 bg-cyan-500/10 rounded-lg shrink-0">
            <Activity class="text-cyan-500" :size="24" />
          </div>
          <div>
            <h3 class="text-base sm:text-lg font-bold uppercase tracking-wider text-white">{{ t.aiSynthesis }}</h3>
            <p class="text-[10px] sm:text-xs text-slate-500 uppercase">{{ t.genIntel }}</p>
          </div>
          <button 
            @click="copyReport"
            :class="['p-2 rounded-lg transition-all active:scale-95 flex items-center gap-2 text-[10px] uppercase font-bold tracking-widest', lang === 'ar' ? 'mr-auto' : 'ml-auto', isCopied ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white border border-slate-700']"
          >
            <Check v-if="isCopied" :size="14" />
            <Copy v-else :size="14" />
            <span class="hidden sm:inline">{{ isCopied ? t.copied : t.copy }}</span>
          </button>
        </div>
        <div class="text-slate-300 leading-relaxed text-base sm:text-lg italic whitespace-pre-wrap">
          "{{ results.aiSummary?.[lang] || t.processingHeuristics }}"
        </div>
      </div>

      <div class="md:col-span-12 lg:col-span-6 neu-card p-6 sm:p-8 result-card">
        <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-500 mb-6 flex items-center gap-2">
          <Activity :size="16" class="text-cyan-500" /> {{ t.registryInfra }}
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 sm:gap-y-6">
          <InfraItem :label="t.targetDomain" :value="results.engines.infra?.domain || getHostname(url)" />
          <InfraItem :label="t.serverProtocol" :value="results.engines.infra?.status === 'unresolved' ? t.hostDown : (results.engines.infra?.server || t.undetected)" />
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <span class="text-[9px] sm:text-[10px] text-slate-500 uppercase tracking-widest">{{ t.publicIp }}</span>
              <span v-if="results.engines.infra?.ip?.includes(':')" class="text-[7px] sm:text-[8px] bg-slate-800 text-slate-400 px-1 py-0.5 rounded border border-slate-700">{{ t.ipv6 }}</span>
            </div>
            <span :class="['text-sm font-bold truncate', results.engines.infra?.status === 'unresolved' ? 'text-red-400' : 'text-slate-300']" :title="results.engines.infra?.ip">
              {{ results.engines.infra?.status === 'unresolved' ? t.dnsFailure : (results.engines.infra?.ip || t.identifying) }}
            </span>
          </div>
          <InfraItem :label="t.geographicHub" :value="results.engines.infra?.status === 'unresolved' ? t.unidentifiable : (results.engines.infra?.country ? `${results.engines.infra.city ? results.engines.infra.city + ', ' : ''}${results.engines.infra.country}` : t.anycast)" />
          <div class="sm:col-span-2">
            <InfraItem :label="t.hostingLogic" :value="results.engines.infra?.status === 'unresolved' ? t.noPath : (results.engines.infra?.asnName || results.engines.infra?.asn || t.proprietaryNetwork)" />
          </div>
        </div>
      </div>

      <div class="md:col-span-12 lg:col-span-6 neu-card p-6 sm:p-8 result-card">
        <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-500 mb-6 flex justify-between items-center">
          {{ t.securityVendors }}
          <span class="px-2 py-0.5 bg-slate-800 rounded text-[9px] sm:text-[10px] text-slate-400">{{ t.liveFeed }}</span>
        </h3>
        <div class="space-y-3 sm:space-y-4">
          <EngineItem name="VirusTotal" :status="results.engines.virusTotal ? (results.engines.virusTotal.status === 'not_scanned' ? 'Pending' : (results.engines.virusTotal.malicious > 0 ? 'Danger' : 'Clean')) : 'Unknown'" :details="results.engines.virusTotal ? (results.engines.virusTotal.status === 'not_scanned' ? t.notScanned : `${results.engines.virusTotal.malicious} flagged engines`) : 'No data'" :t="t" />
          <EngineItem name="Google Safe Browsing" :status="results.engines.safeBrowsing?.length > 0 ? 'Danger' : 'Clean'" :details="results.engines.safeBrowsing?.length > 0 ? t.listed : t.unlisted" :t="t" />
          <EngineItem name="URLScan Heuristics" status="Clean" :details="t.metadataComplete" :t="t" />
        </div>
      </div>
      
      <div class="md:col-span-12 neu-card p-6 result-card">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xs sm:text-sm font-bold uppercase tracking-widest text-slate-500 flex items-center gap-2">
            <Shield :size="16" class="text-cyan-500" /> {{ t.metadataTable }}
          </h3>
          <button v-if="Object.keys(results.engines.virusTotalResults || {}).length > 10" @click="showAllVendors = true" class="px-3 py-1 bg-cyan-600/10 hover:bg-cyan-600/20 text-cyan-400 text-[10px] font-bold uppercase rounded border border-cyan-600/30 transition-all">
            {{ t.seeAll }}
          </button>
        </div>
        <div class="overflow-x-auto">
          <table :class="['w-full text-left text-sm', lang === 'ar' && 'text-right']">
            <thead>
              <tr class="border-b border-slate-700 text-slate-500">
                <th class="pb-3 pr-4 uppercase text-[10px] tracking-widest">{{ t.indicator }}</th>
                <th class="pb-3 pr-4 uppercase text-[10px] tracking-widest">{{ t.verdict }}</th>
                <th class="pb-3 uppercase text-[10px] tracking-widest">{{ t.source }}</th>
              </tr>
            </thead>
            <tbody class="text-slate-300">
              <tr v-for="[vendor, data] in Object.entries(results.engines.virusTotalResults || {}).slice(0, 5)" :key="vendor" class="border-b border-slate-800/50">
                <td class="py-3 pr-4 font-medium">{{ vendor }}</td>
                <td class="py-3 pr-4 text-[10px] uppercase font-black">{{ String((data as any).result).toLowerCase() === 'malicious' ? t.danger : t.clean }}</td>
                <td class="py-3 text-slate-500 text-xs italic">{{ t.vtInvestigation }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="w-full max-w-6xl mt-20 sm:mt-32 px-4">
      <div class="flex items-center gap-3 mb-8 sm:mb-12">
        <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent via-slate-800 to-slate-800" />
        <span class="text-[10px] sm:text-xs font-black uppercase tracking-[0.3em] text-slate-500 whitespace-nowrap">{{ t.globalStats }}</span>
        <div class="h-[1px] flex-1 bg-gradient-to-l from-transparent via-slate-800 to-slate-800" />
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-6 sm:gap-8 overflow-hidden">
        <StatBox :label="t.totalAnalyses" :value="stats.totalAnalyses.toLocaleString()" color="cyan"><Search :size="14" /></StatBox>
        <StatBox :label="t.safeAnalyses" :value="stats.safeAnalyses.toLocaleString()" color="green"><ShieldCheck :size="14" /></StatBox>
        <StatBox :label="t.maliciousAnalyses" :value="stats.maliciousAnalyses.toLocaleString()" color="red"><ShieldAlert :size="14" /></StatBox>
        <StatBox :label="t.aiAnalyses" :value="stats.aiAnalyses.toLocaleString()" color="purple"><TerminalIcon :size="14" /></StatBox>
        <StatBox :label="t.activeNodes" :value="stats.activeNodes.toLocaleString()" color="blue"><Activity :size="14" /></StatBox>
      </div>
    </section>

    <footer class="mt-16 sm:mt-24 border-t border-slate-800 pt-8 sm:pt-12 w-full max-w-6xl pb-8 sm:pb-12 flex flex-col md:flex-row justify-between items-center gap-6 px-4">
      <div class="flex items-center gap-2 text-slate-500 text-[10px] sm:text-xs uppercase tracking-widest">
        <Activity :size="14" class="animate-pulse" /> {{ t.systemStatus }}
      </div>
      <div class="text-slate-600 text-[9px] sm:text-[10px] uppercase tracking-tighter flex flex-wrap justify-center gap-4 sm:gap-8">
        <span>{{ t.dbVersion }}</span>
        <span>{{ t.openStandards }}</span>
      </div>
    </footer>

    <VendorModal v-if="showAllVendors && results?.engines.virusTotalResults" :results="results" :url="url" :t="t" :isRtl="lang === 'ar'" @close="showAllVendors = false" />
    <FullScreenOverlay v-if="isMaximized && results" :results="results" :url="url" :t="t" :isRtl="lang === 'ar'" @close="isMaximized = false" />
    
    <Transition
      enter-active-class="transition duration-500 ease-out"
      enter-from-class="transform translate-y-10 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-300 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="aiError" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4">
        <div class="bg-red-950/40 backdrop-blur-xl border border-red-500/50 rounded-xl p-4 flex items-start gap-4 shadow-2xl shadow-red-900/20">
          <div class="p-2 bg-red-500/20 rounded-lg">
            <ShieldAlert :size="20" class="text-red-400" />
          </div>
          <div class="flex-1">
            <h4 class="text-red-400 font-bold text-xs uppercase tracking-widest mb-1">Gemini AI Engine Fault</h4>
            <p class="text-red-200/80 text-[11px] leading-relaxed font-mono">{{ aiError }}</p>
          </div>
          <button @click="aiError = null" class="text-red-400/50 hover:text-red-400 transition-colors">
            <X :size="16" />
          </button>
        </div>
      </div>
    </Transition>

    <Transition
      enter-active-class="transition duration-500 ease-out"
      enter-from-class="transform translate-y-10 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-300 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="backendError" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[100] w-full max-w-md px-4">
        <div class="bg-slate-950/95 backdrop-blur-2xl border-2 border-amber-500/50 rounded-2xl p-5 flex items-start gap-4 shadow-2xl ring-1 ring-amber-500/20">
          <div class="p-2.5 bg-amber-500/20 rounded-xl">
            <Activity :size="24" class="text-amber-400" />
          </div>
          <div class="flex-1">
            <h4 class="text-amber-400 font-black text-xs uppercase tracking-[0.2em] mb-1.5">System Fault Detected</h4>
            <p class="text-slate-300 text-xs leading-relaxed font-medium">{{ backendError }}</p>
            <button @click="backendError = null" class="mt-3 text-[10px] font-bold text-amber-400/60 hover:text-amber-400 uppercase tracking-widest flex items-center gap-1 transition-colors">
              Dismiss Notification <X :size="12" />
            </button>
          </div>
          <button @click="backendError = null" class="p-1.5 hover:bg-slate-800 rounded-lg text-slate-500 hover:text-white transition-all">
            <X :size="18" />
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
