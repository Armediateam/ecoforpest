import autoprefixer from 'autoprefixer';
import laravel from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/filament/secret/theme.css'],
            refresh: true,
        }),
    ],
    css: {
        postcss: {
            plugins: [tailwindcss(), autoprefixer()],
        },
    },
    build: {
        outDir: 'public/build/filament',
    },
});
