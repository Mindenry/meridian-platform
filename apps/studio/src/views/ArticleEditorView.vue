<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save, Eye, Image as ImageIcon, ExternalLink, Clock3, FileText } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import MediaPicker from '@/components/MediaPicker.vue'
import { api, apiErrorMessage, apiValidationErrors } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'
import type { MediaAsset } from '@/types'

const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))
const locale = ref(String(route.query.locale || 'en'))
const loading = ref(true)
const error = ref('')
const article = ref<any>(null)
const categories = ref<any[]>([])
const tab = ref<'content' | 'seo' | 'history'>('content')
const saving = ref(false)
const pickerOpen = ref(false)
const errors = ref<Record<string, string>>({})
const savedSnapshot = ref('')

const form = ref({
  title: '',
  slug: '',
  excerpt: '',
  type: 'insight',
  status: 'draft',
  category_id: null as number | null,
  featured_media_id: null as number | null,
  tags: '',
  body: null as any,
  seo: {
    title: '',
    description: '',
    canonical: '',
    focus_keyword: '',
    og_title: '',
    og_description: '',
    robots: 'index,follow',
  },
})

const canPublish = computed(() => auth.can('articles.publish'))
const isPublished = computed(() => article.value?.status === 'published')

function textFromDoc(node: any): string {
  if (!node) return ''
  if (node.text) return node.text
  if (Array.isArray(node.content)) return node.content.map(textFromDoc).join(' ')
  return ''
}

const plainText = computed(() => textFromDoc(form.value.body))
const wordCount = computed(() => plainText.value.trim() ? plainText.value.trim().split(/\s+/).length : 0)
const readingTime = computed(() => Math.max(1, Math.ceil(wordCount.value / 220)))

