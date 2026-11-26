<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save, Plus, Trash2, Image as ImageIcon, ExternalLink } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
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
const saving = ref(false)
const study = ref<any>(null)
const industries = ref<any[]>([])
const solutions = ref<any[]>([])
const pickerOpen = ref(false)
const errors = ref<Record<string, string>>({})
const tab = ref<'content' | 'seo'>('content')

const form = ref({
  title: '',
  slug: '',
  summary: '',
  challenge: '',
  strategy: '',
  implementation: '',
  year: new Date().getFullYear(),
  client_label: '',
  industry_id: null as number | null,
  featured_media_id: null as number | null,
  status: 'draft',
  is_featured: false,
  solution_ids: [] as number[],
  results: [] as Array<{ label: string; value: string; note?: string }>,
  seo: { title: '', description: '', canonical: '', focus_keyword: '', robots: 'index,follow' },
})

const canManage = computed(() => auth.can('case_studies.manage'))

async function load() {
  loading.value = true
  try {
    const [studyResponse, industryResponse, solutionResponse] = await Promise.all([
      api.get(`/case-studies/${id.value}`, { params: { locale: locale.value } }),
      api.get('/industries'),
      api.get('/solutions'),
    ])
    study.value = studyResponse.data.data
    industries.value = industryResponse.data.data
    solutions.value = solutionResponse.data.data
    const data = study.value
    form.value = {
      title: data.title || '',
      slug: data.slug || '',
      summary: data.summary || '',
      challenge: data.challenge || '',
      strategy: data.strategy || '',
      implementation: data.implementation || '',
      year: data.year,
      client_label: data.client_label || '',
      industry_id: data.industry?.id || null,
      featured_media_id: data.featured_media?.id || null,
      status: data.status,
      is_featured: data.is_featured,
      solution_ids: (data.solutions || []).map((solution: any) => solution.id),
      results: JSON.parse(JSON.stringify(data.results || [])),
      seo: {
        title: data.seo?.title || '',
        description: data.seo?.description || '',
        canonical: data.seo?.canonical || '',
        focus_keyword: data.seo?.focus_keyword || '',
        robots: data.seo?.robots || 'index,follow',
      },
    }
  } catch (err) {
    toast.error('Could not load case study', apiErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    await api.patch(`/case-studies/${id.value}`, { ...form.value, locale: locale.value })
    toast.success('Case study saved')
    await load()
  } catch (err) {
    errors.value = apiValidationErrors(err)
    toast.error('Could not save case study', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function addResult() {
  form.value.results.push({ label: '', value: '', note: 'Illustrative outcome' })
}

function selectImage(asset: MediaAsset) {
  form.value.featured_media_id = asset.id
  study.value.featured_media = { id: asset.id, url: asset.url, alt: asset.alt?.en }
  pickerOpen.value = false
}

function toggleSolution(solutionId: number) {
  form.value.solution_ids = form.value.solution_ids.includes(solutionId)
    ? form.value.solution_ids.filter((item) => item !== solutionId)
    : [...form.value.solution_ids, solutionId]
}

onMounted(load)
</script>

<template>
  <div>
    <div v-if="loading" class="skeleton" style="height:420px" />
    <template v-else-if="study">
      <PageHead eyebrow="CASE STUDY" :title="study.title || 'Untitled project'" :description="`${study.year} · ${study.status} · ${locale.toUpperCase()}`">
        <template #actions>
          <button type="button" class="btn-s btn-s--ghost" @click="router.push('/content/case-studies')"><ArrowLeft :size="13" /> Back</button>
          <a v-if="study.status === 'published'" :href="`http://localhost:3002/${locale === 'th' ? 'th' : 'en'}/case-studies/${study.slug}`" target="_blank" class="btn-s"><ExternalLink :size="13" /> View live</a>
          <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="save"><Save :size="13" /> {{ saving ? 'Saving…' : 'Save project' }}</button>
        </template>
      </PageHead>

      <div class="editor-tabs">
        <button type="button" :class="{ 'is-active': tab === 'content' }" @click="tab = 'content'">Content</button>
        <button type="button" :class="{ 'is-active': tab === 'seo' }" @click="tab = 'seo'">SEO</button>
      </div>

      <div v-if="tab === 'content'" class="case-layout">
        <div class="stack-s">
          <section class="panel"><div class="panel__body stack-s">
            <div class="field-s"><label class="field-s__label">Title</label><input v-model="form.title" class="input-s" style="font-size:16px;font-weight:600;height:44px"></div>
            <div class="two-fields-s">
              <div class="field-s"><label class="field-s__label">Slug</label><input v-model="form.slug" class="input-s"></div>
              <div class="field-s"><label class="field-s__label">Year</label><input v-model.number="form.year" type="number" class="input-s"></div>
              <div class="field-s"><label class="field-s__label">Status</label><select v-model="form.status" class="select-s"><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></div>
              <div class="field-s"><label class="field-s__label">Client label</label><input v-model="form.client_label" class="input-s" placeholder="Illustrative portfolio project"></div>
            </div>
            <div class="field-s"><label class="field-s__label">Summary</label><textarea v-model="form.summary" class="textarea-s" rows="3" /></div>
            <div class="field-s"><label class="field-s__label">Challenge</label><textarea v-model="form.challenge" class="textarea-s" rows="5" /></div>
            <div class="field-s"><label class="field-s__label">Strategy</label><textarea v-model="form.strategy" class="textarea-s" rows="5" /></div>
            <div class="field-s"><label class="field-s__label">Implementation</label><textarea v-model="form.implementation" class="textarea-s" rows="5" /></div>
          </div></section>

          <section class="panel"><div class="panel__body">
            <div class="flex-between"><p class="panel__title">Results</p><button type="button" class="btn-s btn-s--sm" @click="addResult"><Plus :size="12" /> Add result</button></div>
            <div v-for="(result, index) in form.results" :key="index" class="result-row">
              <input v-model="result.value" class="input-s" placeholder="38%">
              <input v-model="result.label" class="input-s" placeholder="Less unplanned downtime">
              <input v-model="result.note" class="input-s" placeholder="Illustrative outcome">
              <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="form.results.splice(index, 1)"><Trash2 :size="12" /></button>
            </div>
          </div></section>
        </div>

        <aside class="case-side stack-s">
          <section class="panel"><div class="panel__head"><p class="panel__title">Project</p><StatusBadge :status="study.status" /></div><div class="panel__body stack-s">
            <div class="field-s"><label class="field-s__label">Industry</label><select v-model="form.industry_id" class="select-s"><option :value="null">None</option><option v-for="industry in industries" :key="industry.id" :value="industry.id">{{ industry.title }}</option></select></div>
            <label class="checkbox-s"><input v-model="form.is_featured" type="checkbox"><span>Featured project</span></label>
            <div class="field-s"><label class="field-s__label">Featured image</label><button type="button" class="featured" @click="pickerOpen = true"><img v-if="study.featured_media?.url" :src="study.featured_media.url" alt=""><span v-else><ImageIcon :size="16" /> Choose image</span></button></div>
          </div></section>
          <section class="panel"><div class="panel__head"><p class="panel__title">Solutions applied</p></div><div class="panel__body solution-list">
            <label v-for="solution in solutions" :key="solution.id" class="checkbox-s"><input type="checkbox" :checked="form.solution_ids.includes(solution.id)" @change="toggleSolution(solution.id)"><span>{{ solution.title }}</span></label>
          </div></section>
        </aside>
      </div>

      <div v-else class="panel"><div class="panel__body stack-s">
        <div class="field-s"><label class="field-s__label">SEO title</label><input v-model="form.seo.title" class="input-s"></div>
        <div class="field-s"><label class="field-s__label">Meta description</label><textarea v-model="form.seo.description" class="textarea-s" rows="3" /></div>
        <div class="field-s"><label class="field-s__label">Canonical URL</label><input v-model="form.seo.canonical" class="input-s"></div>
        <div class="field-s"><label class="field-s__label">Focus keyword</label><input v-model="form.seo.focus_keyword" class="input-s"></div>
      </div></div>

      <MediaPicker :open="pickerOpen" title="Choose featured image" @close="pickerOpen = false" @select="selectImage" />
    </template>
  </div>
</template>

<style scoped>
.case-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 14px; align-items: start; }
.case-side { position: sticky; top: calc(var(--s-topbar) + 14px); }
.two-fields-s { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.result-row { display: grid; grid-template-columns: 70px 1fr 1fr 30px; gap: 6px; margin-top: 7px; }
.featured { width: 100%; min-height: 120px; border: 1px solid var(--s-line); background: var(--s-white); border-radius: var(--s-radius); display: flex; align-items: center; justify-content: center; gap: 6px; color: var(--s-faint); font-size: 12px; overflow: hidden; }
.featured img { width: 100%; height: 100%; object-fit: cover; }
.solution-list { display: flex; flex-direction: column; gap: 7px; }
@media (max-width: 1000px) { .case-layout { grid-template-columns: 1fr; } .case-side { position: static; } }
</style>
