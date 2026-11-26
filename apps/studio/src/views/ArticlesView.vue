<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Search, Pencil, ExternalLink, Newspaper, Filter } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage, apiValidationErrors } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'
import type { PaginationMeta } from '@/types'

const router = useRouter()
const loading = ref(true)
const rows = ref<any[]>([])
const meta = ref<PaginationMeta | null>(null)
const categories = ref<any[]>([])
const search = ref('')
const status = ref('')
const type = ref('')
const createOpen = ref(false)
const saving = ref(false)
const form = ref({ title: '', slug: '', locale: 'en', type: 'insight' })
const errors = ref<Record<string, string>>({})

const typeLabel = (value: string) => ({
  insight: 'Insight',
  news: 'News',
  technical: 'Technical',
  announcement: 'Announcement',
  report: 'Report',
}[value] || value)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/articles', { params: { q: search.value || undefined, status: status.value || undefined, type: type.value || undefined, per_page: 20 } })
    rows.value = data.data
    meta.value = data.meta
    categories.value = data.filters.categories
  } finally {
    loading.value = false
  }
}

const debounced = (() => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(load, 350)
  }
})()

async function create() {
  saving.value = true
  errors.value = {}
  try {
    const response = await api.post('/articles', {
      ...form.value,
      excerpt: '',
      body: { type: 'doc', content: [] },
      status: 'draft',
    })
    toast.success('Article created')
    createOpen.value = false
    await router.push(`/content/articles/${response.data.data.id}?locale=${form.value.locale}`)
  } catch (err) {
    errors.value = apiValidationErrors(err)
    toast.error('Could not create article', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value))
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="CONTENT" title="Insights & articles" description="Editorial work across insights, news, technical notes, announcements, and reports.">
      <template #actions>
        <RouterLink to="/content/case-studies" class="btn-s">Case studies</RouterLink>
        <button v-if="auth.can('articles.create')" type="button" class="btn-s btn-s--primary" @click="createOpen = true"><Plus :size="14" /> New article</button>
      </template>
    </PageHead>

    <div class="toolbar">
      <div class="toolbar__search">
        <Search :size="13" />
        <input v-model="search" class="input-s" placeholder="Search articles" @input="debounced">
      </div>
      <select v-model="status" class="select-s" style="width:150px" @change="load">
        <option value="">All statuses</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
        <option value="archived">Archived</option>
      </select>
      <select v-model="type" class="select-s" style="width:150px" @change="load">
        <option value="">All types</option>
        <option value="insight">Insight</option>
        <option value="news">News</option>
        <option value="technical">Technical</option>
        <option value="announcement">Announcement</option>
        <option value="report">Report</option>
      </select>
      <span class="mono faint">{{ meta?.total ?? 0 }} articles</span>
    </div>

    <div class="panel">
      <div v-if="loading" class="panel__body">
        <div v-for="i in 6" :key="i" class="skeleton" style="height:42px;margin-bottom:8px" />
      </div>
      <EmptyState v-else-if="!rows.length" title="No articles match this view" body="Clear the filters or write the first article." action-label="New article" @action="createOpen = true" />
      <div v-else class="table-wrap">
        <table class="table-s">
          <thead><tr><th>Article</th><th>Type</th><th>Status</th><th>Category</th><th>Author</th><th>Published</th><th style="text-align:right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="article in rows" :key="article.id">
              <td>
                <RouterLink :to="`/content/articles/${article.id}`" class="table-s__title">{{ article.title || 'Untitled' }}</RouterLink>
                <div class="table-s__sub">{{ article.reading_time }} min read · {{ article.locales.join(', ') }}</div>
              </td>
              <td><span class="badge badge--neutral">{{ typeLabel(article.type) }}</span></td>
              <td><StatusBadge :status="article.status" /></td>
              <td class="muted">{{ article.category || '—' }}</td>
              <td class="muted">{{ article.author || '—' }}</td>
              <td class="muted nowrap">{{ formatDate(article.published_at) }}</td>
              <td>
                <div class="table-s__actions">
                  <RouterLink :to="`/content/articles/${article.id}`" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Edit"><Pencil :size="13" /></RouterLink>
                  <a v-if="article.status === 'published'" :href="`http://localhost:3002/${article.locales.includes('th') ? 'th' : 'en'}/insights/${article.slug}`" target="_blank" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="View public article"><ExternalLink :size="13" /></a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="meta && meta.last_page > 1" class="flex-between" style="padding:10px 14px;border-top:1px solid var(--s-line)">
        <span class="mono muted">{{ meta.total }} articles</span>
        <span class="mono faint">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      </div>
    </div>

    <BaseModal :open="createOpen" title="New article" @close="createOpen = false">
      <div class="stack-s">
        <div class="field-s">
          <label class="field-s__label" for="art-title">Title</label>
          <input id="art-title" v-model="form.title" class="input-s" @input="!form.slug && (form.slug = form.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''))">
          <p v-if="errors.title" class="error-s">{{ errors.title }}</p>
        </div>
        <div class="two-fields-s">
          <div class="field-s">
            <label class="field-s__label" for="art-slug">Slug</label>
            <input id="art-slug" v-model="form.slug" class="input-s">
            <p v-if="errors.slug" class="error-s">{{ errors.slug }}</p>
          </div>
          <div class="field-s">
            <label class="field-s__label" for="art-locale">Locale</label>
            <select id="art-locale" v-model="form.locale" class="select-s"><option value="en">English</option><option value="th">ไทย</option><option value="zh-CN">简体中文</option></select>
          </div>
        </div>
        <div class="field-s">
          <label class="field-s__label" for="art-type">Article type</label>
          <select id="art-type" v-model="form.type" class="select-s">
            <option value="insight">Insight</option><option value="news">News</option><option value="technical">Technical</option><option value="announcement">Announcement</option><option value="report">Report</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="createOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !form.title" @click="create">{{ saving ? 'Creating…' : 'Create article' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.two-fields-s { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (max-width: 560px) { .two-fields-s { grid-template-columns: 1fr; } }
</style>
