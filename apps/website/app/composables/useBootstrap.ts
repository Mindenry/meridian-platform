import type { SiteBootstrap } from '~/types'

export async function useBootstrap() {
  const { localeCode } = useLocale()
  const api = useApi()
  const state = useState<SiteBootstrap | null>('site-bootstrap', () => null)

  // Key by locale so returning to a language reuses its cached navigation
  // instead of showing the previous language until the request completes.
  const { data, error, refresh } = await useAsyncData(
    () => `bootstrap-${localeCode.value}`,
    () => api.get<{ data: SiteBootstrap }>('/public/bootstrap', { query: { locale: localeCode.value } }),
    { watch: [localeCode] },
  )

  // The layout instance persists across locale navigation, so the composable
  // body does not re-run. Without this watcher the header kept the first
  // language's navigation forever.
  watch(
    data,
    (payload) => {
      if (payload?.data) state.value = payload.data
    },
    { immediate: true },
  )

  return {
    bootstrap: computed(() => state.value),
    error,
    refresh,
  }
}
