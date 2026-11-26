<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft, ExternalLink, Eye, Save, Send, CheckCircle2, Clock3, CalendarClock, Ban,
  History, Settings2, SearchCheck, LayoutTemplate, Image as ImageIcon, Plus, AlertTriangle,
  ChevronDown, Trash2, RotateCcw, MessageSquareWarning, Globe2, Layers,
} from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import MediaPicker from '@/components/MediaPicker.vue'
import { api, apiErrorMessage, apiValidationErrors } from '@/api/client'
import { auth } from '@/stores/auth'
import { blocks } from '@/stores/blocks'
import { toast } from '@/stores/toast'
import type { MediaAsset, PageDetail } from '@/types'

const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))
const locale = ref(String(route.query.locale || 'en'))
const loading = ref(true)
const error = ref('')
const page = ref<PageDetail | null>(null)
const tab = ref<'content' | 'seo' | 'settings' | 'history'>('content')
const saving = ref(false)
const actionLoading = ref(false)
const savedSnapshot = ref('')
const blockDefinitions = computed(() => blocks.definitions.value)

const form = ref({
  title: '',
  slug: '',
  excerpt: '',
  template: 'default',
  parent_id: null as number | null,
  lock_version: 1,
})
const seoForm = ref({
  title: '',
  description: '',
  canonical: '',
  focus_keyword: '',
  og_title: '',
  og_description: '',
  robots: 'index,follow',
  schema_type: 'WebPage',
})
const errors = ref<Record<string, string>>({})

const publishOpen = ref(false)
const scheduleOpen = ref(false)
const changesOpen = ref(false)
const translationOpen = ref(false)
const restoreTarget = ref<any>(null)
const deleteOpen = ref(false)
const mediaPickerOpen = ref(false)
const ogPickerOpen = ref(false)
const pickerTarget = ref<'featured' | 'og'>('featured')
const scheduleAt = ref('')
const changesNote = ref('')
const translationForm = ref({ locale: 'th', title: '', slug: '' })
const translationErrors = ref<Record<string, string>>({})

const canEdit = computed(() => page.value?.capabilities.edit ?? false)
const canPublish = computed(() => page.value?.capabilities.publish ?? false)
const canApprove = computed(() => page.value?.capabilities.approve ?? false)
const dirty = computed(() => {
  if (!page.value) return false
  return JSON.stringify(form.value) !== savedSnapshot.value
})

const enabledLocales = computed(() => {
  const site = auth.user.value?.sites.find((item) => item.id === auth.user.value?.current_site_id)
  return site?.enabled_locales?.length ? site.enabled_locales : ['en', 'th', 'zh-CN']
})

const localeLabel = (code: string) => ({ en: 'English', th: 'ไทย', 'zh-CN': '简体中文' }[code] || code)
const blockLabel = (type: string) => blockDefinitions.value.find((item) => item.type === type)?.label || type
const pageLocale = computed(() => page.value?.translations.find((item) => item.locale === locale.value))
const isMissingTranslation = computed(() => Boolean(page.value && !pageLocale.value))

function snapshot() {
  return JSON.stringify({
    title: form.value.title,
    slug: form.value.slug,
    excerpt: form.value.excerpt,
    template: form.value.template,
    parent_id: form.value.parent_id,
    lock_version: form.value.lock_version,
  })
}

function applyPage(data: PageDetail) {
  page.value = data
  form.value = {
    title: data.title || '',
    slug: data.slug || '',
    excerpt: data.excerpt || '',
    template: data.template || 'default',
    parent_id: data.parent_id,
    lock_version: data.lock_version,
  }
  seoForm.value = {
    title: data.seo?.title || '',
    description: data.seo?.description || '',
    canonical: data.seo?.canonical || '',
    focus_keyword: data.seo?.focus_keyword || '',
    og_title: data.seo?.og_title || '',
    og_description: data.seo?.og_description || '',
    robots: data.seo?.robots || 'index,follow',
    schema_type: data.seo?.schema_type || 'WebPage',
  }
  savedSnapshot.value = snapshot()
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [pageResponse] = await Promise.all([
      api.get(`/pages/${id.value}`, { params: { locale: locale.value } }),
      blocks.load(),
    ])
    applyPage(pageResponse.data.data)
    if (route.query.tab) tab.value = String(route.query.tab) as any
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    loading.value = false
  }
}

function switchLocale(code: string) {
  if (code === locale.value) return

  if (dirty.value && !window.confirm('You have unsaved changes. Switch language and discard them?')) {
    return
  }

  locale.value = code
  router.replace({ query: { ...route.query, locale: code } })
  load()
}

function discardChanges() {
  if (!page.value) return
  if (!window.confirm('Discard unsaved changes and restore the last saved version?')) return

  applyPage(page.value)
  toast.info('Unsaved changes discarded')
}

