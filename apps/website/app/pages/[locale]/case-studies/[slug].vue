<script setup lang="ts">
import type { CaseStudyDetail } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const slug = computed(() => {
  const param = route.params.slug
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `case-study-${routeLocale.value}-${slug.value}`,
  () => api.get<{ data: CaseStudyDetail }>(`/public/case-studies/${encodeURIComponent(slug.value)}`, {
    query: { locale: localeCode.value },
  }),
  { watch: [slug, routeLocale] },
)

if (error.value) throw createError({ statusCode: 404, statusMessage: 'Case study not found' })

const study = computed<CaseStudyDetail | null>(() => data.value?.data ?? null)
usePageSeo(study)

const labels = computed(() => ({
  challenge: localeCode.value === 'th' ? 'โจทย์' : localeCode.value === 'zh-CN' ? '挑战' : 'The challenge',
  strategy: localeCode.value === 'th' ? 'กลยุทธ์' : localeCode.value === 'zh-CN' ? '策略' : 'The strategy',
  implementation: localeCode.value === 'th' ? 'การดำเนินการ' : localeCode.value === 'zh-CN' ? '实施' : 'The implementation',
  results: localeCode.value === 'th' ? 'ผลลัพธ์' : localeCode.value === 'zh-CN' ? '成果' : 'The results',
  gallery: localeCode.value === 'th' ? 'ภาพโครงการ' : localeCode.value === 'zh-CN' ? '项目图片' : 'Project gallery',
  relatedHeading: localeCode.value === 'th' ? 'โครงการที่เกี่ยวข้อง' : localeCode.value === 'zh-CN' ? '相关项目' : 'Related projects',
  solutionsHeading: localeCode.value === 'th' ? 'โซลูชันที่ใช้' : localeCode.value === 'zh-CN' ? '应用的解决方案' : 'Solutions applied',
  next: localeCode.value === 'th' ? 'ดูผลงานทั้งหมด' : localeCode.value === 'zh-CN' ? '查看全部案例' : 'All case studies',
  note: localeCode.value === 'th' ? 'เนื้อหาพอร์ตโฟลิโอเพื่อการสาธิต ผลลัพธ์เป็นข้อมูลตัวอย่างและไม่ใช่ผลที่ลูกค้ายืนยัน' : localeCode.value === 'zh-CN' ? '示例作品集内容。成果为演示数据，并非客户确认结果。' : 'Illustrative portfolio content. Outcomes are demonstration data, not client-verified results.',
}))
</script>

<template>
  <div v-if="study">
    <Breadcrumbs :items="study.seo.breadcrumbs || []" />
    <FallbackNotice v-if="study.is_fallback" :locale="study.content_locale || 'en'" />

    <header class="case-hero">
      <div class="case-hero__grid" aria-hidden="true" />
      <div class="container case-hero__inner">
        <div class="case-hero__meta mono">
          <span>{{ study.year }}</span>
          <span>{{ study.industry?.title }}</span>
          <span>{{ study.client_label }}</span>
        </div>
        <h1 class="case-hero__title">{{ study.title }}</h1>
        <p class="case-hero__summary">{{ study.summary }}</p>
      </div>
    </header>

    <div v-if="study.image" class="case-image">
      <div class="container">
        <AppImage :image="study.image" ratio="21:9" sizes="100vw" priority />
        <p class="case-image__note mono">{{ study.illustrative_note || labels.note }}</p>
      </div>
    </div>

    <section v-if="study.results?.length" class="results-band">
      <div class="container results-band__grid">
        <div v-for="result in study.results" :key="result.label" class="result" data-reveal>
          <span class="result__value">{{ result.value }}</span>
          <span class="result__label">{{ result.label }}</span>
          <span class="mono result__note">{{ result.note }}</span>
        </div>
      </div>
    </section>

    <article class="case-body">
      <div class="container case-body__grid">
        <aside class="case-index" aria-label="Case study sections">
          <a href="#challenge" class="case-index__link">{{ labels.challenge }}</a>
          <a href="#strategy" class="case-index__link">{{ labels.strategy }}</a>
          <a href="#implementation" class="case-index__link">{{ labels.implementation }}</a>
          <a v-if="study.gallery?.length" href="#gallery" class="case-index__link">{{ labels.gallery }}</a>
        </aside>

        <div class="case-content">
          <section id="challenge" class="case-section">
            <p class="eyebrow">{{ labels.challenge }}</p>
            <h2 class="case-section__title">{{ labels.challenge }}</h2>
            <p class="case-section__text">{{ study.challenge }}</p>
          </section>
          <section id="strategy" class="case-section">
            <p class="eyebrow">{{ labels.strategy }}</p>
            <h2 class="case-section__title">{{ labels.strategy }}</h2>
            <p class="case-section__text">{{ study.strategy }}</p>
          </section>
          <section id="implementation" class="case-section">
            <p class="eyebrow">{{ labels.implementation }}</p>
            <h2 class="case-section__title">{{ labels.implementation }}</h2>
            <p class="case-section__text">{{ study.implementation }}</p>
          </section>
        </div>

        <aside class="case-side">
          <div v-if="study.industry" class="case-side__block">
            <p class="mono">{{ copy.industry }}</p>
            <NuxtLink :to="study.industry.url" class="case-side__link">{{ study.industry.title }}</NuxtLink>
          </div>
          <div v-if="study.solutions?.length" class="case-side__block">
            <p class="mono">{{ labels.solutionsHeading }}</p>
            <NuxtLink v-for="solution in study.solutions" :key="solution.slug" :to="solution.url" class="case-side__link">
              {{ solution.title }}
            </NuxtLink>
          </div>
          <p class="case-side__note">{{ study.illustrative_note || labels.note }}</p>
        </aside>
      </div>
    </article>

    <section v-if="study.gallery?.length" id="gallery" class="section case-gallery">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ labels.gallery }}</p>
          <h2 class="h2">{{ labels.gallery }}</h2>
        </div>
        <div class="case-gallery__grid">
          <figure v-for="(image, index) in study.gallery" :key="index" class="case-gallery__item">
            <img :src="image.url" :alt="image.alt" loading="lazy" decoding="async">
            <figcaption v-if="image.caption">{{ image.caption }}</figcaption>
          </figure>
        </div>
      </div>
    </section>

    <section v-if="study.related?.length" class="section related-cases">
      <div class="container">
        <div class="related__head">
          <h2 class="h3">{{ labels.relatedHeading }}</h2>
          <NuxtLink :to="`/${routeLocale}/case-studies`" class="text-link">{{ labels.next }}</NuxtLink>
        </div>
        <div class="related-cases__grid">
          <NuxtLink v-for="related in study.related" :key="related.id" :to="related.url" class="related-case">
            <AppImage v-if="related.image" :image="related.image" ratio="3:2" sizes="(max-width: 900px) 100vw, 33vw" />
            <div class="related-case__meta mono">
              <span>{{ related.year }}</span>
              <span>{{ related.industry?.title }}</span>
            </div>
            <h3 class="related-case__title">{{ related.title }}</h3>
          </NuxtLink>
        </div>
      </div>
    </section>

    <SiteCta
      :eyebrow="copy.talkToTeam"
      :heading="localeCode === 'th' ? 'โจทย์ของคุณอาจคล้ายกัน แต่วิธีแก้ไม่เหมือนกัน' : localeCode === 'zh-CN' ? '您的问题可能相似，但解法不会相同。' : 'Your problem may rhyme. The answer will not be the same.'"
      :body="localeCode === 'th' ? 'เล่าให้เราฟังว่าอะไรกำลังจำกัดการดำเนินงาน และอะไรหยุดไม่ได้' : localeCode === 'zh-CN' ? '告诉我们什么在约束运营、什么不能失败。' : 'Tell us what is constraining the operation and what cannot fail.'"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
    />
  </div>
