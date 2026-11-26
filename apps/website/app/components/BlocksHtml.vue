<script setup lang="ts">
import type { Block } from '~/types'
const props = defineProps<{ block: Block }>()
const p = computed(() => props.block.props)
</script>

<template>
  <section v-if="p.html" class="section html-embed" :class="`html-embed--${p.theme || 'light'}`">
    <div class="container" :class="`html-embed--${p.align || 'left'}`">
      <div class="prose" v-html="p.html" />
    </div>
  </section>
</template>

<style scoped>
.html-embed {
  background: var(--ivory);
}

.html-embed--muted { background: #e7e1d5; }
.html-embed--dark { background: var(--ink); color: var(--ivory); }

.html-embed--center {
  text-align: center;
}

.html-embed--center :deep(.prose) {
  margin-inline: auto;
}
</style>
