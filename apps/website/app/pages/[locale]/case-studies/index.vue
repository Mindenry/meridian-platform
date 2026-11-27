<script setup lang="ts">
import type { CaseStudySummary, IndustrySummary, PaginationMeta } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const pageCopy = computed(() => ({
  en: {
    eyebrow: 'Case studies',
    title: 'Work measured in operating outcomes.',
    body: 'Illustrative projects showing how a constraint becomes a system, and a system becomes a measurable change. All outcomes are demonstration data.',
    meta: ['FICTIONAL PORTFOLIO', 'ILLUSTRATIVE OUTCOMES'],
    filterIndustry: 'Industry',
    searchPlaceholder: 'Search case studies',
    featured: 'Featured project',
    clear: 'Clear filters',
    showing: 'projects',
  },
  th: {
    eyebrow: 'ผลงาน',
    title: 'งานที่วัดผลจากการดำเนินงาน',
    body: 'โครงการตัวอย่างที่แสดงว่าข้อจำกัดกลายเป็นระบบ และระบบกลายเป็นการเปลี่ยนแปลงที่วัดได้ ผลลัพธ์ทั้งหมดเป็นข้อมูลสาธิต',
    meta: ['พอร์ตโฟลิโอสมมติ', 'ผลลัพธ์ตัวอย่าง'],
    filterIndustry: 'อุตสาหกรรม',
    searchPlaceholder: 'ค้นหาผลงาน',
    featured: 'โครงการเด่น',
    clear: 'ล้างตัวกรอง',
    showing: 'โครงการ',
  },
  'zh-CN': {
    eyebrow: '案例',
    title: '以运营成果衡量的工作。',
    body: '展示约束如何成为系统、系统如何成为可衡量变化的示例项目。所有成果均为演示数据。',
    meta: ['虚构作品集', '示例成果'],
    filterIndustry: '行业',
    searchPlaceholder: '搜索案例',
    featured: '精选项目',
    clear: '清除筛选',
    showing: '项目',
  },
})[localeCode.value]!)

const filters = computed(() => ({
  industry: String(route.query.industry || ''),
  q: String(route.query.q || ''),
  page: Math.max(1, Number(route.query.page || 1)),
}))

const { data, error } = await useAsyncData(
  `case-studies-${routeLocale.value}-${JSON.stringify(filters.value)}`,
  () => api.get<{ data: CaseStudySummary[]; meta: PaginationMeta; filters: { industries: IndustrySummary[] } }>('/public/case-studies', {
    query: { locale: localeCode.value, industry: filters.value.industry, q: filters.value.q, page: filters.value.page, per_page: 9 },
  }),
  { watch: [filters, routeLocale] },
)

if (error.value) throw createError({ statusCode: 503, statusMessage: 'Case studies are unavailable.' })

const items = computed(() => data.value?.data ?? [])
const meta = computed(() => data.value?.meta)
const industries = computed(() => data.value?.filters?.industries ?? [])
const searchTerm = ref(filters.value.q)

watch(filters, () => { searchTerm.value = filters.value.q })

function applyFilters() {
  const query: Record<string, string> = {}
  if (filters.value.industry) query.industry = filters.value.industry
  if (searchTerm.value.trim()) query.q = searchTerm.value.trim()
  navigateTo({ path: `/${routeLocale.value}/case-studies`, query })
}

function setIndustry(key: string) {
  const query: Record<string, string> = {}
  if (key) query.industry = key
  if (filters.value.q) query.q = filters.value.q
  navigateTo({ path: `/${routeLocale.value}/case-studies`, query })
}

function goToPage(page: number) {
  navigateTo({
    path: `/${routeLocale.value}/case-studies`,
    query: {
      ...(filters.value.industry ? { industry: filters.value.industry } : {}),
      ...(filters.value.q ? { q: filters.value.q } : {}),
      ...(page > 1 ? { page: String(page) } : {}),
    },
  })
}

useSimpleSeo({
  title: localeCode.value === 'th' ? 'ผลงาน — เมอริเดียน' : localeCode.value === 'zh-CN' ? '案例 — Meridian' : 'Case studies — Meridian Industrial Group',
  description: pageCopy.value.body,
  path: `/${routeLocale.value}/case-studies`,
})
</script>

