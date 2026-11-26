<script setup lang="ts">
import type { Breadcrumb } from '~/types'
defineProps<{ items: Breadcrumb[]; theme?: 'light' | 'dark' }>()
</script>

<template>
  <nav class="breadcrumbs" :class="`breadcrumbs--${theme || 'light'}`" aria-label="Breadcrumb">
    <div class="container">
      <ol class="breadcrumbs__list">
        <li v-for="(item, index) in items" :key="`${item.label}-${index}`">
          <NuxtLink v-if="item.url && index < items.length - 1" :to="item.url">{{ item.label }}</NuxtLink>
          <span v-else aria-current="page">{{ item.label }}</span>
          <span v-if="index < items.length - 1" class="breadcrumbs__sep" aria-hidden="true">/</span>
        </li>
      </ol>
    </div>
  </nav>
</template>

<style scoped>
.breadcrumbs {
  border-bottom: 1px solid var(--line);
  background: var(--ivory);
}

.breadcrumbs--dark {
  background: var(--ink);
  color: var(--ivory);
  border-color: var(--line-light);
}

.breadcrumbs__list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  list-style: none;
  padding-block: 0.75rem;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--steel);
}

.breadcrumbs__list li {
  display: inline-flex;
  gap: 0.5rem;
  align-items: center;
}

a:hover {
  color: var(--accent-text);
}

.breadcrumbs__sep {
  color: var(--line-strong);
}

.breadcrumbs--dark .breadcrumbs__sep {
  color: var(--line-light-strong);
}
</style>
