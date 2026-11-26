<script setup lang="ts">
import type { Block, DocumentSummary } from '~/types'
const props = defineProps<{ block: Block }>()
const { localeCode } = useLocale()
const p = computed(() => props.block.props)
const items = computed<DocumentSummary[]>(() => props.block.resolved?.items ?? [])

const labels = computed(() => ({
  download: localeCode.value === 'th' ? 'ดาวน์โหลด' : localeCode.value === 'zh-CN' ? '下载' : 'Download',
  illustrative: localeCode.value === 'th' ? 'เอกสารตัวอย่าง' : localeCode.value === 'zh-CN' ? '示例文件' : 'Illustrative',
  empty: localeCode.value === 'th' ? 'ยังไม่มีเอกสาร' : localeCode.value === 'zh-CN' ? '暂无文件' : 'No documents yet.',
}))

const formatSize = (bytes?: number | null) => {
  if (!bytes) return ''
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
</script>

<template>
  <section class="section documents" :class="`documents--${p.theme || 'light'}`">
    <div class="container">
      <div class="documents__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 documents__heading">{{ p.heading }}</h2>
        </div>
      </div>

      <div v-if="items.length" class="documents__list">
        <a
          v-for="document in items"
          :key="document.id"
          :href="document.file_url || undefined"
          class="document-row"
          :class="{ 'is-disabled': !document.file_url }"
          target="_blank"
          rel="noopener"
          data-reveal
        >
          <span class="document-row__year mono">{{ document.year }}</span>
          <span class="document-row__main">
            <span class="document-row__title">{{ document.title }}</span>
            <span v-if="document.description" class="document-row__description">{{ document.description }}</span>
          </span>
          <span class="document-row__meta mono">
            <span v-if="document.is_illustrative">{{ labels.illustrative }}</span>
            <span>{{ document.file_type || 'PDF' }}</span>
            <span>{{ formatSize(document.file_size) }}</span>
          </span>
          <span class="document-row__download">
            <AppIcon name="download" :size="17" />
            <span class="visually-hidden">{{ labels.download }}</span>
          </span>
        </a>
      </div>
      <p v-else class="documents__empty">{{ labels.empty }}</p>
    </div>
  </section>
</template>

<style scoped>
.documents {
  background: var(--ivory);
}

.documents--muted {
  background: #e7e1d5;
}

.documents--dark {
  background: var(--ink);
  color: var(--ivory);
}

.documents__head {
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.documents__heading {
  margin-top: 1.4rem;
  max-width: 18ch;
}

.documents__list {
  border-top: 1px solid var(--line);
}

.documents--dark .documents__list {
  border-color: var(--line-light);
}

.document-row {
  display: grid;
  grid-template-columns: 4rem 1fr 12rem 2rem;
  gap: clamp(1rem, 2vw, 2rem);
  align-items: center;
  padding-block: 1.3rem;
  border-bottom: 1px solid var(--line);
  transition: padding-left 240ms var(--ease), color 180ms ease;
}

.documents--dark .document-row {
  border-color: var(--line-light);
}

.document-row:hover:not(.is-disabled) {
  padding-left: 0.5rem;
  color: var(--accent);
}

.document-row.is-disabled {
  opacity: 0.5;
  pointer-events: none;
}

.document-row__year {
  color: var(--steel);
  font-size: 0.75rem;
}

.document-row__main {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.document-row__title {
  font-size: clamp(1rem, 1.3vw, 1.2rem);
  letter-spacing: -0.02em;
  font-weight: 560;
}

.document-row__description {
  font-size: 0.8rem;
  line-height: 1.5;
  color: var(--steel-dark);
  max-width: 58ch;
}

.documents--dark .document-row__description {
  color: rgba(241, 237, 228, 0.5);
}

.document-row__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  color: var(--steel);
  font-size: 0.75rem;
}

.document-row__download {
  justify-self: end;
  color: var(--steel);
}

.document-row:hover .document-row__download {
  color: var(--accent);
}

.documents__empty {
  color: var(--steel);
  font-size: 0.9rem;
}

@media (max-width: 760px) {
  .document-row {
    grid-template-columns: 3.2rem 1fr 2rem;
  }

  .document-row__meta {
    grid-column: 2;
    margin-top: 0.35rem;
  }

  .document-row__download {
    grid-column: 3;
    grid-row: 1;
  }
}
</style>
