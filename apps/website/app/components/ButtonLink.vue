<script setup lang="ts">
const props = withDefaults(defineProps<{
  label: string
  to: string
  variant?: 'primary' | 'ghost' | 'light' | 'outline-light' | 'secondary' | 'text'
  icon?: boolean
  size?: 'sm' | 'md'
}>(), { variant: 'primary', icon: true, size: 'md' })

const isExternal = computed(() => /^(https?:|mailto:|tel:)/.test(props.to))
const target = computed(() => (props.to.startsWith('http') ? '_blank' : undefined))
const rel = computed(() => (props.to.startsWith('http') ? 'noopener noreferrer' : undefined))

const classes = computed(() => {
  if (props.variant === 'text') return 'text-link'
  const map: Record<string, string> = {
    primary: 'btn',
    secondary: 'btn btn-ghost',
    ghost: 'btn btn-ghost',
    light: 'btn btn-light',
    'outline-light': 'btn btn-outline-light',
  }
  return [map[props.variant] || 'btn', props.size === 'sm' ? 'btn-sm' : '']
})
</script>

<template>
  <NuxtLink v-if="!isExternal" :to="to" :class="classes">
    <span>{{ label }}</span>
    <AppIcon v-if="icon && variant !== 'text'" name="arrow-up-right" :size="14" class="btn-icon" />
  </NuxtLink>
  <a v-else :href="to" :class="classes" :target="target" :rel="rel">
    <span>{{ label }}</span>
    <AppIcon v-if="icon && variant !== 'text'" name="arrow-up-right" :size="14" class="btn-icon" />
  </a>
</template>
