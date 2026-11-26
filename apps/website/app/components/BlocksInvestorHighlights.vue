<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const years = computed(() => props.block.resolved?.years ?? [])
</script>

<template>
  <section class="section investor" :class="`investor--${p.theme || 'light'}`">
    <div class="container">
      <div class="investor__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 investor__heading">{{ p.heading }}</h2>
        </div>
        <p v-if="p.note" class="investor__note" data-reveal>{{ p.note }}</p>
      </div>

      <div class="investor__table">
        <div class="investor__labels mono">
          <span>Metric</span>
          <span v-for="year in years" :key="year.year">{{ year.year }}</span>
        </div>

        <div class="investor__rows">
          <template v-for="(year, yearIndex) in years" :key="year.year">
            <div v-for="(item, index) in year.items" :key="`${year.year}-${item.label}`" class="investor__cell" :class="{ 'investor__cell--first': yearIndex === 0 }">
              <span class="investor__metric">{{ item.label }}</span>
              <span class="investor__value">{{ item.value }}<small>{{ item.unit }}</small></span>
              <span class="investor__year mono">{{ year.year }}</span>
            </div>
          </template>
        </div>

        <div class="investor__grid">
          <div v-for="year in years" :key="year.year" class="investor-year" data-reveal>
            <p class="investor-year__year">{{ year.year }}</p>
            <div v-for="item in year.items" :key="item.label" class="investor-year__row">
              <span class="investor-year__label">{{ item.label }}</span>
              <span class="investor-year__value">{{ item.value }} <small>{{ item.unit }}</small></span>
            </div>
          </div>
        </div>
      </div>

      <div class="investor__disclosure">
        <span class="mono">ILLUSTRATIVE</span>
        <p>{{ p.note }}</p>
      </div>
    </div>
  </section>
</template>

<style scoped>
.investor {
  background: var(--ivory);
}

.investor--muted { background: #e7e1d5; }
.investor--dark { background: var(--ink); color: var(--ivory); }

.investor__head {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 2rem;
  align-items: end;
  margin-bottom: clamp(2rem, 4vw, 3.5rem);
}

.investor__head > :first-child { grid-column: 1 / span 7; }

.investor__heading {
  margin-top: 1.4rem;
  max-width: 17ch;
}

.investor__note {
  grid-column: 9 / span 4;
  font-size: 0.78rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

.investor--dark .investor__note { color: rgba(241, 237, 228, 0.55); }

.investor__table {
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.investor__labels {
  display: none;
}

.investor__rows {
  display: none;
}

.investor__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.5rem, 3vw, 3rem);
}

.investor-year {
  border-top: 1px solid var(--line);
  padding-top: 1rem;
}

.investor--dark .investor-year { border-color: var(--line-light); }

.investor-year__year {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  color: var(--accent);
  margin-bottom: 1rem;
}

.investor-year__row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding-block: 0.75rem;
  border-bottom: 1px solid var(--line);
  align-items: baseline;
}

.investor--dark .investor-year__row { border-color: var(--line-light); }

.investor-year__label {
  font-size: 0.82rem;
  color: var(--steel-dark);
}

.investor--dark .investor-year__label { color: rgba(241, 237, 228, 0.55); }

.investor-year__value {
  font-size: 1.35rem;
  letter-spacing: -0.03em;
  white-space: nowrap;
}

.investor-year__value small {
  font-size: 0.75rem;
  color: var(--steel);
  margin-left: 0.25rem;
}

.investor__disclosure {
  margin-top: 2rem;
  border: 1px solid var(--line);
  padding: 1rem 1.15rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.investor--dark .investor__disclosure { border-color: var(--line-light); }

.investor__disclosure .mono {
  color: var(--accent);
  flex: 0 0 auto;
  font-size: 0.75rem;
}

.investor__disclosure p {
  font-size: 0.7rem;
  line-height: 1.55;
  color: var(--steel-dark);
  max-width: 92ch;
}

.investor--dark .investor__disclosure p { color: rgba(241, 237, 228, 0.5); }

@media (max-width: 900px) {
  .investor__head > :first-child,
  .investor__note { grid-column: 1 / -1; }

  .investor__grid {
    grid-template-columns: 1fr;
  }
}
</style>
