// @ts-check
import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
  site: 'https://123reinigung.at',
  base: '/',
  server: {
    host: true,
  },
});
