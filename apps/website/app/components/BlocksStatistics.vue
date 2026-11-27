<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])
</script>

<template>
  <section class="section statistics" :class="`statistics--${p.theme || 'light'}`">
    <div class="container">
      <div class="statistics__head">
        <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
        <h2 class="h2 statistics__heading">{{ p.heading }}</h2>
      </div>

      <div class="statistics__grid">
        <div v-for="(item, index) in items" :key="item.label" class="stat" data-reveal>
          <span class="stat__index mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
          <span class="stat__value">{{ item.value }}</span>
          <span class="stat__label">{{ item.label }}</span>
          <span v-if="item.note" class="stat__note mono">{{ item.note }}</span>
        </div>
      </div>

      <p v-if="p.disclaimer" class="statistics__disclaimer">{{ p.disclaimer }}</p>
    </div>
  </section>
</template>

<style scoped>
.statistics {
  background: var(--ivory);
}

.statistics--muted {
  background: #e7e1d5;
}

.statistics--dark {
  background: var(--ink);
  color: var(--ivory);
}

.statistics__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 2rem;
  margin-bottom: clamp(2.5rem, 5vw, 4.5rem);
}

.statistics__heading {
  max-width: 15ch;
}

.statistics__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: clamp(0.75rem, 1.5vw, 1.1rem);
}

.stat {
  padding: clamp(1.4rem, 2.4vw, 2rem);
  border: 1px solid var(--glass-border);
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.58);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  position: relative;
  overflow: hidden;
  transition: background 220ms ease, box-shadow 240ms var(--ease);
}

.stat::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 2px;
  background: var(--accent);
  border-radius: var(--radius-md) var(--radius-md) 0 0;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 280ms var(--ease);
}

.stat:hover {
  background: rgba(255, 255, 255, 0.86);
  box-shadow: var(--shadow-md);
}

.stat:hover::before {
  transform: scaleX(1);
}

.statistics--dark .stat {
  background: rgba(241, 237, 228, 0.05);
  border-color: rgba(241, 237, 228, 0.12);
  box-shadow: none;
}

.statistics--dark .stat:hover {
  background: rgba(241, 237, 228, 0.09);
  box-shadow: none;
}

.stat__index {
  color: var(--steel);
  font-size: 0.75rem;
}

.stat__value {
  font-size: clamp(2.8rem, 4.8vw, 4.9rem);
  line-height: 0.98;
  letter-spacing: -0.025em;
  font-weight: 500;
}

.stat__label {
  font-size: 0.86rem;
  line-height: 1.45;
  color: var(--steel-dark);
  max-width: 20ch;
}

.statistics--dark .stat__label {
  color: rgba(241, 237, 228, 0.55);
}

.stat__note {
  color: var(--steel);
  font-size: 0.75rem;
  margin-top: auto;
}

.statistics__disclaimer {
  margin-top: 1rem;
  font-size: 0.75rem;
  line-height: 1.55;
  color: var(--steel-dark);
  max-width: 72ch;
}

@media (max-width: 900px) {
  .statistics__head {
    flex-direction: column;
    align-items: flex-start;
  }

  .statistics__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 520px) {
  .statistics__grid {
    grid-template-columns: 1fr;
  }
}
</style>
