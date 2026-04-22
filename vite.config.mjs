import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react({
            jsxRuntime: 'classic',
        }),
    ],
    build: {
        outDir: 'assets/admin/gen',
        emptyOutDir: false,
        rollupOptions: {
            input: './assets/admin/src/index.jsx',
            output: {
                format: 'iife',
                entryFileNames: 'admin-paysafe.js',
                globals: {
                    '@wordpress/element': 'wp.element',
                    '@wordpress/components': 'wp.components',
                    '@wordpress/data': 'wp.data',
                    '@wordpress/i18n': 'wp.i18n',
                },
            },
            external: [
                '@wordpress/element',
                '@wordpress/components',
                '@wordpress/data',
                '@wordpress/i18n',
            ],
        },
    },
    optimizeDeps: {
        exclude: [
            '@wordpress/element',
            '@wordpress/components',
            '@wordpress/data',
            '@wordpress/i18n',
        ],
    },
});
