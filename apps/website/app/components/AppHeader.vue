<script setup lang="ts">
import type { NavItem, SiteBootstrap } from '~/types'

const props = defineProps<{ bootstrap: SiteBootstrap | null }>()
const route = useRoute()
const { localeCode } = useLocale()
const { open: openSearch } = useSearch()

const mainNav = computed<NavItem[]>(() => props.bootstrap?.navigation?.main ?? [])
const contactItem = computed<NavItem | null>(() =>
  mainNav.value.find((item) => item.key === 'contact')
  ?? mainNav.value.find((item) => /\/contact\/?$/i.test(item.url))
  ?? null,
)
const navItems = computed(() => mainNav.value.filter((item) => item !== contactItem.value))
const menuOpen = ref(false)
const megaItem = ref<NavItem | null>(null)
const closeTimer = ref<ReturnType<typeof setTimeout> | null>(null)
const menuTrigger = ref<HTMLButtonElement | null>(null)
const scrolled = ref(false)

const ctaLabel = computed(() => {
  if (localeCode.value === 'th') return 'เริ่มโครงการ'
  if (localeCode.value === 'zh-CN') return '开始项目'
  return 'Start a project'
})
const megaIndex = computed(() => navItems.value.findIndex((entry) => entry.url === megaItem.value?.url))
const contactUrl = computed(() => {
  return contactItem.value?.url || `/${localeCode.value === 'zh-CN' ? 'zh-cn' : localeCode.value}/contact`
})

const labels = computed(() => ({
  menu: localeCode.value === 'th' ? 'เมนู' : localeCode.value === 'zh-CN' ? '菜单' : 'Menu',
  close: localeCode.value === 'th' ? 'ปิด' : localeCode.value === 'zh-CN' ? '关闭' : 'Close',
  search: localeCode.value === 'th' ? 'ค้นหา' : localeCode.value === 'zh-CN' ? '搜索' : 'Search',
}))

function clearCloseTimer() {
  if (closeTimer.value) clearTimeout(closeTimer.value)
}

function openMega(item: NavItem) {
  if (!item.children?.length) return
  clearCloseTimer()
  megaItem.value = item
}

function scheduleClose() {
  clearCloseTimer()
  closeTimer.value = setTimeout(() => {
    megaItem.value = null
  }, 160)
}

function toggleMega(item: NavItem) {
  if (!item.children?.length) return
  megaItem.value = megaItem.value?.url === item.url ? null : item
}

function closeMenu() {
  menuOpen.value = false
  nextTick(() => menuTrigger.value?.focus())
}

function onKeydown(event: KeyboardEvent) {
  const target = event.target as HTMLElement | null
  const isTyping = target?.matches('input, textarea, select, [contenteditable="true"]')
  if (isTyping) return

  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault()
    openSearch()
  }

  if (event.key === '/' && !isTyping) {
    event.preventDefault()
    openSearch()
  }

  if (event.key === 'Escape') {
    megaItem.value = null
    menuOpen.value = false
  }
}

// Dropdown panels belong to the page that opened them. The full-screen menu
// closes itself from its own links and language buttons; closing it here would
// also close a menu the user opened just after changing language.
watch(() => route.fullPath, () => {
  megaItem.value = null
})

function onScroll() {
  scrolled.value = window.scrollY > 8
}

onMounted(() => {
  onScroll()
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('scroll', onScroll, { passive: true })
})

