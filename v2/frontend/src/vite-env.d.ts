/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_SOCKET_URL?: string;
  readonly VITE_PROXY_TARGET?: string;
  readonly VITE_API_BASE?: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
