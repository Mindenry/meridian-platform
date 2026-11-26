type ConsentState = 'granted' | 'denied' | null

export interface CookiePreferences {
  analytics: boolean
  updatedAt: string | null
}

const CONSENT_KEY = 'meridian-analytics-consent'
const PREFERENCES_KEY = 'meridian-cookie-preferences'

export function useConsent() {
  const consent = useState<ConsentState>('analytics-consent', () => null)
  const preferences = useState<CookiePreferences>('cookie-preferences', () => ({
    analytics: false,
    updatedAt: null,
  }))
  const hydrated = useState('analytics-consent-hydrated', () => false)
  const preferencesOpen = useState('cookie-preferences-open', () => false)
  const config = useRuntimeConfig()

  onMounted(() => {
    if (hydrated.value) return

    const stored = window.localStorage.getItem(CONSENT_KEY)
    if (stored === 'granted' || stored === 'denied') {
      consent.value = stored
    }

    try {
      const storedPreferences = JSON.parse(window.localStorage.getItem(PREFERENCES_KEY) || 'null')
      if (storedPreferences && typeof storedPreferences.analytics === 'boolean') {
        preferences.value = {
          analytics: storedPreferences.analytics,
          updatedAt: typeof storedPreferences.updatedAt === 'string' ? storedPreferences.updatedAt : null,
        }
        consent.value = storedPreferences.analytics ? 'granted' : 'denied'
      }
    } catch {
      // A malformed preference payload falls back to the simple consent flag.
    }

    hydrated.value = true
  })

  const persist = (analytics: boolean) => {
    preferences.value = { analytics, updatedAt: new Date().toISOString() }
    consent.value = analytics ? 'granted' : 'denied'

    if (!import.meta.client) return

    window.localStorage.setItem(CONSENT_KEY, analytics ? 'granted' : 'denied')
    window.localStorage.setItem(PREFERENCES_KEY, JSON.stringify(preferences.value))
  }

  const accept = () => persist(true)
  const deny = () => persist(false)

  const savePreferences = (next: { analytics: boolean }) => persist(next.analytics)

  const openPreferences = () => {
    preferencesOpen.value = true
  }

  const closePreferences = () => {
    preferencesOpen.value = false
  }

  return {
    consent,
    preferences,
    hydrated,
    preferencesOpen,
    accept,
    deny,
    savePreferences,
    openPreferences,
    closePreferences,
    config,
  }
}

export function useAnalytics() {
  const { consent } = useConsent()
  const { localeCode } = useLocale()
  const config = useRuntimeConfig()
  const route = useRoute()

  const sessionId = () => {
    if (!import.meta.client) return null
    let id = window.sessionStorage.getItem('meridian-session')
    if (!id) {
      id = crypto.randomUUID()
      window.sessionStorage.setItem('meridian-session', id)
    }
    return id
  }

  const track = (event: string, meta?: Record<string, any>) => {
    if (!import.meta.client || consent.value !== 'granted') return
    if (route.path.includes('/preview/')) return

    $fetch(`${config.public.apiBase}/public/analytics/events`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Site': config.public.site,
      },
      body: {
        event,
        path: route.fullPath,
        locale: localeCode.value,
        referrer: document.referrer || null,
        session_id: sessionId(),
        consent: true,
        meta: meta || null,
      },
    }).catch(() => {
      // Analytics must never break the page.
    })
  }

  const trackPageView = () => {
    track('page_view')
  }

  return { track, trackPageView, consent }
}
