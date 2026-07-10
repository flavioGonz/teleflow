import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

// FASE 0: build a un STAGING (dist/). Un script `deploy.sh` (con sudo) lo copia
// como /var/www/teleflow/assets/app.build.js (NOMBRE DIFERENTE, no pisa app.jsx).
//
// base: "/assets/" — el bundle se sirve desde /assets/ pero se carga desde /agentes/
// o desde /. Sin este base, los dynamic imports resuelven contra la URL del HTML → 404.
//
// resolve.dedupe: fuerza UNA SOLA COPIA de react/react-dom en el bundle.
//
// manualChunks: fuerza stores/* al bundle principal. Si un store queda en un chunk
// shared (ej: pbxData-XXX.js) y varios lazy chunks lo importan, cada vez puede
// obtener una instancia distinta del store → useSyncExternalStore falla con #321
// porque el subscribe/getState no matcha entre lazy chunks.
export default defineConfig({
  base: "/assets/",
  plugins: [react()],
  resolve: {
    alias: {
      "@":           path.resolve(__dirname, "src"),
      "@lib":        path.resolve(__dirname, "src/lib"),
      "@views":      path.resolve(__dirname, "src/views"),
      "@components": path.resolve(__dirname, "src/components")
    },
    dedupe: ["react", "react-dom"]
  },
  optimizeDeps: {
    include: ["react", "react-dom", "react-dom/client", "react-router-dom", "zustand"]
  },
  build: {
    outDir: "dist",
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      input: path.resolve(__dirname, "src/main.jsx"),
      output: {
        entryFileNames: "app.build.js",
        chunkFileNames: "chunks/[name]-[hash].js",
        assetFileNames: "chunks/[name]-[hash][extname]",
        // Forzar stores y lib al bundle principal — evita multi-instancias
        // en chunks lazy que romperían useSyncExternalStore (#321).
        manualChunks(id) {
          if (id.includes("/src/stores/") || id.includes("/src/lib/") || id.includes("node_modules/zustand")) {
            return undefined; // → bundle principal (app.build.js)
          }
        }
      }
    }
  }
});
