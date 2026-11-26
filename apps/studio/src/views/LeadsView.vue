<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Search, Download, Inbox, Mail, Phone, Building2, CalendarClock, UserRound, Trash2 } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import BaseDrawer from '@/components/BaseDrawer.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'
import { API_BASE } from '@/api/client'
import type { PaginationMeta } from '@/types'

const loading = ref(true)
const rows = ref<any[]>([])
const meta = ref<PaginationMeta | null>(null)
const owners = ref<any[]>([])
const search = ref('')
const status = ref('')
const priority = ref('')
const ownerId = ref('')
const drawerOpen = ref(false)
const lead = ref<any>(null)
const saving = ref(false)
const note = ref('')
const deleting = ref(false)

const edit = ref({ status: 'new', priority: 'normal', owner_id: null as number | null, follow_up_at: '', notes: '' })

const canManage = computed(() => auth.can('leads.manage'))

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/leads', {
      params: { q: search.value || undefined, status: status.value || undefined, priority: priority.value || undefined, owner_id: ownerId.value || undefined, per_page: 30 },
    })
    rows.value = data.data
    meta.value = data.meta
    owners.value = data.owners
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

async function openLead(row: any) {
  drawerOpen.value = true
  lead.value = null
  try {
    const { data } = await api.get(`/leads/${row.id}`)
    lead.value = data.data
    edit.value = {
      status: data.data.status,
      priority: data.data.priority,
      owner_id: data.data.owner?.id || null,
      follow_up_at: data.data.follow_up_at ? data.data.follow_up_at.slice(0, 10) : '',
      notes: data.data.notes || '',
    }
  } catch (err) {
    toast.error('Could not load lead', apiErrorMessage(err))
  }
}

