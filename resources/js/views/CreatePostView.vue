<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAppStore } from '@/stores/app';
import { storeToRefs } from 'pinia';
import { Terminal, Send, Loader2, CheckCircle2, Image as ImageIcon, Link as LinkIcon, ShieldAlert, BookOpen, AlertTriangle, FileText, EyeOff } from 'lucide-vue-next';
import axios from 'axios';
import { translations } from '../translations';
import { useRouter } from 'vue-router';

const appStore = useAppStore();
const { lang } = storeToRefs(appStore);
const router = useRouter();

const isRtl = computed(() => lang.value === 'ar');
const t = computed(() => translations[lang.value]);

const title = ref('');
const content = ref('');
const category = ref('knowledge');
const link = ref('');
const isAnonymous = ref(false);
const imageFile = ref<File | null>(null);
const imagePreview = ref<string | null>(null);

const isSubmitting = ref(false);
const error = ref('');
const success = ref(false);

const handleImageUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    imageFile.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const submit = async () => {
  if (!title.value || !content.value) return;
  
  try {
    isSubmitting.value = true;
    error.value = '';
    
    const formData = new FormData();
    formData.append('title', title.value);
    formData.append('content', content.value);
    formData.append('category', category.value);
    formData.append('is_anonymous', isAnonymous.value ? '1' : '0');
    
    if (link.value) formData.append('link', link.value);
    if (imageFile.value) formData.append('image', imageFile.value);

    await axios.post('/api/intel', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    
    success.value = true;
    setTimeout(() => {
      router.push('/intel');
    }, 2000);
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Transmission failed';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="create-page" :dir="isRtl ? 'rtl' : 'ltr'">
    <div class="cyber-grid"></div>
    
    <div class="form-container">
      <header class="page-header">
        <Terminal :size="28" class="text-cyan-400" />
        <h1 class="page-title">{{ t.createPost }}</h1>
      </header>

      <div v-if="success" class="success-state">
        <CheckCircle2 :size="48" class="text-green-400 mb-4" />
        <h3>{{ t.pendingApproval }}</h3>
      </div>

      <form v-else @submit.prevent="submit" class="cyber-form">
        <div v-if="error" class="error-box">
          {{ error }}
        </div>

        <div class="form-group">
          <label>{{ t.postCategory }}</label>
          <div class="category-grid">
            <label class="category-radio" :class="{ active: category === 'threat' }">
              <input type="radio" v-model="category" value="threat" class="sr-only" />
              <ShieldAlert :size="20" />
              <span>{{ t.catThreat }}</span>
            </label>
            <label class="category-radio" :class="{ active: category === 'advice' }">
              <input type="radio" v-model="category" value="advice" class="sr-only" />
              <AlertTriangle :size="20" />
              <span>{{ t.catAdvice }}</span>
            </label>
            <label class="category-radio" :class="{ active: category === 'knowledge' }">
              <input type="radio" v-model="category" value="knowledge" class="sr-only" />
              <BookOpen :size="20" />
              <span>{{ t.catKnowledge }}</span>
            </label>
            <label class="category-radio" :class="{ active: category === 'story' }">
              <input type="radio" v-model="category" value="story" class="sr-only" />
              <FileText :size="20" />
              <span>{{ t.catStory }}</span>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label>{{ t.postTitle }}</label>
          <input type="text" v-model="title" required class="cyber-input" />
        </div>

        <div class="form-group">
          <label>{{ t.postContent }}</label>
          <textarea v-model="content" required class="cyber-textarea" rows="6"></textarea>
        </div>

        <div class="form-group">
          <label>
            <LinkIcon :size="14" class="inline mr-1" />
            {{ t.postLink }}
          </label>
          <input type="url" v-model="link" class="cyber-input" placeholder="https://" />
        </div>

        <div class="form-group">
          <label>
            <ImageIcon :size="14" class="inline mr-1" />
            {{ t.postImage }}
          </label>
          <input type="file" @change="handleImageUpload" accept="image/*" class="file-input" />
          <div v-if="imagePreview" class="image-preview">
            <img :src="imagePreview" alt="Preview" />
          </div>
        </div>

        <div class="form-group">
          <label class="toggle-label">
            <input type="checkbox" v-model="isAnonymous" class="cyber-checkbox" />
            <EyeOff :size="16" class="text-slate-400" />
            <span class="text-sm font-medium tracking-wide text-slate-300">{{ t.postAnonymous }}</span>
          </label>
        </div>

        <button type="submit" class="submit-btn" :disabled="isSubmitting">
          <Loader2 v-if="isSubmitting" class="animate-spin" :size="18" />
          <Send v-else :size="18" />
          <span>{{ t.submitIntel }}</span>
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.create-page {
  min-height: 100vh;
  background: #0a0a0a;
  padding: 6rem 1rem 2rem;
  position: relative;
  display: flex;
  justify-content: center;
}

.cyber-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(34, 211, 238, 0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(34, 211, 238, 0.02) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}

.form-container {
  width: 100%;
  max-width: 600px;
  position: relative;
  z-index: 10;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #f1f5f9;
  text-transform: uppercase;
}

.cyber-form {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(51, 65, 85, 0.6);
  border-radius: 1rem;
  padding: 2rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  color: #94a3b8;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.5rem;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.category-radio {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(51, 65, 85, 0.8);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  color: #94a3b8;
  margin: 0 !important;
}

.category-radio:hover {
  background: rgba(255, 255, 255, 0.05);
}

.category-radio.active {
  background: rgba(34, 211, 238, 0.15);
  border-color: rgba(34, 211, 238, 0.5);
  color: #22d3ee;
  box-shadow: 0 0 15px rgba(34, 211, 238, 0.1);
}

.cyber-input, .cyber-textarea {
  width: 100%;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(51, 65, 85, 0.8);
  border-radius: 0.5rem;
  color: #f1f5f9;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.2s;
}

.cyber-input:focus, .cyber-textarea:focus {
  outline: none;
  border-color: rgba(34, 211, 238, 0.5);
  box-shadow: 0 0 10px rgba(34, 211, 238, 0.1);
  background: rgba(34, 211, 238, 0.02);
}

.file-input {
  display: block;
  width: 100%;
  padding: 0.5rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px dashed rgba(51, 65, 85, 0.8);
  border-radius: 0.5rem;
  color: #94a3b8;
  cursor: pointer;
}

.image-preview {
  margin-top: 1rem;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 1px solid rgba(51, 65, 85, 0.8);
  max-height: 200px;
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.toggle-label {
  display: flex !important;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  text-transform: none !important;
  letter-spacing: normal !important;
}

.cyber-checkbox {
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 0.25rem;
  border: 1px solid rgba(51, 65, 85, 0.8);
  background: rgba(255, 255, 255, 0.03);
  appearance: none;
  cursor: pointer;
  position: relative;
}

.cyber-checkbox:checked {
  background: #22d3ee;
  border-color: #22d3ee;
}

.cyber-checkbox:checked::after {
  content: '';
  position: absolute;
  left: 6px;
  top: 2px;
  width: 5px;
  height: 10px;
  border: solid #0a0a0a;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.submit-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem;
  background: rgba(34, 211, 238, 0.1);
  border: 1px solid rgba(34, 211, 238, 0.5);
  color: #22d3ee;
  border-radius: 0.5rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  cursor: pointer;
  transition: all 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background: rgba(34, 211, 238, 0.2);
  box-shadow: 0 0 20px rgba(34, 211, 238, 0.2);
}

.submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-box {
  background: rgba(248, 113, 113, 0.1);
  border: 1px solid rgba(248, 113, 113, 0.3);
  color: #f87171;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.success-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 4rem;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(74, 222, 128, 0.3);
  border-radius: 1rem;
  text-align: center;
  color: #f1f5f9;
}

.success-state h3 {
  font-size: 1.25rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  color: #4ade80;
}
</style>
