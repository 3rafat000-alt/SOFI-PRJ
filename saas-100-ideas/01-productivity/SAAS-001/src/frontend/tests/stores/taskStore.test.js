import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTaskStore } from '../../src/stores/taskStore';
import axios from 'axios';

vi.mock('axios');

describe('taskStore', () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useTaskStore();
        vi.clearAllMocks();
    });

    it('has initial state', () => {
        expect(store.tasks).toEqual([]);
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
        expect(store.currentCursor).toBeNull();
        expect(store.hasMore).toBe(false);
        expect(store.filters).toEqual({});
    });

    it('fetchTasks loads tasks', async () => {
        const mockTasks = [
            { id: 1, title: 'Task 1', status: 'todo', priority: 'high' },
            { id: 2, title: 'Task 2', status: 'in_progress', priority: 'medium' },
        ];

        axios.get.mockResolvedValue({
            data: {
                data: mockTasks,
                meta: { next_cursor: null, has_more: false },
            },
        });

        await store.fetchTasks();

        expect(store.tasks).toEqual(mockTasks);
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
        expect(store.hasMore).toBe(false);
    });

    it('fetchTasks with filters', async () => {
        axios.get.mockResolvedValue({
            data: { data: [], meta: { next_cursor: null, has_more: false } },
        });

        await store.fetchTasks({ status: 'todo', priority: 'high' });

        expect(axios.get).toHaveBeenCalledWith(
            expect.stringContaining('/api/v1/tasks'),
            expect.objectContaining({
                params: expect.objectContaining({
                    status: 'todo',
                    priority: 'high',
                }),
            })
        );
    });

    it('fetchTasks handles error', async () => {
        axios.get.mockRejectedValue({
            response: { data: { error: { message: 'Server error' } } },
        });

        await store.fetchTasks();

        expect(store.error).toBe('Server error');
        expect(store.loading).toBe(false);
    });

    it('createTask adds task to state', async () => {
        const newTask = { id: 3, title: 'New Task', status: 'todo', priority: 'low' };

        axios.post.mockResolvedValue({
            data: { data: newTask, meta: {} },
        });

        await store.createTask({ title: 'New Task', project_id: 1 });

        expect(store.tasks).toContainEqual(newTask);
        expect(store.loading).toBe(false);
    });

    it('updateTask updates existing task', async () => {
        store.tasks = [
            { id: 1, title: 'Old Title', status: 'todo', priority: 'low' },
        ];

        const updatedTask = { id: 1, title: 'New Title', status: 'in_progress', priority: 'medium' };

        axios.put.mockResolvedValue({
            data: { data: updatedTask },
        });

        await store.updateTask(1, { title: 'New Title', status: 'in_progress' });

        expect(store.tasks[0]).toEqual(updatedTask);
    });

    it('updateTask handles not found', async () => {
        axios.put.mockRejectedValue({
            response: { status: 404, data: { error: { message: 'Not found' } } },
        });

        await store.updateTask(999, { title: 'Ghost' });

        expect(store.error).toBe('Not found');
    });

    it('deleteTask removes task from state', async () => {
        store.tasks = [
            { id: 1, title: 'Task 1' },
            { id: 2, title: 'Task 2' },
        ];

        axios.delete.mockResolvedValue({
            data: { data: { message: 'Task deleted.' } },
        });

        await store.deleteTask(1);

        expect(store.tasks).toHaveLength(1);
        expect(store.tasks[0].id).toBe(2);
    });

    it('deleteTask handles 403', async () => {
        store.tasks = [{ id: 1, title: 'Protected task' }];

        axios.delete.mockRejectedValue({
            response: { status: 403, data: { error: { message: 'Forbidden' } } },
        });

        await store.deleteTask(1);

        expect(store.tasks).toHaveLength(1);
        expect(store.error).toBe('Forbidden');
    });

    it('reorderTasks sends order payload', async () => {
        axios.put.mockResolvedValue({
            data: { data: { reordered_count: 2 } },
        });

        const orders = [
            { id: 1, status: 'done', position: 1 },
            { id: 2, status: 'todo', position: 2 },
        ];

        const result = await store.reorderTasks(1, orders);

        expect(result).toBe(2);
        expect(axios.put).toHaveBeenCalledWith(
            expect.stringContaining('/api/v1/tasks/reorder'),
            { project_id: 1, orders }
        );
    });

    it('setFilters updates filters', () => {
        store.setFilters({ status: 'done' });

        expect(store.filters).toEqual({ status: 'done' });
    });

    it('clearFilters resets filters', () => {
        store.filters = { status: 'done', priority: 'high' };

        store.clearFilters();

        expect(store.filters).toEqual({});
    });

    it('getTasksByStatus returns grouped tasks', () => {
        store.tasks = [
            { id: 1, title: 'Todo', status: 'todo' },
            { id: 2, title: 'In Progress', status: 'in_progress' },
            { id: 3, title: 'Done', status: 'done' },
            { id: 4, title: 'Todo 2', status: 'todo' },
        ];

        const grouped = store.getTasksByStatus;

        expect(grouped.todo).toHaveLength(2);
        expect(grouped.in_progress).toHaveLength(1);
        expect(grouped.done).toHaveLength(1);
    });
});
