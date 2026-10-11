<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAppStore } from '@/stores/app';
import { storeToRefs } from 'pinia';
import { translations } from '../translations';
import { Shield, Loader2, LogIn, UserPlus, AlertTriangle } from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const appStore = useAppStore();
const { lang } = storeToRefs(appStore);
const t = computed(() => translations[lang.value]);

const email = ref('');
const password = ref('');
const isSubmitting = ref(false);
const errorMessage = ref('');

const handleLogin = async () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  errorMessage.value = '';
  try {
    await auth.login(email.value, password.value);
    const redirect = (route.query.redirect as string) || '/';
    router.push(redirect);
  } catch (err: any) {
    if (err.response?.status === 422) {
      const errors = err.response.data.errors;
      errorMessage.value = errors?.email?.[0] || errors?.password?.[0] || t.value.loginValidationFailed;
    } else {
      errorMessage.value = err.response?.data?.message || t.value.loginConnectionFailed;
    }
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="auth-page" :dir="lang === 'ar' ? 'rtl' : 'ltr'">
    <div class="cyber-grid"></div>
    <div class="scan-line"></div>
    <div class="particles">
      <div v-for="i in 15" :key="i" class="particle" :style="{
        left: `${Math.random() * 100}%`, top: `${Math.random() * 100}%`,
        animationDelay: `${Math.random() * 5}s`, animationDuration: `${3 + Math.random() * 4}s`
      }"></div>
    </div>

    <div class="auth-container">
      <div class="logo-section">
        <div class="logo-glow"><Shield class="logo-icon" :size="48" /></div>
        <h1 class="logo-text">LinkShield<span class="text-cyan-400">AI</span></h1>
        <p class="logo-tagline">{{ t.loginGateway }}</p>
      </div>

      <div class="auth-card">
        <div class="card-header">
          <div class="header-icon"><LogIn :size="20" /></div>
          <div>
            <h2 class="card-title">{{ t.loginTitle }}</h2>
            <p class="card-subtitle">{{ t.loginSubtitle }}</p>
          </div>
        </div>

        <div v-if="errorMessage" class="error-alert">
          <AlertTriangle :size="16" /><span>{{ errorMessage }}</span>
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
          <div class="input-group">
            <label class="input-label" for="login-email">{{ t.loginEmail }}</label>
            <input id="login-email" v-model="email" type="email" :placeholder="t.loginEmailPlaceholder" required autocomplete="email" :disabled="isSubmitting" class="cyber-input" />
          </div>
          <div class="input-group">
            <label class="input-label" for="login-password">{{ t.loginPassword }}</label>
            <input id="login-password" v-model="password" type="password" placeholder="••••••••••" required autocomplete="current-password" :disabled="isSubmitting" class="cyber-input" />
          </div>
          <button type="submit" class="submit-btn" :disabled="isSubmitting">
            <Loader2 v-if="isSubmitting" class="animate-spin" :size="20" />
            <LogIn v-else :size="20" />
            <span>{{ isSubmitting ? t.loginAuthenticating : t.loginInitSession }}</span>
          </button>
        </form>

        <div class="card-footer">
          <span class="footer-text">{{ t.loginNoClearance }}</span>
          <router-link to="/register" class="footer-link"><UserPlus :size="14" /><span>{{ t.loginCreateAccount }}</span></router-link>
        </div>
      </div>

      <div class="status-bar"><div class="status-dot"></div><span>{{ t.loginStatusBar }}</span></div>
    </div>
  </div>
</template>

