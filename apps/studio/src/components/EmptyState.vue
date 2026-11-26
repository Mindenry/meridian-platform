<script setup lang="ts">
import { Inbox } from 'lucide-vue-next'
defineProps<{ title: string; body?: string; actionLabel?: string }>()
const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div class="empty-s">
    <div class="empty-s__icon"><Inbox :size="18" /></div>
    <p class="empty-s__title">{{ title }}</p>
    <p v-if="body" class="empty-s__body">{{ body }}</p>
    <button v-if="actionLabel" type="button" class="btn-s btn-s--primary btn-s--sm" style="margin-top: 12px" @click="emit('action')">
      {{ actionLabel }}
    </button>
  </div>
</template>
