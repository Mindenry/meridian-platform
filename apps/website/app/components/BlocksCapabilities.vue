<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])
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

      <div class="capabilities__list">
        <NuxtLink
          v-for="(item, index) in items"
          :key="item.number || index"
          :to="item.url || '#'"
          class="capability"
          data-reveal
        >
          <span class="capability__number mono">{{ item.number || String(Number(index) + 1).padStart(2, '0') }}</span>
          <span class="capability__title">{{ item.title }}</span>
          <span class="capability__description">{{ item.description }}</span>
          <span v-if="item.image" class="capability__image">
            <AppImage :image="item.image" ratio="3:2" sizes="180px" />
          </span>
          <span class="capability__arrow"><AppIcon name="arrow-up-right" :size="18" /></span>
        </NuxtLink>
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

.capabilities__list {
  border-top: 1px solid var(--line);
}

.capabilities--dark .capabilities__list {
  border-color: var(--line-light);
}

.capability {
  display: grid;
  grid-template-columns: 3rem 1.1fr 1fr 140px 2rem;
  gap: clamp(1rem, 2vw, 2.5rem);
  align-items: center;
  padding-block: clamp(1.1rem, 2vw, 1.6rem);
  border-bottom: 1px solid var(--line);
  position: relative;
  transition: background 240ms ease, color 240ms ease, padding 260ms var(--ease);
}

.capability::before {
  content: "";
  position: absolute;
  inset: 0 auto 0 0;
  width: 3px;
  background: var(--accent);
  transform: scaleY(0);
  transform-origin: top;
  transition: transform 280ms var(--ease);
}

.capabilities--dark .capability {
  border-color: var(--line-light);
}

.capability:hover {
  padding-left: 1.1rem;
  padding-right: 1rem;
  background: var(--ink);
  color: var(--ivory);
}

.capability:hover::before {
  transform: scaleY(1);
}

.capabilities--dark .capability:hover {
  background: var(--ivory);
  color: var(--ink);
}

.capability__number {
  color: var(--steel);
  font-size: 0.75rem;
}

.capability:hover .capability__number {
  color: var(--accent);
}

.capability__title {
  font-size: clamp(1.3rem, 2vw, 1.95rem);
  letter-spacing: -0.012em;
  font-weight: 500;
  line-height: 1.15;
}

.capability__description {
  font-size: 0.88rem;
  line-height: 1.55;
  color: var(--steel-dark);
  max-width: 40ch;
  transition: color 220ms ease;
}

.capability:hover .capability__description {
  color: rgba(241, 237, 228, 0.72);
}

.capabilities--dark .capability:hover .capability__description {
  color: var(--steel-dark);
}

.capabilities--dark .capability__description {
  color: rgba(241, 237, 228, 0.55);
}

.capability__image {
  display: block;
  width: 140px;
  opacity: 0.8;
  transition: opacity 220ms ease, transform 300ms var(--ease);
}

.capability:hover .capability__image {
  opacity: 1;
  transform: scale(1.03);
}

.capability__arrow {
  justify-self: end;
  color: var(--steel);
  transition: color 180ms ease, transform 240ms var(--ease);
}

.capability:hover .capability__arrow {
  color: var(--accent);
  transform: translate(2px, -2px);
}

@media (max-width: 980px) {
  .capabilities__head {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }

  .capability {
    grid-template-columns: 2.5rem 1fr 2rem;
    gap: 0.75rem 1rem;
  }

  .capability__description {
    grid-column: 2 / 3;
  }

  .capability__image {
    display: none;
  }

  .capability__arrow {
    grid-column: 3;
    grid-row: 1;
  }

  .capabilities__head > :first-child,
  .capabilities__intro {
    grid-column: 1 / -1;
  }

  .capabilities__intro {
    margin-top: 1.25rem;
  }
}
</style>
