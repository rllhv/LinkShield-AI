<script setup lang="ts">
import { Shield, ShieldCheck, X } from 'lucide-vue-next';

const props = defineProps<{
  results: any;
  url: string;
  t: Record<string, string>;
  isRtl: boolean;
}>();
const emit = defineEmits<{ (e: 'close'): void }>();

function getHostname(urlStr: string) {
  try {
    const u = /^https?:\/\//i.test(urlStr) ? urlStr : `https://${urlStr}`;
    return new URL(u).hostname;
  } catch { return urlStr; }
}
</script>

<template>
  <div class="fixed inset-0 z-[100] bg-slate-950 flex flex-col">
    <header class="bg-slate-900 border-b border-slate-800 p-4 flex items-center justify-between gap-6">
      <div class="flex items-center gap-3 shrink-0">
        <Shield class="text-cyan-500" :size="24" />
        <div>
          <h2 class="text-white font-bold leading-none uppercase tracking-tight">{{ t.secureSandbox }}</h2>
          <p class="text-[10px] text-slate-500 font-mono mt-1 uppercase tracking-widest">{{ getHostname(results.url) }}</p>
        </div>
      </div>
      <div class="hidden lg:flex flex-1 items-center justify-center bg-slate-950/50 border border-slate-800 rounded-lg px-4 py-2">
        <p class="text-[10px] text-slate-400 italic text-center leading-tight">
          <span class="text-red-500/80 font-bold uppercase mr-1">{{ isRtl ? 'ملاحظة:' : 'Notice:' }}</span> {{ t.sandboxNote }}
        </p>
      </div>
      <div class="flex items-center gap-4 shrink-0">
        <div class="hidden sm:flex flex-col items-end px-4 border-r border-slate-800">
          <span class="text-[9px] text-slate-500 uppercase font-bold">{{ t.encryptionStatus }}</span>
          <span class="text-xs text-green-400 font-bold uppercase flex items-center gap-1">
            <ShieldCheck :size="12" /> {{ t.activeSecure }}
          </span>
        </div>
        <button @click="emit('close')" class="p-2 bg-slate-800 hover:bg-red-600/20 text-slate-400 hover:text-red-400 rounded-lg transition-all">
          <X :size="24" />
        </button>
      </div>
    </header>

    <main class="flex-1 relative bg-white">
      <iframe :src="results.url" class="w-full h-full border-none" title="Full Screen Live Preview" sandbox="allow-scripts allow-forms allow-popups allow-same-origin" />
      <div class="absolute bottom-6 left-6 right-6 flex items-center justify-between pointer-events-none">
        <div class="bg-red-600/90 backdrop-blur-md px-4 py-2 rounded-lg border border-red-400 text-white text-xs font-bold animate-pulse">
          {{ t.securityShield }}
        </div>
        <div class="bg-slate-900/90 backdrop-blur-md px-4 py-2 rounded-lg border border-slate-700 text-slate-400 text-[10px] uppercase tracking-widest">
          {{ t.sessionMonitor }}
        </div>
      </div>
    </main>
  </div>
</template>
