<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { useUiStore } from '@/stores/uiStore';
import AppLayout from '@/components/AppLayout.vue';

const router = useRouter();
const authStore = useAuthStore();
const uiStore = useUiStore();

onMounted(async () => {
  if (authStore.token) {
    try {
      await authStore.fetchProfile();
    } catch {
      authStore.logout();
      router.push('/login');
    }
  }
});
</script>

<template>
  <div :dir="uiStore.direction" :lang="uiStore.locale" class="min-h-screen">
    <template v-if="authStore.isAuthenticated">
      <AppLayout>
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </AppLayout>
    </template>
    <template v-else>
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </template>
  </div>
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
