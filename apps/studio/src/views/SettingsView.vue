<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Save, Globe2, Palette, Search, PlugZap, CheckCircle2, AlertTriangle, RefreshCw } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const loading = ref(true)
const saving = ref(false)
const tab = ref<'general' | 'brand' | 'seo' | 'integrations'>('general')
const site = ref<any>(null)
const locales = ref<any[]>([])
const catalogue = ref<any>({})
const integrations = ref<any[]>([])
const settings = ref<any>({})
const brand = ref<any>({})
const theme = ref<any>({})
const seo = ref<any>({})
const integrationForms = ref<Record<string, any>>({})
const testing = ref<string | null>(null)

const canManage = computed(() => auth.can('settings.manage'))
const integrationLabels: Record<string, string> = {
  google_search_console: 'Google Search Console',
  google_analytics_4: 'Google Analytics 4',
  google_tag_manager: 'Google Tag Manager',
}

const contact = computed(() => settings.value?.contact || {})

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/settings')
    site.value = data.data.site
    locales.value = data.data.locales
    catalogue.value = data.data.locale_catalogue
    integrations.value = data.data.integrations
    settings.value = JSON.parse(JSON.stringify(data.data.site.settings || {}))
    brand.value = JSON.parse(JSON.stringify(data.data.site.brand || {}))
    theme.value = JSON.parse(JSON.stringify(data.data.site.theme || {}))
    seo.value = JSON.parse(JSON.stringify(data.data.site.seo_defaults || {}))
    for (const integration of integrations.value) {
      integrationForms.value[integration.provider] = { client_id: '', client_secret: '', property_id: '', container_id: '', service_account: '' }
    }
  } catch (err) {
    toast.error('Could not load settings', apiErrorMessage(err))
  } finally {
    loading.value = false
  }
}

function toggleLocale(code: string) {
  const exists = locales.value.find((locale) => locale.locale === code)
  if (exists) {
    exists.is_enabled = !exists.is_enabled
  } else {
    locales.value.push({ locale: code, label: catalogue.value[code]?.label || code, is_default: false, is_enabled: true })
  }
}

function enabledLocaleCodes() {
  return locales.value.filter((locale) => locale.is_enabled).map((locale) => locale.locale)
}

