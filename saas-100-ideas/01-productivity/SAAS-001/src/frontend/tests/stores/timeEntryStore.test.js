import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTimeEntryStore } from '../../src/stores/timeEntryStore';
import axios from 'axios';

vi.mock('axios');

describe('timeEntryStore', () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useTimeEntryStore();
        vi.clearAllMocks();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has initial state', () => {
        expect(store.activeEntry).toBeNull();
        expect(store.entries).toEqual([]);
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
        expect(store.elapsedSeconds).toBe(0);
    });

    it('startTimer starts tracking', async () => {
        const mockEntry = {
            id: 1,
            task_id: 1,
            started_at: new Date().toISOString(),
            is_running: true,
            ended_at: null,
        };

        axios.post.mockResolvedValue({
            data: { data: mockEntry, meta: {} },
        });

        await store.startTimer(1);

        expect(store.activeEntry).toEqual(mockEntry);
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
    });

    it('startTimer handles 409 conflict', async () => {
        axios.post.mockRejectedValue({
            response: { status: 409, data: { error: { code: 'CONFLICT', message: 'Timer already running' } } },
        });

        await store.startTimer(1);

        expect(store.error).toBe('Timer already running');
        expect(store.activeEntry).toBeNull();
    });

    it('stopTimer stops tracking', async () => {
        store.activeEntry = {
            id: 1,
            task_id: 1,
            started_at: new Date(Date.now() - 3600000).toISOString(),
            is_running: true,
            ended_at: null,
        };
        store.elapsedSeconds = 3600;

        const mockStopped = {
            id: 1,
            task_id: 1,
            started_at: store.activeEntry.started_at,
            ended_at: new Date().toISOString(),
            is_running: false,
            duration_minutes: 60,
        };

        axios.post.mockResolvedValue({
            data: { data: mockStopped },
        });

        await store.stopTimer('Done');

        expect(store.activeEntry).toBeNull();
        expect(store.elapsedSeconds).toBe(0);
    });

    it('stopTimer handles no active entry', async () => {
        await store.stopTimer();

        expect(store.error).toBe('No active timer');
    });

    it('fetchEntries loads entries', async () => {
        const mockEntries = [
            { id: 1, task_id: 1, duration_minutes: 30 },
            { id: 2, task_id: 2, duration_minutes: 45 },
        ];

        axios.get.mockResolvedValue({
            data: { data: mockEntries, meta: { current_page: 1, last_page: 1, total: 2 } },
        });

        await store.fetchEntries();

        expect(store.entries).toEqual(mockEntries);
        expect(store.loading).toBe(false);
    });

    it('fetchEntries handles error', async () => {
        axios.get.mockRejectedValue({
            response: { data: { error: { message: 'Failed to load' } } },
        });

        await store.fetchEntries();

        expect(store.error).toBe('Failed to load');
    });

    it('elapsedIncrement increases timer when active', () => {
        store.activeEntry = { id: 1, is_running: true };

        store.elapsedIncrement();

        expect(store.elapsedSeconds).toBe(1);
    });

    it('elapsedIncrement does nothing when inactive', () => {
        store.activeEntry = null;
        store.elapsedSeconds = 0;

        store.elapsedIncrement();

        expect(store.elapsedSeconds).toBe(0);
    });

    it('formattedElapsed returns correct format', () => {
        store.elapsedSeconds = 3661; // 1h 1m 1s

        const formatted = store.formattedElapsed;

        expect(formatted).toContain('01');
    });

    it('formattedElapsed returns 00:00:00 when zero', () => {
        store.elapsedSeconds = 0;

        expect(store.formattedElapsed).toBe('00:00:00');
    });

    it('fetchReport returns report data', async () => {
        const mockReport = {
            summary: { total_minutes: 150, total_hours: 2.5, period: { from: '2026-07-01', to: '2026-07-31' } },
            entries: [{ id: 1, duration_minutes: 150 }],
        };

        axios.get.mockResolvedValue({
            data: { data: mockReport, meta: {} },
        });

        const result = await store.fetchReport({ workspace_id: 1, from: '2026-07-01', to: '2026-07-31' });

        expect(result).toEqual(mockReport);
    });
});
