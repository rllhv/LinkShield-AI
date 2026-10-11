<script setup lang="ts">
import { ShieldAlert } from 'lucide-vue-next';

defineProps<{
  message: string;
  type?: 'success' | 'warning' | 'error' | 'info';
}>();
</script>

<template>
  <div class="hud-toast" :class="type || 'info'">
    <div class="scanline"></div>
    <div class="toast-content">
      <ShieldAlert :size="20" class="icon" />
      <span class="message">{{ message }}</span>
    </div>
  </div>
</template>

<style scoped>
.hud-toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(51, 65, 85, 0.8);
  border-radius: 0.5rem;
  padding: 1rem 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  z-index: 9999;
  overflow: hidden;
  animation: slideIn 0.3s ease-out forwards;
}

[dir="rtl"] .hud-toast {
  right: auto;
  left: 2rem;
}

.scanline {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.5);
  opacity: 0.5;
  animation: scan 2s linear infinite;
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.message {
  font-family: var(--font-mono);
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #f1f5f9;
  text-transform: uppercase;
}

.hud-toast.success { border-color: rgba(16, 185, 129, 0.5); }
.hud-toast.success .icon { color: #10b981; }
.hud-toast.success .scanline { background: #10b981; }

.hud-toast.warning { border-color: rgba(245, 158, 11, 0.5); }
.hud-toast.warning .icon { color: #f59e0b; }
.hud-toast.warning .scanline { background: #f59e0b; }

.hud-toast.error { border-color: rgba(239, 68, 68, 0.5); }
.hud-toast.error .icon { color: #ef4444; }
.hud-toast.error .scanline { background: #ef4444; }

.hud-toast.info { border-color: rgba(34, 211, 238, 0.5); }
.hud-toast.info .icon { color: #22d3ee; }
.hud-toast.info .scanline { background: #22d3ee; }

@keyframes slideIn {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes scan {
  0% { transform: translateY(-100px); }
  100% { transform: translateY(100px); }
}
</style>
