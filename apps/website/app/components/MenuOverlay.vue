<script setup lang="ts">
import type { NavItem } from '~/types'

const props = defineProps<{
  open: boolean
  nav: NavItem[]
  contactUrl: string
  ctaLabel: string
}>()

const emit = defineEmits<{ close: [] }>()

const { localeCode, localeLinks, switchLocale } = useLocale()
const { open: openSearch } = useSearch()
const closeButton = ref<HTMLButtonElement | null>(null)
const overlay = ref<HTMLElement | null>(null)

const primary = computed(() =>
  props.nav.filter((item) => item.key !== 'contact' && !/\/contact\/?$/i.test(item.url)),
)
const solutions = computed(() => props.nav.find((item) => item.url.includes('/solutions'))?.children ?? [])
const industries = computed(() => props.nav.find((item) => item.url.includes('/industries'))?.children ?? [])
const company = computed(() =>
  (props.nav.find((item) => item.url.includes('/about'))?.children ?? [])
    .filter((item) => item.key !== 'contact' && !/\/contact\/?$/i.test(item.url)),
)

const labels = computed(() => ({
  menu: localeCode.value === 'th' ? 'เมนู' : localeCode.value === 'zh-CN' ? '菜单' : 'Menu',
  close: localeCode.value === 'th' ? 'ปิด' : localeCode.value === 'zh-CN' ? '关闭' : 'Close',
  capabilities: localeCode.value === 'th' ? 'ขีดความสามารถ' : localeCode.value === 'zh-CN' ? '能力' : 'Capabilities',
  sectors: localeCode.value === 'th' ? 'อุตสาหกรรม' : localeCode.value === 'zh-CN' ? '行业' : 'Sectors',
  company: localeCode.value === 'th' ? 'บริษัท' : localeCode.value === 'zh-CN' ? '公司' : 'Company',
  language: localeCode.value === 'th' ? 'ภาษา' : localeCode.value === 'zh-CN' ? '语言' : 'Language',
  search: localeCode.value === 'th' ? 'ค้นหา' : localeCode.value === 'zh-CN' ? '搜索' : 'Search',
  contact: localeCode.value === 'th' ? 'ติดต่อทีมงาน' : localeCode.value === 'zh-CN' ? '联系团队' : 'Talk to the team',
}))

async function changeLocale(code: string) {
  emit('close')
  await switchLocale(code)
}

function openSearchFromMenu() {
  emit('close')
  nextTick(() => openSearch())
}

function onKeydown(event: KeyboardEvent) {
  if (!props.open) return

  if (event.key === 'Escape') {
    emit('close')
    return
  }

  if (event.key === 'Tab' && overlay.value) {
    const focusable = [...overlay.value.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled])')]
      .filter((element) => element.offsetParent !== null)

    if (!focusable.length) return

    const first = focusable[0]
    const last = focusable[focusable.length - 1]

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  }
}

