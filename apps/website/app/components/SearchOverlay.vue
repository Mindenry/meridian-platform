<script setup lang="ts">
import type { SearchGroup } from '~/types'

const { isOpen, query, groups, loading, error, close } = useSearch()
const { localeCode, routeLocale } = useLocale()
const { track } = useAnalytics()
const api = useApi()
const input = ref<HTMLInputElement | null>(null)
const shell = ref<HTMLElement | null>(null)
const recent = ref<string[]>([])
const suggestions = ref<Array<{ title: string; url: string; type: string }>>([])
let previousFocus: HTMLElement | null = null

const RECENT_KEY = 'meridian-search-recent'

const labels = computed(() => ({
  title: localeCode.value === 'th' ? 'ค้นหา' : localeCode.value === 'zh-CN' ? '搜索' : 'Search',
  placeholder: localeCode.value === 'th' ? 'ค้นหาโซลูชัน ผลงาน บทความ หรือตำแหน่งงาน' : localeCode.value === 'zh-CN' ? '搜索解决方案、案例、文章或职位' : 'Search solutions, case studies, insights, or roles',
  empty: localeCode.value === 'th' ? 'พิมพ์อย่างน้อยสองตัวอักษร' : localeCode.value === 'zh-CN' ? '输入至少两个字符' : 'Type at least two characters',
  none: localeCode.value === 'th' ? 'ไม่พบผลลัพธ์ ลองใช้คำที่กว้างขึ้น' : localeCode.value === 'zh-CN' ? '没有结果，请尝试更宽泛的关键词。' : 'No results. Try a broader term.',
  noneHelp: localeCode.value === 'th' ? 'ลองค้นหาด้วยคำเดียว หรือดูคำยอดนิยมด้านล่าง' : localeCode.value === 'zh-CN' ? '尝试使用单个词，或查看下方的热门搜索。' : 'Try a single keyword, or start from a popular search below.',
  popular: localeCode.value === 'th' ? 'คำค้นยอดนิยม' : localeCode.value === 'zh-CN' ? '热门搜索' : 'Popular searches',
  recent: localeCode.value === 'th' ? 'ค้นหาล่าสุด' : localeCode.value === 'zh-CN' ? '最近搜索' : 'Recent searches',
  clearRecent: localeCode.value === 'th' ? 'ล้างประวัติ' : localeCode.value === 'zh-CN' ? '清除记录' : 'Clear history',
  closest: localeCode.value === 'th' ? 'ใกล้เคียงที่สุด' : localeCode.value === 'zh-CN' ? '最接近的结果' : 'Closest matches',
  viewAll: localeCode.value === 'th' ? 'ดูผลลัพธ์ทั้งหมด' : localeCode.value === 'zh-CN' ? '查看全部结果' : 'View all results',
  contact: localeCode.value === 'th' ? 'ถามทีมงานโดยตรง' : localeCode.value === 'zh-CN' ? '直接联系团队' : 'Ask the team directly',
  close: localeCode.value === 'th' ? 'ปิด' : localeCode.value === 'zh-CN' ? '关闭' : 'Close',
  searching: localeCode.value === 'th' ? 'กำลังค้นหา…' : localeCode.value === 'zh-CN' ? '搜索中…' : 'Searching…',
}))

const popular = computed(() => localeCode.value === 'th'
  ? ['ระบบอัตโนมัติ', 'โรงงานอัจฉริยะ', 'โครงสร้างพื้นฐาน', 'พลังงาน']
  : localeCode.value === 'zh-CN'
    ? ['自动化', '智能制造', '基础设施', '能源']
    : ['automation', 'smart manufacturing', 'infrastructure', 'energy'])

const total = computed(() => groups.value.reduce((sum, group) => sum + group.items.length, 0))
const announcement = computed(() => {
  if (loading.value) return labels.value.searching
  if (query.value.trim().length < 2) return ''
  return total.value === 0
    ? labels.value.none
    : `${total.value} ${localeCode.value === 'th' ? 'ผลลัพธ์' : localeCode.value === 'zh-CN' ? '个结果' : total.value === 1 ? 'result' : 'results'}`
})

