<script setup lang="ts">
import type { PageData } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const config = useRuntimeConfig()

const token = computed(() => {
  const param = route.params.token
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `preview-${token.value}`,
  () => api.get<{ data: PageData }>(`/public/preview/${encodeURIComponent(token.value)}`),
  { watch: [token] },
)

if (error.value) {
  throw createError({ statusCode: 404, statusMessage: 'This preview link is invalid or has expired.' })
}

const page = computed<PageData | null>(() => data.value?.data ?? null)
usePageSeo(page)

useHead(() => ({
  title: page.value ? `Preview — ${page.value.title}` : 'Preview',
  meta: [{ name: 'robots', content: 'noindex,nofollow' }],
}))

const labels = computed(() => ({
  preview: localeCode.value === 'th' ? 'โหมดตัวอย่าง' : localeCode.value === 'zh-CN' ? '预览模式' : 'Preview mode',
  note: localeCode.value === 'th' ? 'หน้านี้ยังไม่เผยแพร่ และไม่ถูกจัดทำดัชนี' : localeCode.value === 'zh-CN' ? '此页面未发布，也不会被索引。' : 'This page is unpublished and is not indexed.',
  studio: localeCode.value === 'th' ? 'กลับไปที่สตูดิโอ' : localeCode.value === 'zh-CN' ? '返回 Studio' : 'Back to Studio',
  close: localeCode.value === 'th' ? 'ออกจากโหมดตัวอย่าง' : localeCode.value === 'zh-CN' ? '退出预览' : 'Exit preview',
}))
</script>

<template>
  <div v-if="page" class="preview-page">
    <div class="preview-bar">
      <div class="container preview-bar__inner">
        <span class="preview-bar__badge mono">{{ labels.preview }}</span>
        <span class="preview-bar__note">{{ labels.note }}</span>
        <span class="preview-bar__spacer" />
        <a :href="config.public.studioUrl" class="preview-bar__link">{{ labels.studio }}</a>
        <NuxtLink :to="`/${routeLocale}`" class="preview-bar__link">{{ labels.close }}</NuxtLink>
      </div>
    </div>

    <FallbackNotice v-if="page.is_fallback" :locale="page.content_locale" />
    <BlockRenderer v-for="block in page.blocks" :key="block.uid" :block="block" />
  </div>
</template>

<style scoped>
.preview-bar {
  background: var(--accent);
  color: var(--ivory);
  position: sticky;
  top: 0;
  z-index: 80;
}

.preview-bar__inner {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-block: 0.6rem;
  flex-wrap: wrap;
}

.preview-bar__badge {
  border: 1px solid rgba(241, 237, 228, 0.5);
  padding: 0.2rem 0.4rem;
  font-size: 0.75rem;
}

.preview-bar__note {
  font-size: 0.7rem;
  opacity: 0.85;
}

.preview-bar__spacer {
  flex: 1;
}

.preview-bar__link {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(241, 237, 228, 0.5);
  padding-bottom: 0.1rem;
}

.preview-bar__link:hover {
  opacity: 0.75;
}
</style>
