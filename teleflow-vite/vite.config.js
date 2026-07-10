import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import path from "path";

const reactRoot    = path.resolve(__dirname, "node_modules/react");
const reactDomRoot = path.resolve(__dirname, "node_modules/react-dom");

// FASE 0: build a un STAGING (dist/). Se copia como /var/www/teleflow/assets/app.build.js
// (nombre diferente, no pisa app.jsx). base:"/assets/" para que dynamic imports
// resuelvan bien desde cualquier shell.
//
// FIX #321: aliases absolutos a UNA ruta canonica de react/react-dom evitan que
// Vite genere 2 copias del modulo (una CJS del pre-bundle, otra ESM del import).
// NO usar optimizeDeps.include para React — el pre-bundle CJS colisiona con ESM.
export default defineConfig({
  base: "/assets/",
  plugins: [react()],
  resolve: {
    alias: [
      { find: /^react$/,               replacement: reactRoot },
      { find: /^react\/(.*)$/,        replacement: reactRoot + "/$1" },
      { find: /^react-dom$/,           replacement: reactDomRoot },
      { find: /^react-dom\/(.*)$/,    replacement: reactDomRoot + "/$1" },
      { find: "@",           replacement: path.resolve(__dirname, "src") },
      { find: "@lib",        replacement: path.resolve(__dirname, "src/lib") },
      { find: "@views",      replacement: path.resolve(__dirname, "src/views") },
      { find: "@components", replacement: path.resolve(__dirname, "src/components") }
    ],
    dedupe: ["react", "react-dom"]
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
        assetFileNames: "chunks/[name]-[hash][extname]"
      }
    }
  }
});
