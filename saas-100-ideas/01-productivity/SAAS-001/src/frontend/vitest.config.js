import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [vue()],
    test: {
        globals: true,
        environment: 'jsdom',
        include: ['tests/**/*.test.{js,ts}', 'src/**/*.test.{js,ts}'],
        coverage: {
            provider: 'v8',
            include: ['src/**/*.{js,ts,vue}'],
            exclude: [
                'src/main.js',
                'src/router/**',
                'src/assets/**',
                'src/**/*.test.{js,ts}',
            ],
            thresholds: {
                statements: 90,
                branches: 85,
                functions: 90,
                lines: 90,
            },
            reporter: ['text', 'clover', 'html'],
        },
        setupFiles: ['./tests/setup.js'],
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
        },
    },
});