function loadRecent() {
  if (!import.meta.client) return
  try {
    const stored = JSON.parse(window.localStorage.getItem(RECENT_KEY) || '[]')
    recent.value = Array.isArray(stored) ? stored.filter((item) => typeof item === 'string').slice(0, 5) : []
  } catch {
    recent.value = []
  }
}

function remember(term: string) {
  if (!import.meta.client) return
  const value = term.trim()
  if (value.length < 2) return
  recent.value = [value, ...recent.value.filter((item) => item.toLowerCase() !== value.toLowerCase())].slice(0, 5)
  window.localStorage.setItem(RECENT_KEY, JSON.stringify(recent.value))
}

function clearRecent() {
  recent.value = []
  if (import.meta.client) window.localStorage.removeItem(RECENT_KEY)
}

const runSearch = useDebounceFn(async () => {
  const term = query.value.trim()
  suggestions.value = []

  if (term.length < 2) {
    groups.value = []
    error.value = null
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await api.get<{ data: { groups: SearchGroup[] } }>('/public/search', {
      query: { q: term, locale: localeCode.value, limit: 8 },
    })
    groups.value = response.data.groups

    if (!groups.value.some((group) => group.items.length) && term.length >= 3) {
      const broader = term.split(/\s+/)[0]
      const fallback = await api.get<{ data: Array<{ title: string; url: string; type: string }> }>(
        '/public/search/suggestions',
        { query: { q: broader, locale: localeCode.value } },
      ).catch(() => null)

      suggestions.value = fallback?.data ?? []
    }
  } catch {
    error.value = labels.value.none
    groups.value = []
  } finally {
    loading.value = false
  }
}, 220)

watch(query, () => runSearch())

watch(isOpen, async (open) => {
  if (!import.meta.client) return
  document.documentElement.style.overflow = open ? 'hidden' : ''
  if (open) {
    previousFocus = document.activeElement as HTMLElement | null
    loadRecent()
    await nextTick()
    input.value?.focus()
  } else {
    previousFocus?.focus?.()
    previousFocus = null
  }
})

function closeOverlay() {
  close()
}

function setTerm(term: string) {
  query.value = term
  nextTick(() => input.value?.focus())
}

function submitSearch() {
  const term = query.value.trim()
  if (term.length < 2) return
  remember(term)
  track('search', { query: term })
  navigateTo(`/${routeLocale.value}/search?q=${encodeURIComponent(term)}`)
  close()
}

function openResult(item: { url: string }) {
  remember(query.value)
  close()
  navigateTo(item.url)
}

