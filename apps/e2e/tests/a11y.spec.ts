import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'
import { dismissConsent } from './support/api'

const pages = [
  '/en',
  '/th',
  '/zh-cn',
  '/en/about',
  '/en/contact',
  '/en/insights',
  '/en/insights/dashboard-nobody-trusts',
  '/en/careers',
  '/en/careers/senior-automation-engineer',
  '/en/search?q=automation',
  '/en/credits',
  '/en/accessibility',
]

const WCAG_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']

for (const path of pages) {
  test(`no serious accessibility violations on ${path}`, async ({ page }) => {
    await page.goto(path)
    await dismissConsent(page)
    await page.waitForTimeout(600)

    const results = await new AxeBuilder({ page }).withTags(WCAG_TAGS).analyze()
    const serious = results.violations.filter((violation) =>
      violation.impact === 'serious' || violation.impact === 'critical',
    )

    expect(
      serious.map((violation) => ({
        id: violation.id,
        nodes: violation.nodes.map((node) => node.target.join(' ')).slice(0, 4),
      })),
    ).toEqual([])
  })
}

test('mobile menu has no serious accessibility violations', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/th')
  await dismissConsent(page)
  await page.locator('.menu-toggle').click()
  await expect(page.locator('#site-menu')).toBeVisible()
  await page.waitForTimeout(600)

  const results = await new AxeBuilder({ page })
    .include('#site-menu')
    .withTags(WCAG_TAGS)
    .analyze()
  const serious = results.violations.filter((violation) =>
    violation.impact === 'serious' || violation.impact === 'critical',
  )

  expect(
    serious.map((violation) => ({
      id: violation.id,
      targets: violation.nodes.map((node) => node.target.join(' ')).slice(0, 4),
    })),
  ).toEqual([])
})

test('search overlay has no serious accessibility violations', async ({ page }) => {
  await page.goto('/en')
  await dismissConsent(page)
  await page.getByRole('button', { name: 'Search' }).first().click()
  await expect(page.locator('.search-overlay')).toBeVisible()
  await page.waitForTimeout(400)

  const results = await new AxeBuilder({ page })
    .include('.search-overlay')
    .withTags(WCAG_TAGS)
    .analyze()
  const serious = results.violations.filter((violation) =>
    violation.impact === 'serious' || violation.impact === 'critical',
  )

  expect(
    serious.map((violation) => ({
      id: violation.id,
      targets: violation.nodes.map((node) => node.target.join(' ')).slice(0, 3),
    })),
  ).toEqual([])
})

test('keyboard focus is visible on primary controls', async ({ page }) => {
  await page.goto('/en')
  await dismissConsent(page)
  await page.evaluate(() => (document.activeElement as HTMLElement | null)?.blur())

  let onSkipLink = false
  for (let attempt = 0; attempt < 4; attempt += 1) {
    await page.keyboard.press('Tab')
    onSkipLink = await page.evaluate(() => document.activeElement?.classList.contains('skip-link') ?? false)
    if (onSkipLink) break
  }

  expect(onSkipLink).toBe(true)

  const style = await page.locator('a.skip-link').evaluate((element) => {
    const computed = getComputedStyle(element)
    return { outline: computed.outlineStyle, width: computed.outlineWidth }
  })

  expect(style.outline).not.toBe('none')
  expect(Number.parseFloat(style.width || '0')).toBeGreaterThan(0)
})
