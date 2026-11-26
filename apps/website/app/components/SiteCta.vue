<script setup lang="ts">
withDefaults(defineProps<{
  eyebrow?: string
  heading: string
  body?: string
  primaryLabel: string
  primaryTo?: string
  secondaryLabel?: string
  secondaryTo?: string
}>(), { primaryTo: '/contact' })
</script>

<template>
  <section class="site-cta">
    <div class="site-cta__grid" aria-hidden="true" />
    <div class="container site-cta__inner">
      <div data-reveal>
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <h2 class="site-cta__heading">{{ heading }}</h2>
      </div>
      <div class="site-cta__side" data-reveal>
        <p v-if="body" class="site-cta__body">{{ body }}</p>
        <div class="site-cta__actions">
          <ButtonLink :label="primaryLabel" :to="primaryTo" variant="light" />
          <ButtonLink v-if="secondaryLabel" :label="secondaryLabel" :to="secondaryTo || '/contact'" variant="outline-light" />
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.site-cta {
  background: var(--ink);
  color: var(--ivory);
  padding-block: clamp(4rem, 8vw, 7rem);
  position: relative;
  overflow: hidden;
}

.site-cta__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(241, 237, 228, 0.06) 1px, transparent 1px),
    radial-gradient(circle at 82% 30%, rgba(227, 109, 63, 0.15), transparent 34%);
  background-size: calc(100% / 12) 100%, 100% 100%;
  pointer-events: none;
}

.site-cta__inner {
  position: relative;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 2.5rem;
  align-items: end;
}

.site-cta__inner > :first-child {
  grid-column: 1 / span 7;
}

.site-cta__heading {
  margin-top: 1.4rem;
  font-size: clamp(2.3rem, 4.8vw, 4.8rem);
  line-height: 0.98;
  letter-spacing: -0.05em;
  font-weight: 500;
  max-width: 16ch;
}

.site-cta__side {
  grid-column: 9 / span 4;
}

.site-cta__body {
  font-size: 0.95rem;
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.62);
}

.site-cta__actions {
  margin-top: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.7rem;
}

@media (max-width: 900px) {
  .site-cta__inner > :first-child,
  .site-cta__side {
    grid-column: 1 / -1;
  }

  .site-cta__actions :deep(.btn) {
    width: 100%;
  }
}
</style>
