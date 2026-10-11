<script setup lang="ts">
defineProps<{
  label: string;
  value: string;
  color: 'cyan' | 'green' | 'red' | 'purple' | 'blue';
}>();

const colorMap: Record<string, string> = {
  cyan:   'text-cyan-400 group-hover:text-cyan-300',
  green:  'text-green-400 group-hover:text-green-300',
  red:    'text-red-400 group-hover:text-red-300',
  purple: 'text-purple-400 group-hover:text-purple-300',
  blue:   'text-blue-400 group-hover:text-blue-300',
};
const bgMap: Record<string, string> = {
  cyan:   'bg-cyan-500/5 group-hover:bg-cyan-500/10',
  green:  'bg-green-500/5 group-hover:bg-green-500/10',
  red:    'bg-red-500/5 group-hover:bg-red-500/10',
  purple: 'bg-purple-500/5 group-hover:bg-purple-500/10',
  blue:   'bg-blue-500/5 group-hover:bg-blue-500/10',
};
const barMap: Record<string, string> = {
  cyan:   'bg-cyan-500/50',
  green:  'bg-green-500/50',
  red:    'bg-red-500/50',
  purple: 'bg-purple-500/50',
  blue:   'bg-blue-500/50',
};
const dotMap: Record<string, string> = {
  cyan:   'bg-cyan-500',
  green:  'bg-green-500',
  red:    'bg-red-500',
  purple: 'bg-purple-500',
  blue:   'bg-blue-500',
};
</script>

<template>
  <div :class="['flex flex-col items-center justify-center p-6 rounded-2xl border border-slate-800/50 transition-all duration-500 group relative overflow-hidden', bgMap[color]]">
    <div :class="['mb-2 opacity-50 transition-opacity group-hover:opacity-100 scale-110', colorMap[color]]">
      <slot />
    </div>
    <div class="text-xl sm:text-2xl font-black text-white tracking-tighter mb-1 select-none">{{ value }}</div>
    <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-slate-500 group-hover:text-slate-400 transition-colors text-center line-clamp-1 px-1">
      {{ label }}
    </div>
    <div :class="['absolute bottom-0 left-0 h-[2px] w-0 group-hover:w-full transition-all duration-700', barMap[color]]" />
    <div :class="['absolute top-2 right-2 w-1 h-1 rounded-full animate-pulse', dotMap[color]]" />
  </div>
</template>
