<script setup lang="ts">
import { Clock, Shield, ShieldAlert, ShieldCheck, X, Loader2, Download } from 'lucide-vue-next';

const props = defineProps<{
  history: Array<{ id: number; url: string; score: number; search_count: number; updated_at: string }>;
  t: any;
  isRtl: boolean;
  isOpen: boolean;
  hasMore: boolean;
  isLoadingMore: boolean;
}>();

const emit = defineEmits(['select', 'close', 'load-more']);

const getHostname = (urlStr: string) => {
  try {
    return new URL(/^https?:\/\//i.test(urlStr) ? urlStr : `https://${urlStr}`).hostname;
  } catch {
    return urlStr;
  }
};

const handleScroll = (e: Event) => {
  const target = e.target as HTMLElement;
  // Trigger load-more when scrolled within 50px of the bottom
  if (target.scrollTop + target.clientHeight >= target.scrollHeight - 50) {
    emit('load-more');
  }
};

const downloadReport = (id: number) => {
  window.open(`/report/download/${id}?lang=${props.isRtl ? 'ar' : 'en'}`, '_blank');
};
</script>

<template>
  <Transition
    enter-active-class="transition-opacity duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-300"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="isOpen" class="fixed inset-0 z-50 flex justify-end">
      <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm cursor-pointer" @click="emit('close')"></div>
      
      <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        :enter-from-class="isRtl ? '-translate-x-full' : 'translate-x-full'"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300 ease-in"
        leave-from-class="translate-x-0"
        :leave-to-class="isRtl ? '-translate-x-full' : 'translate-x-full'"
        appear
      >
        <div v-if="isOpen" 
          class="relative w-full max-w-sm sm:w-96 h-full bg-[#0f172a] shadow-2xl flex flex-col"
          :class="isRtl ? 'border-r border-cyan-500/20' : 'border-l border-cyan-500/20'"
        >
          <div class="flex items-center justify-between p-4 sm:p-6 border-b border-slate-800">
            <div class="flex items-center gap-2 text-slate-300">
              <Clock class="text-cyan-500 w-5 h-5" />
              <h2 class="text-sm sm:text-base font-bold uppercase tracking-widest">{{ t.scanHistory }}</h2>
            </div>
            <button @click="emit('close')" class="p-2 hover:bg-slate-800 rounded-lg text-slate-400 hover:text-white transition-colors active:scale-95">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3" @scroll="handleScroll">
            <div v-if="history.length === 0 && !isLoadingMore" class="text-xs sm:text-sm text-slate-500 italic text-center py-12">
              {{ t.noHistory }}
            </div>

            <button 
              v-for="item in history" 
              :key="item.id"
              @click="emit('select', item.url)"
              class="w-full group p-4 rounded-xl bg-slate-800/20 border border-slate-800/50 hover:bg-slate-800/50 hover:border-cyan-500/30 transition-all text-left"
              :dir="isRtl ? 'rtl' : 'ltr'"
            >
              <!-- Top Row: URL & Count -->
              <div class="flex items-center justify-between gap-3 mb-3">
                <span :class="['text-slate-200 text-sm font-bold truncate group-hover:text-cyan-400 transition-colors', isRtl ? 'text-right' : 'text-left']" :title="item.url">
                  {{ getHostname(item.url) }}
                </span>
                <span v-if="item.search_count > 1" class="shrink-0 bg-slate-900 text-slate-500 text-[10px] px-1.5 py-0.5 rounded border border-slate-800 font-mono">
                  {{ item.search_count }}x
                </span>
              </div>
              
              <!-- Bottom Row: Meta, Score & Action -->
              <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-800/50">
                <div class="flex items-center gap-2 text-slate-500 text-[10px]">
                  <Clock class="w-3 h-3" />
                  <span>{{ new Date(item.updated_at).toLocaleDateString() }} {{ new Date(item.updated_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
                </div>

                <div class="flex items-center gap-2">
                  <div :class="['flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold border', 
                    item.score > 80 ? 'text-green-400 bg-green-400/5 border-green-400/20' : 
                    item.score === -1 ? 'text-slate-500 bg-slate-500/5 border-slate-500/20' : 
                    'text-red-400 bg-red-400/5 border-red-400/20'
                  ]">
                    <div :class="['w-1 h-1 rounded-full', item.score > 80 ? 'bg-green-400' : item.score === -1 ? 'bg-slate-500' : 'bg-red-400']" />
                    {{ item.score === -1 ? 'N/A' : `${item.score}%` }}
                  </div>

                  <button 
                    @click.stop="downloadReport(item.id)"
                    class="p-1.5 bg-slate-900/50 hover:bg-cyan-500/10 rounded-lg text-slate-500 hover:text-cyan-400 transition-all border border-slate-800 hover:border-cyan-500/20"
                    :title="t.downloadReport"
                  >
                    <Download class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </button>

            <div v-if="isLoadingMore" class="flex justify-center py-4">
              <Loader2 class="w-5 h-5 text-cyan-500 animate-spin" />
            </div>
            
            <div v-if="!hasMore && history.length > 0" class="flex justify-center py-4 text-[10px] text-slate-600 uppercase tracking-widest">
              — End of History —
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>
