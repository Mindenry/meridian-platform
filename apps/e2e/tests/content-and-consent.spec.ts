import { expect, test } from '@playwright/test'
import { API_BASE, dismissConsent } from './support/api'

test.describe('content, consent, and feeds', () => {
  test('cookie preferences are granular, persisted, and reopenable', async ({ page }) => {
    await page.goto('/en')
    await dismissConsent(page).catch(async () => {
      // The banner is already handled when a previous test stored consent.
    })

    await page.evaluate(() => localStorage.removeItem('meridian-analytics-consent'))
    await page.evaluate(() => localStorage.removeItem('meridian-cookie-preferences'))
    await page.reload()

    const banner = page.getByRole('complementary', { name: 'Analytics consent' })
    await expect(banner).toBeVisible()
    await banner.getByRole('button', { name: 'Manage preferences' }).click()

    const dialog = page.getByRole('dialog', { name: 'Cookie settings' })
    await expect(dialog).toBeVisible()
    await expect(dialog).toContainText('Necessary')
    await expect(dialog.locator('.prefs-locked')).toBeVisible()

    await dialog.getByRole('checkbox', { name: 'Analytics' }).check()
    await dialog.getByRole('button', { name: 'Save preferences' }).click()
    await expect(dialog).toBeHidden()
    await expect(banner).toBeHidden()

    const stored = await page.evaluate(() => JSON.parse(localStorage.getItem('meridian-cookie-preferences') || 'null'))
    expect(stored.analytics).toBe(true)

    await page.getByRole('button', { name: 'Cookie settings' }).click()
    await expect(page.getByRole('dialog', { name: 'Cookie settings' })).toBeVisible()
    await page.getByRole('dialog', { name: 'Cookie settings' }).getByRole('checkbox', { name: 'Analytics' }).uncheck()
    await page.getByRole('dialog', { name: 'Cookie settings' }).getByRole('button', { name: 'Save preferences' }).click()

    const updated = await page.evaluate(() => JSON.parse(localStorage.getItem('meridian-cookie-preferences') || 'null'))
    expect(updated.analytics).toBe(false)
  })

  test('search remembers recent queries and shows them when reopened', async ({ page }) => {
    await page.goto('/en')
    await dismissConsent(page)
    await page.evaluate(() => localStorage.removeItem('meridian-search-recent'))

    await page.getByRole('button', { name: 'Search' }).first().click()
    const dialog = page.locator('.search-overlay')
    await expect(dialog).toBeVisible()

    await dialog.locator('.search-input').fill('automation')
    await expect(dialog.locator('.search-result').first()).toBeVisible({ timeout: 10_000 })
    await dialog.locator('.search-input').press('Enter')
    await expect(page).toHaveURL(/\/en\/search\?q=automation/)
    await expect(dialog).toBeHidden()

    await page.locator('.icon-button').first().click()
    await expect(dialog).toBeVisible()
    const recent = page.locator('.search-recent .search-chip').first()
    await expect(recent).toHaveText(/automation/i)
  })

  test('accessibility statement exists in every locale and is linked from the footer', async ({ page }) => {
    for (const path of ['/en/accessibility', '/th/accessibility', '/zh-cn/accessibility']) {
      const response = await page.goto(path)
      expect(response?.status()).toBe(200)
      await expect(page.locator('h1')).toBeVisible()
    }

    await page.goto('/en')
    await dismissConsent(page)
    await expect(page.getByRole('link', { name: 'Accessibility' }).last()).toBeVisible()
  })

  test('the insights feed is valid RSS with items and an alternate link', async ({ page, request }) => {
    for (const path of ['/en/rss.xml', '/th/rss.xml', '/zh-cn/rss.xml', '/rss.xml']) {
      const response = await request.get(path)
      expect(response.status()).toBe(200)
      expect(response.headers()['content-type']).toContain('application/rss+xml')
      const body = await response.text()
      expect(body).toContain('<rss version="2.0"')
      expect(body).toContain('<item>')
    }

    await page.goto('/en')
    await expect(page.locator('link[rel="alternate"][type="application/rss+xml"]')).toHaveAttribute('href', '/en/rss.xml')
  })

  test('the second website has its own navigation and pages', async ({ request }) => {
    const bootstrap = await request.get(`${API_BASE}/api/v1/public/bootstrap?locale=en`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian-energy' },
    })
    expect(bootstrap.status()).toBe(200)
    const payload = await bootstrap.json()
    expect(payload.data.site.slug).toBe('meridian-energy')
    expect(payload.data.navigation.main.map((item: any) => item.label)).toEqual(['Home', 'Platform', 'Contact'])

    const platform = await request.get(`${API_BASE}/api/v1/public/pages/platform?locale=en`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian-energy' },
    })
    expect(platform.status()).toBe(200)

    const crossSite = await request.get(`${API_BASE}/api/v1/public/pages/platform?locale=en`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian' },
    })
    expect(crossSite.status()).toBe(404)
  })
})
