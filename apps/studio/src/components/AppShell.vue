<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  LayoutDashboard, Files, Newspaper, FolderKanban, Image, Navigation, ClipboardList,
  Inbox, SearchCheck, BarChart3, Users, GitBranch, Globe2, Settings, Bell, Command,
  ChevronLeft, ChevronRight, LogOut, ExternalLink, Menu, X, Plus, Check, ShieldCheck,
} from 'lucide-vue-next'
import { auth } from '@/stores/auth'
import { ui } from '@/stores/ui'
import { api } from '@/api/client'
import { toast } from '@/stores/toast'

const route = useRoute()
const router = useRouter()
const user = computed(() => auth.user.value)
const sites = computed(() => user.value?.sites ?? [])
const currentSite = computed(() => sites.value.find((site) => site.id === user.value?.current_site_id) ?? sites.value[0])
const collapsed = computed(() => ui.sidebarCollapsed.value)
const mobileOpen = ref(false)
const siteMenuOpen = ref(false)
const userMenuOpen = ref(false)
const notificationsOpen = ref(false)
const notifications = ref<any[]>([])
const notificationsLoading = ref(false)

const groups = [
  {
    label: 'Workspace',
    items: [
      { label: 'Overview', to: '/', icon: LayoutDashboard, permission: 'pages.view', name: 'dashboard' },
      { label: 'Pages', to: '/pages', icon: Files, permission: 'pages.view', name: 'pages' },
      { label: 'Content', to: '/content/articles', icon: Newspaper, permission: 'articles.view', name: 'articles' },
      { label: 'Case studies', to: '/content/case-studies', icon: FolderKanban, permission: 'case_studies.view', name: 'case-studies' },
      { label: 'Media', to: '/media', icon: Image, permission: 'media.view', name: 'media' },
      { label: 'Navigation', to: '/navigation', icon: Navigation, permission: 'navigation.manage', name: 'navigation' },
    ],
  },
  {
    label: 'Demand',
    items: [
      { label: 'Forms', to: '/forms', icon: ClipboardList, permission: 'forms.view', name: 'forms' },
      { label: 'Leads', to: '/leads', icon: Inbox, permission: 'leads.view', name: 'leads' },
    ],
  },
  {
    label: 'Optimise',
    items: [
      { label: 'SEO health', to: '/seo', icon: SearchCheck, permission: 'seo.view', name: 'seo' },
      { label: 'Analytics', to: '/analytics', icon: BarChart3, permission: 'analytics.view', name: 'analytics' },
    ],
  },
  {
    label: 'Organisation',
    items: [
      { label: 'Team & access', to: '/team', icon: Users, permission: 'users.view', name: 'team' },
      { label: 'Workflows', to: '/workflows', icon: GitBranch, permission: 'workflow.view', name: 'workflows' },
      { label: 'Websites', to: '/sites', icon: Globe2, permission: 'sites.view', name: 'sites' },
      { label: 'Settings', to: '/settings', icon: Settings, permission: 'settings.manage', name: 'settings' },
    ],
  },
]

const visibleGroups = computed(() => groups.map((group) => ({
  ...group,
  items: group.items.filter((item) => auth.can(item.permission)),
})).filter((group) => group.items.length))

const crumbs = computed(() => {
  const matched = route.matched.filter((item) => item.name && item.name !== 'dashboard')
  return matched
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 18) return 'Good afternoon'
  return 'Good evening'
})

watch(() => route.fullPath, () => {
  mobileOpen.value = false
  siteMenuOpen.value = false
  userMenuOpen.value = false
  notificationsOpen.value = false
})

function setSite(siteId: number) {
  auth.setSite(siteId)
  siteMenuOpen.value = false
  window.location.reload()
}

function notificationPath(url?: string | null): string {
  if (!url) return '#'
  try {
    return new URL(url).pathname
  } catch {
    return url
  }
}

async function openNotifications() {
  notificationsOpen.value = !notificationsOpen.value
  if (!notificationsOpen.value) return
  notificationsLoading.value = true
  try {
    const { data } = await api.get('/auth/notifications')
    notifications.value = data.data
  } finally {
    notificationsLoading.value = false
  }
}

async function markAllRead() {
  await api.post('/auth/notifications/read-all')
  notifications.value = notifications.value.map((item) => ({ ...item, read_at: item.read_at || new Date().toISOString() }))
}

async function signOut() {
  await auth.logout()
  toast.info('Signed out')
  router.push({ name: 'login' })
}

function isActive(item: { to: string; name: string }) {
  if (item.name === 'articles') return route.path.startsWith('/content/articles')
  if (item.name === 'case-studies') return route.path.startsWith('/content/case-studies')
  if (item.name === 'pages') return route.path.startsWith('/pages')
  return route.path === item.to
}
</script>

