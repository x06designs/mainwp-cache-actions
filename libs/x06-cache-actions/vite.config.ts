import { v4wp } from '@kucrut/vite-for-wp';
import externalGlobals from 'rollup-plugin-external-globals';
import { defineConfig } from 'vite';

/** The WordPress packages the bundle reads from WordPress's globals instead of bundling. */
const globals = { '@wordpress/i18n': 'wp.i18n' };

export default defineConfig({
  plugins: [
    v4wp({ input: { 'backend/admin': 'web/backend/index.ts' }, outDir: 'build' }),
    externalGlobals(globals),
  ],
  build: {
    sourcemap: false,
    rollupOptions: {
      external: Object.keys(globals),
      output: { format: 'iife', globals },
    },
  },
});
