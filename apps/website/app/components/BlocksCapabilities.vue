<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])
const active = ref(0)
const current = computed(() => items.value[active.value] ?? items.value[0])
</script>

<template>
  <section class="section capabilities" :class="`capabilities--${p.theme || 'light'}`">
    <div class="container">
      <div class="capabilities__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 capabilities__heading">{{ p.heading }}</h2>
        </div>
        <p v-if="p.intro" class="capabilities__intro" data-reveal>{{ p.intro }}</p>
      </div>

      <div class="capabilities__body">
        <div class="capabilities__stage" aria-hidden="true">
          <div
            v-for="(item, index) in items"
            :key="`frame-${item.number || index}`"
            class="capabilities__frame"
            :class="{ 'is-active': index === active }"
          >
            <AppImage v-if="item.image" :image="item.image" ratio="4:3" sizes="(max-width: 980px) 100vw, 38vw" />
          </div>
          <div v-if="current" class="capabilities__stage-meta mono">
            <span>{{ current.number || String(active + 1).padStart(2, '0') }}</span>
            <span>{{ current.title }}</span>
          </div>
        </div>

        <ul class="capabilities__list">
          <li v-for="(item, index) in items" :key="item.number || index">
            <NuxtLink
              :to="item.url || '#'"
              class="capability"
              :class="{ 'is-active': index === active }"
              data-reveal
              @mouseenter="active = Number(index)"
              @focus="active = Number(index)"
            >
              <span class="capability__number mono">{{ item.number || String(Number(index) + 1).padStart(2, '0') }}</span>
              <span class="capability__text">
                <span class="capability__title">{{ item.title }}</span>
                <span v-if="item.description" class="capability__description">{{ item.description }}</span>
              </span>
              <span class="capability__arrow"><AppIcon name="arrow-up-right" :size="18" /></span>
            </NuxtLink>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<style scoped>
.capabilities {
  background: var(--ivory);
}

.capabilities--muted {
  background: #e7e1d5;
}

.capabilities--dark {
  background: var(--ink);
  color: var(--ivory);
}

.capabilities__head {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(1rem, 2vw, 2rem);
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
  align-items: end;
}

.capabilities__head > :first-child {
  grid-column: 1 / span 7;
}

.capabilities__heading {
  margin-top: 1.4rem;
  max-width: 16ch;
}

.capabilities__intro {
  grid-column: 9 / span 4;
  max-width: 38ch;
  font-size: 0.95rem;
  line-height: 1.65;
  color: var(--steel-dark);
}

.capabilities--dark .capabilities__intro {
  color: rgba(241, 237, 228, 0.6);
}

.capabilities__body {
  display: grid;
  grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
  gap: clamp(2rem, 5vw, 5rem);
  align-items: start;
}

.capabilities__stage {
  position: relative;
  aspect-ratio: 4 / 5;
  overflow: hidden;
  background: #d9d6cf;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
}

.capabilities__frame {
  position: absolute;
  inset: 0;
  opacity: 0;
  transform: scale(1.02);
  transition: opacity 520ms var(--ease), transform 900ms var(--ease);
}

.capabilities__frame.is-active {
  opacity: 1;
  transform: scale(1);
}

.capabilities__frame :deep(.media) {
  height: 100%;
  aspect-ratio: auto !important;
}

.capabilities__frame :deep(img) {
  filter: grayscale(1) contrast(1.08) brightness(0.9);
}

.capabilities__stage-meta {
  position: absolute;
  inset: auto 0 0;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
  color: var(--ivory);
  background: linear-gradient(0deg, rgba(14, 20, 17, 0.86), rgba(14, 20, 17, 0));
  font-size: 0.7rem;
  letter-spacing: 0.06em;
}

.capabilities__list {
  list-style: none;
  border-top: 1px solid var(--line);
}

.capabilities--dark .capabilities__list {
  border-color: var(--line-light);
}

.capability {
  display: grid;
  grid-template-columns: 2.6rem minmax(0, 1fr) 2rem;
  gap: 1rem;
  align-items: start;
  padding-block: clamp(1.2rem, 2.2vw, 1.9rem);
  padding-inline: 0.75rem;
  margin-inline: -0.75rem;
  border-bottom: 1px solid var(--line);
  border-radius: var(--radius-sm);
  position: relative;
  transition: background 220ms ease;
}

.capability:hover,
.capability.is-active {
  background: rgba(255, 255, 255, 0.42);
}

.capabilities--dark .capability {
  border-color: var(--line-light);
}

.capability::before {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 2px;
  border-radius: 2px;
  background: var(--accent);
  transform: scaleY(0);
  transform-origin: top;
  transition: transform 300ms var(--ease);
}

.capability.is-active::before {
  transform: scaleY(1);
}

.capability__number {
  color: var(--steel);
  font-size: 0.75rem;
  padding-top: 0.45rem;
  transition: color 200ms ease;
}

.capability.is-active .capability__number {
  color: var(--accent-text);
}

.capability__text {
  display: block;
  min-width: 0;
}

.capability__title {
  display: block;
  font-family: var(--font-display);
  font-optical-sizing: auto;
  font-size: clamp(1.5rem, 2.3vw, 2.2rem);
  line-height: 1.1;
  letter-spacing: -0.015em;
  font-weight: 500;
  transition: color 200ms ease, transform 300ms var(--ease);
}

.capability.is-active .capability__title {
  color: var(--accent-text);
}

.capability__description {
  display: block;
  margin-top: 0.55rem;
  max-width: 44ch;
  font-size: 0.88rem;
  line-height: 1.6;
  color: var(--steel-dark);
}

.capabilities--dark .capability__description {
  color: rgba(241, 237, 228, 0.55);
}

.capability__arrow {
  justify-self: end;
  color: var(--steel);
  opacity: 0;
  transform: translate(-4px, 4px);
  transition: opacity 200ms ease, transform 260ms var(--ease), color 200ms ease;
}

.capability:hover .capability__arrow,
.capability.is-active .capability__arrow {
  opacity: 1;
  transform: translate(0, 0);
  color: var(--accent-text);
}

@media (max-width: 980px) {
  .capabilities__head {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .capabilities__head > :first-child,
  .capabilities__intro {
    grid-column: 1 / -1;
  }

  .capabilities__intro {
    margin-top: 1.25rem;
  }

  .capabilities__body {
    grid-template-columns: 1fr;
  }

  .capabilities__stage {
    display: none;
  }

  .capability {
    grid-template-columns: 2.4rem minmax(0, 1fr) 1.6rem;
  }

  /* The image stage is hidden on touch widths, so the highlight would mark a
     row as selected for no reason. */
  .capability::before {
    display: none;
  }

  .capability.is-active .capability__title {
    color: inherit;
  }

  .capability.is-active .capability__number {
    color: var(--steel);
  }

  .capability__arrow {
    opacity: 0.4;
    transform: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .capabilities__frame {
    transition-duration: 0.01ms;
  }
}
</style>
