<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()
const route = useRoute()
const { localeCode, routeLocale } = useLocale()

const status = computed(() => props.error?.statusCode || 500)
const is404 = computed(() => status.value === 404)

const copy = computed(() => ({
  eyebrow: is404.value ? '404' : String(status.value),
  title: is404.value
    ? (localeCode.value === 'th' ? 'ไม่พบหน้าที่คุณกำลังมองหา' : localeCode.value === 'zh-CN' ? '未找到页面。' : 'This page is not here.')
    : (localeCode.value === 'th' ? 'เกิดข้อผิดพลาดบางอย่าง' : localeCode.value === 'zh-CN' ? '出现了问题。' : 'Something went wrong.'),
  body: is404.value
    ? (localeCode.value === 'th' ? 'ลิงก์อาจเปลี่ยนแปลง เนื้อหาอาจถูกย้าย หรือหน้านี้อาจไม่เปิดให้เข้าถึงแล้ว' : localeCode.value === 'zh-CN' ? '链接可能已更改、内容可能已迁移，或此页面不再可访问。' : 'The link may have changed, the content may have moved, or this page is no longer available.')
    : (localeCode.value === 'th' ? 'เราบันทึกปัญหาแล้ว กรุณาลองใหม่อีกครั้ง หรือกลับไปที่หน้าหลัก' : localeCode.value === 'zh-CN' ? '问题已记录。请重试或返回首页。' : 'The issue has been recorded. Try again, or return to the home page.'),
  home: localeCode.value === 'th' ? 'กลับหน้าหลัก' : localeCode.value === 'zh-CN' ? '返回首页' : 'Back to home',
  search: localeCode.value === 'th' ? 'ค้นหาเว็บไซต์' : localeCode.value === 'zh-CN' ? '搜索网站' : 'Search the site',
  contact: localeCode.value === 'th' ? 'ติดต่อเรา' : localeCode.value === 'zh-CN' ? '联系我们' : 'Contact',
}))

useHead({ title: `${status.value} — Meridian`, meta: [{ name: 'robots', content: 'noindex' }] })

function goHome() {
  clearError({ redirect: `/${routeLocale.value || 'th'}` })
}
</script>

<template>
  <div class="error-page">
    <div class="error-page__grid" aria-hidden="true" />
    <div class="container error-page__inner">
      <p class="eyebrow">{{ copy.eyebrow }}</p>
      <h1 class="error-page__title">{{ copy.title }}</h1>
      <p class="error-page__body">{{ copy.body }}</p>
      <div class="error-page__actions">
        <button type="button" class="btn btn-light" @click="goHome">{{ copy.home }}</button>
        <NuxtLink :to="`/${routeLocale || 'th'}/search`" class="btn btn-outline-light">{{ copy.search }}</NuxtLink>
        <NuxtLink :to="`/${routeLocale || 'th'}/contact`" class="error-page__contact">{{ copy.contact }}</NuxtLink>
      </div>
    </div>
    <div class="error-page__mark" aria-hidden="true">MERIDIAN</div>
  </div>
</template>

<style scoped>
.error-page {
  min-height: 100vh;
  background: var(--ink);
  color: var(--ivory);
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
}

.error-page__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(241, 237, 228, 0.055) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(241, 237, 228, 0.04) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%, 100% 88px;
}

.error-page__inner {
  position: relative;
  z-index: 2;
  padding-block: 8rem;
}

.error-page__title {
  margin-top: 1.5rem;
  font-size: clamp(3rem, 7vw, 7rem);
  line-height: 0.94;
  letter-spacing: -0.058em;
  font-weight: 500;
  max-width: 13ch;
}

.error-page__body {
  margin-top: 1.5rem;
  max-width: 54ch;
  font-size: clamp(1rem, 1.2vw, 1.15rem);
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.62);
}

.error-page__actions {
  margin-top: 2rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.error-page__contact {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(241, 237, 228, 0.4);
  padding-bottom: 0.25rem;
}

.error-page__mark {
  position: absolute;
  right: -0.05em;
  bottom: -0.18em;
  font-size: clamp(6rem, 22vw, 24rem);
  line-height: 0.75;
  letter-spacing: -0.08em;
  font-weight: 500;
  color: rgba(241, 237, 228, 0.035);
  pointer-events: none;
  user-select: none;
}
</style>
