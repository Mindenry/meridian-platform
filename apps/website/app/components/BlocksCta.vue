<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const primary = computed(() => p.value.primary_cta)
const secondary = computed(() => p.value.secondary_cta)
</script>

<template>
  <section class="cta" :class="`cta--${p.theme || 'dark'}`">
    <div v-if="p.image?.url" class="cta__media">
      <AppImage :image="p.image" ratio="21:9" sizes="100vw" />
    </div>
    <div class="cta__scrim" aria-hidden="true" />
    <div class="container cta__inner">
      <div class="cta__content" data-reveal>
        <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
        <h2 class="cta__heading">{{ p.heading }}</h2>
        <p v-if="p.body" class="cta__body">{{ p.body }}</p>
        <div class="cta__actions">
          <ButtonLink v-if="primary?.label" :label="primary.label" :to="primary.url" variant="light" />
          <ButtonLink v-if="secondary?.label" :label="secondary.label" :to="secondary.url" variant="outline-light" />
        </div>
      </div>
      <div class="cta__mark" aria-hidden="true">
        <span class="mono">MERIDIAN</span>
        <span class="cta__mark-line" />
        <span class="mono">ENGINEERING PROGRESS</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.cta {
  position: relative;
  background: var(--ink);
  color: var(--ivory);
  overflow: hidden;
  padding-block: clamp(5rem, 10vw, 9rem);
}

.cta__media {
  position: absolute;
  inset: 0;
  opacity: 0.38;
}

.cta__media :deep(.media) {
  height: 100%;
  aspect-ratio: auto !important;
}

.cta__media :deep(img) {
  filter: grayscale(1) contrast(1.15) brightness(0.55);
}

.cta__scrim {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(14, 20, 17, 0.92) 0%, rgba(14, 20, 17, 0.72) 52%, rgba(14, 20, 17, 0.35) 100%),
    linear-gradient(to right, rgba(241, 237, 228, 0.06) 1px, transparent 1px);
  background-size: auto, calc(100% / 12) 100%;
}

.cta__inner {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: space-between;
  gap: 3rem;
  align-items: flex-end;
}

.cta__content {
  max-width: 760px;
}

.cta__heading {
  margin-top: 1.5rem;
  font-size: clamp(2.3rem, 5.2vw, 5.2rem);
  line-height: 1.04;
  letter-spacing: -0.02em;
  font-weight: 500;
  max-width: 16ch;
}

.cta__body {
  margin-top: 1.5rem;
  max-width: 52ch;
  font-size: clamp(0.95rem, 1.1vw, 1.1rem);
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.64);
}

.cta__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 2rem;
}

.cta__mark {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.8rem;
  color: rgba(241, 237, 228, 0.35);
  font-size: 0.75rem;
  writing-mode: vertical-rl;
  text-orientation: mixed;
  flex: 0 0 auto;
}

.cta__mark-line {
  width: 1px;
  height: 72px;
  background: var(--accent);
}

@media (max-width: 800px) {
  .cta__mark {
    display: none;
  }

  .cta__actions :deep(.btn) {
    width: 100%;
  }
}
</style>
