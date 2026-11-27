<script setup lang="ts">
import type { IndustrySummary } from '~/types'

const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const pageCopy = computed(() => ({
  en: {
    eyebrow: 'Industries',
    title: 'Each sector is constrained differently. We design for that.',
    body: 'We work inside the constraints of each sector: regulation, seasonality, asset life, and the expectations of the people who depend on the operation.',
    meta: ['MANUFACTURING', 'LOGISTICS', 'ENERGY', 'INFRASTRUCTURE', 'PROPERTY'],
    listEyebrow: 'Where we work',
    listTitle: 'Five sectors. One standard of evidence.',
    ctaEyebrow: 'Sector conversation',
    ctaHeading: 'Talk to someone who understands your operating context.',
    ctaBody: 'We will be explicit about where our experience is strong and where a different specialist should be involved.',
  },
  th: {
    eyebrow: 'อุตสาหกรรม',
    title: 'แต่ละอุตสาหกรรมมีข้อจำกัดต่างกัน เราออกแบบให้ตรงกับข้อจำกัดนั้น',
    body: 'เราทำงานภายใต้ข้อจำกัดของแต่ละอุตสาหกรรม ทั้งกฎระเบียบ ฤดูกาล อายุสินทรัพย์ และความคาดหวังของผู้ที่พึ่งพาการดำเนินงาน',
    meta: ['การผลิต', 'โลจิสติกส์', 'พลังงาน', 'โครงสร้างพื้นฐาน', 'อสังหาริมทรัพย์'],
    listEyebrow: 'พื้นที่ที่เราทำงาน',
    listTitle: 'ห้าอุตสาหกรรม มาตรฐานหลักฐานเดียว',
    ctaEyebrow: 'บทสนทนาเฉพาะอุตสาหกรรม',
    ctaHeading: 'คุยกับคนที่เข้าใจบริบทการดำเนินงานของคุณ',
    ctaBody: 'เราจะบอกอย่างชัดเจนว่าประสบการณ์ของเราแข็งแกร่งที่ใด และควรเชิญผู้เชี่ยวชาญด้านใดเข้ามา',
  },
  'zh-CN': {
    eyebrow: '行业',
    title: '每个行业的约束不同，设计也要随之不同。',
    body: '我们在每个行业的约束内工作：监管、季节性、资产寿命，以及依赖运营的人的期望。',
    meta: ['制造', '物流', '能源', '基础设施', '物业'],
    listEyebrow: '服务领域',
    listTitle: '五个行业，同一套证据标准。',
    ctaEyebrow: '行业对话',
    ctaHeading: '与理解您运营语境的人交流。',
    ctaBody: '我们会明确说明经验强项，以及何时应引入其他专家。',
  },
})[localeCode.value]!)

const { data, error } = await useAsyncData(
  `industries-${routeLocale.value}`,
  () => api.get<{ data: IndustrySummary[] }>('/public/industries', { query: { locale: localeCode.value } }),
  { watch: [routeLocale] },
)

if (error.value) throw createError({ statusCode: 503, statusMessage: 'Industries are unavailable.' })

const items = computed(() => data.value?.data ?? [])

useSimpleSeo({
  title: localeCode.value === 'th' ? 'อุตสาหกรรม — เมอริเดียน' : localeCode.value === 'zh-CN' ? '行业 — Meridian' : 'Industries — Meridian Industrial Group',
  description: pageCopy.value.body,
  path: `/${routeLocale.value}/industries`,
})
</script>

<template>
  <div>
    <PageHeader :eyebrow="pageCopy.eyebrow" :title="pageCopy.title" :body="pageCopy.body" :meta="pageCopy.meta" />

    <section class="section industry-list">
      <div class="container">
        <div class="industry-list__head">
          <p class="eyebrow">{{ pageCopy.listEyebrow }}</p>
          <h2 class="h2">{{ pageCopy.listTitle }}</h2>
        </div>

        <div class="industry-list__rows">
          <NuxtLink v-for="(industry, index) in items" :key="industry.key" :to="industry.url" class="industry-block" data-reveal>
            <div class="industry-block__media">
              <AppImage v-if="industry.image" :image="industry.image" ratio="16:9" sizes="(max-width: 900px) 100vw, 38vw" />
            </div>
            <div class="industry-block__body">
              <span class="mono industry-block__index">{{ String(index + 1).padStart(2, '0') }}</span>
              <h3 class="industry-block__title">{{ industry.title }}</h3>
              <p class="industry-block__tagline">{{ industry.tagline }}</p>
              <span class="text-link">
                {{ copy.explore }}
                <AppIcon name="arrow-up-right" :size="13" />
              </span>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>

    <SiteCta
      :eyebrow="pageCopy.ctaEyebrow"
      :heading="pageCopy.ctaHeading"
      :body="pageCopy.ctaBody"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
      :secondary-label="copy.allSolutions"
      :secondary-to="`/${routeLocale}/solutions`"
    />
  </div>
</template>

<style scoped>
.industry-list {
  background: var(--ivory);
}

.industry-list__head {
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.industry-list__head h2 {
  margin-top: 1.4rem;
  max-width: 17ch;
}

.industry-list__rows {
  border-top: 1px solid var(--line);
}

.industry-block {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: clamp(2rem, 5vw, 5rem);
  padding-block: clamp(2rem, 4vw, 3.5rem);
  border-bottom: 1px solid var(--line);
  align-items: center;
}

.industry-block:nth-child(even) .industry-block__media {
  order: 2;
}

.industry-block__body {
  max-width: 52ch;
}

.industry-block__index {
  color: var(--accent);
  font-size: 0.75rem;
}

.industry-block__title {
  margin-top: 0.7rem;
  font-size: clamp(2rem, 3.6vw, 3.6rem);
  line-height: 1;
  letter-spacing: -0.045em;
  font-weight: 500;
}

.industry-block__tagline {
  margin-top: 1rem;
  font-size: 1rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

.industry-block__body .text-link {
  margin-top: 1.5rem;
}

.industry-block:hover .industry-block__title {
  color: var(--accent);
  transition: color 180ms ease;
}

@media (max-width: 900px) {
  .industry-block {
    grid-template-columns: 1fr;
  }

  .industry-block:nth-child(even) .industry-block__media {
    order: 0;
  }
}
</style>