<template>
  <div class="app-shell" :class="{ 'is-collapsed': collapsed }">
    <aside class="sidebar" :class="{ 'is-mobile-open': mobileOpen }">
      <div class="sidebar__brand">
        <svg class="sidebar__brand-mark" viewBox="0 0 32 32" aria-hidden="true">
          <path d="M16 2.5v27" stroke="#f5f3ee" stroke-width="1.1" />
          <path d="M4.5 16h23" stroke="#f5f3ee" stroke-width="1.1" opacity="0.4" />
          <path d="M8 24V8l8 10 8-10v16" fill="none" stroke="#f5f3ee" stroke-width="1.6" />
          <circle cx="24.5" cy="7.5" r="2.4" fill="#e36d3f" />
        </svg>
        <div class="sidebar__brand-text">
          <span class="sidebar__wordmark">MERIDIAN</span>
          <span class="sidebar__studio">MERIDIAN STUDIO</span>
        </div>
      </div>

      <div class="sidebar__site">
        <div class="site-switcher-wrap">
          <button type="button" class="site-switcher" :aria-expanded="siteMenuOpen" @click="siteMenuOpen = !siteMenuOpen">
            <span class="site-switcher__dot" />
            <span class="site-switcher__name">{{ currentSite?.name || 'Select website' }}</span>
            <ChevronRight :size="13" :style="{ transform: siteMenuOpen ? 'rotate(90deg)' : 'none', transition: 'transform 160ms ease' }" />
          </button>
          <div v-if="siteMenuOpen" class="site-menu">
            <p class="site-menu__label mono">Websites</p>
            <button
              v-for="site in sites"
              :key="site.id"
              type="button"
              class="site-menu__item"
              :class="{ 'is-active': site.id === currentSite?.id }"
              @click="setSite(site.id)"
            >
              <span>{{ site.name }}</span>
              <Check v-if="site.id === currentSite?.id" :size="13" />
            </button>
            <NuxtLink v-if="auth.can('sites.view')" to="/sites" class="site-menu__manage" @click="siteMenuOpen = false">
              Manage websites
            </NuxtLink>
          </div>
        </div>
      </div>

      <nav class="sidebar__nav" aria-label="Studio">
        <div v-for="group in visibleGroups" :key="group.label" class="nav-group">
          <p class="nav-group__label">{{ group.label }}</p>
          <RouterLink
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="nav-item"
            :class="{ 'is-active': isActive(item) }"
            :title="collapsed ? item.label : undefined"
          >
            <component :is="item.icon" :size="16" class="nav-item__icon" />
            <span>{{ item.label }}</span>
            <span v-if="item.name === 'seo' && notifications.length" class="nav-item__badge">!</span>
          </RouterLink>
        </div>
      </nav>

      <div class="sidebar__foot">
        <div class="sidebar__user">
          <span class="avatar">{{ user?.initials }}</span>
          <div class="sidebar__user-meta" style="min-width:0">
            <p class="sidebar__user-name">{{ user?.name }}</p>
            <p class="sidebar__user-role">{{ user?.roles?.[0]?.name || 'Member' }}</p>
          </div>
        </div>
        <button type="button" class="sidebar__collapse" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" @click="ui.toggleSidebar">
          <ChevronRight v-if="collapsed" :size="15" />
          <ChevronLeft v-else :size="15" />
        </button>
      </div>
    </aside>

    <div v-if="mobileOpen" class="mobile-backdrop" @click="mobileOpen = false" />

    <div class="app-main">
      <header class="topbar">
        <button type="button" class="topbar__icon mobile-menu-button" aria-label="Open menu" @click="mobileOpen = true">
          <Menu :size="18" />
        </button>
        <div class="topbar__crumbs">
          <RouterLink to="/">Studio</RouterLink>
          <span>/</span>
          <span class="topbar__title">{{ route.meta.title }}</span>
        </div>
        <div class="topbar__spacer" />
        <button type="button" class="command-trigger" @click="ui.openCommand">
          <Command :size="13" />
          <span>Search or jump to…</span>
          <kbd>⌘K</kbd>
        </button>
        <a :href="'http://localhost:3002' + (currentSite?.default_locale === 'th' ? '/th' : currentSite?.default_locale === 'zh-CN' ? '/zh-cn' : '/en')" target="_blank" class="topbar__icon" title="View website">
          <ExternalLink :size="16" />
        </a>
        <div class="notification-wrap">
          <button type="button" class="topbar__icon" aria-label="Notifications" :aria-expanded="notificationsOpen" @click="openNotifications">
            <Bell :size="16" />
            <span v-if="user?.notifications_unread" class="topbar__dot" />
          </button>
          <div v-if="notificationsOpen" class="notification-panel">
            <div class="notification-panel__head">
              <strong>Notifications</strong>
              <button type="button" class="link-s" @click="markAllRead">Mark all read</button>
            </div>
            <div v-if="notificationsLoading" class="notification-panel__empty">Loading…</div>
            <div v-else-if="!notifications.length" class="notification-panel__empty">You are all caught up.</div>
            <RouterLink v-for="notification in notifications" :key="notification.id" :to="notificationPath(notification.data?.url)" class="notification-item" :class="{ 'is-unread': !notification.read_at }">
              <span class="notification-item__dot" />
              <span>
                <strong>{{ notification.data?.title }}</strong>
                <span>{{ notification.data?.message }}</span>
              </span>
            </RouterLink>
            <div class="notification-panel__foot">
              <RouterLink to="/workflows">Open workflow queue</RouterLink>
            </div>
          </div>
        </div>
        <div class="user-wrap">
          <button type="button" class="user-button" :aria-expanded="userMenuOpen" @click="userMenuOpen = !userMenuOpen">
            <span class="avatar">{{ user?.initials }}</span>
            <ChevronRight :size="12" :style="{ transform: userMenuOpen ? 'rotate(90deg)' : 'rotate(0)' }" />
          </button>
          <div v-if="userMenuOpen" class="user-menu">
            <div class="user-menu__head">
              <strong>{{ user?.name }}</strong>
              <span>{{ user?.email }}</span>
            </div>
            <RouterLink to="/team" class="user-menu__item"><ShieldCheck :size="14" /> Team & access</RouterLink>
            <RouterLink to="/settings" class="user-menu__item"><Settings :size="14" /> Site settings</RouterLink>
            <button type="button" class="user-menu__item" @click="signOut"><LogOut :size="14" /> Sign out</button>
          </div>
        </div>
      </header>

      <div class="app-content">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.site-switcher-wrap {
  position: relative;
}

