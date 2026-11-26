import { buildLocaleRss } from '../../utils/rss'

export default defineEventHandler((event) => {
  const raw = String(getRouterParam(event, 'locale') || 'th').toLowerCase()
  const locale = raw === 'en' ? 'en' : raw === 'zh-cn' ? 'zh-CN' : 'th'

  return buildLocaleRss(event, locale)
})
