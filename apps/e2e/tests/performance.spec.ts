import { expect, test } from '@playwright/test'
import { dismissConsent } from './support/api'

test.describe('performance budget', () => {
  test('home page meets local LCP and CLS budgets', async ({ page }) => {
    await page.addInitScript(() => {
      const state = { lcp: 0, cls: 0 }
      ;(window as any).__perf = state

      new PerformanceObserver((list) => {
        const entries = list.getEntries()
        const last = entries[entries.length - 1] as any
        if (last) state.lcp = last.startTime
      }).observe({ type: 'largest-contentful-paint', buffered: true })

      new PerformanceObserver((list) => {
        for (const entry of list.getEntries() as any[]) {
          if (!entry.hadRecentInput) state.cls += entry.value
        }
      }).observe({ type: 'layout-shift', buffered: true })
    })

    await page.goto('/en')
    await dismissConsent(page)
    await page.waitForTimeout(2500)

    const metrics = await page.evaluate(() => (window as any).__perf)
    const jsBytes = await page.evaluate(() =>
      performance
        .getEntriesByType('resource')
        .filter((entry) => (entry as PerformanceResourceTiming).initiatorType === 'script')
        .reduce((sum, entry) => sum + ((entry as PerformanceResourceTiming).transferSize || 0), 0),
    )

    expect(metrics.lcp).toBeGreaterThan(0)
    expect(metrics.lcp).toBeLessThan(3000)
    expect(metrics.cls).toBeLessThan(0.1)
    expect(jsBytes).toBeLessThan(2_500_000)
  })

  test('hero image is present and decoded on the home page', async ({ page }) => {
    await page.goto('/en')
    await dismissConsent(page)

    const hero = page.locator('.hero__media img').first()
    await expect(hero).toBeVisible()
    await expect.poll(async () => hero.evaluate((image: HTMLImageElement) => image.complete && image.naturalWidth > 0)).toBe(true)
  })
})
