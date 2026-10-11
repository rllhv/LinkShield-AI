import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useAppStore = defineStore('app', () => {
  const isSidebarOpen = ref(false);
  const initialLang = (localStorage.getItem('lang') as 'en' | 'ar') || 'en';
  const lang = ref<'en' | 'ar'>(initialLang);

  // Set initial attributes immediately
  if (typeof document !== 'undefined') {
    document.documentElement.dir = initialLang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = initialLang;
  }

  const history = ref<any[]>([]);
  const currentHistoryPage = ref(1);
  const hasMoreHistory = ref(true);
  const isLoadingHistory = ref(false);

  const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
  };

  const openSidebar = () => {
    isSidebarOpen.value = true;
    if (history.value.length === 0) {
      fetchHistory(1, true);
    }
  };

  const closeSidebar = () => {
    isSidebarOpen.value = false;
  };

  const setLanguage = (newLang: 'en' | 'ar') => {
    lang.value = newLang;
    localStorage.setItem('lang', newLang);
    document.documentElement.dir = newLang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = newLang;
  };

  const toggleLang = () => {
    setLanguage(lang.value === 'en' ? 'ar' : 'en');
  };

  const fetchHistory = async (page = 1, reset = false) => {
    if (isLoadingHistory.value) return;
    isLoadingHistory.value = true;
    try {
      const res = await axios.get(`/api/history?page=${page}`);
      if (reset) {
        history.value = res.data.data;
      } else {
        history.value = [...history.value, ...res.data.data];
      }
      currentHistoryPage.value = res.data.current_page;
      hasMoreHistory.value = !!res.data.next_page_url;
    } catch (err) {
      console.error('Failed to fetch history', err);
    } finally {
      isLoadingHistory.value = false;
    }
  };

  const loadMoreHistory = () => {
    if (hasMoreHistory.value && !isLoadingHistory.value) {
      fetchHistory(currentHistoryPage.value + 1);
    }
  };

  return { 
    isSidebarOpen, 
    toggleSidebar, 
    openSidebar, 
    closeSidebar, 
    lang, 
    setLanguage,
    toggleLang,
    history,
    hasMoreHistory,
    isLoadingHistory,
    fetchHistory,
    loadMoreHistory
  };
});
