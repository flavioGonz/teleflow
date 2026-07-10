import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

// FASE 0: build a un STAGING (dist/). Un script `deploy.sh` (con sudo) lo copia
// como /var/www/teleflow/assets/app.build.js (NOMBRE DIFERENTE, no pisa app.jsx).
// El bundle nuevo se activa vía feature flag ?build=1.
//
// base: "/assets/" — el bundle se sirve desde /assets/ pero se carga desde /agentes/
// o desde /. Sin este base, los dynamic imports resuelven contra la URL del HTML → 404.
export default defineConfig({
  base: "/assets/",
  plugins: [react()],
  build: {
    outDir: "dist",
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      input: path.resolve(__dirname, "src/main.jsx"),
      output: {
        entryFileNames: "app.build.js",
        chunkFileNames: "chunks/[name]-[hash].js",
        assetFileNames: "chunks/[name]-[hash][extname]"
      }
    }
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
      "@lib": path.resolve(__dirname, "src/lib"),
      "@views": path.resolve(__dirname, "src/views"),
      "@components": path.resolve(__dirname, "src/components")
    }
  }
});
