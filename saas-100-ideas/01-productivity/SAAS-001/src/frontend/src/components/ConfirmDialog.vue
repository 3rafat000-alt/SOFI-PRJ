<script setup>
import { ref } from 'vue';

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: { type: String, required: true },
  message: { type: String, required: true },
  confirmLabel: { type: String, default: 'تأكيد' },
  cancelLabel: { type: String, default: 'إلغاء' },
  variant: { type: String, default: 'danger' }, // danger | primary
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel', 'update:visible']);

const dialogRef = ref(null);

const close = () => {
  if (!props.loading) {
    emit('update:visible', false);
    emit('cancel');
  }
};

const confirm = () => {
  emit('confirm');
};

const onBackdropClick = (e) => {
  if (e.target === dialogRef.value) close();
};

const onKeydown = (e) => {
  if (e.key === 'Escape') close();
};
</script>

<template>
  <Teleport to="body">
    <transition name="modal">
      <div
        v-if="visible"
        ref="dialogRef"
        class="modal-backdrop"
        @click="onBackdropClick"
        @keydown="onKeydown"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <div class="modal-content p-6 max-w-sm">
          <h3 class="text-lg font-semibold text-neutral-900 mb-2">{{ title }}</h3>
          <p class="text-sm text-neutral-600 mb-6">{{ message }}</p>
          <div class="flex items-center justify-end gap-3">
            <button
              @click="close"
              class="btn-secondary"
              :disabled="loading"
              :aria-label="cancelLabel"
            >
              {{ cancelLabel }}
            </button>
            <button
              @click="confirm"
              :class="[variant === 'danger' ? 'btn-danger' : 'btn-primary']"
              :disabled="loading"
            >
              <svg v-if="loading" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75" />
              </svg>
              <span v-else>{{ confirmLabel }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.2s ease;
}
.modal-enter-from .modal-content {
  transform: scale(0.95);
}
.modal-leave-to .modal-content {
  transform: scale(0.95);
}
</style>
