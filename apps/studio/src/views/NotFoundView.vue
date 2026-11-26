<script setup lang="ts">
import { useRouter } from 'vue-router'
const router = useRouter()
</script>

<template>
  <div class="not-found">
    <p class="mono muted">404 / STUDIO</p>
    <h1>This screen does not exist.</h1>
    <p>The link may be outdated, or your role may not include access to this area.</p>
    <button type="button" class="btn-s btn-s--primary" @click="router.push('/')">Back to overview</button>
  </div>
</template>

<style scoped>
.not-found { min-height: 50vh; display: flex; flex-direction: column; justify-content: center; max-width: 620px; }
h1 { margin-top: 8px; font-size: 38px; line-height: 1.02; letter-spacing: -.045em; font-weight: 600; }
p { margin-top: 8px; color: var(--s-muted); font-size: 12px; }
.btn-s { margin-top: 20px; align-self: flex-start; }
</style>
