import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(({ command }) => ({
  plugins: [
    vue(),
    tailwindcss()
  ],
  test: {
    environment: 'jsdom',
    globals: true
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    allowedHosts: ['vue_app', 'localhost'],
    hmr: {
      clientPort: 80,
    },
    // En Docker/WSL inotify no siempre detecta cambios del volumen montado.
    // Solo aplica con `npm run dev` (command === 'serve'); el build de prod lo ignora.
    watch: command === 'serve'
      ? {
          usePolling: true,
          interval: 300,
        }
      : undefined,
    proxy: {
      '/api': {
        target: 'http://nginx:80',
        changeOrigin: true,
      },
    },
  },
}))
