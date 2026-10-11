<script setup lang="ts">
import { ShieldCheck, ShieldAlert, Shield, X, Activity } from 'lucide-vue-next';

const props = defineProps<{
  results: any;
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
  <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md">
    <div class="w-full max-w-4xl max-h-[90vh] bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl flex flex-col overflow-hidden">
      <div class="p-4 sm:p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
        <h3 class="text-sm sm:text-base font-black uppercase tracking-widest text-white flex items-center gap-3">
          <Activity class="text-cyan-500 animate-pulse" :size="18" />
          {{ t.metadataTable }}
        </h3>
        <button @click="emit('close')" class="p-2 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl transition-all active:scale-90">
          <X :size="24" />
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-4 sm:p-8">
        <table :class="['w-full text-left text-sm', isRtl && 'text-right']">
          <thead>
            <tr class="border-b border-slate-700 text-slate-500">
              <th class="pb-4 pr-4 uppercase text-[10px] tracking-widest">{{ t.indicator }}</th>
              <th class="pb-4 pr-4 uppercase text-[10px] tracking-widest">{{ t.verdict }}</th>
              <th class="pb-4 uppercase text-[10px] tracking-widest">{{ t.source }}</th>
            </tr>
          </thead>
          <tbody class="text-slate-300">
            <tr v-for="[vendor, data] in Object.entries(results.engines.virusTotalResults || {})" :key="vendor" class="border-b border-slate-800/50 hover:bg-slate-800/30 transition-colors group">
              <td class="py-4 pr-4 font-bold text-slate-200 group-hover:text-cyan-400 transition-colors">{{ vendor }}</td>
              <td class="py-4 pr-4">
                <span :class="[
                  'inline-flex items-center gap-1.5 text-[10px] uppercase font-black px-2.5 py-1 rounded tracking-tighter shadow-sm',
                  ['clean','harmless','unrated','undetected','safe'].includes(String((data as any).result).toLowerCase())
                    ? 'text-green-400 bg-green-400/5 border border-green-400/20'
                    : String((data as any).result).toLowerCase() === 'suspicious'
                    ? 'text-yellow-400 bg-yellow-400/5 border border-yellow-400/20'
                    : 'text-red-400 bg-red-400/5 border border-red-400/20'
                ]">
                  <ShieldCheck v-if="['clean','harmless','unrated','undetected','safe'].includes(String((data as any).result).toLowerCase())" :size="12" />
                  <ShieldAlert v-else :size="12" />
                  {{ String((data as any).result || 'unknown').toLowerCase() === 'malicious' ? t.danger :
                     String((data as any).result || 'unknown').toLowerCase() === 'suspicious' ? t.suspicious : t.clean }}
                </span>
              </td>
              <td class="py-4 text-slate-500 text-xs italic opacity-60">{{ t.vtInvestigation }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="p-4 sm:p-6 border-t border-slate-800 bg-slate-900/50 flex justify-end">
        <button @click="emit('close')" class="px-6 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold uppercase text-xs rounded-xl border border-slate-700 transition-all active:scale-95">
          {{ t.close }}
        </button>
      </div>
    </div>
  </div>
</template>
