<script setup lang="ts">
import type { SearchGroup } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()
const { track } = useAnalytics()

const query = ref(String(route.query.q || ''))
const submitted = ref(String(route.query.q || ''))
const groups = ref<SearchGroup[]>([])
const loading = ref(false)
const total = ref(0)
const hasSearched = ref(false)

const pageCopy = computed(() => ({
  en: {
    eyebrow: 'Search',
    title: 'Find the work, the idea, or the role.',
    body: 'Search across pages, solutions, industries, case studies, insights, and open positions.',
    placeholder: 'Search the site',
    popular: 'Popular searches',
    noResults: 'No results. Try a broader term or explore the sections below.',
    resultsFor: 'Results for',
    allLabel: 'All results',
  },
  th: {
    eyebrow: 'ค้นหา',
    title: 'ค้นหางาน ความคิด หรือตำแหน่งที่ใช่',
    body: 'ค้นหาข้ามหน้าโซลูชัน อุตสาหกรรม ผลงาน บทความ และตำแหน่งงานที่เปิดอยู่',
    placeholder: 'ค้นหาเว็บไซต์',
    popular: 'คำค้นยอดนิยม',
    noResults: 'ไม่พบผลลัพธ์ ลองใช้คำที่กว้างขึ้นหรือสำรวจส่วนด้านล่าง',
    resultsFor: 'ผลลัพธ์สำหรับ',
    allLabel: 'ผลลัพธ์ทั้งหมด',
  },
  'zh-CN': {
    eyebrow: '搜索',
    title: '找到工作、观点或职位。',
    body: '搜索页面、解决方案、行业、案例、洞察与开放职位。',
    placeholder: '搜索网站',
    popular: '热门搜索',
    noResults: '没有结果。请尝试更宽泛的关键词或浏览下方栏目。',
    resultsFor: '搜索结果：',
    allLabel: '全部结果',
  },
})[localeCode.value]!)

const popular = computed(() => localeCode.value === 'th'
  ? ['ระบบอัตโนมัติ', 'โรงงานอัจฉริยะ', 'พลังงาน', 'ผลงาน']
  : localeCode.value === 'zh-CN'
    ? ['自动化', '智能制造', '能源', '案例']
    : ['automation', 'smart manufacturing', 'energy', 'case studies'])

const typeLabel = (type: string) => {
  const map: Record<string, Record<string, string>> = {
    page: { en: 'Page', th: 'หน้า', 'zh-CN': '页面' },
    solution: { en: 'Solution', th: 'โซลูชัน', 'zh-CN': '解决方案' },
    industry: { en: 'Industry', th: 'อุตสาหกรรม', 'zh-CN': '行业' },
    case_study: { en: 'Case study', th: 'ผลงาน', 'zh-CN': '案例' },
    article: { en: 'Insight', th: 'บทความ', 'zh-CN': '洞察' },
    job: { en: 'Role', th: 'ตำแหน่งงาน', 'zh-CN': '职位' },
  }
  return map[type]?.[localeCode.value] || map[type]?.en || type
}

async function runSearch(term: string) {
  const value = term.trim()
  if (value.length < 2) {
    groups.value = []
    total.value = 0
    hasSearched.value = false
    return
  }

  loading.value = true
  hasSearched.value = true
  try {
    const response = await api.get<{ data: { groups: SearchGroup[]; total: number } }>('/public/search', {
      query: { q: value, locale: localeCode.value, limit: 12 },
    })
    groups.value = response.data.groups
    total.value = response.data.total
  } finally {
    loading.value = false
  }
}

function submitSearch() {
  const term = query.value.trim()
  if (term.length < 2) return
  submitted.value = term
  track('search', { query: term })
  navigateTo({ path: `/${routeLocale.value}/search`, query: { q: term } })
  runSearch(term)
}

function setTerm(term: string) {
  query.value = term
  submitSearch()
}

await runSearch(submitted.value)

watch(
  () => route.query.q,
  (value) => {
    const term = String(value || '')
    if (term !== submitted.value) {
      submitted.value = term
      query.value = term
      runSearch(term)
    }
  },
)

useSimpleSeo({
  title: localeCode.value === 'th' ? 'ค้นหา — เมอริเดียน' : localeCode.value === 'zh-CN' ? '搜索 — Meridian' : 'Search — Meridian Industrial Group',
  description: pageCopy.value.body,
  path: `/${routeLocale.value}/search`,
})
</script>

