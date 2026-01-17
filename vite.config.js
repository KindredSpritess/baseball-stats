import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import * as fs from 'fs';

// Check if Lando certificates exist
const keyPath = '/Users/kindred/.lando/certs/appserver.baseball.key';
const certPath = '/Users/kindred/.lando/certs/appserver.baseball.crt';
const hasLandoCerts = fs.existsSync(keyPath) && fs.existsSync(certPath);

const serverConfig = hasLandoCerts ? {
    host: 'baseball.lndo.site',
    port: 5173,
    https: {
        key: fs.readFileSync(keyPath),
        cert: fs.readFileSync(certPath),
    },
    headers: {
        'Access-Control-Allow-Origin': '*',
    },
} : {
    host: '0.0.0.0',
    port: 5173,
    headers: {
        'Access-Control-Allow-Origin': '*',
    },
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/js/preferences.js', 'resources/js/touch-score.js'],
            refresh: true,
        }),
        vue(),
    ],
    server: serverConfig,
});
