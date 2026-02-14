import react from '@vitejs/plugin-react';
import path from 'path';
import { defineConfig, type UserConfig } from 'vite';

export default defineConfig(({ command }) => {
  const config: UserConfig = {
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    server: {
      port: 5173,
      proxy: {
        '/api': {
          target: 'http://127.0.0.1:8000',
          changeOrigin: true,
        },
      },
    },
  };

  if (command === 'build') {
    // Build into Laravel's public directory so the SPA is served from the same origin.
    // Assets go to /app-assets/ to avoid conflicting with Laravel's own files.
    config.base = '/';
    config.build = {
      outDir: path.resolve(__dirname, '../public'),
      emptyOutDir: false,
      rollupOptions: {
        output: {
          assetFileNames: 'app-assets/[name]-[hash][extname]',
          chunkFileNames: 'app-assets/[name]-[hash].js',
          entryFileNames: 'app-assets/[name]-[hash].js',
        },
      },
    };
  }

  return config;
});
