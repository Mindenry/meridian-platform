/**
 * Captures the public API responses used by the website into a static fixture
 * set. The demo build reads these files, so Netlify does not need Laravel,
 * PostgreSQL, Redis, Meilisearch, or MinIO.
 *
 * Usage:
 *   API_BASE=http://localhost:8000/api/v1 node scripts/snapshot-demo.mjs
 */
import { mkdir, rm, writeFile, cp } from 'node:fs/promises'
import { dirname, join } from 'node:path'

const API_BASE = (process.env.API_BASE || 'http://localhost:8000/api/v1').replace(/\/$/, '')
const SITE = process.env.API_SITE || 'meridian'
const OUTPUT = join(process.cwd(), 'public/demo/api')
const SEARCH_OUTPUT = join(process.cwd(), 'public/demo/search.json')

const locales = [
  { api: 'th', route: 'th' },
  { api: 'en', route: 'en' },
  { api: 'zh-CN', route: 'zh-cn' },
]

const collectionPrefixes = [
  '/solutions/',
  '/industries/',
  '/case-studies/',
  '/insights/',
  '/careers/',
]

const responses = new Map()
const searchEntries = new Map()

async function api(path, query = {}) {
  const url = new URL(`${API_BASE}${path}`)

  for (const [key, value] of Object.entries(query)) {
    if (value === undefined || value === null || value === '') continue
    url.searchParams.set(key, String(value))
  }

  for (let attempt = 0; attempt < 6; attempt += 1) {
    const response = await fetch(url, {
      headers: { Accept: 'application/json', 'X-Site': SITE },
    })

    if (response.status === 429) {
      await new Promise((resolve) => setTimeout(resolve, 10_000))
      continue
    }

    if (!response.ok) {
      throw new Error(`${path} failed with ${response.status}`)
    }

    // The public API allows 120 requests per minute; stay below it so a
    // snapshot does not trip the limiter halfway through.
    await new Promise((resolve) => setTimeout(resolve, 550))

    return response.json()
  }

  throw new Error(`${path} was rate limited too many times`)
}

function key(path, query = {}) {
  const params = new URLSearchParams()

  for (const name of Object.keys(query).sort()) {
    const value = query[name]
    if (value === undefined || value === null || value === '') continue
    params.set(name, String(value))
  }

  const serialized = params.toString()

  return path + (serialized ? `?${serialized}` : '')
}

function store(path, query, body) {
  responses.set(key(path, query), body)
}

function addSearch(entry) {
  const id = `${entry.locale}:${entry.type}:${entry.url}`
  if (!searchEntries.has(id)) searchEntries.set(id, entry)
}

