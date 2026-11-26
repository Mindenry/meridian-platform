import { createRouter, createWebHistory } from 'vue-router'
import { auth } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { public: true, title: 'Sign in' },
    },
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      meta: { title: 'Overview', permission: 'pages.view' },
    },
    {
      path: '/pages',
      name: 'pages',
      component: () => import('@/views/PagesView.vue'),
      meta: { title: 'Pages', permission: 'pages.view' },
    },
    {
      path: '/pages/:id',
      name: 'page-editor',
      component: () => import('@/views/PageEditorView.vue'),
      meta: { title: 'Page editor', permission: 'pages.view' },
    },
    {
      path: '/pages/:id/builder',
      name: 'page-builder',
      component: () => import('@/views/PageBuilderView.vue'),
      meta: { title: 'Visual builder', permission: 'pages.view' },
    },
    {
      path: '/content/articles',
      name: 'articles',
      component: () => import('@/views/ArticlesView.vue'),
      meta: { title: 'Articles', permission: 'articles.view' },
    },
    {
      path: '/content/articles/:id',
      name: 'article-editor',
      component: () => import('@/views/ArticleEditorView.vue'),
      meta: { title: 'Article editor', permission: 'articles.view' },
    },
    {
      path: '/content/case-studies',
      name: 'case-studies',
      component: () => import('@/views/CaseStudiesView.vue'),
      meta: { title: 'Case studies', permission: 'case_studies.view' },
    },
    {
      path: '/content/case-studies/:id',
      name: 'case-study-editor',
      component: () => import('@/views/CaseStudyEditorView.vue'),
      meta: { title: 'Case study editor', permission: 'case_studies.view' },
    },
    {
      path: '/media',
      name: 'media',
      component: () => import('@/views/MediaView.vue'),
      meta: { title: 'Media library', permission: 'media.view' },
    },
    {
      path: '/navigation',
      name: 'navigation',
      component: () => import('@/views/NavigationView.vue'),
      meta: { title: 'Navigation', permission: 'navigation.manage' },
    },
    {
      path: '/forms',
      name: 'forms',
      component: () => import('@/views/FormsView.vue'),
      meta: { title: 'Forms & submissions', permission: 'forms.view' },
    },
    {
      path: '/leads',
      name: 'leads',
      component: () => import('@/views/LeadsView.vue'),
      meta: { title: 'Lead inbox', permission: 'leads.view' },
    },
    {
      path: '/seo',
      name: 'seo',
      component: () => import('@/views/SeoView.vue'),
      meta: { title: 'SEO health', permission: 'seo.view' },
    },
    {
      path: '/analytics',
      name: 'analytics',
      component: () => import('@/views/AnalyticsView.vue'),
      meta: { title: 'Analytics', permission: 'analytics.view' },
    },
    {
      path: '/team',
      name: 'team',
      component: () => import('@/views/TeamView.vue'),
      meta: { title: 'Team & access', permission: 'users.view' },
    },
    {
      path: '/workflows',
      name: 'workflows',
      component: () => import('@/views/WorkflowsView.vue'),
      meta: { title: 'Workflows', permission: 'workflow.view' },
    },
    {
      path: '/sites',
      name: 'sites',
      component: () => import('@/views/SitesView.vue'),
      meta: { title: 'Websites', permission: 'sites.view' },
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/SettingsView.vue'),
      meta: { title: 'Settings', permission: 'settings.manage' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
      meta: { title: 'Not found' },
    },
  ],
})

router.beforeEach(async (to) => {
  await auth.bootstrap()

  if (to.meta.public) {
    if (auth.user.value && to.name === 'login') return { name: 'dashboard' }
    return true
  }

  if (!auth.user.value) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.name === 'sites' && !auth.can('sites.view')) {
    return { name: 'dashboard' }
  }

  if (to.meta.permission && !auth.can(String(to.meta.permission))) {
    return { name: 'dashboard' }
  }

  return true
})

router.afterEach((to) => {
  document.title = `${String(to.meta.title || 'Studio')} — MERIDIAN STUDIO`
})

window.addEventListener('meridian:auth-expired', () => {
  if (router.currentRoute.value.name !== 'login') {
    router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
  }
})

export default router
