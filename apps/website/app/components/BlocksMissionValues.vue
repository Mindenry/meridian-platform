<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])
</script>

<template>
  <section class="section values" :class="`values--${p.theme || 'light'}`">
    <div class="container">
      <div class="values__grid">
        <div class="values__lead" data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 values__heading">{{ p.heading }}</h2>
          <p v-if="p.body" class="values__body">{{ p.body }}</p>
        </div>
        <ol class="values__list">
          <li v-for="(item, index) in items" :key="item.title" class="value-item" data-reveal>
            <span class="value-item__number mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
            <div>
              <h3 class="value-item__title">{{ item.title }}</h3>
              <p class="value-item__description">{{ item.description }}</p>
            </div>
          </li>
        </ol>
      </div>
    </div>
  </section>
</template>

<style scoped>
.values {
  background: var(--ivory);
}

.values--muted {
  background: #e7e1d5;
}

.values--dark {
  background: var(--ink);
  color: var(--ivory);
}

.values__grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(2rem, 4vw, 5rem);
}

.values__lead {
  grid-column: 1 / span 5;
  position: sticky;
  top: calc(var(--header-h) + 2rem);
  align-self: start;
}

.values__heading {
  margin-top: 1.4rem;
  max-width: 14ch;
}

.values__body {
  margin-top: 1.5rem;
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--steel-dark);
  max-width: 38ch;
}

.values--dark .values__body {
  color: rgba(241, 237, 228, 0.6);
}

.values__list {
  grid-column: 7 / span 6;
  list-style: none;
  border-top: 1px solid var(--line);
}

.values--dark .values__list {
  border-color: var(--line-light);
}

.value-item {
  display: grid;
  grid-template-columns: 3rem 1fr;
  gap: 1rem;
  padding-block: 1.6rem;
  border-bottom: 1px solid var(--line);
}

.values--dark .value-item {
  border-color: var(--line-light);
}

.value-item__number {
  color: var(--steel);
  font-size: 0.75rem;
  padding-top: 0.2rem;
}

.value-item__title {
  font-size: clamp(1.1rem, 1.5vw, 1.35rem);
  letter-spacing: -0.02em;
  font-weight: 560;
}

.value-item__description {
  margin-top: 0.4rem;
  font-size: 0.9rem;
  line-height: 1.6;
  color: var(--steel-dark);
  max-width: 52ch;
}

.values--dark .value-item__description {
  color: rgba(241, 237, 228, 0.55);
}

@media (max-width: 900px) {
  .values__lead,
  .values__list {
    grid-column: 1 / -1;
  }

  .values__lead {
    position: static;
  }

  .values__list {
    margin-top: 2rem;
  }
}
</style>
