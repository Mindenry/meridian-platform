import { expect, test } from '@playwright/test'
import {
  API_BASE,
  STUDIO_BASE,
  WEBSITE_BASE,
  apiLogin,
  apiRequest,
  createDraftPage,
  deletePage,
  studioLogin,
} from './support/api'

test.describe('Studio publishing workflow', () => {
  test('an administrator submits, approves, and publishes a page from the UI', async ({ page, request }) => {
    await apiLogin(request, 'admin@meridian.example')
    const fixture = await createDraftPage(request, 'workflow')

    try {
      await studioLogin(page, 'admin@meridian.example')
      await page.goto(`${STUDIO_BASE}/pages/${fixture.id}?locale=en`)

      await expect(page.getByRole('heading', { name: fixture.title })).toBeVisible()
      await expect(page.locator('.editor-side .badge').first()).toHaveText('Draft')

      await page.getByRole('button', { name: 'Submit for review' }).click()
      await expect(page.locator('.editor-side .badge').first()).toHaveText('In review')
      await expect(page.getByRole('button', { name: 'Approve' })).toBeVisible()

      await page.getByRole('button', { name: 'Approve' }).click()
      await expect(page.locator('.editor-side .badge').first()).toHaveText('Approved')

      await page.getByRole('button', { name: 'Publish now' }).first().click()
      const publishDialog = page.getByRole('dialog', { name: 'Publish page' })
      await expect(publishDialog).toBeVisible()
      await publishDialog.getByRole('button', { name: 'Publish now' }).click()

      await expect(page.locator('.editor-side .badge').first()).toHaveText('Published')

      const published = await request.get(`${WEBSITE_BASE}/en/${fixture.slug}`)
      expect(published.status()).toBe(200)
      expect(await published.text()).toContain(fixture.title)
    } finally {
      await deletePage(request, fixture.id)
    }
  })

  test('a content editor cannot approve or publish, and the page stays private', async ({ request, playwright }) => {
    await apiLogin(request, 'editor@meridian.example')
    const fixture = await createDraftPage(request, 'rbac')

    const editorApprove = await apiRequest(request, 'POST', `/admin/pages/${fixture.id}/approve`, { note: 'Approved.' })
    expect(editorApprove.status()).toBe(403)

    const editorPublish = await apiRequest(request, 'POST', `/admin/pages/${fixture.id}/publish`)
    expect(editorPublish.status()).toBe(403)

    const privatePage = await request.get(`${WEBSITE_BASE}/en/${fixture.slug}`)
    expect(privatePage.status()).toBe(404)

    const submitted = await apiRequest(request, 'POST', `/admin/pages/${fixture.id}/submit`, { note: 'Ready for review.' })
    expect(submitted.ok()).toBeTruthy()

    const admin = await playwright.request.newContext({ baseURL: API_BASE })
    try {
      await apiLogin(admin, 'admin@meridian.example')

      const approved = await apiRequest(admin, 'POST', `/admin/pages/${fixture.id}/approve`, { note: 'Approved by admin.' })
      expect(approved.ok()).toBeTruthy()

      const published = await apiRequest(admin, 'POST', `/admin/pages/${fixture.id}/publish`)
      expect(published.ok()).toBeTruthy()

      const live = await admin.get(`${WEBSITE_BASE}/en/${fixture.slug}`)
      expect(live.status()).toBe(200)
      expect(await live.text()).toContain(fixture.title)
    } finally {
      await deletePage(admin, fixture.id).catch(() => undefined)
      await admin.dispose()
      await deletePage(request, fixture.id)
    }
  })
})
