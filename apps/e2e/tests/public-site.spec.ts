import { expect, test } from '@playwright/test'
import { apiLogin, apiRequest, dismissConsent } from './support/api'

test.describe('public website', () => {
  test('serves localized pages with hreflang, canonical URLs, and structured data', async ({ page }) => {
    await page.goto('/en')

    await expect(page.locator('html')).toHaveAttribute('lang', 'en')
    await expect(page.locator('h1').first()).toBeVisible()
    await expect(page).toHaveTitle(/Meridian/i)

    const canonical = await page.locator('link[rel="canonical"]').getAttribute('href')
    expect(canonical).toContain('/en')

    const alternates = page.locator('link[rel="alternate"][hreflang]')
    await expect(alternates).toHaveCount(4)

    const hreflangs = await alternates.evaluateAll((nodes) => nodes.map((node) => node.getAttribute('hreflang')))
    expect(hreflangs).toEqual(expect.arrayContaining(['th', 'en', 'zh-CN', 'x-default']))

    const jsonLd = await page.locator('script[type="application/ld+json"]').first().textContent()
    expect(() => JSON.parse(jsonLd || '{}')).not.toThrow()

    await page.goto('/th')
    await expect(page.locator('html')).toHaveAttribute('lang', 'th')

    await page.goto('/zh-cn')
    await expect(page.locator('html')).toHaveAttribute('lang', 'zh-CN')
  })

  test('exposes a sitemap index, per-locale sitemaps, and robots directives', async ({ request }) => {
    const index = await request.get('/sitemap.xml')
    expect(index.status()).toBe(200)
    expect(index.headers()['content-type']).toContain('xml')

    const indexBody = await index.text()
    expect(indexBody).toContain('<sitemapindex')
    expect(indexBody).toContain('/sitemaps/th.xml')
    expect(indexBody).toContain('/sitemaps/en.xml')
    expect(indexBody).toContain('/sitemaps/zh-cn.xml')

    const english = await request.get('/sitemaps/en.xml')
    expect(english.status()).toBe(200)
    const englishBody = await english.text()
    expect(englishBody).toContain('<urlset')
    expect(englishBody).toContain('/en/about')

    const robots = await request.get('/robots.txt')
    expect(robots.status()).toBe(200)
    expect(await robots.text()).toContain('Sitemap:')
  })

  test('search overlay returns grouped results for a query', async ({ page }) => {
    await page.goto('/en')
    await dismissConsent(page)
    await page.getByRole('button', { name: 'Search' }).click()

    const dialog = page.getByRole('dialog')
    await expect(dialog).toBeVisible()

    await dialog.locator('input[type="search"]').fill('automation')

    await expect
      .poll(async () => dialog.locator('a[href]').count(), { timeout: 10_000 })
      .toBeGreaterThan(0)

    await expect(dialog.getByText(/automation/i).first()).toBeVisible()
  })

  test('a public enquiry creates a real lead visible in the CMS', async ({ page, request }) => {
    const email = `e2e.form.${Date.now()}@example.com`

    await page.goto('/en/contact')
    await dismissConsent(page)

    // The enquiry form is below the fold and revealed by a scroll trigger.
    const nameField = page.locator('#field-name')
    await nameField.scrollIntoViewIfNeeded()
    await expect(nameField).toBeVisible()

    await nameField.fill('Playwright Verification')
    await page.locator('#field-email').fill(email)
    await page.locator('#field-phone').fill('+66 2 000 0000')
    await page.locator('#field-company').fill('Automated Test Company')
    await page.locator('#field-department').selectOption('project')
    await page.locator('#field-message').fill('This enquiry was created by the automated end-to-end suite.')
    await page.locator('#field-consent').check()

    await page.locator('.form-actions').getByText('Send enquiry').click()

    const success = page.getByRole('status')
    await expect(success).toContainText(/message received/i)
    await expect(success).toContainText(/REFERENCE/i)

    await apiLogin(request, 'admin@meridian.example')
    const leads = await apiRequest(request, 'GET', `/admin/leads?q=${encodeURIComponent(email)}`)
    expect(leads.ok()).toBeTruthy()

    const payload = await leads.json()
    const lead = payload.data.find((item: any) => item.email === email)
    expect(lead).toBeTruthy()
    expect(lead.source).toBe('contact')
    expect(lead.status).toBe('new')

    await apiRequest(request, 'DELETE', `/admin/leads/${lead.id}`)
  })
})
