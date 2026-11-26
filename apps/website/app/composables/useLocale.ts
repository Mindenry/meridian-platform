import type { Alternate, SiteBootstrap } from '~/types'

const ROUTE_LOCALES = ['th', 'en', 'zh-cn'] as const

export function useLocale() {
  const route = useRoute()
  const config = useRuntimeConfig()

  const routeLocale = computed(() => {
    const param = route.params.locale
    const value = Array.isArray(param) ? param[0] : param
    const candidate = String(value || 'th').toLowerCase()
    return ROUTE_LOCALES.includes(candidate as any) ? candidate : 'th'
  })

  const localeCode = computed(() => {
    if (routeLocale.value === 'zh-cn') return 'zh-CN'
    return routeLocale.value
  })

  const alternates = useState<Alternate[]>('page-alternates', () => [])
  const bootstrap = useState<SiteBootstrap | null>('site-bootstrap', () => null)

  const localeLinks = computed(() => {
    const locales = bootstrap.value?.locales ?? []
    const fallbackAlternate = alternates.value.find((item) => item.locale === 'en')
      ?? alternates.value.find((item) => item.locale === 'th')
      ?? alternates.value[0]

    return locales.map((locale) => {
      const alternate = alternates.value.find((item) => item.locale === locale.code)
      let to = `/${locale.route}`

      if (alternate) {
        try {
          to = new URL(alternate.url, config.public.siteUrl).pathname
        } catch {
          to = `/${locale.route}`
        }
      } else {
        // No translation for this locale. Use the English/Thai path as the
        // canonical source rather than carrying the current localized slug
        // into another language (which can produce a dead URL).
        const source = fallbackAlternate
          ? new URL(fallbackAlternate.url, config.public.siteUrl).pathname
          : route.path
        const parts = source.split('?')[0].split('/')

        if (parts.length > 1) {
          parts[1] = locale.route
        } else {
          parts.push(locale.route)
        }

        to = parts.join('/') || `/${locale.route}`

        if (!fallbackAlternate && route.query && Object.keys(route.query).length) {
          const query = new URLSearchParams()
          for (const [key, value] of Object.entries(route.query)) {
            if (typeof value === 'string') query.set(key, value)
          }
          const serialized = query.toString()
          if (serialized) to += `?${serialized}`
        }
      }

      return { ...locale, to }
    })
  })

  const localePath = (path: string) => {
    const clean = path.replace(/^\/+/, '')
    return `/${routeLocale.value}${clean ? `/${clean}` : ''}`
  }

  const switchLocale = (code: string) => {
    const link = localeLinks.value.find((item) => item.code === code)
    if (link) {
      return navigateTo(link.to)
    }
    return navigateTo(`/${routeLocale.value}`)
  }

  return {
    routeLocale,
    localeCode,
    alternates,
    localeLinks,
    localePath,
    switchLocale,
    isThai: computed(() => localeCode.value === 'th'),
    isEnglish: computed(() => localeCode.value === 'en'),
  }
}
