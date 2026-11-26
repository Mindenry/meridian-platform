<script setup lang="ts">
import type { IndustryDetail } from '~/types'

const route = useRoute()
const api = useApi()
const { localeCode, routeLocale } = useLocale()
const copy = useCopy()

const slug = computed(() => {
  const param = route.params.slug
  return Array.isArray(param) ? String(param[0] || '') : String(param || '')
})

const { data, error } = await useAsyncData(
  `industry-${routeLocale.value}-${slug.value}`,
  () => api.get<{ data: IndustryDetail }>(`/public/industries/${encodeURIComponent(slug.value)}`, {
    query: { locale: localeCode.value },
  }),
  { watch: [slug, routeLocale] },
)

if (error.value) throw createError({ statusCode: 404, statusMessage: 'Industry not found' })

const industry = computed<IndustryDetail | null>(() => data.value?.data ?? null)
usePageSeo(industry)

const labels = computed(() => ({
  challengeEyebrow: localeCode.value === 'th' ? 'บริบท' : localeCode.value === 'zh-CN' ? '背景' : 'The context',
  challengeHeading: localeCode.value === 'th' ? 'แรงกดดันที่ทำให้การดำเนินงานยากขึ้น' : localeCode.value === 'zh-CN' ? '让运营更困难的压力。' : 'The pressures that make operations harder.',
  capabilityEyebrow: localeCode.value === 'th' ? 'สิ่งที่เรานำมา' : localeCode.value === 'zh-CN' ? '我们带来的能力' : 'What we bring',
  capabilityHeading: localeCode.value === 'th' ? 'ความสามารถที่ออกแบบตามบริบท' : localeCode.value === 'zh-CN' ? '贴合语境的能力。' : 'Capability shaped by the context.',
  solutionEyebrow: localeCode.value === 'th' ? 'โซลูชัน' : localeCode.value === 'zh-CN' ? '解决方案' : 'Solutions',
  solutionHeading: localeCode.value === 'th' ? 'เส้นทางที่มักเหมาะสมกับอุตสาหกรรมนี้' : localeCode.value === 'zh-CN' ? '通常适合该行业的路径。' : 'Paths that usually fit this sector.',
  projectEyebrow: localeCode.value === 'th' ? 'ผลงาน' : localeCode.value === 'zh-CN' ? '案例' : 'Selected work',
  projectHeading: localeCode.value === 'th' ? 'งานที่วัดผลในบริบทนี้' : localeCode.value === 'zh-CN' ? '在该语境中衡量的工作。' : 'Work measured in this context.',
  ctaEyebrow: localeCode.value === 'th' ? 'พูดคุย' : localeCode.value === 'zh-CN' ? '交流' : 'Talk to us',
  ctaHeading: localeCode.value === 'th' ? 'บอกเราว่าข้อจำกัดอยู่ที่ใด' : localeCode.value === 'zh-CN' ? '告诉我们约束在哪里。' : 'Tell us where the constraint sits.',
  ctaBody: localeCode.value === 'th' ? 'เราจะช่วยแยกแยะระหว่างปัญหาการดำเนินงาน ปัญหาข้อมูล และปัญหาการออกแบบระบบ' : localeCode.value === 'zh-CN' ? '我们会帮助区分运营问题、数据问题与系统设计问题。' : 'We will help separate an operating problem from a data problem and a system design problem.',
}))
</script>

