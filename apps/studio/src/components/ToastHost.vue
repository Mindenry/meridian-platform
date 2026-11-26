<script setup lang="ts">
import { X, CheckCircle2, AlertTriangle, Info } from 'lucide-vue-next'
import { toast } from '@/stores/toast'
</script>

<template>
  <div class="toast-host" role="status" aria-live="polite">
    <TransitionGroup name="toast">
      <div v-for="message in toast.toasts" :key="message.id" class="toast" :class="`toast--${message.tone}`">
        <CheckCircle2 v-if="message.tone === 'success'" :size="16" style="margin-top:1px" />
        <AlertTriangle v-else-if="message.tone === 'error'" :size="16" style="margin-top:1px" />
        <Info v-else :size="16" style="margin-top:1px" />
        <div style="min-width:0">
          <p class="toast__title">{{ message.title }}</p>
          <p v-if="message.message" class="toast__message">{{ message.message }}</p>
        </div>
        <button type="button" class="toast__close" aria-label="Dismiss" @click="toast.remove(message.id)">
          <X :size="14" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 180ms ease, transform 260ms var(--s-ease);
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
