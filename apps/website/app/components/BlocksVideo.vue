<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)

const embed = computed(() => {
  const url = String(p.value.url || '')
  if (!url) return null
  if (/\.(mp4|webm|ogg)(\?|$)/i.test(url)) return { type: 'video', src: url }
  const youtube = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?/]+)/)
  if (youtube) return { type: 'iframe', src: `https://www.youtube-nocookie.com/embed/${youtube[1]}` }
  const vimeo = url.match(/vimeo\.com\/(\d+)/)
  if (vimeo) return { type: 'iframe', src: `https://player.vimeo.com/video/${vimeo[1]}` }
  return { type: 'iframe', src: url }
})
</script>

<template>
  <section class="section video-block" :class="`video-block--${p.theme || 'dark'}`">
    <div class="container">
      <div v-if="p.title" class="video-block__head" data-reveal>
        <p class="eyebrow">Film</p>
        <h2 class="h2">{{ p.title }}</h2>
      </div>
      <div v-if="embed" class="video-frame" data-reveal>
        <iframe
          v-if="embed.type === 'iframe'"
          :src="embed.src"
          :title="p.title || 'Meridian video'"
          loading="lazy"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen
        />
        <video v-else controls preload="metadata" :poster="p.poster?.url || undefined">
          <source :src="embed.src">
        </video>
      </div>
      <p v-if="p.caption" class="video-caption">{{ p.caption }}</p>
    </div>
  </section>
</template>

<style scoped>
.video-block {
  background: var(--ink);
  color: var(--ivory);
}

.video-block--light { background: var(--ivory); color: var(--ink); }

.video-block__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 2rem;
  margin-bottom: 2rem;
}

.video-frame {
  position: relative;
  aspect-ratio: 16 / 9;
  background: #0a121b;
}

.video-frame iframe,
.video-frame video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
  object-fit: cover;
}

.video-caption {
  margin-top: 0.75rem;
  font-size: 0.75rem;
  color: var(--steel);
}
</style>
