<script setup lang="ts">
import type { Block } from '~/types'

const props = defineProps<{ block: Block }>()
const root = ref<HTMLElement | null>(null)
const reduced = usePreferredReducedMotion()

const p = computed(() => props.block.props)
const lines = computed(() => String(p.value.headline || '').split('\n').filter(Boolean))
const meta = computed(() => Array.isArray(p.value.meta) ? p.value.meta : [])
const primary = computed(() => p.value.primary_cta)
const secondary = computed(() => p.value.secondary_cta)
const align = computed(() => p.value.align === 'center' ? 'center' : 'left')
const layout = computed(() => p.value.layout || 'panel')

onMounted(async () => {
  if (reduced.value === 'reduce' || !root.value) return
  const { gsap } = await import('gsap')
  const q = gsap.utils.selector(root.value)
  const timeline = gsap.timeline({ defaults: { ease: 'power3.out' } })

  timeline
    .from(q('[data-hero-eyebrow]'), { autoAlpha: 0, y: 14, duration: 0.7 })
    .from(q('[data-hero-line]'), { yPercent: 112, duration: 1.05, stagger: 0.09 }, '-=0.35')
    .from(q('[data-hero-copy]'), { autoAlpha: 0, y: 18, duration: 0.8 }, '-=0.55')
    .from(q('[data-hero-cta]'), { autoAlpha: 0, y: 12, duration: 0.6, stagger: 0.08 }, '-=0.45')
    .from(q('[data-hero-meta]'), { autoAlpha: 0, duration: 0.7 }, '-=0.3')

  if (p.value.image?.url) {
    timeline.from(q('[data-hero-media]'), { clipPath: 'inset(0 0 100% 0)', duration: 1.15 }, 0.1)
  }
})
</script>

<template>
  <section ref="root" class="hero" :class="[`hero--${layout}`, `hero--${align}`]" data-hero>
    <div class="hero__grid" aria-hidden="true" />
    <div class="container hero__inner">
      <div class="hero__content">
        <p v-if="p.eyebrow" class="eyebrow hero__eyebrow" data-hero-eyebrow>{{ p.eyebrow }}</p>

        <h1 class="hero__title">
          <span v-for="(line, index) in lines" :key="index" class="hero__line-mask">
            <span class="hero__line" data-hero-line>{{ line }}</span>
          </span>
        </h1>

        <p v-if="p.accent_line" class="hero__accent" data-hero-copy>{{ p.accent_line }}</p>
        <p v-if="p.body" class="hero__body" data-hero-copy>{{ p.body }}</p>

        <div class="hero__actions">
          <ButtonLink
            v-if="primary?.label"
            :label="primary.label"
            :to="primary.url"
            variant="light"
            data-hero-cta
          />
          <ButtonLink
            v-if="secondary?.label"
            :label="secondary.label"
            :to="secondary.url"
            variant="outline-light"
            data-hero-cta
          />
        </div>
      </div>

      <div v-if="p.image?.url" class="hero__media-wrap" data-hero-media>
        <span class="hero__media-frame" aria-hidden="true" />
        <AppImage :image="p.image" ratio="4:3" sizes="(max-width: 900px) 100vw, 44vw" priority class="hero__media" />
      </div>
    </div>

    <div class="container hero__meta" data-hero-meta>
      <div class="hero__meta-items">
        <div v-for="item in meta" :key="item.label" class="hero__meta-item">
          <span class="mono">{{ item.label }}</span>
          <span class="hero__meta-value">{{ item.value }}</span>
        </div>
      </div>
      <div v-if="p.show_scroll && p.scroll_label" class="hero__scroll" aria-hidden="true">
        <span class="mono">{{ p.scroll_label }}</span>
        <span class="hero__scroll-track"><span class="hero__scroll-dot" /></span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  min-height: calc(100svh - var(--header-h));
  background: var(--ink);
  color: var(--ivory);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  isolation: isolate;
}

.hero__grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(241, 237, 228, 0.055) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(241, 237, 228, 0.04) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%, 100% 88px;
  pointer-events: none;
}

.hero__inner {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(1rem, 2vw, 2rem);
  flex: 1;
  align-items: center;
  padding-top: clamp(2rem, 4vh, 3rem);
  padding-bottom: 1rem;
}

.hero__content {
  grid-column: 1 / span 7;
  padding-block: 0.75rem;
}

.hero__eyebrow {
  color: rgba(241, 237, 228, 0.58);
}

.hero__title {
  margin-top: clamp(0.8rem, 1.6vh, 1.1rem);
  font-family: var(--font-display);
  font-size: clamp(3rem, 6vw, 6.6rem);
  line-height: 1.04;
  letter-spacing: -0.02em;
  font-weight: 500;
  font-optical-sizing: auto;
  max-width: 14ch;
  text-wrap: balance;
}

