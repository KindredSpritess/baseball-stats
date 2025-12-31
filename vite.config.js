import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import * as fs from 'fs';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/js/touch-score.js'],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        host: '192.168.1.56',
        // port: 5173,
        // https: {
        //     key: fs.readFileSync('/Users/kindred/.lando/certs/appserver.baseball.key'),
        //     cert: fs.readFileSync('/Users/kindred/.lando/certs/appserver.baseball.crt'),
        // },
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
});
