<script setup lang="ts">
const nuxt = useNuxtApp()
const reduced = usePreferredReducedMotion()

const active = ref(false)
const value = ref(0)
let hideTimer: ReturnType<typeof setTimeout> | null = null
let startedAt = 0

function start() {
  if (!import.meta.client) return
  if (hideTimer) clearTimeout(hideTimer)
  startedAt = performance.now()
  active.value = true
  value.value = 0.08
  // Let the first frame paint before the trickle starts, so the bar grows
  // from the left instead of appearing at its final width.
  requestAnimationFrame(() => {
    value.value = reduced.value === 'reduce' ? 0.9 : 0.72
  })
}

function finish() {
  if (!import.meta.client || !active.value) return
  const elapsed = performance.now() - startedAt
  const wait = Math.max(0, 360 - elapsed)
  hideTimer = setTimeout(() => {
    value.value = 1
    hideTimer = setTimeout(() => {
      active.value = false
      value.value = 0
    }, 220)
  }, wait)
}

nuxt.hook('page:loading:start', start)
nuxt.hook('page:loading:end', finish)
</script>

<template>
  <div class="route-progress" :class="{ 'is-active': active }" aria-hidden="true">
    <span :style="{ transform: `scaleX(${value})` }" />
  </div>
</template>

<style scoped>
.route-progress {
  position: fixed;
  inset: 0 0 auto;
  height: 2px;
  z-index: 200;
  pointer-events: none;
  opacity: 0;
  transition: opacity 180ms ease 40ms;
}

.route-progress.is-active {
  opacity: 1;
  transition: opacity 80ms ease;
}

.route-progress span {
  display: block;
  height: 100%;
  width: 100%;
  background: var(--accent);
  transform-origin: left center;
  transition: transform 480ms cubic-bezier(0.22, 1, 0.36, 1);
  box-shadow: 0 0 14px rgba(240, 82, 31, 0.45);
}

@media (prefers-reduced-motion: reduce) {
  .route-progress span {
    transition-duration: 120ms;
  }
}
</style>
