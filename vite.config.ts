import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import { existsSync, readFileSync } from 'node:fs';
import path from 'path';

const laragonSslDir = path.join(process.env.LARAGON_ROOT ?? 'C:/laragon', 'etc', 'ssl');
const devKeyPath = process.env.VITE_DEV_SERVER_KEY ?? path.join(laragonSslDir, 'laragon.key');
const devCertPath = process.env.VITE_DEV_SERVER_CERT ?? path.join(laragonSslDir, 'laragon.crt');
const devHttps = existsSync(devKeyPath) && existsSync(devCertPath)
    ? { key: readFileSync(devKeyPath), cert: readFileSync(devCertPath) }
    : undefined;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.scss', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
        AutoImport({
            imports: ['vue', 'vue-router', 'pinia', '@vueuse/core'],
            resolvers: [ElementPlusResolver()],
            dts: 'resources/js/auto-imports.d.ts',
            dirs: ['resources/js/stores'],
            vueTemplate: true,
        }),
        Components({
            resolvers: [ElementPlusResolver({ importStyle: 'sass' })],
            dts: 'resources/js/components.d.ts',
            dirs: ['resources/js/components'],
        }),
    ],
    server: {
        host: '127.0.0.1',
        ...(devHttps ? { https: devHttps } : {}),
        hmr: {
            host: '127.0.0.1',
            ...(devHttps ? { protocol: 'wss' as const } : {}),
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, './resources/js'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                additionalData: `@use "@/assets/element-variables.scss" as *;`,
            },
        },
    },
});