function stripTags(value = '') {
  return String(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
}

function entriesFromList(items, locale, type, titleField, excerptField) {
  for (const item of items || []) {
    if (!item?.url) continue

    addSearch({
      locale,
      type,
      title: stripTags(item[titleField] || ''),
      excerpt: stripTags(item[excerptField] || '').slice(0, 220),
      url: item.url,
    })
  }
}

for (const locale of locales) {
  const query = { locale: locale.api }

  const bootstrap = await api('/public/bootstrap', query)
  store('/public/bootstrap', query, bootstrap)

  const home = await api('/public/home', query)
  store('/public/home', query, home)

  for (const collection of ['solutions', 'industries']) {
    const list = await api(`/public/${collection}`, query)
    store(`/public/${collection}`, query, list)
    entriesFromList(list.data, locale.route, collection === 'solutions' ? 'solution' : 'industry', 'title', collection === 'solutions' ? 'overview' : 'tagline')

    for (const item of list.data || []) {
      const detail = await api(`/public/${collection}/${encodeURIComponent(item.slug)}`, query)
      store(`/public/${collection}/${encodeURIComponent(item.slug)}`, query, detail)
    }
  }

  const caseStudies = await api('/public/case-studies', { ...query, per_page: 100 })
  store('/public/case-studies', query, caseStudies)
  entriesFromList(caseStudies.data, locale.route, 'case_study', 'title', 'summary')
  for (const item of caseStudies.data || []) {
    const detail = await api(`/public/case-studies/${encodeURIComponent(item.slug)}`, query)
    store(`/public/case-studies/${encodeURIComponent(item.slug)}`, query, detail)
  }

  const articles = await api('/public/articles', { ...query, per_page: 100 })
  store('/public/articles', query, articles)
  entriesFromList(articles.data, locale.route, 'article', 'title', 'excerpt')
  for (const item of articles.data || []) {
    const detail = await api(`/public/articles/${encodeURIComponent(item.slug)}`, query)
    store(`/public/articles/${encodeURIComponent(item.slug)}`, query, detail)
  }

  const careers = await api('/public/careers', { ...query, per_page: 100 })
  store('/public/careers', query, careers)
  entriesFromList(careers.data, locale.route, 'job', 'title', 'summary')
  for (const item of careers.data || []) {
    const detail = await api(`/public/careers/${encodeURIComponent(item.slug)}`, query)
    store(`/public/careers/${encodeURIComponent(item.slug)}`, query, detail)
  }

  const documents = await api('/public/documents', { ...query, per_page: 100 })
  store('/public/documents', query, documents)

  const team = await api('/public/team', { ...query, per_page: 100 }).catch(() => null)
  if (team) store('/public/team', query, team)

  const sitemap = await api('/public/sitemap', query)
  store('/public/sitemap', query, sitemap)

  const sitemapIndex = await api('/public/sitemap-index')
  store('/public/sitemap-index', {}, sitemapIndex)

  const pageSlugs = new Set()

  for (const entry of sitemap.data?.urls || []) {
    const pathname = decodeURIComponent(new URL(entry.loc).pathname)
    const parts = pathname.split('/').filter(Boolean)

    if (parts.length !== 2) continue
    if (collectionPrefixes.some((prefix) => pathname.includes(prefix))) continue
    if (['search', 'credits', 'accessibility', 'preview'].includes(parts[1])) continue

    pageSlugs.add(parts[1])
  }

  for (const slug of pageSlugs) {
    const detail = await api(`/public/pages/${encodeURIComponent(slug)}`, query)
    store(`/public/pages/${encodeURIComponent(slug)}`, query, detail)

    if (detail?.data?.title) {
      addSearch({
        locale: locale.route,
        type: 'page',
        title: stripTags(detail.data.title),
        excerpt: stripTags(detail.data.excerpt || '').slice(0, 220),
        url: detail.data.url || `/${locale.route}/${slug}`,
      })
    }
  }

  // The site links to these canonical page slugs from CTAs and navigation.
  // Fetch them for every locale so the demo also covers fallback content.
  for (const slug of ['about', 'contact', 'investor-relations', 'careers', 'privacy-policy', 'cookie-policy', 'terms-of-use', 'data-protection']) {
    if (pageSlugs.has(slug)) continue
    const detail = await api(`/public/pages/${encodeURIComponent(slug)}`, query)
    store(`/public/pages/${encodeURIComponent(slug)}`, query, detail)
  }

  console.log(`${locale.api}: ${responses.size} responses captured so far`)
}

await rm(OUTPUT, { recursive: true, force: true })
await mkdir(join(OUTPUT, 'data'), { recursive: true })

const manifest = {}
let index = 0

for (const [responseKey, body] of responses) {
  const relative = `data/${String(index++).padStart(4, '0')}.json`
  const target = join(OUTPUT, relative)
  await mkdir(dirname(target), { recursive: true })
  await writeFile(target, JSON.stringify(body), 'utf8')
  manifest[responseKey] = relative
}

await writeFile(join(OUTPUT, 'manifest.json'), JSON.stringify(manifest), 'utf8')
await mkdir(dirname(SEARCH_OUTPUT), { recursive: true })
await writeFile(SEARCH_OUTPUT, JSON.stringify([...searchEntries.values()]), 'utf8')

// Media and document URLs are rewritten to local paths by the demo adapter.
const mediaSource = join(process.cwd(), '..', 'api', 'storage', 'app', 'public', 'media')
const mediaTarget = join(process.cwd(), 'public', 'demo-media', 'media')
const documentsSource = join(mediaSource, 'seed', 'documents')
const documentsTarget = join(process.cwd(), 'public', 'documents')

await rm(mediaTarget, { recursive: true, force: true })
await cp(mediaSource, mediaTarget, { recursive: true })
await mkdir(documentsTarget, { recursive: true })
await cp(documentsSource, documentsTarget, { recursive: true })

console.log(`Wrote ${responses.size} API fixtures and ${searchEntries.size} search entries.`)
