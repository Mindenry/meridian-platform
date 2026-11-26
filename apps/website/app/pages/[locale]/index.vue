<script setup lang="ts">
import type { PageData } from '~/types'

const api = useApi()
const { routeLocale, localeCode } = useLocale()

const { data, error } = await useAsyncData(
  `home-${routeLocale.value}`,
  () => api.get<{ data: PageData }>('/public/home', { query: { locale: localeCode.value } }),
  { watch: [routeLocale] },
)

if (error.value) {
  throw createError({ statusCode: 503, statusMessage: 'The website content service is unavailable.' })
}

const page = computed<PageData | null>(() => data.value?.data ?? null)
usePageSeo(page)
</script>

<template>
  <div v-if="page">
    <FallbackNotice v-if="page.is_fallback" :locale="page.content_locale" />
    <BlockRenderer v-for="block in page.blocks" :key="block.uid" :block="block" />
  </div>
</template>