<template>
  <div v-if="industry">
    <Breadcrumbs :items="industry.breadcrumbs" />
    <FallbackNotice v-if="industry.is_fallback" :locale="industry.content_locale || 'en'" />

    <header class="industry-hero">
      <div class="industry-hero__grid" aria-hidden="true" />
      <div class="container industry-hero__inner">
        <div class="industry-hero__content">
          <p class="eyebrow">{{ localeCode === 'th' ? 'อุตสาหกรรม' : localeCode === 'zh-CN' ? '行业' : 'Industry' }}</p>
          <h1 class="industry-hero__title">{{ industry.title }}</h1>
          <p class="industry-hero__tagline">{{ industry.tagline }}</p>
          <ButtonLink :label="copy.discuss" :to="`/${routeLocale}/contact`" variant="light" />
        </div>
        <div v-if="industry.image" class="industry-hero__media">
          <AppImage :image="industry.image" ratio="4:3" sizes="(max-width: 900px) 100vw, 40vw" priority />
        </div>
      </div>
    </header>

    <section class="section industry-overview">
      <div class="container">
        <div class="industry-overview__grid">
          <p class="industry-overview__text" data-reveal>{{ industry.overview }}</p>
          <div v-if="industry.stats?.length" class="industry-stats" data-reveal>
            <div v-for="stat in industry.stats" :key="stat.label" class="industry-stat">
              <span class="industry-stat__value">{{ stat.value }}</span>
              <span class="industry-stat__label">{{ stat.label }}</span>
              <span v-if="stat.note" class="mono industry-stat__note">{{ stat.note }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section v-if="industry.challenges?.length" class="section industry-challenges">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ labels.challengeEyebrow }}</p>
          <h2 class="h2">{{ labels.challengeHeading }}</h2>
        </div>
        <div class="challenge-list">
          <article v-for="(challenge, index) in industry.challenges" :key="challenge.title" class="challenge" data-reveal>
            <span class="mono">{{ String(index + 1).padStart(2, '0') }}</span>
            <h3 class="challenge__title">{{ challenge.title }}</h3>
            <p class="challenge__body">{{ challenge.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section v-if="industry.capabilities?.length" class="section industry-capabilities">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ labels.capabilityEyebrow }}</p>
          <h2 class="h2">{{ labels.capabilityHeading }}</h2>
        </div>
        <div class="capability-tags">
          <span v-for="capability in industry.capabilities" :key="capability" class="capability-tag">{{ capability }}</span>
        </div>
      </div>
    </section>

    <section v-if="industry.solutions?.length" class="section industry-solutions">
      <div class="container">
        <div class="section-head">
          <p class="eyebrow">{{ labels.solutionEyebrow }}</p>
          <h2 class="h2">{{ labels.solutionHeading }}</h2>
        </div>
        <div class="industry-solutions__grid">
          <NuxtLink v-for="solution in industry.solutions" :key="solution.key" :to="solution.url" class="solution-chip" data-reveal>
            <span class="mono">{{ solution.key }}</span>
            <span class="solution-chip__title">{{ solution.title }}</span>
            <span class="solution-chip__tagline">{{ solution.tagline }}</span>
            <AppIcon name="arrow-up-right" :size="17" />
          </NuxtLink>
        </div>
      </div>
    </section>

    <section v-if="industry.related_projects?.length" class="section industry-projects">
      <div class="container">
        <div class="section-head section-head--inline">
          <div>
            <p class="eyebrow">{{ labels.projectEyebrow }}</p>
            <h2 class="h2">{{ labels.projectHeading }}</h2>
          </div>
          <NuxtLink :to="`/${routeLocale}/case-studies`" class="text-link">{{ copy.allCaseStudies }}</NuxtLink>
        </div>
        <div class="project-row">
          <NuxtLink v-for="project in industry.related_projects" :key="project.id" :to="project.url" class="project-mini" data-reveal>
            <AppImage v-if="project.image" :image="project.image" ratio="3:2" sizes="(max-width: 900px) 100vw, 33vw" />
            <div class="project-mini__meta mono">
              <span>{{ project.year }}</span>
              <span>{{ project.industry?.title }}</span>
            </div>
            <h3 class="project-mini__title">{{ project.title }}</h3>
          </NuxtLink>
        </div>
      </div>
    </section>

    <SiteCta
      :eyebrow="labels.ctaEyebrow"
      :heading="labels.ctaHeading"
      :body="labels.ctaBody"
      :primary-label="copy.discuss"
      :primary-to="`/${routeLocale}/contact`"
      :secondary-label="copy.allIndustries"
      :secondary-to="`/${routeLocale}/industries`"
    />
  </div>
</template>

<style scoped>
.industry-hero {
  background: var(--ink);
  color: var(--ivory);
  position: relative;
  overflow: hidden;
  padding-block: clamp(3.5rem, 8vw, 7rem);
}

.industry-hero__grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(to right, rgba(241, 237, 228, 0.055) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%;
}

.industry-hero__inner {
  position: relative;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(2rem, 4vw, 4rem);
  align-items: end;
}