function focusable(): HTMLElement[] {
  if (!shell.value) return []
  return [...shell.value.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled])')]
    .filter((element) => element.offsetParent !== null)
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    close()
    return
  }

  if (event.key !== 'Tab') return

  const items = focusable()
  if (!items.length) return

  const first = items[0]
  const last = items[items.length - 1]

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="search">
      <div v-if="isOpen" class="search-overlay" role="dialog" aria-modal="true" aria-labelledby="search-title" @keydown="onKeydown">
        <button type="button" class="search-overlay__backdrop" :aria-label="labels.close" tabindex="-1" @click="closeOverlay" />
        <div ref="shell" class="search-shell">
          <div class="search-head">
            <p id="search-title" class="eyebrow">MERIDIAN / {{ labels.title }}</p>
            <button type="button" class="search-close" :aria-label="labels.close" @click="closeOverlay">
              <AppIcon name="close" :size="22" />
            </button>
          </div>

          <form class="search-form" role="search" @submit.prevent="submitSearch">
            <AppIcon name="search" :size="26" class="search-form__icon" />
            <input
              ref="input"
              v-model="query"
              type="search"
              class="search-input"
              :placeholder="labels.placeholder"
              :aria-label="labels.placeholder"
              autocomplete="off"
              spellcheck="false"
            >
            <button type="submit" class="search-submit" :disabled="query.trim().length < 2" :aria-label="labels.title">
              <AppIcon name="arrow-right" :size="20" />
            </button>
          </form>

          <p class="visually-hidden" aria-live="polite">{{ announcement }}</p>

          <div class="search-body">
            <div v-if="loading" class="search-state">
              <span class="search-spinner" /> {{ labels.searching }}
            </div>

            <div v-else-if="query.trim().length < 2" class="search-empty">
              <div v-if="recent.length" class="search-recent">
                <div class="search-empty__head">
                  <p class="mono muted">{{ labels.recent }}</p>
                  <button type="button" class="search-clear" @click="clearRecent">{{ labels.clearRecent }}</button>
                </div>
                <div class="search-chips">
                  <button v-for="term in recent" :key="term" type="button" class="search-chip" @click="setTerm(term)">{{ term }}</button>
                </div>
              </div>

              <div>
                <p class="mono muted">{{ labels.popular }}</p>
                <div class="search-chips">
                  <button v-for="term in popular" :key="term" type="button" class="search-chip" @click="setTerm(term)">{{ term }}</button>
                </div>
              </div>
            </div>

            <div v-else-if="error" class="search-state">{{ error }}</div>

            <div v-else-if="total === 0" class="search-empty">
              <div class="search-state">{{ labels.none }}</div>
              <p class="search-hint">{{ labels.noneHelp }}</p>

              <div v-if="suggestions.length" class="search-closest">
                <p class="mono muted">{{ labels.closest }}</p>
                <ul class="search-group__list">
                  <li v-for="item in suggestions" :key="item.url">
                    <NuxtLink :to="item.url" class="search-result search-result--compact" @click="openResult(item)">
                      <span class="search-result__title">{{ item.title }}</span>
                      <AppIcon name="arrow-up-right" :size="15" />
                    </NuxtLink>
                  </li>
                </ul>
              </div>

              <div class="search-chips">
                <button v-for="term in popular" :key="term" type="button" class="search-chip" @click="setTerm(term)">{{ term }}</button>
              </div>

              <NuxtLink :to="`/${routeLocale}/contact`" class="search-contact" @click="close">{{ labels.contact }}</NuxtLink>
            </div>

            <div v-else class="search-groups">
              <section v-for="group in groups" :key="group.type" class="search-group">
                <p class="search-group__label mono">{{ group.label }}</p>
                <ul class="search-group__list">
                  <li v-for="item in group.items" :key="item.url">
                    <NuxtLink :to="item.url" class="search-result" @click="openResult(item)">
                      <span class="search-result__title" v-html="item.title" />
                      <span class="search-result__excerpt" v-html="item.excerpt" />
                      <AppIcon name="arrow-up-right" :size="15" />
                    </NuxtLink>
                  </li>
                </ul>
              </section>
              <button type="button" class="search-all" @click="submitSearch">
                {{ labels.viewAll }}
                <AppIcon name="arrow-right" :size="15" />
              </button>
            </div>
          </div>

          <div class="search-foot mono">
            <span>ESC {{ labels.close }}</span>
            <span>⌘K / CTRL K</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.search-overlay {
  position: fixed;
  inset: 0;
  z-index: 120;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: clamp(1rem, 5vh, 4rem) 1rem;
}

.search-overlay__backdrop {
  position: absolute;
  inset: 0;
  background: rgba(14, 20, 17, 0.78);
  backdrop-filter: blur(8px);
  border: 0;
}

.search-shell {
  position: relative;
  width: min(980px, 100%);
  max-height: 88vh;
  background: var(--ivory);
  border: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.search-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--line);
}

.search-close {
  border: 0;
  background: transparent;
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  color: var(--ink);
}

.search-close:hover {
  color: var(--accent-text);
}

.search-form {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border-bottom: 1px solid var(--line);
}

.search-form__icon {
  color: var(--steel);
  flex: 0 0 auto;
}

.search-input {
  flex: 1;
  border: 0;
  background: transparent;
  font-size: clamp(1.35rem, 3vw, 2.3rem);
  letter-spacing: -0.035em;
  padding: 0.5rem 0;
  min-width: 0;
}

.search-input:focus {
  outline: 2px solid var(--accent-text);
  outline-offset: 4px;
}

