<script setup lang="ts">
const { bootstrap } = await useBootstrap()
const { localeCode, routeLocale } = useLocale()
const { trackPageView } = useAnalytics()

useReveal()

watch(
  () => useRoute().fullPath,
  () => {
    nextTick(() => trackPageView())
  },
  { immediate: true },
)

const skipLabel = computed(() =>
  localeCode.value === 'th' ? 'ข้ามไปยังเนื้อหา' : localeCode.value === 'zh-CN' ? '跳到主要内容' : 'Skip to content',
)

useHead(() => ({
  htmlAttrs: { lang: localeCode.value },
  link: [
    {
      rel: 'alternate',
      type: 'application/rss+xml',
      title: 'Meridian insights',
      href: `/${routeLocale.value}/rss.xml`,
    },
  ],
  script: bootstrap.value?.organization
    ? [{ type: 'application/ld+json', innerHTML: JSON.stringify(bootstrap.value.organization).replace(/</g, '\\u003c'), key: 'organization' }]
    : [],
}))
</script>

<template>
  <div class="shell">
    <NuxtLoadingIndicator color="#f0521f" :height="2" />
    <a class="skip-link" href="#main-content">{{ skipLabel }}</a>
    <AppHeader :bootstrap="bootstrap" />
    <main id="main-content">
      <slot />
    </main>
    <AppFooter :bootstrap="bootstrap" />
    <SearchOverlay />
    <CookieBanner />
    <CookiePreferences />
  </div>
</template>
