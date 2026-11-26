<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Plus, Search, Filter, Copy, Trash2, Pencil, MoreHorizontal, ChevronLeft, ChevronRight, FileText, ExternalLink, CheckCircle2, Archive } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import BaseModal from '@/components/BaseModal.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage, apiValidationErrors } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'
import type { PageRow, PaginationMeta } from '@/types'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref('')
const rows = ref<PageRow[]>([])
const meta = ref<PaginationMeta | null>(null)
const counts = ref<Record<string, number>>({})
const selected = ref<number[]>([])
const search = ref(String(route.query.q || ''))
const status = ref(String(route.query.status || ''))
const locale = ref(String(route.query.locale || ''))
const createOpen = ref(false)
const deleteTarget = ref<PageRow | null>(null)
const bulkConfirm = ref<'publish' | 'archive' | 'delete' | null>(null)
const saving = ref(false)
const routeQuery = computed(() => route.query)

const createForm = ref({
  title: '',
  slug: '',
  locale: 'en',
  template: 'default',
  excerpt: '',
})
const createErrors = ref<Record<string, string>>({})

const locales = [
  { code: 'en', label: 'EN' },
  { code: 'th', label: 'TH' },
  { code: 'zh-CN', label: '中文' },
]

const filters = computed(() => ({
  q: String(route.query.q || ''),
  status: String(route.query.status || ''),
  locale: String(route.query.locale || ''),
  page: Math.max(1, Number(route.query.page || 1)),
}))

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: 'published', label: 'Published' },
  { value: 'draft', label: 'Draft' },
  { value: 'in_review', label: 'In review' },
  { value: 'changes_requested', label: 'Changes requested' },
  { value: 'approved', label: 'Approved' },
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'archived', label: 'Archived' },
]

async function load() {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/pages', {
      params: { ...filters.value, per_page: 20, locale: filters.value.locale || undefined },
    })
    rows.value = response.data.data
    meta.value = response.data.meta
    counts.value = response.data.counts
    selected.value = []
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  const query: Record<string, string> = {}
  if (search.value.trim()) query.q = search.value.trim()
  if (status.value) query.status = status.value
  if (locale.value) query.locale = locale.value
  router.replace({ path: '/pages', query })
  load()
}

watch(() => route.query, (value, old) => {
  if (JSON.stringify(value) !== JSON.stringify(old)) {
    search.value = String(value.q || '')
    status.value = String(value.status || '')
    locale.value = String(value.locale || '')
    load()
  }
})

watch(search, (value) => {
  window.clearTimeout((search as any)._timer)
  ;(search as any)._timer = window.setTimeout(() => applyFilters(), 400)
})

function toggleSelect(id: number) {
  selected.value = selected.value.includes(id)
    ? selected.value.filter((item) => item !== id)
    : [...selected.value, id]
}

function toggleAll() {
  selected.value = selected.value.length === rows.value.length ? [] : rows.value.map((row) => row.id)
}

function openCreate() {
  createForm.value = { title: '', slug: '', locale: 'en', template: 'default', excerpt: '' }
  createErrors.value = {}
  createOpen.value = true
}

