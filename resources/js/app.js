import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { createPinia } from 'pinia';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    createApp({
      render: () => {
        el.classList.add('h-full');
        el.classList.add('overflow-hidden');
        return h(App, props);
      }
    })
      .use(plugin)
      .use(ZiggyVue)
      .use(createPinia())
      .use(i18n)
      .mount(el);
  },
  progress: {
    color: '#4B5563'
  }
});
