export function useApi() {
  const config = useRuntimeConfig()

  const request = <T>(path: string, options: Record<string, any> = {}) => {
    const base = import.meta.server
      ? (process.env.NUXT_API_INTERNAL_BASE || String(config.apiInternal))
      : config.public.apiBase

    return $fetch<T>(`${base}${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Site': config.public.site,
        ...(options.headers || {}),
      },
    })
  }

  return {
    get: <T>(path: string, options: Record<string, any> = {}) => request<T>(path, { ...options, method: 'GET' }),
    post: <T>(path: string, body?: any, options: Record<string, any> = {}) => request<T>(path, { ...options, method: 'POST', body }),
  }
}
