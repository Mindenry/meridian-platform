<script setup lang="ts">
import type { ArticleSummary, PaginationMeta } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const pageCopy = computed(() => ({
  en: {
    eyebrow: 'Insights',
    title: 'Field notes from industrial practice.',
    body: 'Technical observations, project lessons, and company announcements from the people doing the work. Written plainly, without the vendor theatre.',
    meta: ['INSIGHTS', 'NEWS', 'TECHNICAL NOTES'],
    filterCategory: 'Category',
    searchPlaceholder: 'Search insights',
  },
  th: {
    eyebrow: 'บทความ',
    title: 'บันทึกภาคสนามจากงานอุตสาหกรรม',
    body: 'ข้อสังเกตทางเทคนิค บทเรียนจากโครงการ และประกาศองค์กรจากคนที่ลงมือทำงานจริง เขียนอย่างตรงไปตรงมา โดยไม่มีการแสดงเกินจริง',
    meta: ['บทความ', 'ข่าว', 'บันทึกเทคนิค'],
    filterCategory: 'หมวดหมู่',
    searchPlaceholder: 'ค้นหาบทความ',
  },
  'zh-CN': {
    eyebrow: '洞察',
    title: '来自工业实践的现场笔记。',
    body: '来自一线人员的技术观察、项目经验与公司公告。直白书写，不做供应商表演。',
    meta: ['洞察', '新闻', '技术笔记'],
    filterCategory: '分类',
    searchPlaceholder: '搜索洞察',
  },
})[localeCode.value]!)

const filters = computed(() => ({
  category: String(route.query.category || ''),
  q: String(route.query.q || ''),
  page: Math.max(1, Number(route.query.page || 1)),
}))

const { data, error } = await useAsyncData(
  `articles-${routeLocale.value}-${JSON.stringify(filters.value)}`,
  () => api.get<{ data: ArticleSummary[]; meta: PaginationMeta; filters: { categories: Array<{ slug: string; name: string }>; types: string[] } }>('/public/articles', {
    query: { locale: localeCode.value, category: filters.value.category, q: filters.value.q, page: filters.value.page, per_page: 9 },
  }),
  { watch: [filters, routeLocale] },
)

if (error.value) throw createError({ statusCode: 503, statusMessage: 'Insights are unavailable.' })

const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)
const categories = computed(() => data.value?.filters?.categories ?? [])
const searchTerm = ref(filters.value.q)

watch(filters, () => { searchTerm.value = filters.value.q })

function setCategory(slug: string) {
  const query: Record<string, string> = {}
  if (slug) query.category = slug
  if (filters.value.q) query.q = filters.value.q
  navigateTo({ path: `/${routeLocale.value}/insights`, query })
}

function applySearch() {
  const query: Record<string, string> = {}
  if (filters.value.category) query.category = filters.value.category
  if (searchTerm.value.trim()) query.q = searchTerm.value.trim()
  navigateTo({ path: `/${routeLocale.value}/insights`, query })
}

function goToPage(page: number) {
  navigateTo({
    path: `/${routeLocale.value}/insights`,
    query: {
      ...(filters.value.category ? { category: filters.value.category } : {}),
      ...(filters.value.q ? { q: filters.value.q } : {}),
      ...(page > 1 ? { page: String(page) } : {}),
    },
  })
}

const formatDate = (iso: string) => new Intl.DateTimeFormat(
  localeCode.value === 'th' ? 'th-TH' : localeCode.value === 'zh-CN' ? 'zh-CN' : 'en-GB',
  { day: 'numeric', month: 'short', year: 'numeric' },
).format(new Date(iso))

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

useSimpleSeo({
  title: localeCode.value === 'th' ? 'บทความ — เมอริเดียน' : localeCode.value === 'zh-CN' ? '洞察 — Meridian' : 'Insights — Meridian Industrial Group',
  description: pageCopy.value.body,
  path: `/${routeLocale.value}/insights`,
})
</script>

