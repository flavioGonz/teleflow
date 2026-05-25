import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// API target: en local apunta a localhost; dentro de docker compose,
// el backend se resuelve por nombre de servicio `backend`.
const API_TARGET = process.env.VITE_PROXY_TARGET || 'http://localhost:4000';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 3000,
    proxy: {
      '/api': API_TARGET,
    },
  },
});
