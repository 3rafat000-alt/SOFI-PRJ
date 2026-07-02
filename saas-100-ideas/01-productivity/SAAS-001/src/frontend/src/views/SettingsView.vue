<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useWorkspaceStore } from '@/stores/workspaceStore';
import { useUiStore } from '@/stores/uiStore';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

const router = useRouter();
const authStore = useAuthStore();
const workspaceStore = useWorkspaceStore();
const uiStore = useUiStore();

const form = ref({
  name: authStore.workspace?.name || '',
  description: authStore.workspace?.description || '',
  locale: uiStore.locale,
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Riyadh',
  theme: uiStore.theme,
});

const saving = ref(false);
const showDeleteConfirm = ref(false);

const saveSettings = async () => {
  saving.value = true;
  await workspaceStore.updateWorkspace({
    name: form.value.name,
    description: form.value.description,
  });
  uiStore.setLocale(form.value.locale);
  uiStore.setTheme(form.value.theme);
  uiStore.addToast(
    uiStore.locale === 'ar' ? 'تم حفظ الإعدادات' : 'Settings saved',
    'success',
  );
  saving.value = false;
};

const confirmDelete = async () => {
  const ok = await workspaceStore.deleteWorkspace();
  if (ok) {
    router.push('/login');
  }
  showDeleteConfirm.value = false;
};
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'الإعدادات' : 'Settings' }}</h1>
    </div>

    <!-- Settings sub-navigation -->
    <div class="flex items-center gap-1 border-b border-neutral-200 mb-6">
      <router-link to="/settings" class="px-4 py-3 text-sm font-medium border-b-2 border-primary-600 text-primary-600 -mb-px">
        {{ uiStore.locale === 'ar' ? 'عام' : 'General' }}
      </router-link>
      <router-link to="/settings/members" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 -mb-px">
        {{ uiStore.locale === 'ar' ? 'فريق العمل' : 'Team' }}
      </router-link>
      <router-link to="/settings/webhooks" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 -mb-px">
        {{ uiStore.locale === 'ar' ? 'الويبهوك' : 'Webhooks' }}
      </router-link>
    </div>

    <div class="max-w-2xl space-y-6">
      <!-- Workspace settings -->
      <div class="card">
        <h2 class="text-base font-semibold text-neutral-900 mb-4">
          {{ uiStore.locale === 'ar' ? 'مساحة العمل' : 'Workspace' }}
        </h2>
        <form @submit.prevent="saveSettings" class="space-y-4">
          <div>
            <label class="input-label" for="settings-name">{{ uiStore.locale === 'ar' ? 'الاسم' : 'Name' }}</label>
            <input id="settings-name" v-model="form.name" class="input" required />
          </div>
          <div>
            <label class="input-label" for="settings-desc">{{ uiStore.locale === 'ar' ? 'الوصف' : 'Description' }}</label>
            <textarea id="settings-desc" v-model="form.description" class="input min-h-[80px]" rows="3" />
          </div>

          <h3 class="text-sm font-semibold text-neutral-800 pt-4 border-t border-neutral-100">
            {{ uiStore.locale === 'ar' ? 'التفضيلات' : 'Preferences' }}
          </h3>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="input-label" for="settings-locale">{{ uiStore.locale === 'ar' ? 'اللغة' : 'Language' }}</label>
              <select id="settings-locale" v-model="form.locale" class="input">
                <option value="ar">العربية</option>
                <option value="en">English</option>
              </select>
            </div>
            <div>
              <label class="input-label" for="settings-tz">{{ uiStore.locale === 'ar' ? 'المنطقة الزمنية' : 'Timezone' }}</label>
              <select id="settings-tz" v-model="form.timezone" class="input">
                <option value="Asia/Riyadh">Asia/Riyadh (UTC+3)</option>
                <option value="Asia/Dubai">Asia/Dubai (UTC+4)</option>
                <option value="Asia/Kuwait">Asia/Kuwait (UTC+3)</option>
                <option value="Africa/Cairo">Africa/Cairo (UTC+2)</option>
                <option value="America/New_York">America/New_York (UTC-5)</option>
                <option value="Europe/London">Europe/London (UTC+0)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="input-label">{{ uiStore.locale === 'ar' ? 'المظهر' : 'Theme' }}</label>
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.theme" value="light" class="text-primary-600 focus:ring-primary-500" />
                <span class="text-sm text-neutral-700">{{ uiStore.locale === 'ar' ? 'فاتح' : 'Light' }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.theme" value="dark" class="text-primary-600 focus:ring-primary-500" />
                <span class="text-sm text-neutral-700">{{ uiStore.locale === 'ar' ? 'داكن' : 'Dark' }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.theme" value="system" class="text-primary-600 focus:ring-primary-500" />
                <span class="text-sm text-neutral-700">{{ uiStore.locale === 'ar' ? 'النظام' : 'System' }}</span>
              </label>
            </div>
          </div>

          <div class="pt-4">
            <button type="submit" class="btn-primary" :disabled="saving">
              <svg v-if="saving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
              </svg>
              <span>{{ uiStore.locale === 'ar' ? 'حفظ الإعدادات' : 'Save Settings' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Danger zone -->
      <div class="card border border-error-200">
        <h2 class="text-base font-semibold text-error-600 mb-2">
          {{ uiStore.locale === 'ar' ? 'منطقة الخطر' : 'Danger Zone' }}
        </h2>
        <p class="text-sm text-neutral-600 mb-4">
          {{ uiStore.locale === 'ar' ? 'حذف مساحة العمل لا يمكن التراجع عنه. سيتم حذف جميع المشاريع والمهام.' : 'Deleting your workspace is irreversible. All projects and tasks will be lost.' }}
        </p>
        <button @click="showDeleteConfirm = true" class="btn-danger" :disabled="!authStore.isWorkspaceOwner">
          {{ uiStore.locale === 'ar' ? 'حذف مساحة العمل' : 'Delete Workspace' }}
        </button>
        <p v-if="!authStore.isWorkspaceOwner" class="text-xs text-neutral-500 mt-2">
          {{ uiStore.locale === 'ar' ? 'فقط مالك مساحة العمل يمكنه الحذف' : 'Only workspace owner can delete' }}
        </p>
      </div>
    </div>

    <ConfirmDialog
      :visible="showDeleteConfirm"
      :title="uiStore.locale === 'ar' ? 'حذف مساحة العمل' : 'Delete Workspace'"
      :message="uiStore.locale === 'ar' ? 'هل أنت متأكد؟ هذا الإجراء لا يمكن التراجع عنه.' : 'Are you sure? This action cannot be undone.'"
      :loading="workspaceStore.saving"
      @confirm="confirmDelete"
      @cancel="showDeleteConfirm = false"
      @update:visible="showDeleteConfirm = $event"
    />
  </div>
</template>
