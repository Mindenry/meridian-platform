<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])
</script>

<template>
  <section class="section technology" :class="`technology--${p.theme || 'muted'}`">
    <div class="container">
      <div class="technology__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 technology__heading">{{ p.heading }}</h2>
        </div>
        <p v-if="p.note" class="technology__note" data-reveal>{{ p.note }}</p>
      </div>

      <div class="technology__grid">
        <div v-for="(item, index) in items" :key="item.name" class="tech-item" data-reveal>
          <span class="tech-item__index mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
          <span class="tech-item__name">{{ item.name }}</span>
          <span class="tech-item__category mono">{{ item.category }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.technology {
  background: #e7e1d5;
}

.technology--light {
  background: var(--ivory);
}

.technology--dark {
  background: var(--ink);
  color: var(--ivory);
}

.technology__head {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 2rem;
  align-items: end;
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.technology__head > :first-child {
  grid-column: 1 / span 7;
}

.technology__heading {
  margin-top: 1.4rem;
  max-width: 16ch;
}

.technology__note {
  grid-column: 9 / span 4;
  font-size: 0.75rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

.technology--dark .technology__note {
  color: rgba(241, 237, 228, 0.5);
}

.technology__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  border-top: 1px solid var(--line);
}

.technology--dark .technology__grid {
  border-color: var(--line-light);
}

.tech-item {
  display: grid;
  grid-template-columns: 2.2rem minmax(0, 1fr) auto;
  gap: 1rem;
  align-items: baseline;
  padding: clamp(1.5rem, 2.4vw, 2.1rem) clamp(0.6rem, 1.4vw, 1.2rem);
  border-bottom: 1px solid var(--line);
  transition: background 180ms ease;
}

.tech-item:nth-child(odd) {
  border-right: 1px solid var(--line);
}

.technology--dark .tech-item {
  border-color: var(--line-light);
}

.tech-item:hover {
  background: rgba(14, 20, 17, 0.035);
}

.technology--dark .tech-item:hover {
  background: rgba(241, 237, 228, 0.035);
}

.tech-item__index {
  color: var(--steel);
  font-size: 0.75rem;
}

.tech-item__name {
  font-family: var(--font-display);
  font-optical-sizing: auto;
  font-size: clamp(1.35rem, 2vw, 1.9rem);
  font-weight: 500;
  letter-spacing: -0.012em;
  line-height: 1.15;
  transition: color 200ms ease, transform 260ms var(--ease);
}

.tech-item:hover .tech-item__name {
  color: var(--accent-text);
  transform: translateX(4px);
}

.tech-item__category {
  color: var(--steel);
  font-size: 0.75rem;
}

@media (max-width: 900px) {
  .technology__head {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .technology__head > :first-child,
  .technology__note {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .technology__grid {
    grid-template-columns: 1fr;
  }

  .tech-item:nth-child(odd) {
    border-right: 0;
  }
}
</style>
