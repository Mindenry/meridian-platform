<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Search, Upload, X, Check } from 'lucide-vue-next'
import BaseModal from '@/components/BaseModal.vue'
import { api } from '@/api/client'
import type { MediaAsset } from '@/types'

const props = defineProps<{ open: boolean; title?: string }>()
const emit = defineEmits<{ close: []; select: [asset: MediaAsset] }>()

const assets = ref<MediaAsset[]>([])
const loading = ref(false)
const query = ref('')
const selected = ref<MediaAsset | null>(null)
const uploading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/media', { params: { q: query.value || undefined, per_page: 48 } })
    assets.value = data.data
  } finally {
    loading.value = false
  }
}

watch(() => props.open, (open) => {
  if (open) {
    selected.value = null
    load()
  }
})

const search = (() => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(load, 240)
  }
})()

async function upload(event: Event) {
  const files = (event.target as HTMLInputElement).files
  if (!files?.length) return
  uploading.value = true
  try {
    const body = new FormData()
    Array.from(files).forEach((file) => body.append('files[]', file))
    await api.post('/media', body, { headers: { 'Content-Type': 'multipart/form-data' } })
    await load()
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

function choose() {
  if (selected.value) emit('select', selected.value)
}

onMounted(() => {
  if (props.open) load()
})
</script>

<template>
  <BaseModal :open="open" :title="title || 'Choose media'" wide @close="emit('close')">
    <div class="toolbar" style="margin-bottom: 10px">
      <div class="toolbar__search">
        <Search :size="13" />
        <input v-model="query" class="input-s" placeholder="Search media by filename or alt text" @input="search">
      </div>
      <div class="toolbar__spacer" />
      <input ref="fileInput" type="file" multiple accept="image/*,application/pdf" style="display:none" @change="upload">
      <button type="button" class="btn-s" :disabled="uploading" @click="fileInput?.click()">
        <Upload :size="13" /> {{ uploading ? 'Uploading…' : 'Upload' }}
      </button>
    </div>

    <div v-if="loading" class="media-picker-grid">
      <div v-for="i in 12" :key="i" class="skeleton" style="aspect-ratio:4/3" />
    </div>
    <div v-else class="media-picker-grid">
      <button
        v-for="asset in assets"
        :key="asset.id"
        type="button"
        class="media-tile"
        :class="{ 'is-selected': selected?.id === asset.id }"
        @click="selected = asset"
        @dblclick="selected = asset; choose()"
      >
        <img v-if="asset.mime_type.startsWith('image/')" :src="asset.url" :alt="asset.alt?.en || asset.original_name" loading="lazy">
        <span v-else class="media-tile__file">{{ asset.extension.toUpperCase() }}</span>
        <span class="media-tile__name">{{ asset.original_name }}</span>
        <span v-if="selected?.id === asset.id" class="media-tile__check"><Check :size="12" /></span>
      </button>
    </div>
    <p v-if="!loading && !assets.length" class="muted" style="text-align:center;padding:30px 0;font-size: 12px">No media found.</p>

    <template #footer>
      <button type="button" class="btn-s" @click="emit('close')">Cancel</button>
      <button type="button" class="btn-s btn-s--primary" :disabled="!selected" @click="choose">Use selected</button>
    </template>
  </BaseModal>
</template>

<style scoped>
.media-picker-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
}

.media-tile {
  border: 1px solid var(--s-line);
  background: var(--s-white);
  padding: 0;
  text-align: left;
  border-radius: 3px;
  overflow: hidden;
  position: relative;
}

.media-tile:hover,
.media-tile.is-selected {
  border-color: var(--s-ink);
}

.media-tile img {
  width: 100%;
  aspect-ratio: 4 / 3;
  object-fit: cover;
  display: block;
  background: var(--s-bg);
}

.media-tile__file {
  aspect-ratio: 4 / 3;
  display: grid;
  place-items: center;
  background: var(--s-bg);
  font-family: var(--font-mono);
  font-size: 12px;
  color: var(--s-muted);
}

.media-tile__name {
  display: block;
  padding: 5px 6px;
  font-size: 9.5px;
  color: var(--s-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.media-tile__check {
  position: absolute;
  top: 5px;
  right: 5px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: var(--s-accent);
  color: #fff;
  display: grid;
  place-items: center;
}

@media (max-width: 700px) {
  .media-picker-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>
