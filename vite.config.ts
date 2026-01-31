import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import svgLoader from 'vite-svg-loader';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    svgLoader(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
  server: {
    host: 'localhost',
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
});
