<script setup lang="ts">
defineProps<{ label: string; value: string }>();
</script>

<template>
  <div class="flex flex-col gap-1">
    <span class="text-[10px] text-slate-500 uppercase tracking-widest">{{ label }}</span>
    <span class="text-sm font-bold text-slate-300 truncate" :title="value">{{ value }}</span>
  </div>
</template>
