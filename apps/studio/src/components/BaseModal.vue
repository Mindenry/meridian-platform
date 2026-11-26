<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { X } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  open: boolean
  title: string
  wide?: boolean
  closeOnBackdrop?: boolean
}>(), { closeOnBackdrop: true })

const emit = defineEmits<{ close: [] }>()
const panel = ref<HTMLElement | null>(null)

function onKeydown(event: KeyboardEvent) {
  if (!props.open) return
  if (event.key === 'Escape') emit('close')
  if (event.key === 'Tab' && panel.value) {
    const focusable = panel.value.querySelectorAll<HTMLElement>('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
    if (!focusable.length) return
    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault()
      last.focus()
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault()
      first.focus()
    }
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-backdrop" @click.self="closeOnBackdrop && emit('close')">
        <div ref="panel" class="modal" :class="{ 'modal--wide': wide }" role="dialog" aria-modal="true" :aria-label="title">
          <div class="modal__head">
            <h2 class="modal__title">{{ title }}</h2>
            <button type="button" class="btn-s btn-s--ghost btn-s--icon" aria-label="Close" @click="emit('close')">
              <X :size="16" />
            </button>
          </div>
          <div class="modal__body">
            <slot />
          </div>
          <div v-if="$slots.footer" class="modal__foot">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 180ms ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
