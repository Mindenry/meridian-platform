<script setup lang="ts">
withDefaults(defineProps<{
  eyebrow?: string
  title: string
  body?: string
  meta?: string[]
  theme?: 'dark' | 'light'
  align?: 'left' | 'wide'
}>(), { theme: 'dark', align: 'left' })
</script>

<template>
  <header class="page-header" :class="[`page-header--${theme}`, `page-header--${align}`]">
    <div class="page-header__grid" aria-hidden="true" />
    <div class="container page-header__inner">
      <div class="page-header__main">
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <h1 class="page-header__title">{{ title }}</h1>
      </div>
      <div v-if="body || meta" class="page-header__side">
        <p v-if="body" class="page-header__body">{{ body }}</p>
        <div v-if="meta?.length" class="page-header__meta">
          <span v-for="item in meta" :key="item" class="mono">{{ item }}</span>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.page-header {
  position: relative;
  background: var(--ink);
  color: var(--ivory);
  padding-block: clamp(4rem, 9vw, 8rem) clamp(3rem, 6vw, 5rem);
  overflow: hidden;
}

.page-header--light {
  background: var(--ivory);
  color: var(--ink);
  border-bottom: 1px solid var(--line);
}

.page-header__grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(to right, rgba(241, 237, 228, 0.055) 1px, transparent 1px);
  background-size: calc(100% / 12) 100%;
  pointer-events: none;
}

.page-header--light .page-header__grid {
  background-image: linear-gradient(to right, rgba(14, 20, 17, 0.06) 1px, transparent 1px);
}

.page-header__inner {
  position: relative;
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: clamp(1.5rem, 4vw, 4rem);
  align-items: end;
}

.page-header__main {
  grid-column: 1 / span 8;
}

.page-header__title {
  margin-top: 1.5rem;
  font-size: clamp(3rem, 7vw, 7.2rem);
  line-height: 1.02;
  letter-spacing: -0.022em;
  font-weight: 500;
  max-width: 14ch;
}

.page-header--wide .page-header__main {
  grid-column: 1 / span 10;
}

.page-header--wide .page-header__title {
  max-width: 18ch;
}

.page-header__side {
  grid-column: 9 / span 4;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding-bottom: 0.5rem;
}

.page-header--wide .page-header__side {
  grid-column: 1 / span 6;
}

.page-header__body {
  font-size: clamp(0.95rem, 1.1vw, 1.1rem);
  line-height: 1.65;
  color: rgba(241, 237, 228, 0.62);
  max-width: 42ch;
}

.page-header--light .page-header__body {
  color: var(--steel-dark);
}

.page-header__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1.25rem;
  color: rgba(241, 237, 228, 0.4);
  font-size: 0.75rem;
  border-top: 1px solid var(--line-light);
  padding-top: 1rem;
}

.page-header--light .page-header__meta {
  color: var(--steel);
  border-color: var(--line);
}

@media (max-width: 900px) {
  .page-header__main,
  .page-header__side {
    grid-column: 1 / -1;
  }

  .page-header__title {
    max-width: 12ch;
    font-size: clamp(2.7rem, 11vw, 4.6rem);
  }

  .page-header__side {
    margin-top: 0.5rem;
  }
}
</style>
