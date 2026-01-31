/// <reference types="vite/client" />
/// <reference types="vite-svg-loader" />

declare module '*.svg?component' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent;
  export default component;
}

// Google Analytics 4 gtag types
interface Window {
  gtag?: (command: 'event', eventName: string, eventParams?: Record<string, any>) => void;
}
