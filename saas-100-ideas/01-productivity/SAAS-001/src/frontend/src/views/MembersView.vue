<script setup>
import { ref, onMounted } from 'vue';
import { useWorkspaceStore } from '@/stores/workspaceStore';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import MemberAvatar from '@/components/MemberAvatar.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';
import EmptyState from '@/components/EmptyState.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

const workspaceStore = useWorkspaceStore();
const authStore = useAuthStore();
const uiStore = useUiStore();

const inviteEmail = ref('');
const inviteRole = ref('member');
const showRemoveConfirm = ref(false);
const removeMemberId = ref(null);

onMounted(() => {
  workspaceStore.fetchMembers();
});

const sendInvite = async () => {
  if (!inviteEmail.value.trim()) return;
  const ok = await workspaceStore.inviteMember(inviteEmail.value.trim(), inviteRole.value);
  if (ok) {
    inviteEmail.value = '';
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم إرسال الدعوة' : 'Invitation sent',
      'success',
    );
  }
};

const confirmRemove = (memberId) => {
  removeMemberId.value = memberId;
  showRemoveConfirm.value = true;
};

const handleRemove = async () => {
  if (!removeMemberId.value) return;
  const ok = await workspaceStore.removeMember(removeMemberId.value);
  if (ok) {
    uiStore.addToast(
      uiStore.locale === 'ar' ? 'تم إزالة العضو' : 'Member removed',
      'success',
    );
  }
  showRemoveConfirm.value = false;
  removeMemberId.value = null;
};

const changeRole = async (memberId, role) => {
  await workspaceStore.changeRole(memberId, role);
  uiStore.addToast(
    uiStore.locale === 'ar' ? 'تم تغيير الدور' : 'Role changed',
    'success',
  );
};

const roleOptions = [
  { value: 'owner', label: { ar: 'مالك', en: 'Owner' } },
  { value: 'admin', label: { ar: 'مدير', en: 'Admin' } },
  { value: 'member', label: { ar: 'عضو', en: 'Member' } },
];

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString(uiStore.locale === 'ar' ? 'ar-SA' : 'en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  });
};
</script>

