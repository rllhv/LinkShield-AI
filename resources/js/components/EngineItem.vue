<script setup lang="ts">
defineProps<{
  name: string;
  status: 'Clean' | 'Danger' | 'Unknown' | 'Pending';
  details: string;
  t: Record<string, string>;
}>();
</script>

<template>
  <div class="flex items-center justify-between p-3 sm:p-4 bg-slate-800/50 rounded-lg sm:rounded-xl border border-slate-700/50 hover:bg-slate-800 transition-colors">
    <div class="text-start pr-2">
      <h4 class="text-sm sm:text-base font-bold text-slate-300">{{ name }}</h4>
      <p class="text-[9px] sm:text-[10px] text-slate-500 uppercase tracking-widest">{{ details }}</p>
    </div>
    <div :class="[
      'px-3 py-1 rounded text-[10px] font-bold uppercase',
      status === 'Clean'   ? 'bg-green-500/10 text-green-400' :
      status === 'Danger'  ? 'bg-red-500/10 text-red-400' :
      status === 'Pending' ? 'bg-cyan-500/10 text-cyan-400' :
                             'bg-slate-700/50 text-slate-500'
    ]">
      {{ status === 'Clean' ? t.clean : status === 'Danger' ? t.danger : status === 'Pending' ? t.pending : t.unknown }}
    </div>
  </div>
</template>
