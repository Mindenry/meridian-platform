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
  gap: 0;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
}

.statistics--dark .statistics__grid {
  border-color: var(--line-light);
}

.stat {
  padding: clamp(1.5rem, 3vw, 2.5rem) clamp(0.75rem, 1.5vw, 1.5rem) clamp(1.5rem, 3vw, 2.5rem) 0;
  border-right: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  position: relative;
  transition: background 220ms ease, padding-left 260ms var(--ease);
}

.stat::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 2px;
  background: var(--accent);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 280ms var(--ease);
}

.stat:hover {
  background: rgba(240, 82, 31, 0.04);
  padding-left: 1rem;
}

.stat:hover::before {
  transform: scaleX(1);
}

.statistics--dark .stat {
  border-color: var(--line-light);
}

.statistics--dark .stat:hover {
  background: rgba(241, 237, 228, 0.04);
}

.stat:last-child {
  border-right: 0;
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

  .stat {
    border-bottom: 1px solid var(--line);
  }

  .stat:nth-child(2) {
    border-right: 0;
  }

  .stat:nth-last-child(-n + 2) {
    border-bottom: 0;
  }
}

@media (max-width: 520px) {
  .statistics__grid {
    grid-template-columns: 1fr;
  }

  .stat {
    border-right: 0;
    border-bottom: 1px solid var(--line);
  }

  .stat:nth-last-child(-n + 2) {
    border-bottom: 1px solid var(--line);
  }

  .stat:last-child {
    border-bottom: 0;
  }
}
</style>