</template>

<style scoped>
.case-hero {
  background: var(--ink);
  color: var(--ivory);
  position: relative;
  overflow: hidden;
  padding-block: clamp(3.5rem, 7vw, 6rem);
}

.case-hero__grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(to right, rgba(241, 237, 228, 0.05) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%;
}

.case-hero__inner {
  position: relative;
}

.case-hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
  color: rgba(241, 237, 228, 0.4);
  font-size: 0.75rem;
}

.case-hero__title {
  margin-top: 1.5rem;
  font-size: clamp(2.8rem, 6vw, 6.4rem);
  line-height: 0.93;
  letter-spacing: -0.058em;
  font-weight: 500;
  max-width: 17ch;
}

.case-hero__summary {
  margin-top: 1.5rem;
  max-width: 62ch;
  font-size: clamp(1rem, 1.25vw, 1.15rem);
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.65);
}

.case-image {
  background: var(--ink);
  padding-bottom: 1.25rem;
}

.case-image :deep(img) {
  filter: grayscale(1) contrast(1.1) brightness(0.8);
}

.case-image__note {
  margin-top: 0.75rem;
  color: rgba(241, 237, 228, 0.4);
  font-size: 0.75rem;
}

.results-band {
  background: #e7e1d5;
  padding-block: 2rem;
}

