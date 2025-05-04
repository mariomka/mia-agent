import { createI18n } from 'vue-i18n';
import en from '../i18n/locales/en.json';

// Create i18n instance for tests
export const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {
    en
  }
});

/**
 * Helper function to mount components with i18n support in tests
 * @param {Function} mountFunction - The mount function from @vue/test-utils
 * @param {Component} component - The Vue component to mount
 * @param {Object} options - Mount options
 * @returns {VueWrapper} Mounted component wrapper
 */
export function mountWithI18n(mountFunction, component, options = {}) {
  return mountFunction(component, {
    global: {
      plugins: [i18n],
      ...options.global
    },
    ...options
  });
} 