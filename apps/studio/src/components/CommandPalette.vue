<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Search, CornerDownLeft, FileText } from 'lucide-vue-next'
import { ui } from '@/stores/ui'
import { auth } from '@/stores/auth'
import { api } from '@/api/client'

const router = useRouter()
const query = ref('')
const activeIndex = ref(0)
const input = ref<HTMLInputElement | null>(null)
const pages = ref<Array<{ id: number; title: string; status: string; slug?: string }>>([])
const loadingPages = ref(false)

interface Item {
  label: string
  hint?: string
  group: string
  action: () => void
  icon?: any
}

const baseItems = computed<Item[]>(() => {
  const items: Item[] = [
    { group: 'Go to', label: 'Overview', hint: 'G O', action: () => router.push('/') },
    { group: 'Go to', label: 'Pages', hint: 'G P', action: () => router.push('/pages') },
    { group: 'Go to', label: 'Media library', action: () => router.push('/media') },
    { group: 'Go to', label: 'Insights & articles', action: () => router.push('/content/articles') },
    { group: 'Go to', label: 'Case studies', action: () => router.push('/content/case-studies') },
    { group: 'Go to', label: 'SEO health', action: () => router.push('/seo') },
    { group: 'Go to', label: 'Analytics', action: () => router.push('/analytics') },
    { group: 'Go to', label: 'Lead inbox', action: () => router.push('/leads') },
    { group: 'Go to', label: 'Team & access', action: () => router.push('/team') },
    { group: 'Go to', label: 'Site settings', action: () => router.push('/settings') },
    { group: 'Actions', label: 'Create a page', action: () => router.push('/pages?create=1') },
    { group: 'Actions', label: 'Upload media', action: () => router.push('/media?upload=1') },
    { group: 'Actions', label: 'Run an SEO audit', action: () => router.push('/seo?audit=1') },
    { group: 'Actions', label: 'Sign out', action: async () => { await auth.logout(); router.push('/login') } },
  ]
  return items.filter((item) => {
    if (['Team & access'].includes(item.label)) return auth.can('users.view')
    if (['Site settings'].includes(item.label)) return auth.can('settings.manage')
    return true
  })
})

const pageItems = computed<Item[]>(() => pages.value.map((page) => ({
  group: 'Pages',
  label: page.title,
  hint: page.status,
  icon: FileText,
  action: () => router.push(`/pages/${page.id}`),
})))

const filtered = computed(() => {
  const term = query.value.trim().toLowerCase()
  const all = [...baseItems.value, ...pageItems.value]
  if (!term) return all
  return all.filter((item) => item.label.toLowerCase().includes(term) || item.group.toLowerCase().includes(term))
})

const grouped = computed(() => {
  const groups: Record<string, Item[]> = {}
  for (const item of filtered.value) {
    groups[item.group] = groups[item.group] || []
    groups[item.group].push(item)
  }
  return groups
})

const flat = computed(() => Object.values(grouped.value).flat())

watch(() => ui.state.commandOpen, async (open) => {
  if (open) {
    query.value = ''
    activeIndex.value = 0
    await nextTick()
    input.value?.focus()
  }
})

const searchPages = (() => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return (term: string) => {
    if (timer) clearTimeout(timer)
    if (term.length < 2) {
      pages.value = []
      return
    }
    timer = setTimeout(async () => {
      loadingPages.value = true
      try {
        const { data } = await api.get('/pages', { params: { q: term, per_page: 6 } })
        pages.value = data.data
      } finally {
        loadingPages.value = false
      }
    }, 200)
  }
})()

watch(query, (term) => {
  activeIndex.value = 0
  searchPages(term)
})

function close() {
  ui.closeCommand()
}

function run(item?: Item) {
  const target = item || flat.value[activeIndex.value]
  if (!target) return
  close()
  target.action()
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') close()
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    activeIndex.value = Math.min(activeIndex.value + 1, flat.value.length - 1)
  }
  if (event.key === 'ArrowUp') {
    event.preventDefault()
    activeIndex.value = Math.max(activeIndex.value - 1, 0)
  }
  if (event.key === 'Enter') {
    event.preventDefault()
    run()
  }
}

function itemIndex(item: Item) {
  return flat.value.indexOf(item)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="command">
      <div v-if="ui.state.commandOpen" class="command-backdrop" @click.self="close">
        <div class="command" role="dialog" aria-modal="true" aria-label="Command menu" @keydown="onKeydown">
          <div class="command__input-row">
            <Search :size="16" style="color: var(--s-faint)" />
            <input ref="input" v-model="query" class="command__input" placeholder="Search pages, jump to a screen, or run an action…" aria-label="Command search">
            <span v-if="loadingPages" class="mono faint">…</span>
            <kbd class="command__hint">ESC</kbd>
          </div>
          <div class="command__list">
            <div v-for="(items, group) in grouped" :key="group">
              <p class="command__group">{{ group }}</p>
              <button
                v-for="item in items"
                :key="item.group + item.label"
                type="button"
                class="command__item"
                :class="{ 'is-active': itemIndex(item) === activeIndex }"
                @mouseenter="activeIndex = itemIndex(item)"
                @click="run(item)"
              >
                <component :is="item.icon || Search" :size="14" style="color: var(--s-faint)" />
                <span>{{ item.label }}</span>
                <span v-if="item.hint" class="command__hint">{{ item.hint }}</span>
              </button>
            </div>
            <p v-if="!flat.length" class="command__group" style="padding: 18px 10px">No matching commands or pages.</p>
          </div>
          <div class="command__foot">
            <span>↑↓ navigate</span>
            <span><CornerDownLeft :size="10" style="display:inline;vertical-align:-1px" /> open</span>
            <span>esc close</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.command-enter-active,
.command-leave-active {
  transition: opacity 160ms ease;
}

.command-enter-from,
.command-leave-to {
  opacity: 0;
}
</style>
