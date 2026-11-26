<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowUpRight, ArrowDownRight, FileText, Clock3, Inbox, ShieldAlert, Plus, RefreshCw, ExternalLink } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import LineChart from '@/components/LineChart.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const router = useRouter()
const loading = ref(true)
const error = ref('')
const range = ref(30)
const data = ref<any>(null)

const user = computed(() => auth.user.value)

const dateLabel = computed(() => new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date()))

const greeting = computed(() => {
  const hour = new Date().getHours()
  return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening'
})

const metrics = computed(() => data.value?.analytics?.metrics ?? {})
const content = computed(() => data.value?.content ?? {})
const seo = computed(() => data.value?.seo ?? {})
const analytics = computed(() => data.value?.analytics ?? {})
const demoNotice = computed(() => Boolean(analytics.value?.demo_only || (data.value?.leads?.demo ?? 0) > 0))

function formatNumber(value: number | null | undefined) {
  if (value === null || value === undefined) return '—'
  return new Intl.NumberFormat('en-GB').format(value)
}

function formatDelta(value: number | null | undefined) {
  if (value === null || value === undefined) return ''
  return `${value > 0 ? '+' : ''}${value}%`
}

async function load() {
  loading.value = true
  error.value = ''
  const to = new Date()
  const from = new Date()
  from.setDate(to.getDate() - (range.value - 1))
  try {
    const response = await api.get('/dashboard', {
      params: { from: from.toISOString().slice(0, 10), to: to.toISOString().slice(0, 10) },
    })
    data.value = response.data.data
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    loading.value = false
  }
}

watch(range, load)
onMounted(load)
</script>

