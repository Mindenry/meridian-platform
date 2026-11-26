<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
</script>

<template>
  <section class="section rich-text" :class="`rich-text--${p.theme || 'light'}`">
    <div class="container">
      <div class="rich-text__inner" :class="`rich-text__inner--${p.width || 'standard'}`" data-reveal>
        <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
        <h2 v-if="p.heading" class="h2 rich-text__heading">{{ p.heading }}</h2>
        <div v-if="p.body" class="prose rich-text__body" v-html="p.body" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.rich-text {
  background: var(--ivory);
}

.rich-text--muted {
  background: #e7e1d5;
}

.rich-text--dark {
  background: var(--ink);
  color: var(--ivory);
}

.rich-text__inner--narrow {
  max-width: 760px;
  margin-inline: auto;
}

.rich-text__inner--standard {
  max-width: 920px;
}

.rich-text__inner--wide {
  max-width: 1120px;
}

.rich-text__heading {
  margin-top: 1.4rem;
  margin-bottom: 2rem;
  max-width: 18ch;
}

.rich-text__body {
  max-width: 72ch;
}

.rich-text--dark .rich-text__body {
  color: rgba(241, 237, 228, 0.72);
}

.rich-text--dark .rich-text__body :deep(h2),
.rich-text--dark .rich-text__body :deep(h3) {
  color: var(--ivory);
}
</style>
