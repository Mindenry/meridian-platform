<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
</script>

<template>
  <section class="section statement" :class="`statement--${p.theme || 'light'}`">
    <div class="container">
      <div class="statement__grid">
        <div class="statement__lead" data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="statement__heading">{{ p.heading }}</h2>
        </div>
        <div class="statement__support" data-reveal>
          <p v-if="p.body" class="statement__body">{{ p.body }}</p>
          <p v-if="p.aside" class="statement__aside">{{ p.aside }}</p>
          <NuxtLink v-if="p.link_label" :to="p.link_url" class="text-link">
            {{ p.link_label }}
            <AppIcon name="arrow-right" :size="13" />
          </NuxtLink>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.statement {
  background: var(--ivory);
}

.statement--muted {
  background: #e7e1d5;
}

.statement--white {
  background: var(--paper);
}

.statement--dark {
  background: var(--ink);
  color: var(--ivory);
}

.statement__grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(1rem, 2vw, 2rem);
  align-items: start;
}

.statement__lead {
  grid-column: 1 / span 7;
}

.statement__heading {
  margin-top: 1.75rem;
  font-size: clamp(2.2rem, 4.6vw, 4.9rem);
  line-height: 1.06;
  letter-spacing: -0.018em;
  font-weight: 500;
  max-width: 18ch;
}

.statement__support {
  grid-column: 9 / span 4;
  padding-top: 3rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.statement__body {
  font-size: clamp(1rem, 1.1vw, 1.1rem);
  line-height: 1.7;
  color: var(--ink-2);
}

.statement--dark .statement__body {
  color: rgba(241, 237, 228, 0.72);
}

.statement__aside {
  font-size: 0.9rem;
  line-height: 1.65;
  color: var(--steel-dark);
  padding-top: 1.25rem;
  border-top: 1px solid var(--line);
}

.statement--dark .statement__aside {
  color: rgba(241, 237, 228, 0.52);
  border-color: var(--line-light);
}

.statement--dark .eyebrow {
  color: rgba(241, 237, 228, 0.5);
}

@media (max-width: 900px) {
  .statement__lead,
  .statement__support {
    grid-column: 1 / -1;
  }

  .statement__support {
    padding-top: 0;
    margin-top: 2rem;
    max-width: 60ch;
  }

  .statement__heading {
    max-width: 16ch;
  }
}
</style>
