import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useProjectStore } from '../../src/stores/projectStore';
import axios from 'axios';

vi.mock('axios');

describe('projectStore', () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useProjectStore();
        vi.clearAllMocks();
    });

    it('has initial state', () => {
        expect(store.projects).toEqual([]);
        expect(store.currentProject).toBeNull();
        expect(store.loading).toBe(false);
        expect(store.error).toBeNull();
        expect(store.filters).toEqual({});
    });

    it('fetchProjects loads projects', async () => {
        const mockProjects = [
            { id: 1, name: 'Project Alpha', status: 'active' },
            { id: 2, name: 'Project Beta', status: 'archived' },
        ];

        axios.get.mockResolvedValue({
            data: { data: mockProjects, meta: { current_page: 1, last_page: 1, total: 2 } },
        });

        await store.fetchProjects();

        expect(store.projects).toEqual(mockProjects);
        expect(store.loading).toBe(false);
    });

    it('fetchProjects with status filter', async () => {
        axios.get.mockResolvedValue({
            data: { data: [], meta: { total: 0 } },
        });

        await store.fetchProjects({ status: 'active' });

        expect(axios.get).toHaveBeenCalledWith(
            expect.any(String),
            expect.objectContaining({
                params: expect.objectContaining({ status: 'active' }),
            })
        );
    });

    it('fetchProjects handles error', async () => {
        axios.get.mockRejectedValue({
            response: { data: { error: { message: 'Failed' } } },
        });

        await store.fetchProjects();

        expect(store.error).toBe('Failed');
    });

    it('createProject adds project', async () => {
        const newProject = { id: 3, name: 'New Project', status: 'active' };

        axios.post.mockResolvedValue({
            data: { data: newProject, meta: {} },
        });

        await store.createProject({ name: 'New Project', description: 'Desc' });

        expect(store.projects).toContainEqual(newProject);
    });

    it('updateProject updates in state', async () => {
        store.projects = [
            { id: 1, name: 'Old', status: 'active' },
        ];

        const updated = { id: 1, name: 'Updated', status: 'active' };

        axios.put.mockResolvedValue({
            data: { data: updated },
        });

        await store.updateProject(1, { name: 'Updated' });

        expect(store.projects[0].name).toBe('Updated');
    });

    it('updateProject also updates currentProject', async () => {
        store.currentProject = { id: 1, name: 'Old', status: 'active' };

        axios.put.mockResolvedValue({
            data: { data: { id: 1, name: 'Updated', status: 'active' } },
        });

        await store.updateProject(1, { name: 'Updated' });

        expect(store.currentProject.name).toBe('Updated');
    });

    it('deleteProject removes from state', async () => {
        store.projects = [
            { id: 1, name: 'To Delete' },
            { id: 2, name: 'Keep' },
        ];

        axios.delete.mockResolvedValue({
            data: { data: { message: 'Project deleted.' } },
        });

        await store.deleteProject(1);

        expect(store.projects).toHaveLength(1);
        expect(store.projects[0].id).toBe(2);
    });

    it('fetchProject sets currentProject', async () => {
        const project = { id: 1, name: 'Detail', status: 'active' };

        axios.get.mockResolvedValue({
            data: { data: project },
        });

        await store.fetchProject(1);

        expect(store.currentProject).toEqual(project);
    });

    it('setFilters updates filters', () => {
        store.setFilters({ status: 'archived' });

        expect(store.filters).toEqual({ status: 'archived' });
    });
});
