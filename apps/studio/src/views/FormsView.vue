<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Plus, Download, Search, Inbox, FileText, Settings2, Trash2, ChevronUp, ChevronDown } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import BaseDrawer from '@/components/BaseDrawer.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const loading = ref(true)
const tab = ref<'forms' | 'submissions'>('submissions')
const forms = ref<any[]>([])
const submissions = ref<any[]>([])
const meta = ref<any>(null)
const search = ref('')
const formFilter = ref('')
const statusFilter = ref('')
const detailOpen = ref(false)
const selectedSubmission = ref<any>(null)
const formEditorOpen = ref(false)
const selectedForm = ref<any>(null)
const formEdit = ref({ name: '', description: '', is_active: true, fields: [] as any[] })
const saving = ref(false)

const fieldTypes = ['text', 'textarea', 'email', 'tel', 'phone', 'number', 'select', 'radio', 'checkbox', 'date', 'file', 'hidden', 'consent']

async function loadForms() {
  const { data } = await api.get('/forms')
  forms.value = data.data
}

async function loadSubmissions() {
  const { data } = await api.get('/submissions', { params: { q: search.value || undefined, form_id: formFilter.value || undefined, status: statusFilter.value || undefined, per_page: 30 } })
  submissions.value = data.data
  meta.value = data.meta
}

async function load() {
  loading.value = true
  try {
    await Promise.all([loadForms(), loadSubmissions()])
  } finally {
    loading.value = false
  }
}

const debounced = (() => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(loadSubmissions, 320)
  }
})()

async function openForm(form: any) {
  const { data } = await api.get(`/forms/${form.id}`)
  selectedForm.value = data.data
  formEdit.value = {
    name: data.data.name,
    description: data.data.description || '',
    is_active: data.data.is_active,
    fields: JSON.parse(JSON.stringify(data.data.schema?.fields || [])),
  }
  formEditorOpen.value = true
}

function addField(type = 'text') {
  formEdit.value.fields.push({
    name: `field_${formEdit.value.fields.length + 1}`,
    type,
    label: { en: 'New field', th: 'ฟิลด์ใหม่', 'zh-CN': '新字段' },
    placeholder: { en: '', th: '', 'zh-CN': '' },
    required: false,
    options: [],
    width: type === 'textarea' || type === 'consent' ? 'full' : 'half',
  })
}

function removeField(index: number) {
  formEdit.value.fields.splice(index, 1)
}

function moveField(index: number, direction: -1 | 1) {
  const target = index + direction
  if (target < 0 || target >= formEdit.value.fields.length) return
  const [item] = formEdit.value.fields.splice(index, 1)
  formEdit.value.fields.splice(target, 0, item)
}