async function save() {
  saving.value = true
  try {
    await api.patch('/settings', {
      name: site.value.name,
      legal_name: site.value.legal_name,
      domain: site.value.domain || null,
      default_locale: site.value.default_locale,
      enabled_locales: enabledLocaleCodes(),
      brand: brand.value,
      theme: theme.value,
      seo_defaults: seo.value,
      settings: settings.value,
    })
    toast.success('Site settings saved')
    await load()
  } catch (err) {
    toast.error('Could not save settings', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function saveIntegration(provider: string) {
  try {
    const form = integrationForms.value[provider] || {}
    const credentials = Object.fromEntries(Object.entries(form).filter(([, value]) => value))
    await api.put(`/integrations/${provider}`, { credentials })
    toast.success('Integration settings stored')
    await load()
  } catch (err) {
    toast.error('Could not save integration', apiErrorMessage(err))
  }
}

async function testIntegration(provider: string) {
  testing.value = provider
  try {
    const { data } = await api.post(`/integrations/${provider}/test`)
    if (data.status === 'unverified') toast.info('Credentials found', data.message)
    else toast.error('Not connected', data.message)
    await load()
  } catch (err) {
    toast.error('Could not test integration', apiErrorMessage(err))
  } finally {
    testing.value = null
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="CONFIGURATION" title="Site settings" description="Website identity, locales, brand tokens, SEO defaults, and integration boundaries.">
      <template #actions>
        <button v-if="canManage" type="button" class="btn-s btn-s--primary" :disabled="saving" @click="save"><Save :size="13" /> {{ saving ? 'Saving…' : 'Save settings' }}</button>
      </template>
    </PageHead>

    <div v-if="loading" class="skeleton" style="height:420px" />
    <template v-else>
      <div class="settings-tabs">
        <button type="button" :class="{ 'is-active': tab === 'general' }" @click="tab = 'general'"><Globe2 :size="13" /> General</button>
        <button type="button" :class="{ 'is-active': tab === 'brand' }" @click="tab = 'brand'"><Palette :size="13" /> Brand</button>
        <button type="button" :class="{ 'is-active': tab === 'seo' }" @click="tab = 'seo'"><Search :size="13" /> SEO defaults</button>
        <button type="button" :class="{ 'is-active': tab === 'integrations' }" @click="tab = 'integrations'"><PlugZap :size="13" /> Integrations</button>
      </div>

      <div v-if="tab === 'general'" class="s-two-col">
        <section class="panel"><div class="panel__head"><p class="panel__title">Website identity</p></div><div class="panel__body stack-s">
          <div class="field-s"><label class="field-s__label">Site name</label><input v-model="site.name" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Legal display name</label><input v-model="site.legal_name" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Domain</label><input v-model="site.domain" class="input-s" placeholder="meridian.example"></div>
          <div class="field-s"><label class="field-s__label">Default locale</label><select v-model="site.default_locale" class="select-s"><option v-for="(meta, code) in catalogue" :key="code" :value="code">{{ meta.label }} ({{ code }})</option></select></div>
        </div></section>

        <section class="panel"><div class="panel__head"><p class="panel__title">Locales</p><span class="mono faint">URL prefix</span></div><div class="panel__body">
          <label v-for="(meta, code) in catalogue" :key="code" class="locale-row">
            <input type="checkbox" :checked="locales.find(l => l.locale === code)?.is_enabled" @change="toggleLocale(String(code))">
            <span><strong>{{ meta.label }}</strong><small>/{{ meta.route }} · {{ code }}</small></span>
            <span v-if="site.default_locale === code" class="badge badge--accent">DEFAULT</span>
          </label>
          <p class="mono faint" style="margin-top:10px">Every enabled locale is independently indexable. Missing translations fall back without generating false hreflang links.</p>
        </div></section>
      </div>

      <div v-if="tab === 'brand'" class="s-two-col">
        <section class="panel"><div class="panel__head"><p class="panel__title">Brand tokens</p></div><div class="panel__body stack-s">
          <div class="field-s"><label class="field-s__label">Logo text</label><input v-model="brand.logo_text" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Descriptor</label><input v-model="brand.descriptor" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Tagline</label><input v-model="brand.tagline" class="input-s"></div>
          <div class="color-grid">
            <div class="field-s"><label class="field-s__label">Accent</label><div class="color-field"><input v-model="brand.accent" type="color"><input v-model="brand.accent" class="input-s"></div></div>
            <div class="field-s"><label class="field-s__label">Ink</label><div class="color-field"><input v-model="brand.ink" type="color"><input v-model="brand.ink" class="input-s"></div></div>
            <div class="field-s"><label class="field-s__label">Paper</label><div class="color-field"><input v-model="brand.paper" type="color"><input v-model="brand.paper" class="input-s"></div></div>
            <div class="field-s"><label class="field-s__label">Steel</label><div class="color-field"><input v-model="brand.steel" type="color"><input v-model="brand.steel" class="input-s"></div></div>
          </div>
        </div></section>

        <section class="panel"><div class="panel__head"><p class="panel__title">Live tokens</p></div><div class="panel__body">
          <div class="brand-preview" :style="{ background: brand.paper || '#f5f3ee', color: brand.ink || '#101d2b' }">
            <div class="brand-preview__bar"><span :style="{ background: brand.accent || '#e36d3f' }" /><span>{{ brand.descriptor }}</span></div>
            <p class="brand-preview__word">{{ brand.logo_text || 'MERIDIAN' }}</p>
            <p class="brand-preview__tag">{{ brand.tagline }}</p>
            <div class="brand-preview__swatches"><span v-for="color in [brand.ink, brand.accent, brand.steel, brand.paper]" :key="color" :style="{ background: color }" /></div>
          </div>
          <p class="mono faint" style="margin-top:10px">Theme values are consumed by the public website through the bootstrap payload.</p>
        </div></section>
      </div>

      <div v-if="tab === 'seo'" class="panel"><div class="panel__body stack-s">
        <div class="field-s"><label class="field-s__label">Default title</label><input v-model="seo.title" class="input-s"></div>
        <div class="field-s"><label class="field-s__label">Default description</label><textarea v-model="seo.description" class="textarea-s" rows="3" /></div>
        <div class="field-s"><label class="field-s__label">Default Open Graph image URL</label><input v-model="seo.og_image_url" class="input-s"></div>
        <div class="field-s"><label class="field-s__label">Organisation schema name</label><input v-model="seo.organization_name" class="input-s"></div>
        <p class="mono faint">Sitemap, hreflang, canonicals, and structured data are generated from published content. Missing values fall back to page or site defaults.</p>
      </div></div>

      <div v-if="tab === 'integrations'" class="stack-s">
        <div v-for="integration in integrations" :key="integration.provider" class="panel">
          <div class="panel__head">
            <div><p class="panel__title">{{ integrationLabels[integration.provider] || integration.label }}</p><p class="mono faint" style="margin-top:2px">{{ integration.required_env.join(' · ') }}</p></div>
            <StatusBadge :status="integration.status" />
          </div>
          <div class="panel__body integration-body">
            <div class="integration-note">
              <AlertTriangle :size="14" />
              <p>Credentials are encrypted at rest. No live provider call is made by this demo; the connection remains <strong>unverified</strong> until OAuth is completed in the provider console.</p>
            </div>
            <div class="integration-form">
              <div class="field-s"><label class="field-s__label">Client ID / property</label><input v-model="integrationForms[integration.provider].client_id" class="input-s"></div>
              <div class="field-s"><label class="field-s__label">Client secret / service account</label><input v-model="integrationForms[integration.provider].client_secret" type="password" class="input-s"></div>
            </div>
            <div class="flex-s">
              <button type="button" class="btn-s" :disabled="!canManage" @click="saveIntegration(integration.provider)">Store credentials</button>
              <button type="button" class="btn-s btn-s--ghost" :disabled="testing === integration.provider" @click="testIntegration(integration.provider)"><RefreshCw :size="12" :class="{ spin: testing === integration.provider }" /> Test boundary</button>
              <span v-if="integration.last_checked_at" class="mono faint">Checked {{ new Date(integration.last_checked_at).toLocaleString('en-GB') }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.settings-tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--s-line); margin-bottom: 14px; }
.settings-tabs button { border: 0; background: transparent; display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; font-size: 12px; color: var(--s-muted); border-bottom: 2px solid transparent; }
.settings-tabs button.is-active { color: var(--s-ink); border-bottom-color: var(--s-ink); font-weight: 650; }
.locale-row { display: flex; align-items: center; gap: 9px; padding: 8px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }
.locale-row input { accent-color: var(--s-accent); }
.locale-row span:nth-child(2) { flex: 1; }
.locale-row strong { display: block; font-size: 12px; }
.locale-row small { display: block; font-family: var(--font-mono); font-size: 8px; color: var(--s-faint); margin-top: 1px; }
.color-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.color-field { display: flex; gap: 5px; }
.color-field input[type="color"] { width: 38px; height: 38px; border: 1px solid var(--s-line); background: var(--s-white); padding: 3px; border-radius: var(--s-radius); }
.brand-preview { border: 1px solid var(--s-line); border-radius: var(--s-radius); padding: 20px; min-height: 220px; display: flex; flex-direction: column; }
.brand-preview__bar { display: flex; align-items: center; gap: 7px; font-family: var(--font-mono); font-size: 7px; letter-spacing: .1em; }
.brand-preview__bar span:first-child { width: 14px; height: 3px; }
.brand-preview__word { margin-top: auto; font-size: 36px; font-weight: 700; letter-spacing: -.04em; }
.brand-preview__tag { font-size: 12px; opacity: .65; margin-top: 4px; }
.brand-preview__swatches { display: flex; gap: 4px; margin-top: 16px; }
.brand-preview__swatches span { width: 24px; height: 12px; border: 1px solid rgba(0,0,0,.1); }
.integration-body { display: flex; flex-direction: column; gap: 12px; }
.integration-note { display: flex; gap: 8px; align-items: flex-start; background: var(--s-amber-soft); border: 1px solid #e6d2a8; border-radius: var(--s-radius); padding: 9px 10px; color: #71500f; font-size: 12px; line-height: 1.45; }
.integration-form { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.spin { animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 700px) { .color-grid, .integration-form { grid-template-columns: 1fr; } }
</style>
