<script setup>
import { useUiStore } from '@/stores/uiStore';
import Sidebar from './Sidebar.vue';
import TopBar from './TopBar.vue';

const uiStore = useUiStore();
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-neutral-50">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main content area -->
    <div class="flex-1 flex flex-col min-w-0 transition-all duration-200">
      <TopBar />

      <!-- Toast container -->
      <div class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2" role="alert" aria-live="polite">
        <transition-group name="toast">
          <div
            v-for="toast in uiStore.toasts"
            :key="toast.id"
            :class="[
              'rounded-8 px-4 py-3 text-sm font-medium shadow-elevated animate-slide-up flex items-center gap-2',
              toast.type === 'success' ? 'bg-success-600 text-white' : '',
              toast.type === 'error' ? 'bg-error-600 text-white' : '',
              toast.type === 'info' ? 'bg-primary-600 text-white' : '',
            ]"
          >
            <span class="text-lg" aria-hidden="true">
              {{ toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : 'ℹ' }}
            </span>
            <span>{{ toast.message }}</span>
          </div>
        </transition-group>
      </div>

      <!-- Page content -->
      <main
        id="main-content"
        class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8"
        role="main"
        aria-label="Main content"
      >
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(1rem);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-1rem);
}
</style>