watch(
  () => props.open,
  async (open) => {
    if (!import.meta.client) return
    document.documentElement.style.overflow = open ? 'hidden' : ''
    if (open) {
      await nextTick()
      closeButton.value?.focus()
    }
  },
)

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  if (import.meta.client) document.documentElement.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="index">
      <div v-if="open" id="site-menu" ref="overlay" class="index-overlay" role="dialog" aria-modal="true" :aria-label="labels.menu">
        <div class="index-overlay__grid" aria-hidden="true" />

        <div class="index-overlay__bar container">
          <SiteLogo variant="light" />
          <button ref="closeButton" type="button" class="index-close" @click="emit('close')">
            <span>{{ labels.close }}</span>
            <AppIcon name="close" :size="18" />
          </button>
        </div>

        <div class="index-overlay__body container">
          <nav class="index-primary" aria-label="Menu">
            <NuxtLink
              v-for="item in primary"
              :key="item.url"
              :to="item.url"
              class="index-link"
              @click="emit('close')"
            >
              <span class="index-link__label">{{ item.label }}</span>
              <AppIcon name="arrow-up-right" :size="22" class="index-link__arrow" />
            </NuxtLink>
          </nav>

          <div class="index-columns">
            <section class="index-column">
              <p class="mono index-column__label">{{ labels.capabilities }}</p>
              <NuxtLink v-for="child in solutions" :key="child.url" :to="child.url" class="index-column__link" @click="emit('close')">
                {{ child.label }}
              </NuxtLink>
            </section>

            <section class="index-column">
              <p class="mono index-column__label">{{ labels.sectors }}</p>
              <NuxtLink v-for="child in industries" :key="child.url" :to="child.url" class="index-column__link" @click="emit('close')">
                {{ child.label }}
              </NuxtLink>
            </section>

            <section class="index-column">
              <p class="mono index-column__label">{{ labels.company }}</p>
              <NuxtLink v-for="child in company" :key="child.url" :to="child.url" class="index-column__link" @click="emit('close')">
                {{ child.label }}
              </NuxtLink>
              <button type="button" class="index-column__link index-column__link--button" @click="openSearchFromMenu">
                {{ labels.search }} <span class="mono">/</span>
              </button>
            </section>
          </div>
        </div>

        <div class="index-overlay__foot container">
          <p class="mono">BANGKOK · RAYONG · SINGAPORE</p>

          <div class="index-foot-langs">
            <span class="mono index-foot-langs__label">{{ labels.language }}</span>
            <button
              v-for="locale in localeLinks"
              :key="locale.code"
              type="button"
              class="index-foot-lang"
              :class="{ 'is-active': locale.code === localeCode }"
              :aria-current="locale.code === localeCode ? 'true' : undefined"
              @click="changeLocale(locale.code)"
            >
              {{ locale.label }}
            </button>
          </div>

          <NuxtLink :to="contactUrl" class="index-contact" @click="emit('close')">
            {{ labels.contact }}
            <AppIcon name="arrow-up-right" :size="20" />
          </NuxtLink>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.index-overlay {
  position: fixed;
  inset: 0;
  z-index: 300;
  background: var(--ink);
  color: var(--ivory);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.index-overlay__grid {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image:
    linear-gradient(to right, rgba(241, 237, 228, 0.06) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(241, 237, 228, 0.045) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%, 100% 92px;
}

.index-overlay__bar {
  position: relative;
  z-index: 2;
  min-height: var(--header-h);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  border-bottom: 1px solid var(--line-light);
}

.index-close {
  justify-self: end;
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  border: 1px solid var(--line-light-strong);
  border-radius: 999px;
  background: transparent;
  color: var(--ivory);
  padding: 0.55rem 0.9rem;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: background 180ms ease, color 180ms ease, border-color 180ms ease;
}

.index-close:hover {
  background: var(--accent);
  border-color: var(--accent);
  color: var(--ink);
}

.index-overlay__body {
  position: relative;
  z-index: 2;
  flex: 1;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(2rem, 5vw, 6rem);
  padding-block: clamp(2rem, 4.5vh, 3.5rem);
}

.index-primary {
  grid-column: 1 / span 7;
  display: flex;
  flex-direction: column;
}

.index-link {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: baseline;
  gap: 1rem;
  padding-block: clamp(0.55rem, 1.4vh, 0.95rem);
  border-bottom: 1px solid var(--line-light);
  transition: color 180ms ease, padding-left 260ms var(--ease);
}

.index-link:first-child {
  border-top: 1px solid var(--line-light);
}

.index-link:hover,
.index-link:focus-visible {
  color: var(--accent);
  padding-left: 0.6rem;
}

.index-link__label {
  font-family: var(--font-display);
  font-size: clamp(2.2rem, 4.6vw, 4.6rem);
  line-height: 1.02;
  letter-spacing: -0.02em;
}

html[lang="th"] .index-link__label {
  font-family: var(--font-display-th);
  letter-spacing: 0;
  line-height: 1.18;
}

.index-link__arrow {
  opacity: 0.35;
  transition: opacity 180ms ease, transform 220ms var(--ease);
}

.index-link:hover .index-link__arrow {
  opacity: 1;
  transform: translate(2px, -2px);
}

.index-columns {
  grid-column: 9 / span 4;
  display: grid;
  gap: 2.2rem;
  align-content: start;
}

.index-column {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.index-column__label {
  color: rgba(241, 237, 228, 0.76);
  font-size: 0.75rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  padding-bottom: 0.45rem;
  border-bottom: 1px solid var(--line-light);
}

.index-column__link {
  color: rgba(241, 237, 228, 0.78);
  font-size: 0.86rem;
  line-height: 1.5;
  transition: color 160ms ease, transform 200ms var(--ease);
}

.index-column__link:hover {
  color: var(--accent);
  transform: translateX(2px);
}

.index-column__link--button {
  border: 0;
  background: transparent;
  padding: 0;
  text-align: left;
  color: rgba(241, 237, 228, 0.78);
}

.index-overlay__foot {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: 1fr auto auto;
  align-items: center;
  gap: 2rem;
  padding-block: 1.2rem 1.6rem;
  border-top: 1px solid var(--line-light);
  color: rgba(241, 237, 228, 0.76);
  font-size: 0.75rem;
  letter-spacing: 0.12em;
}

.index-foot-langs {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.index-foot-langs__label {
  color: rgba(241, 237, 228, 0.76);
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.index-foot-lang {
  border: 0;
  background: transparent;
  color: rgba(241, 237, 228, 0.82);
  padding: 0.25rem 0;
  font-size: 0.75rem;
  border-bottom: 1px solid transparent;
  transition: color 160ms ease, border-color 160ms ease;
}

.index-foot-lang:hover,
.index-foot-lang.is-active {
  color: var(--ivory);
  border-color: var(--accent);
}

.index-contact {
  display: inline-flex;
  align-items: center;
  gap: 0.8rem;
  color: var(--ivory);
  font-size: 0.95rem;
  border-bottom: 1px solid var(--accent);
  padding-bottom: 0.35rem;
  transition: color 160ms ease;
}

.index-contact:hover {
  color: var(--accent);
}

.index-enter-active,
.index-leave-active {
  transition: opacity 260ms ease, clip-path 500ms var(--ease);
}

.index-enter-from,
.index-leave-to {
  opacity: 0;
  clip-path: inset(0 0 100% 0);
}

@media (max-width: 1000px) {
  .index-overlay__body {
    grid-template-columns: 1fr;
    gap: 2.5rem;
  }

  .index-primary,
  .index-columns {
    grid-column: 1 / -1;
  }

  .index-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.8rem 2rem;
  }

  .index-link__label {
    font-size: clamp(1.75rem, 7vw, 2.8rem);
  }
}

@media (max-width: 620px) {
  .index-overlay__body {
    padding-block: 2rem 3rem;
  }

  .index-link {
    grid-template-columns: 1fr auto;
    gap: 0.6rem;
  }

  .index-columns {
    grid-template-columns: 1fr;
  }

  .index-overlay__foot {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .index-foot-langs {
    flex-wrap: wrap;
  }
}
</style>
