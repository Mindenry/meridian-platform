import { chromium } from '@playwright/test'

const base = 'https://meridian-industrial.netlify.app'
const browser = await chromium.launch({ channel: 'chrome' })

for (const [name, width, height] of [['1440', 1440, 900], ['1000', 1000, 900], ['390', 390, 844]]) {
  const page = await browser.newPage({ viewport: { width, height } })
  await page.goto(`${base}/th`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2300)
  const deny = page.getByRole('button', { name: /ไม่ตอนนี้|Not now/i })
  if (await deny.count()) await deny.click().catch(() => {})
  await page.waitForTimeout(300)
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight))
  await page.waitForTimeout(700)
  const info = await page.evaluate(() => {
    const footer = document.querySelector('.site-footer').getBoundingClientRect()
    const email = document.querySelector('.footer-email').getBoundingClientRect()
    const wm = document.querySelector('.footer-wordmark span').getBoundingClientRect()
    return {
      footerTop: Math.round(footer.top),
      footerHeight: Math.round(footer.height),
      emailTop: Math.round(email.top),
      emailSize: getComputedStyle(document.querySelector('.footer-email')).fontSize,
      wordmark: [Math.round(wm.width), Math.round(wm.height)],
      bg: getComputedStyle(document.querySelector('.site-footer')).backgroundColor,
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    }
  })
  console.log(name, info)
  if (name !== '390') await page.screenshot({ path: `live-footer-${name}.png` })
  await page.close()
}

await browser.close()
