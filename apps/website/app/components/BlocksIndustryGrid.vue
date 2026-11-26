<script setup lang="ts">
import type { Block, IndustrySummary } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed<IndustrySummary[]>(() => props.block.resolved?.items ?? [])
</script>

<template>
  <section class="section industries" :class="`industries--${p.theme || 'dark'}`">
    <div class="container">
      <div class="industries__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 industries__heading">{{ p.heading }}</h2>
        </div>
        <p v-if="p.intro" class="industries__intro" data-reveal>{{ p.intro }}</p>
      </div>

      <div class="industries__list">
        <NuxtLink v-for="(industry, index) in items" :key="industry.key" :to="industry.url" class="industry-row" data-reveal>
          <span class="industry-row__index mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
          <span class="industry-row__title">{{ industry.title }}</span>
          <span class="industry-row__tagline">{{ industry.tagline }}</span>
          <span v-if="industry.image" class="industry-row__image">
            <AppImage :image="industry.image" ratio="16:9" sizes="240px" />
          </span>
          <span class="industry-row__arrow"><AppIcon name="arrow-up-right" :size="20" /></span>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.industries {
  background: var(--ink);
  color: var(--ivory);
}

.industries--light {
  background: var(--ivory);
  color: var(--ink);
}

.industries__head {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 2rem;
  align-items: end;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.industries__head > :first-child {
  grid-column: 1 / span 7;
}

.industries__heading {
  margin-top: 1.4rem;
  max-width: 15ch;
}

.industries__intro {
  grid-column: 9 / span 4;
  font-size: 0.95rem;
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.6);
}

.industries--light .industries__intro {
  color: var(--steel-dark);
}

.industries__list {
  border-top: 1px solid var(--line-light);
}

.industries--light .industries__list {
  border-color: var(--line);
}

.industry-row {
  display: grid;
  grid-template-columns: 3rem 1.1fr 1fr 240px 2rem;
  gap: clamp(1rem, 2vw, 2.5rem);
  align-items: center;
  padding-block: clamp(1.15rem, 2vw, 1.75rem);
  border-bottom: 1px solid var(--line-light);
  transition: padding-left 280ms var(--ease), background 200ms ease;
}

.industries--light .industry-row {
  border-color: var(--line);
}

.industry-row:hover {
  padding-left: 0.75rem;
  background: rgba(241, 237, 228, 0.035);
}

.industries--light .industry-row:hover {
  background: rgba(14, 20, 17, 0.025);
}

.industry-row__index {
  color: rgba(241, 237, 228, 0.35);
  font-size: 0.75rem;
}

.industries--light .industry-row__index {
  color: var(--steel);
}

.industry-row__title {
  font-size: clamp(1.25rem, 1.9vw, 1.9rem);
  letter-spacing: -0.035em;
  font-weight: 500;
}

.industry-row__tagline {
  font-size: 0.86rem;
  line-height: 1.5;
  color: rgba(241, 237, 228, 0.55);
}

.industries--light .industry-row__tagline {
  color: var(--steel-dark);
}

.industry-row__image {
  width: 240px;
  opacity: 0.72;
  transition: opacity 220ms ease;
}

.industry-row:hover .industry-row__image {
  opacity: 1;
}

.industry-row__arrow {
  justify-self: end;
  color: rgba(241, 237, 228, 0.5);
  transition: transform 240ms var(--ease), color 180ms ease;
}

.industries--light .industry-row__arrow {
  color: var(--steel);
}

.industry-row:hover .industry-row__arrow {
  transform: translate(2px, -2px);
  color: var(--accent);
}

@media (max-width: 980px) {
  .industries__head {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .industries__head > :first-child,
  .industries__intro {
    grid-column: 1 / -1;
  }

  .industry-row {
    grid-template-columns: 2.25rem 1fr 2rem;
    gap: 0.6rem 1rem;
  }

  .industry-row__tagline {
    grid-column: 2 / 3;
  }

  .industry-row__image {
    display: none;
  }

  .industry-row__arrow {
    grid-column: 3;
    grid-row: 1;
  }
}
</style>
