<script setup lang="ts">
import type { Alternate } from '~/types'

const props = defineProps<{ locale: string }>()
const { localeCode, routeLocale, switchLocale } = useLocale()
const { alternates } = useLocale()

const labels = computed(() => ({
  notice: localeCode.value === 'th'
    ? 'เนื้อหานี้ยังไม่มีฉบับภาษาไทย จึงแสดงฉบับภาษาอังกฤษ'
    : localeCode.value === 'zh-CN'
      ? '此页面尚无中文版本，当前显示英文内容。'
      : 'This page is not available in the selected language yet.',
  switchLabel: localeCode.value === 'th' ? 'ดูต้นฉบับ' : localeCode.value === 'zh-CN' ? '查看原文' : 'View the original',
}))

const original = computed<Alternate | undefined>(() => alternates.value.find((item) => item.locale === props.locale))
const originalPath = computed(() => {
  try {
    return new URL(original.value?.url || '').pathname
  } catch {
    return '/'
  }
})
</script>

<template>
  <div class="fallback-notice" role="status">
    <div class="container fallback-notice__inner">
      <span class="fallback-notice__dot" aria-hidden="true" />
      <p>{{ labels.notice }}</p>
      <a v-if="original" :href="originalPath" class="fallback-notice__link">{{ labels.switchLabel }}</a>
    </div>
  </div>
</template>

<style scoped>
.fallback-notice {
  background: #e7e1d5;
  border-bottom: 1px solid var(--line);
}

.fallback-notice__inner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-block: 0.7rem;
  font-size: 0.75rem;
  color: var(--steel-dark);
  flex-wrap: wrap;
}

.fallback-notice__dot {
  width: 6px;
  height: 6px;
  background: var(--accent);
  flex: 0 0 auto;
}

.fallback-notice__link {
  margin-left: auto;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border-bottom: 1px solid var(--ink);
}
</style>
