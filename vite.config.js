import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        outDir: 'public/assets', // compiled assets go here
        emptyOutDir: false,      // do NOT clear entire public folder
    },
    plugins: [
        laravel({
            input: [
                'public/assets/css/app.css',
                'public/assets/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
