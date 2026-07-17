import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';
import path from 'path';

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
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
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
