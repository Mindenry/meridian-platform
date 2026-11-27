import type { H3Event } from 'h3'

function key(path: string, query: Record<string, any> = {}): string {
  const params = new URLSearchParams()

  for (const name of Object.keys(query).sort()) {
    const value = query[name]
    if (value === undefined || value === null || value === '') continue
    params.set(name, String(value))
  }

  const serialized = params.toString()

  return path + (serialized ? `?${serialized}` : '')
}

function normalize(value: any): any {
  if (typeof value === 'string') {
    const siteUrl = String(useRuntimeConfig().public.siteUrl || '').replace(/\/$/, '')

    return value
      .replaceAll('http://localhost:8000/storage', '/demo-media')
      .replaceAll('http://127.0.0.1:8000/storage', '/demo-media')
      .replaceAll('http://localhost:3002', siteUrl)
      .replaceAll('http://127.0.0.1:3002', siteUrl)
  }

  if (Array.isArray(value)) return value.map((item) => normalize(item))

  if (value && typeof value === 'object') {
    return Object.fromEntries(Object.entries(value).map(([name, item]) => [name, normalize(item)]))
  }

  return value
}

const cache = new Map<string, any>()

export async function readDemoFixture<T = any>(path: string, query: Record<string, any> = {}): Promise<T> {
  const fs = await import('node:fs/promises')
  const nodePath = await import('node:path')
  const root = nodePath.join(process.cwd(), 'public', 'demo', 'api')
  const manifest = JSON.parse(await fs.readFile(nodePath.join(root, 'manifest.json'), 'utf8')) as Record<string, string>

  const candidates = [key(path, query), key(path, { locale: query.locale })]

  for (const candidate of candidates) {
    const file = manifest[candidate]
    if (!file) continue

    if (!cache.has(candidate)) {
      cache.set(candidate, JSON.parse(await fs.readFile(nodePath.join(root, file), 'utf8')))
    }

    return normalize(cache.get(candidate)) as T
  }

  throw new Error(`Missing demo fixture for ${candidates[0]}`)
}
