<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])
</script>

<template>
  <section class="section timeline" :class="`timeline--${p.theme || 'light'}`">
    <div class="container">
      <div class="timeline__head">
        <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
        <h2 class="h2 timeline__heading">{{ p.heading }}</h2>
      </div>

      <div class="timeline__list">
        <div v-for="item in items" :key="item.year" class="timeline-item" data-reveal>
          <span class="timeline-item__year">{{ item.year }}</span>
          <div class="timeline-item__rule">
            <span />
          </div>
          <div class="timeline-item__body">
            <h3 class="timeline-item__title">{{ item.title }}</h3>
            <p class="timeline-item__description">{{ item.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.timeline {
  background: var(--ivory);
}

.timeline--muted {
  background: #e7e1d5;
}

.timeline--dark {
  background: var(--ink);
  color: var(--ivory);
}

.timeline__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 2rem;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.timeline__heading {
  max-width: 16ch;
}

.timeline__list {
  border-top: 1px solid var(--line);
}

.timeline--dark .timeline__list {
  border-color: var(--line-light);
}

.timeline-item {
  display: grid;
  grid-template-columns: 5rem 1fr 2fr;
  gap: clamp(1rem, 3vw, 3rem);
  padding-block: 1.6rem;
  border-bottom: 1px solid var(--line);
  align-items: start;
}

.timeline--dark .timeline-item {
  border-color: var(--line-light);
}

.timeline-item__year {
  font-family: var(--font-mono);
  font-size: 0.8rem;
  color: var(--accent);
  letter-spacing: 0.04em;
}

.timeline-item__rule {
  display: flex;
  align-items: center;
  height: 1rem;
}

.timeline-item__rule span {
  display: block;
  width: 100%;
  height: 1px;
  background: var(--line);
  position: relative;
}

.timeline--dark .timeline-item__rule span {
  background: var(--line-light);
}

.timeline-item__rule span::before {
  content: "";
  position: absolute;
  left: 0;
  top: -2px;
  width: 5px;
  height: 5px;
  background: var(--accent);
}

.timeline-item__title {
  font-size: clamp(1.05rem, 1.4vw, 1.3rem);
  font-weight: 560;
  letter-spacing: -0.02em;
}

.timeline-item__description {
  margin-top: 0.35rem;
  font-size: 0.88rem;
  line-height: 1.6;
  color: var(--steel-dark);
  max-width: 56ch;
}

.timeline--dark .timeline-item__description {
  color: rgba(241, 237, 228, 0.55);
}

@media (max-width: 760px) {
  .timeline__head {
    flex-direction: column;
    align-items: flex-start;
  }

  .timeline-item {
    grid-template-columns: 3.5rem 1fr;
  }

  .timeline-item__rule {
    display: none;
  }
}
</style>
