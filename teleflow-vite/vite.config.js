import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";  // SWC-based (mas estable que Babel)
import path from "path";

// FIX #321: usamos plugin-react-swc en vez de plugin-react. El plugin basado
// en Babel tiene bugs con la transformacion de JSX que pueden crear referencias
// inconsistentes de React entre chunks lazy. SWC es un drop-in replacement mas
// estable, sin ese bug.
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