onBeforeUnmount(() => {
  clearCloseTimer()
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <header class="site-header" :class="{ 'is-scrolled': scrolled }">
    <div class="container site-header__inner">
      <SiteLogo />

      <nav class="primary-nav" aria-label="Primary">
        <ul class="primary-nav__list">
          <li
            v-for="(item, itemIndex) in navItems"
            :key="item.url"
            class="primary-nav__item"
            @mouseenter="openMega(item)"
            @mouseleave="scheduleClose"
          >
            <NuxtLink
              v-if="!item.children?.length"
              :to="item.url"
              class="primary-nav__link"
              :aria-current="route.path === item.url ? 'page' : undefined"
            >
              {{ item.label }}
            </NuxtLink>
            <button
              v-else
              type="button"
              class="primary-nav__link primary-nav__button"
              :aria-expanded="megaItem?.url === item.url"
              :aria-controls="`mega-panel-${itemIndex}`"
              @focus="openMega(item)"
              @click="toggleMega(item)"
            >
              {{ item.label }}
              <AppIcon name="chevron-down" :size="11" />
            </button>
          </li>
        </ul>
      </nav>

      <div class="header-actions">
        <button type="button" class="icon-button" :aria-label="labels.search" @click="openSearch">
          <AppIcon name="search" :size="17" />
        </button>

        <LocaleSwitcher class="header-locale" />

        <NuxtLink :to="contactUrl" class="btn btn-sm header-cta">
          {{ ctaLabel }}
          <AppIcon name="arrow-up-right" :size="13" class="btn-icon" />
        </NuxtLink>

        <button
          ref="menuTrigger"
          type="button"
          class="menu-toggle"
          :aria-label="menuOpen ? labels.close : labels.menu"
          :aria-expanded="menuOpen"
          aria-controls="site-menu"
          @click="menuOpen ? closeMenu() : (menuOpen = true)"
        >
          <span class="menu-toggle__label">{{ labels.menu }}</span>
          <span class="menu-toggle__icon" aria-hidden="true">
            <i />
            <i />
            <i />
          </span>
        </button>
      </div>
    </div>

    <Transition name="mega">
      <div
        v-if="megaItem"
        :id="`mega-panel-${megaIndex}`"
        class="mega-panel"
        @mouseenter="clearCloseTimer"
        @mouseleave="scheduleClose"
      >
        <div class="container mega-panel__inner">
          <div class="mega-panel__intro">
            <p class="mono mega-panel__label">{{ megaItem.label }}</p>
            <p class="mega-panel__copy">
              {{
                localeCode === 'th'
                  ? 'ความสามารถที่เชื่อมโยงกันเป็นระบบเดียว'
                  : localeCode === 'zh-CN'
                    ? '以同一系统连接的能力。'
                    : 'Capability connected as one accountable system.'
              }}
            </p>
            <NuxtLink :to="megaItem.url" class="text-link">
              {{ localeCode === 'th' ? 'ดูทั้งหมด' : localeCode === 'zh-CN' ? '查看全部' : 'View all' }}
            </NuxtLink>
          </div>

          <ul class="mega-panel__list">
            <li v-for="child in megaItem.children" :key="child.url">
              <NuxtLink :to="child.url" class="mega-panel__link">
                <span>{{ child.label }}</span>
                <AppIcon name="arrow-up-right" :size="14" />
              </NuxtLink>
            </li>
          </ul>
        </div>
      </div>
    </Transition>
  </header>

  <MenuOverlay
    :open="menuOpen"
    :nav="mainNav"
    :contact-url="contactUrl"
    :cta-label="ctaLabel"
    @close="closeMenu"
  />
</template>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 60;
  background: var(--glass);
  backdrop-filter: blur(18px) saturate(150%);
  border-bottom: 1px solid var(--glass-border);
  box-shadow: 0 1px 0 rgba(14, 20, 17, 0.03);
  transition: background 240ms var(--ease), border-color 240ms var(--ease), box-shadow 260ms var(--ease);
}

.site-header::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: -1px;
  height: 1px;
  background: var(--accent);
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform 420ms var(--ease);
  pointer-events: none;
}

.site-header.is-scrolled {
  background: var(--glass-strong);
  border-bottom-color: rgba(14, 20, 17, 0.06);
  box-shadow: var(--shadow-md);
}

.site-header.is-scrolled::after {
  transform: scaleX(1);
}

.site-header__inner {
  height: var(--header-h);
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: clamp(1.5rem, 3vw, 3rem);
}

.primary-nav {
  justify-self: center;
}

.primary-nav__list {
  display: flex;
  align-items: center;
  gap: clamp(1.2rem, 2.2vw, 2.1rem);
  list-style: none;
}

.primary-nav__item {
  position: relative;
}

.primary-nav__link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding-block: 0.55rem;
  border: 0;
  background: transparent;
  color: var(--ink-2);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0;
  position: relative;
  white-space: nowrap;
  transition: color 180ms ease, transform 120ms var(--ease);
}

.primary-nav__link:hover,
.primary-nav__button[aria-expanded="true"] {
  color: var(--ink);
}

.primary-nav__link:active {
  transform: translateY(1px);
}

.primary-nav__link::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0.1rem;
  height: 1px;
  background: var(--accent);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 220ms var(--ease);
}

.primary-nav__link:hover::after,
.primary-nav__link[aria-current="page"]::after,
.primary-nav__button[aria-expanded="true"]::after {
  transform: scaleX(1);
}

.primary-nav__button {
  cursor: pointer;
}

.header-actions {
  justify-self: end;
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--ink);
  transition: border-color 160ms ease, color 160ms ease, background 160ms ease, transform 140ms var(--ease);
}

.icon-button:hover {
  border-color: var(--line);
  color: var(--accent-text);
}

.icon-button:active {
  transform: scale(0.94);
}

