<script setup lang="ts">
import { ShieldCheck, ShieldAlert, Activity } from 'lucide-vue-next';

const props = defineProps<{ score: number; t: Record<string, string> }>();

function scoreClass() {
  if (props.score === -1) return 'border-slate-800 shadow-slate-900/50';
  if (props.score >= 80)  return 'border-green-500/40 shadow-green-500/10';
  if (props.score < 50)   return 'border-red-500/40 shadow-red-500/10';
  return 'border-slate-800';
}
function numClass() {
  if (props.score === -1) return 'text-slate-500';
  if (props.score >= 80)  return 'text-green-400';
  if (props.score < 50)   return 'text-red-400';
  return 'text-white';
}
</script>

<template>
  <div :class="[
    'lg:col-span-4 md:col-span-12 neu-card p-6 sm:p-8 flex flex-col items-center justify-between text-center result-card relative overflow-hidden transition-all duration-700',
    score !== -1 && score >= 80 ? 'score-glow-safe border-green-500/30' :
    score !== -1 && score < 50  ? 'score-glow-danger border-red-500/30' : ''
  ]">
    <div :class="[
      'absolute inset-0 opacity-10 pointer-events-none transition-opacity duration-1000',
      score >= 80 ? 'bg-gradient-to-br from-green-500/40 via-transparent to-transparent' :
      score < 50 && score !== -1 ? 'bg-gradient-to-br from-red-500/40 via-transparent to-transparent' : ''
    ]" />

    <div class="relative z-10 w-full flex flex-col items-center">
      <div class="relative mb-6">
        <div :class="['w-40 h-40 sm:w-48 sm:h-48 rounded-full border-8 flex items-center justify-center transition-all duration-700 shadow-inner', scoreClass()]">
          <div class="flex flex-col items-center">
            <span id="trust-score-val" :class="['text-5xl sm:text-6xl font-black transition-colors duration-700', numClass()]">0</span>
            <span v-if="score !== -1" :class="[
              'text-[10px] sm:text-[12px] font-black uppercase tracking-[0.2em] mt-[-4px] transition-colors duration-700',
              score >= 80 ? 'text-green-500/70' : score < 50 ? 'text-red-500/70' : 'text-slate-500'
            ]">
              {{ score >= 80 ? t.clean : score < 50 ? t.danger : t.unknown }}
            </span>
          </div>
        </div>
        <div :class="[
          'absolute -top-2 -right-2 p-2 rounded-xl border transition-all duration-700',
          score === -1  ? 'bg-slate-800 border-slate-700 text-slate-500' :
          score >= 80   ? 'bg-green-500/20 border-green-500/30 text-green-400' :
          score < 50    ? 'bg-red-500/20 border-red-500/30 text-red-400' :
                          'bg-slate-800 border-slate-700 text-cyan-500'
        ]">
          <ShieldCheck v-if="score >= 80" :size="24" />
          <ShieldAlert v-else-if="score < 50 && score !== -1" :size="24" />
          <Activity v-else :size="24" />
        </div>
      </div>

      <h3 class="text-lg sm:text-xl font-bold uppercase tracking-[0.2em] text-slate-200 mb-1">
        {{ score === -1 ? t.integrityFault : t.trustScore }}
      </h3>
      <p class="text-xs sm:text-sm text-slate-500 max-w-[240px] leading-relaxed">
        {{ score === -1 ? t.idFailed : t.safetyCoefficient }}
      </p>
    </div>

    <div class="mt-8 pt-6 border-t border-slate-800/50 w-full relative z-10">
      <div class="flex flex-col items-center gap-1 group">
        <span class="text-[9px] uppercase tracking-[3px] text-slate-600 font-bold group-hover:text-slate-400 transition-colors">{{ t.totalScans }}</span>
        <div class="flex items-center gap-2">
          <div class="flex -space-x-1">
            <div class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse" />
            <div class="w-1.5 h-1.5 rounded-full bg-cyan-700 animate-pulse delay-75" />
          </div>
          <span class="text-xl sm:text-2xl font-black text-slate-300 tracking-tighter">1</span>
        </div>
        <p class="text-[8px] uppercase tracking-wider text-slate-600 max-w-[180px] leading-tight mt-1">{{ t.scansCount }}</p>
      </div>
    </div>
  </div>
</template>
