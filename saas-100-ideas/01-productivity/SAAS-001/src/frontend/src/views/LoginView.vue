<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import { useI18n } from 'vue-i18n';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const uiStore = useUiStore();
const { t } = useI18n();

const email = ref('');
const password = ref('');
const remember = ref(false);
const showPassword = ref(false);

const submit = async () => {
  const success = await authStore.login(email.value, password.value);
  if (success) {
    const redirect = route.query.redirect || '/dashboard';
    router.push(redirect);
  }
};

const switchLocale = () => {
  const newLocale = uiStore.locale === 'ar' ? 'en' : 'ar';
  uiStore.setLocale(newLocale);
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-secondary-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-12 bg-gradient-to-br from-primary-600 to-secondary-600 mb-4">
          <span class="text-white text-2xl font-bold">T</span>
        </div>
        <h1 class="text-2xl font-bold text-neutral-900">{{ t('auth.login_title') }}</h1>
        <p class="text-sm text-neutral-500 mt-1">{{ t('auth.login_subtitle') }}</p>
      </div>

      <!-- Card -->
      <div class="card">
        <!-- Locale switch -->
        <div class="flex justify-center mb-6">
          <button
            @click="switchLocale"
            class="text-xs text-primary-600 hover:text-primary-700 font-medium"
            :aria-label="`Switch to ${uiStore.locale === 'ar' ? 'English' : 'Arabic'}`"
          >
            {{ uiStore.locale === 'ar' ? 'English' : 'العربية' }}
          </button>
        </div>

        <!-- Error -->
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
          <div>
            <label class="input-label" for="login-email">{{ t('auth.email') }}</label>
            <input
              id="login-email"
              v-model="email"
              type="email"
              class="input"
              :placeholder="uiStore.locale === 'ar' ? 'بريدك الإلكتروني' : 'Your email'"
              required
              autocomplete="email"
              autofocus
            />
          </div>

          <div>
            <label class="input-label" for="login-password">{{ t('auth.password') }}</label>
            <div class="relative">
              <input
                id="login-password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="input pl-9"
                :placeholder="uiStore.locale === 'ar' ? 'كلمة المرور' : 'Password'"
                required
                autocomplete="current-password"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 left-2 flex items-center text-neutral-400 hover:text-neutral-600"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
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

          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-neutral-600 cursor-pointer">
              <input
                v-model="remember"
                type="checkbox"
                class="rounded-4 border-neutral-300 text-primary-600 focus:ring-primary-500"
              />
              {{ t('auth.remember_me') }}
            </label>
            <router-link to="/forgot-password" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
              {{ t('auth.forgot_password') }}
            </router-link>
          </div>

          <button
            type="submit"
            class="btn-primary w-full"
            :disabled="authStore.loading"
          >
            <svg v-if="authStore.loading" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
              <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
            </svg>
            <span>{{ authStore.loading ? (uiStore.locale === 'ar' ? 'جاري تسجيل الدخول...' : 'Signing in...') : t('auth.login') }}</span>
          </button>
        </form>

        <p class="text-center text-sm text-neutral-500 mt-6">
          {{ t('auth.no_account') }}
          <router-link to="/register" class="text-primary-600 hover:text-primary-700 font-medium">
            {{ t('auth.register') }}
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>
