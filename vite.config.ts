import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { execSync } from 'child_process';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

const gitVersion = execSync('git describe --tags --always').toString().trim();

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    define: {
        'import.meta.env.VITE_APP_VERSION': JSON.stringify(gitVersion),
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules')) {
                        if (id.includes('@vueform')) return 'vendor-vueform';
                        if (id.includes('lodash-es')) return 'vendor-lodash';
                        if (id.includes('quill')) return 'vendor-quill';
                        return 'vendor';
                    }
                },
            },
        },
    },
});
