<script setup lang="ts">
const { localeLinks, localeCode, switchLocale } = useLocale()
</script>

<template>
  <div class="locale-switcher" role="group" aria-label="Language">
    <template v-for="(locale, index) in localeLinks" :key="locale.code">
      <span v-if="index > 0" class="locale-switcher__sep" aria-hidden="true">/</span>
      <button
        type="button"
        class="locale-switcher__item"
        :class="{ 'is-active': locale.code === localeCode }"
        :aria-current="locale.code === localeCode ? 'true' : undefined"
        @click="switchLocale(locale.code)"
      >
        {{ locale.short }}
      </button>
    </template>
  </div>
</template>

<style scoped>
.locale-switcher {
  display: inline-flex;
  align-items: center;
  gap: 0.1rem;
  font-family: var(--font-mono);
  font-size: 0.75rem;
  letter-spacing: 0.04em;
}

.locale-switcher__sep {
  color: var(--line-strong);
  padding-inline: 0.15rem;
  pointer-events: none;
}

.locale-switcher__item {
  position: relative;
  border: 0;
  background: transparent;
  min-height: 44px;
  min-width: 32px;
  padding: 0.5rem 0.45rem;
  color: var(--steel-dark);
  transition: color 160ms ease, transform 120ms var(--ease);
}

.locale-switcher__item::after {
  content: "";
  position: absolute;
  left: 0.42rem;
  right: 0.42rem;
  bottom: 0.55rem;
  height: 1px;
  background: var(--accent);
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform 240ms var(--ease);
}

.locale-switcher__item:hover,
.locale-switcher__item.is-active {
  color: var(--ink);
}

.locale-switcher__item:hover::after,
.locale-switcher__item.is-active::after {
  transform: scaleX(1);
}

.locale-switcher__item:active {
  transform: translateY(1px);
}

@media (max-width: 420px) {
  .locale-switcher__item {
    min-width: 26px;
    padding-inline: 0.3rem;
  }

  .locale-switcher__sep {
    padding-inline: 0.05rem;
  }
}
</style>
