import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2026-01-01',
  devtools: { enabled: false },
  ssr: true,
  srcDir: 'app/',

  devServer: {
    port: 3002,
    host: '127.0.0.1',
  },

  css: [
    '@fontsource-variable/manrope/index.css',
    '@fontsource-variable/noto-sans-thai/index.css',
    '@fontsource-variable/fraunces/wght.css',
    '@fontsource-variable/fraunces/wght-italic.css',
    '@fontsource/noto-serif-thai/thai-500.css',
    '@fontsource/noto-serif-thai/thai-600.css',
    '@fontsource/ibm-plex-mono/400.css',
    '~/assets/css/main.css',
  ],

  modules: ['@vueuse/nuxt'],

  vite: {
    plugins: [tailwindcss()],
  },

  runtimeConfig: {
    // Server-side fetches inside Compose must target the service network.
    apiInternal: process.env.NUXT_API_INTERNAL_BASE || 'http://127.0.0.1:8000/api/v1',

    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api/v1',
      site: process.env.NUXT_PUBLIC_SITE || 'meridian',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || 'http://localhost:3002',
      studioUrl: process.env.NUXT_PUBLIC_STUDIO_URL || 'http://localhost:3003',
      demoMode: process.env.NUXT_PUBLIC_DEMO_MODE !== 'false',
    },
  },

  app: {
    // Route changes are instantaneous. A global fade transition can be
    // interrupted by a fast language switch and leave both pages half-mounted.
    pageTransition: false,
    head: {
      htmlAttrs: { lang: 'th' },
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1, viewport-fit=cover',
      meta: [
        { name: 'theme-color', content: '#0E1411' },
        { name: 'format-detection', content: 'telephone=no' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
        { rel: 'apple-touch-icon', href: '/favicon.svg' },
      ],
    },
  },

  nitro: {
    compressPublicAssets: true,
  },

  experimental: {
    payloadExtraction: false,
    renderJsonPayloads: true,
  },

  typescript: {
    strict: true,
  },
})