async function createPage() {
  saving.value = true
  createErrors.value = {}
  try {
    const response = await api.post('/pages', createForm.value)
    toast.success('Page created', 'Continue in the editor or open the visual builder.')
    createOpen.value = false
    await router.push(`/pages/${response.data.data.id}`)
  } catch (err) {
    createErrors.value = apiValidationErrors(err)
    toast.error('Could not create page', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function duplicate(row: PageRow) {
  try {
    const response = await api.post(`/pages/${row.id}/duplicate`)
    toast.success('Page duplicated', 'The copy is a draft.')
    await router.push(`/pages/${response.data.data.id}`)
  } catch (err) {
    toast.error('Could not duplicate page', apiErrorMessage(err))
  }
}

async function removePage() {
  if (!deleteTarget.value) return
  saving.value = true
  try {
    await api.delete(`/pages/${deleteTarget.value.id}`)
    toast.success('Page deleted')
    deleteTarget.value = null
    await load()
  } catch (err) {
    toast.error('Could not delete page', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function runBulk() {
  if (!bulkConfirm.value) return
  saving.value = true
  try {
    const response = await api.post('/pages/bulk', { ids: selected.value, action: bulkConfirm.value })
    const failed = response.data.data.filter((item: any) => !item.ok)
    if (failed.length) toast.error('Some pages were not processed', `${failed.length} page(s) were skipped.`)
    else toast.success(`Bulk ${bulkConfirm.value} complete`)
    bulkConfirm.value = null
    await load()
  } catch (err) {
    toast.error('Bulk action failed', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function localeLabel(code: string) {
  return locales.find((item) => item.code === code)?.label || code
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value))
}

onMounted(async () => {
  await load()
  if (route.query.create === '1' && auth.can('pages.create')) openCreate()
})
</script>

<template>
  <div>
    <PageHead
      eyebrow="CONTENT"
      title="Pages"
      description="Compose, review, and publish every page across the selected website and its enabled languages."
    >
      <template #actions>
        <button v-if="auth.can('pages.create')" type="button" class="btn-s btn-s--primary" @click="openCreate">
          <Plus :size="14" /> New page
        </button>
      </template>
    </PageHead>

    <div class="toolbar">
      <div class="toolbar__search">
        <Search :size="13" />
        <input v-model="search" class="input-s" placeholder="Search pages by title or slug" aria-label="Search pages">
      </div>
      <select v-model="status" class="select-s" style="width: 160px" aria-label="Filter status">
        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
      <select v-model="locale" class="select-s" style="width: 130px" aria-label="Filter locale">
        <option value="">All locales</option>
        <option v-for="item in locales" :key="item.code" :value="item.code">{{ item.label }}</option>
      </select>
      <button type="button" class="btn-s btn-s--ghost btn-s--sm" @click="applyFilters"><Filter :size="13" /> Apply</button>
      <div class="toolbar__spacer" />
      <div class="filter-pill" :class="{ 'is-active': !status }" role="button" tabindex="0" @click="status = ''; applyFilters()">
        All <span>{{ Object.values(counts).reduce((sum, n) => sum + Number(n), 0) }}</span>
      </div>
      <div class="filter-pill" :class="{ 'is-active': status === 'published' }" role="button" tabindex="0" @click="status = 'published'; applyFilters()">
        Published <span>{{ counts.published || 0 }}</span>
      </div>
      <div class="filter-pill" :class="{ 'is-active': status === 'in_review' }" role="button" tabindex="0" @click="status = 'in_review'; applyFilters()">
        In review <span>{{ counts.in_review || 0 }}</span>
      </div>
    </div>

    <div v-if="selected.length" class="bulk-bar">
      <span>{{ selected.length }} selected</span>
      <div class="flex-s">
        <button v-if="auth.can('pages.publish')" type="button" class="btn-s btn-s--sm" @click="bulkConfirm = 'publish'"><CheckCircle2 :size="12" /> Publish</button>
        <button type="button" class="btn-s btn-s--sm" @click="bulkConfirm = 'archive'"><Archive :size="12" /> Archive</button>
        <button v-if="auth.can('pages.delete')" type="button" class="btn-s btn-s--sm btn-s--danger" @click="bulkConfirm = 'delete'"><Trash2 :size="12" /> Delete</button>
        <button type="button" class="btn-s btn-s--ghost btn-s--sm" @click="selected = []">Clear</button>
      </div>
    </div>

    <div class="panel">
      <div v-if="loading" class="panel__body">
        <div v-for="i in 7" :key="i" class="skeleton" style="height:42px;margin-bottom:8px" />
      </div>

      <div v-else-if="error" class="table-empty">
        <p>{{ error }}</p>
        <button type="button" class="btn-s btn-s--sm" style="margin-top:10px" @click="load">Try again</button>
      </div>

      <EmptyState
        v-else-if="!rows.length"
        title="No pages match this view"
        body="Clear the filters or create a new page to get started."
        :action-label="auth.can('pages.create') ? 'Create page' : undefined"
        @action="openCreate"
      />

      <div v-else class="table-wrap">
        <table class="table-s">
          <thead>
            <tr>
              <th style="width: 34px">
                <input type="checkbox" :checked="selected.length === rows.length && rows.length > 0" aria-label="Select all pages" @change="toggleAll">
              </th>
              <th>Page</th>
              <th>Status</th>
              <th>Locales</th>
              <th>Author</th>
              <th>Updated</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id" :class="{ 'is-selected': selected.includes(row.id) }">
              <td><input type="checkbox" :checked="selected.includes(row.id)" :aria-label="`Select ${row.title}`" @change="toggleSelect(row.id)"></td>
              <td>
                <RouterLink :to="`/pages/${row.id}`" class="table-s__title">
                  {{ row.title }}
                  <span v-if="row.is_home" class="badge badge--neutral">HOME</span>
                </RouterLink>
                <div class="table-s__sub">/{{ row.slug }} · ID {{ row.id }}</div>
              </td>
              <td><StatusBadge :status="row.status" /></td>
              <td>
                <div class="locale-row">
                  <span v-for="item in locales" :key="item.code" class="locale-pill" :class="{ 'is-present': row.locales.some((l) => l.locale === item.code) }">
                    {{ item.label }}
                  </span>
                </div>
              </td>
              <td class="muted">{{ row.author || '—' }}</td>
              <td class="muted nowrap">{{ formatDate(row.updated_at) }}</td>
              <td>
                <div class="table-s__actions">
                  <RouterLink :to="`/pages/${row.id}`" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Edit"><Pencil :size="13" /></RouterLink>
                  <RouterLink :to="`/pages/${row.id}/builder`" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Open visual builder"><ExternalLink :size="13" /></RouterLink>
                  <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Duplicate" @click="duplicate(row)"><Copy :size="13" /></button>
                  <button v-if="auth.can('pages.delete') && !row.is_home" type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Delete" @click="deleteTarget = row"><Trash2 :size="13" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta && meta.last_page > 1" class="pager">
        <span class="mono muted">{{ meta.total }} pages · page {{ meta.current_page }} of {{ meta.last_page }}</span>
        <div class="flex-s">
          <button type="button" class="btn-s btn-s--sm" :disabled="meta.current_page <= 1" @click="router.replace({ query: { ...route.query, page: String(meta.current_page - 1) } })"><ChevronLeft :size="13" /></button>
          <button type="button" class="btn-s btn-s--sm" :disabled="meta.current_page >= meta.last_page" @click="router.replace({ query: { ...route.query, page: String(meta.current_page + 1) } })"><ChevronRight :size="13" /></button>
        </div>
      </div>
    </div>

    <BaseModal :open="createOpen" title="Create a page" @close="createOpen = false">
      <div class="form-grid-s">
        <div class="field-s" style="grid-column: 1 / -1">
          <label class="field-s__label" for="new-page-title">Page title</label>
          <input id="new-page-title" v-model="createForm.title" class="input-s" placeholder="e.g. Industrial outlook 2027" @input="!createForm.slug && (createForm.slug = createForm.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''))">
          <p v-if="createErrors.title" class="error-s">{{ createErrors.title }}</p>
        </div>
        <div class="field-s">
          <label class="field-s__label" for="new-page-slug">Slug</label>
          <input id="new-page-slug" v-model="createForm.slug" class="input-s" placeholder="industrial-outlook-2027">
          <p v-if="createErrors.slug" class="error-s">{{ createErrors.slug }}</p>
        </div>
        <div class="field-s">
          <label class="field-s__label" for="new-page-locale">Locale</label>
          <select id="new-page-locale" v-model="createForm.locale" class="select-s">
            <option value="en">English</option>
            <option value="th">ไทย</option>
            <option value="zh-CN">简体中文</option>
          </select>
        </div>
        <div class="field-s">
          <label class="field-s__label" for="new-page-template">Template</label>
          <select id="new-page-template" v-model="createForm.template" class="select-s">
            <option value="default">Default</option>
            <option value="editorial">Editorial</option>
            <option value="home">Home</option>
            <option value="contact">Contact</option>
            <option value="legal">Legal</option>
          </select>
        </div>
        <div class="field-s" style="grid-column: 1 / -1">
          <label class="field-s__label" for="new-page-excerpt">Excerpt</label>
          <textarea id="new-page-excerpt" v-model="createForm.excerpt" class="textarea-s" rows="3" placeholder="A short summary used in listings and as a fallback meta description." />
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="createOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !createForm.title" @click="createPage">{{ saving ? 'Creating…' : 'Create page' }}</button>
      </template>
    </BaseModal>

    <BaseModal :open="Boolean(deleteTarget)" title="Delete page" @close="deleteTarget = null">
      <p style="font-size: 12px; line-height: 1.6">Delete <strong>{{ deleteTarget?.title }}</strong>? This removes the page and its revisions from the website. It cannot be undone.</p>
      <template #footer>
        <button type="button" class="btn-s" @click="deleteTarget = null">Cancel</button>
        <button type="button" class="btn-s btn-s--danger" :disabled="saving" @click="removePage">{{ saving ? 'Deleting…' : 'Delete page' }}</button>
      </template>
    </BaseModal>

    <BaseModal :open="Boolean(bulkConfirm)" :title="`Bulk ${bulkConfirm || ''}`" @close="bulkConfirm = null">
      <p style="font-size: 12px; line-height: 1.6">Apply <strong>{{ bulkConfirm }}</strong> to {{ selected.length }} selected page(s)? Pages that are in review or change-requested may be skipped.</p>
      <template #footer>
        <button type="button" class="btn-s" @click="bulkConfirm = null">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="runBulk">{{ saving ? 'Working…' : 'Confirm' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.bulk-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: var(--s-ink);
  color: #f5f3ee;
  border-radius: var(--s-radius);
  padding: 8px 12px;
  margin-bottom: 10px;
  font-size: 12px;
}

.locale-row {
  display: flex;
  gap: 3px;
}

.locale-pill {
  font-family: var(--font-mono);
  font-size: 8px;
  border: 1px solid var(--s-line);
  color: var(--s-faint);
  padding: 2px 4px;
  border-radius: 2px;
}

.locale-pill.is-present {
  border-color: var(--s-line-strong);
  color: var(--s-ink);
  background: var(--s-bg);
}

.pager {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  border-top: 1px solid var(--s-line);
}

.form-grid-s {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

@media (max-width: 600px) {
  .form-grid-s { grid-template-columns: 1fr; }
}
</style>
