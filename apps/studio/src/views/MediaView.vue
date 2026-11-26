<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Search, Upload, FolderPlus, Trash2, Copy, Check, Image as ImageIcon, FileText, X } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import BaseDrawer from '@/components/BaseDrawer.vue'
import BaseModal from '@/components/BaseModal.vue'
import EmptyState from '@/components/EmptyState.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { toast } from '@/stores/toast'
import type { MediaAsset, PaginationMeta } from '@/types'

const route = useRoute()
const loading = ref(true)
const uploads = ref(0)
const uploadProgress = ref(0)
const assets = ref<MediaAsset[]>([])
const meta = ref<PaginationMeta | null>(null)
const folders = ref<any[]>([])
const storage = ref<any>(null)
const search = ref('')
const type = ref('')
const folderId = ref('')
const selected = ref<MediaAsset | null>(null)
const detailOpen = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const saving = ref(false)
const copied = ref(false)
const usageModal = ref<any[] | null>(null)
const deleteTarget = ref<MediaAsset | null>(null)
const deleteReason = ref('')
const newFolder = ref('')
const folderModal = ref(false)

const edit = ref({ altEn: '', altTh: '', altZh: '', captionEn: '', captionTh: '', captionZh: '', credit: '', license: '', source_url: '', focal_x: 0.5, focal_y: 0.5 })
const alt = computed(() => selected.value?.alt || {})

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/media', { params: { q: search.value || undefined, type: type.value || undefined, folder_id: folderId.value || undefined, per_page: 36 } })
    assets.value = data.data
    meta.value = data.meta
    folders.value = data.folders
    storage.value = data.storage
  } finally {
    loading.value = false
  }
}

const debounced = (() => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(load, 320)
  }
})()

function selectAsset(asset: MediaAsset) {
  selected.value = asset
  edit.value = {
    altEn: asset.alt?.en || '',
    altTh: asset.alt?.th || '',
    altZh: asset.alt?.['zh-CN'] || '',
    captionEn: asset.caption?.en || '',
    captionTh: asset.caption?.th || '',
    captionZh: asset.caption?.['zh-CN'] || '',
    credit: asset.credit || '',
    license: asset.license || '',
    source_url: asset.source_url || '',
    focal_x: asset.focal_x ?? 0.5,
    focal_y: asset.focal_y ?? 0.5,
  }
  detailOpen.value = true
}

