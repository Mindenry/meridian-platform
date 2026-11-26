<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const reverse = computed(() => Boolean(p.value.reverse))
const primary = computed(() => p.value.primary_cta)
</script>

<template>
  <section class="split-hero" :class="[`split-hero--${p.theme || 'dark'}`, { 'split-hero--reverse': reverse }]">
    <div class="container split-hero__inner">
      <div class="split-hero__content" data-reveal>
        <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
        <h1 class="split-hero__title">{{ p.headline }}</h1>
        <p v-if="p.body" class="split-hero__body">{{ p.body }}</p>
        <ButtonLink
          v-if="primary?.label"
          :label="primary.label"
          :to="primary.url"
          :variant="p.theme === 'dark' ? 'light' : 'primary'"
        />
      </div>
      <div v-if="p.image" class="split-hero__media" data-reveal>
        <AppImage :image="p.image" ratio="4:3" sizes="(max-width: 900px) 100vw, 44vw" priority />
      </div>
    </div>
  </section>
</template>

<style scoped>
.split-hero {
  background: var(--ink);
  color: var(--ivory);
  padding-block: clamp(3.5rem, 8vw, 7rem) clamp(4rem, 8vw, 7rem);
  position: relative;
  overflow: hidden;
}

.split-hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: linear-gradient(to right, rgba(241, 237, 228, 0.05) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%;
  pointer-events: none;
}

.split-hero--light {
  background: var(--ivory);
  color: var(--ink);
}

.split-hero--muted {
  background: #e7e1d5;
  color: var(--ink);
}

.split-hero__inner {
  position: relative;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(2rem, 4vw, 5rem);
  align-items: end;
}

.split-hero__content {
  grid-column: 1 / span 7;
  padding-bottom: 0.5rem;
}

.split-hero__title {
  margin-top: 1.5rem;
  font-size: clamp(2.8rem, 5.4vw, 5.6rem);
  line-height: 1.04;
  letter-spacing: -0.02em;
  font-weight: 500;
  max-width: 15ch;
}

.split-hero__body {
  margin-top: 1.5rem;
  max-width: 54ch;
  font-size: clamp(1rem, 1.15vw, 1.15rem);
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.7);
}

.split-hero--light .split-hero__body,
.split-hero--muted .split-hero__body {
  color: var(--ink-2);
}

.split-hero__content :deep(.btn) {
  margin-top: 2rem;
}

.split-hero__media {
  grid-column: 9 / span 4;
  position: relative;
}

.split-hero__media::before {
  content: "";
  position: absolute;
  inset: -0.7rem 0.7rem 0.7rem -0.7rem;
  border: 1px solid rgba(240, 82, 31, 0.5);
  pointer-events: none;
}

.split-hero__media :deep(.media) {
  position: relative;
  z-index: 1;
}

.split-hero__media :deep(img) {
  filter: grayscale(1) contrast(1.08) brightness(0.82);
  transition: transform 800ms var(--ease), filter 500ms ease;
}

.split-hero__media:hover :deep(img) {
  transform: scale(1.02);
  filter: grayscale(0.4) contrast(1.05) brightness(0.86);
}

.split-hero--reverse .split-hero__content {
  grid-column: 6 / span 7;
  grid-row: 1;
}

.split-hero--reverse .split-hero__media {
  grid-column: 1 / span 4;
  grid-row: 1;
}

@media (max-width: 900px) {
  .split-hero__content,
  .split-hero__media,
  .split-hero--reverse .split-hero__content,
  .split-hero--reverse .split-hero__media {
    grid-column: 1 / -1;
    grid-row: auto;
  }

  .split-hero__media {
    margin-top: 2rem;
  }

  .split-hero__title {
    max-width: 13ch;
  }
}
</style>
