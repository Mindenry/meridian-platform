import tailwindcss from '@tailwindcss/vite'

const demoStatic = process.env.NUXT_PUBLIC_DEMO_STATIC === 'true'
const demoLocales = ['th', 'en', 'zh-cn']
const demoSeeds = demoLocales.flatMap((locale) => [
  `/${locale}`,
  `/${locale}/search`,
  `/${locale}/accessibility`,
  `/${locale}/credits`,
  `/${locale}/studio`,
])
const demoFeeds = [
  '/robots.txt',
  '/sitemap.xml',
  '/sitemaps/th.xml',
  '/sitemaps/en.xml',
  '/sitemaps/zh-cn.xml',
  '/rss.xml',
  '/th/rss.xml',
  '/en/rss.xml',
  '/zh-cn/rss.xml',
]

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
    // The static demo reads committed fixtures instead.
    apiInternal: demoStatic ? '' : (process.env.NUXT_API_INTERNAL_BASE || 'http://127.0.0.1:8000/api/v1'),

    public: {
      apiBase: demoStatic ? '' : (process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api/v1'),
      site: process.env.NUXT_PUBLIC_SITE || 'meridian',
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || process.env.URL || 'http://localhost:3002',
      studioUrl: process.env.NUXT_PUBLIC_STUDIO_URL || 'http://localhost:3003',
      demoMode: process.env.NUXT_PUBLIC_DEMO_MODE !== 'false',
      demoStatic,
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
    preset: process.env.NITRO_PRESET || undefined,
    compressPublicAssets: true,
    prerender: demoStatic
      ? {
          crawlLinks: true,
          failOnError: true,
          routes: [...demoSeeds, ...demoFeeds],
          ignore: ['/preview/**', '/documents/**'],
        }
      : undefined,
  },

  experimental: {
    payloadExtraction: false,
    renderJsonPayloads: true,
  },

  typescript: {
    strict: true,
  },
})
