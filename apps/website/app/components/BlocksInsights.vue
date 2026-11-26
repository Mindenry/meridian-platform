<script setup lang="ts">
import type { ArticleSummary, Block } from '~/types'
const props = defineProps<{ block: Block }>()
const { localeCode, routeLocale } = useLocale()
const p = computed(() => props.block.props)
const items = computed<ArticleSummary[]>(() => props.block.resolved?.items ?? [])

const formatDate = (iso?: string | null) => {
  if (!iso) return ''
  const locale = localeCode.value === 'th' ? 'th-TH' : localeCode.value === 'zh-CN' ? 'zh-CN' : 'en-GB'
  return new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

const typeLabel = (type: string) => {
  const map: Record<string, Record<string, string>> = {
    insight: { en: 'Insight', th: 'บทความ', 'zh-CN': '洞察' },
    news: { en: 'News', th: 'ข่าว', 'zh-CN': '新闻' },
    technical: { en: 'Technical', th: 'เทคนิค', 'zh-CN': '技术' },
    announcement: { en: 'Announcement', th: 'ประกาศ', 'zh-CN': '公告' },
    report: { en: 'Report', th: 'รายงาน', 'zh-CN': '报告' },
  }
  return map[type]?.[localeCode.value] || map[type]?.en || type
}

const allLabel = computed(() => localeCode.value === 'th' ? 'บทความทั้งหมด' : localeCode.value === 'zh-CN' ? '全部洞察' : 'All insights')
const readLabel = computed(() => localeCode.value === 'th' ? 'นาที' : localeCode.value === 'zh-CN' ? '分钟阅读' : 'min read')
</script>

<template>
  <section class="section insights" :class="`insights--${p.theme || 'light'}`">
    <div class="container">
      <div class="insights__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 insights__heading">{{ p.heading }}</h2>
        </div>
        <NuxtLink :to="`/${routeLocale}/insights`" class="text-link" data-reveal>
          {{ allLabel }}
          <AppIcon name="arrow-right" :size="13" />
        </NuxtLink>
      </div>

      <div v-if="items.length" class="insights__grid">
        <NuxtLink
          v-for="(article, index) in items"
          :key="article.id"
          :to="article.url"
          class="article-card"
          :class="{ 'article-card--lead': index === 0 && items.length <= 3 }"
          data-reveal
        >
          <div class="article-card__media">
            <AppImage v-if="article.image" :image="article.image" :ratio="index === 0 ? '16:9' : '3:2'" :sizes="index === 0 ? '(max-width: 900px) 100vw, 55vw' : '(max-width: 900px) 100vw, 30vw'" />
          </div>
          <div class="article-card__meta mono">
            <span>{{ typeLabel(article.type) }}</span>
            <span>{{ formatDate(article.published_at) }}</span>
          </div>
          <h3 class="article-card__title">{{ article.title }}</h3>
          <p class="article-card__excerpt">{{ article.excerpt }}</p>
          <div class="article-card__footer mono">
            <span>{{ article.reading_time }} {{ readLabel }}</span>
            <span v-if="article.author">{{ article.author.name }}</span>
          </div>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.insights {
  background: var(--ivory);
}

.insights--muted {
  background: #e7e1d5;
}

.insights--dark {
  background: var(--ink);
  color: var(--ivory);
}

.insights__head {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 2rem;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.insights__heading {
  margin-top: 1.4rem;
  max-width: 17ch;
}

.insights--dark .text-link {
  border-color: var(--line-light-strong);
}

.insights__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.5rem, 2.5vw, 2.5rem);
  counter-reset: article;
}

.article-card {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  min-width: 0;
  counter-increment: article;
}

.article-card--lead {
  grid-column: span 2;
  grid-row: span 2;
}

.article-card__media :deep(img) {
  transition: transform 700ms var(--ease), filter 500ms ease;
}

.article-card:hover .article-card__media :deep(img) {
  transform: scale(1.025);
  filter: grayscale(0.35) contrast(1.05) brightness(0.95);
}

.article-card__meta {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.6rem;
}

.article-card__meta::before {
  content: counter(article, decimal-leading-zero);
  font-family: var(--font-mono);
  color: var(--accent);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  margin-right: 0.65rem;
}

.article-card__meta > :first-child {
  margin-left: 0;
}

.insights--dark .article-card__meta {
  border-color: var(--line-light);
  color: rgba(241, 237, 228, 0.42);
}

.article-card__title {
  font-size: clamp(1.25rem, 1.9vw, 1.8rem);
  line-height: 1.16;
  letter-spacing: -0.012em;
  font-weight: 500;
  max-width: 26ch;
  transition: color 160ms ease;
}

.article-card--lead .article-card__title {
  font-size: clamp(1.8rem, 3vw, 2.9rem);
  max-width: 22ch;
}

.article-card:hover .article-card__title {
  color: var(--accent);
}

.article-card__excerpt {
  font-size: 0.85rem;
  line-height: 1.55;
  color: var(--steel-dark);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.insights--dark .article-card__excerpt {
  color: rgba(241, 237, 228, 0.55);
}

.article-card__footer {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: auto;
  padding-top: 0.8rem;
  color: var(--steel);
  font-size: 0.75rem;
}

.insights--dark .article-card__footer {
  color: rgba(241, 237, 228, 0.42);
}

@media (max-width: 900px) {
  .insights__head {
    flex-direction: column;
    align-items: flex-start;
  }

  .insights__grid {
    grid-template-columns: 1fr;
  }

  .article-card--lead {
    grid-column: auto;
    grid-row: auto;
  }
}
</style>
