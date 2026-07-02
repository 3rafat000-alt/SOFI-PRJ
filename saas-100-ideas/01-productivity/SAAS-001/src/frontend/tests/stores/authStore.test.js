import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../../src/stores/authStore';
import axios from 'axios';

vi.mock('axios');

describe('authStore', () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useAuthStore();
        vi.clearAllMocks();
        localStorage.clear();
    });

    it('has initial state', () => {
        expect(store.user).toBeNull();
        expect(store.token).toBeNull();
        expect(store.isAuthenticated).toBe(false);
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
    });

    it('login sets user and token', async () => {
        const mockUser = { id: 1, name: 'Sara', email: 'sara@test.com' };
        const mockToken = 'token-123';

        axios.post.mockResolvedValue({
            data: { data: { user: mockUser, token: mockToken } },
        });

        await store.login({ email: 'sara@test.com', password: 'password' });

        expect(store.user).toEqual(mockUser);
        expect(store.token).toBe(mockToken);
        expect(store.isAuthenticated).toBe(true);
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
        expect(localStorage.getItem('auth_token')).toBe(mockToken);
    });

    it('login handles error', async () => {
        axios.post.mockRejectedValue({
            response: { data: { error: { message: 'Invalid credentials' } } },
        });

        await store.login({ email: 'bad@test.com', password: 'wrong' });

        expect(store.user).toBeNull();
        expect(store.token).toBeNull();
        expect(store.isAuthenticated).toBe(false);
        expect(store.error).toBe('Invalid credentials');
        expect(store.loading).toBe(false);
    });

    it('login handles network error', async () => {
        axios.post.mockRejectedValue(new Error('Network Error'));

        await store.login({ email: 'sara@test.com', password: 'password' });

        expect(store.error).toBe('Network Error');
    });

    it('register creates account and logs in', async () => {
        const mockUser = { id: 2, name: 'New User', email: 'new@test.com' };
        const mockToken = 'token-456';

        axios.post.mockResolvedValue({
            data: { data: { user: mockUser, token: mockToken } },
        });

        await store.register({
            name: 'New User',
            email: 'new@test.com',
            password: 'SecureP@ss123',
            password_confirmation: 'SecureP@ss123',
            workspace_name: 'My Workspace',
        });

        expect(store.user).toEqual(mockUser);
        expect(store.token).toBe(mockToken);
        expect(store.isAuthenticated).toBe(true);
    });

    it('fetchMe loads user profile', async () => {
        const mockUser = { id: 1, name: 'Sara', email: 'sara@test.com' };

        axios.get.mockResolvedValue({
            data: { data: mockUser },
        });

        await store.fetchMe();

        expect(store.user).toEqual(mockUser);
    });

    it('fetchMe handles unauthorized', async () => {
        axios.get.mockRejectedValue({
            response: { status: 401 },
        });

        await store.fetchMe();

        expect(store.user).toBeNull();
        expect(store.isAuthenticated).toBe(false);
    });

    it('logout clears state', async () => {
        store.user = { id: 1, name: 'Sara' };
        store.token = 'token-123';
        store.isAuthenticated = true;
        localStorage.setItem('auth_token', 'token-123');

        axios.post.mockResolvedValue({});

        await store.logout();

        expect(store.user).toBeNull();
        expect(store.token).toBeNull();
        expect(store.isAuthenticated).toBe(false);
        expect(localStorage.getItem('auth_token')).toBeNull();
    });

    it('logout clears state even when API fails', async () => {
        store.user = { id: 1, name: 'Sara' };
        store.token = 'token-123';
        store.isAuthenticated = true;

        axios.post.mockRejectedValue(new Error('Network error'));

        await store.logout();

        expect(store.user).toBeNull();
        expect(store.isAuthenticated).toBe(false);
    });
});
