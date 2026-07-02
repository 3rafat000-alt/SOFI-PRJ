<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import { useI18n } from 'vue-i18n';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();
const { t } = useI18n();

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  workspace_name: '',
  locale: 'ar',
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Riyadh',
});
const showPassword = ref(false);

const submit = async () => {
  if (form.value.password.length < 8) {
    authStore.error = uiStore.locale === 'ar'
      ? 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'
      : 'Password must be at least 8 characters';
    return;
  }
  if (form.value.password !== form.value.password_confirmation) {
    authStore.error = uiStore.locale === 'ar' ? 'كلمتا المرور غير متطابقتين' : 'Passwords do not match';
    return;
  }
  const success = await authStore.register({ ...form.value, locale: uiStore.locale });
  if (success) {
    router.push('/dashboard');
  }
};

const switchLocale = () => {
  const newLocale = uiStore.locale === 'ar' ? 'en' : 'ar';
  uiStore.setLocale(newLocale);
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-secondary-50 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-12 bg-gradient-to-br from-primary-600 to-secondary-600 mb-4">
          <span class="text-white text-2xl font-bold">T</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">{{ t('auth.register_title') }}</h1>
        <p class="text-sm text-neutral-500 mt-1">{{ t('auth.register_subtitle') }}</p>
      </div>

      <div class="card">
        <div class="flex justify-center mb-6">
          <button
            @click="switchLocale"
            class="text-xs text-primary-600 hover:text-primary-700 font-medium"
          >
            {{ uiStore.locale === 'ar' ? 'English' : 'العربية' }}
          </button>
        </div>

        <div
          v-if="authStore.error"
          class="mb-4 p-3 rounded-8 bg-error-50 border border-error-200 text-sm text-error-700 flex items-center gap-2"
          role="alert"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 flex-shrink-0" aria-hidden="true">
            <circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />
          </svg>
          <span>{{ authStore.error }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="input-label" for="reg-name">{{ t('auth.name') }}</label>
              <input id="reg-name" v-model="form.name" type="text" class="input" required autocomplete="name" />
            </div>
            <div>
              <label class="input-label" for="reg-workspace">{{ t('auth.workspace_name') }}</label>
              <input id="reg-workspace" v-model="form.workspace_name" type="text" class="input" required />
            </div>
          </div>

          <div>
            <label class="input-label" for="reg-email">{{ t('auth.email') }}</label>
            <input id="reg-email" v-model="form.email" type="email" class="input" required autocomplete="email" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="input-label" for="reg-password">{{ t('auth.password') }}</label>
              <div class="relative">
                <input
                  id="reg-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="input pl-9"
                  required
                  minlength="8"
                  autocomplete="new-password"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 left-2 flex items-center text-neutral-400"
                  :aria-label="showPassword ? 'Hide' : 'Show'"
                >
                  <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4" aria-hidden="true">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" /><line x1="1" y1="1" x2="23" y2="23" />
                  </svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />
                  </svg>
                </button>
              </div>
            </div>
            <div>
              <label class="input-label" for="reg-confirm">{{ t('auth.confirm_password') }}</label>
              <input id="reg-confirm" v-model="form.password_confirmation" type="password" class="input" required autocomplete="new-password" />
            </div>
          </div>

          <!-- Hidden locale field -->
          <input type="hidden" v-model="form.locale" />
          <input type="hidden" v-model="form.timezone" />

          <button
            type="submit"
            class="btn-primary w-full"
            :disabled="authStore.loading"
          >
            <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
              <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
            </svg>
            <span>{{ authStore.loading ? (uiStore.locale === 'ar' ? 'جاري إنشاء الحساب...' : 'Creating account...') : t('auth.register') }}</span>
          </button>
        </form>

        <p class="text-center text-sm text-neutral-500 mt-6">
          {{ t('auth.have_account') }}
          <router-link to="/login" class="text-primary-600 hover:text-primary-700 font-medium">
            {{ t('auth.login') }}
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>