<template>
  <div>
    <PageHeader :eyebrow="pageCopy.eyebrow" :title="pageCopy.title" :body="pageCopy.body" :meta="pageCopy.meta" />

    <section class="case-index">
      <div class="container">
        <div class="case-filters">
          <div class="case-filters__group">
            <span class="mono">{{ pageCopy.filterIndustry }}</span>
            <div class="case-filters__chips">
              <button type="button" class="filter-chip" :class="{ 'is-active': !filters.industry }" @click="setIndustry('')">{{ copy.all }}</button>
              <button
                v-for="industry in industries"
                :key="industry.key"
                type="button"
                class="filter-chip"
                :class="{ 'is-active': filters.industry === industry.key }"
                @click="setIndustry(industry.key)"
              >
                {{ industry.title }}
              </button>
            </div>
          </div>

          <form class="case-search" role="search" @submit.prevent="applyFilters">
            <label for="case-search" class="visually-hidden">{{ pageCopy.searchPlaceholder }}</label>
            <input id="case-search" v-model="searchTerm" type="search" :placeholder="pageCopy.searchPlaceholder" class="input">
            <button type="submit" class="case-search__button" :aria-label="copy.search">
              <AppIcon name="search" :size="17" />
            </button>
          </form>
        </div>

        <div v-if="items.length" class="case-grid">
          <NuxtLink
            v-for="(item, index) in items"
            :key="item.id"
            :to="item.url"
            class="case-card"
            :class="{ 'case-card--featured': index === 0 && filters.page === 1 && !filters.q && !filters.industry }"
            data-reveal
          >
            <div class="case-card__media">
              <AppImage v-if="item.image" :image="item.image" :ratio="index === 0 ? '16:9' : '4:3'" :sizes="index === 0 ? '(max-width: 900px) 100vw, 62vw' : '(max-width: 900px) 100vw, 40vw'" />
              <span class="case-card__label mono">{{ index === 0 && filters.page === 1 ? pageCopy.featured : item.industry?.title }}</span>
            </div>
            <div class="case-card__body">
              <div class="case-card__meta mono">
                <span>{{ item.year }}</span>
                <span>{{ item.industry?.title }}</span>
              </div>
              <h2 class="case-card__title">{{ item.title }}</h2>
              <p class="case-card__summary">{{ item.summary }}</p>
              <div v-if="item.results?.length" class="case-card__results">
                <div v-for="result in item.results.slice(0, 2)" :key="result.label">
                  <span class="case-card__value">{{ result.value }}</span>
                  <span class="case-card__result-label">{{ result.label }}</span>
                </div>
              </div>
              <span class="text-link">{{ copy.readMore }} <AppIcon name="arrow-up-right" :size="13" /></span>
            </div>
          </NuxtLink>
        </div>

        <div v-else class="empty-state">
          <p>{{ copy.noResults }}</p>
          <button type="button" class="btn btn-ghost" @click="navigateTo(`/${routeLocale}/case-studies`)">{{ pageCopy.clear }}</button>
        </div>

        <nav v-if="meta && meta.last_page > 1" class="pagination" aria-label="Pagination">
          <button type="button" class="pagination__button" :disabled="meta.current_page <= 1" @click="goToPage(meta.current_page - 1)">
            {{ copy.previous }}
          </button>
          <span class="mono">{{ copy.page }} {{ meta.current_page }} {{ copy.of }} {{ meta.last_page }}</span>
          <button type="button" class="pagination__button" :disabled="meta.current_page >= meta.last_page" @click="goToPage(meta.current_page + 1)">
            {{ copy.next }}
          </button>
        </nav>
      </div>
    </section>

    <SiteCta
      :heading="localeCode === 'th' ? 'ผลงานที่ดีที่สุดคือระบบที่ลูกค้าดูแลต่อได้' : localeCode === 'zh-CN' ? '最好的成果，是客户能够继续运营的系统。' : 'The best outcome is a system the client can keep running.'"
      :body="localeCode === 'th' ? 'พูดคุยเกี่ยวกับโจทย์ของคุณ และเราจะช่วยประเมินจุดเริ่มต้นที่เหมาะสม' : localeCode === 'zh-CN' ? '谈谈您的问题，我们会帮助判断合适的起点。' : 'Talk through your problem and we will help identify a sensible starting point.'"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
      :secondary-label="copy.allSolutions"
      :secondary-to="`/${routeLocale}/solutions`"
    />
  </div>
</template>

<style scoped>
.case-index {
  background: var(--ivory);
  padding-block: clamp(2rem, 4vw, 3.5rem) var(--section-y);
}

.case-filters {
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-end;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--line);
  margin-bottom: 2rem;
}

.case-filters__group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.case-filters__group > .mono {
  color: var(--steel);
}

.case-filters__chips {
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

.case-search {
  display: flex;
  align-items: center;
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.72);
  box-shadow: var(--shadow-xs);
  min-width: 280px;
}

.case-search .input {
  border: 0;
  background: transparent;
  box-shadow: none;
}

.case-search__button {
  border: 0;
  background: transparent;
  padding-inline: 1rem;
  color: var(--steel);
}

.case-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.75rem, 3vw, 3rem) 1.5rem;
}

.case-card {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
  min-width: 0;
}

.case-card--featured {
  grid-column: span 2;
  grid-row: span 2;
}

.case-card__media {
  position: relative;
}

.case-card__label {
  position: absolute;
  left: 0.75rem;
  top: 0.75rem;
  padding: 0.25rem 0.45rem;
  background: var(--ivory);
  color: var(--ink);
  font-size: 0.75rem;
}

.case-card__meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.55rem;
}

.case-card__title {
  font-size: clamp(1.15rem, 1.7vw, 1.5rem);
  line-height: 1.14;
  letter-spacing: -0.03em;
  font-weight: 550;
  max-width: 24ch;
}

.case-card--featured .case-card__title {
  font-size: clamp(1.7rem, 2.8vw, 2.8rem);
  max-width: 20ch;
}

.case-card:hover .case-card__title {
  color: var(--accent);
}

.case-card__summary {
  font-size: 0.85rem;
  line-height: 1.55;
  color: var(--steel-dark);
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.case-card__results {
  display: flex;
  gap: 2rem;
  padding-block: 0.75rem;
  border-top: 1px solid var(--line);
}

.case-card__value {
  display: block;
  font-size: 1.4rem;
  letter-spacing: -0.04em;
}

.case-card__result-label {
  display: block;
  font-size: 0.75rem;
  color: var(--steel);
}

.case-card .text-link {
  align-self: flex-start;
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

.pagination__button {
  border: 0;
  background: transparent;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border-bottom: 1px solid var(--ink);
  padding: 0.3rem 0;
}

.pagination__button:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

@media (max-width: 980px) {
  .case-filters {
    flex-direction: column;
    align-items: flex-start;
  }

  .case-search {
    width: 100%;
    min-width: 0;
  }

  .case-grid {
    grid-template-columns: 1fr 1fr;
  }

  .case-card--featured {
    grid-column: 1 / -1;
    grid-row: auto;
  }
}

@media (max-width: 600px) {
  .case-grid {
    grid-template-columns: 1fr;
  }
}
</style>