<template>
  <div>
    <PageHead
      eyebrow="OVERVIEW"
      :title="`${greeting}, ${user?.name?.split(' ')[0] || 'there'}.`"
      :description="`Here is what is moving across ${user?.sites?.find(s => s.id === user?.current_site_id)?.name || 'your website'} on ${dateLabel}.`"
    >
      <template #actions>
        <select v-model.number="range" class="select-s" style="width: 130px; height: 36px">
          <option :value="7">Last 7 days</option>
          <option :value="30">Last 30 days</option>
          <option :value="90">Last 90 days</option>
        </select>
        <button type="button" class="btn-s" :disabled="loading" @click="load">
          <RefreshCw :size="13" :class="{ spinning: loading }" /> Refresh
        </button>
        <button type="button" class="btn-s btn-s--primary" @click="router.push('/pages?create=1')">
          <Plus :size="14" /> New page
        </button>
      </template>
    </PageHead>

    <div v-if="demoNotice && !loading" class="demo-banner">
      <span class="badge badge--demo">Demonstration data</span>
      <p>Analytics, leads, and selected content in this installation are clearly labelled sample data. They are not real business results.</p>
    </div>

    <div v-if="loading" class="stat-grid" style="margin-bottom:14px">
      <div v-for="i in 4" :key="i" class="stat-card">
        <div class="skeleton" style="height:9px;width:60%" />
        <div class="skeleton" style="height:26px;width:45%" />
        <div class="skeleton" style="height:10px;width:70%" />
      </div>
    </div>

    <div v-else-if="error" class="empty-s">
      <p class="empty-s__title">Dashboard unavailable</p>
      <p class="empty-s__body">{{ error }}</p>
      <button type="button" class="btn-s btn-s--primary btn-s--sm" style="margin-top:12px" @click="load">Try again</button>
    </div>

    <template v-else-if="data">
      <div class="stat-grid" style="margin-bottom:14px">
        <article class="stat-card">
          <div class="stat-card__label"><span>Published pages</span><FileText :size="13" /></div>
          <p class="stat-card__value">{{ formatNumber(content.published_pages) }}</p>
          <p class="stat-card__meta"><span class="delta-up">{{ content.drafts }} drafts</span> in the pipeline</p>
        </article>
        <article class="stat-card">
          <div class="stat-card__label"><span>Pending approval</span><Clock3 :size="13" /></div>
          <p class="stat-card__value">{{ formatNumber(content.pending_approvals) }}</p>
          <p class="stat-card__meta">{{ content.scheduled_publications }} scheduled · {{ content.in_review_pages }} in review</p>
        </article>
        <article class="stat-card">
          <div class="stat-card__label"><span>Open leads</span><Inbox :size="13" /></div>
          <p class="stat-card__value">{{ formatNumber(data.leads.open) }}</p>
          <p class="stat-card__meta"><span :class="metrics.leads_delta >= 0 ? 'delta-up' : 'delta-down'">{{ formatDelta(metrics.leads_delta) }}</span> vs previous period</p>
        </article>
        <article class="stat-card">
          <div class="stat-card__label"><span>Page views</span><ArrowUpRight :size="13" /></div>
          <p class="stat-card__value">{{ formatNumber(metrics.page_views) }}</p>
          <p class="stat-card__meta">
            <span :class="metrics.page_views_delta >= 0 ? 'delta-up' : 'delta-down'">
              <ArrowUpRight v-if="metrics.page_views_delta >= 0" :size="11" style="display:inline" />
              <ArrowDownRight v-else :size="11" style="display:inline" />
              {{ formatDelta(metrics.page_views_delta) }}
            </span>
            vs previous period
          </p>
        </article>
      </div>

      <div class="s-two-col" style="margin-bottom:14px">
        <section class="panel">
          <div class="panel__head">
            <div>
              <p class="panel__title">Traffic</p>
              <p class="mono muted" style="margin-top:3px">{{ analytics.range?.days }} days · first-party events</p>
            </div>
            <div class="chart-legend">
              <span><i style="background:var(--s-accent)" /> Views</span>
              <span><i style="background:var(--s-ink)" /> Visitors</span>
            </div>
          </div>
          <div class="panel__body">
            <LineChart v-if="analytics.series?.length && analytics.has_data" :series="analytics.series" :height="240" />
            <div v-else class="empty-s" style="border:0;background:transparent;padding:48px 0">
              <p class="empty-s__title">No traffic recorded yet</p>
              <p class="empty-s__body">First-party events will appear here once the public website receives consented visits.</p>
            </div>
          </div>
        </section>

        <section class="panel">
          <div class="panel__head">
            <p class="panel__title">Content health</p>
            <RouterLink to="/seo" class="btn-s btn-s--ghost btn-s--sm">SEO centre <ArrowUpRight :size="12" /></RouterLink>
          </div>
          <div class="panel__body">
            <div class="seo-score">
              <div class="seo-score__ring" :style="{ '--score': `${seo.score ?? 0}%` }">
                <span>{{ seo.score ?? '—' }}</span>
                <small>/ 100</small>
              </div>
              <div>
                <StatusBadge :status="seo.status || 'not_run'" />
                <p class="seo-score__meta">{{ seo.checked_at ? `Checked ${new Date(seo.checked_at).toLocaleString('en-GB')}` : 'No audit has been run yet.' }}</p>
              </div>
            </div>
            <div class="health-rows">
              <div class="health-row"><span><ShieldAlert :size="13" style="color:var(--s-red)" /> Critical issues</span><strong>{{ seo.critical ?? 0 }}</strong></div>
              <div class="health-row"><span><ShieldAlert :size="13" style="color:var(--s-amber)" /> Warnings</span><strong>{{ seo.warnings ?? 0 }}</strong></div>
              <div class="health-row"><span><FileText :size="13" /> Drafts</span><strong>{{ content.draft_pages ?? 0 }}</strong></div>
              <div class="health-row"><span><FileText :size="13" /> Articles</span><strong>{{ content.articles ?? 0 }}</strong></div>
            </div>
            <div class="media-usage">
              <div class="flex-between"><span class="mono muted">MEDIA STORAGE</span><span class="mono">{{ data.media?.storage_human }}</span></div>
              <div class="media-usage__bar"><span :style="{ width: `${Math.min(100, ((data.media?.storage_bytes || 0) / (5 * 1024 * 1024 * 1024)) * 100)}%` }" /></div>
              <p class="mono faint" style="margin-top:4px">{{ data.media?.assets }} assets · 5 GB demonstration quota</p>
            </div>
          </div>
        </section>
      </div>

      <div class="s-two-col">
        <section class="panel">
          <div class="panel__head">
            <p class="panel__title">Top pages</p>
            <span class="mono faint">VIEWS</span>
          </div>
          <div class="table-wrap">
            <table v-if="data.analytics?.top_pages?.length" class="table-s">
              <tbody>
                <tr v-for="page in data.analytics.top_pages" :key="page.path">
                  <td style="width: 100%">
                    <div class="table-s__title">{{ page.title }}</div>
                    <div class="table-s__sub">{{ page.path }}</div>
                  </td>
                  <td class="nowrap" style="text-align:right">{{ formatNumber(page.views) }}</td>
                  <td class="nowrap muted" style="text-align:right">{{ formatNumber(page.visitors) }}</td>
                </tr>
              </tbody>
            </table>
            <div v-else class="table-empty">No page traffic recorded for this period.</div>
          </div>
        </section>

        <section class="panel">
          <div class="panel__head">
            <p class="panel__title">Recent activity</p>
            <RouterLink to="/workflows" class="btn-s btn-s--ghost btn-s--sm">View audit</RouterLink>
          </div>
          <div class="activity-list">
            <div v-for="item in data.recent_activity" :key="item.id" class="activity-item">
              <span class="activity-item__dot" />
              <div style="min-width:0">
                <p class="activity-item__text">{{ item.description }}</p>
                <p class="activity-item__meta mono">{{ item.user }} · {{ new Date(item.created_at).toLocaleString('en-GB') }}</p>
              </div>
            </div>
            <div v-if="!data.recent_activity?.length" class="table-empty">No activity yet.</div>
          </div>
        </section>
      </div>

      <div class="s-two-col" style="margin-top:14px">
        <section class="panel">
          <div class="panel__head">
            <p class="panel__title">Recent submissions</p>
            <RouterLink to="/leads" class="btn-s btn-s--ghost btn-s--sm">Open lead inbox</RouterLink>
          </div>
          <div class="table-wrap">
            <table v-if="data.recent_submissions?.length" class="table-s">
              <thead><tr><th>Contact</th><th>Form</th><th>Status</th><th>Received</th></tr></thead>
              <tbody>
                <tr v-for="submission in data.recent_submissions" :key="submission.id">
                  <td><div class="table-s__title">{{ submission.name }}</div><div class="table-s__sub">{{ submission.email }}</div></td>
                  <td>{{ submission.form }}</td>
                  <td><StatusBadge :status="submission.status" /></td>
                  <td class="nowrap muted">{{ new Date(submission.created_at).toLocaleDateString('en-GB') }}</td>
                </tr>
              </tbody>
            </table>
            <div v-else class="table-empty">No submissions yet.</div>
          </div>
        </section>

        <section class="panel">
          <div class="panel__head"><p class="panel__title">Publishing queue</p><RouterLink to="/pages?status=scheduled" class="btn-s btn-s--ghost btn-s--sm">Open pages</RouterLink></div>
          <div class="panel__body">
            <div class="queue-row">
              <div><p class="queue-row__title">Scheduled pages</p><p class="queue-row__meta">Awaiting the scheduler</p></div>
              <strong>{{ content.scheduled_publications ?? 0 }}</strong>
            </div>
            <div class="queue-row">
              <div><p class="queue-row__title">Pending review</p><p class="queue-row__meta">Blocked until approval</p></div>
              <strong>{{ content.pending_approvals ?? 0 }}</strong>
            </div>
            <div class="queue-row">
              <div><p class="queue-row__title">Open roles</p><p class="queue-row__meta">Source: careers module</p></div>
              <strong>—</strong>
            </div>
            <RouterLink to="/workflows" class="text-inline">Review workflow queue <ArrowUpRight :size="12" /></RouterLink>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

