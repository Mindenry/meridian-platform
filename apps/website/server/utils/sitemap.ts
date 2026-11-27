import type { H3Event } from 'h3'

interface SitemapEntry {
  loc: string
  lastmod?: string | null
  changefreq?: string
  priority?: string
}

const escapeXml = (value: string) => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&apos;')

/**
 * Builds a localized sitemap from the public API so the website and CMS never
 * disagree about which URLs are publishable.
 */
export async function buildLocaleSitemap(event: H3Event, locale: 'th' | 'en' | 'zh-CN'): Promise<string> {
  const response = await publicApiGet<{ data: { urls: SitemapEntry[] } }>('/public/sitemap', { locale })

  const urls = response.data.urls.map((entry) => `  <url>
    <loc>${escapeXml(entry.loc)}</loc>
    ${entry.lastmod ? `<lastmod>${escapeXml(entry.lastmod)}</lastmod>` : ''}
    ${entry.changefreq ? `<changefreq>${escapeXml(entry.changefreq)}</changefreq>` : ''}
    ${entry.priority ? `<priority>${escapeXml(entry.priority)}</priority>` : ''}
  </url>`).join('\n')

  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  setHeader(event, 'Cache-Control', 'public, max-age=900')

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>`
}