<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">{{ uiStore.locale === 'ar' ? 'فريق العمل' : 'Team Members' }}</h1>
    </div>

    <!-- Settings sub-nav -->
    <div class="flex items-center gap-1 border-b border-neutral-200 mb-6">
      <router-link to="/settings" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 -mb-px">
        {{ uiStore.locale === 'ar' ? 'عام' : 'General' }}
      </router-link>
      <router-link to="/settings/members" class="px-4 py-3 text-sm font-medium border-b-2 border-primary-600 text-primary-600 -mb-px">
        {{ uiStore.locale === 'ar' ? 'فريق العمل' : 'Team' }}
      </router-link>
      <router-link to="/settings/webhooks" class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-neutral-500 hover:text-neutral-700 -mb-px">
        {{ uiStore.locale === 'ar' ? 'الويبهوك' : 'Webhooks' }}
      </router-link>
    </div>

    <div class="max-w-3xl space-y-6">
      <!-- Invite form -->
      <div class="card">
        <h2 class="text-sm font-semibold text-neutral-900 mb-4">
          {{ uiStore.locale === 'ar' ? 'دعوة عضو جديد' : 'Invite Member' }}
        </h2>
        <div class="flex items-end gap-3">
          <div class="flex-1">
            <label class="input-label" for="invite-email">{{ uiStore.locale === 'ar' ? 'البريد الإلكتروني' : 'Email' }}</label>
            <input
              id="invite-email"
              v-model="inviteEmail"
              type="email"
              class="input"
              :placeholder="uiStore.locale === 'ar' ? 'بريد العضو الإلكتروني' : 'member@example.com'"
              @keydown.enter="sendInvite"
            />
          </div>
          <div class="w-32">
            <label class="input-label" for="invite-role">{{ uiStore.locale === 'ar' ? 'الدور' : 'Role' }}</label>
            <select id="invite-role" v-model="inviteRole" class="input">
              <option value="member">{{ uiStore.locale === 'ar' ? 'عضو' : 'Member' }}</option>
              <option value="admin">{{ uiStore.locale === 'ar' ? 'مدير' : 'Admin' }}</option>
            </select>
          </div>
          <button @click="sendInvite" class="btn-primary" :disabled="workspaceStore.saving || !inviteEmail.trim()">
            <svg v-if="workspaceStore.saving" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
              <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
            </svg>
            <span>{{ uiStore.locale === 'ar' ? 'دعوة' : 'Invite' }}</span>
          </button>
        </div>
        <p v-if="workspaceStore.error" class="input-error-text mt-2">{{ workspaceStore.error }}</p>
      </div>

      <!-- Members list -->
      <div class="card">
        <h2 class="text-sm font-semibold text-neutral-900 mb-4">
          {{ uiStore.locale === 'ar' ? 'الأعضاء' : 'Members' }} ({{ workspaceStore.memberCount }})
        </h2>

        <LoadingSpinner v-if="workspaceStore.loading" size="sm" />

        <EmptyState
          v-else-if="workspaceStore.members.length === 0"
          :title="uiStore.locale === 'ar' ? 'لا يوجد أعضاء' : 'No members'"
          :description="uiStore.locale === 'ar' ? 'ادعو أعضاء للانضمام إلى مساحة العمل' : 'Invite members to join your workspace'"
        />

        <div v-else class="divide-y divide-neutral-100">
          <div
            v-for="member in workspaceStore.members"
            :key="member.id"
            class="flex items-center gap-4 py-3"
          >
            <MemberAvatar :user="member" size="md" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-neutral-900">{{ member.name || member.email }}</span>
                <span class="badge-neutral text-xs">{{ roleOptions.find(r => r.value === member.role)?.label[uiStore.locale] || member.role }}</span>
                <span v-if="member.status === 'pending'" class="badge-warning text-xs">
                  {{ uiStore.locale === 'ar' ? 'معلق' : 'Pending' }}
                </span>
              </div>
              <div class="flex items-center gap-3 text-xs text-neutral-500 mt-0.5">
                <span>{{ member.email }}</span>
                <span v-if="member.joined_at">{{ uiStore.locale === 'ar' ? 'انضم' : 'Joined' }}: {{ formatDate(member.joined_at) }}</span>
                <span v-if="member.task_count !== undefined">{{ member.task_count }} {{ uiStore.locale === 'ar' ? 'مهمة' : 'tasks' }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <!-- Role changer (admin/owner only) -->
              <select
                v-if="authStore.isWorkspaceOwner && member.id !== authStore.userId"
                :value="member.role"
                @change="(e) => changeRole(member.id, e.target.value)"
                class="input text-xs py-1 w-24"
                :aria-label="uiStore.locale === 'ar' ? 'تغيير الدور' : 'Change role'"
              >
                <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value" :disabled="opt.value === 'owner' && workspaceStore.owners.length <= 1">
                  {{ opt.label[uiStore.locale] || opt.label.en }}
                </option>
              </select>

              <!-- Remove button -->
              <button
                v-if="authStore.isWorkspaceOwner && member.id !== authStore.userId"
                @click="confirmRemove(member.id)"
                class="btn-icon btn-ghost btn-sm text-neutral-400 hover:text-error-500"
                :aria-label="uiStore.locale === 'ar' ? 'إزالة' : 'Remove'"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                  <polyline points="3 6 5 6 21 6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmDialog
      :visible="showRemoveConfirm"
      :title="uiStore.locale === 'ar' ? 'إزالة عضو' : 'Remove Member'"
      :message="uiStore.locale === 'ar' ? 'هل أنت متأكد من إزالة هذا العضو؟' : 'Are you sure you want to remove this member?'"
      :loading="workspaceStore.saving"
      @confirm="handleRemove"
      @cancel="showRemoveConfirm = false; removeMemberId = null"
      @update:visible="showRemoveConfirm = $event"
    />
  </div>
</template>