<style scoped>
.spinning { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.demo-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--s-accent-soft);
  border: 1px solid #efd0c2;
  border-radius: var(--s-radius);
  padding: 9px 12px;
  margin-bottom: 14px;
}

.demo-banner p {
  font-size: 12px;
  color: #7e4b34;
}

.chart-legend {
  display: flex;
  gap: 12px;
  font-family: var(--font-mono);
  font-size: 8px;
  color: var(--s-faint);
  text-transform: uppercase;
}

.chart-legend span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.chart-legend i {
  width: 12px;
  height: 2px;
  display: inline-block;
}

.seo-score {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--s-line);
}

.seo-score__ring {
  --score: 0%;
  width: 78px;
  height: 78px;
  border-radius: 50%;
  background: conic-gradient(var(--s-accent) var(--score), var(--s-bg) 0);
  display: grid;
  place-items: center;
  position: relative;
  flex: 0 0 auto;
}

.seo-score__ring::before {
  content: "";
  position: absolute;
  inset: 7px;
  background: var(--s-surface);
  border-radius: 50%;
}

.seo-score__ring span {
  position: relative;
  font-size: 22px;
  font-weight: 600;
  letter-spacing: -0.04em;
}

.seo-score__ring small {
  position: absolute;
  bottom: 19px;
  font-family: var(--font-mono);
  font-size: 7px;
  color: var(--s-faint);
}

.seo-score__meta {
  margin-top: 6px;
  font-size: 9.5px;
  color: var(--s-faint);
}

.health-rows {
  padding-block: 12px;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.health-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid var(--s-line);
  font-size: 12px;
}

.health-row span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--s-muted);
}

.health-row strong {
  font-size: 13px;
}

.media-usage {
  margin-top: 12px;
}

.media-usage__bar {
  height: 5px;
  background: var(--s-bg);
  border-radius: 999px;
  overflow: hidden;
  margin-top: 6px;
}

.media-usage__bar span {
  display: block;
  height: 100%;
  background: var(--s-ink);
}

.activity-list {
  padding: 4px 16px 8px;
}

.activity-item {
  display: flex;
  gap: 9px;
  padding: 9px 0;
  border-bottom: 1px solid var(--s-line);
}

.activity-item:last-child {
  border-bottom: 0;
}

.activity-item__dot {
  width: 5px;
  height: 5px;
  background: var(--s-accent);
  margin-top: 6px;
  flex: 0 0 auto;
}

.activity-item__text {
  font-size: 12px;
  line-height: 1.45;
}

.activity-item__meta {
  margin-top: 2px;
  font-size: 8px;
  color: var(--s-faint);
}

.queue-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 11px 0;
  border-bottom: 1px solid var(--s-line);
}

.queue-row__title { font-size: 12px; font-weight: 600; }
.queue-row__meta { margin-top: 2px; font-size: 9.5px; color: var(--s-faint); }
.queue-row strong { font-size: 18px; letter-spacing: -0.03em; }

.text-inline {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 12px;
  font-size: 12px;
  color: var(--s-accent);
}
</style>
