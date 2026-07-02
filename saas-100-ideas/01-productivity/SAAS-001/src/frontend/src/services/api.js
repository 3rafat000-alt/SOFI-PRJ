import axios from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import router from '@/router';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 30000,
});

api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();
    const uiStore = useUiStore();

    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`;
    }

    config.headers['Accept-Language'] = uiStore.locale || 'ar';
    config.headers['X-Timezone'] = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Riyadh';

    return config;
  },
  (error) => Promise.reject(error),
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      const authStore = useAuthStore();

      try {
        await authStore.refreshToken();
        originalRequest.headers.Authorization = `Bearer ${authStore.token}`;
        return api(originalRequest);
      } catch {
        authStore.logout();
        router.push('/login');
        return Promise.reject(error);
      }
    }

    // Normalize error shape. Supports BOTH envelopes:
    //  - custom:    { error: { code, message, details } }
    //  - Laravel:   { message, errors: { field: [msg, ...] } }  (validation 422)
    const data = error.response?.data || {};
    const fieldErrors = data.errors || data.error?.details || {};
    const firstFieldError =
      fieldErrors && typeof fieldErrors === 'object'
        ? Object.values(fieldErrors)?.[0]?.[0]
        : null;

    const err = {
      code: data.error?.code || (error.response?.status === 422 ? 'VALIDATION_ERROR' : 'UNKNOWN_ERROR'),
      message:
        data.error?.message ||
        firstFieldError ||
        data.message ||
        error.message ||
        'Something went wrong',
      details: fieldErrors,
      status: error.response?.status || 0,
      meta: data.error?.meta || {},
    };

    return Promise.reject(err);
  },
);

export default api;
