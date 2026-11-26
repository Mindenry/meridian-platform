import { chromium } from '@playwright/test'
import fs from 'node:fs'

const outDir = 'screenshots'
fs.mkdirSync(outDir, { recursive: true })

const CONSENT_DENIED = () => {
  window.localStorage.setItem('meridian-analytics-consent', 'denied')
}

const browser = await chromium.launch({ channel: 'chrome' })
const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 })
await context.addInitScript(CONSENT_DENIED)
const page = await context.newPage()

const shots = [
  ['http://localhost:3002/th', 'website-home-th'],
  ['http://localhost:3002/en', 'website-home-en'],
  ['http://localhost:3002/zh-cn', 'website-home-zh'],
  ['http://localhost:3002/en/solutions', 'website-solutions'],
  ['http://localhost:3002/en/solutions/industrial-automation', 'website-solution-detail'],
  ['http://localhost:3002/en/industries', 'website-industries'],
  ['http://localhost:3002/en/case-studies', 'website-cases'],
  ['http://localhost:3002/en/case-studies/rayong-precision-line', 'website-case-detail'],
  ['http://localhost:3002/en/insights', 'website-insights'],
  ['http://localhost:3002/en/insights/dashboard-nobody-trusts', 'website-article'],
  ['http://localhost:3002/en/about', 'website-about'],
  ['http://localhost:3002/en/careers', 'website-careers'],
  ['http://localhost:3002/en/accessibility', 'website-accessibility'],
  ['http://localhost:3002/en/careers/senior-automation-engineer', 'website-job-detail'],
  ['http://localhost:3002/en/investor-relations', 'website-investor'],
  ['http://localhost:3002/en/contact', 'website-contact'],
]

for (const [url, name] of shots) {
  await page.goto(url, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2400)
  await page.screenshot({ path: `${outDir}/${name}.png`, fullPage: false })
  console.log('shot', name, await page.title())
}

// Desktop primary navigation with its dropdown panel.
await page.goto('http://localhost:3002/en', { waitUntil: 'networkidle' })
await page.waitForTimeout(1200)
await page.locator('.primary-nav__button', { hasText: /Solutions/i }).first().hover()
await page.waitForTimeout(700)
await page.screenshot({ path: `${outDir}/website-menu-desktop.png`, fullPage: false })
await page.mouse.move(10, 500)

// Mobile home.
const mobile = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 })
await mobile.addInitScript(CONSENT_DENIED)
const mobilePage = await mobile.newPage()
await mobilePage.goto('http://localhost:3002/th', { waitUntil: 'networkidle' })
await mobilePage.waitForTimeout(2400)
await mobilePage.screenshot({ path: `${outDir}/website-home-mobile.png`, fullPage: false })
await mobilePage.locator('.menu-toggle').click()
await mobilePage.waitForTimeout(700)
await mobilePage.screenshot({ path: `${outDir}/website-menu-mobile.png`, fullPage: false })
await mobilePage.locator('.index-close').click()
console.log('shot mobile')

// Studio: login and main screens.
if (process.env.WEBSITE_ONLY !== '1') {
const studio = await context.newPage()
await studio.goto('http://localhost:3003/login', { waitUntil: 'networkidle' })
await studio.screenshot({ path: `${outDir}/studio-login.png`, fullPage: false })
await studio.waitForSelector('#login-email')
await studio.waitForTimeout(600)
await studio.fill('#login-email', 'admin@meridian.example')
await studio.fill('#login-password', 'Meridian!2026')
await studio.click('button[type="submit"]')

try {
  await studio.waitForFunction(() => window.location.pathname === '/', null, { timeout: 30_000 })
} catch (error) {
  console.error('Studio login did not navigate. Current URL:', studio.url())
  console.error((await studio.locator('body').innerText()).slice(0, 500))
  throw error
}

await studio.waitForTimeout(1200)
await studio.screenshot({ path: `${outDir}/studio-dashboard.png`, fullPage: false })

const studioShots = [
  ['/pages', 'studio-pages'],
  ['/content/articles', 'studio-articles'],
  ['/content/case-studies', 'studio-case-studies'],
  ['/media', 'studio-media'],
  ['/forms', 'studio-forms'],
  ['/leads', 'studio-leads'],
  ['/seo', 'studio-seo'],
  ['/analytics', 'studio-analytics'],
  ['/workflows', 'studio-workflows'],
  ['/team', 'studio-team'],
  ['/sites', 'studio-sites'],
  ['/settings', 'studio-settings'],
]

for (const [path, name] of studioShots) {
  await studio.goto(`http://localhost:3003${path}`, { waitUntil: 'networkidle' })
  await studio.waitForTimeout(600)
  await studio.screenshot({ path: `${outDir}/${name}.png`, fullPage: false })
  console.log('shot', name)
}

// Editor + builder for a representative page.
await studio.goto('http://localhost:3003/pages', { waitUntil: 'networkidle' })
const aboutRow = studio.locator('table tbody tr', { hasText: 'About Meridian' }).first()
const firstPage = (await aboutRow.count())
  ? await aboutRow.locator('a').first().getAttribute('href')
  : await studio.locator('table tbody tr a').first().getAttribute('href')
if (firstPage) {
  await studio.goto(`http://localhost:3003${firstPage}`, { waitUntil: 'networkidle' })
  await studio.waitForTimeout(600)
  await studio.screenshot({ path: `${outDir}/studio-page-editor.png`, fullPage: false })
  await studio.goto(`http://localhost:3003${firstPage}/builder?locale=en`, { waitUntil: 'networkidle' })
  await studio.waitForTimeout(1000)
  await studio.screenshot({ path: `${outDir}/studio-builder.png`, fullPage: false })
}
}

await browser.close()
