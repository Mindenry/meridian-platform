<script setup lang="ts">
import type { PageData } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()

const slug = computed(() => {
  const param = route.params.page
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `page-${routeLocale.value}-${slug.value}`,
  () => api.get<{ data: PageData }>(`/public/pages/${encodeURIComponent(slug.value)}`, {
    query: { locale: localeCode.value },
  }),
  { watch: [slug, routeLocale] },
)

if (error.value) {
  throw createError({ statusCode: 404, statusMessage: 'Page not found' })
}

const page = computed<PageData | null>(() => data.value?.data ?? null)
usePageSeo(page)

const hasHero = computed(() => page.value?.blocks?.some((block) => ['hero', 'split_hero'].includes(block.type)) ?? false)
</script>

<template>
  <div v-if="page">
    <h1 v-if="!hasHero" class="visually-hidden">{{ page.title }}</h1>
    <FallbackNotice v-if="page.is_fallback" :locale="page.content_locale" />
    <BlockRenderer v-for="block in page.blocks" :key="block.uid" :block="block" />
  </div>
</template>
