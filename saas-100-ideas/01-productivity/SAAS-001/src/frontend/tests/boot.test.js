import { describe, it, expect } from 'vitest';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import App from '@/App.vue';
import router from '@/router';
import i18n from '@/i18n';

// Regression: i18n/index.js used to call useUiStore() at module load time —
// before Pinia was installed — which threw "no active Pinia" and left the
// SPA stuck on the "جاري تحميل التطبيق" splash. This test boots the app the
// same way main.js does and asserts it mounts and replaces the splash.
describe('app boot', () => {
  it('mounts without throwing and replaces the loading splash', async () => {
    document.body.innerHTML =
      '<div id="app"><span>جاري تحميل التطبيق...</span></div>';

    const app = createApp(App);
    const pinia = createPinia();
    pinia.use(piniaPluginPersistedstate);
    app.use(pinia);
    app.use(router);
    app.use(i18n);

    await router.isReady();

    expect(() => app.mount('#app')).not.toThrow();
    expect(document.getElementById('app').textContent).not.toContain(
      'جاري تحميل التطبيق'
    );
  });
});