.menu-toggle {
  display: none;
  align-items: center;
  gap: 0.55rem;
  min-height: 44px;
  padding: 0 0.85rem;
  border: 1px solid var(--line-strong);
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--ink);
  font-family: var(--font-sans);
  font-size: 0.78rem;
  font-weight: 600;
  transition: background 180ms ease, border-color 180ms ease, color 180ms ease, transform 140ms var(--ease);
}

.menu-toggle:hover {
  background: var(--ink);
  border-color: var(--ink);
  color: var(--ivory);
}

.menu-toggle:active {
  transform: scale(0.97);
}

.menu-toggle__icon {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 3px;
  width: 15px;
}

.menu-toggle__icon i {
  display: block;
  width: 100%;
  height: 1px;
  background: currentColor;
  transition: transform 200ms var(--ease), opacity 160ms ease;
}

.menu-toggle[aria-expanded="true"] .menu-toggle__icon i:first-child {
  transform: translateY(4px) rotate(45deg);
}

.menu-toggle[aria-expanded="true"] .menu-toggle__icon i:nth-child(2) {
  opacity: 0;
}

.menu-toggle[aria-expanded="true"] .menu-toggle__icon i:last-child {
  transform: translateY(-4px) rotate(-45deg);
}

.mega-panel {
  position: absolute;
  top: calc(var(--header-h) - 4px);
  left: 50%;
  width: min(1160px, calc(100vw - 2.5rem));
  transform: translateX(-50%);
  background: rgba(251, 250, 246, 0.97);
  backdrop-filter: blur(26px) saturate(150%);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.mega-panel__inner {
  display: grid;
  grid-template-columns: 0.8fr 2fr;
  gap: clamp(2rem, 5vw, 5rem);
  padding: clamp(1.6rem, 3vw, 2.4rem) clamp(1.5rem, 3vw, 2.5rem);
}

.mega-panel__label {
  color: var(--accent-text);
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.mega-panel__copy {
  margin-top: 0.8rem;
  max-width: 28ch;
  color: var(--steel-dark);
  font-size: 0.9rem;
  line-height: 1.6;
}

.mega-panel__intro .text-link {
  margin-top: 1.4rem;
}

.mega-panel__list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.15rem 0.75rem;
  list-style: none;
}

.mega-panel__link {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  gap: 0.7rem;
  padding: 0.8rem 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 600;
  transition: color 160ms ease, background 160ms ease, padding-left 220ms var(--ease);
}

.mega-panel__link:hover {
  color: var(--accent-text);
  background: rgba(14, 20, 17, 0.045);
  padding-left: 0.95rem;
}

.mega-enter-active {
  transition: opacity 160ms ease, clip-path 320ms var(--ease);
}

.mega-leave-active {
  transition: opacity 130ms ease, clip-path 170ms var(--ease);
}

.mega-enter-from,
.mega-leave-to {
  opacity: 0;
  clip-path: inset(0 0 100% 0);
}

.mega-enter-active .mega-panel__intro {
  animation: mega-copy-in 260ms var(--ease) both;
}

.mega-enter-active .mega-panel__link {
  animation: mega-link-in 340ms var(--ease) both;
}

.mega-enter-active .mega-panel__link:nth-child(1) { animation-delay: 20ms; }
.mega-enter-active .mega-panel__link:nth-child(2) { animation-delay: 45ms; }
.mega-enter-active .mega-panel__link:nth-child(3) { animation-delay: 70ms; }
.mega-enter-active .mega-panel__link:nth-child(4) { animation-delay: 95ms; }
.mega-enter-active .mega-panel__link:nth-child(5) { animation-delay: 120ms; }
.mega-enter-active .mega-panel__link:nth-child(6) { animation-delay: 145ms; }
.mega-enter-active .mega-panel__link:nth-child(7) { animation-delay: 170ms; }
.mega-enter-active .mega-panel__link:nth-child(8) { animation-delay: 195ms; }

@keyframes mega-link-in {
  from {
    opacity: 0;
    transform: translateY(9px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes mega-copy-in {
  from {
    opacity: 0;
    transform: translateY(5px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 1120px) {
  .primary-nav,
  .header-cta {
    display: none;
  }

  .site-header__inner {
    grid-template-columns: 1fr auto;
  }

  .menu-toggle {
    display: inline-flex;
  }
}

@media (max-width: 760px) {
  .header-locale {
    display: inline-flex;
  }
}

@media (max-width: 600px) {
  .icon-button {
    display: none;
  }
}

@media (max-width: 360px) {
  .menu-toggle__label {
    display: none;
  }

  .menu-toggle {
    padding-inline: 0.65rem;
  }
}
</style>
