<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Globe2, Plus, ExternalLink, Check, Server, Users } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import BaseModal from '@/components/BaseModal.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'

const loading = ref(true)
const sites = ref<any[]>([])
const createOpen = ref(false)
const saving = ref(false)
const form = ref({
  name: '',
  legal_name: '',
  slug: '',
  domain: '',
  default_locale: 'en',
  enabled_locales: ['en'],
})

const canCreate = computed(() => auth.user.value?.is_super_admin || auth.can('sites.manage'))
const currentSiteId = computed(() => auth.user.value?.current_site_id)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/sites')
    sites.value = data.data
  } finally {
    loading.value = false
  }
}

function switchSite(site: any) {
  auth.setSite(site.id)
  window.location.reload()
}

function toggleLocale(code: string) {
  form.value.enabled_locales = form.value.enabled_locales.includes(code)
    ? form.value.enabled_locales.filter((item) => item !== code)
    : [...form.value.enabled_locales, code]
}

async function create() {
  saving.value = true
  try {
    await api.post('/sites', {
      ...form.value,
      domain: form.value.domain || null,
      enabled_locales: form.value.enabled_locales,
    })
    toast.success('Website created')
    createOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not create website', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="PLATFORM" title="Websites" description="Every website in this installation has independent content, locales, navigation, and access rules.">
      <template #actions>
        <button v-if="canCreate" type="button" class="btn-s btn-s--primary" @click="createOpen = true"><Plus :size="14" /> New website</button>
      </template>
    </PageHead>

    <div v-if="loading" class="skeleton" style="height:320px" />
    <div v-else class="site-grid">
      <article v-for="site in sites" :key="site.id" class="panel site-card" :class="{ 'is-current': site.id === currentSiteId }">
        <div class="panel__body">
          <div class="flex-between"><span class="site-card__icon"><Globe2 :size="18" /></span><span v-if="site.is_primary" class="badge badge--accent">PRIMARY</span><span v-else-if="site.id === currentSiteId" class="badge badge--published">CURRENT</span></div>
          <h2 class="site-card__name">{{ site.name }}</h2>
          <p class="mono faint">{{ site.domain || `${site.slug}.meridian.example` }}</p>
          <div class="site-card__stats">
            <div><span>Pages</span><strong>{{ site.pages_count }}</strong></div>
            <div><span>Published</span><strong>{{ site.published_count }}</strong></div>
            <div><span>People</span><strong>{{ site.users_count }}</strong></div>
          </div>
          <div class="site-card__locales"><span v-for="locale in site.enabled_locales" :key="locale" class="badge badge--neutral">{{ locale }}</span></div>
          <div class="flex-s site-card__actions">
            <button v-if="site.id !== currentSiteId" type="button" class="btn-s btn-s--sm" @click="switchSite(site)"><Check :size="12" /> Switch to site</button>
            <RouterLink to="/settings" class="btn-s btn-s--sm"><Server :size="12" /> Settings</RouterLink>
            <a :href="`http://localhost:3002/${site.default_locale === 'th' ? 'th' : site.default_locale === 'zh-CN' ? 'zh-cn' : 'en'}`" target="_blank" class="btn-s btn-s--ghost btn-s--icon btn-s--sm"><ExternalLink :size="12" /></a>
          </div>
        </div>
      </article>
    </div>

    <BaseModal :open="createOpen" title="Create website" :wide="true" @close="createOpen = false">
      <div class="stack-s">
        <div class="two-fields-s">
          <div class="field-s"><label class="field-s__label">Name</label><input v-model="form.name" class="input-s" @input="!form.slug && (form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''))"></div>
          <div class="field-s"><label class="field-s__label">Legal name</label><input v-model="form.legal_name" class="input-s"></div>
        </div>
        <div class="two-fields-s">
          <div class="field-s"><label class="field-s__label">Slug</label><input v-model="form.slug" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Domain</label><input v-model="form.domain" class="input-s" placeholder="example.com"></div>
        </div>
        <div class="field-s"><label class="field-s__label">Default locale</label><select v-model="form.default_locale" class="select-s"><option value="en">English</option><option value="th">ไทย</option><option value="zh-CN">简体中文</option></select></div>
        <div>
          <p class="mono muted" style="margin-bottom:6px">ENABLED LOCALES</p>
          <label v-for="locale in [{ code: 'en', label: 'English' }, { code: 'th', label: 'ไทย' }, { code: 'zh-CN', label: '简体中文' }]" :key="locale.code" class="checkbox-s" style="padding:4px 0">
            <input type="checkbox" :checked="form.enabled_locales.includes(locale.code)" @change="toggleLocale(locale.code)">
            <span>{{ locale.label }}</span>
          </label>
        </div>
        <p class="mono faint">The creator receives a site administrator role for the new website.</p>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="createOpen = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !form.name || !form.slug || !form.enabled_locales.length" @click="create">{{ saving ? 'Creating…' : 'Create website' }}</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.site-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.site-card.is-current { border-color: var(--s-accent); }
.site-card__icon { width: 34px; height: 34px; display: grid; place-items: center; border: 1px solid var(--s-line); color: var(--s-accent); }
.site-card__name { margin-top: 14px; font-size: 19px; letter-spacing: -.03em; font-weight: 600; }
.site-card__stats { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid var(--s-line); border-bottom: 1px solid var(--s-line); margin-block: 12px; }
.site-card__stats > div { padding: 8px 0; }
.site-card__stats span { display: block; font-family: var(--font-mono); font-size: 7.5px; text-transform: uppercase; color: var(--s-faint); }
.site-card__stats strong { display: block; font-size: 17px; letter-spacing: -.03em; }
.site-card__locales { display: flex; gap: 4px; margin-bottom: 12px; flex-wrap: wrap; }
.site-card__actions { flex-wrap: wrap; }
.two-fields-s { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
@media (max-width: 1100px) { .site-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 700px) { .site-grid, .two-fields-s { grid-template-columns: 1fr; } }
</style>
