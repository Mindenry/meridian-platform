<script setup lang="ts">
import type { ArticleDetail } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const slug = computed(() => {
  const param = route.params.slug
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `article-${routeLocale.value}-${slug.value}`,
  () => api.get<{ data: ArticleDetail }>(`/public/articles/${encodeURIComponent(slug.value)}`, {
    query: { locale: localeCode.value },
  }),
  { watch: [slug, routeLocale] },
)

if (error.value) throw createError({ statusCode: 404, statusMessage: 'Article not found' })

const article = computed<ArticleDetail | null>(() => data.value?.data ?? null)
usePageSeo(article)

const canonical = computed(() => article.value?.seo?.canonical || '')
const copied = ref(false)
const bodyRef = ref<HTMLElement | null>(null)

const formatDate = (iso?: string | null) => {
  if (!iso) return ''
  return new Intl.DateTimeFormat(
    localeCode.value === 'th' ? 'th-TH' : localeCode.value === 'zh-CN' ? 'zh-CN' : 'en-GB',
    { day: 'numeric', month: 'long', year: 'numeric' },
  ).format(new Date(iso))
}

const labels = computed(() => ({
  minRead: localeCode.value === 'th' ? 'นาทีในการอ่าน' : localeCode.value === 'zh-CN' ? '分钟阅读' : 'min read',
  share: localeCode.value === 'th' ? 'แชร์บทความ' : localeCode.value === 'zh-CN' ? '分享文章' : 'Share article',
  copy: localeCode.value === 'th' ? 'คัดลอกลิงก์' : localeCode.value === 'zh-CN' ? '复制链接' : 'Copy link',
  copied: localeCode.value === 'th' ? 'คัดลอกแล้ว' : localeCode.value === 'zh-CN' ? '已复制' : 'Copied',
  related: localeCode.value === 'th' ? 'บทความที่เกี่ยวข้อง' : localeCode.value === 'zh-CN' ? '相关文章' : 'Related articles',
  back: localeCode.value === 'th' ? 'กลับไปที่บทความทั้งหมด' : localeCode.value === 'zh-CN' ? '返回全部洞察' : 'Back to all insights',
  shareLinkedIn: 'LinkedIn',
  shareX: 'X',
}))

async function copyLink() {
  if (!import.meta.client || !canonical.value) return
  try {
    await navigator.clipboard.writeText(canonical.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    copied.value = false
  }
}

onMounted(async () => {
  await nextTick()
  const headings = bodyRef.value?.querySelectorAll('h2, h3') ?? []
  article.value?.toc?.forEach((item, index) => {
    const heading = headings[index]
    if (heading) heading.id = item.id
  })
})
</script>

<template>
  <div v-if="article">
    <Breadcrumbs :items="article.seo.breadcrumbs || []" />
    <FallbackNotice v-if="article.is_fallback" :locale="article.content_locale || 'en'" />

    <header class="article-hero">
      <div class="container">
        <div class="article-hero__meta mono">
          <span>{{ article.category?.name }}</span>
          <span>{{ formatDate(article.published_at) }}</span>
          <span>{{ article.reading_time }} {{ labels.minRead }}</span>
        </div>
        <h1 class="article-hero__title">{{ article.title }}</h1>
        <p class="article-hero__excerpt">{{ article.excerpt }}</p>
        <div class="article-hero__byline">
          <div v-if="article.author" class="author-mark">{{ article.author.initials }}</div>
          <div>
            <p class="article-hero__author">{{ article.author?.name }}</p>
            <p class="mono">{{ article.author?.job_title }}</p>
          </div>
        </div>
      </div>
    </header>

    <div v-if="article.image" class="article-image">
      <div class="container">
        <AppImage :image="article.image" ratio="21:9" sizes="100vw" priority />
      </div>
    </div>

    <article class="article-body-wrap">
      <div class="container article-body-grid">
        <aside class="article-toc">
          <p class="mono">{{ copy.tableOfContents }}</p>
          <nav aria-label="Table of contents">
            <a
              v-for="item in article.toc"
              :key="item.id"
              :href="`#${item.id}`"
              class="article-toc__link"
              :class="{ 'article-toc__link--sub': item.level === 3 }"
            >
              {{ item.text }}
            </a>
          </nav>
        </aside>

        <div class="article-content">
          <div ref="bodyRef" class="prose article-prose" v-html="article.body" />

          <div class="article-tags">
            <span v-for="tag in article.tags" :key="tag.slug" class="article-tag">#{{ tag.name }}</span>
          </div>

          <div class="article-share">
            <p class="mono">{{ labels.share }}</p>
            <div class="article-share__actions">
              <a :href="`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(canonical)}`" target="_blank" rel="noopener">{{ labels.shareLinkedIn }}</a>
              <a :href="`https://x.com/intent/tweet?url=${encodeURIComponent(canonical)}&text=${encodeURIComponent(article.title)}`" target="_blank" rel="noopener">{{ labels.shareX }}</a>
              <button type="button" @click="copyLink">{{ copied ? labels.copied : labels.copy }}</button>
            </div>
          </div>
        </div>

        <aside class="article-aside">
          <div class="article-aside__block">
            <p class="mono">{{ copy.published }}</p>
            <p>{{ formatDate(article.published_at) }}</p>
            <p v-if="article.updated_at" class="mono">{{ copy.updated }} {{ formatDate(article.updated_at) }}</p>
          </div>
          <NuxtLink :to="`/${routeLocale}/insights`" class="text-link">{{ labels.back }}</NuxtLink>
        </aside>
      </div>
    </article>

    <section v-if="article.related?.length" class="section related-articles">
      <div class="container">
        <div class="related-articles__head">
          <h2 class="h3">{{ labels.related }}</h2>
          <NuxtLink :to="`/${routeLocale}/insights`" class="text-link">{{ copy.allInsights }}</NuxtLink>
        </div>
        <div class="related-articles__grid">
          <NuxtLink v-for="related in article.related" :key="related.id" :to="related.url" class="related-article">
            <AppImage v-if="related.image" :image="related.image" ratio="3:2" sizes="(max-width: 900px) 100vw, 33vw" />
            <div class="related-article__meta mono">
              <span>{{ formatDate(related.published_at) }}</span>
              <span>{{ related.reading_time }} min</span>
            </div>
            <h3 class="related-article__title">{{ related.title }}</h3>
          </NuxtLink>
        </div>
      </div>
    </section>

    <SiteCta
      :eyebrow="copy.talkToTeam"
      :heading="localeCode === 'th' ? 'มีโจทย์ที่อยากพูดคุยหรือไม่' : localeCode === 'zh-CN' ? '有问题想讨论？' : 'Have a problem worth discussing?'"
      :body="localeCode === 'th' ? 'ทีมของเรายินดีรับฟังและให้มุมมองที่ตรงไปตรงมา' : localeCode === 'zh-CN' ? '我们的团队愿意倾听并给出直率观点。' : 'Our team is happy to listen and give a direct point of view.'"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
      :secondary-label="copy.allInsights"
      :secondary-to="`/${routeLocale}/insights`"
    />
  </div>
</template>

<style scoped>
.article-hero {
  background: var(--ivory);
  padding-block: clamp(3rem, 6vw, 5.5rem) clamp(2rem, 4vw, 3rem);
}

.article-hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 1rem;
}

.article-hero__title {
  margin-top: 1.5rem;
  font-size: clamp(2.6rem, 5.6vw, 5.8rem);
  line-height: 0.96;
  letter-spacing: -0.055em;
  font-weight: 500;
  max-width: 17ch;
}

.article-hero__excerpt {
  margin-top: 1.5rem;
  max-width: 64ch;
  font-size: clamp(1.05rem, 1.35vw, 1.25rem);
  line-height: 1.6;
  color: var(--steel-dark);
}

.article-hero__byline {
  margin-top: 2rem;
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.author-mark {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border: 1px solid var(--line-strong);
  font-family: var(--font-mono);
  font-size: 0.75rem;
  color: var(--accent-text);
}

.article-hero__author {
  font-size: 0.85rem;
  font-weight: 600;
}

.article-image {
  background: var(--ivory);
  padding-bottom: 0.5rem;
}

.article-image :deep(img) {
  filter: grayscale(0.4) contrast(1.02) brightness(0.95);
}

.article-body-wrap {
  background: var(--ivory);
  padding-block: clamp(3rem, 6vw, 5rem);
}

.article-body-grid {
  display: grid;
  grid-template-columns: 9rem minmax(0, 1fr) 12rem;
  gap: clamp(2rem, 5vw, 5rem);
  align-items: start;
}

.article-toc {
  position: sticky;
  top: calc(var(--header-h) + 2rem);
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.article-toc > .mono {
  color: var(--steel);
  font-size: 0.75rem;
  margin-bottom: 0.75rem;
}

.article-toc nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.article-toc__link {
  font-size: 0.75rem;
  line-height: 1.4;
  color: var(--steel-dark);
}

.article-toc__link--sub {
  padding-left: 0.75rem;
  font-size: 0.75rem;
  color: var(--steel);
}

.article-toc__link:hover {
  color: var(--accent);
}

.article-content {
  min-width: 0;
}

.article-prose {
  max-width: 72ch;
}

.article-prose :deep(h2),
.article-prose :deep(h3) {
  scroll-margin-top: calc(var(--header-h) + 2rem);
}

.article-tags {
  margin-top: 2.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--line);
}

.article-tag {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  color: var(--steel-dark);
  border: 1px solid var(--line);
  padding: 0.35rem 0.5rem;
}

.article-share {
  margin-top: 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.article-share > .mono {
  color: var(--steel);
  font-size: 0.75rem;
}

.article-share__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.article-share__actions a,
.article-share__actions button {
  border: 0;
  background: transparent;
  padding: 0;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-bottom: 1px solid var(--line-strong);
  padding-bottom: 0.2rem;
}

.article-share__actions a:hover,
.article-share__actions button:hover {
  color: var(--accent);
  border-color: var(--accent);
}

.article-aside {
  position: sticky;
  top: calc(var(--header-h) + 2rem);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  border-top: 1px solid var(--line);
  padding-top: 1rem;
  font-size: 0.75rem;
}

.article-aside__block {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  color: var(--steel-dark);
}

.article-aside__block .mono {
  color: var(--steel);
  font-size: 0.75rem;
}

.related-articles {
  background: #e7e1d5;
}

.related-articles__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.related-articles__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.related-article {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.related-article__meta {
  display: flex;
  justify-content: space-between;
  color: var(--steel-dark);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.5rem;
}

.related-article__title {
  font-size: 1.1rem;
  line-height: 1.2;
  font-weight: 550;
  letter-spacing: -0.02em;
}

.related-article:hover .related-article__title {
  color: var(--accent);
}

@media (max-width: 1100px) {
  .article-body-grid {
    grid-template-columns: 8rem minmax(0, 1fr);
  }

  .article-aside {
    display: none;
  }
}

@media (max-width: 760px) {
  .article-body-grid {
    grid-template-columns: 1fr;
  }

  .article-toc {
    position: static;
  }

  .related-articles__grid {
    grid-template-columns: 1fr;
  }

  .related-articles__head {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
