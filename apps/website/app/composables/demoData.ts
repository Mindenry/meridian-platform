import type { SearchGroup } from '~/types'

const DEMO_BASE = '/demo/api'
const TYPE_ORDER = ['page', 'solution', 'industry', 'case_study', 'article', 'job']

const typeLabels: Record<string, Record<string, string>> = {
  page: { en: 'Pages', th: 'หน้า', 'zh-CN': '页面' },
  solution: { en: 'Solutions', th: 'โซลูชัน', 'zh-CN': '解决方案' },
  industry: { en: 'Industries', th: 'อุตสาหกรรม', 'zh-CN': '行业' },
  case_study: { en: 'Case studies', th: 'ผลงาน', 'zh-CN': '案例' },
  article: { en: 'Insights', th: 'บทความ', 'zh-CN': '洞察' },
  job: { en: 'Open roles', th: 'ตำแหน่งงาน', 'zh-CN': '开放职位' },
}

export function isDemoStatic(): boolean {
  return Boolean(useRuntimeConfig().public.demoStatic)
}

export function demoKey(path: string, query: Record<string, any> = {}): string {
  const params = new URLSearchParams()

  for (const name of Object.keys(query).sort()) {
    const value = query[name]
    if (value === undefined || value === null || value === '') continue
    params.set(name, String(value))
  }

  const serialized = params.toString()

  return path + (serialized ? `?${serialized}` : '')
}

let manifestPromise: Promise<Record<string, string>> | null = null
const responseCache = new Map<string, any>()
let searchPromise: Promise<any[]> | null = null

async function readJson(relative: string): Promise<any> {
  if (import.meta.server) {
    const fs = await import('node:fs/promises')
    const path = await import('node:path')
    const file = path.join(process.cwd(), 'public', 'demo', 'api', relative)
    return JSON.parse(await fs.readFile(file, 'utf8'))
  }

  const response = await fetch(`${DEMO_BASE}/${relative}`)
  if (!response.ok) throw new Error(`Demo fixture ${relative} returned ${response.status}`)
  return response.json()
}

async function manifest(): Promise<Record<string, string>> {
  if (!manifestPromise) manifestPromise = readJson('manifest.json')
  return manifestPromise
}

function normalize(value: any, siteUrl: string): any {
  if (typeof value === 'string') {
    return value
      .replaceAll('http://localhost:8000/storage', '/demo-media')
      .replaceAll('http://127.0.0.1:8000/storage', '/demo-media')
      .replaceAll('http://localhost:3002', siteUrl)
      .replaceAll('http://127.0.0.1:3002', siteUrl)
  }

  if (Array.isArray(value)) return value.map((item) => normalize(item, siteUrl))

  if (value && typeof value === 'object') {
    return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, normalize(item, siteUrl)]))
  }

  return value
}

async function loadKey(key: string, siteUrl: string): Promise<any> {
  if (responseCache.has(key)) return responseCache.get(key)

  const files = await manifest()
  const file = files[key]

  if (!file) throw new Error(`Missing demo fixture for ${key}`)

  const data = normalize(await readJson(file), siteUrl)
  responseCache.set(key, data)

  return data
}

function localeOf(query: Record<string, any>): string {
  return String(query.locale || 'en')
}

function searchLocale(locale: string): string {
  return locale.toLowerCase() === 'zh-cn' ? 'zh-cn' : locale.toLowerCase()
}

async function searchIndex(): Promise<any[]> {
  if (!searchPromise) {
    searchPromise = import.meta.server
      ? (async () => {
          const fs = await import('node:fs/promises')
          const path = await import('node:path')
          return JSON.parse(await fs.readFile(path.join(process.cwd(), 'public/demo/search.json'), 'utf8'))
        })()
      : fetch('/demo/search.json').then((response) => response.json())
  }

  return searchPromise
}

function highlight(text: string, query: string): string {
  const escaped = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')

  if (!query) return escaped

  const pattern = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')

  return escaped.replace(new RegExp(`(${pattern})`, 'giu'), '<mark>$1</mark>')
}

