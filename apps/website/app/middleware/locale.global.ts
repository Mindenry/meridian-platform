export default defineNuxtRouteMiddleware((to) => {
  const routeLocales = ['th', 'en', 'zh-cn']
  const segments = to.path.split('/').filter(Boolean)
  const first = segments[0]

  if (!first) {
    return navigateTo('/th', { redirectCode: 302 })
  }

  if (!routeLocales.includes(first)) {
    return navigateTo(`/th${to.fullPath.startsWith('/') ? to.fullPath : `/${to.fullPath}`}`, { redirectCode: 302 })
  }

  if (to.params.locale && !routeLocales.includes(String(to.params.locale).toLowerCase())) {
    throw createError({ statusCode: 404, statusMessage: 'Locale not found' })
  }
})