.results-band__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.result {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  border-left: 1px solid var(--line-strong);
  padding-left: 1.25rem;
}

.result__value {
  font-size: clamp(2.2rem, 3.8vw, 3.6rem);
  letter-spacing: -0.055em;
  line-height: 1;
}

.result__label {
  font-size: 0.82rem;
  color: var(--steel-dark);
}

.result__note {
  color: var(--steel);
  font-size: 0.75rem;
}

.case-body {
  background: var(--ivory);
  padding-block: clamp(4rem, 8vw, 7rem);
}

.case-body__grid {
  display: grid;
  grid-template-columns: 8rem 1fr 16rem;
  gap: clamp(2rem, 5vw, 5rem);
  align-items: start;
}

.case-index {
  position: sticky;
  top: calc(var(--header-h) + 2rem);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.case-index__link {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--steel-dark);
}

.case-index__link:hover {
  color: var(--accent);
}

.case-content {
  display: flex;
  flex-direction: column;
  gap: clamp(3rem, 6vw, 5rem);
}

.case-section__title {
  margin-top: 0.75rem;
  font-size: clamp(1.7rem, 2.6vw, 2.4rem);
  line-height: 1.1;
  letter-spacing: -0.035em;
  font-weight: 550;
  color: var(--ink);
}

.case-section__text {
  margin-top: 1rem;
  font-size: 1.05rem;
  line-height: 1.8;
  color: var(--ink-2);
  max-width: 68ch;
}

.case-side {
  position: sticky;
  top: calc(var(--header-h) + 2rem);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.case-side__block {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.case-side__block .mono {
  color: var(--steel);
  font-size: 0.75rem;
  margin-bottom: 0.25rem;
}

.case-side__link {
  font-size: 0.88rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.3rem;
  transition: color 160ms ease;
}

.case-side__link:hover {
  color: var(--accent);
}

.case-side__note {
  font-size: 0.75rem;
  line-height: 1.55;
  color: var(--steel);
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.case-gallery {
  background: #e7e1d5;
}

.section-head .h2 {
  margin-top: 1.4rem;
}

.case-gallery__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.5rem;
}

.case-gallery__item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.case-gallery__item img {
  width: 100%;
  aspect-ratio: 3 / 2;
  object-fit: cover;
  filter: grayscale(0.6) contrast(1.03);
}

.case-gallery__item figcaption {
  font-size: 0.75rem;
  color: var(--steel-dark);
}

.related-cases {
  background: var(--ivory);
}

.related__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.related-cases__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.related-case {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.related-case__meta {
  display: flex;
  justify-content: space-between;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.5rem;
}

.related-case__title {
  font-size: 1.1rem;
  font-weight: 550;
  letter-spacing: -0.02em;
}

.related-case:hover .related-case__title {
  color: var(--accent);
}

@media (max-width: 980px) {
  .case-body__grid {
    grid-template-columns: 1fr;
  }

  .case-index,
  .case-side {
    position: static;
  }

  .case-index {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 1rem;
  }

  .results-band__grid {
    grid-template-columns: 1fr;
  }

  .related-cases__grid,
  .case-gallery__grid {
    grid-template-columns: 1fr;
  }

  .related__head {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
