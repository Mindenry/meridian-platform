<script setup lang="ts">
const props = withDefaults(defineProps<{ variant?: 'dark' | 'light'; compact?: boolean }>(), {
  variant: 'dark',
  compact: false,
})

const { localePath } = useLocale()
</script>

<template>
  <NuxtLink :to="localePath('')" class="logo" :class="[`logo--${variant}`, { 'logo--compact': compact }]">
    <svg class="logo__mark" viewBox="0 0 32 32" aria-hidden="true">
      <path d="M16 2.5v27" stroke="currentColor" stroke-width="1.2" />
      <path d="M4.5 16h23" stroke="currentColor" stroke-width="1.2" opacity="0.45" />
      <path d="M8 24V8l8 10 8-10v16" fill="none" stroke="currentColor" stroke-width="1.6" />
      <circle cx="24.5" cy="7.5" r="2.4" fill="var(--accent)" />
    </svg>
    <span class="logo__type">
      <span class="logo__word">MERIDIAN</span>
      <span class="logo__descriptor">INDUSTRIAL GROUP</span>
    </span>
  </NuxtLink>
</template>

<style scoped>
.logo {
  display: inline-flex;
  align-items: center;
  gap: 0.7rem;
  color: var(--ink);
  line-height: 1;
  flex: 0 0 auto;
}

.logo--light {
  color: var(--ivory);
}

.logo--compact .logo__descriptor {
  display: none;
}

.logo__mark {
  width: 2rem;
  height: 2rem;
  flex: 0 0 auto;
}

.logo__type {
  display: flex;
  flex-direction: column;
  gap: 0.28rem;
}

.logo__word {
  font-size: 0.98rem;
  font-weight: 680;
  letter-spacing: 0.2em;
}

.logo__descriptor {
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.28em;
  opacity: 0.62;
  white-space: nowrap;
}

@media (max-width: 760px) {
  .logo {
    gap: 0.75rem;
  }

  .logo__mark {
    width: 1.65rem;
    height: 1.65rem;
  }

  .logo__word {
    font-size: 0.82rem;
    letter-spacing: 0.16em;
  }

  .logo__descriptor {
    display: none;
  }
}

@media (max-width: 420px) {
  .logo__mark {
    width: 1.5rem;
    height: 1.5rem;
  }

  .logo__word {
    font-size: 0.78rem;
    letter-spacing: 0.14em;
  }
}
</style>