.site-menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  background: var(--s-navy-2);
  border: 1px solid rgba(245, 243, 238, 0.14);
  border-radius: var(--s-radius);
  z-index: 10;
  padding: 6px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.3);
}

.site-menu__label {
  color: rgba(245, 243, 238, 0.3);
  padding: 6px 7px;
  font-size: 8px;
}

.site-menu__item {
  width: 100%;
  border: 0;
  background: transparent;
  color: rgba(245, 243, 238, 0.72);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 8px 7px;
  border-radius: 3px;
  font-size: 12px;
  text-align: left;
}

.site-menu__item:hover,
.site-menu__item.is-active {
  background: rgba(245, 243, 238, 0.08);
  color: #fff;
}

.site-menu__manage {
  display: block;
  border-top: 1px solid rgba(245, 243, 238, 0.1);
  margin-top: 5px;
  padding: 8px 7px 4px;
  font-size: 12px;
  color: rgba(245, 243, 238, 0.45);
}

.mobile-menu-button {
  display: none;
}

.notification-wrap,
.user-wrap {
  position: relative;
}

.notification-panel,
.user-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  width: 320px;
  background: var(--s-surface);
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  box-shadow: 0 18px 50px rgba(15, 27, 41, 0.18);
  z-index: 50;
  overflow: hidden;
}

.notification-panel__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  border-bottom: 1px solid var(--s-line);
  font-size: 12px;
}

.link-s {
  border: 0;
  background: transparent;
  color: var(--s-accent);
  font-size: 12px;
}

.notification-panel__empty {
  padding: 20px 12px;
  text-align: center;
  color: var(--s-muted);
  font-size: 12px;
}

.notification-item {
  display: flex;
  gap: 8px;
  padding: 10px 12px;
  border-bottom: 1px solid var(--s-line);
  font-size: 12px;
}

.notification-item:hover {
  background: var(--s-bg);
}

.notification-item__dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--s-line-strong);
  margin-top: 5px;
  flex: 0 0 auto;
}

.notification-item.is-unread .notification-item__dot {
  background: var(--s-accent);
}

.notification-item strong {
  display: block;
  font-size: 12px;
}

.notification-item span {
  display: block;
  color: var(--s-muted);
  line-height: 1.4;
  margin-top: 2px;
}

.notification-panel__foot {
  padding: 8px 12px;
  font-size: 12px;
  border-top: 1px solid var(--s-line);
}

.notification-panel__foot a {
  color: var(--s-accent);
}

.user-button {
  border: 0;
  background: transparent;
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--s-muted);
}

.user-button .avatar {
  background: var(--s-ink);
  color: #f5f3ee;
  border: 0;
}

.user-menu__head {
  padding: 12px;
  border-bottom: 1px solid var(--s-line);
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-menu__head strong { font-size: 12px; }
.user-menu__head span { font-size: 12px; color: var(--s-muted); }

.user-menu__item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border: 0;
  background: transparent;
  font-size: 12px;
  text-align: left;
}

.user-menu__item:hover {
  background: var(--s-bg);
}

.mobile-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 27, 41, 0.5);
  z-index: 45;
}

@media (max-width: 900px) {
  .sidebar {
    display: flex;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 252px;
    transform: translateX(-100%);
    transition: transform 260ms var(--s-ease);
  }

  .sidebar.is-mobile-open {
    transform: translateX(0);
  }

  .mobile-menu-button {
    display: grid;
  }

  .notification-panel {
    width: min(300px, calc(100vw - 24px));
  }
}
</style>