async function saveForm() {
  if (!selectedForm.value) return
  saving.value = true
  try {
    await api.patch(`/forms/${selectedForm.value.id}`, {
      name: formEdit.value.name,
      description: formEdit.value.description,
      is_active: formEdit.value.is_active,
      schema: { version: 1, fields: formEdit.value.fields },
    })
    toast.success('Form saved')
    formEditorOpen.value = false
    await loadForms()
  } catch (err) {
    toast.error('Could not save form', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function openSubmission(row: any) {
  detailOpen.value = true
  selectedSubmission.value = null
  const { data } = await api.get(`/submissions/${row.id}`)
  selectedSubmission.value = data.data
}

async function setSubmissionStatus(status: string) {
  if (!selectedSubmission.value) return
  await api.patch(`/submissions/${selectedSubmission.value.id}`, { status })
  toast.success('Submission updated')
  detailOpen.value = false
  await loadSubmissions()
}

async function exportCsv() {
  try {
    const response = await api.get('/submissions/export', { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `submissions-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (err) {
    toast.error('Export failed', apiErrorMessage(err))
  }
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="DEMAND" title="Forms & submissions" description="Form definitions, public submissions, spam scoring, and the handoff into leads.">
      <template #actions>
        <button v-if="auth.can('leads.export')" type="button" class="btn-s" @click="exportCsv"><Download :size="13" /> Export submissions</button>
      </template>
    </PageHead>

    <div class="editor-tabs">
      <button type="button" :class="{ 'is-active': tab === 'submissions' }" @click="tab = 'submissions'">Submissions <span class="count">{{ meta?.total ?? 0 }}</span></button>
      <button type="button" :class="{ 'is-active': tab === 'forms' }" @click="tab = 'forms'">Forms <span class="count">{{ forms.length }}</span></button>
    </div>

    <template v-if="tab === 'forms'">
      <div v-if="loading" class="skeleton" style="height:300px" />
      <div v-else class="forms-grid">
        <article v-for="form in forms" :key="form.id" class="panel form-card">
          <div class="panel__body">
            <div class="flex-between"><p class="mono muted">{{ form.key }}</p><StatusBadge :status="form.is_active ? 'published' : 'draft'" /></div>
            <h2 class="form-card__title">{{ form.name }}</h2>
            <p class="form-card__desc">{{ form.description }}</p>
            <div class="form-card__meta">
              <span>{{ form.fields_count }} fields</span>
              <span>{{ form.submissions_count }} submissions</span>
              <span>{{ form.creates_lead ? 'Creates leads' : 'No lead' }}</span>
            </div>
            <button type="button" class="btn-s btn-s--sm" @click="openForm(form)"><Settings2 :size="12" /> Edit form</button>
          </div>
        </article>
      </div>
    </template>

    <template v-else>
      <div class="toolbar">
        <div class="toolbar__search"><Search :size="13" /><input v-model="search" class="input-s" placeholder="Search submission data" @input="debounced"></div>
        <select v-model="formFilter" class="select-s" style="width:170px" @change="loadSubmissions">
          <option value="">All forms</option>
          <option v-for="form in forms" :key="form.id" :value="form.id">{{ form.name }}</option>
        </select>
        <select v-model="statusFilter" class="select-s" style="width:140px" @change="loadSubmissions">
          <option value="">All statuses</option><option value="new">New</option><option value="read">Read</option><option value="archived">Archived</option><option value="spam">Spam</option>
        </select>
        <div class="toolbar__spacer" />
        <span class="mono faint">Spam score is calculated server-side</span>
      </div>

      <div class="panel">
        <div v-if="loading" class="panel__body"><div v-for="i in 6" :key="i" class="skeleton" style="height:42px;margin-bottom:7px" /></div>
        <EmptyState v-else-if="!submissions.length" title="No submissions" body="Send a test enquiry from the public website to verify the form workflow." />
        <div v-else class="table-wrap">
          <table class="table-s">
            <thead><tr><th>Submission</th><th>Form</th><th>Status</th><th>Spam score</th><th>Received</th></tr></thead>
            <tbody>
              <tr v-for="submission in submissions" :key="submission.id" style="cursor:pointer" @click="openSubmission(submission)">
                <td><div class="table-s__title">{{ submission.summary }} <span v-if="submission.is_demo" class="badge badge--demo">SAMPLE</span></div><div class="table-s__sub">{{ submission.data?.email }}</div></td>
                <td class="muted">{{ submission.form }}</td>
                <td><StatusBadge :status="submission.status" /></td>
                <td class="mono" :style="{ color: submission.spam_score > 3 ? 'var(--s-red)' : 'var(--s-faint)' }">{{ submission.spam_score }}</td>
                <td class="muted nowrap">{{ formatDate(submission.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <BaseDrawer :open="detailOpen" title="Submission" width="500px" @close="detailOpen = false">
      <div v-if="!selectedSubmission" class="skeleton" style="height:260px" />
      <div v-else class="stack-s">
        <div class="flex-between"><p class="mono muted">{{ selectedSubmission.form }}</p><StatusBadge :status="selectedSubmission.status" /></div>
        <div class="submission-fields">
          <div v-for="(value, key) in selectedSubmission.data" :key="key" class="submission-field">
            <span class="mono muted">{{ key }}</span>
            <span>{{ value === true ? 'Yes' : value === false ? 'No' : value }}</span>
          </div>
        </div>
        <div class="mono faint">Received {{ formatDate(selectedSubmission.created_at) }} · IP {{ selectedSubmission.meta?.ip || 'not stored' }}</div>
        <div v-if="auth.can('leads.manage')" class="flex-s">
          <button type="button" class="btn-s" @click="setSubmissionStatus('read')">Mark read</button>
          <button type="button" class="btn-s" @click="setSubmissionStatus('archived')">Archive</button>
          <button type="button" class="btn-s btn-s--danger" @click="setSubmissionStatus('spam')">Mark spam</button>
        </div>
      </div>
    </BaseDrawer>

    <BaseDrawer :open="formEditorOpen" title="Edit form" width="560px" @close="formEditorOpen = false">
      <div v-if="selectedForm" class="stack-s">
        <div class="field-s"><label class="field-s__label">Name</label><input v-model="formEdit.name" class="input-s"></div>
        <div class="field-s"><label class="field-s__label">Description</label><textarea v-model="formEdit.description" class="textarea-s" rows="2" /></div>
        <label class="checkbox-s"><input v-model="formEdit.is_active" type="checkbox"><span>Form is active</span></label>

        <div class="form-builder">
          <div class="flex-between"><p class="mono muted">FIELDS</p><button type="button" class="btn-s btn-s--sm" @click="addField()"><Plus :size="12" /> Add field</button></div>
          <div v-for="(field, index) in formEdit.fields" :key="index" class="form-field-row">
            <div class="form-field-row__head">
              <span class="mono">{{ field.type }}</span>
              <div class="flex-s">
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :disabled="index === 0" @click="moveField(index, -1)"><ChevronUp :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :disabled="index === formEdit.fields.length - 1" @click="moveField(index, 1)"><ChevronDown :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="removeField(index)"><Trash2 :size="12" /></button>
              </div>
            </div>
            <div class="form-field-row__grid">
              <input v-model="field.name" class="input-s" placeholder="field_name">
              <select v-model="field.type" class="select-s">
                <option v-for="type in fieldTypes" :key="type" :value="type">{{ type }}</option>
              </select>
              <input v-model="field.label.en" class="input-s" placeholder="English label">
              <label class="checkbox-s"><input v-model="field.required" type="checkbox"><span>Required</span></label>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="formEditorOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="saveForm">{{ saving ? 'Saving…' : 'Save form' }}</button>
      </template>
    </BaseDrawer>
  </div>
</template>

<style scoped>
.forms-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.form-card__title { margin-top: 8px; font-size: 17px; letter-spacing: -.025em; font-weight: 600; }
.form-card__desc { margin-top: 4px; font-size: 12px; color: var(--s-muted); line-height: 1.45; min-height: 30px; }
.form-card__meta { display: flex; gap: 12px; flex-wrap: wrap; margin: 12px 0; font-size: 9.5px; color: var(--s-faint); }
.count { display: inline-flex; min-width: 17px; height: 17px; align-items: center; justify-content: center; border-radius: 999px; background: var(--s-bg); color: var(--s-muted); font-family: var(--font-mono); font-size: 7.5px; margin-left: 4px; }
.submission-fields { border-top: 1px solid var(--s-line); }
.submission-field { display: grid; grid-template-columns: 110px 1fr; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }
.form-builder { margin-top: 12px; border-top: 1px solid var(--s-line); padding-top: 10px; }
.form-field-row { border: 1px solid var(--s-line); border-radius: var(--s-radius); margin-top: 6px; background: var(--s-white); }
.form-field-row__head { display: flex; justify-content: space-between; align-items: center; padding: 5px 7px; border-bottom: 1px solid var(--s-line); background: var(--s-bg); }
.form-field-row__grid { display: grid; grid-template-columns: 1fr 110px; gap: 6px; padding: 7px; }
@media (max-width: 1100px) { .forms-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 700px) { .forms-grid { grid-template-columns: 1fr; } }
</style>
