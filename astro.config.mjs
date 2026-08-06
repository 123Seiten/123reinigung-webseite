// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// https://astro.build/config
export default defineConfig({
  site: 'https://123Seiten.github.io',
  base: '/123reinigung-webseite/',
  server: {
    host: true,
  },
  integrations: [sitemap()],
});
