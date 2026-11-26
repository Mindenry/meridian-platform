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
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border-top: 1px solid var(--line);
  border-left: 1px solid var(--line);
}

.technology--dark .technology__grid {
  border-color: var(--line-light);
}

.tech-item {
  display: grid;
  grid-template-columns: 2rem 1fr auto;
  gap: 1rem;
  align-items: baseline;
  padding: 1.35rem 1rem;
  border-right: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  transition: background 180ms ease;
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
  font-size: clamp(1rem, 1.4vw, 1.25rem);
  letter-spacing: -0.02em;
  font-weight: 550;
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

  .technology__grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 520px) {
  .technology__grid {
    grid-template-columns: 1fr;
  }
}
</style>
