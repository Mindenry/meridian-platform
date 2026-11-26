<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
const items = computed(() => props.block.resolved?.items ?? [])
</script>

<template>
  <section class="section team" :class="`team--${p.theme || 'dark'}`">
    <div class="container">
      <div class="team__head">
        <div data-reveal>
          <p v-if="p.eyebrow" class="eyebrow">{{ p.eyebrow }}</p>
          <h2 class="h2 team__heading">{{ p.heading }}</h2>
        </div>
        <p v-if="p.intro" class="team__intro" data-reveal>{{ p.intro }}</p>
      </div>

      <div class="team__grid">
        <article v-for="member in items" :key="member.id" class="member" data-reveal>
          <div class="member__portrait">
            <img v-if="member.photo?.url" :src="member.photo.url" :alt="member.photo.alt" loading="lazy" decoding="async">
            <span v-else class="member__initials">{{ member.initials }}</span>
            <span class="member__corner" aria-hidden="true" />
          </div>
          <div class="member__body">
            <h3 class="member__name">{{ member.name }}</h3>
            <p class="member__role">{{ member.role }}</p>
            <p v-if="member.bio" class="member__bio">{{ member.bio }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<style scoped>
.team {
  background: var(--ink);
  color: var(--ivory);
}

.team--light {
  background: var(--ivory);
  color: var(--ink);
}

.team__head {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 2rem;
  align-items: end;
  margin-bottom: clamp(2.5rem, 5vw, 4rem);
}

.team__head > :first-child {
  grid-column: 1 / span 7;
}

.team__heading {
  margin-top: 1.4rem;
  max-width: 14ch;
}

.team__intro {
  grid-column: 9 / span 4;
  font-size: 0.9rem;
  line-height: 1.6;
  color: rgba(241, 237, 228, 0.55);
}

.team--light .team__intro {
  color: var(--steel-dark);
}

.team__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(1.5rem, 2.5vw, 2.5rem);
}

.member {
  border-top: 1px solid var(--line-light);
  padding-top: 1rem;
}

.team--light .member {
  border-color: var(--line);
}

.member__portrait {
  aspect-ratio: 4 / 3;
  background: #17283a;
  position: relative;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.team--light .member__portrait {
  background: #e5e1d9;
}

.member__portrait img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: grayscale(1) contrast(1.05);
}

.member__initials {
  font-size: clamp(3rem, 5vw, 5.4rem);
  letter-spacing: -0.06em;
  font-weight: 500;
  color: rgba(241, 237, 228, 0.55);
}

.team--light .member__initials {
  color: rgba(14, 20, 17, 0.4);
}

.member__corner {
  position: absolute;
  right: 0.7rem;
  bottom: 0.7rem;
  width: 7px;
  height: 7px;
  background: var(--accent);
}

.member__body {
  padding-top: 1rem;
}

.member__name {
  font-size: clamp(1.05rem, 1.35vw, 1.25rem);
  font-weight: 560;
  letter-spacing: -0.02em;
}

.member__role {
  margin-top: 0.2rem;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--accent);
}

.member__bio {
  margin-top: 0.75rem;
  font-size: 0.8rem;
  line-height: 1.55;
  color: rgba(241, 237, 228, 0.5);
}

.team--light .member__bio {
  color: var(--steel-dark);
}

@media (max-width: 900px) {
  .team__head > :first-child,
  .team__intro {
    grid-column: 1 / -1;
  }

  .team__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 560px) {
  .team__grid {
    grid-template-columns: 1fr;
  }
}
</style>
