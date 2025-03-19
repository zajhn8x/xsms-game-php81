
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'workspace.NguynTng13.repl.co'
        },
        allowedHosts: [
            'b3a71595-3a48-44f7-8084-42d695b792b6-00-tdw9bj7rp8vd.sisko.replit.dev',
            '.replit.dev'
        ]
    }
});
