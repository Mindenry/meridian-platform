<script setup lang="ts">
import type { SolutionDetail } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const slug = computed(() => {
  const param = route.params.slug
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `solution-${routeLocale.value}-${slug.value}`,
  () => api.get<{ data: SolutionDetail }>(`/public/solutions/${encodeURIComponent(slug.value)}`, {
    query: { locale: localeCode.value },
  }),
  { watch: [slug, routeLocale] },
)

if (error.value) throw createError({ statusCode: 404, statusMessage: 'Solution not found' })

const solution = computed<SolutionDetail | null>(() => data.value?.data ?? null)
usePageSeo(solution)

const sectionLabels = computed(() => ({
  problemEyebrow: localeCode.value === 'th' ? 'ความท้าทาย' : localeCode.value === 'zh-CN' ? '挑战' : 'The problem',
  problemHeading: localeCode.value === 'th'
    ? 'สิ่งที่มักขวางการดำเนินงานไว้'
    : localeCode.value === 'zh-CN'
      ? '通常阻碍运营的是什么。'
      : 'What usually gets in the way.',
  approachEyebrow: localeCode.value === 'th' ? 'แนวทาง' : localeCode.value === 'zh-CN' ? '方法' : 'The approach',
  approachHeading: localeCode.value === 'th'
    ? 'ลำดับการทำงานที่ลดความเสี่ยง'
    : localeCode.value === 'zh-CN'
      ? '降低风险的工作顺序。'
      : 'A sequence that reduces risk.',
  capabilityEyebrow: localeCode.value === 'th' ? 'ขีดความสามารถ' : localeCode.value === 'zh-CN' ? '能力' : 'Capabilities',
  capabilityHeading: localeCode.value === 'th'
    ? 'สิ่งที่เรานำมาสู่ระบบ'
    : localeCode.value === 'zh-CN'
      ? '我们为系统带来的能力。'
      : 'What we bring to the system.',
  benefitEyebrow: localeCode.value === 'th' ? 'ผลลัพธ์' : localeCode.value === 'zh-CN' ? '成果' : 'Outcomes',
  benefitHeading: localeCode.value === 'th'
    ? 'สิ่งที่ควรเปลี่ยนหลังส่งมอบ'
    : localeCode.value === 'zh-CN'
      ? '移交后应改变的事。'
      : 'What should change after handover.',
  relatedHeading: localeCode.value === 'th' ? 'โครงการที่เกี่ยวข้อง' : localeCode.value === 'zh-CN' ? '相关项目' : 'Related projects',
  industriesHeading: localeCode.value === 'th' ? 'อุตสาหกรรมที่ใช้โซลูชันนี้' : localeCode.value === 'zh-CN' ? '应用此方案的行业' : 'Industries using this solution',
  ctaEyebrow: localeCode.value === 'th' ? 'เริ่มต้น' : localeCode.value === 'zh-CN' ? '开始' : 'Get started',
  ctaHeading: localeCode.value === 'th' ? 'พูดคุยเกี่ยวกับข้อจำกัดของคุณ' : localeCode.value === 'zh-CN' ? '谈谈您的约束。' : 'Talk through your constraint.',
  ctaBody: localeCode.value === 'th'
    ? 'เราจะช่วยประเมินว่าจุดเริ่มต้นที่เหมาะสมคืออะไร และอะไรที่ยังไม่ควรทำ'
    : localeCode.value === 'zh-CN'
      ? '我们会帮助判断合适的起点，以及暂时不应该做什么。'
      : 'We will help identify the sensible starting point — and what not to do yet.',
}))
</script>

<template>
  <div v-if="solution">
    <Breadcrumbs :items="solution.breadcrumbs" />
    <FallbackNotice v-if="solution.is_fallback" :locale="solution.content_locale || 'en'" />

    <header class="detail-hero">
      <div class="detail-hero__grid" aria-hidden="true" />
      <div class="container detail-hero__inner">
        <div class="detail-hero__content">
          <p class="eyebrow">{{ $route.params.locale === 'th' ? 'โซลูชัน' : $route.params.locale === 'zh-cn' ? '解决方案' : 'Solutions' }}</p>
          <h1 class="detail-hero__title">{{ solution.title }}</h1>
          <p class="detail-hero__tagline">{{ solution.tagline }}</p>
          <div class="detail-hero__actions">
            <ButtonLink :label="copy.discuss" :to="`/${routeLocale}/contact`" variant="light" />
          </div>
        </div>
        <div v-if="solution.image" class="detail-hero__media" data-reveal>
          <AppImage :image="solution.image" ratio="4:3" sizes="(max-width: 900px) 100vw, 40vw" priority />
          <span class="mono detail-hero__media-note">MERIDIAN / SOLUTION</span>
        </div>
      </div>
    </header>

    <section class="section detail-overview">
      <div class="container">
        <div class="detail-overview__grid">
          <div data-reveal>
            <p class="eyebrow">{{ copy.overview }}</p>
            <p class="detail-overview__text">{{ solution.overview }}</p>
          </div>
          <aside class="detail-overview__facts" data-reveal>
            <div v-if="solution.industries.length" class="fact">
              <span class="mono">{{ copy.industries }}</span>
              <span class="fact__value">{{ solution.industries.map((industry) => industry.title).join(' · ') }}</span>
            </div>
            <div v-if="solution.capabilities.length" class="fact">
              <span class="mono">{{ copy.capabilities }}</span>
              <span class="fact__value">{{ solution.capabilities.length }} {{ localeCode === 'th' ? 'ด้าน' : localeCode === 'zh-CN' ? '项' : 'areas' }}</span>
            </div>
            <div class="fact">
              <span class="mono">{{ localeCode === 'th' ? 'รูปแบบการมีส่วนร่วม' : localeCode === 'zh-CN' ? '合作方式' : 'Engagement' }}</span>
              <span class="fact__value">{{ localeCode === 'th' ? 'ประเมิน · ออกแบบ · ส่งมอบ · ดูแล' : localeCode === 'zh-CN' ? '评估 · 设计 · 交付 · 支持' : 'Assess · Design · Deliver · Support' }}</span>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <section v-if="solution.problems.length" class="section problems">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ sectionLabels.problemEyebrow }}</p>
          <h2 class="h2">{{ sectionLabels.problemHeading }}</h2>
        </div>
        <div class="problem-grid">
          <article v-for="(problem, index) in solution.problems" :key="problem.title" class="problem" data-reveal>
            <span class="problem__number mono">{{ String(index + 1).padStart(2, '0') }}</span>
            <h3 class="problem__title">{{ problem.title }}</h3>
            <p class="problem__body">{{ problem.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section v-if="solution.approach.length" class="section approach">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ sectionLabels.approachEyebrow }}</p>
          <h2 class="h2">{{ sectionLabels.approachHeading }}</h2>
        </div>
        <div class="approach-grid">
          <article v-for="(step, index) in solution.approach" :key="step.title" class="approach-step" data-reveal>
            <span class="approach-step__number mono">{{ String(index + 1).padStart(2, '0') }}</span>
            <h3 class="approach-step__title">{{ step.title }}</h3>
            <p class="approach-step__body">{{ step.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section v-if="solution.capabilities.length" class="section capabilities-detail">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ sectionLabels.capabilityEyebrow }}</p>
          <h2 class="h2">{{ sectionLabels.capabilityHeading }}</h2>
        </div>
        <div class="capability-rows">
          <div v-for="(capability, index) in solution.capabilities" :key="capability.title" class="capability-row" data-reveal>
            <span class="mono">{{ String(index + 1).padStart(2, '0') }}</span>
            <h3 class="capability-row__title">{{ capability.title }}</h3>
            <p class="capability-row__body">{{ capability.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <section v-if="solution.benefits.length" class="section benefits">
      <div class="container">
        <div class="section-head section-head--light">
          <p class="eyebrow">{{ sectionLabels.benefitEyebrow }}</p>
          <h2 class="h2">{{ sectionLabels.benefitHeading }}</h2>
        </div>
        <div class="benefit-grid">
          <article v-for="benefit in solution.benefits" :key="benefit.title" class="benefit" data-reveal>
            <h3 class="benefit__title">{{ benefit.title }}</h3>
            <p class="benefit__body">{{ benefit.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section v-if="solution.industries.length" class="section related">
      <div class="container">
        <div class="related__head">
          <h2 class="h3">{{ sectionLabels.industriesHeading }}</h2>
        </div>
        <div class="related__links">
          <NuxtLink v-for="industry in solution.industries" :key="industry.key" :to="industry.url" class="related-link">
            {{ industry.title }}
            <AppIcon name="arrow-up-right" :size="15" />
          </NuxtLink>
        </div>
      </div>
    </section>

    <section v-if="solution.related_projects.length" class="section related-projects">
      <div class="container">
        <div class="related__head">
          <h2 class="h3">{{ sectionLabels.relatedHeading }}</h2>
          <NuxtLink :to="`/${routeLocale}/case-studies`" class="text-link">{{ copy.allCaseStudies }}</NuxtLink>
        </div>
        <div class="related-projects__grid">
          <NuxtLink v-for="project in solution.related_projects" :key="project.id" :to="project.url" class="related-project">
            <AppImage v-if="project.image" :image="project.image" ratio="3:2" sizes="(max-width: 900px) 100vw, 33vw" />
            <div class="related-project__meta mono">
              <span>{{ project.year }}</span>
              <span>{{ project.industry?.title }}</span>
            </div>
            <h3 class="related-project__title">{{ project.title }}</h3>
          </NuxtLink>
        </div>
      </div>
    </section>

    <SiteCta
      :eyebrow="sectionLabels.ctaEyebrow"
      :heading="sectionLabels.ctaHeading"
      :body="sectionLabels.ctaBody"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
      :secondary-label="copy.allSolutions"
      :secondary-to="`/${routeLocale}/solutions`"
    />
  </div>
</template>

<style scoped>
.detail-hero {
  background: var(--ink);
  color: var(--ivory);
  position: relative;
  overflow: hidden;
  padding-block: clamp(3.5rem, 8vw, 7rem);
}

.detail-hero__grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(to right, rgba(241, 237, 228, 0.055) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%;
}

.detail-hero__inner {
  position: relative;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(2rem, 4vw, 4rem);
  align-items: end;
}

.detail-hero__content {
  grid-column: 1 / span 7;
}

.detail-hero__title {
  margin-top: 1.25rem;
  font-size: clamp(2.9rem, 6vw, 6.2rem);
  line-height: 0.94;
  letter-spacing: -0.055em;
  font-weight: 500;
  max-width: 13ch;
}

.detail-hero__tagline {
  margin-top: 1.5rem;
  max-width: 48ch;
  font-size: clamp(1rem, 1.3vw, 1.2rem);
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.65);
}

.detail-hero__actions {
  margin-top: 2rem;
}

.detail-hero__media {
  grid-column: 9 / span 4;
  position: relative;
}

.detail-hero__media :deep(img) {
  filter: grayscale(1) contrast(1.1) brightness(0.8);
}

.detail-hero__media-note {
  display: block;
  margin-top: 0.6rem;
  color: rgba(241, 237, 228, 0.4);
  font-size: 0.75rem;
}

.detail-overview {
  background: var(--ivory);
}

.detail-overview__grid {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: clamp(2rem, 6vw, 7rem);
}

.detail-overview__text {
  margin-top: 1.5rem;
  font-size: clamp(1.35rem, 2.4vw, 2.3rem);
  line-height: 1.35;
  letter-spacing: -0.03em;
  color: var(--ink);
}

.detail-overview__facts {
  border-top: 1px solid var(--line);
}

.fact {
  padding-block: 1.1rem;
  border-bottom: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.fact .mono {
  color: var(--steel);
  font-size: 0.75rem;
}

.fact__value {
  font-size: 0.92rem;
  line-height: 1.5;
}

.section-head {
  margin-bottom: clamp(2rem, 4vw, 3.5rem);
}

.section-head .h2 {
  margin-top: 1.4rem;
  max-width: 18ch;
}

.problems {
  background: #e7e1d5;
}

.problem-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border-left: 1px solid var(--line);
  border-top: 1px solid var(--line);
}

.problem {
  padding: 1.5rem;
  border-right: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  min-height: 230px;
  display: flex;
  flex-direction: column;
}

.problem__number {
  color: var(--accent);
  font-size: 0.75rem;
}

.problem__title {
  margin-top: auto;
  font-size: 1.25rem;
  font-weight: 560;
  letter-spacing: -0.025em;
}

.problem__body {
  margin-top: 0.6rem;
  font-size: 0.85rem;
  line-height: 1.55;
  color: var(--steel-dark);
}

.approach {
  background: var(--ivory);
}

.approach-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  border-top: 1px solid var(--line);
}

.approach-step {
  padding: 1.5rem 1.5rem 2rem 0;
  border-right: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  min-height: 250px;
}

.approach-step:last-child {
  border-right: 0;
}

.approach-step__number {
  color: var(--accent);
  font-size: 0.75rem;
}

.approach-step__title {
  margin-top: 1.5rem;
  font-size: 1.15rem;
  font-weight: 560;
  letter-spacing: -0.025em;
}

.approach-step__body {
  margin-top: 0.6rem;
  font-size: 0.82rem;
  line-height: 1.55;
  color: var(--steel-dark);
}

.capabilities-detail {
  background: #e7e1d5;
}

.capability-rows {
  border-top: 1px solid var(--line);
}

.capability-row {
  display: grid;
  grid-template-columns: 3rem 1fr 1.4fr;
  gap: 1.5rem;
  padding-block: 1.3rem;
  border-bottom: 1px solid var(--line);
  align-items: baseline;
}

.capability-row .mono {
  color: var(--steel);
  font-size: 0.75rem;
}

.capability-row__title {
  font-size: 1.1rem;
  font-weight: 560;
  letter-spacing: -0.02em;
}

.capability-row__body {
  font-size: 0.86rem;
  line-height: 1.55;
  color: var(--steel-dark);
}

.benefits {
  background: var(--ink);
  color: var(--ivory);
}

.section-head--light .eyebrow {
  color: rgba(241, 237, 228, 0.45);
}

.benefit-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0;
  border-top: 1px solid var(--line-light);
  border-left: 1px solid var(--line-light);
}

.benefit {
  padding: clamp(1.5rem, 3vw, 2.5rem);
  border-right: 1px solid var(--line-light);
  border-bottom: 1px solid var(--line-light);
}

.benefit__title {
  font-size: clamp(1.2rem, 1.7vw, 1.5rem);
  font-weight: 550;
  letter-spacing: -0.025em;
}

.benefit__body {
  margin-top: 0.6rem;
  font-size: 0.86rem;
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.55);
  max-width: 46ch;
}

