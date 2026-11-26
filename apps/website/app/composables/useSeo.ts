import type { Alternate, PageData, SeoSchema } from '~/types'

function schemaScript(schema: Record<string, any>[]) {
  if (!schema?.length) return []
  const json = JSON.stringify(schema.length === 1 ? schema[0] : schema).replace(/</g, '\\u003c')
  return [{ type: 'application/ld+json', innerHTML: json, key: 'page-schema' }]
}

interface SeoPageLike {
  type: string
  seo: SeoSchema
  alternates: Alternate[]
  content_locale?: string
  locale?: string
}

export function usePageSeo(page: Ref<SeoPageLike | null | undefined>) {
  const { localeCode, alternates } = useLocale()
  const config = useRuntimeConfig()

  watchEffect(() => {
    alternates.value = page.value?.alternates ?? []
  })

  useHead((): any => {
    const data = page.value
    if (!data) return {}

    const seo = data.seo
    const contentLocale = data.content_locale || data.locale || localeCode.value
    const htmlLang = contentLocale === 'zh-CN' ? 'zh-CN' : contentLocale

    return {
      title: seo.title,
      htmlAttrs: { lang: htmlLang },
      meta: [
        { name: 'description', content: seo.description },
        { name: 'robots', content: seo.robots },
        { name: 'page-type', content: data.type },
        { name: 'site-kind', content: 'service' },
        { property: 'og:type', content: seo.og.type || 'website' },
        { property: 'og:title', content: seo.og.title },
        { property: 'og:description', content: seo.og.description },
        { property: 'og:url', content: seo.canonical },
        { property: 'og:site_name', content: 'Meridian Industrial Group' },
        { property: 'og:locale', content: localeCode.value === 'th' ? 'th_TH' : localeCode.value === 'zh-CN' ? 'zh_CN' : 'en_US' },
        ...(seo.og.image ? [{ property: 'og:image', content: seo.og.image }, { name: 'twitter:image', content: seo.og.image }] : []),
        { name: 'twitter:card', content: 'summary_large_image' },
        { name: 'twitter:title', content: seo.og.title },
        { name: 'twitter:description', content: seo.og.description },
      ],
      link: [
        { rel: 'canonical', href: seo.canonical },
        ...(data.alternates ?? []).map((alternate: Alternate) => ({
          rel: 'alternate',
          hreflang: alternate.hreflang,
          href: alternate.url,
        })),
      ],
      script: schemaScript(seo.schema),
    }
  })
}

export function useSimpleSeo(options: {
  title: string
  description: string
  path: string
  image?: string | null
  type?: string
}) {
  const config = useRuntimeConfig()
  const { localeCode, alternates } = useLocale()
  const canonical = new URL(options.path, config.public.siteUrl).toString()
  const image = options.image || `${config.public.siteUrl}/images/og-default.jpg`

  // Static pages have no per-page alternates. Clear the previous page's state
  // so the language switcher never reuses another page's translated slugs.
  alternates.value = []

  useHead((): any => ({
    title: options.title,
    htmlAttrs: { lang: localeCode.value === 'zh-CN' ? 'zh-CN' : localeCode.value },
    meta: [
      { name: 'description', content: options.description },
      { name: 'robots', content: 'index,follow' },
      { property: 'og:type', content: options.type || 'website' },
      { property: 'og:title', content: options.title },
      { property: 'og:description', content: options.description },
      { property: 'og:url', content: canonical },
      { property: 'og:image', content: image },
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: options.title },
      { name: 'twitter:description', content: options.description },
    ],
    link: [{ rel: 'canonical', href: canonical }],
  }))
}

export function useSchema(schema: Record<string, any> | Record<string, any>[]) {
  useHead((): any => ({
    script: schemaScript(Array.isArray(schema) ? schema : [schema]),
  }))
}

export function absoluteMedia(url?: string | null): string | null {
  if (!url) return null
  if (url.startsWith('http')) return url
  const config = useRuntimeConfig()
  return new URL(url, config.public.siteUrl).toString()
}
