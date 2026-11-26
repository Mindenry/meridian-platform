<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { SearchCheck, Play, ExternalLink, Plus, Trash2, RefreshCw, Filter } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import BaseModal from '@/components/BaseModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'
import type { PaginationMeta } from '@/types'

const route = useRoute()
const loading = ref(true)
const health = ref<any>(null)
const issues = ref<any[]>([])
const meta = ref<PaginationMeta | null>(null)
const redirects = ref<any[]>([])
const tab = ref<'issues' | 'redirects'>('issues')
const severity = ref('')
const statusFilter = ref('open')
const running = ref(false)
const redirectOpen = ref(false)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const redirectForm = ref({ from_path: '', to_path: '', status_code: 301, locale: '', is_active: true })

const severityCounts = computed(() => health.value?.open || {})
const score = computed(() => health.value?.score ?? 0)

async function load() {
  loading.value = true
  try {
    const [healthResponse, issuesResponse, redirectsResponse] = await Promise.all([
      api.get('/seo/health'),
      api.get('/seo/issues', { params: { severity: severity.value || undefined, status: statusFilter.value, per_page: 40 } }),
      api.get('/redirects'),
    ])
    health.value = healthResponse.data.data
    issues.value = issuesResponse.data.data
    meta.value = issuesResponse.data.meta
    redirects.value = redirectsResponse.data.data
  } catch (err) {
    toast.error('Could not load SEO data', apiErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function runAudit() {
  running.value = true
  try {
    const { data } = await api.post('/seo/audit')
    toast.success('Audit complete', `Score ${data.data.score}/100 across ${data.data.issues} issues.`)
    await load()
  } catch (err) {
    toast.error('Audit failed', apiErrorMessage(err))
  } finally {
    running.value = false
  }
}

async function updateIssue(issue: any, status: string) {
  try {
    await api.patch(`/seo/issues/${issue.id}`, { status })
    toast.success('Issue updated')
    await load()
  } catch (err) {
    toast.error('Could not update issue', apiErrorMessage(err))
  }
}

async function createRedirect() {
  saving.value = true
  errors.value = {}
  try {
    await api.post('/redirects', { ...redirectForm.value, locale: redirectForm.value.locale || null })
    toast.success('Redirect created')
    redirectOpen.value = false
    redirectForm.value = { from_path: '', to_path: '', status_code: 301, locale: '', is_active: true }
    await load()
  } catch (err) {
    errors.value = (err as any)?.response?.data?.errors || {}
    toast.error('Could not create redirect', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function deleteRedirect(redirect: any) {
  try {
    await api.delete(`/redirects/${redirect.id}`)
    toast.success('Redirect deleted')
    await load()
  } catch (err) {
    toast.error('Could not delete redirect', apiErrorMessage(err))
  }
}

function severityClass(value: string) {
  return value === 'critical' ? 'badge--critical' : value === 'warning' ? 'badge--warning' : 'badge--info'
}

watch([severity, statusFilter], load)

onMounted(async () => {
  await load()
  if (route.query.audit === '1') runAudit()
})
</script>

<template>
  <div>
    <PageHead eyebrow="SEARCH" title="SEO health centre" description="Deterministic checks for metadata, structure, canonicals, hreflang, images, redirects, and content depth. A score is a health signal, not a ranking promise.">
      <template #actions>
        <a href="http://localhost:3002/sitemap.xml" target="_blank" class="btn-s"><ExternalLink :size="13" /> Sitemap</a>
        <button v-if="auth.can('seo.manage')" type="button" class="btn-s btn-s--primary" :disabled="running" @click="runAudit"><RefreshCw :size="13" :class="{ spin: running }" /> {{ running ? 'Auditing…' : 'Run audit' }}</button>
      </template>
    </PageHead>

    <div class="seo-top">
      <section class="panel seo-overview">
        <div class="seo-overview__score">
          <div class="score-ring" :style="{ '--score': `${score}%` }"><span>{{ health?.score ?? '—' }}</span><small>/100</small></div>
          <div>
            <p class="mono muted">DETERMINISTIC HEALTH SCORE</p>
            <p class="seo-overview__date">{{ health?.checked_at ? `Last audited ${new Date(health.checked_at).toLocaleString('en-GB')}` : 'No audit has been run yet.' }}</p>
            <StatusBadge :status="health?.status || 'not_run'" />
          </div>
        </div>
        <div class="severity-grid">
          <div class="severity severity--critical"><span>Critical</span><strong>{{ severityCounts.critical || 0 }}</strong></div>
          <div class="severity severity--warning"><span>Warnings</span><strong>{{ severityCounts.warning || 0 }}</strong></div>
          <div class="severity severity--info"><span>Info</span><strong>{{ severityCounts.info || 0 }}</strong></div>
        </div>
        <p class="seo-method">{{ health?.method || 'Checks run across published pages and content.' }}</p>
      </section>

      <section class="panel">
        <div class="panel__head"><p class="panel__title">Quick checks</p></div>
        <div class="panel__body quick-checks">
          <p><span>Sitemap index</span><a href="http://localhost:3002/sitemap.xml" target="_blank">Open</a></p>
          <p><span>Robots.txt</span><a href="http://localhost:3002/robots.txt" target="_blank">Open</a></p>
          <p><span>Redirect manager</span><button type="button" @click="tab = 'redirects'">Manage</button></p>
          <p><span>Search integrations</span><button type="button" @click="$router.push('/settings')">Configure</button></p>
        </div>
      </section>
    </div>

    <div class="editor-tabs">
      <button type="button" :class="{ 'is-active': tab === 'issues' }" @click="tab = 'issues'">Issues <span class="count">{{ meta?.total ?? 0 }}</span></button>
      <button type="button" :class="{ 'is-active': tab === 'redirects' }" @click="tab = 'redirects'">Redirects <span class="count">{{ redirects.length }}</span></button>
    </div>

    <div v-if="tab === 'issues'" class="panel">
      <div class="toolbar" style="padding:10px 12px;margin-bottom:0;border-bottom:1px solid var(--s-line)">
        <select v-model="statusFilter" class="select-s" style="width:150px">
          <option value="open">Open issues</option>
          <option value="resolved">Resolved</option>
          <option value="ignored">Ignored</option>
          <option value="all">All issues</option>
        </select>
        <select v-model="severity" class="select-s" style="width:140px">
          <option value="">All severities</option>
          <option value="critical">Critical</option>
          <option value="warning">Warning</option>
          <option value="info">Info</option>
        </select>
        <div class="toolbar__spacer" />
        <span class="mono faint">Scores are explainable and deterministic</span>
      </div>
      <div v-if="loading" class="panel__body"><div v-for="i in 6" :key="i" class="skeleton" style="height:42px;margin-bottom:7px" /></div>
      <EmptyState v-else-if="!issues.length" title="No SEO issues in this view" body="Run an audit after content changes, or adjust the filters." action-label="Run audit" @action="runAudit" />
      <div v-else class="table-wrap">
        <table class="table-s">
          <thead><tr><th>Severity</th><th>Issue</th><th>URL</th><th>Recommendation</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
          <tbody>
            <tr v-for="issue in issues" :key="issue.id">
              <td><span class="badge" :class="severityClass(issue.severity)">{{ issue.severity }}</span></td>
              <td>
                <div class="table-s__title">{{ issue.code }}</div>
                <div class="table-s__sub">{{ issue.explanation }}</div>
              </td>
              <td class="mono" style="font-size: 12px">{{ issue.url }}</td>
              <td class="muted" style="max-width:260px;font-size: 12px">{{ issue.recommendation }}</td>
              <td><StatusBadge :status="issue.status" /></td>
              <td>
                <div class="table-s__actions">
                  <button v-if="issue.status === 'open' && auth.can('seo.manage')" type="button" class="btn-s btn-s--sm" @click="updateIssue(issue, 'resolved')">Resolve</button>
                  <button v-if="issue.status === 'open' && auth.can('seo.manage')" type="button" class="btn-s btn-s--ghost btn-s--sm" @click="updateIssue(issue, 'ignored')">Ignore</button>
                  <button v-if="issue.status !== 'open' && auth.can('seo.manage')" type="button" class="btn-s btn-s--ghost btn-s--sm" @click="updateIssue(issue, 'open')">Reopen</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="panel">
      <div class="toolbar" style="padding:10px 12px;margin-bottom:0;border-bottom:1px solid var(--s-line)">
        <span class="mono faint">Redirect chains and loops are checked during each audit.</span>
        <div class="toolbar__spacer" />
        <button v-if="auth.can('redirects.manage')" type="button" class="btn-s btn-s--primary btn-s--sm" @click="redirectOpen = true"><Plus :size="12" /> New redirect</button>
      </div>
      <EmptyState v-if="!redirects.length" title="No redirects configured" body="Add a redirect when a published URL changes so readers and crawlers reach the new page." action-label="New redirect" @action="redirectOpen = true" />
      <div v-else class="table-wrap">
        <table class="table-s">
          <thead><tr><th>From</th><th>To</th><th>Status</th><th>Locale</th><th>Hits</th><th>Active</th><th /></tr></thead>
          <tbody>
            <tr v-for="redirect in redirects" :key="redirect.id">
              <td class="mono" style="font-size:9.5px">{{ redirect.from_path }}</td>
              <td class="mono" style="font-size:9.5px">{{ redirect.to_path }}</td>
              <td>{{ redirect.status_code }}</td>
              <td>{{ redirect.locale || 'Any' }}</td>
              <td>{{ redirect.hits }}</td>
              <td><StatusBadge :status="redirect.is_active ? 'published' : 'draft'" /></td>
              <td><button v-if="auth.can('redirects.manage')" type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="deleteRedirect(redirect)"><Trash2 :size="12" /></button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <BaseModal :open="redirectOpen" title="New redirect" @close="redirectOpen = false">
      <div class="stack-s">
        <div class="field-s"><label class="field-s__label">From path</label><input v-model="redirectForm.from_path" class="input-s" placeholder="/old-path"><p v-if="errors.from_path" class="error-s">{{ errors.from_path[0] }}</p></div>
        <div class="field-s"><label class="field-s__label">To path or URL</label><input v-model="redirectForm.to_path" class="input-s" placeholder="/new-path"><p v-if="errors.to_path" class="error-s">{{ errors.to_path[0] }}</p></div>
        <div class="redirect-fields">
          <div class="field-s"><label class="field-s__label">Status code</label><select v-model="redirectForm.status_code" class="select-s"><option :value="301">301 Permanent</option><option :value="302">302 Temporary</option><option :value="307">307 Temporary</option><option :value="308">308 Permanent</option></select></div>
          <div class="field-s"><label class="field-s__label">Locale</label><select v-model="redirectForm.locale" class="select-s"><option value="">Any locale</option><option value="th">ไทย</option><option value="en">English</option><option value="zh-CN">简体中文</option></select></div>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="redirectOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !redirectForm.from_path || !redirectForm.to_path" @click="createRedirect">{{ saving ? 'Saving…' : 'Create redirect' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.spin { animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.seo-top { display: grid; grid-template-columns: 1.6fr 1fr; gap: 14px; margin-bottom: 14px; }

.seo-overview { padding: 16px; }
.seo-overview__score { display: flex; align-items: center; gap: 18px; }
.score-ring { width: 92px; height: 92px; border-radius: 50%; background: conic-gradient(var(--s-accent) var(--score), var(--s-bg) 0); display: grid; place-items: center; position: relative; flex: 0 0 auto; }
.score-ring::before { content: ""; position: absolute; inset: 8px; border-radius: 50%; background: var(--s-surface); }
.score-ring span { position: relative; font-size: 27px; font-weight: 600; letter-spacing: -.04em; }
.score-ring small { position: absolute; bottom: 23px; font-family: var(--font-mono); font-size: 7px; color: var(--s-faint); }
.seo-overview__date { font-size: 12px; color: var(--s-muted); margin: 5px 0 7px; }
.severity-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 14px; border-top: 1px solid var(--s-line); padding-top: 12px; }
.severity { display: flex; flex-direction: column; gap: 3px; padding-left: 8px; border-left: 2px solid var(--s-line); }
.severity span { font-size: 9.5px; color: var(--s-muted); }
.severity strong { font-size: 20px; letter-spacing: -.03em; }
.severity--critical { border-color: var(--s-red); }
.severity--warning { border-color: var(--s-amber); }
.severity--info { border-color: var(--s-blue); }
.seo-method { margin-top: 12px; font-size: 12px; color: var(--s-faint); line-height: 1.5; }

.quick-checks { display: flex; flex-direction: column; gap: 1px; }
.quick-checks p { display: flex; justify-content: space-between; gap: 10px; align-items: center; font-size: 12px; padding: 7px 0; border-bottom: 1px solid var(--s-line); color: var(--s-muted); }
.quick-checks p:last-child { border-bottom: 0; }
.quick-checks a, .quick-checks button { border: 0; background: transparent; color: var(--s-accent); font-size: 12px; }

.count { display: inline-flex; min-width: 17px; height: 17px; align-items: center; justify-content: center; border-radius: 999px; background: var(--s-bg); color: var(--s-muted); font-family: var(--font-mono); font-size: 7.5px; margin-left: 4px; }

.redirect-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
</style>