<template>
  <div>
    <PageHeader :eyebrow="pageCopy.eyebrow" :title="pageCopy.title" :body="pageCopy.body" :meta="pageCopy.meta" />

    <section class="insights-index">
      <div class="container">
        <div class="insights-filters">
          <div class="insights-filters__group">
            <span class="mono">{{ pageCopy.filterCategory }}</span>
            <div class="insights-filters__chips">
              <button type="button" class="filter-chip" :class="{ 'is-active': !filters.category }" @click="setCategory('')">{{ copy.all }}</button>
              <button
                v-for="category in categories"
                :key="category.slug"
                type="button"
                class="filter-chip"
                :class="{ 'is-active': filters.category === category.slug }"
                @click="setCategory(category.slug)"
              >
                {{ category.name }}
              </button>
            </div>
          </div>

          <form class="article-search" role="search" @submit.prevent="applySearch">
            <label for="article-search" class="visually-hidden">{{ pageCopy.searchPlaceholder }}</label>
            <input id="article-search" v-model="searchTerm" type="search" :placeholder="pageCopy.searchPlaceholder" class="input">
            <button type="submit" :aria-label="copy.search"><AppIcon name="search" :size="17" /></button>
          </form>
        </div>

        <div v-if="items.length" class="insights-grid">
          <NuxtLink
            v-for="(article, index) in items"
            :key="article.id"
            :to="article.url"
            class="insight-card"
            :class="{ 'insight-card--lead': index === 0 && filters.page === 1 && !filters.q && !filters.category }"
            data-reveal
          >
            <div class="insight-card__media">
              <AppImage v-if="article.image" :image="article.image" :ratio="index === 0 ? '16:9' : '3:2'" :sizes="index === 0 ? '(max-width: 900px) 100vw, 62vw' : '(max-width: 900px) 100vw, 40vw'" />
            </div>
            <div class="insight-card__meta mono">
              <span>{{ typeLabel(article.type) }}</span>
              <span>{{ formatDate(article.published_at) }}</span>
            </div>
            <h2 class="insight-card__title">{{ article.title }}</h2>
            <p class="insight-card__excerpt">{{ article.excerpt }}</p>
            <div class="insight-card__footer mono">
              <span>{{ article.reading_time }} {{ localeCode === 'th' ? 'นาที' : localeCode === 'zh-CN' ? '分钟' : 'min read' }}</span>
              <span>{{ article.category?.name }}</span>
            </div>
          </NuxtLink>
        </div>

        <div v-else class="empty-state">
          <p>{{ copy.noResults }}</p>
          <button type="button" class="btn btn-ghost" @click="navigateTo(`/${routeLocale}/insights`)">{{ copy.clear }}</button>
        </div>

        <nav v-if="meta && meta.last_page > 1" class="pagination" aria-label="Pagination">
          <button type="button" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">{{ copy.previous }}</button>
          <span class="mono">{{ copy.page }} {{ meta.current_page }} {{ copy.of }} {{ meta.last_page }}</span>
          <button type="button" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">{{ copy.next }}</button>
        </nav>
      </div>
    </section>
  </div>
</template>

<style scoped>
.insights-index {
  background: var(--ivory);
  padding-block: clamp(2rem, 4vw, 3.5rem) var(--section-y);
}

.insights-filters {
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-end;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--line);
  margin-bottom: 2rem;
}

.insights-filters__group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.insights-filters__group > .mono {
  color: var(--steel);
}

.insights-filters__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.filter-chip {
  border: 1px solid var(--glass-border);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.55);
  padding: 0.5rem 0.9rem;
  font-size: 0.78rem;
  box-shadow: var(--shadow-xs);
  transition: border-color 160ms ease, background 160ms ease, color 160ms ease, box-shadow 200ms ease;
}

.filter-chip:hover,
.filter-chip.is-active {
  background: var(--ink);
  border-color: var(--ink);
  color: var(--ivory);
  box-shadow: var(--shadow-sm);
}

.article-search {
  display: flex;
  align-items: center;
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.72);
  box-shadow: var(--shadow-xs);
  min-width: 280px;
}

.article-search .input {
  border: 0;
  background: transparent;
  box-shadow: none;
}

.article-search button {
  border: 0;
  background: transparent;
  padding-inline: 1rem;
  color: var(--steel);
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.75rem, 3vw, 3rem) 1.5rem;
}

.insight-card {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  min-width: 0;
}

.insight-card--lead {
  grid-column: span 2;
  grid-row: span 2;
}

.insight-card__meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.55rem;
}

.insight-card__title {
  font-size: clamp(1.1rem, 1.6vw, 1.4rem);
  line-height: 1.16;
  letter-spacing: -0.03em;
  font-weight: 550;
  max-width: 26ch;
}

.insight-card--lead .insight-card__title {
  font-size: clamp(1.7rem, 2.8vw, 2.7rem);
  max-width: 20ch;
}

.insight-card:hover .insight-card__title {
  color: var(--accent);
}

.insight-card__excerpt {
  font-size: 0.85rem;
  line-height: 1.55;
  color: var(--steel-dark);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.insight-card__footer {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-top: auto;
  padding-top: 0.6rem;
  color: var(--steel);
  font-size: 0.75rem;
}

.empty-state {
  padding-block: 4rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: flex-start;
  color: var(--steel-dark);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1.5rem;
  margin-top: 3rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--line);
}

.pagination button {
  border: 0;
  background: transparent;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border-bottom: 1px solid var(--ink);
  padding: 0.3rem 0;
}

.pagination button:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

@media (max-width: 980px) {
  .insights-filters {
    flex-direction: column;
    align-items: flex-start;
  }

  .article-search {
    width: 100%;
    min-width: 0;
  }

  .insights-grid {
    grid-template-columns: 1fr 1fr;
  }

  .insight-card--lead {
    grid-column: 1 / -1;
    grid-row: auto;
  }
}

@media (max-width: 600px) {
  .insights-grid {
    grid-template-columns: 1fr;
  }
}
</style>
