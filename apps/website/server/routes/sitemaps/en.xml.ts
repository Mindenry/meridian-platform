import { buildLocaleSitemap } from '../../utils/sitemap'

export default defineEventHandler((event) => buildLocaleSitemap(event, 'en'))
