<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
</script>

<template>
  <section class="section quote" :class="`quote--${p.theme || 'dark'}`">
    <div class="container">
      <figure class="quote__figure" data-reveal>
        <AppIcon name="quote" :size="32" class="quote__mark" />
        <blockquote class="quote__text">{{ p.quote }}</blockquote>
        <figcaption v-if="p.attribution" class="quote__attribution">
          <span class="quote__name">{{ p.attribution }}</span>
          <span v-if="p.role" class="quote__role mono">{{ p.role }}</span>
        </figcaption>
      </figure>
    </div>
  </section>
</template>

<style scoped>
.quote {
  background: var(--ink);
  color: var(--ivory);
}

.quote--light { background: var(--ivory); color: var(--ink); }
.quote--muted { background: #e7e1d5; color: var(--ink); }

.quote__figure {
  max-width: 1000px;
  margin-inline: auto;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.quote__mark {
  color: var(--accent);
}

.quote__text {
  font-size: clamp(1.7rem, 3.8vw, 3.6rem);
  line-height: 1.12;
  letter-spacing: -0.04em;
  font-weight: 480;
  text-wrap: balance;
}

.quote__attribution {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.quote__name {
  font-size: 0.95rem;
  font-weight: 600;
}

.quote__role {
  color: var(--steel);
  font-size: 0.75rem;
}
</style>
