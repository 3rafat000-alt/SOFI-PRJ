import { createI18n } from 'vue-i18n';
import ar from './ar.json';
import en from './en.json';

const messages = { ar, en };

// Initial locale comes from localStorage (the same source uiStore reads).
// Do NOT call useUiStore() here — this module is imported before Pinia is
// installed, so touching a store at load time throws "no active Pinia" and
// the app never mounts (splash stays forever).
const initialLocale =
  (typeof window !== 'undefined' && window.localStorage.getItem('locale')) || 'ar';

const i18n = createI18n({
  legacy: false,
  locale: initialLocale,
  fallbackLocale: 'en',
  messages,
  pluralizationRules: {
    ar: (choice) => {
      if (choice === 0) return 0;
      if (choice === 1) return 1;
      if (choice === 2) return 2;
      if (choice >= 3 && choice <= 10) return 3;
      return 4;
    },
  },
});

export default i18n;
