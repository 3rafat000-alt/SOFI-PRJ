import { vi } from 'vitest';
import { config } from '@vue/test-utils';

// Mock localStorage
const localStorageMock = (() => {
    let store = {};
    return {
        getItem: (key) => store[key] || null,
        setItem: (key, value) => { store[key] = String(value); },
        removeItem: (key) => { delete store[key]; },
        clear: () => { store = {}; },
        get length() { return Object.keys(store).length; },
        key: (i) => Object.keys(store)[i] || null,
    };
})();

Object.defineProperty(window, 'localStorage', { value: localStorageMock });

// Mock IntersectionObserver
global.IntersectionObserver = class {
    constructor(callback) { this.callback = callback; }
    observe() { return null; }
    unobserve() { return null; }
    disconnect() { return null; }
};

global.ResizeObserver = class {
    observe() { return null; }
    unobserve() { return null; }
    disconnect() { return null; }
};