onBeforeRouteLeave(() => {
  if (!dirty.value) return true

  return window.confirm('You have unsaved changes. Leave this page without saving?')
})

function beforeUnload(event: BeforeUnloadEvent) {
  if (!dirty.value) return
  event.preventDefault()
  event.returnValue = ''
}

onMounted(() => window.addEventListener('beforeunload', beforeUnload))
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload))

async function save() {
  if (!page.value || !canEdit.value) return
  saving.value = true
  errors.value = {}
  try {
    const response = await api.patch(`/pages/${id.value}`, {
      ...form.value,
      locale: locale.value,
      excerpt: form.value.excerpt,
    })
    applyPage(response.data.data)
    toast.success('Page saved', 'The revision history has been updated.')
  } catch (err) {
    errors.value = apiValidationErrors(err)
    toast.error('Could not save page', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function saveSeo() {
  if (!page.value || !canEdit.value) return
  saving.value = true
  try {
    await api.put(`/pages/${id.value}/seo`, { ...seoForm.value, locale: locale.value })
    toast.success('SEO metadata saved')
    await load()
  } catch (err) {
    toast.error('Could not save SEO', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function openPreview() {
  const win = window.open('about:blank', '_blank')
  try {
    const response = await api.post(`/pages/${id.value}/preview-token`, null, { params: { locale: locale.value } })
    if (win) win.location.href = response.data.data.url
    else toast.error('Popup blocked', 'Allow popups to open the preview.')
  } catch (err) {
    win?.close()
    toast.error('Could not create preview', apiErrorMessage(err))
  }
}

async function act(endpoint: string, payload: any = {}, success = 'Action completed') {
  actionLoading.value = true
  try {
    const response = await api.post(`/pages/${id.value}/${endpoint}`, payload)
    toast.success(success)
    publishOpen.value = false
    scheduleOpen.value = false
    changesOpen.value = false
    await load()
    return response
  } catch (err) {
    toast.error('Action failed', apiErrorMessage(err))
  } finally {
    actionLoading.value = false
  }
}

async function schedule() {
  if (!scheduleAt.value) return
  const iso = new Date(scheduleAt.value).toISOString()
  await act('schedule', { publish_at: iso }, 'Publication scheduled')
}

async function requestChanges() {
  if (!changesNote.value.trim()) {
    toast.error('Add a note', 'Explain what needs to change before sending.')
    return
  }
  await act('request-changes', { note: changesNote.value }, 'Changes requested')
}

async function addTranslation() {
  translationErrors.value = {}
  try {
    const response = await api.post(`/pages/${id.value}/translations`, translationForm.value)
    toast.success('Translation added')
    translationOpen.value = false
    locale.value = translationForm.value.locale
    await router.replace({ query: { ...route.query, locale: locale.value } })
    await load()
  } catch (err) {
    translationErrors.value = apiValidationErrors(err)
    toast.error('Could not add translation', apiErrorMessage(err))
  }
}

async function restoreRevision() {
  if (!restoreTarget.value) return
  actionLoading.value = true
  try {
    await api.post(`/pages/${id.value}/revisions/${restoreTarget.value.id}/restore`)
    toast.success('Version restored', `Returned to version ${restoreTarget.value.version}.`)
    restoreTarget.value = null
    await load()
  } catch (err) {
    toast.error('Could not restore version', apiErrorMessage(err))
  } finally {
    actionLoading.value = false
  }
}

async function deletePage() {
  actionLoading.value = true
  try {
    await api.delete(`/pages/${id.value}`)
    toast.success('Page deleted')
    await router.push('/pages')
  } catch (err) {
    toast.error('Could not delete page', apiErrorMessage(err))
  } finally {
    actionLoading.value = false
  }
}

function pickMedia(target: 'featured' | 'og') {
  pickerTarget.value = target
  if (target === 'featured') mediaPickerOpen.value = true
  else ogPickerOpen.value = true
}

async function featuredSelected(asset: MediaAsset) {
  mediaPickerOpen.value = false
  try {
    await api.patch(`/pages/${id.value}`, { locale: locale.value, featured_media_id: asset.id, lock_version: page.value?.lock_version })
    toast.success('Featured image updated')
    await load()
  } catch (err) {
    toast.error('Could not update image', apiErrorMessage(err))
  }
}

async function ogSelected(asset: MediaAsset) {
  ogPickerOpen.value = false
  seoForm.value.og_title = seoForm.value.og_title || seoForm.value.title
  try {
    await api.put(`/pages/${id.value}/seo`, { ...seoForm.value, og_image_id: asset.id, locale: locale.value })
    toast.success('Social image updated')
    await load()
  } catch (err) {
    toast.error('Could not update social image', apiErrorMessage(err))
  }
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

watch(tab, (value) => {
  router.replace({ query: { ...route.query, tab: value } })
})

onMounted(load)

const seoPreviewTitle = computed(() => seoForm.value.title || `${form.value.title} — Meridian Industrial Group`)
const seoPreviewDescription = computed(() => seoForm.value.description || form.value.excerpt || 'Add a meta description to control how this page appears in search results.')
const seoPreviewUrl = computed(() => `meridian.example/${locale.value}/${form.value.slug}`)
</script>

<template>
  <div>
    <div v-if="loading" class="stack-s">
      <div class="skeleton" style="height:30px;width:40%" />
      <div class="skeleton" style="height:420px" />
    </div>

    <div v-else-if="error" class="empty-s">
      <p class="empty-s__title">Page unavailable</p>
      <p class="empty-s__body">{{ error }}</p>
      <button type="button" class="btn-s btn-s--sm" style="margin-top:12px" @click="router.push('/pages')">Back to pages</button>
    </div>

    <template v-else-if="page">
      <PageHead eyebrow="PAGE EDITOR" :title="page.title || 'Untitled page'" :description="`ID ${page.id} · ${page.template} template · last saved by ${page.author?.name || 'system'}`">
        <template #actions>
          <button type="button" class="btn-s btn-s--ghost" @click="router.push('/pages')"><ArrowLeft :size="13" /> Back</button>
          <button type="button" class="btn-s" @click="openPreview"><Eye :size="13" /> Preview</button>
          <RouterLink :to="`/pages/${page.id}/builder?locale=${locale}`" class="btn-s"><LayoutTemplate :size="13" /> Visual builder</RouterLink>
          <button v-if="canEdit" type="button" class="btn-s btn-s--primary" :disabled="saving || !dirty" @click="save">
            <Save :size="13" /> {{ saving ? 'Saving…' : dirty ? 'Save changes' : 'Saved' }}
          </button>
        </template>
      </PageHead>

      <div v-if="dirty" class="unsaved-banner">
        <AlertTriangle :size="13" /> Unsaved content changes. Save before previewing or switching locale.
        <button type="button" class="unsaved-banner__discard" @click="discardChanges">Discard changes</button>
      </div>

      <div class="locale-tabs">
        <button
          v-for="code in enabledLocales"
          :key="code"
          type="button"
          class="locale-tab"
          :class="{ 'is-active': locale === code, 'is-missing': !page?.translations.some(t => t.locale === code) }"
          @click="switchLocale(code)"
        >
          {{ localeLabel(code) }}
          <span v-if="!page?.translations.some(t => t.locale === code)" class="locale-tab__dot" />
        </button>
        <button type="button" class="locale-tab locale-tab--add" @click="translationForm = { locale: enabledLocales.find(c => !page?.translations.some(t => t.locale === c)) || 'th', title: '', slug: '' }; translationOpen = true">
          <Plus :size="11" /> Translation
        </button>
      </div>

      <div class="editor-layout">
        <div class="editor-main">
          <div class="editor-tabs" role="tablist">
            <button type="button" :class="{ 'is-active': tab === 'content' }" @click="tab = 'content'">Content</button>
            <button type="button" :class="{ 'is-active': tab === 'seo' }" @click="tab = 'seo'">SEO</button>
            <button type="button" :class="{ 'is-active': tab === 'settings' }" @click="tab = 'settings'">Settings</button>
            <button type="button" :class="{ 'is-active': tab === 'history' }" @click="tab = 'history'">History</button>
          </div>

          <div v-if="isMissingTranslation" class="missing-translation">
            <div>
              <p class="missing-translation__title">{{ localeLabel(locale) }} translation is missing</p>
              <p class="missing-translation__body">Create it before editing this locale or publishing it.</p>
            </div>
            <button type="button" class="btn-s btn-s--primary btn-s--sm" @click="translationForm = { locale, title: '', slug: '' }; translationOpen = true">
              <Plus :size="12" /> Add translation
            </button>
          </div>

          <template v-else>
            <section v-if="tab === 'content'" class="panel">
              <div class="panel__body">
                <div class="content-grid">
                  <div class="field-s" style="grid-column: 1 / -1">
                    <label class="field-s__label" for="page-title">Title</label>
                    <input id="page-title" v-model="form.title" class="input-s" :aria-invalid="!!errors.title" :disabled="!canEdit">
                    <p v-if="errors.title" class="error-s">{{ errors.title }}</p>
                  </div>
                  <div class="field-s">
                    <label class="field-s__label" for="page-slug">Slug</label>
                    <input id="page-slug" v-model="form.slug" class="input-s" :aria-invalid="!!errors.slug" :disabled="!canEdit">
                    <p class="field-s__hint">/{{ locale === 'zh-CN' ? 'zh-cn' : locale }}/{{ form.slug }}</p>
                    <p v-if="errors.slug" class="error-s">{{ errors.slug }}</p>
                  </div>
                  <div class="field-s">
                    <label class="field-s__label">Locale</label>
                    <div class="readonly-box mono">{{ localeLabel(locale) }}</div>
                  </div>
                  <div class="field-s" style="grid-column: 1 / -1">
                    <label class="field-s__label" for="page-excerpt">Excerpt</label>
                    <textarea id="page-excerpt" v-model="form.excerpt" class="textarea-s" rows="4" :disabled="!canEdit" />
                  </div>
                </div>

                <div class="blocks-summary">
                  <div class="flex-between">
                    <div>
                      <p class="panel__title">Page layout</p>
                      <p class="mono muted" style="margin-top:3px">{{ page.blocks.length }} blocks · schema v1</p>
                    </div>
                    <RouterLink :to="`/pages/${page.id}/builder?locale=${locale}`" class="btn-s btn-s--sm"><Layers :size="12" /> Open builder</RouterLink>
                  </div>
                  <div class="mini-blocks">
                    <div v-for="(block, index) in page.blocks" :key="block.uid" class="mini-block">
                      <span class="mono">{{ String(index + 1).padStart(2, '0') }}</span>
                      <span>{{ blockLabel(block.type) }}</span>
                      <span class="mono faint">{{ block.uid.slice(0, 8) }}</span>
                    </div>
                    <div v-if="!page.blocks.length" class="mini-block mini-block--empty">No blocks yet. Open the builder to compose the page.</div>
                  </div>
                </div>
              </div>
            </section>

            <section v-else-if="tab === 'seo'" class="panel">
              <div class="panel__body">
                <div class="seo-grid">
                  <div class="seo-fields">
                    <div class="field-s">
                      <label class="field-s__label" for="seo-title">SEO title <span>{{ seoForm.title.length }}/60</span></label>
                      <input id="seo-title" v-model="seoForm.title" class="input-s" placeholder="Leave blank to use the page title">
                    </div>
                    <div class="field-s">
                      <label class="field-s__label" for="seo-description">Meta description <span>{{ seoForm.description.length }}/160</span></label>
                      <textarea id="seo-description" v-model="seoForm.description" class="textarea-s" rows="4" placeholder="A specific, benefit-led description." />
                    </div>
                    <div class="two-fields">
                      <div class="field-s">
                        <label class="field-s__label" for="seo-canonical">Canonical URL</label>
                        <input id="seo-canonical" v-model="seoForm.canonical" class="input-s" placeholder="https://meridian.example/…">
                      </div>
                      <div class="field-s">
                        <label class="field-s__label" for="seo-focus">Focus keyword</label>
                        <input id="seo-focus" v-model="seoForm.focus_keyword" class="input-s" placeholder="Editorial guidance only">
                      </div>
                    </div>
                    <div class="two-fields">
                      <div class="field-s">
                        <label class="field-s__label" for="seo-robots">Robots</label>
                        <select id="seo-robots" v-model="seoForm.robots" class="select-s">
                          <option value="index,follow">index, follow</option>
                          <option value="noindex,follow">noindex, follow</option>
                          <option value="index,nofollow">index, nofollow</option>
                          <option value="noindex,nofollow">noindex, nofollow</option>
                        </select>
                      </div>
                      <div class="field-s">
                        <label class="field-s__label" for="seo-schema">Schema type</label>
                        <select id="seo-schema" v-model="seoForm.schema_type" class="select-s">
                          <option value="WebPage">WebPage</option>
                          <option value="AboutPage">AboutPage</option>
                          <option value="ContactPage">ContactPage</option>
                          <option value="Article">Article</option>
                        </select>
                      </div>
                    </div>
                    <div class="two-fields">
                      <div class="field-s">
                        <label class="field-s__label" for="seo-og-title">OG title</label>
                        <input id="seo-og-title" v-model="seoForm.og_title" class="input-s">
                      </div>
                      <div class="field-s">
                        <label class="field-s__label">Social image</label>
                        <button type="button" class="media-select" @click="pickMedia('og')">
                          <img v-if="page.seo?.og_image?.url" :src="page.seo.og_image.url" alt="">
                          <span v-else><ImageIcon :size="14" /> Choose image</span>
                        </button>
                      </div>
                    </div>
                    <div class="field-s">
                      <label class="field-s__label" for="seo-og-desc">OG description</label>
                      <textarea id="seo-og-desc" v-model="seoForm.og_description" class="textarea-s" rows="3" />
                    </div>
                    <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="saveSeo"><Save :size="13" /> Save SEO</button>
                  </div>

                  <aside class="seo-preview">
                    <p class="mono muted">SEARCH PREVIEW</p>
                    <div class="serp">
                      <p class="serp__url">{{ seoPreviewUrl }}</p>
                      <p class="serp__title">{{ seoPreviewTitle }}</p>
                      <p class="serp__description">{{ seoPreviewDescription }}</p>
                    </div>
                    <div class="seo-checks">
                      <p class="mono muted">CHECKS</p>
                      <p :class="seoForm.title.length > 60 ? 'is-warn' : 'is-ok'">{{ seoForm.title.length > 60 ? 'Title is long' : 'Title length is healthy' }}</p>
                      <p :class="seoForm.description.length > 160 || seoForm.description.length < 60 ? 'is-warn' : 'is-ok'">{{ seoForm.description.length > 160 ? 'Description is long' : seoForm.description.length < 60 ? 'Description is short' : 'Description length is healthy' }}</p>
                      <p :class="page.seo?.og_image ? 'is-ok' : 'is-warn'">{{ page.seo?.og_image ? 'Social image present' : 'No social image' }}</p>
                      <p :class="seoForm.canonical ? 'is-ok' : 'is-ok'">{{ seoForm.canonical ? 'Canonical set explicitly' : 'Canonical generated from slug' }}</p>
                    </div>
                  </aside>
                </div>
              </div>
            </section>

            <section v-else-if="tab === 'settings'" class="panel">
              <div class="panel__body">
                <div class="settings-grid">
                  <div class="field-s">
                    <label class="field-s__label">Template</label>
                    <select v-model="form.template" class="select-s" :disabled="!canEdit">
                      <option value="default">Default</option>
                      <option value="home">Home</option>
                      <option value="editorial">Editorial</option>
                      <option value="contact">Contact</option>
                      <option value="legal">Legal</option>
                    </select>
                  </div>
                  <div class="field-s">
                    <label class="field-s__label">Parent page ID</label>
                    <input v-model.number="form.parent_id" type="number" class="input-s" placeholder="None" :disabled="!canEdit">
                  </div>
                  <div class="field-s">
                    <label class="field-s__label">Featured image</label>
                    <button type="button" class="media-select" @click="pickMedia('featured')">
                      <img v-if="page.featured_media?.url" :src="page.featured_media.url" alt="">
                      <span v-else><ImageIcon :size="14" /> Choose image</span>
                    </button>
                  </div>
                  <div class="field-s">
                    <label class="field-s__label">Page state</label>
                    <div class="readonly-box"><StatusBadge :status="page.status" /></div>
                  </div>
                </div>

                <div class="translations-panel">
                  <p class="panel__title" style="margin-bottom:8px">Translations</p>
                  <div v-for="translation in page.translations" :key="translation.locale" class="translation-row">
                    <span class="mono">{{ translation.locale }}</span>
                    <span>{{ translation.title }}</span>
                    <span class="mono faint">/{{ translation.slug }}</span>
                    <button type="button" class="btn-s btn-s--ghost btn-s--sm" @click="switchLocale(translation.locale)">Edit</button>
                  </div>
                  <div v-for="missing in page.missing_locales" :key="missing" class="translation-row translation-row--missing">
                    <span class="mono">{{ missing }}</span>
                    <span>Missing translation</span>
                    <span />
                    <button type="button" class="btn-s btn-s--sm" @click="translationForm = { locale: missing, title: '', slug: '' }; translationOpen = true">Add</button>
                  </div>
                </div>

                <div v-if="auth.can('pages.delete') && !page.is_home" class="danger-zone">
                  <div>
                    <p class="panel__title">Delete this page</p>
                    <p class="muted" style="font-size: 12px;margin-top:3px">This removes the page and its revisions. The homepage cannot be deleted.</p>
                  </div>
                  <button type="button" class="btn-s btn-s--danger" @click="deleteOpen = true"><Trash2 :size="13" /> Delete page</button>
                </div>
              </div>
            </section>

            <section v-else-if="tab === 'history'" class="panel">
              <div class="panel__body">
                <div class="flex-between" style="margin-bottom:10px">
                  <p class="panel__title">Revision history</p>
                  <span class="mono faint">{{ page.revisions.length }} recent versions</span>
                </div>
                <div v-if="page.revisions.length" class="revision-list">
                  <div v-for="revision in page.revisions" :key="revision.id" class="revision-row">
                    <span class="revision-row__version mono">v{{ revision.version }}</span>
                    <div>
                      <p class="revision-row__message">{{ revision.message || 'Content updated' }}</p>
                      <p class="revision-row__meta mono">{{ revision.locale }} · {{ revision.user || 'System' }} · {{ formatDate(revision.created_at) }}</p>
                    </div>
                    <button type="button" class="btn-s btn-s--sm" @click="restoreTarget = revision"><RotateCcw :size="12" /> Restore</button>
                  </div>
                </div>
                <div v-else class="table-empty">No revisions have been captured for this locale.</div>
              </div>
            </section>
          </template>
        </div>

        <aside class="editor-side">
          <section class="panel">
            <div class="panel__head"><p class="panel__title">Publication</p><StatusBadge :status="page.status" /></div>
            <div class="panel__body stack-s">
              <div class="meta-row"><span>Published</span><strong>{{ formatDate(page.published_at) }}</strong></div>
              <div class="meta-row"><span>Scheduled</span><strong>{{ formatDate(page.scheduled_at) }}</strong></div>
              <div class="meta-row"><span>Author</span><strong>{{ page.author?.name || '—' }}</strong></div>
              <div class="meta-row"><span>Last updated</span><strong>{{ formatDate(page.updated_at) }}</strong></div>

              <div class="workflow-actions">
                <button v-if="['draft','changes_requested'].includes(page.status) && canEdit" type="button" class="btn-s btn-s--primary" :disabled="actionLoading" @click="act('submit', { note: 'Ready for review.' }, 'Submitted for review')">
                  <Send :size="13" /> Submit for review
                </button>
                <template v-if="page.status === 'in_review'">
                  <button v-if="canApprove" type="button" class="btn-s btn-s--primary" :disabled="actionLoading" @click="act('approve', { note: 'Approved.' }, 'Page approved')"><CheckCircle2 :size="13" /> Approve</button>
                  <button v-if="canApprove" type="button" class="btn-s" :disabled="actionLoading" @click="changesOpen = true"><MessageSquareWarning :size="13" /> Request changes</button>
                </template>
                <button v-if="['approved','draft'].includes(page.status) && canPublish" type="button" class="btn-s btn-s--accent" :disabled="actionLoading" @click="publishOpen = true"><CheckCircle2 :size="13" /> Publish now</button>
                <button v-if="['approved','draft'].includes(page.status) && canPublish" type="button" class="btn-s" :disabled="actionLoading" @click="scheduleOpen = true"><CalendarClock :size="13" /> Schedule</button>
                <button v-if="page.status === 'published' && canPublish" type="button" class="btn-s btn-s--danger" :disabled="actionLoading" @click="act('unpublish', {}, 'Page unpublished')"><Ban :size="13" /> Unpublish</button>
                <button v-if="page.status === 'scheduled' && canPublish" type="button" class="btn-s" :disabled="actionLoading" @click="act('cancel-schedule', {}, 'Schedule cancelled')"><Ban :size="13" /> Cancel schedule</button>
              </div>
            </div>
          </section>

          <section v-if="page.workflow" class="panel">
            <div class="panel__head"><p class="panel__title">Review trail</p><StatusBadge :status="page.workflow.status" /></div>
            <div class="panel__body">
              <div v-for="(step, index) in page.workflow.steps" :key="index" class="workflow-step" :class="{ 'is-current': page.workflow.current_step === index && page.workflow.status === 'in_review', 'is-done': page.workflow.status === 'completed' && index <= page.workflow.current_step }">
                <span class="workflow-step__dot" />
                <span>{{ step.name }}</span>
              </div>
              <div class="workflow-approvals">
                <div v-for="approval in page.workflow.approvals" :key="approval.decided_at + approval.step_name" class="approval-row">
                  <p>{{ approval.step_name }} <span class="mono faint">{{ approval.decision }}</span></p>
                  <p class="mono faint">{{ approval.user || 'System' }} · {{ formatDate(approval.decided_at) }}</p>
                  <p v-if="approval.note" class="approval-note">{{ approval.note }}</p>
                </div>
              </div>
            </div>
          </section>
        </aside>
      </div>

      <BaseModal :open="publishOpen" title="Publish page" @close="publishOpen = false">
        <p style="font-size:12px;line-height:1.6">Publish <strong>{{ page.title }}</strong>? The page will become publicly available at its canonical URL and the published revision will be recorded in the audit log.</p>
        <template #footer>
          <button type="button" class="btn-s" @click="publishOpen = false">Cancel</button>
          <button type="button" class="btn-s btn-s--accent" :disabled="actionLoading" @click="act('publish', {}, 'Page published')">Publish now</button>
        </template>
      </BaseModal>

      <BaseModal :open="scheduleOpen" title="Schedule publication" @close="scheduleOpen = false">
        <div class="field-s">
          <label class="field-s__label" for="schedule-at">Publication date and time</label>
          <input id="schedule-at" v-model="scheduleAt" type="datetime-local" class="input-s">
          <p class="field-s__hint">The scheduler checks every minute. The action is idempotent.</p>
        </div>
        <template #footer>
          <button type="button" class="btn-s" @click="scheduleOpen = false">Cancel</button>
          <button type="button" class="btn-s btn-s--primary" :disabled="actionLoading || !scheduleAt" @click="schedule">Schedule</button>
        </template>
      </BaseModal>

      <BaseModal :open="changesOpen" title="Request changes" @close="changesOpen = false">
        <div class="field-s">
          <label class="field-s__label" for="changes-note">What needs to change?</label>
          <textarea id="changes-note" v-model="changesNote" class="textarea-s" rows="5" placeholder="Be specific. The author receives this note in their workflow inbox." />
        </div>
        <template #footer>
          <button type="button" class="btn-s" @click="changesOpen = false">Cancel</button>
          <button type="button" class="btn-s btn-s--primary" :disabled="actionLoading" @click="requestChanges">Send request</button>
        </template>
      </BaseModal>

      <BaseModal :open="translationOpen" title="Add a translation" @close="translationOpen = false">
        <div class="stack-s">
          <div class="field-s">
            <label class="field-s__label" for="translation-locale">Locale</label>
            <select id="translation-locale" v-model="translationForm.locale" class="select-s">
              <option v-for="code in enabledLocales.filter(c => !page?.translations.some(t => t.locale === c))" :key="code" :value="code">{{ localeLabel(code) }}</option>
            </select>
          </div>
          <div class="field-s">
            <label class="field-s__label" for="translation-title">Title</label>
            <input id="translation-title" v-model="translationForm.title" class="input-s" @input="!translationForm.slug && (translationForm.slug = translationForm.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''))">
            <p v-if="translationErrors.title" class="error-s">{{ translationErrors.title }}</p>
          </div>
          <div class="field-s">
            <label class="field-s__label" for="translation-slug">Slug</label>
            <input id="translation-slug" v-model="translationForm.slug" class="input-s">
            <p v-if="translationErrors.slug" class="error-s">{{ translationErrors.slug }}</p>
          </div>
        </div>
        <template #footer>
          <button type="button" class="btn-s" @click="translationOpen = false">Cancel</button>
          <button type="button" class="btn-s btn-s--primary" :disabled="!translationForm.title" @click="addTranslation">Add translation</button>
        </template>
      </BaseModal>

      <BaseModal :open="Boolean(restoreTarget)" title="Restore revision" @close="restoreTarget = null">
        <p style="font-size:12px;line-height:1.6">Restore version <strong>v{{ restoreTarget?.version }}</strong>? The current content is saved as a new revision first, so this can be reversed.</p>
        <template #footer>
          <button type="button" class="btn-s" @click="restoreTarget = null">Cancel</button>
          <button type="button" class="btn-s btn-s--primary" :disabled="actionLoading" @click="restoreRevision">Restore version</button>
        </template>
      </BaseModal>

      <BaseModal :open="deleteOpen" title="Delete page" @close="deleteOpen = false">
        <p style="font-size:12px;line-height:1.6">Permanently delete <strong>{{ page.title }}</strong>? This cannot be undone.</p>
        <template #footer>
          <button type="button" class="btn-s" @click="deleteOpen = false">Cancel</button>
          <button type="button" class="btn-s btn-s--danger" :disabled="actionLoading" @click="deletePage">Delete page</button>
        </template>
      </BaseModal>

      <MediaPicker :open="mediaPickerOpen" title="Choose featured image" @close="mediaPickerOpen = false" @select="featuredSelected" />
      <MediaPicker :open="ogPickerOpen" title="Choose social image" @close="ogPickerOpen = false" @select="ogSelected" />
    </template>
  </div>
</template>

<style scoped>
.unsaved-banner {
  background: var(--s-amber-soft);
  border: 1px solid #e6d2a8;
  color: #71500f;
  border-radius: var(--s-radius);
  padding: 7px 10px;
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  margin-bottom: 10px;
}

.unsaved-banner__discard {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #71500f;
  font-size: 12px;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
}

.locale-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  border-bottom: 1px solid var(--s-line);
  margin-bottom: 14px;
}

.locale-tab {
  border: 0;
  background: transparent;
  padding: 8px 12px;
  font-size: 12px;
  color: var(--s-muted);
  border-bottom: 2px solid transparent;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.locale-tab:hover { color: var(--s-ink); }

.locale-tab.is-active {
  color: var(--s-ink);
  font-weight: 650;
  border-bottom-color: var(--s-accent);
}

.locale-tab.is-missing { color: var(--s-faint); }

.locale-tab__dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--s-amber);
}

.locale-tab--add { margin-left: auto; color: var(--s-accent); }

.editor-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 300px;
  gap: 14px;
  align-items: start;
}

.editor-tabs {
  display: flex;
  gap: 2px;
  border-bottom: 1px solid var(--s-line);
  margin-bottom: 10px;
}

.editor-tabs button {
  border: 0;
  background: transparent;
  padding: 8px 12px;
  font-size: 12px;
  color: var(--s-muted);
  border-bottom: 2px solid transparent;
}

.editor-tabs button:hover { color: var(--s-ink); }

.editor-tabs button.is-active {
  color: var(--s-ink);
  border-bottom-color: var(--s-ink);
  font-weight: 650;
}

.missing-translation {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: var(--s-surface);
  border: 1px dashed var(--s-line-strong);
  border-radius: var(--s-radius);
  padding: 18px;
}

.missing-translation__title { font-size: 12px; font-weight: 700; }
.missing-translation__body { margin-top: 3px; font-size: 12px; color: var(--s-muted); }

.content-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 14px;
}

.readonly-box {
  height: 38px;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  background: var(--s-bg);
  display: flex;
  align-items: center;
  padding: 0 10px;
  font-size: 12px;
  color: var(--s-muted);
}

.blocks-summary {
  margin-top: 24px;
  border-top: 1px solid var(--s-line);
  padding-top: 16px;
}

.mini-blocks {
  margin-top: 10px;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  overflow: hidden;
}

.mini-block {
  display: grid;
  grid-template-columns: 36px 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 7px 10px;
  font-size: 12px;
  border-bottom: 1px solid var(--s-line);
  background: var(--s-white);
}

.mini-block:last-child { border-bottom: 0; }
.mini-block .mono { color: var(--s-faint); }
.mini-block--empty { display: block; color: var(--s-muted); }

.seo-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: 22px;
}

.seo-fields {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.two-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.seo-preview {
  border-left: 1px solid var(--s-line);
  padding-left: 20px;
}

.serp {
  margin-top: 10px;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  padding: 12px;
  background: var(--s-white);
}

.serp__url { font-size: 12px; color: var(--s-muted); }
.serp__title { margin-top: 4px; font-size: 14px; color: #1a0dab; line-height: 1.3; }
.serp__description { margin-top: 4px; font-size: 12px; line-height: 1.5; color: var(--s-muted); }

.seo-checks {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-size: 12px;
}

.seo-checks .is-ok::before { content: "✓"; color: var(--s-green); margin-right: 5px; }
.seo-checks .is-warn::before { content: "!"; color: var(--s-amber); margin-right: 5px; }

.settings-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.media-select {
  height: 76px;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  background: var(--s-white);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  overflow: hidden;
  color: var(--s-muted);
  font-size: 12px;
}

.media-select img { width: 100%; height: 100%; object-fit: cover; }

.translations-panel {
  margin-top: 22px;
  border-top: 1px solid var(--s-line);
  padding-top: 16px;
}

.translation-row {
  display: grid;
  grid-template-columns: 50px 1fr 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 7px 0;
  border-bottom: 1px solid var(--s-line);
  font-size: 12px;
}

.translation-row--missing { color: var(--s-muted); }
.translation-row .mono { color: var(--s-faint); }

.danger-zone {
  margin-top: 24px;
  border: 1px solid var(--s-red-soft);
  border-radius: var(--s-radius);
  padding: 13px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  background: #fdf7f5;
}

.revision-list { border-top: 1px solid var(--s-line); }

.revision-row {
  display: grid;
  grid-template-columns: 44px 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--s-line);
}

.revision-row__version { color: var(--s-accent); }
.revision-row__message { font-size: 12px; font-weight: 600; }
.revision-row__meta { color: var(--s-faint); font-size: 8px; margin-top: 2px; }

.editor-side {
  display: flex;
  flex-direction: column;
  gap: 12px;
  position: sticky;
  top: calc(var(--s-topbar) + 14px);
}

.meta-row {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  font-size: 12px;
  color: var(--s-muted);
  padding-bottom: 7px;
  border-bottom: 1px solid var(--s-line);
}

.meta-row strong { color: var(--s-ink); font-weight: 600; text-align: right; font-size: 12px; }

.workflow-actions {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding-top: 8px;
}

.workflow-actions .btn-s { width: 100%; justify-content: flex-start; }

.workflow-step {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 0;
  font-size: 12px;
  color: var(--s-muted);
  border-bottom: 1px solid var(--s-line);
}

.workflow-step__dot { width: 6px; height: 6px; border: 1px solid var(--s-line-strong); border-radius: 50%; }
.workflow-step.is-current { color: var(--s-ink); font-weight: 600; }
.workflow-step.is-current .workflow-step__dot { background: var(--s-amber); border-color: var(--s-amber); }
.workflow-step.is-done .workflow-step__dot { background: var(--s-green); border-color: var(--s-green); }

.workflow-approvals { margin-top: 10px; }
.approval-row { padding: 7px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }
.approval-note { margin-top: 3px; color: var(--s-muted); line-height: 1.4; }

@media (max-width: 1100px) {
  .editor-layout { grid-template-columns: 1fr; }
  .editor-side { position: static; }
  .seo-grid { grid-template-columns: 1fr; }
  .seo-preview { border-left: 0; padding-left: 0; border-top: 1px solid var(--s-line); padding-top: 14px; }
}

@media (max-width: 700px) {
  .content-grid, .settings-grid, .two-fields { grid-template-columns: 1fr; }
  .translation-row { grid-template-columns: 40px 1fr auto; }
  .translation-row > :nth-child(3) { display: none; }
}
</style>