.industry-hero__content {
  grid-column: 1 / span 7;
}

.industry-hero__title {
  margin-top: 1.25rem;
  font-size: clamp(3rem, 6.5vw, 6.8rem);
  line-height: 0.92;
  letter-spacing: -0.057em;
  font-weight: 500;
  max-width: 12ch;
}

.industry-hero__tagline {
  margin: 1.5rem 0 2rem;
  max-width: 46ch;
  font-size: clamp(1rem, 1.3vw, 1.2rem);
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.65);
}

.industry-hero__media {
  grid-column: 9 / span 4;
}

.industry-hero__media :deep(img) {
  filter: grayscale(1) contrast(1.1) brightness(0.8);
}

.industry-overview {
  background: var(--ivory);
}

.industry-overview__grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: clamp(2rem, 6vw, 7rem);
  align-items: start;
}

.industry-overview__text {
  font-size: clamp(1.35rem, 2.3vw, 2.2rem);
  line-height: 1.38;
  letter-spacing: -0.03em;
  max-width: 30ch;
}

.industry-stats {
  border-top: 1px solid var(--line);
}

.industry-stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding-block: 1rem;
  border-bottom: 1px solid var(--line);
}

.industry-stat__value {
  font-size: clamp(1.8rem, 3vw, 2.6rem);
  letter-spacing: -0.05em;
  line-height: 1;
}

.industry-stat__label {
  font-size: 0.78rem;
  color: var(--steel-dark);
}

.industry-stat__note {
  color: var(--steel);
  font-size: 0.75rem;
}

.section-head {
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.section-head .h2 {
  margin-top: 1.4rem;
  max-width: 18ch;
}

.industry-challenges {
  background: #e7e1d5;
}

.challenge-list {
  border-top: 1px solid var(--line);
}

.challenge {
  display: grid;
  grid-template-columns: 3rem 1fr 1.2fr;
  gap: 1.5rem;
  padding-block: 1.35rem;
  border-bottom: 1px solid var(--line);
  align-items: baseline;
}

.challenge .mono {
  color: var(--steel);
  font-size: 0.75rem;
}

.challenge__title {
  font-size: 1.15rem;
  font-weight: 560;
  letter-spacing: -0.02em;
}

.challenge__body {
  font-size: 0.86rem;
  line-height: 1.55;
  color: var(--steel-dark);
}

.industry-capabilities {
  background: var(--ivory);
}

.capability-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.capability-tag {
  border: 1px solid var(--line);
  padding: 0.65rem 0.85rem;
  font-size: 0.9rem;
}

.industry-solutions {
  background: #e7e1d5;
}

.industry-solutions__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
}

.solution-chip {
  border: 1px solid var(--line);
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 220px;
  transition: background 200ms ease, color 200ms ease, border-color 200ms ease;
}

.solution-chip:hover {
  background: var(--ink);
  color: var(--ivory);
  border-color: var(--ink);
}

.solution-chip .mono {
  color: var(--steel);
  font-size: 0.75rem;
}

.solution-chip__title {
  font-size: 1.3rem;
  font-weight: 550;
  letter-spacing: -0.025em;
}

.solution-chip__tagline {
  font-size: 0.82rem;
  line-height: 1.55;
  color: var(--steel-dark);
}

.solution-chip:hover .solution-chip__tagline {
  color: rgba(241, 237, 228, 0.6);
}

.solution-chip > svg {
  margin-top: auto;
  align-self: flex-end;
}

.section-head--inline {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 2rem;
}

.project-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.5rem;
}

.project-mini {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.project-mini__meta {
  display: flex;
  justify-content: space-between;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.5rem;
}

.project-mini__title {
  font-size: 1.1rem;
  font-weight: 550;
  letter-spacing: -0.02em;
}

@media (max-width: 900px) {
  .industry-hero__content,
  .industry-hero__media {
    grid-column: 1 / -1;
  }

  .industry-overview__grid,
  .industry-solutions__grid,
  .project-row {
    grid-template-columns: 1fr;
  }

  .challenge {
    grid-template-columns: 2rem 1fr;
  }

  .challenge__body {
    grid-column: 2;
  }

  .section-head--inline {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
