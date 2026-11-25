import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/filament/fm/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
