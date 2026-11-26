<script setup lang="ts">
import type { Block, SolutionSummary } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed<SolutionSummary[]>(() => props.block.resolved?.items ?? [])
</script>

<template>
  <section class="section solutions" :class="`solutions--${p.theme || 'light'}`">
    <div class="container">
      <div class="solutions__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 solutions__heading">{{ p.heading }}</h2>
        </div>
        <div class="solutions__head-side" data-reveal>
          <p v-if="p.intro" class="solutions__intro">{{ p.intro }}</p>
          <NuxtLink :to="`/${$route.params.locale || 'th'}/solutions`" class="text-link">
            {{ p.theme === 'dark' ? 'All capabilities' : 'View all solutions' }}
            <AppIcon name="arrow-right" :size="13" />
          </NuxtLink>
        </div>
      </div>

      <div class="solutions__list">
        <NuxtLink v-for="(item, index) in items" :key="item.key" :to="item.url" class="solution-row" data-reveal>
          <span class="solution-row__index mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
          <span class="solution-row__title">{{ item.title }}</span>
          <span class="solution-row__tagline">{{ item.tagline }}</span>
          <span v-if="item.image" class="solution-row__image">
            <AppImage :image="item.image" ratio="3:2" sizes="220px" />
          </span>
          <span class="solution-row__arrow"><AppIcon name="arrow-up-right" :size="20" /></span>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<style scoped>
.solutions {
  background: var(--ivory);
}

.solutions--muted {
  background: #e7e1d5;
}

.solutions--dark {
  background: var(--ink);
  color: var(--ivory);
}

.solutions__head {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 2rem;
  align-items: end;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.solutions__head > :first-child {
  grid-column: 1 / span 7;
}

.solutions__heading {
  margin-top: 1.4rem;
  max-width: 16ch;
}

.solutions__head-side {
  grid-column: 9 / span 4;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  align-items: flex-start;
}

.solutions__intro {
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--steel-dark);
}

.solutions--dark .solutions__intro {
  color: rgba(241, 237, 228, 0.6);
}

.solutions--dark .text-link {
  border-color: var(--line-light-strong);
}

.solutions__list {
  border-top: 1px solid var(--line);
}

.solutions--dark .solutions__list {
  border-color: var(--line-light);
}

.solution-row {
  display: grid;
  grid-template-columns: 3rem 1.2fr 1fr 200px 2rem;
  gap: clamp(1rem, 2vw, 2.5rem);
  align-items: center;
  padding-block: clamp(1.15rem, 2vw, 1.7rem);
  border-bottom: 1px solid var(--line);
  position: relative;
  transition: background 240ms ease, color 240ms ease, padding 280ms var(--ease);
}

.solution-row::before {
  content: "";
  position: absolute;
  inset: 0 auto 0 0;
  width: 3px;
  background: var(--accent);
  transform: scaleY(0);
  transform-origin: top;
  transition: transform 280ms var(--ease);
}

.solutions--dark .solution-row {
  border-color: var(--line-light);
}

.solution-row:hover {
  padding-left: 1.1rem;
  padding-right: 1rem;
  background: var(--ink);
  color: var(--ivory);
}

.solution-row:hover::before {
  transform: scaleY(1);
}

.solutions--dark .solution-row:hover {
  background: var(--ivory);
  color: var(--ink);
}

.solution-row__index {
  color: var(--steel);
  font-size: 0.75rem;
}

.solution-row:hover .solution-row__index {
  color: var(--accent);
}

.solution-row__title {
  font-size: clamp(1.3rem, 2vw, 1.95rem);
  letter-spacing: -0.012em;
  font-weight: 500;
  line-height: 1.15;
}

.solution-row__tagline {
  font-size: 0.86rem;
  line-height: 1.55;
  color: var(--steel-dark);
  max-width: 40ch;
  transition: color 220ms ease;
}

.solution-row:hover .solution-row__tagline {
  color: rgba(241, 237, 228, 0.72);
}

.solutions--dark .solution-row:hover .solution-row__tagline {
  color: var(--steel-dark);
}

.solutions--dark .solution-row__tagline {
  color: rgba(241, 237, 228, 0.55);
}

.solution-row__image {
  width: 200px;
  opacity: 0.82;
}

.solution-row__arrow {
  justify-self: end;
  color: var(--steel);
  transition: transform 240ms var(--ease), color 180ms ease;
}

.solution-row:hover .solution-row__arrow {
  transform: translate(2px, -2px);
  color: var(--accent);
}

@media (max-width: 980px) {
  .solutions__head {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .solutions__head > :first-child,
  .solutions__head-side {
    grid-column: 1 / -1;
  }

  .solution-row {
    grid-template-columns: 2.25rem 1fr 2rem;
    gap: 0.6rem 1rem;
  }

  .solution-row__tagline {
    grid-column: 2 / 3;
  }

  .solution-row__image {
    display: none;
  }

  .solution-row__arrow {
    grid-column: 3;
    grid-row: 1;
  }
}
</style>