.related {
  background: var(--ivory);
}

.related__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.related__links {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.related-link {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  border: 1px solid var(--line);
  padding: 0.7rem 0.9rem;
  font-size: 0.88rem;
  transition: border-color 180ms ease, color 180ms ease, background 180ms ease;
}

.related-link:hover {
  border-color: var(--ink);
  background: var(--ink);
  color: var(--ivory);
}

.related-projects {
  background: #e7e1d5;
}

.related-projects__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.related-project {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.related-project__meta {
  display: flex;
  justify-content: space-between;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.5rem;
}

.related-project__title {
  font-size: 1.15rem;
  font-weight: 550;
  letter-spacing: -0.025em;
}

.related-project:hover .related-project__title {
  color: var(--accent);
}

@media (max-width: 900px) {
  .detail-hero__content,
  .detail-hero__media {
    grid-column: 1 / -1;
  }

  .detail-overview__grid,
  .related-projects__grid {
    grid-template-columns: 1fr;
  }

  .problem-grid,
  .approach-grid,
  .benefit-grid {
    grid-template-columns: 1fr;
  }

  .approach-step,
  .benefit {
    border-right: 0;
  }

  .capability-row {
    grid-template-columns: 2rem 1fr;
  }

  .capability-row__body {
    grid-column: 2;
  }
}
</style>
