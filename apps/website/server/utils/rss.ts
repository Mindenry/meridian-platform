import type { H3Event } from 'h3'

interface ArticleFeedItem {
  title: string
  url: string
  excerpt: string
  published_at: string
  image?: { url?: string | null } | null
}

const escapeXml = (value: string) => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&apos;')

const titles: Record<string, string> = {
  th: 'Meridian Industrial Group — บทความ',
  en: 'Meridian Industrial Group — Insights',
  'zh-CN': 'Meridian Industrial Group — 洞察',
}

const descriptions: Record<string, string> = {
  th: 'ข้อสังเกตทางเทคนิค บทเรียนจากโครงการ และประกาศจากทีมงาน Meridian',
  en: 'Technical observations, project lessons, and company notes from the Meridian team.',
  'zh-CN': '来自 Meridian 团队的技术观察、项目经验与公司动态。',
}

export async function buildLocaleRss(event: H3Event, locale: 'th' | 'en' | 'zh-CN'): Promise<string> {
  const config = useRuntimeConfig()
  const siteUrl = String(config.public.siteUrl).replace(/\/$/, '')
  const routeLocale = locale === 'zh-CN' ? 'zh-cn' : locale
  const feedUrl = `${siteUrl}/${routeLocale}/rss.xml`

  const response = await $fetch<{ data: ArticleFeedItem[] }>(
    `${internalApiBase()}/public/articles`,
    {
      headers: { Accept: 'application/json', 'X-Site': config.public.site },
      query: { locale, per_page: 20 },
    },
  ).catch(() => ({ data: [] as ArticleFeedItem[] }))

  const items = response.data.map((article) => {
    const link = article.url.startsWith('http') ? article.url : `${siteUrl}${article.url}`
    const image = article.image?.url
      ? `<enclosure url="${escapeXml(article.image.url)}" type="image/webp" />`
      : ''

    return `    <item>
      <title>${escapeXml(article.title)}</title>
      <link>${escapeXml(link)}</link>
      <guid isPermaLink="true">${escapeXml(link)}</guid>
      <description>${escapeXml(article.excerpt)}</description>
      <pubDate>${new Date(article.published_at).toUTCString()}</pubDate>
      ${image}
    </item>`
  }).join('\n')

  setHeader(event, 'Content-Type', 'application/rss+xml; charset=utf-8')
  setHeader(event, 'Cache-Control', 'public, max-age=900')

  return `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>${escapeXml(titles[locale])}</title>
    <link>${escapeXml(`${siteUrl}/${routeLocale}/insights`)}</link>
    <description>${escapeXml(descriptions[locale])}</description>
    <language>${locale === 'zh-CN' ? 'zh-CN' : locale}</language>
    <atom:link href="${escapeXml(feedUrl)}" rel="self" type="application/rss+xml" />
    <lastBuildDate>${new Date().toUTCString()}</lastBuildDate>
${items}
  </channel>
</rss>`
}