.hero__line-mask {
  display: block;
  overflow: hidden;
  padding-bottom: 0.1em;
}

.hero__line {
  display: block;
  transform-origin: bottom left;
}

.hero__title > .hero__line-mask:nth-child(2) .hero__line {
  color: var(--steel);
}

.hero__accent {
  margin-top: clamp(0.85rem, 1.8vh, 1.2rem);
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--accent);
}

.hero__body {
  margin-top: 0.75rem;
  max-width: 48ch;
  font-size: clamp(0.95rem, 1.05vw, 1.05rem);
  line-height: 1.55;
  color: rgba(241, 237, 228, 0.7);
}

.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: clamp(1.1rem, 2.2vh, 1.5rem);
}

.hero__media-wrap {
  grid-column: 8 / span 5;
  align-self: stretch;
  position: relative;
  min-height: 480px;
  margin-block: 1.2rem 0;
}

.hero__media-frame {
  position: absolute;
  inset: -0.8rem 0.8rem 0.8rem -0.8rem;
  border: 1px solid rgba(240, 82, 31, 0.58);
  pointer-events: none;
  z-index: 0;
}

.hero__media {
  position: relative;
  z-index: 1;
  height: 100%;
  aspect-ratio: auto !important;
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.hero__media :deep(img) {
  filter: grayscale(1) contrast(1.12) brightness(0.78);
  transition: transform 900ms var(--ease), filter 600ms var(--ease);
}

.hero__media:hover :deep(img) {
  transform: scale(1.025);
  filter: grayscale(0.4) contrast(1.08) brightness(0.82);
}

.hero__meta {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 2rem;
  border-top: 1px solid var(--line-light);
  padding-block: 0.9rem 1.1rem;
}

.hero__scroll {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: rgba(241, 237, 228, 0.55);
  font-size: 0.7rem;
  letter-spacing: 0.08em;
  white-space: nowrap;
}

.hero__scroll-track {
  position: relative;
  display: block;
  width: 1px;
  height: 44px;
  overflow: hidden;
  background: rgba(241, 237, 228, 0.2);
}

.hero__scroll-dot {
  position: absolute;
  top: 0;
  left: 0;
  width: 1px;
  height: 16px;
  background: var(--accent);
  animation: hero-scroll-cue 2.4s cubic-bezier(0.22, 1, 0.36, 1) infinite;
}

@keyframes hero-scroll-cue {
  0% {
    transform: translateY(-16px);
    opacity: 0;
  }

  30% {
    opacity: 1;
  }

  100% {
    transform: translateY(44px);
    opacity: 0;
  }
}

.hero__meta-items {
  display: flex;
  flex-wrap: wrap;
  gap: clamp(1.5rem, 4vw, 4rem);
}

.hero__meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  color: rgba(241, 237, 228, 0.5);
  font-size: 0.75rem;
}

.hero__meta-value {
  color: var(--ivory);
  font-family: var(--font-sans);
  font-size: 0.9rem;
  letter-spacing: -0.01em;
}

.hero--center .hero__content {
  grid-column: 1 / -1;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hero--center .hero__title {
  max-width: 15ch;
}

.hero--center .hero__body {
  margin-inline: auto;
}

@media (min-width: 1200px) {
  /* Let the hero image run to the viewport edge while the copy stays on the
     container line. The frame closes on the left and top only. */
  .hero__media-wrap {
    margin-right: calc(var(--gutter) * -1);
  }

  .hero__media {
    border-radius: var(--radius-lg) 0 0 var(--radius-lg);
  }

  .hero__media-frame {
    right: 0;
  }
}

@media (max-width: 760px) {
  .hero__scroll {
    display: none;
  }
}

@media (max-width: 1100px) {
  .hero__content {
    grid-column: 1 / span 7;
  }

  .hero__media-wrap {
    grid-column: 8 / span 5;
  }
}

@media (max-width: 900px) {
  .hero {
    min-height: 0;
  }

  .hero__inner {
    grid-template-columns: 1fr;
    padding-top: 3rem;
  }

  .hero__content,
  .hero__media-wrap {
    grid-column: 1 / -1;
  }

  .hero__media-wrap {
    min-height: 0;
    margin-block: 1.8rem 0;
  }

  .hero__media-frame {
    inset: -0.55rem 0.55rem 0.55rem -0.55rem;
  }

  .hero__media {
    height: auto;
    aspect-ratio: 4 / 3 !important;
  }

  .hero__title {
    font-size: clamp(2.9rem, 11vw, 5.4rem);
    max-width: 12ch;
  }

  .hero__meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.25rem;
  }
}

@media (max-width: 560px) {
  .hero__actions :deep(.btn) {
    width: 100%;
  }
}
</style>
