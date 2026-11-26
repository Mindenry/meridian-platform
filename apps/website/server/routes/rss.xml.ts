import { buildLocaleRss } from '../utils/rss'

export default defineEventHandler((event) => buildLocaleRss(event, 'th'))
