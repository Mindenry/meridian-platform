<script setup lang="ts">
import type { MediaImage } from '~/types'

const props = withDefaults(defineProps<{
  image?: MediaImage | null
  ratio?: '16:9' | '4:3' | '3:2' | '1:1' | '21:9'
  sizes?: string
  priority?: boolean
  class?: string
}>(), {
  ratio: '4:3',
  sizes: '(max-width: 760px) 100vw, 50vw',
  priority: false,
})

const variantList = computed(() => {
  if (!props.image?.variants) return []
  if (typeof props.image.variants === 'string') {
    try {
      return JSON.parse(props.image.variants)
    } catch {
      return []
    }
  }
  return props.image.variants
})

const srcset = computed(() => variantList.value
  .filter((variant: any) => variant?.url)
  .map((variant: any) => `${variant.url} ${variant.width}w`)
  .join(', '))

const fallbackAlt = computed(() => props.image?.alt || '')
</script>

<template>
  <div v-if="image?.url" class="media" :class="[`media-ratio-${ratio}`, $props.class]">
    <img
      :src="image.url"
      :srcset="srcset || undefined"
      :sizes="srcset ? sizes : undefined"
      :alt="fallbackAlt"
      :width="image.width || undefined"
      :height="image.height || undefined"
      :loading="priority ? 'eager' : 'lazy'"
      :fetchpriority="priority ? 'high' : 'auto'"
      decoding="async"
      :style="{
        objectPosition: `${(image.focal_x ?? 0.5) * 100}% ${(image.focal_y ?? 0.5) * 100}%`,
      }"
    >
  </div>
  <div v-else class="media media--placeholder" :class="`media-ratio-${ratio}`" aria-hidden="true">
    <div class="media__grid" />
  </div>
</template>

<style scoped>
.media--placeholder {
  background: #e3e0d9;
}

.media__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(14, 20, 17, 0.08) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(14, 20, 17, 0.08) 1px, transparent 1px);
  background-size: 32px 32px;
}
</style>
