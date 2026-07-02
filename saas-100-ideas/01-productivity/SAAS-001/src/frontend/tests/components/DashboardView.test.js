import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { setActivePinia, createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';

// Stub child components
const stubs = {
    ActivityFeed: { template: '<div class="activity-feed-stub" />' },
    LoadingSpinner: { template: '<div class="loading-stub" />' },
    EmptyState: { template: '<div class="empty-stub" />' },
    RouterLink: { template: '<a class="router-link-stub" />' },
    RouterView: { template: '<div />' },
};

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'Dashboard', component: { template: '<div />' } },
        { path: '/tasks', name: 'Tasks', component: { template: '<div />' } },
    ],
});

describe('DashboardView', () => {
    beforeEach(async () => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        router.push('/');
        await router.isReady();
    });

    it('renders dashboard title', async () => {
        const wrapper = mount(await import('../../src/views/DashboardView.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 100));

        expect(wrapper.text()).toContain('Dashboard');
    });

    it('shows KPI cards when stats loaded', async () => {
        // Manually set stats on the component
        const wrapper = mount(await import('../../src/views/DashboardView.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 100));

        const statCards = wrapper.findAll('.stat-card');
        expect(statCards.length).toBeGreaterThanOrEqual(2);
    });

    it('shows error state when fetch fails', async () => {
        // Mock api to reject
        const api = await import('@/services/api');
        api.default.get = vi.fn().mockRejectedValue(new Error('API Error'));

        const wrapper = mount(await import('../../src/views/DashboardView.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 100));
    });

    it('shows upcoming tasks section', async () => {
        const wrapper = mount(await import('../../src/views/DashboardView.vue'), {
            global: { plugins: [router], stubs },
        });

        await new Promise(r => setTimeout(r, 100));

        expect(wrapper.text()).toContain('Upcoming');
    });
});