async function upload(event: Event) {
  const files = (event.target as HTMLInputElement).files
  if (!files?.length) return
  uploads.value = files.length
  uploadProgress.value = 0
  const body = new FormData()
  Array.from(files).forEach((file) => body.append('files[]', file))
  try {
    await api.post('/media', body, {
      onUploadProgress: (event) => {
        if (event.total) uploadProgress.value = Math.round((event.loaded / event.total) * 100)
      },
    })
    toast.success(`${files.length} file(s) uploaded`)
    await load()
  } catch (err) {
    toast.error('Upload failed', apiErrorMessage(err))
  } finally {
    uploads.value = 0
    uploadProgress.value = 0
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function saveDetails() {
  if (!selected.value) return
  saving.value = true
  try {
    await api.patch(`/media/${selected.value.id}`, {
      alt: { en: edit.value.altEn, th: edit.value.altTh, 'zh-CN': edit.value.altZh },
      caption: { en: edit.value.captionEn, th: edit.value.captionTh, 'zh-CN': edit.value.captionZh },
      credit: edit.value.credit || null,
      license: edit.value.license || null,
      source_url: edit.value.source_url || null,
      focal_x: edit.value.focal_x,
      focal_y: edit.value.focal_y,
    })
    toast.success('Media updated')
    detailOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not update media', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function attemptDelete(asset: MediaAsset) {
  deleteTarget.value = asset
  try {
    await api.delete(`/media/${asset.id}`)
    toast.success('Media deleted')
    deleteTarget.value = null
    await load()
  } catch (err: any) {
    if (err?.response?.status === 409) {
      usageModal.value = err.response.data.usage
      deleteReason.value = err.response.data.message
    } else {
      toast.error('Could not delete media', apiErrorMessage(err))
      deleteTarget.value = null
    }
  }
}

async function forceDelete() {
  if (!deleteTarget.value) return
  try {
    await api.delete(`/media/${deleteTarget.value.id}?force=1`)
    toast.success('Media deleted with force')
    deleteTarget.value = null
    usageModal.value = null
    detailOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not force delete', apiErrorMessage(err))
  }
}

async function copyUrl() {
  if (!selected.value) return
  await navigator.clipboard.writeText(selected.value.url)
  copied.value = true
  setTimeout(() => { copied.value = false }, 1800)
}

async function createFolder() {
  if (!newFolder.value.trim()) return
  try {
    await api.post('/media/folders', { name: newFolder.value.trim() })
    newFolder.value = ''
    folderModal.value = false
    toast.success('Folder created')
    await load()
  } catch (err) {
    toast.error('Could not create folder', apiErrorMessage(err))
  }
}

function formatBytes(bytes?: number) {
  if (!bytes) return '—'
  if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

onMounted(async () => {
  await load()
  if (route.query.upload === '1') fileInput.value?.click()
})
</script>

<template>
  <div>
    <PageHead eyebrow="ASSETS" title="Media library" description="Upload, describe, and reuse the images and documents that power every page and locale.">
      <template #actions>
        <button type="button" class="btn-s" @click="folderModal = true"><FolderPlus :size="13" /> New folder</button>
        <input ref="fileInput" type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif,application/pdf" style="display:none" @change="upload">
        <button v-if="auth.can('media.upload')" type="button" class="btn-s btn-s--primary" @click="fileInput?.click()"><Upload :size="13" /> Upload files</button>
      </template>
    </PageHead>

    <div class="media-stats">
      <div><span class="mono muted">ASSETS</span><strong>{{ meta?.total ?? 0 }}</strong></div>
      <div><span class="mono muted">STORAGE</span><strong>{{ storage?.bytes ? formatBytes(storage.bytes) : '—' }}</strong></div>
      <div><span class="mono muted">FOLDERS</span><strong>{{ folders.length }}</strong></div>
      <div class="media-quota">
        <span class="mono muted">DEMO QUOTA</span>
        <div class="quota-track"><span :style="{ width: `${Math.min(100, ((storage?.bytes || 0) / (5 * 1024 * 1024 * 1024)) * 100)}%` }" /></div>
      </div>
    </div>

    <div v-if="uploads" class="upload-progress">
      <div class="flex-between"><span>Uploading {{ uploads }} file(s)</span><span class="mono">{{ uploadProgress }}%</span></div>
      <div class="quota-track"><span :style="{ width: `${uploadProgress}%`, background: 'var(--s-accent)' }" /></div>
    </div>

    <div class="toolbar">
      <div class="toolbar__search">
        <Search :size="13" />
        <input v-model="search" class="input-s" placeholder="Search by filename or alt text" @input="debounced">
      </div>
      <select v-model="type" class="select-s" style="width:140px" @change="load">
        <option value="">All types</option>
        <option value="image">Images</option>
        <option value="document">Documents</option>
      </select>
      <select v-model="folderId" class="select-s" style="width:170px" @change="load">
        <option value="">All folders</option>
        <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }} ({{ folder.count }})</option>
      </select>
      <div class="toolbar__spacer" />
      <span class="mono faint">Click an asset to edit metadata</span>
    </div>

    <div v-if="loading" class="media-grid">
      <div v-for="i in 18" :key="i" class="skeleton" style="aspect-ratio:4/3" />
    </div>

    <EmptyState v-else-if="!assets.length" title="No media yet" body="Upload images or PDFs to start building the library." action-label="Upload files" @action="fileInput?.click()" />

    <div v-else class="media-grid">
      <button v-for="asset in assets" :key="asset.id" type="button" class="media-card" @click="selectAsset(asset)">
        <div class="media-card__preview">
          <img v-if="asset.mime_type.startsWith('image/')" :src="asset.url" :alt="asset.alt?.en || asset.original_name" loading="lazy">
          <div v-else class="media-card__file"><FileText :size="22" /><span>{{ asset.extension.toUpperCase() }}</span></div>
          <span v-if="asset.usage_count" class="media-card__usage">{{ asset.usage_count }} use{{ asset.usage_count === 1 ? '' : 's' }}</span>
        </div>
        <div class="media-card__meta">
          <span class="media-card__name">{{ asset.original_name }}</span>
          <span class="mono faint">{{ formatBytes(asset.size) }}</span>
        </div>
      </button>
    </div>

    <BaseDrawer :open="detailOpen" title="Media details" width="480px" @close="detailOpen = false">
      <div v-if="selected">
        <div class="asset-preview">
          <img v-if="selected.mime_type.startsWith('image/')" :src="selected.url" :alt="selected.alt?.en || selected.original_name" :style="{ objectPosition: `${edit.focal_x * 100}% ${edit.focal_y * 100}%` }">
          <div v-else class="media-card__file"><FileText :size="28" /><span>{{ selected.extension.toUpperCase() }}</span></div>
        </div>

        <div class="asset-file">
          <div>
            <p class="asset-file__name">{{ selected.original_name }}</p>
            <p class="mono faint">{{ selected.mime_type }} · {{ formatBytes(selected.size) }} <span v-if="selected.width">· {{ selected.width }}×{{ selected.height }}</span></p>
          </div>
          <button type="button" class="btn-s btn-s--sm" @click="copyUrl"><Check v-if="copied" :size="12" /><Copy v-else :size="12" /> {{ copied ? 'Copied' : 'Copy URL' }}</button>
        </div>

        <div class="drawer-section">
          <p class="mono muted">ALT TEXT</p>
          <div class="field-s"><label class="field-s__label">English</label><input v-model="edit.altEn" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">ไทย</label><input v-model="edit.altTh" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">简体中文</label><input v-model="edit.altZh" class="input-s"></div>
        </div>

        <div class="drawer-section">
          <p class="mono muted">CAPTION</p>
          <div class="field-s"><label class="field-s__label">English</label><textarea v-model="edit.captionEn" class="textarea-s" rows="2" /></div>
          <div class="field-s"><label class="field-s__label">ไทย</label><textarea v-model="edit.captionTh" class="textarea-s" rows="2" /></div>
        </div>

        <div v-if="selected.mime_type.startsWith('image/')" class="drawer-section">
          <p class="mono muted">FOCAL POINT</p>
          <div class="focal-grid">
            <label class="field-s"><span class="field-s__label">X axis</span><input v-model.number="edit.focal_x" type="range" min="0" max="1" step="0.01"></label>
            <label class="field-s"><span class="field-s__label">Y axis</span><input v-model.number="edit.focal_y" type="range" min="0" max="1" step="0.01"></label>
          </div>
        </div>

        <div class="drawer-section">
          <p class="mono muted">CREDIT & LICENCE</p>
          <div class="field-s"><label class="field-s__label">Credit</label><input v-model="edit.credit" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Licence</label><input v-model="edit.license" class="input-s"></div>
          <div class="field-s"><label class="field-s__label">Source URL</label><input v-model="edit.source_url" class="input-s"></div>
        </div>

        <div class="drawer-section">
          <p class="mono muted">USAGE</p>
          <p style="font-size: 12px;color:var(--s-muted)">{{ selected.usage_count }} reference(s). Deletion is blocked while published content still points at this asset.</p>
          <p class="mono faint" style="margin-top:4px">{{ selected.uploaded_by || 'Seed asset' }} · {{ selected.created_at ? new Date(selected.created_at).toLocaleDateString('en-GB') : '' }}</p>
        </div>
      </div>

      <template #footer>
        <button v-if="auth.can('media.delete')" type="button" class="btn-s btn-s--danger" @click="selected && attemptDelete(selected)"><Trash2 :size="12" /> Delete</button>
        <button type="button" class="btn-s" @click="detailOpen = false">Close</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving" @click="saveDetails">{{ saving ? 'Saving…' : 'Save metadata' }}</button>
      </template>
    </BaseDrawer>

    <BaseModal :open="Boolean(usageModal)" title="This asset is in use" @close="usageModal = null; deleteTarget = null">
      <p style="font-size: 12px;line-height:1.6;color:var(--s-muted)">{{ deleteReason }}</p>
      <div class="usage-list">
        <div v-for="(usage, index) in usageModal || []" :key="index" class="usage-item">
          <span class="mono">{{ usage.type }}</span>
          <span>{{ usage.title }}</span>
          <StatusBadge :status="usage.status || 'unknown'" />
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="usageModal = null; deleteTarget = null">Keep asset</button>
        <button type="button" class="btn-s btn-s--danger" @click="forceDelete">Delete anyway</button>
      </template>
    </BaseModal>

    <BaseModal :open="folderModal" title="New folder" @close="folderModal = false">
      <div class="field-s">
        <label class="field-s__label" for="folder-name">Folder name</label>
        <input id="folder-name" v-model="newFolder" class="input-s" placeholder="Project photography">
      </div>
      <template #footer>
        <button type="button" class="btn-s" @click="folderModal = false">Cancel</button>
        <button type="button" class="btn-s btn-s--primary" :disabled="!newFolder.trim()" @click="createFolder">Create folder</button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.media-stats {
  display: flex;
  gap: 24px;
  align-items: flex-end;
  flex-wrap: wrap;
  border: 1px solid var(--s-line);
  background: var(--s-surface);
  border-radius: var(--s-radius);
  padding: 10px 14px;
  margin-bottom: 12px;
}

.media-stats > div { display: flex; flex-direction: column; gap: 2px; }
.media-stats strong { font-size: 17px; letter-spacing: -.03em; }
.media-quota { width: 220px; margin-left: auto; }
.quota-track { height: 4px; background: var(--s-bg); border-radius: 999px; overflow: hidden; margin-top: 5px; }
.quota-track span { display: block; height: 100%; background: var(--s-ink); }

.upload-progress { border: 1px solid var(--s-line); background: var(--s-surface); padding: 9px 12px; border-radius: var(--s-radius); margin-bottom: 12px; font-size: 12px; }

.media-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 9px;
}

.media-card {
  border: 1px solid var(--s-line);
  background: var(--s-white);
  border-radius: var(--s-radius);
  padding: 0;
  overflow: hidden;
  text-align: left;
  transition: border-color 150ms ease, transform 150ms ease;
}

.media-card:hover { border-color: var(--s-ink); transform: translateY(-1px); }

.media-card__preview { position: relative; aspect-ratio: 4 / 3; background: var(--s-bg); }
.media-card__preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
.media-card__file { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; color: var(--s-muted); font-family: var(--font-mono); font-size: 12px; }
.media-card__usage { position: absolute; left: 5px; bottom: 5px; background: rgba(15,27,41,.78); color: #f5f3ee; font-family: var(--font-mono); font-size: 7px; padding: 2px 4px; }
.media-card__meta { padding: 6px 7px; display: flex; flex-direction: column; gap: 2px; }
.media-card__name { font-size: 9.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.asset-preview { aspect-ratio: 16 / 9; background: var(--s-bg); border-radius: var(--s-radius); overflow: hidden; display: flex; align-items: center; justify-content: center; }
.asset-preview img { width: 100%; height: 100%; object-fit: cover; }
.asset-file { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; padding-block: 12px; border-bottom: 1px solid var(--s-line); }
.asset-file__name { font-size: 12px; font-weight: 650; }
.drawer-section { padding-block: 12px; border-bottom: 1px solid var(--s-line); display: flex; flex-direction: column; gap: 8px; }
.drawer-section > .mono { margin-bottom: 2px; }
.focal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.focal-grid input[type="range"] { accent-color: var(--s-accent); }

.usage-list { margin-top: 12px; border-top: 1px solid var(--s-line); }
.usage-item { display: grid; grid-template-columns: 80px 1fr auto; gap: 8px; align-items: center; padding: 7px 0; border-bottom: 1px solid var(--s-line); font-size: 12px; }

@media (max-width: 1300px) { .media-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 800px) { .media-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .media-quota { margin-left: 0; } }
</style>
