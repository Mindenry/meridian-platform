import { expect, type APIRequestContext, type Page } from '@playwright/test'

export const API_BASE = process.env.E2E_API_URL || 'http://localhost:8000'
export const STUDIO_BASE = process.env.E2E_STUDIO_URL || 'http://localhost:3003'
export const WEBSITE_BASE = process.env.E2E_BASE_URL || 'http://localhost:3002'
export const SITE_SLUG = 'meridian'
export const DEMO_PASSWORD = 'Meridian!2026'

/** Headers that make Sanctum treat the request as a first-party SPA request. */
const statefulHeaders = {
  Accept: 'application/json',
  Origin: STUDIO_BASE,
  Referer: `${STUDIO_BASE}/`,
}

async function csrfHeader(request: APIRequestContext): Promise<Record<string, string>> {
  const state = await request.storageState()
  const cookie = state.cookies.find((item) => item.name === 'XSRF-TOKEN')

  return cookie ? { 'X-XSRF-TOKEN': decodeURIComponent(cookie.value) } : {}
}

export async function apiLogin(
  request: APIRequestContext,
  email: string,
  password: string = DEMO_PASSWORD,
): Promise<void> {
  await request.get(`${API_BASE}/sanctum/csrf-cookie`, { headers: statefulHeaders })
  const headers = { ...statefulHeaders, ...(await csrfHeader(request)) }

  const response = await request.post(`${API_BASE}/api/v1/auth/login`, {
    headers,
    data: { email, password },
  })

  if (!response.ok()) {
    throw new Error(`API login failed for ${email}: ${response.status()} ${await response.text()}`)
  }

  // Refresh the token after the session id has been rotated by login.
  await request.get(`${API_BASE}/sanctum/csrf-cookie`, { headers: statefulHeaders })
}

export async function apiRequest(
  request: APIRequestContext,
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
  path: string,
  data?: unknown,
  site: string = SITE_SLUG,
) {
  const headers: Record<string, string> = { ...statefulHeaders, 'X-Site': site }

  if (method !== 'GET') {
    Object.assign(headers, await csrfHeader(request))
  }

  return request.fetch(`${API_BASE}/api/v1${path}`, { method, headers, data })
}

export async function adminJson<T = any>(
  request: APIRequestContext,
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
  path: string,
  data?: unknown,
  site: string = SITE_SLUG,
): Promise<T> {
  const response = await apiRequest(request, method, path, data, site)

  if (!response.ok()) {
    throw new Error(`${method} ${path} failed: ${response.status()} ${await response.text()}`)
  }

  return response.json() as Promise<T>
}

export interface WorkflowPage {
  id: number
  slug: string
  title: string
}

export async function createDraftPage(request: APIRequestContext, label: string): Promise<WorkflowPage> {
  const unique = `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 7)}`
  const slug = `e2e-${label}-${unique}`
  const title = `E2E ${label} ${unique}`

  const payload = await adminJson(request, 'POST', '/admin/pages', {
    title,
    slug,
    locale: 'en',
    template: 'default',
    excerpt: 'Automated end-to-end verification fixture.',
    blocks: [
      {
        type: 'rich_text',
        props: {
          eyebrow: 'End-to-end verification',
          heading: title,
          body: `<p>This page was created by the Playwright suite to verify the ${label} workflow.</p>`,
          width: 'standard',
        },
      },
    ],
  })

  return { id: Number(payload.data.id), slug: payload.data.slug || slug, title }
}

export async function deletePage(request: APIRequestContext, id: number): Promise<void> {
  await apiRequest(request, 'DELETE', `/admin/pages/${id}`).catch(() => undefined)
}

/**
 * Waits for the consent banner, which only renders after Nuxt hydration, then
 * dismisses it. Interacting before this point races Vue's hydration pass and
 * can silently clear form values.
 */
export async function dismissConsent(page: Page): Promise<void> {
  const banner = page.getByRole('complementary', { name: 'Analytics consent' })
  await expect(banner).toBeVisible({ timeout: 20_000 })
  await banner.getByRole('button', { name: /decline|拒绝|ปฏิเสธ/i }).click()
  await expect(banner).toBeHidden({ timeout: 10_000 })
}

export async function studioLogin(page: Page, email: string, password: string = DEMO_PASSWORD): Promise<void> {
  await page.goto(`${STUDIO_BASE}/login`)
  await page.locator('#login-email').waitFor({ state: 'visible' })

  const overlayCleared = await page
    .waitForFunction(() => !document.querySelector('vite-error-overlay'), null, { timeout: 20_000 })
    .then(() => true)
    .catch(() => false)

  if (!overlayCleared) {
    const message = await page
      .locator('vite-error-overlay')
      .evaluate((element) => (element as HTMLElement).shadowRoot?.textContent || element.textContent || '')
    throw new Error(`The Studio dev server reported a build error:\n${message.slice(0, 1200)}`)
  }

  await page.locator('#login-email').fill(email)
  await page.locator('#login-password').fill(password)
  await page.getByRole('button', { name: /sign in/i }).click()
  await expect(page).toHaveURL(
    (url) => url.origin === STUDIO_BASE && url.pathname === '/',
    { timeout: 30_000 },
  )
}

export async function expectPublicPage(slug: string, title: string): Promise<void> {
  const response = await fetch(`${WEBSITE_BASE}/en/${slug}`, { redirect: 'manual' })
  expect(response.status).toBe(200)
  expect(await response.text()).toContain(title)
}
