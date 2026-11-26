<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { X } from 'lucide-vue-next'

const props = withDefaults(defineProps<{ open: boolean; title: string; width?: string }>(), { width: '520px' })
const emit = defineEmits<{ close: [] }>()

function onKeydown(event: KeyboardEvent) {
  if (props.open && event.key === 'Escape') emit('close')
}
onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition name="drawer">
      <div v-if="open">
        <div class="drawer-backdrop" @click="emit('close')" />
        <aside class="drawer" :style="{ width }" role="dialog" aria-modal="true" :aria-label="title">
          <div class="drawer__head">
            <h2 class="modal__title">{{ title }}</h2>
            <button type="button" class="btn-s btn-s--ghost btn-s--icon" aria-label="Close" @click="emit('close')">
              <X :size="16" />
            </button>
          </div>
          <div class="drawer__body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="drawer__foot">
            <slot name="footer" />
          </div>
        </aside>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 200ms ease;
}

.drawer-enter-active .drawer,
.drawer-leave-active .drawer {
  transition: transform 280ms var(--s-ease);
}

.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
}

.drawer-enter-from .drawer,
.drawer-leave-to .drawer {
  transform: translateX(30px);
}
</style>
