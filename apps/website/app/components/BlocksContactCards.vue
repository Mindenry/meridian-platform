<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const { localeCode } = useLocale()
const p = computed(() => props.block.props)
const items = computed(() => p.value.items ?? [])

const localized = (value: any): string => {
  if (!value) return ''
  if (typeof value === 'string') return value
  return value[localeCode.value] || value.en || ''
}
</script>

<template>
  <section class="section offices" :class="`offices--${p.theme || 'muted'}`">
    <div class="container">
      <div class="offices__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 offices__heading">{{ p.heading }}</h2>
        </div>
      </div>

      <div class="offices__grid">
        <article v-for="(office, index) in items" :key="office.city" class="office" data-reveal>
          <span class="office__index mono">{{ String(Number(index) + 1).padStart(2, '0') }}</span>
          <p class="office__label mono">{{ localized(office.label) }}</p>
          <h3 class="office__city">{{ office.city }}</h3>
          <p class="office__address">{{ office.address }}</p>
          <div class="office__contact">
            <a v-if="office.email" :href="`mailto:${office.email}`" class="office__link">{{ office.email }}</a>
            <a v-if="office.phone" :href="`tel:${office.phone.replace(/\s/g, '')}`" class="office__link">{{ office.phone }}</a>
          </div>
        </article>
      </div>

      <div class="offices__map" aria-label="Illustrative office location map">
        <div class="offices__map-grid" />
        <div class="offices__map-label mono">BANGKOK / KHLONG TOEI</div>
        <span class="offices__pin offices__pin--one" />
        <span class="offices__pin offices__pin--two" />
        <span class="offices__pin offices__pin--three" />
        <span class="offices__map-note">Illustrative geography. Not a live map.</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.offices {
  background: #e7e1d5;
}

.offices--light {
  background: var(--ivory);
}

.offices--dark {
  background: var(--ink);
  color: var(--ivory);
}

.offices__head {
  margin-bottom: clamp(2rem, 4vw, 3rem);
}

.offices__heading {
  margin-top: 1.4rem;
  max-width: 16ch;
}

.offices__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border-top: 1px solid var(--line);
  border-left: 1px solid var(--line);
}

.office {
  padding: 1.5rem;
  border-right: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  display: flex;
  flex-direction: column;
  min-height: 260px;
}

.office__index {
  color: var(--steel);
  font-size: 0.75rem;
}

.office__label {
  margin-top: 1.4rem;
  color: var(--steel);
  font-size: 0.75rem;
}

.office__city {
  margin-top: 0.6rem;
  font-size: clamp(1.7rem, 2.7vw, 2.4rem);
  letter-spacing: -0.04em;
  font-weight: 500;
}

.office__address {
  margin-top: 0.6rem;
  font-size: 0.85rem;
  line-height: 1.55;
  color: var(--steel-dark);
  max-width: 32ch;
}

.office__contact {
  margin-top: auto;
  padding-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.office__link {
  font-size: 0.82rem;
  border-bottom: 1px solid var(--line);
  align-self: flex-start;
  transition: color 160ms ease, border-color 160ms ease;
}

.office__link:hover {
  color: var(--accent);
  border-color: var(--accent);
}

.offices__map {
  margin-top: 2rem;
  height: 260px;
  background: var(--ink);
  position: relative;
  overflow: hidden;
}

.offices__map-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(to right, rgba(241, 237, 228, 0.09) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(241, 237, 228, 0.09) 1px, transparent 1px);
  background-size: 64px 64px;
  transform: perspective(600px) rotateX(42deg) scale(1.5);
  transform-origin: bottom center;
}

.offices__map-label {
  position: absolute;
  top: 1.25rem;
  left: 1.25rem;
  color: rgba(241, 237, 228, 0.78);
  font-size: 0.75rem;
}

.offices__pin {
  position: absolute;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--accent);
  box-shadow: 0 0 0 8px rgba(227, 109, 63, 0.12);
}

.offices__pin--one { left: 28%; top: 55%; }
.offices__pin--two { left: 55%; top: 42%; }
.offices__pin--three { left: 74%; top: 62%; }

.offices__map-note {
  position: absolute;
  right: 1.25rem;
  bottom: 1.25rem;
  color: rgba(241, 237, 228, 0.35);
  font-size: 0.75rem;
}

@media (max-width: 900px) {
  .offices__grid {
    grid-template-columns: 1fr;
  }

  .office {
    min-height: 220px;
  }
}
</style>
