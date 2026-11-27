export async function publicApiGet<T = any>(path: string, query: Record<string, any> = {}): Promise<T> {
  const config = useRuntimeConfig()

  if (config.public.demoStatic) {
    return readDemoFixture<T>(path, query)
  }

  return $fetch<T>(`${internalApiBase()}${path}`, {
    headers: { Accept: 'application/json', 'X-Site': config.public.site },
    query,
  })
}
