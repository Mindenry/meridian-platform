import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios'

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api/v1'
const ROOT = API_BASE.replace(/\/api\/v1\/?$/, '')

export const api = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
  headers: { Accept: 'application/json' },
})

let csrfReady = false

function readCookie(name: string): string | null {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.*+?^${}()|[\]\\])/g, '\\$1') + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : null
}

export async function ensureCsrf(force = false): Promise<void> {
  if (csrfReady && !force) return
  await axios.get(`${ROOT}/sanctum/csrf-cookie`, { withCredentials: true })
  csrfReady = true
}

api.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
  const method = (config.method || 'get').toLowerCase()
  if (!['get', 'head', 'options'].includes(method)) {
    await ensureCsrf()
  }

  // Studio exposes auth under /auth and every content call under /admin.
  if (config.url && !config.url.startsWith('/auth/') && !config.url.startsWith('/admin/') && !config.url.startsWith('http')) {
    config.url = `/admin${config.url.startsWith('/') ? '' : '/'}${config.url}`
  }

  const siteId = window.localStorage.getItem('meridian-site-id')
  if (siteId) config.headers.set('X-Site-Id', siteId)

  const token = readCookie('XSRF-TOKEN')
  if (token) config.headers.set('X-XSRF-TOKEN', token)

  return config
})

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as (InternalAxiosRequestConfig & { _retried?: boolean }) | undefined
    if (error.response?.status === 419 && config && !config._retried) {
      config._retried = true
      await ensureCsrf(true)
      return api.request(config)
    }

    if (error.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('meridian:auth-expired'))
    }

    return Promise.reject(error)
  },
)

export function apiErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as any
    if (data?.errors) {
      const first = Object.values(data.errors)[0]
      if (Array.isArray(first)) return String(first[0])
    }
    return data?.message || error.message
  }
  return error instanceof Error ? error.message : 'Something went wrong.'
}

export function apiValidationErrors(error: unknown): Record<string, string> {
  const result: Record<string, string> = {}
  if (axios.isAxiosError(error)) {
    const errors = (error.response?.data as any)?.errors as Record<string, string[]> | undefined
    if (errors) {
      for (const [key, messages] of Object.entries(errors)) {
        result[key] = Array.isArray(messages) ? String(messages[0]) : String(messages)
      }
    }
  }
  return result
}

export { API_BASE, ROOT as API_ROOT }