function snapshot() {
  return JSON.stringify(form.value)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [articleResponse, categoriesResponse] = await Promise.all([
      api.get(`/articles/${id.value}`, { params: { locale: locale.value } }),
      api.get('/articles/categories'),
    ])
    article.value = articleResponse.data.data
    categories.value = categoriesResponse.data.data
    const data = article.value
    form.value = {
      title: data.title || '',
      slug: data.slug || '',
      excerpt: data.excerpt || '',
      type: data.type || 'insight',
      status: data.status || 'draft',
      category_id: data.category?.id || null,
      featured_media_id: data.featured_media?.id || null,
      tags: (data.tags || []).map((tag: any) => tag.name).join(', '),
      body: data.body || { type: 'doc', content: [] },
      seo: {
        title: data.seo?.title || '',
        description: data.seo?.description || '',
        canonical: data.seo?.canonical || '',
        focus_keyword: data.seo?.focus_keyword || '',
        og_title: data.seo?.og_title || '',
        og_description: data.seo?.og_description || '',
        robots: data.seo?.robots || 'index,follow',
      },
    }
    savedSnapshot.value = snapshot()
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      ...form.value,
      locale: locale.value,
      tags: form.value.tags.split(',').map((tag) => tag.trim()).filter(Boolean),
      seo: form.value.seo,
    }
    const response = await api.patch(`/articles/${id.value}`, payload)
    article.value = response.data.data
    savedSnapshot.value = snapshot()
    toast.success('Article saved')
  } catch (err) {
    errors.value = apiValidationErrors(err)
    toast.error('Could not save article', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function selectImage(asset: MediaAsset) {
  form.value.featured_media_id = asset.id
  pickerOpen.value = false
  article.value.featured_media = { id: asset.id, url: asset.url, alt: asset.alt?.en }
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

onMounted(load)
</script>

<template>
  <div>
    <div v-if="loading" class="skeleton" style="height:420px" />
    <div v-else-if="error" class="empty-s">
      <p class="empty-s__title">Article unavailable</p>
      <p class="empty-s__body">{{ error }}</p>
      <button type="button" class="btn-s btn-s--sm" style="margin-top:10px" @click="router.push('/content/articles')">Back to articles</button>
    </div>

    <template v-else-if="article">
      <PageHead eyebrow="ARTICLE EDITOR" :title="article.title || 'Untitled article'" :description="`${article.type} · ${article.status} · ${locale.toUpperCase()}`">
        <template #actions>
          <button type="button" class="btn-s btn-s--ghost" @click="router.push('/content/articles')"><ArrowLeft :size="13" /> Back</button>
          <a v-if="isPublished" :href="`http://localhost:3002/${locale === 'th' ? 'th' : locale === 'zh-CN' ? 'zh-cn' : 'en'}/insights/${article.slug}`" target="_blank" class="btn-s"><ExternalLink :size="13" /> View live</a>
          <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="save"><Save :size="13" /> {{ saving ? 'Saving…' : 'Save article' }}</button>
        </template>
      </PageHead>

      <div class="editor-tabs">
        <button type="button" :class="{ 'is-active': tab === 'content' }" @click="tab = 'content'">Content</button>
        <button type="button" :class="{ 'is-active': tab === 'seo' }" @click="tab = 'seo'">SEO</button>
        <button type="button" :class="{ 'is-active': tab === 'history' }" @click="tab = 'history'">History</button>
      </div>

      <div v-if="tab === 'content'" class="article-layout">
        <div class="stack-s">
          <div class="panel">
            <div class="panel__body stack-s">
              <div class="field-s">
                <label class="field-s__label" for="article-title">Title</label>
                <input id="article-title" v-model="form.title" class="input-s" style="height:44px;font-size:16px;font-weight:600">
                <p v-if="errors.title" class="error-s">{{ errors.title }}</p>
              </div>
              <div class="article-fields">
                <div class="field-s">
                  <label class="field-s__label" for="article-slug">Slug</label>
                  <input id="article-slug" v-model="form.slug" class="input-s">
                  <p v-if="errors.slug" class="error-s">{{ errors.slug }}</p>
                </div>
                <div class="field-s">
                  <label class="field-s__label" for="article-type">Type</label>
                  <select id="article-type" v-model="form.type" class="select-s">
                    <option value="insight">Insight</option><option value="news">News</option><option value="technical">Technical</option><option value="announcement">Announcement</option><option value="report">Report</option>
                  </select>
                </div>
                <div class="field-s">
                  <label class="field-s__label" for="article-status">Status</label>
                  <select id="article-status" v-model="form.status" class="select-s">
                    <option value="draft">Draft</option>
                    <option v-if="canPublish" value="published">Published</option>
                    <option value="archived">Archived</option>
                  </select>
                </div>
                <div class="field-s">
                  <label class="field-s__label" for="article-category">Category</label>
                  <select id="article-category" v-model="form.category_id" class="select-s">
                    <option :value="null">Uncategorised</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name?.en || category.slug }}</option>
                  </select>
                </div>
              </div>
              <div class="field-s">
                <label class="field-s__label" for="article-excerpt">Excerpt</label>
                <textarea id="article-excerpt" v-model="form.excerpt" class="textarea-s" rows="3" />
              </div>
              <div class="field-s">
                <label class="field-s__label" for="article-tags">Tags</label>
                <input id="article-tags" v-model="form.tags" class="input-s" placeholder="automation, data, operations">
              </div>
            </div>
          </div>

          <div>
            <div class="flex-between" style="margin-bottom:6px">
              <span class="mono muted">BODY</span>
              <span class="mono faint">{{ wordCount }} words · {{ readingTime }} min read</span>
            </div>
            <RichTextEditor v-model="form.body" format="json" placeholder="Write the article…" :min-height="'520px'" />
          </div>
        </div>

        <aside class="article-side stack-s">
          <section class="panel">
            <div class="panel__head"><p class="panel__title">Publishing</p><StatusBadge :status="article.status" /></div>
            <div class="panel__body stack-s">
              <div class="meta-row"><span>Published</span><strong>{{ formatDate(article.published_at) }}</strong></div>
              <div class="meta-row"><span>Views</span><strong>{{ article.view_count }}</strong></div>
              <div class="meta-row"><span>Reading time</span><strong>{{ readingTime }} min</strong></div>
              <div class="meta-row"><span>Locales</span><strong>{{ article.translations?.map((t: any) => t.locale).join(', ') }}</strong></div>
            </div>
          </section>
          <section class="panel">
            <div class="panel__head"><p class="panel__title">Featured image</p></div>
            <div class="panel__body">
              <button type="button" class="featured" @click="pickerOpen = true">
                <img v-if="article.featured_media?.url" :src="article.featured_media.url" alt="">
                <span v-else><ImageIcon :size="18" /> Choose image</span>
              </button>
            </div>
          </section>
          <section class="panel">
            <div class="panel__head"><p class="panel__title">Checks</p></div>
            <div class="panel__body checks">
              <p :class="form.title.length >= 20 ? 'ok' : 'warn'">{{ form.title.length >= 20 ? 'Title is descriptive' : 'Title is short' }}</p>
              <p :class="form.excerpt.length >= 70 ? 'ok' : 'warn'">{{ form.excerpt.length >= 70 ? 'Excerpt is substantial' : 'Excerpt is short' }}</p>
              <p :class="wordCount >= 200 ? 'ok' : 'warn'">{{ wordCount >= 200 ? 'Body has enough content' : 'Body may be thin' }}</p>
              <p :class="article.featured_media ? 'ok' : 'warn'">{{ article.featured_media ? 'Featured image present' : 'No featured image' }}</p>
            </div>
          </section>
        </aside>
      </div>

      <div v-if="tab === 'seo'" class="panel">
        <div class="panel__body seo-layout">
          <div class="stack-s">
            <div class="field-s">
              <label class="field-s__label" for="article-seo-title">SEO title</label>
              <input id="article-seo-title" v-model="form.seo.title" class="input-s" placeholder="Leave blank to use the article title">
            </div>
            <div class="field-s">
              <label class="field-s__label" for="article-seo-description">Meta description</label>
              <textarea id="article-seo-description" v-model="form.seo.description" class="textarea-s" rows="3" />
            </div>
            <div class="field-s">
              <label class="field-s__label" for="article-seo-keyword">Focus keyword</label>
              <input id="article-seo-keyword" v-model="form.seo.focus_keyword" class="input-s">
            </div>
            <div class="field-s">
              <label class="field-s__label" for="article-seo-canonical">Canonical URL</label>
              <input id="article-seo-canonical" v-model="form.seo.canonical" class="input-s">
            </div>
            <div class="field-s">
              <label class="field-s__label" for="article-seo-robots">Robots</label>
              <select id="article-seo-robots" v-model="form.seo.robots" class="select-s">
                <option value="index,follow">index, follow</option><option value="noindex,follow">noindex, follow</option>
              </select>
            </div>
          </div>
          <aside class="serp-card">
            <p class="mono muted">SEARCH PREVIEW</p>
            <p class="serp-url">meridian.example/{{ locale === 'th' ? 'th' : locale === 'zh-CN' ? 'zh-cn' : 'en' }}/insights/{{ form.slug }}</p>
            <p class="serp-title">{{ form.seo.title || form.title }}</p>
            <p class="serp-desc">{{ form.seo.description || form.excerpt }}</p>
          </aside>
        </div>
      </div>

      <div v-if="tab === 'history'" class="panel">
        <div class="panel__body">
          <div v-for="revision in article.revisions || []" :key="revision.id" class="revision-row">
            <span class="mono">v{{ revision.version }}</span>
            <div><p class="revision-title">{{ revision.message }}</p><p class="mono faint">{{ revision.user }} · {{ formatDate(revision.created_at) }}</p></div>
          </div>
          <p v-if="!article.revisions?.length" class="muted" style="font-size: 12px">No revisions captured yet.</p>
        </div>
      </div>

      <MediaPicker :open="pickerOpen" title="Choose featured image" @close="pickerOpen = false" @select="selectImage" />
    </template>
  </div>
</template>

<style scoped>
.article-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: 14px;
  align-items: start;
}

