import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import TaskCard from '../../src/components/TaskCard.vue';

// Stub child components
const stubs = {
    PriorityBadge: { template: '<span class="priority-badge-stub" />' },
    StatusBadge: { template: '<span class="status-badge-stub" />' },
    MemberAvatar: { template: '<span class="member-avatar-stub" />' },
};

describe('TaskCard', () => {
    let task;

    beforeEach(() => {
        task = {
            id: 1,
            title: 'Test Task',
            status: 'todo',
            priority: 'high',
            due_date: null,
            assignee: { id: 1, name: 'Sara', avatar_url: null },
            tags: [],
            comments_count: 0,
            attachments_count: 0,
            estimated_minutes: null,
            logged_minutes: null,
        };
    });

    it('renders task title', () => {
        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Test Task');
    });

    it('renders assignee name', () => {
        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Sara');
    });

    it('shows overdue styling when past due date', () => {
        task.due_date = '2025-01-01';
        task.status = 'todo';

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.classes()).toContain('border-error-500');
    });

    it('does not show overdue when status is done', () => {
        task.due_date = '2025-01-01';
        task.status = 'done';

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.classes()).not.toContain('border-error-500');
    });

    it('emits click event on click', async () => {
        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        await wrapper.trigger('click');

        expect(wrapper.emitted('click')).toBeTruthy();
        expect(wrapper.emitted('click')[0][0]).toEqual(task);
    });

    it('emits dragstart on drag start', async () => {
        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        const event = new Event('dragstart');
        Object.defineProperty(event, 'dataTransfer', {
            value: { setData: vi.fn(), effectAllowed: '' },
        });

        await wrapper.trigger('dragstart', event);

        expect(wrapper.emitted('dragstart')).toBeTruthy();
        expect(wrapper.emitted('dragstart')[0][0]).toEqual(task);
    });

    it('emits dragend on drag end', async () => {
        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        await wrapper.trigger('dragend');

        expect(wrapper.emitted('dragend')).toBeTruthy();
        expect(wrapper.emitted('dragend')[0][0]).toEqual(task);
    });

    it('shows tag badges when tags exist', () => {
        task.tags = [
            { id: 1, name: 'urgent', color: '#FF0000' },
            { id: 2, name: 'design', color: '#00FF00' },
        ];

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('urgent');
        expect(wrapper.text()).toContain('design');
    });

    it('shows comment count when > 0', () => {
        task.comments_count = 3;

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('3');
    });

    it('shows attachment count when > 0', () => {
        task.attachments_count = 2;

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('2');
    });

    it('shows estimated time when set', () => {
        task.estimated_minutes = 180;

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('3h');
        expect(wrapper.text()).toContain('0m');
    });

    it('shows logged time when set', () => {
        task.logged_minutes = 90;

        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('1h');
        expect(wrapper.text()).toContain('30m');
    });

    it('is not draggable when draggable=false', () => {
        const wrapper = mount(TaskCard, {
            props: { task, draggable: false },
            global: { stubs },
        });

        expect(wrapper.attributes('draggable')).toBe('false');
    });

    it('has correct aria-label', () => {
        const wrapper = mount(TaskCard, {
            props: { task },
            global: { stubs },
        });

        expect(wrapper.attributes('aria-label')).toBe('Test Task');
    });
});
