import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
    plugins: [
        laravel({
            input: ['css/app.css', 'js/app.js'],
            publicDirectory: path.resolve(__dirname, '../backend/public'),
            buildDirectory: 'build',
            refresh: [
                path.resolve(__dirname, 'views/**'),
            ],
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './js'),
        },
    },
});