<template>
  <div>
    <PageHeader :eyebrow="pageCopy.eyebrow" :title="pageCopy.title" :body="pageCopy.body" />

    <section class="search-page">
      <div class="container">
        <form class="search-page__form" role="search" @submit.prevent="submitSearch">
          <label for="site-search" class="visually-hidden">{{ pageCopy.placeholder }}</label>
          <input id="site-search" v-model="query" type="search" :placeholder="pageCopy.placeholder" autofocus>
          <button type="submit">
            {{ copy.search }}
            <AppIcon name="arrow-right" :size="18" />
          </button>
        </form>

        <div class="search-page__popular">
          <span class="mono">{{ pageCopy.popular }}</span>
          <button v-for="term in popular" :key="term" type="button" @click="setTerm(term)">{{ term }}</button>
        </div>

        <div v-if="loading" class="search-page__state">
          <span class="spinner" />
          {{ localeCode === 'th' ? 'กำลังค้นหา…' : localeCode === 'zh-CN' ? '搜索中…' : 'Searching…' }}
        </div>

        <div v-else-if="hasSearched && total === 0" class="search-page__state">
          <p>{{ pageCopy.noResults }}</p>
        </div>

        <div v-else-if="hasSearched" class="search-page__results">
          <div class="search-page__summary">
            <p class="mono">{{ pageCopy.resultsFor }} "{{ submitted }}"</p>
            <p class="mono">{{ total }} {{ copy.showing.toLowerCase() }}</p>
          </div>

          <section v-for="group in groups" :key="group.type" class="result-group">
            <div class="result-group__head">
              <h2 class="h4">{{ group.label }}</h2>
              <span class="mono">{{ group.items.length }}</span>
            </div>
            <ul class="result-list">
              <li v-for="item in group.items" :key="item.url">
                <NuxtLink :to="item.url" class="result-item">
                  <span class="result-item__type mono">{{ typeLabel(item.type) }}</span>
                  <span class="result-item__title" v-html="item.title" />
                  <span class="result-item__excerpt" v-html="item.excerpt" />
                  <AppIcon name="arrow-up-right" :size="16" />
                </NuxtLink>
              </li>
            </ul>
          </section>
        </div>

        <div v-else class="search-page__empty">
          <p class="mono">{{ localeCode === 'th' ? 'เริ่มต้นสำรวจ' : localeCode === 'zh-CN' ? '开始探索' : 'Start exploring' }}</p>
          <div class="search-page__links">
            <NuxtLink :to="`/${routeLocale}/solutions`">{{ copy.allSolutions }} <AppIcon name="arrow-right" :size="13" /></NuxtLink>
            <NuxtLink :to="`/${routeLocale}/case-studies`">{{ copy.allCaseStudies }} <AppIcon name="arrow-right" :size="13" /></NuxtLink>
            <NuxtLink :to="`/${routeLocale}/insights`">{{ copy.allInsights }} <AppIcon name="arrow-right" :size="13" /></NuxtLink>
            <NuxtLink :to="`/${routeLocale}/careers`">{{ copy.careers }} <AppIcon name="arrow-right" :size="13" /></NuxtLink>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.search-page {
  background: var(--ivory);
  padding-block: clamp(2rem, 4vw, 3.5rem) var(--section-y);
}

.search-page__form {
  display: flex;
  border-bottom: 2px solid var(--ink);
  padding-bottom: 0.5rem;
}

.search-page__form input {
  flex: 1;
  border: 0;
  background: transparent;
  font-size: clamp(1.6rem, 3.8vw, 3.4rem);
  letter-spacing: -0.04em;
  padding: 0.5rem 0;
  min-width: 0;
}

.search-page__form input:focus {
  outline: none;
}

.search-page__form button {
  border: 0;
  background: transparent;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.search-page__popular {
  margin-top: 1.25rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem 1.1rem;
  align-items: center;
}

.search-page__popular .mono {
  color: var(--steel);
}

.search-page__popular button {
  border: 1px solid var(--line);
  background: transparent;
  padding: 0.4rem 0.65rem;
  font-size: 0.75rem;
}

.search-page__popular button:hover {
  border-color: var(--ink);
  color: var(--accent);
}

.search-page__state {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-block: 4rem;
  color: var(--steel-dark);
}

.spinner {
  width: 16px;
  height: 16px;
  border: 1px solid var(--line);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.search-page__results {
  margin-top: 2.5rem;
}

.search-page__summary {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: var(--steel);
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--line);
}

.result-group {
  margin-top: 2rem;
}

.result-group__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.result-group__head .mono {
  color: var(--steel);
}

.result-list {
  list-style: none;
  border-top: 1px solid var(--line);
}

.result-item {
  display: grid;
  grid-template-columns: 7rem 1fr auto;
  gap: 0.35rem 1rem;
  padding-block: 1rem;
  border-bottom: 1px solid var(--line);
  transition: padding-left 220ms var(--ease), color 160ms ease;
}

.result-item:hover {
  padding-left: 0.5rem;
  color: var(--accent);
}

.result-item__type {
  color: var(--steel);
  font-size: 0.75rem;
  padding-top: 0.2rem;
}

.result-item__title {
  font-size: 1.05rem;
  font-weight: 600;
}

.result-item__excerpt {
  grid-column: 2;
  font-size: 0.82rem;
  line-height: 1.5;
  color: var(--steel-dark);
}

.result-item :deep(mark) {
  background: rgba(227, 109, 63, 0.18);
  color: inherit;
}

.result-item > svg {
  grid-row: 1 / span 2;
  align-self: center;
}

.search-page__empty {
  padding-block: 3rem;
}

.search-page__empty > .mono {
  color: var(--steel);
  margin-bottom: 1rem;
}

.search-page__links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.search-page__links a {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid var(--line);
  padding: 0.7rem 0.85rem;
  font-size: 0.85rem;
}

.search-page__links a:hover {
  background: var(--ink);
  color: var(--ivory);
  border-color: var(--ink);
}

@media (max-width: 700px) {
  .result-item {
    grid-template-columns: 1fr auto;
  }

  .result-item__type {
    grid-column: 1 / -1;
  }

  .result-item__excerpt {
    grid-column: 1 / -1;
  }

  .result-item > svg {
    grid-column: 2;
    grid-row: 1;
  }
}
</style>