async function demoSearch(query: string, locale: string, limit: number) {
  const term = query.trim().toLocaleLowerCase()
  const entries = await searchIndex()

  const matches = (entries as any[])
    .filter((entry) => entry.locale === searchLocale(locale))
    .filter((entry) => !term || `${entry.title} ${entry.excerpt}`.toLocaleLowerCase().includes(term))
    .slice(0, limit)

  const groups: SearchGroup[] = TYPE_ORDER
    .map((type) => ({
      type,
      label: typeLabels[type]?.[locale] ?? type,
      items: matches
        .filter((entry) => entry.type === type)
        .map((entry) => ({
          type: entry.type,
          title: highlight(entry.title, query),
          excerpt: highlight(entry.excerpt, query),
          url: entry.url,
          meta: {},
        })),
    }))
    .filter((group) => group.items.length)

  return { data: { groups, total: matches.length, engine: 'demo' } }
}

function paginate<T>(items: T[], query: Record<string, any>) {
  const page = Math.max(1, Number(query.page || 1))
  const perPage = Math.max(1, Number(query.per_page || 9))
  const total = items.length
  const lastPage = Math.max(1, Math.ceil(total / perPage))
  const from = (page - 1) * perPage

  return {
    items: items.slice(from, from + perPage),
    meta: {
      current_page: page,
      last_page: lastPage,
      per_page: perPage,
      total,
      from: total ? from + 1 : null,
      to: Math.min(from + perPage, total) || null,
    },
  }
}

function matchesTerm(item: any, query: string, fields: string[]) {
  if (!query) return true
  const haystack = fields.map((field) => String(item?.[field] || '')).join(' ').toLocaleLowerCase()
  return haystack.includes(query.toLocaleLowerCase())
}

export async function demoGet<T = any>(path: string, query: Record<string, any> = {}): Promise<T> {
  const config = useRuntimeConfig()
  const siteUrl = String(config.public.siteUrl || '').replace(/\/$/, '')
  const locale = localeOf(query)
  const baseKey = demoKey(path, { locale })

  if (path === '/public/search') {
    return demoSearch(String(query.q || ''), locale, Number(query.limit || 8)) as T
  }

  if (path === '/public/search/suggestions') {
    const result = await demoSearch(String(query.q || ''), locale, 6)
    return { data: result.data.groups.flatMap((group) => group.items) } as T
  }

  if (path === '/public/articles' || path === '/public/case-studies') {
    const base = await loadKey(baseKey, siteUrl)
    const items = (base.data || []).filter((item: any) => {
      if (path === '/public/articles') {
        const category = String(query.category || '')
        if (category && item.category?.slug !== category) return false
        return matchesTerm(item, String(query.q || ''), ['title', 'excerpt'])
      }

      const industry = String(query.industry || '')
      if (industry && item.industry?.slug !== industry && item.industry?.key !== industry) return false
      return matchesTerm(item, String(query.q || ''), ['title', 'summary'])
    })

    const { items: pageItems, meta } = paginate(items, query)

    return {
      ...base,
      data: pageItems,
      meta,
    } as T
  }

  if (path === '/public/solutions' || path === '/public/industries') {
    return (await loadKey(baseKey, siteUrl)) as T
  }

  if (path === '/public/careers' || path === '/public/documents' || path === '/public/team') {
    const base = await loadKey(baseKey, siteUrl)
    const { items, meta } = paginate(base.data || [], { ...query, per_page: Number(query.per_page || 100) })
    return { ...base, data: items, meta } as T
  }

  return (await loadKey(demoKey(path, query), siteUrl)) as T
}

export async function demoPost(_path: string): Promise<any> {
  const { localeCode } = useLocale()
  const reference = `DEMO-${Date.now().toString(36).toUpperCase()}`

  const message = localeCode.value === 'th'
    ? 'นี่คือเดโมแบบ static จึงไม่มีการส่งข้อมูลจริง'
    : localeCode.value === 'zh-CN'
      ? '这是静态演示，没有发送真实数据。'
      : 'This is a static demo; no data was sent.'

  return { message, reference }
}
