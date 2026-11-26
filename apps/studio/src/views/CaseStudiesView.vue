<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Search, Pencil, FolderKanban } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const router = useRouter()
const loading = ref(true)
const rows = ref<any[]>([])
const search = ref('')
const status = ref('')
const createOpen = ref(false)
const saving = ref(false)
const form = ref({ title: '', slug: '', locale: 'en', year: new Date().getFullYear() })

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/case-studies', { params: { q: search.value || undefined, status: status.value || undefined, per_page: 30 } })
    rows.value = data.data
  } finally {
    loading.value = false
  }
}

const debounced = (() => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(load, 320)
  }
})()

async function create() {
  saving.value = true
  try {
    const response = await api.post('/case-studies', {
      ...form.value,
      summary: 'Add a summary in the editor.',
      challenge: 'Add the challenge in the editor.',
      strategy: 'Add the strategy in the editor.',
      implementation: 'Add the implementation in the editor.',
      results: [],
      status: 'draft',
    })
    toast.success('Case study created')
    createOpen.value = false
    await router.push(`/content/case-studies/${response.data.data.id}?locale=${form.value.locale}`)
  } catch (err) {
    toast.error('Could not create case study', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="CONTENT" title="Case studies" description="Illustrative portfolio projects with a challenge, strategy, implementation, and results.">
      <template #actions>
        <RouterLink to="/content/articles" class="btn-s">Insights</RouterLink>
        <button v-if="auth.can('case_studies.manage')" type="button" class="btn-s btn-s--primary" @click="createOpen = true"><Plus :size="14" /> New case study</button>
      </template>
    </PageHead>

    <div class="toolbar">
      <div class="toolbar__search"><Search :size="13" /><input v-model="search" class="input-s" placeholder="Search case studies" @input="debounced"></div>
      <select v-model="status" class="select-s" style="width:150px" @change="load">
        <option value="">All statuses</option><option value="published">Published</option><option value="draft">Draft</option><option value="archived">Archived</option>
      </select>
      <div class="toolbar__spacer" /><span class="mono faint">{{ rows.length }} case studies</span>
    </div>

    <div class="panel">
      <div v-if="loading" class="panel__body"><div v-for="i in 6" :key="i" class="skeleton" style="height:42px;margin-bottom:8px" /></div>
      <EmptyState v-else-if="!rows.length" title="No case studies" body="Create the first portfolio project." action-label="New case study" @action="createOpen = true" />
      <div v-else class="table-wrap">
        <table class="table-s">
          <thead><tr><th>Project</th><th>Year</th><th>Industry</th><th>Status</th><th>Locales</th><th>Updated</th><th /></tr></thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td><RouterLink :to="`/content/case-studies/${row.id}`" class="table-s__title">{{ row.title || 'Untitled' }} <span v-if="row.featured" class="badge badge--accent">FEATURED</span></RouterLink><div class="table-s__sub">/{{ row.slug }}</div></td>
              <td>{{ row.year }}</td>
              <td class="muted">{{ row.industry || '—' }}</td>
              <td><StatusBadge :status="row.status" /></td>
              <td class="mono faint">{{ row.locales.join(', ') }}</td>
              <td class="muted nowrap">{{ row.updated_at ? new Date(row.updated_at).toLocaleDateString('en-GB') : '—' }}</td>
              <td><RouterLink :to="`/content/case-studies/${row.id}`" class="btn-s btn-s--ghost btn-s--icon btn-s--sm"><Pencil :size="13" /></RouterLink></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <BaseModal :open="createOpen" title="New case study" @close="createOpen = false">
      <div class="stack-s">
        <div class="field-s"><label class="field-s__label">Title</label><input v-model="form.title" class="input-s" @input="!form.slug && (form.slug = form.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''))"></div>
        <div class="two-fields-s">
          <div class="field-s"><label class="field-s__label">Slug</label><input v-model="form.slug" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Year</label><input v-model.number="form.year" type="number" class="input-s"></div>
        </div>
        <div class="field-s"><label class="field-s__label">Locale</label><select v-model="form.locale" class="select-s"><option value="en">English</option><option value="th">ไทย</option></select></div>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="createOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !form.title" @click="create">{{ saving ? 'Creating…' : 'Create case study' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.two-fields-s { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
</style>
