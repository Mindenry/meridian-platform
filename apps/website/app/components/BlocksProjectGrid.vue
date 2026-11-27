<script setup lang="ts">
import type { Block, CaseStudySummary } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed<CaseStudySummary[]>(() => props.block.resolved?.items ?? [])
</script>

<template>
  <section class="section project-grid" :class="`project-grid--${p.theme || 'light'}`">
    <div class="container">
      <div class="project-grid__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 project-grid__heading">{{ p.heading }}</h2>
        </div>
        <NuxtLink :to="`/${$route.params.locale || 'th'}/case-studies`" class="text-link" data-reveal>
          All case studies
          <AppIcon name="arrow-right" :size="13" />
        </NuxtLink>
      </div>

      <div class="project-grid__items" :class="{ 'project-grid__items--single': items.length === 1 }">
        <NuxtLink
          v-for="(project, index) in items"
          :key="project.id"
          :to="project.url"
          class="project-card"
          :class="{ 'project-card--lead': index === 0 }"
          data-reveal
        >
          <div class="project-card__media">
            <AppImage v-if="project.image" :image="project.image" :ratio="index === 0 ? '4:3' : '3:2'" :sizes="index === 0 ? '(max-width: 900px) 100vw, 56vw' : '(max-width: 900px) 100vw, 38vw'" />
            <span class="project-card__year mono">{{ project.year }}</span>
          </div>
          <div class="project-card__body">
            <div class="project-card__meta mono">
              <span>{{ project.industry?.title || 'Industrial' }}</span>
              <span>{{ project.results?.[0]?.value }} {{ project.results?.[0]?.label }}</span>
            </div>
            <h3 class="project-card__title">{{ project.title }}</h3>
            <p class="project-card__summary">{{ project.summary }}</p>
            <span class="project-card__arrow"><AppIcon name="arrow-up-right" :size="17" /></span>
          </div>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.project-grid {
  background: var(--ivory);
}

.project-grid--muted {
  background: #e7e1d5;
}

.project-grid--dark {
  background: var(--ink);
  color: var(--ivory);
}

.project-grid__head {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 2rem;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.project-grid__heading {
  margin-top: 1.4rem;
  max-width: 17ch;
}

.project-grid__items {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(1.5rem, 3vw, 3rem);
  align-items: start;
}

.project-grid__items--single {
  grid-template-columns: 1fr;
}

.project-card {
  grid-column: span 6;
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
  min-width: 0;
}

.project-card--lead {
  grid-column: 1 / span 7;
  grid-row: 1 / span 2;
}

.project-card:nth-child(2) {
  grid-column: 8 / span 5;
  grid-row: 1;
}

.project-card:nth-child(3) {
  grid-column: 8 / span 5;
  grid-row: 2;
}

.project-card__media {
  position: relative;
  overflow: hidden;
}

.project-card__year {
  position: absolute;
  top: 0.9rem;
  left: 0.9rem;
  background: var(--ivory);
  color: var(--ink);
  padding: 0.28rem 0.45rem;
  font-size: 0.75rem;
}

.project-card__body {
  position: relative;
  padding-right: 2rem;
}

.project-card__meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: var(--steel);
  font-size: 0.75rem;
  border-bottom: 1px solid var(--line);
  padding-bottom: 0.65rem;
}

.project-grid--dark .project-card__meta {
  border-color: var(--line-light);
  color: rgba(241, 237, 228, 0.45);
}

.project-card__title {
  margin-top: 0.9rem;
  font-size: clamp(1.25rem, 2vw, 1.9rem);
  line-height: 1.1;
  letter-spacing: -0.035em;
  font-weight: 550;
  max-width: 24ch;
}

.project-card--lead .project-card__title {
  font-size: clamp(1.6rem, 2.6vw, 2.6rem);
}

.project-card__summary {
  margin-top: 0.65rem;
  font-size: 0.86rem;
  line-height: 1.55;
  color: var(--steel-dark);
  max-width: 46ch;
}

.project-grid--dark .project-card__summary {
  color: rgba(241, 237, 228, 0.55);
}

.project-card__arrow {
  position: absolute;
  right: 0;
  bottom: 0;
  color: var(--steel);
  transition: transform 240ms var(--ease), color 180ms ease;
}

.project-card:hover .project-card__arrow {
  transform: translate(2px, -2px);
  color: var(--accent);
}

.project-card:hover .project-card__title {
  color: var(--accent);
  transition: color 180ms ease;
}

@media (max-width: 900px) {
  .project-grid__head {
    flex-direction: column;
    align-items: flex-start;
  }

  .project-grid__items {
    grid-template-columns: 1fr;
  }

  .project-card,
  .project-card--lead,
  .project-card:nth-child(2),
  .project-card:nth-child(3) {
    grid-column: 1 / -1;
    grid-row: auto;
  }
}
</style>
