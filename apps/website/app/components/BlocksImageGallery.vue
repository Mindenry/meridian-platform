<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => (p.value.items ?? []).filter((item: any) => item.image?.url))
</script>

<template>
  <section class="section gallery" :class="`gallery--${p.theme || 'light'}`">
    <div class="container">
      <div v-if="p.heading" class="gallery__head" data-reveal>
        <h2 class="h2">{{ p.heading }}</h2>
      </div>
      <div class="gallery__grid" :class="`gallery__grid--${p.columns || '3'}`">
        <figure v-for="(item, index) in items" :key="index" class="gallery__item" data-reveal>
          <AppImage :image="item.image" ratio="4:3" sizes="(max-width: 760px) 100vw, 33vw" />
          <figcaption v-if="item.caption" class="gallery__caption">
            <span class="mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
            {{ item.caption }}
          </figcaption>
        </figure>
      </div>
    </div>
  </section>
</template>

<style scoped>
.gallery {
  background: var(--ivory);
}

.gallery--muted { background: #e7e1d5; }
.gallery--dark { background: var(--ink); color: var(--ivory); }

.gallery__head {
  margin-bottom: 2rem;
}

.gallery__grid {
  display: grid;
  gap: 1.25rem;
}

.gallery__grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.gallery__grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.gallery__grid--4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

.gallery__item {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.gallery__caption {
  font-size: 0.75rem;
  line-height: 1.5;
  color: var(--steel-dark);
  display: flex;
  gap: 0.7rem;
}

.gallery--dark .gallery__caption { color: rgba(241, 237, 228, 0.55); }

.gallery__caption .mono {
  color: var(--steel);
  font-size: 0.75rem;
  flex: 0 0 auto;
}

@media (max-width: 900px) {
  .gallery__grid--3,
  .gallery__grid--4 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .gallery__grid--2,
  .gallery__grid--3,
  .gallery__grid--4 {
    grid-template-columns: 1fr;
  }
}
</style>
