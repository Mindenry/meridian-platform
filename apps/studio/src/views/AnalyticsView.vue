<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { BarChart3, ArrowUpRight, ArrowDownRight, RefreshCw, Download } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import LineChart from '@/components/LineChart.vue'
import { api, apiErrorMessage } from '@/api/client'
import { toast } from '@/stores/toast'

const loading = ref(true)
const range = ref(30)
const data = ref<any>(null)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  const to = new Date()
  const from = new Date()
  from.setDate(to.getDate() - (range.value - 1))
  try {
    const { data: response } = await api.get('/analytics', { params: { from: from.toISOString().slice(0, 10), to: to.toISOString().slice(0, 10) } })
    data.value = response.data
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    loading.value = false
  }
}

const metrics = () => data.value?.metrics || {}

function number(value: number | undefined) {
  return value === undefined ? '—' : new Intl.NumberFormat('en-GB').format(value)
}

function delta(value: number | undefined) {
  if (value === undefined) return ''
  return `${value > 0 ? '+' : ''}${value}%`
}

watch(range, load)
onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="MEASUREMENT" title="Analytics" description="First-party page views, consent-aware events, form submissions, and lead conversion. External providers are never presented as connected unless they are.">
      <template #actions>
        <select v-model.number="range" class="select-s" style="width:140px">
          <option :value="7">Last 7 days</option>
          <option :value="30">Last 30 days</option>
          <option :value="90">Last 90 days</option>
        </select>
        <button type="button" class="btn-s" :disabled="loading" @click="load"><RefreshCw :size="13" :class="{ spin: loading }" /> Refresh</button>
      </template>
    </PageHead>

    <div v-if="data?.demo_only" class="demo-banner" style="display:flex;gap:10px;align-items:center;background:var(--s-accent-soft);border:1px solid #efd0c2;border-radius:var(--s-radius);padding:8px 12px;margin-bottom:12px">
      <span class="badge badge--demo">Demonstration data</span>
      <span style="font-size: 12px;color:#7e4b34">This period contains seeded events only. First-party production events will mix in when the website receives consented visits.</span>
    </div>

    <div v-if="loading" class="skeleton" style="height:420px;border-radius:4px" />
    <div v-else-if="error" class="empty-s"><p class="empty-s__title">Analytics unavailable</p><p class="empty-s__body">{{ error }}</p></div>

    <template v-else-if="data">
      <div class="stat-grid" style="margin-bottom:14px">
        <article class="stat-card"><div class="stat-card__label"><span>Page views</span><BarChart3 :size="13" /></div><p class="stat-card__value">{{ number(metrics().page_views) }}</p><p class="stat-card__meta"><span :class="metrics().page_views_delta >= 0 ? 'delta-up' : 'delta-down'">{{ delta(metrics().page_views_delta) }}</span> vs previous period</p></article>
        <article class="stat-card"><div class="stat-card__label"><span>Unique visitors</span></div><p class="stat-card__value">{{ number(metrics().unique_visitors) }}</p><p class="stat-card__meta">Hashed sessions, no cookies</p></article>
        <article class="stat-card"><div class="stat-card__label"><span>Form submissions</span></div><p class="stat-card__value">{{ number(metrics().form_submissions) }}</p><p class="stat-card__meta"><span :class="metrics().form_submissions_delta >= 0 ? 'delta-up' : 'delta-down'">{{ delta(metrics().form_submissions_delta) }}</span> vs previous</p></article>
        <article class="stat-card"><div class="stat-card__label"><span>Leads</span></div><p class="stat-card__value">{{ number(metrics().leads) }}</p><p class="stat-card__meta">{{ metrics().conversion_rate }}% view-to-lead</p></article>
      </div>

      <section class="panel" style="margin-bottom:14px">
        <div class="panel__head">
          <div><p class="panel__title">Traffic</p><p class="mono muted" style="margin-top:3px">{{ data.range.days }} days · views and sessions</p></div>
          <div class="chart-legend"><span><i style="background:var(--s-accent)" /> Views</span><span><i style="background:var(--s-ink)" /> Visitors</span></div>
        </div>
        <div class="panel__body">
          <LineChart v-if="data.has_data" :series="data.series" :height="260" />
          <div v-else class="empty-s" style="border:0;background:transparent;padding:50px 0"><p class="empty-s__title">No events in this period</p><p class="empty-s__body">Analytics events appear only after a visitor consents.</p></div>
        </div>
      </section>

      <div class="s-two-col">
        <section class="panel">
          <div class="panel__head"><p class="panel__title">Top pages</p><span class="mono faint">VIEWS</span></div>
          <div class="table-wrap">
            <table v-if="data.top_pages?.length" class="table-s">
              <tbody><tr v-for="page in data.top_pages" :key="page.path"><td><div class="table-s__title">{{ page.title }}</div><div class="table-s__sub">{{ page.path }}</div></td><td class="nowrap" style="text-align:right">{{ number(page.views) }}</td><td class="nowrap muted" style="text-align:right">{{ number(page.visitors) }}</td></tr></tbody>
            </table>
            <p v-else class="table-empty">No page traffic.</p>
          </div>
        </section>

        <section class="panel">
          <div class="panel__head"><p class="panel__title">Sources & locales</p></div>
          <div class="panel__body">
            <p class="mono muted" style="margin-bottom:6px">SOURCES</p>
            <div v-for="source in data.sources" :key="source.source" class="bar-row"><span>{{ source.source }}</span><div class="bar-track"><div class="bar-fill" :style="{ width: `${Math.max(4, (source.views / (data.sources[0]?.views || 1)) * 100)}%` }" /></div><span class="mono" style="text-align:right">{{ source.views }}</span></div>
            <p v-if="!data.sources?.length" class="muted" style="font-size: 12px;padding:6px 0">No sources recorded.</p>
            <p class="mono muted" style="margin-top:14px;margin-bottom:6px">LOCALES</p>
            <div v-for="locale in data.locales" :key="locale.locale" class="bar-row"><span>{{ locale.locale }}</span><div class="bar-track"><div class="bar-fill bar-fill--accent" :style="{ width: `${Math.max(4, (locale.views / (data.locales[0]?.views || 1)) * 100)}%` }" /></div><span class="mono" style="text-align:right">{{ locale.views }}</span></div>
          </div>
        </section>
      </div>

      <div class="s-three-col" style="margin-top:14px">
        <section class="panel">
          <div class="panel__head"><p class="panel__title">Top articles</p></div>
          <div class="panel__body">
            <div v-for="article in data.top_articles" :key="article.path" class="list-row"><span>{{ article.title }}</span><strong>{{ article.views }}</strong></div>
            <p v-if="!data.top_articles?.length" class="muted" style="font-size: 12px">No article traffic.</p>
          </div>
        </section>
        <section class="panel">
          <div class="panel__head"><p class="panel__title">Search queries</p></div>
          <div class="panel__body">
            <div v-for="search in data.searches" :key="search.query" class="list-row"><span>{{ search.query }}</span><strong>{{ search.count }}</strong></div>
            <p v-if="!data.searches?.length" class="muted" style="font-size: 12px">No internal searches recorded.</p>
          </div>
        </section>
        <section class="panel">
          <div class="panel__head"><p class="panel__title">Devices</p></div>
          <div class="panel__body">
            <div v-for="device in data.devices" :key="device.device" class="list-row"><span>{{ device.device }}</span><strong>{{ device.views }}</strong></div>
            <p v-if="!data.devices?.length" class="muted" style="font-size: 12px">No device data.</p>
          </div>
        </section>
      </div>

      <div class="analytics-note">
        <span class="mono">METHOD</span>
        <p>{{ data.note }} External GA4, GSC, or GTM data is never substituted for first-party events and remains disconnected until credentials are configured and verified.</p>
      </div>
    </template>
  </div>
</template>

<style scoped>
.spin { animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.chart-legend { display: flex; gap: 12px; font-family: var(--font-mono); font-size: 8px; color: var(--s-faint); text-transform: uppercase; }
.chart-legend span { display: inline-flex; align-items: center; gap: 4px; }
.chart-legend i { width: 12px; height: 2px; display: inline-block; }
.list-row { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }
.list-row:last-child { border-bottom: 0; }
.analytics-note { margin-top: 14px; border: 1px solid var(--s-line); border-radius: var(--s-radius); padding: 10px 12px; display: flex; gap: 12px; align-items: flex-start; background: var(--s-surface); }
.analytics-note .mono { color: var(--s-accent); flex: 0 0 auto; font-size: 8px; }
.analytics-note p { font-size: 9.5px; line-height: 1.55; color: var(--s-muted); max-width: 100ch; }
</style>
