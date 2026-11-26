import { computed, reactive } from 'vue'
import { api, ensureCsrf } from '@/api/client'
import type { StudioUser } from '@/types'

const state = reactive<{
  user: StudioUser | null
  initialized: boolean
  loading: boolean
  error: string | null
}>({
  user: null,
  initialized: false,
  loading: false,
  error: null,
})

function applySite(user: StudioUser) {
  const stored = window.localStorage.getItem('meridian-site-id')
  const valid = user.sites.some((site) => String(site.id) === stored)
  const preferred = user.sites.find((site) => site.slug === 'meridian') || user.sites[0]
  const siteId = valid ? stored : preferred?.id ? String(preferred.id) : null
  if (siteId) window.localStorage.setItem('meridian-site-id', siteId)
  user.current_site_id = siteId ? Number(siteId) : null
}

async function bootstrap() {
  if (state.initialized || state.loading) return
  state.loading = true
  try {
    const { data } = await api.get('/auth/me')
    state.user = data.data
    applySite(state.user!)
  } catch {
    state.user = null
  } finally {
    state.loading = false
    state.initialized = true
  }
}

async function login(email: string, password: string, remember = true) {
  state.loading = true
  state.error = null
  try {
    await ensureCsrf(true)
    const { data } = await api.post('/auth/login', { email, password, remember })
    state.user = data.data
    applySite(state.user!)
    state.initialized = true
    return state.user
  } catch (error: any) {
    state.error = error?.response?.data?.message || 'Sign in failed.'
    throw error
  } finally {
    state.loading = false
  }
}

async function logout() {
  try {
    await api.post('/auth/logout')
  } finally {
    state.user = null
    state.initialized = false
    window.localStorage.removeItem('meridian-site-id')
  }
}

async function refresh() {
  const { data } = await api.get('/auth/me')
  state.user = data.data
  applySite(state.user!)
}

function can(permission: string): boolean {
  return Boolean(state.user?.permissions?.includes(permission) || state.user?.is_super_admin)
}

function setSite(siteId: number) {
  window.localStorage.setItem('meridian-site-id', String(siteId))
  if (state.user) state.user.current_site_id = siteId
  window.dispatchEvent(new CustomEvent('meridian:site-changed', { detail: siteId }))
}

export const auth = {
  state,
  user: computed(() => state.user),
  initialized: computed(() => state.initialized),
  bootstrap,
  login,
  logout,
  refresh,
  can,
  setSite,
}
