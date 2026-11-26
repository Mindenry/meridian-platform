export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const siteUrl = String(config.public.siteUrl).replace(/\/$/, '')

  const response = await $fetch<{ data: Array<{ locale: string; route: string; url: string }> }>(
    `${internalApiBase()}/public/sitemap-index`,
    { headers: { Accept: 'application/json', 'X-Site': config.public.site } },
  )

  const entries = response.data.map((locale) => `  <sitemap>
    <loc>${siteUrl}/sitemaps/${locale.route}.xml</loc>
    <lastmod>${new Date().toISOString()}</lastmod>
  </sitemap>`).join('\n')

  setHeader(event, 'Content-Type', 'application/xml; charset=utf-8')
  setHeader(event, 'Cache-Control', 'public, max-age=900')

  return `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${entries}
</sitemapindex>`
})
