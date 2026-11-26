import { expect, test } from '@playwright/test'
import { API_BASE, dismissConsent } from './support/api'

test.describe('UX regressions', () => {
  test('desktop header shows a visible primary menu, not an index button', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/en')
    await dismissConsent(page)

    const nav = page.locator('nav.primary-nav')
    await expect(nav).toBeVisible()
    await expect(nav.locator('.primary-nav__link').first()).toBeVisible()

    const links = await nav.locator('.primary-nav__link').allTextContents()
    expect(links.length).toBeGreaterThanOrEqual(4)

    for (const text of links) {
      expect(text.trim().toLowerCase()).not.toBe('index')
      expect(text.trim()).not.toContain('ดัชนี')
    }

    await expect(page.locator('.menu-toggle')).toBeHidden()
  })

  test('the mobile menu fills the viewport and exposes navigation and language', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto('/th')
    await dismissConsent(page)

    const toggle = page.locator('.menu-toggle')
    await expect(toggle).toBeVisible()
    await expect(toggle).toContainText('เมนู')

    await toggle.click()

    const overlay = page.locator('#site-menu')
    await expect(overlay).toBeVisible()

    const box = await overlay.boundingBox()
    expect(box?.height || 0).toBeGreaterThan(700)

    await expect(overlay.locator('.index-link').first()).toBeVisible()
    await expect(overlay.locator('.index-foot-lang')).toHaveCount(3)

    await overlay.locator('.index-close').click()
    await expect(overlay).toBeHidden()
  })

  test('header navigation follows the selected language in both directions', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/th/about')
    await dismissConsent(page)

    const nav = page.locator('nav.primary-nav')
    await expect(nav).toContainText('โซลูชัน')

    await page.locator('.locale-switcher__item', { hasText: '中文' }).first().click()
    await expect(page).toHaveURL(/\/zh-cn\//)
    await expect(nav).toContainText('解决方案')
    await expect(nav).not.toContainText('โซลูชัน')

    await page.locator('.locale-switcher__item', { hasText: 'TH' }).first().click()
    await expect(page).toHaveURL(/\/th\//)
    await expect(nav).toContainText('โซลูชัน')
    await expect(nav).not.toContainText('解决方案')
  })

  test('mobile menu follows the selected language after switching back', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto('/th/about')
    await dismissConsent(page)

    await page.locator('.locale-switcher__item', { hasText: '中文' }).first().click()
    await expect(page).toHaveURL(/\/zh-cn\//)

    await page.locator('.menu-toggle').click()
    await expect(page.locator('#site-menu .index-link').first()).toContainText('解决方案')

    await page.locator('#site-menu .index-foot-lang', { hasText: 'ไทย' }).click()
    await expect(page).toHaveURL(/\/th\//)

    await page.locator('.menu-toggle').click()
    await expect(page.locator('#site-menu .index-link').first()).toContainText('โซลูชัน')
  })

  test('language switching stays on the current page after client-side navigation', async ({ page }) => {
    await page.goto('/en/about')
    await dismissConsent(page)

    await page.locator('a[href="/en/credits"]').first().click()
    await expect(page).toHaveURL(/\/en\/credits$/)

    await page.locator('.locale-switcher__item', { hasText: 'TH' }).first().click()

    await expect(page).toHaveURL(/\/th\/credits$/)
    await expect(page.locator('h1').first()).toContainText('เครดิต')
  })

  test('language switching is available on mobile and keeps the page', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto('/en/about')
    await dismissConsent(page)

    await page.locator('.locale-switcher__item', { hasText: 'TH' }).first().click()

    await expect(page).toHaveURL(/\/th\//)
    await expect(page.locator('h1').first()).toContainText(/พันธมิตร|เกี่ยวกับ/)
  })

  test('a missing translation keeps a clean target-locale fallback URL', async ({ page }) => {
    await page.goto('/th/นโยบายความเป็นส่วนตัว')
    await dismissConsent(page)

    await page.locator('.locale-switcher__item', { hasText: '中文' }).first().click()

    await expect(page).toHaveURL(/\/zh-cn\/privacy-policy$/)
    await expect(page.locator('h1')).toBeVisible()
  })

  test('Thai headings use relaxed tracking and leading', async ({ page }) => {
    await page.goto('/th')
    await dismissConsent(page)

    const metrics = await page.locator('h1').first().evaluate((element) => {
      const style = getComputedStyle(element)

      return {
        fontSize: Number.parseFloat(style.fontSize),
        lineHeight: Number.parseFloat(style.lineHeight),
        letterSpacing: style.letterSpacing,
        fontFamily: style.fontFamily,
      }
    })

    expect(metrics.fontFamily).toContain('Noto Serif Thai')
    expect(metrics.letterSpacing === 'normal' || Number.parseFloat(metrics.letterSpacing) >= 0).toBe(true)
    expect(metrics.lineHeight).toBeGreaterThan(metrics.fontSize * 1.15)
  })

  test('the desktop hero actions stay inside the first screen', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 })
    await page.goto('/en')
    await dismissConsent(page)

    const actions = await page.locator('.hero__actions').boundingBox()
    expect(actions).not.toBeNull()
    expect((actions?.y || 0) + (actions?.height || 0)).toBeLessThanOrEqual(900)
  })

  test('public job detail is available with structured data', async ({ request }) => {
    const response = await request.get(`${API_BASE}/api/v1/public/careers/senior-automation-engineer?locale=en`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian' },
    })

    expect(response.status()).toBe(200)

    const payload = await response.json()
    expect(payload.data.type).toBe('job')
    expect(payload.data.slug).toBe('senior-automation-engineer')
    expect(payload.data.seo.schema).toBeTruthy()
  })
})