async function saveLead() {
  if (!lead.value) return
  saving.value = true
  try {
    await api.patch(`/leads/${lead.value.id}`, {
      status: edit.value.status,
      priority: edit.value.priority,
      owner_id: edit.value.owner_id,
      follow_up_at: edit.value.follow_up_at || null,
      notes: edit.value.notes,
    })
    toast.success('Lead updated')
    drawerOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not update lead', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function addNote() {
  if (!note.value.trim() || !lead.value) return
  try {
    const { data } = await api.post(`/leads/${lead.value.id}/activities`, { type: 'note', note: note.value })
    lead.value.activities.unshift({ id: data.data.id, type: 'note', note: note.value, user: auth.user.value?.name, created_at: new Date().toISOString() })
    note.value = ''
    toast.success('Activity recorded')
  } catch (err) {
    toast.error('Could not add activity', apiErrorMessage(err))
  }
}

async function removeLead() {
  if (!lead.value) return
  deleting.value = true
  try {
    await api.delete(`/leads/${lead.value.id}`)
    toast.success('Lead deleted')
    drawerOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not delete lead', apiErrorMessage(err))
  } finally {
    deleting.value = false
  }
}

async function exportLeads() {
  try {
    const response = await api.get('/leads/export', { responseType: 'blob' })
    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = `leads-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (err) {
    toast.error('Export failed', apiErrorMessage(err))
  }
}

function formatDate(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value))
}

function formatDateTime(value?: string | null) {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="DEMAND" title="Lead inbox" description="Every website enquiry becomes a lead with a source, owner, status, and activity trail.">
      <template #actions>
        <button v-if="auth.can('leads.export')" type="button" class="btn-s" @click="exportLeads"><Download :size="13" /> Export CSV</button>
        <button type="button" class="btn-s" @click="load">Refresh</button>
      </template>
    </PageHead>

    <div class="toolbar">
      <div class="toolbar__search">
        <Search :size="13" />
        <input v-model="search" class="input-s" placeholder="Search by name, email, or company" @input="debounced">
      </div>
      <select v-model="status" class="select-s" style="width:140px" @change="load">
        <option value="">All statuses</option><option value="new">New</option><option value="contacted">Contacted</option><option value="qualified">Qualified</option><option value="proposal">Proposal</option><option value="won">Won</option><option value="lost">Lost</option><option value="archived">Archived</option>
      </select>
      <select v-model="priority" class="select-s" style="width:120px" @change="load">
        <option value="">Any priority</option><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option>
      </select>
      <select v-model="ownerId" class="select-s" style="width:160px" @change="load">
        <option value="">Any owner</option>
        <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
      </select>
      <div class="toolbar__spacer" />
      <span class="badge badge--demo">Demo leads labelled</span>
    </div>

    <div class="panel">
      <div v-if="loading" class="panel__body">
        <div v-for="i in 7" :key="i" class="skeleton" style="height:42px;margin-bottom:8px" />
      </div>
      <EmptyState v-else-if="!rows.length" title="No leads in this view" body="Adjust the filters or send a test enquiry from the public contact form." />
      <div v-else class="table-wrap">
        <table class="table-s">
          <thead><tr><th>Contact</th><th>Source</th><th>Status</th><th>Priority</th><th>Owner</th><th>Follow-up</th><th>Received</th></tr></thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id" style="cursor:pointer" @click="openLead(row)">
              <td>
                <div class="table-s__title">{{ row.name }} <span v-if="row.is_demo" class="badge badge--demo">SAMPLE</span></div>
                <div class="table-s__sub">{{ row.email }} <span v-if="row.company">· {{ row.company }}</span></div>
              </td>
              <td class="muted">{{ row.source }}</td>
              <td><StatusBadge :status="row.status" /></td>
              <td><span class="badge" :class="row.priority === 'urgent' || row.priority === 'high' ? 'badge--critical' : 'badge--neutral'">{{ row.priority }}</span></td>
              <td class="muted">{{ row.owner?.name || 'Unassigned' }}</td>
              <td class="muted nowrap">{{ formatDate(row.follow_up_at) }}</td>
              <td class="muted nowrap">{{ formatDate(row.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="meta" class="flex-between" style="padding:10px 14px;border-top:1px solid var(--s-line)">
        <span class="mono muted">{{ meta.total }} leads</span>
        <span class="mono faint">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      </div>
    </div>

    <BaseDrawer :open="drawerOpen" title="Lead detail" width="540px" @close="drawerOpen = false">
      <div v-if="!lead" class="skeleton" style="height:300px" />
      <div v-else class="stack-s">
        <div class="lead-head">
          <div class="lead-avatar">{{ lead.name.slice(0, 2).toUpperCase() }}</div>
          <div>
            <p class="lead-name">{{ lead.name }} <span v-if="lead.is_demo" class="badge badge--demo">SAMPLE</span></p>
            <p class="mono faint">{{ lead.source }} · {{ formatDateTime(lead.created_at) }}</p>
          </div>
        </div>

        <div class="lead-contact">
          <a :href="`mailto:${lead.email}`"><Mail :size="13" /> {{ lead.email }}</a>
          <a v-if="lead.phone" :href="`tel:${lead.phone}`"><Phone :size="13" /> {{ lead.phone }}</a>
          <span v-if="lead.company"><Building2 :size="13" /> {{ lead.company }}</span>
        </div>

        <div v-if="lead.message_text" class="lead-message">{{ lead.message_text }}</div>

        <div class="lead-form">
          <div class="field-s">
            <label class="field-s__label">Status</label>
            <select v-model="edit.status" class="select-s">
              <option value="new">New</option><option value="contacted">Contacted</option><option value="qualified">Qualified</option><option value="proposal">Proposal</option><option value="won">Won</option><option value="lost">Lost</option><option value="archived">Archived</option>
            </select>
          </div>
          <div class="field-s">
            <label class="field-s__label">Priority</label>
            <select v-model="edit.priority" class="select-s">
              <option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option>
            </select>
          </div>
          <div class="field-s">
            <label class="field-s__label">Owner</label>
            <select v-model="edit.owner_id" class="select-s">
              <option :value="null">Unassigned</option>
              <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
            </select>
          </div>
          <div class="field-s">
            <label class="field-s__label">Follow-up date</label>
            <input v-model="edit.follow_up_at" type="date" class="input-s">
          </div>
          <div class="field-s" style="grid-column: 1 / -1">
            <label class="field-s__label">Internal notes</label>
            <textarea v-model="edit.notes" class="textarea-s" rows="3" />
          </div>
        </div>

        <div class="flex-s">
          <button type="button" class="btn-s btn-s--primary" :disabled="saving || !canManage" @click="saveLead">{{ saving ? 'Saving…' : 'Save lead' }}</button>
          <button v-if="auth.can('leads.manage')" type="button" class="btn-s btn-s--danger" :disabled="deleting" @click="removeLead"><Trash2 :size="12" /> Delete</button>
        </div>

        <div class="activity-section">
          <p class="mono muted">ACTIVITY</p>
          <div class="activity-add">
            <input v-model="note" class="input-s" placeholder="Add a note or call summary" @keyup.enter="addNote">
            <button type="button" class="btn-s" :disabled="!note.trim() || !canManage" @click="addNote">Add</button>
          </div>
          <div class="activity-list">
            <div v-for="activity in lead.activities" :key="activity.id" class="activity-row">
              <span class="activity-dot" />
              <div>
                <p>{{ activity.note || activity.type }}</p>
                <p class="mono faint">{{ activity.type }} · {{ activity.user || 'System' }} · {{ formatDateTime(activity.created_at) }}</p>
              </div>
            </div>
            <p v-if="!lead.activities?.length" class="muted" style="font-size: 12px;padding:8px 0">No activity recorded yet.</p>
          </div>
        </div>
      </div>
    </BaseDrawer>
  </div>
</template>

<style scoped>
.lead-head { display: flex; gap: 10px; align-items: center; padding-bottom: 12px; border-bottom: 1px solid var(--s-line); }
.lead-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--s-ink); color: #f5f3ee; display: grid; place-items: center; font-family: var(--font-mono); font-size: 12px; }
.lead-name { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.lead-contact { display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: var(--s-muted); }
.lead-contact a, .lead-contact span { display: inline-flex; align-items: center; gap: 5px; }
.lead-contact a:hover { color: var(--s-accent); }
.lead-message { background: var(--s-bg); border-left: 2px solid var(--s-accent); padding: 10px 12px; font-size: 12px; line-height: 1.6; color: var(--s-ink-2); }
.lead-form { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-block: 12px; border-top: 1px solid var(--s-line); border-bottom: 1px solid var(--s-line); }
.activity-section { border-top: 1px solid var(--s-line); padding-top: 12px; }
.activity-add { display: flex; gap: 6px; margin-top: 8px; }
.activity-list { margin-top: 8px; }
.activity-row { display: flex; gap: 8px; padding: 8px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }
.activity-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--s-accent); margin-top: 5px; flex: 0 0 auto; }
</style>