.article-side { position: sticky; top: calc(var(--s-topbar) + 14px); }

.article-fields {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
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

.editor-tabs button.is-active { color: var(--s-ink); border-bottom-color: var(--s-ink); font-weight: 650; }

.meta-row { display: flex; justify-content: space-between; gap: 10px; font-size: 12px; color: var(--s-muted); border-bottom: 1px solid var(--s-line); padding-bottom: 7px; }
.meta-row strong { color: var(--s-ink); font-size: 12px; text-align: right; }

.featured {
  width: 100%;
  border: 1px solid var(--s-line);
  background: var(--s-white);
  border-radius: var(--s-radius);
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: var(--s-faint);
  font-size: 12px;
  overflow: hidden;
}

.featured img { width: 100%; height: 100%; object-fit: cover; }

.checks { display: flex; flex-direction: column; gap: 5px; font-size: 12px; }
.checks .ok::before { content: "✓"; color: var(--s-green); margin-right: 5px; }
.checks .warn::before { content: "!"; color: var(--s-amber); margin-right: 5px; }

.seo-layout { display: grid; grid-template-columns: 1fr 300px; gap: 24px; }
.serp-card { border-left: 1px solid var(--s-line); padding-left: 20px; }
.serp-url { font-size: 12px; color: var(--s-muted); margin-top: 10px; }
.serp-title { font-size: 15px; color: #1a0dab; margin-top: 4px; }
.serp-desc { font-size: 12px; color: var(--s-muted); line-height: 1.5; margin-top: 4px; }

.revision-row { display: grid; grid-template-columns: 40px 1fr; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--s-line); }
.revision-title { font-size: 12px; font-weight: 600; }

@media (max-width: 1100px) {
  .article-layout { grid-template-columns: 1fr; }
  .article-side { position: static; }
  .article-fields { grid-template-columns: 1fr 1fr; }
  .seo-layout { grid-template-columns: 1fr; }
  .serp-card { border-left: 0; border-top: 1px solid var(--s-line); padding-left: 0; padding-top: 16px; }
}

@media (max-width: 560px) {
  .article-fields { grid-template-columns: 1fr; }
}
</style>