.search-submit {
  width: 48px;
  height: 48px;
  border: 1px solid var(--ink);
  background: var(--ink);
  color: var(--ivory);
  display: grid;
  place-items: center;
  transition: background 180ms ease, border-color 180ms ease;
}

.search-submit:hover:not(:disabled) {
  background: var(--accent);
  border-color: var(--accent);
  color: var(--ink);
}

.search-submit:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.search-body {
  overflow-y: auto;
  padding: 1.5rem;
  min-height: 220px;
}

.search-state {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  color: var(--steel-dark);
  font-size: 0.95rem;
}

.search-spinner {
  width: 16px;
  height: 16px;
  border: 1px solid var(--line);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.search-empty {
  display: grid;
  gap: 1.25rem;
}

.search-empty__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.search-recent {
  display: grid;
  gap: 0.6rem;
}

.search-clear {
  border: 0;
  background: transparent;
  min-height: 44px;
  padding: 0 0.25rem;
  font-size: 0.75rem;
  color: var(--steel-dark);
  text-decoration: underline;
  text-underline-offset: 3px;
}

.search-clear:hover {
  color: var(--ink);
}

.search-hint {
  color: var(--steel-dark);
  font-size: 0.92rem;
  line-height: 1.55;
  max-width: 52ch;
}

.search-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.search-chip {
  border: 1px solid var(--line-strong);
  background: transparent;
  min-height: 44px;
  padding: 0.55rem 0.9rem;
  font-size: 0.85rem;
  transition: border-color 160ms ease, color 160ms ease;
}

.search-chip:hover {
  border-color: var(--ink);
  color: var(--accent-text);
}

.search-closest {
  display: grid;
  gap: 0.4rem;
}

.search-groups {
  display: grid;
  gap: 1.75rem;
}

.search-group__label {
  color: var(--steel);
  margin-bottom: 0.5rem;
}

.search-group__list {
  list-style: none;
  border-top: 1px solid var(--line);
}

.search-result {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.25rem 1rem;
  padding: 0.9rem 0;
  border-bottom: 1px solid var(--line);
  transition: padding-left 200ms var(--ease), color 160ms ease;
}

.search-result--compact {
  padding-block: 0.7rem;
}

.search-result:hover {
  padding-left: 0.45rem;
  color: var(--accent-text);
}

.search-result__title {
  font-weight: 650;
  font-size: 0.98rem;
}

.search-result__excerpt {
  grid-column: 1 / 2;
  font-size: 0.85rem;
  color: var(--steel-dark);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.search-result :deep(mark) {
  background: rgba(240, 82, 31, 0.22);
  color: inherit;
  padding: 0 0.1em;
}

.search-result > svg {
  grid-row: 1 / span 2;
  align-self: center;
}

.search-all {
  justify-self: start;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 44px;
  border: 0;
  background: transparent;
  border-bottom: 1px solid var(--ink);
  padding: 0.35rem 0;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.search-contact {
  justify-self: start;
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-size: 0.9rem;
  text-decoration: underline;
  text-underline-offset: 4px;
}

.search-foot {
  display: flex;
  justify-content: space-between;
  padding: 0.8rem 1.5rem;
  border-top: 1px solid var(--line);
  color: var(--steel);
}

.search-enter-active {
  transition: opacity 200ms ease;
}

.search-leave-active {
  transition: opacity 150ms ease;
}

.search-enter-from,
.search-leave-to {
  opacity: 0;
}

.search-enter-active .search-shell {
  transition: transform 320ms var(--ease), opacity 240ms ease;
}

.search-enter-from .search-shell {
  opacity: 0;
  transform: translateY(-10px) scale(0.985);
}

.search-leave-active .search-shell {
  transition: transform 160ms var(--ease), opacity 140ms ease;
}

.search-leave-to .search-shell {
  opacity: 0;
  transform: translateY(-4px) scale(0.995);
}

@media (max-width: 640px) {
  .search-overlay {
    padding: 0;
  }

  .search-shell {
    max-height: 100vh;
    height: 100vh;
    border: 0;
  }
}
</style>