<style scoped>
.auth-page { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0a0a0a; position:relative; overflow:hidden; padding:6rem 1rem 2rem 1rem; }
.cyber-grid { position:absolute; inset:0; background-image:linear-gradient(rgba(34,211,238,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(34,211,238,0.03) 1px,transparent 1px); background-size:60px 60px; animation:grid-drift 20s linear infinite; }
@keyframes grid-drift { 0%{transform:translate(0,0)} 100%{transform:translate(60px,60px)} }
.scan-line { position:absolute; top:0; left:0; right:0; height:2px; background:linear-gradient(90deg,transparent,rgba(34,211,238,0.6),transparent); animation:scan 4s ease-in-out infinite; z-index:1; }
@keyframes scan { 0%{top:-2px;opacity:0} 10%{opacity:1} 90%{opacity:1} 100%{top:100vh;opacity:0} }
.particles { position:absolute; inset:0; pointer-events:none; }
.particle { position:absolute; width:2px; height:2px; background:rgba(34,211,238,0.4); border-radius:50%; animation:float-p ease-in-out infinite alternate; }
@keyframes float-p { 0%{transform:translateY(0) scale(1);opacity:0.2} 100%{transform:translateY(-30px) scale(1.5);opacity:0.8} }
.auth-container { position:relative; z-index:10; width:100%; max-width:440px; display:flex; flex-direction:column; align-items:center; gap:2rem; }
.logo-section { text-align:center; display:flex; flex-direction:column; align-items:center; gap:0.75rem; }
.logo-glow { width:80px; height:80px; border-radius:20px; background:linear-gradient(135deg,rgba(34,211,238,0.1),rgba(16,185,129,0.1)); border:1px solid rgba(34,211,238,0.2); display:flex; align-items:center; justify-content:center; box-shadow:0 0 40px rgba(34,211,238,0.15),inset 0 0 20px rgba(34,211,238,0.05); animation:logo-pulse 3s ease-in-out infinite; }
@keyframes logo-pulse { 0%,100%{box-shadow:0 0 40px rgba(34,211,238,0.15),inset 0 0 20px rgba(34,211,238,0.05)} 50%{box-shadow:0 0 60px rgba(34,211,238,0.25),inset 0 0 30px rgba(34,211,238,0.1)} }
.logo-icon { color:#22d3ee; }
.logo-text { font-size:1.75rem; font-weight:800; color:#f1f5f9; letter-spacing:-0.03em; }
.logo-tagline { font-size:0.7rem; color:#64748b; text-transform:uppercase; letter-spacing:0.25em; font-weight:600; }
.auth-card { width:100%; background:linear-gradient(145deg,rgba(15,23,42,0.9),rgba(10,10,10,0.95)); border:1px solid rgba(34,211,238,0.12); border-radius:1.25rem; padding:2rem; backdrop-filter:blur(24px); position:relative; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.6),0 0 80px rgba(34,211,238,0.04); }
.card-header { display:flex; align-items:center; gap:0.875rem; margin-bottom:1.75rem; }
.header-icon { width:40px; height:40px; border-radius:10px; background:rgba(34,211,238,0.1); border:1px solid rgba(34,211,238,0.2); display:flex; align-items:center; justify-content:center; color:#22d3ee; flex-shrink:0; }
.card-title { font-size:0.95rem; font-weight:800; color:#f1f5f9; letter-spacing:0.15em; }
.card-subtitle { font-size:0.7rem; color:#64748b; text-transform:uppercase; letter-spacing:0.1em; margin-top:2px; }
.error-alert { display:flex; align-items:center; gap:0.5rem; padding:0.75rem 1rem; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); border-radius:0.75rem; margin-bottom:1.5rem; color:#fca5a5; font-size:0.8rem; font-weight:500; }
.auth-form { display:flex; flex-direction:column; gap:1.25rem; }
.input-group { display:flex; flex-direction:column; gap:0.5rem; }
.input-label { font-size:0.65rem; font-weight:700; color:#64748b; letter-spacing:0.2em; }
.cyber-input { width:100%; padding:0.875rem 1rem; background:rgba(15,23,42,0.6); border:1px solid rgba(51,65,85,0.5); border-radius:0.75rem; color:#e2e8f0; font-size:0.95rem; font-weight:500; outline:none; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); font-family:inherit; }
.cyber-input::placeholder { color:#475569; }
.cyber-input:focus { border-color:rgba(34,211,238,0.5); box-shadow:0 0 0 3px rgba(34,211,238,0.08),0 0 20px rgba(34,211,238,0.06); background:rgba(15,23,42,0.8); }
.cyber-input:disabled { opacity:0.5; cursor:not-allowed; }
.submit-btn { display:flex; align-items:center; justify-content:center; gap:0.625rem; width:100%; padding:0.875rem 1.5rem; margin-top:0.5rem; background:linear-gradient(135deg,#0891b2,#0e7490); border:1px solid rgba(34,211,238,0.3); border-radius:0.75rem; color:#fff; font-size:0.8rem; font-weight:700; letter-spacing:0.12em; cursor:pointer; transition:all 0.3s; font-family:inherit; position:relative; overflow:hidden; }
.submit-btn:hover:not(:disabled) { background:linear-gradient(135deg,#06b6d4,#0891b2); box-shadow:0 0 30px rgba(34,211,238,0.2),0 10px 40px -10px rgba(34,211,238,0.3); transform:translateY(-1px); }
.submit-btn:active:not(:disabled) { transform:translateY(0) scale(0.98); }
.submit-btn:disabled { opacity:0.6; cursor:not-allowed; }
.card-footer { display:flex; align-items:center; justify-content:center; gap:0.5rem; margin-top:1.75rem; padding-top:1.5rem; border-top:1px solid rgba(51,65,85,0.3); }
.footer-text { font-size:0.8rem; color:#64748b; }
.footer-link { display:flex; align-items:center; gap:0.375rem; color:#22d3ee; font-size:0.8rem; font-weight:600; text-decoration:none; transition:all 0.2s; }
.footer-link:hover { color:#67e8f9; text-shadow:0 0 10px rgba(34,211,238,0.4); }
.status-bar { display:flex; align-items:center; gap:0.5rem; font-size:0.6rem; color:#475569; text-transform:uppercase; letter-spacing:0.2em; font-weight:600; }
.status-dot { width:6px; height:6px; border-radius:50%; background:#10b981; box-shadow:0 0 8px rgba(16,185,129,0.6); animation:blink 2s ease-in-out infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
@media(max-width:480px) { .auth-card{padding:1.5rem} .logo-glow{width:64px;height:64px} .logo-text{font-size:1.5rem} }
</style>
