<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const project = computed(() => props.block.resolved?.project)
</script>

<template>
  <section v-if="project" class="section featured-project">
    <div class="container">
      <div class="featured-project__head">
        <p class="eyebrow">{{ p.eyebrow || 'Selected work' }}</p>
        <h2 class="h2 featured-project__heading">{{ p.heading }}</h2>
      </div>

      <NuxtLink :to="project.url" class="featured-project__link" data-reveal>
        <div class="featured-project__media">
          <AppImage v-if="project.image" :image="project.image" ratio="16:9" sizes="(max-width: 900px) 100vw, 58vw" />
          <div class="featured-project__overlay">
            <span class="mono">{{ project.year }} / {{ project.industry?.title || 'Industrial' }}</span>
            <span class="mono">ILLUSTRATIVE PROJECT</span>
          </div>
        </div>

        <div class="featured-project__content">
          <div class="featured-project__meta">
            <span class="mono">{{ project.industry?.title }}</span>
            <span class="mono">{{ project.client_label }}</span>
          </div>
          <h3 class="featured-project__title">{{ project.title }}</h3>
          <p class="featured-project__summary">{{ project.summary }}</p>

          <div v-if="project.results?.length" class="featured-project__metrics">
            <div v-for="result in project.results" :key="result.label" class="featured-project__metric">
              <span class="featured-project__value">{{ result.value }}</span>
              <span class="featured-project__label">{{ result.label }}</span>
            </div>
          </div>

          <span class="featured-project__cta text-link">
            Read the case study
            <AppIcon name="arrow-up-right" :size="14" />
          </span>
        </div>
      </NuxtLink>
    </div>
  </section>
</template>

<style scoped>
.featured-project {
  background: var(--ink);
  color: var(--ivory);
}

.featured-project__head {
  display: flex;
  justify-content: space-between;
  gap: 2rem;
  align-items: flex-start;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.featured-project__heading {
  max-width: 18ch;
  text-align: right;
}

.featured-project__link {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(1.5rem, 3vw, 3rem);
  align-items: stretch;
}

.featured-project__media {
  grid-column: 1 / span 7;
  position: relative;
}

.featured-project__media :deep(img) {
  filter: grayscale(1) contrast(1.1) brightness(0.78);
}

.featured-project__overlay {
  position: absolute;
  inset: auto 0 0;
  display: flex;
  justify-content: space-between;
  padding: 1rem 1.15rem;
  background: linear-gradient(0deg, rgba(14, 20, 17, 0.9), transparent);
  color: rgba(241, 237, 228, 0.65);
  font-size: 0.75rem;
}

.featured-project__content {
  grid-column: 8 / span 5;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding-block: 0.5rem;
}

.featured-project__meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  color: rgba(241, 237, 228, 0.42);
  border-bottom: 1px solid var(--line-light);
  padding-bottom: 1rem;
  font-size: 0.75rem;
}

.featured-project__title {
  margin-top: 1.5rem;
  font-size: clamp(2rem, 3.4vw, 3.5rem);
  line-height: 1.08;
  letter-spacing: -0.012em;
  font-weight: 500;
}

.featured-project__summary {
  margin-top: 1.25rem;
  color: rgba(241, 237, 228, 0.62);
  font-size: 0.92rem;
  line-height: 1.65;
}

.featured-project__metrics {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
  margin-top: 2rem;
  border-top: 1px solid var(--line-light);
  padding-top: 1.5rem;
}

.featured-project__metric {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.featured-project__value {
  font-family: var(--font-display);
  font-size: clamp(2rem, 3.2vw, 3rem);
  letter-spacing: -0.015em;
  line-height: 1;
  color: var(--ivory);
}

.featured-project__label {
  font-size: 0.7rem;
  color: rgba(241, 237, 228, 0.5);
}

.featured-project__cta {
  margin-top: 2rem;
  align-self: flex-start;
  border-color: var(--line-light-strong);
}

@media (max-width: 980px) {
  .featured-project__head {
    flex-direction: column;
  }

  .featured-project__heading {
    text-align: left;
  }

  .featured-project__link {
    grid-template-columns: 1fr;
  }

  .featured-project__media,
  .featured-project__content {
    grid-column: 1 / -1;
  }
}
</style>
