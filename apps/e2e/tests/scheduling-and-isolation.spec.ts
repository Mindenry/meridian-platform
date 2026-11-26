import { expect, test } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { existsSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import {
  API_BASE,
  WEBSITE_BASE,
  adminJson,
  apiLogin,
  apiRequest,
  createDraftPage,
  deletePage,
} from './support/api'

const API_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../api')
const REPO_DIR = path.resolve(API_DIR, '../..')

function runScheduler(): void {
  if (process.env.E2E_DOCKER === '1') {
    execFileSync(
      'docker',
      ['compose', '-f', 'infrastructure/compose.yaml', 'exec', '-T', 'api', 'php', 'artisan', 'content:publish-scheduled', '--sync'],
      { cwd: REPO_DIR, encoding: 'utf8' },
    )

    return
  }

  execFileSync(phpBinary(), ['artisan', 'content:publish-scheduled', '--sync'], {
    cwd: API_DIR,
    encoding: 'utf8',
  })
}

function phpBinary(): string {
  if (process.env.PHP_BIN) return process.env.PHP_BIN
  if (process.platform === 'win32' && existsSync('C:/tools/php86/php.exe')) return 'C:/tools/php86/php.exe'
  return 'php'
}

test.describe('scheduled publishing', () => {
  test('publishes a page once its scheduled time arrives and the scheduler runs', async ({ request }) => {
    await apiLogin(request, 'admin@meridian.example')
    const fixture = await createDraftPage(request, 'schedule')

    try {
      await apiRequest(request, 'POST', `/admin/pages/${fixture.id}/submit`, { note: 'Ready for scheduled review.' })
      await apiRequest(request, 'POST', `/admin/pages/${fixture.id}/approve`, { note: 'Approved for scheduling.' })

      const publishAt = new Date(Date.now() + 3_000).toISOString()
      const scheduled = await apiRequest(request, 'POST', `/admin/pages/${fixture.id}/schedule`, { publish_at: publishAt })
      expect(scheduled.ok()).toBeTruthy()

      const before = await adminJson(request, 'GET', `/admin/pages/${fixture.id}?locale=en`)
      expect(before.data.status).toBe('scheduled')

      await new Promise((resolve) => setTimeout(resolve, 3_600))

      runScheduler()

      const after = await adminJson(request, 'GET', `/admin/pages/${fixture.id}?locale=en`)
      expect(after.data.status).toBe('published')

      const live = await request.get(`${WEBSITE_BASE}/en/${fixture.slug}`)
      expect(live.status()).toBe(200)
      expect(await live.text()).toContain(fixture.title)
    } finally {
      await deletePage(request, fixture.id)
    }
  })
})

test.describe('multi-site isolation', () => {
  test('a site administrator cannot read or publish another website’s pages', async ({ request }) => {
    await apiLogin(request, 'admin@meridian-energy.example')

    const crossed = await apiRequest(request, 'GET', '/admin/pages', undefined, 'meridian')
    expect(crossed.status()).toBe(403)

    const own = await apiRequest(request, 'GET', '/admin/pages', undefined, 'meridian-energy')
    expect(own.ok()).toBeTruthy()

    const ownPayload = await own.json()
    expect(Array.isArray(ownPayload.data)).toBe(true)

    const anonymous = await fetch(`${API_BASE}/api/v1/admin/pages`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian' },
    })
    expect(anonymous.status).toBe(401)
  })

  test('public content is scoped to the requested website', async ({ request }) => {
    const meridian = await fetch(`${API_BASE}/api/v1/public/pages/about?locale=en`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian' },
    })
    expect(meridian.status).toBe(200)
    const meridianBody = await meridian.json()
    expect(meridianBody.data.slug).toBe('about')

    const energy = await fetch(`${API_BASE}/api/v1/public/pages/about`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian-energy' },
    })
    expect(energy.status).toBe(404)

    const energyBootstrap = await fetch(`${API_BASE}/api/v1/public/bootstrap`, {
      headers: { Accept: 'application/json', 'X-Site': 'meridian-energy' },
    })
    expect(energyBootstrap.status).toBe(200)
    const energyBody = await energyBootstrap.json()
    expect(energyBody.data.site.slug).toBe('meridian-energy')
  })
})
