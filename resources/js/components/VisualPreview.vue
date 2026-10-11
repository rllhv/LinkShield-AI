<script setup lang="ts">
import { ref } from 'vue';
import { Loader2, ShieldAlert, Shield, Maximize2, CameraOff } from 'lucide-vue-next';

const props = defineProps<{
  results: any;
  url: string;
  t: Record<string, string>;
  isRtl: boolean;
}>();
const emit = defineEmits<{ (e: 'maximize'): void }>();

const previewMode = ref<'live' | 'static'>('live');

function getHostname(urlStr: string) {
  try {
    const u = /^https?:\/\//i.test(urlStr) ? urlStr : `https://${urlStr}`;
    return new URL(u).hostname;
  } catch { return urlStr; }
}
</script>

<template>
  <div class="lg:col-span-8 md:col-span-12 neu-card overflow-hidden result-card">
    <!-- Browser chrome bar -->
    <div class="bg-slate-800 px-3 sm:px-4 py-2 border-b border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
      <div class="flex items-center justify-between sm:justify-start gap-4">
        <span class="text-[10px] sm:text-xs text-slate-400 uppercase tracking-tighter truncate max-w-[200px]">
          {{ t.siteObserver }}: {{ getHostname(url) }}
        </span>
        <div class="flex bg-slate-900 rounded-lg p-0.5 border border-slate-700 shrink-0">
          <button @click="previewMode = 'live'" :class="['px-2 sm:px-3 py-1 text-[8px] sm:text-[9px] font-bold uppercase rounded transition-all', previewMode === 'live' ? 'bg-red-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-300']">
            {{ t.liveLink }}
          </button>
          <button @click="previewMode = 'static'" :class="['px-2 sm:px-3 py-1 text-[8px] sm:text-[9px] font-bold uppercase rounded transition-all', previewMode === 'static' ? 'bg-cyan-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-300']">
            {{ t.staticTrace }}
          </button>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button v-if="previewMode === 'live'" @click="emit('maximize')" class="p-1 px-2 text-[10px] font-bold uppercase text-slate-400 hover:text-white bg-slate-700/50 rounded flex items-center gap-1.5 transition-colors group">
          <Maximize2 :size="12" class="group-hover:scale-110 transition-transform" />
          <span class="hidden sm:inline">{{ t.fullScreen }}</span>
        </button>
        <div class="hidden sm:flex gap-1.5 shrink-0">
          <div class="w-2 h-2 rounded-full bg-red-500/50" />
          <div class="w-2 h-2 rounded-full bg-yellow-500/50" />
          <div class="w-2 h-2 rounded-full bg-green-500/50" />
        </div>
      </div>
    </div>

    <!-- Preview area -->
    <div class="relative aspect-video flex items-center justify-center bg-slate-900 group/sandbox">
      <!-- Hover expand (live mode) -->
      <button v-if="previewMode === 'live'" @click="emit('maximize')" class="absolute inset-0 bg-slate-900/0 hover:bg-slate-900/60 z-20 flex flex-col items-center justify-center opacity-0 hover:opacity-100 transition-all cursor-zoom-in p-6 sm:p-10 text-center">
        <div class="bg-slate-900/95 backdrop-blur-md px-6 py-3 rounded-xl border border-cyan-500/50 shadow-2xl flex items-center gap-3 transform -translate-y-4 group-hover/sandbox:translate-y-0 transition-all duration-300 mb-4">
          <Maximize2 class="text-cyan-400 animate-pulse" :size="20" />
          <span class="text-white font-bold uppercase tracking-wider text-sm">{{ t.fullScreen }}</span>
        </div>
        <p class="text-[10px] sm:text-xs text-slate-300 leading-relaxed max-w-sm transform translate-y-4 group-hover/sandbox:translate-y-0 transition-all duration-500 delay-100 italic bg-black/60 backdrop-blur-sm p-4 rounded-xl border border-slate-700/50 shadow-2xl">
          <span class="text-red-400 font-bold uppercase mr-1">{{ isRtl ? 'تنبيه أمني:' : 'Security Note:' }}</span> {{ t.sandboxNote }}
        </p>
      </button>

      <!-- Static mode -->
      <template v-if="previewMode === 'static'">
        <div v-if="results.engines.urlScan?.isUnreachable" class="flex flex-col items-center justify-center p-8 bg-red-500/5 w-full h-full text-center">
          <div class="relative mb-6">
            <div class="absolute inset-0 bg-red-500/20 blur-3xl rounded-full animate-pulse" />
            <ShieldAlert :size="64" class="text-red-500 relative z-10" />
          </div>
          <h4 class="text-red-500 font-bold uppercase tracking-[0.2em] mb-2 text-sm sm:text-lg">{{ t.targetUnreachable }}</h4>
          <p class="text-[10px] sm:text-xs text-red-400/60 leading-relaxed italic">{{ t.dnsFailed }}</p>
        </div>
        <div v-else-if="results.engines.urlScan?.screenshot || results.engines.urlScan?.fallbackScreenshot" class="relative w-full h-full group">
          <img :src="results.engines.urlScan.screenshot || results.engines.urlScan.fallbackScreenshot" alt="Site Preview" class="w-full h-full object-cover transition-opacity duration-700" referrerpolicy="no-referrer" />
          <div v-if="results.engines.urlScan?.status === 'scanning'" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm flex flex-col items-center justify-center pointer-events-none">
            <Loader2 class="animate-spin text-cyan-500 mb-2" :size="32" />
            <span class="text-[10px] uppercase tracking-[0.2em] text-cyan-400">{{ t.capturing }}</span>
          </div>
        </div>
        <div v-else class="flex flex-col items-center justify-center p-8 bg-slate-900/50 w-full h-full text-center">
          <CameraOff :size="64" class="text-slate-700 relative z-10 mb-6" />
          <h4 class="text-slate-400 font-bold uppercase tracking-[0.2em] mb-2 text-sm sm:text-base">{{ t.staticSnapshotUnavailable }}</h4>
          <p class="text-[10px] sm:text-xs text-slate-500 leading-relaxed italic">{{ t.useLivePreview }}</p>
        </div>
      </template>

      <!-- Live mode iframe -->
      <template v-else>
        <div class="w-full h-full flex flex-col items-center justify-center relative">
          <iframe :src="results.url" class="w-full h-full border-none bg-white" title="Live Preview" sandbox="allow-scripts allow-forms allow-popups allow-same-origin" />
          <div :class="['absolute top-4 bg-red-600 text-[9px] font-bold px-2 py-1 rounded border border-red-400/50 shadow-xl pointer-events-none animate-pulse', isRtl ? 'left-4' : 'right-4']">
            {{ t.liveSandbox }}
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
