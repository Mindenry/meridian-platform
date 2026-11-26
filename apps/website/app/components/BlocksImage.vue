<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const widthClass = computed(() => {
  const sizes = {
    standard: 'container',
    wide: 'container',
    full: 'container-wide',
  }

  return sizes[(p.value.width as keyof typeof sizes) || 'standard'] || 'container'
})
</script>

<template>
  <section class="section image-block" :class="`image-block--${p.theme || 'light'}`">
    <div :class="widthClass">
      <figure class="image-figure" data-reveal>
        <AppImage :image="p.image" :ratio="p.ratio || '16:9'" sizes="(max-width: 900px) 100vw, 86vw" />
        <figcaption v-if="p.caption" class="image-caption">
          <span class="mono">FIG.</span>
          {{ p.caption }}
        </figcaption>
      </figure>
    </div>
  </section>
</template>

<style scoped>
.image-block {
  background: var(--ivory);
}

.image-block--muted { background: #e7e1d5; }
.image-block--dark { background: var(--ink); color: var(--ivory); }

.image-figure {
  display: grid;
  grid-template-columns: 1fr 18rem;
  gap: 1.5rem;
  align-items: end;
}

.image-figure :deep(.media) {
  grid-column: 1;
}

.image-caption {
  grid-column: 2;
  font-size: 0.75rem;
  line-height: 1.55;
  color: var(--steel-dark);
  border-top: 1px solid var(--line);
  padding-top: 0.75rem;
}

.image-caption .mono {
  display: block;
  color: var(--steel);
  margin-bottom: 0.35rem;
  font-size: 0.75rem;
}

@media (max-width: 760px) {
  .image-figure {
    grid-template-columns: 1fr;
  }

  .image-caption,
  .image-figure :deep(.media) {
    grid-column: 1;
  }
}
</style>
