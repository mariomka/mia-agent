import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import es from './locales/es.json';

// Supported languages
const SUPPORTED_LOCALES = ['en', 'es'];
const DEFAULT_LOCALE = 'en';

// Get browser language - first segment only (e.g., 'en-US' becomes 'en')
function getBrowserLanguage() {
  const browserLang = navigator.language || navigator.userLanguage || DEFAULT_LOCALE;
  const shortLang = browserLang.split('-')[0];

  // Check if the browser language is supported, otherwise use default
  return SUPPORTED_LOCALES.includes(shortLang) ? shortLang : DEFAULT_LOCALE;
}

// Set the browser language as the HTML lang attribute
function setHtmlLang(locale) {
  document.querySelector('html').setAttribute('lang', locale);
}

// Create i18n instance with browser language
const detectedLocale = getBrowserLanguage();
setHtmlLang(detectedLocale);

// Create i18n instance
export const i18n = createI18n({
  legacy: false, // Set to false for Composition API
  locale: detectedLocale,
  fallbackLocale: DEFAULT_LOCALE,
  messages: {
    en,
    es
  },
  globalInjection: true, // Make translation functions available in templates
});

export default i18n;
