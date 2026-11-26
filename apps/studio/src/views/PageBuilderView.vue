<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { VueDraggable } from 'vue-draggable-plus'
import {
  ArrowLeft, Undo2, Redo2, Monitor, Tablet, Smartphone, Eye, Save, Search, Plus, Copy,
  Trash2, GripVertical, ChevronUp, ChevronDown, Layers, ExternalLink, AlertTriangle,
  CheckCircle2, Loader2, PanelRightClose, PanelRightOpen, Settings2, MousePointer2,
} from 'lucide-vue-next'
import StatusBadge from '@/components/StatusBadge.vue'
import BuilderCanvasBlock from '@/components/BuilderCanvasBlock.vue'
import FieldEditor from '@/components/FieldEditor.vue'
import { api, apiErrorMessage } from '@/api/client'
import { auth } from '@/stores/auth'
import { blocks as blockStore } from '@/stores/blocks'
import { toast } from '@/stores/toast'
import type { Block, MediaAsset, PageDetail } from '@/types'

const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))
const locale = ref(String(route.query.locale || 'en'))
const localeLabel = computed(() => ({ en: 'EN', th: 'TH', 'zh-CN': '中文' }[locale.value] || locale.value))
const loading = ref(true)
const error = ref('')
const page = ref<PageDetail | null>(null)
const blocksList = ref<Block[]>([])
const selectedUid = ref<string | null>(null)
const device = ref<'desktop' | 'tablet' | 'mobile'>('desktop')
const librarySearch = ref('')
const inspectorOpen = ref(true)
const saving = ref(false)
const saveStatus = ref<'saved' | 'unsaved' | 'saving' | 'error'>('saved')
const lockVersion = ref(0)
const history = ref<string[]>([])
const historyIndex = ref(-1)
const suppressHistory = ref(false)
const initialized = ref(false)
const dragActive = ref(false)
const canvas = ref<HTMLElement | null>(null)
let saveTimer: ReturnType<typeof setTimeout> | null = null

const selectedBlock = computed(() => blocksList.value.find((block) => block.uid === selectedUid.value) || null)
const selectedDefinition = computed(() => selectedBlock.value ? blockStore.definition(selectedBlock.value.type) : null)
const pageTitle = computed(() => page.value?.title || 'Untitled page')

const categories = computed(() => {
  const map: Record<string, typeof blockStore.definitions.value> = {}
  for (const definition of blockStore.definitions.value) {
    map[definition.category] = map[definition.category] || []
    map[definition.category].push(definition)
  }
  return map
})

const filteredCategories = computed(() => {
  const term = librarySearch.value.trim().toLowerCase()
  const result: Record<string, typeof blockStore.definitions.value> = {}
  for (const [category, list] of Object.entries(categories.value)) {
    const filtered = list.filter((item) => !term || item.label.toLowerCase().includes(term) || item.description.toLowerCase().includes(term))
    if (filtered.length) result[category] = filtered
  }
  return result
})

const stageClass = computed(() => `stage--${device.value}`)
const visibilityClass = computed(() => ({
  'stage-hide-desktop': !blocksList.value.length,
}))

function normalizeBlock(block: Block): Block {
  return {
    uid: block.uid || crypto.randomUUID(),
    type: block.type,
    props: block.props || {},
    responsive: block.responsive || { desktop: true, tablet: true, mobile: true, overrides: {} },
  }
}

async function load() {
  loading.value = true
  error.value = ''
  initialized.value = false
  try {
    const [pageResponse] = await Promise.all([
      api.get(`/pages/${id.value}`, { params: { locale: locale.value } }),
      blockStore.load(),
    ])
    page.value = pageResponse.data.data
    lockVersion.value = page.value!.lock_version
    blocksList.value = page.value!.blocks.map(normalizeBlock)
    selectedUid.value = blocksList.value[0]?.uid || null
    history.value = [JSON.stringify(blocksList.value)]
    historyIndex.value = 0
    saveStatus.value = 'saved'
    await nextTick()
    initialized.value = true
  } catch (err) {
    error.value = apiErrorMessage(err)
  } finally {
    loading.value = false
  }
}

function snapshot() {
  return JSON.stringify(blocksList.value.map((block) => ({
    uid: block.uid,
    type: block.type,
    props: block.props,
    responsive: block.responsive,
  })))
}

function pushHistory() {
  const snap = snapshot()
  if (history.value[historyIndex.value] === snap) return
  history.value = history.value.slice(0, historyIndex.value + 1)
  history.value.push(snap)
  historyIndex.value = history.value.length - 1
  if (history.value.length > 60) {
    history.value.shift()
    historyIndex.value--
  }
}

function applySnapshot(snap: string) {
  suppressHistory.value = true
  blocksList.value = JSON.parse(snap)
  if (!blocksList.value.some((block) => block.uid === selectedUid.value)) {
    selectedUid.value = blocksList.value[0]?.uid || null
  }
  nextTick(() => { suppressHistory.value = false })
}

function undo() {
  if (historyIndex.value <= 0) return
  historyIndex.value--
  applySnapshot(history.value[historyIndex.value])
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function redo() {
  if (historyIndex.value >= history.value.length - 1) return
  historyIndex.value++
  applySnapshot(history.value[historyIndex.value])
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function scheduleSave() {
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(saveBlocks, 1200)
}

function addBlock(type: string) {
  const definition = blockStore.definition(type)
  if (!definition) return
  const block: Block = {
    uid: crypto.randomUUID(),
    type,
    props: JSON.parse(JSON.stringify(definition.defaults)),
    responsive: { desktop: true, tablet: true, mobile: true, overrides: {} },
  }
  blocksList.value.push(block)
  selectedUid.value = block.uid
  saveStatus.value = 'unsaved'
  scheduleSave()
  nextTick(() => {
    canvas.value?.scrollTo({ top: canvas.value.scrollHeight, behavior: 'smooth' })
  })
}

function duplicateBlock(block: Block) {
  const index = blocksList.value.findIndex((item) => item.uid === block.uid)
  const copy: Block = {
    uid: crypto.randomUUID(),
    type: block.type,
    props: JSON.parse(JSON.stringify(block.props)),
    responsive: JSON.parse(JSON.stringify(block.responsive || { desktop: true, tablet: true, mobile: true })),
  }
  blocksList.value.splice(index + 1, 0, copy)
  selectedUid.value = copy.uid
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function removeBlock(block: Block) {
  const index = blocksList.value.findIndex((item) => item.uid === block.uid)
  if (index === -1) return
  blocksList.value.splice(index, 1)
  if (selectedUid.value === block.uid) {
    selectedUid.value = blocksList.value[Math.max(0, index - 1)]?.uid || null
  }
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function moveBlock(block: Block, direction: -1 | 1) {
  const index = blocksList.value.findIndex((item) => item.uid === block.uid)
  const target = index + direction
  if (index < 0 || target < 0 || target >= blocksList.value.length) return
  const [item] = blocksList.value.splice(index, 1)
  blocksList.value.splice(target, 0, item)
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function updateProp(key: string, value: any) {
  if (!selectedBlock.value) return
  selectedBlock.value.props = { ...selectedBlock.value.props, [key]: value }
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function updateResponsive(key: 'desktop' | 'tablet' | 'mobile', value: boolean) {
  if (!selectedBlock.value) return
  selectedBlock.value.responsive = {
    desktop: selectedBlock.value.responsive?.desktop ?? true,
    tablet: selectedBlock.value.responsive?.tablet ?? true,
    mobile: selectedBlock.value.responsive?.mobile ?? true,
    overrides: selectedBlock.value.responsive?.overrides || {},
    ...{ [key]: value },
  }
  saveStatus.value = 'unsaved'
  scheduleSave()
}

function updateOverride(key: string, value: any) {
  if (!selectedBlock.value) return
  const responsive = selectedBlock.value.responsive || { desktop: true, tablet: true, mobile: true, overrides: {} }
  selectedBlock.value.responsive = {
    ...responsive,
    overrides: { ...(responsive.overrides || {}), [key]: value },
  }
  saveStatus.value = 'unsaved'
  scheduleSave()
}

async function saveBlocks(manual = false) {
  if (!page.value) return
  if (saveTimer) clearTimeout(saveTimer)
  saving.value = true
  saveStatus.value = 'saving'
  try {
    const response = await api.put(`/pages/${id.value}/blocks`, {
      locale: locale.value,
      blocks: blocksList.value,
      lock_version: lockVersion.value,
    })
    lockVersion.value = response.data.data.lock_version
    saveStatus.value = 'saved'
    if (manual) toast.success('Layout saved')
  } catch (err) {
    saveStatus.value = 'error'
    toast.error('Autosave failed', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function openPreview() {
  const win = window.open('about:blank', '_blank')
  try {
    const response = await api.post(`/pages/${id.value}/preview-token`, null, { params: { locale: locale.value } })
    if (win) win.location.href = response.data.data.url
  } catch (err) {
    win?.close()
    toast.error('Could not create preview', apiErrorMessage(err))
  }
}

async function publish() {
  try {
    await api.post(`/pages/${id.value}/publish`)
    toast.success('Page published')
    await load()
  } catch (err) {
    toast.error('Publish blocked', apiErrorMessage(err))
  }
}

function onDragStart(event: DragEvent, type: string) {
  event.dataTransfer?.setData('application/x-meridian-block', type)
  event.dataTransfer!.effectAllowed = 'copy'
  dragActive.value = true
}

function onDrop(event: DragEvent) {
  event.preventDefault()
  dragActive.value = false
  const type = event.dataTransfer?.getData('application/x-meridian-block')
  if (type) addBlock(type)
}

watch(blocksList, () => {
  if (!initialized.value || suppressHistory.value) return
  saveStatus.value = 'unsaved'
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    pushHistory()
    saveBlocks()
  }, 1400)
}, { deep: true })

onMounted(load)

onBeforeUnmount(() => {
  if (saveTimer) clearTimeout(saveTimer)
})

onBeforeRouteLeave(async () => {
  if (saveStatus.value === 'saved' || saveStatus.value === 'saving') return true
  await saveBlocks()
  return true
})
</script>

<template>
  <div class="builder">
    <div v-if="loading" class="builder-loading">
      <Loader2 :size="22" class="spin" />
      <p class="mono">LOADING BUILDER</p>
    </div>

    <div v-else-if="error" class="empty-s" style="margin:30px">
      <p class="empty-s__title">Builder unavailable</p>
      <p class="empty-s__body">{{ error }}</p>
      <button type="button" class="btn-s" style="margin-top:10px" @click="router.push('/pages')">Back to pages</button>
    </div>

    <template v-else-if="page">
      <div class="builder-toolbar">
        <div class="builder-toolbar__left">
          <button type="button" class="btn-s btn-s--ghost btn-s--sm" @click="router.push(`/pages/${id}?locale=${locale}`)"><ArrowLeft :size="13" /> Editor</button>
          <div class="builder-divider" />
          <div class="builder-title">
            <p class="builder-title__name">{{ pageTitle }}</p>
            <div class="builder-title__meta">
              <StatusBadge :status="page.status" />
              <span class="mono faint">{{ localeLabel }} · {{ blocksList.length }} blocks</span>
            </div>
          </div>
        </div>

        <div class="builder-toolbar__center">
          <div class="device-switcher">
            <button type="button" :class="{ 'is-active': device === 'desktop' }" title="Desktop" @click="device = 'desktop'"><Monitor :size="14" /></button>
            <button type="button" :class="{ 'is-active': device === 'tablet' }" title="Tablet" @click="device = 'tablet'"><Tablet :size="14" /></button>
            <button type="button" :class="{ 'is-active': device === 'mobile' }" title="Mobile" @click="device = 'mobile'"><Smartphone :size="14" /></button>
          </div>
        </div>

        <div class="builder-toolbar__right">
          <span class="save-state" :class="`save-state--${saveStatus}`">
            <Loader2 v-if="saveStatus === 'saving'" :size="11" class="spin" />
            <CheckCircle2 v-else-if="saveStatus === 'saved'" :size="11" />
            <AlertTriangle v-else-if="saveStatus === 'error'" :size="11" />
            {{ saveStatus === 'saved' ? 'Saved' : saveStatus === 'saving' ? 'Saving…' : saveStatus === 'error' ? 'Save failed' : 'Unsaved' }}
          </span>
          <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Undo" :disabled="historyIndex <= 0" @click="undo"><Undo2 :size="14" /></button>
          <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" title="Redo" :disabled="historyIndex >= history.length - 1" @click="redo"><Redo2 :size="14" /></button>
          <div class="builder-divider" />
          <button type="button" class="btn-s btn-s--sm" @click="openPreview"><Eye :size="13" /> Preview</button>
          <button type="button" class="btn-s btn-s--sm" @click="saveBlocks(true)"><Save :size="13" /> Save</button>
          <button v-if="page.capabilities.publish" type="button" class="btn-s btn-s--accent btn-s--sm" @click="publish"><CheckCircle2 :size="13" /> Publish</button>
          <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :title="inspectorOpen ? 'Hide inspector' : 'Show inspector'" @click="inspectorOpen = !inspectorOpen">
            <PanelRightClose v-if="inspectorOpen" :size="14" />
            <PanelRightOpen v-else :size="14" />
          </button>
        </div>
      </div>

      <div class="builder-body" :class="{ 'builder-body--no-inspector': !inspectorOpen }">
        <aside class="builder-library">
          <div class="builder-library__head">
            <p class="mono">BLOCK LIBRARY</p>
            <span class="mono faint">{{ blockStore.definitions.value.length }}</span>
          </div>
          <div class="builder-library__search">
            <Search :size="12" />
            <input v-model="librarySearch" placeholder="Filter blocks" aria-label="Filter block library">
          </div>
          <div class="builder-library__scroll">
            <div v-for="(list, category) in filteredCategories" :key="category" class="library-group">
              <p class="library-group__label">{{ category }}</p>
              <button
                v-for="definition in list"
                :key="definition.type"
                type="button"
                class="library-item"
                draggable="true"
                @dragstart="onDragStart($event, definition.type)"
                @click="addBlock(definition.type)"
              >
                <span class="library-item__icon"><Plus :size="12" /></span>
                <span>
                  <span class="library-item__title">{{ definition.label }}</span>
                  <span class="library-item__desc">{{ definition.description }}</span>
                </span>
                <GripVertical :size="12" class="library-item__grip" />
              </button>
            </div>
          </div>
          <p class="builder-library__hint"><MousePointer2 :size="11" /> Click or drag a block into the canvas.</p>
        </aside>

        <main ref="canvas" class="builder-canvas" :class="{ 'is-drag-over': dragActive }" @dragover.prevent="dragActive = true" @dragleave="dragActive = false" @drop="onDrop">
          <div class="stage-wrap">
            <div class="stage" :class="stageClass">
              <VueDraggable v-model="blocksList" :animation="180" handle=".block-drag" ghost-class="block-ghost" class="stage-blocks">
                <article
                  v-for="(block, index) in blocksList"
                  :key="block.uid"
                  class="canvas-block"
                  :class="{ 'is-selected': selectedUid === block.uid, [`canvas-block--${block.type}`]: true }"
                  @click="selectedUid = block.uid"
                >
                  <div class="canvas-block__bar">
                    <span class="block-drag" title="Drag to reorder"><GripVertical :size="13" /></span>
                    <span class="canvas-block__type mono">{{ blockStore.definition(block.type)?.label || block.type }}</span>
                    <span class="canvas-block__uid mono">{{ block.uid.slice(0, 6) }}</span>
                    <div class="canvas-block__actions">
                      <button type="button" title="Move up" :disabled="index === 0" @click.stop="moveBlock(block, -1)"><ChevronUp :size="12" /></button>
                      <button type="button" title="Move down" :disabled="index === blocksList.length - 1" @click.stop="moveBlock(block, 1)"><ChevronDown :size="12" /></button>
                      <button type="button" title="Duplicate" @click.stop="duplicateBlock(block)"><Copy :size="12" /></button>
                      <button type="button" title="Delete" @click.stop="removeBlock(block)"><Trash2 :size="12" /></button>
                    </div>
                  </div>
                  <div class="canvas-block__preview">
                    <BuilderCanvasBlock :block="block" />
                  </div>
                  <div v-if="block.responsive && (!block.responsive.desktop || !block.responsive.tablet || !block.responsive.mobile)" class="canvas-block__visibility mono">
                    HIDDEN:
                    <span v-if="!block.responsive.desktop">desktop</span>
                    <span v-if="!block.responsive.tablet">tablet</span>
                    <span v-if="!block.responsive.mobile">mobile</span>
                  </div>
                </article>
              </VueDraggable>

              <div v-if="!blocksList.length" class="canvas-empty" @click="addBlock('hero')">
                <Layers :size="26" />
                <p>Start with a hero</p>
                <span>Click to add, or drag a block from the library.</span>
              </div>
            </div>
          </div>
          <div v-if="dragActive" class="drop-overlay">Drop block into the page</div>
        </main>

        <aside v-if="inspectorOpen" class="builder-inspector">
          <div class="builder-inspector__head">
            <p class="mono">BLOCK SETTINGS</p>
            <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="inspectorOpen = false"><PanelRightClose :size="14" /></button>
          </div>

          <div v-if="selectedBlock && selectedDefinition" class="builder-inspector__body">
            <div class="inspector-title">
              <p>{{ selectedDefinition.label }}</p>
              <span>{{ selectedDefinition.description }}</span>
            </div>

            <div class="inspector-section">
              <p class="inspector-section__label">Content</p>
              <div v-for="field in selectedDefinition.fields" :key="field.key" class="inspector-field">
                <label class="field-s__label">{{ field.label }}</label>
                <FieldEditor :field="field" :model-value="selectedBlock.props[field.key]" @update:model-value="(next) => updateProp(field.key, next)" />
              </div>
            </div>

            <div class="inspector-section">
              <p class="inspector-section__label">Visibility</p>
              <div class="visibility-grid">
                <label v-for="key in ['desktop','tablet','mobile']" :key="key" class="visibility-toggle">
                  <input type="checkbox" :checked="selectedBlock.responsive?.[key] ?? true" @change="updateResponsive(key as any, ($event.target as HTMLInputElement).checked)">
                  <span>{{ key }}</span>
                </label>
              </div>
            </div>

            <div class="inspector-section">
              <p class="inspector-section__label">Presentation overrides</p>
              <div class="inspector-field">
                <label class="field-s__label">Background</label>
                <select :value="selectedBlock.responsive?.overrides?.background || ''" class="select-s" @change="updateOverride('background', ($event.target as HTMLSelectElement).value)">
                  <option value="">Block default</option>
                  <option value="ivory">Ivory</option>
                  <option value="muted">Steel</option>
                  <option value="dark">Midnight</option>
                </select>
              </div>
              <div class="inspector-field">
                <label class="field-s__label">Spacing</label>
                <select :value="selectedBlock.responsive?.overrides?.spacing || 'normal'" class="select-s" @change="updateOverride('spacing', ($event.target as HTMLSelectElement).value)">
                  <option value="compact">Compact</option>
                  <option value="normal">Normal</option>
                  <option value="roomy">Roomy</option>
                </select>
              </div>
              <div class="inspector-field">
                <label class="field-s__label">Alignment</label>
                <select :value="selectedBlock.responsive?.overrides?.alignment || 'left'" class="select-s" @change="updateOverride('alignment', ($event.target as HTMLSelectElement).value)">
                  <option value="left">Left</option>
                  <option value="center">Centre</option>
                </select>
              </div>
            </div>

            <div class="inspector-danger">
              <button type="button" class="btn-s btn-s--danger btn-s--sm" @click="removeBlock(selectedBlock)"><Trash2 :size="12" /> Remove block</button>
              <button type="button" class="btn-s btn-s--ghost btn-s--sm" @click="duplicateBlock(selectedBlock)"><Copy :size="12" /> Duplicate</button>
            </div>
          </div>

          <div v-else class="builder-inspector__empty">
            <Settings2 :size="24" />
            <p>Select a block</p>
            <span>Choose a block on the canvas to edit its content, visibility, and presentation.</span>
          </div>
        </aside>
      </div>
    </template>
  </div>
</template>

<style scoped>
.builder {
  margin: -26px -30px -48px;
  height: calc(100vh - var(--s-topbar));
  display: flex;
  flex-direction: column;
  background: var(--s-bg);
  overflow: hidden;
}

.builder-loading {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--s-muted);
}

.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.builder-toolbar {
  height: 56px;
  flex: 0 0 auto;
  background: var(--s-surface);
  border-bottom: 1px solid var(--s-line);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 0 16px;
}

.builder-toolbar__left,
.builder-toolbar__right {
  display: flex;
  align-items: center;
  gap: 7px;
  min-width: 0;
}

.builder-toolbar__left { flex: 1; }
.builder-toolbar__right { flex: 1; justify-content: flex-end; }

.builder-title { min-width: 0; }
.builder-title__name { font-size: 12px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.builder-title__meta { display: flex; align-items: center; gap: 8px; margin-top: 2px; }

.builder-divider { width: 1px; height: 20px; background: var(--s-line); }

.device-switcher {
  display: flex;
  background: var(--s-bg);
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  padding: 2px;
}

.device-switcher button {
  width: 30px;
  height: 26px;
  border: 0;
  background: transparent;
  border-radius: 3px;
  color: var(--s-muted);
  display: grid;
  place-items: center;
}

.device-switcher button.is-active { background: var(--s-white); color: var(--s-ink); box-shadow: 0 1px 2px rgba(15,27,41,.1); }

.save-state {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-family: var(--font-mono);
  font-size: 8px;
  text-transform: uppercase;
  color: var(--s-faint);
  margin-right: 5px;
}

.save-state--saved { color: var(--s-green); }
.save-state--error { color: var(--s-red); }
.save-state--unsaved { color: var(--s-amber); }

.builder-body {
  flex: 1;
  display: grid;
  grid-template-columns: 232px minmax(0, 1fr) 330px;
  min-height: 0;
}

.builder-body--no-inspector { grid-template-columns: 232px minmax(0, 1fr); }

.builder-library {
  border-right: 1px solid var(--s-line);
  background: var(--s-surface);
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.builder-library__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 11px 12px;
  border-bottom: 1px solid var(--s-line);
}

.builder-library__search {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 8px;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  background: var(--s-white);
  padding: 0 8px;
  color: var(--s-faint);
}

.builder-library__search input {
  border: 0;
  background: transparent;
  height: 30px;
  width: 100%;
  font-size: 12px;
  outline: none;
}

.builder-library__scroll {
  flex: 1;
  overflow-y: auto;
  padding: 0 8px 10px;
}

.library-group { margin-bottom: 10px; }
.library-group__label {
  font-family: var(--font-mono);
  font-size: 7.5px;
  text-transform: uppercase;
  letter-spacing: .09em;
  color: var(--s-faint);
  padding: 5px 4px;
}

.library-item {
  width: 100%;
  border: 1px solid transparent;
  background: transparent;
  border-radius: var(--s-radius);
  padding: 7px 6px;
  display: flex;
  align-items: flex-start;
  gap: 7px;
  text-align: left;
  color: var(--s-ink-2);
  transition: background 140ms ease, border-color 140ms ease;
  cursor: grab;
}

.library-item:hover {
  background: var(--s-white);
  border-color: var(--s-line);
}

.library-item__icon {
  width: 20px;
  height: 20px;
  border: 1px solid var(--s-line);
  display: grid;
  place-items: center;
  color: var(--s-accent);
  flex: 0 0 auto;
  margin-top: 1px;
}

.library-item__title { display: block; font-size: 12px; font-weight: 650; }
.library-item__desc { display: block; font-size: 12px; color: var(--s-faint); line-height: 1.35; margin-top: 1px; }
.library-item__grip { margin-left: auto; color: var(--s-line-strong); flex: 0 0 auto; }

.builder-library__hint {
  padding: 8px 12px;
  border-top: 1px solid var(--s-line);
  color: var(--s-faint);
  font-size: 8.5px;
  display: flex;
  gap: 5px;
  align-items: center;
}

.builder-canvas {
  overflow-y: auto;
  background: #e6e2da;
  padding: 24px;
  position: relative;
}

.stage-wrap { display: flex; justify-content: center; }

.stage {
  width: 100%;
  max-width: 1180px;
  background: #fbfaf7;
  box-shadow: 0 8px 30px rgba(15, 27, 41, .1);
  transition: width 260ms var(--s-ease), max-width 260ms var(--s-ease);
  min-height: 200px;
}

.stage--tablet { width: 834px; max-width: 834px; }
.stage--mobile { width: 390px; max-width: 390px; }

.stage-blocks { min-height: 200px; }

.canvas-block {
  position: relative;
  border: 1px solid transparent;
  transition: border-color 140ms ease;
}

.canvas-block:hover { border-color: rgba(227, 109, 63, .35); }
.canvas-block.is-selected { border-color: var(--s-accent); }

.canvas-block__bar {
  position: absolute;
  top: -23px;
  left: -1px;
  right: -1px;
  height: 23px;
  background: var(--s-ink);
  color: #f5f3ee;
  display: none;
  align-items: center;
  gap: 7px;
  padding: 0 7px;
  z-index: 5;
}

.canvas-block.is-selected .canvas-block__bar,
.canvas-block:hover .canvas-block__bar { display: flex; }

.block-drag { cursor: grab; display: inline-flex; color: rgba(245,243,238,.5); }
.canvas-block__type { font-size: 7.5px; }
.canvas-block__uid { font-size: 7px; color: rgba(245,243,238,.35); }
.canvas-block__actions { margin-left: auto; display: flex; gap: 1px; }
.canvas-block__actions button {
  border: 0;
  background: transparent;
  color: rgba(245,243,238,.6);
  width: 20px;
  height: 19px;
  display: grid;
  place-items: center;
}
.canvas-block__actions button:hover { background: rgba(245,243,238,.1); color: #fff; }
.canvas-block__actions button:disabled { opacity: .25; }

.canvas-block__visibility {
  position: absolute;
  right: 6px;
  bottom: 6px;
  background: var(--s-amber-soft);
  color: var(--s-amber);
  padding: 2px 5px;
  font-size: 7px;
  z-index: 3;
}

.block-ghost { opacity: .3; }

.canvas-empty {
  min-height: 380px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  color: var(--s-muted);
  border: 1px dashed var(--s-line-strong);
  margin: 20px;
  cursor: pointer;
}

.canvas-empty p { font-size: 14px; font-weight: 650; color: var(--s-ink); }
.canvas-empty span { font-size: 12px; }

.drop-overlay {
  position: fixed;
  inset: 0;
  background: rgba(227, 109, 63, .1);
  border: 2px dashed var(--s-accent);
  pointer-events: none;
  z-index: 20;
  display: grid;
  place-items: center;
  color: var(--s-accent);
  font-family: var(--font-mono);
  font-size: 12px;
  text-transform: uppercase;
}

.builder-inspector {
  border-left: 1px solid var(--s-line);
  background: var(--s-surface);
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.builder-inspector__head {
  height: 42px;
  padding: 0 10px 0 12px;
  border-bottom: 1px solid var(--s-line);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.builder-inspector__body {
  overflow-y: auto;
  padding: 12px;
}

.inspector-title { padding-bottom: 10px; border-bottom: 1px solid var(--s-line); }
.inspector-title p { font-size: 12px; font-weight: 700; }
.inspector-title span { display: block; margin-top: 3px; font-size: 9.5px; color: var(--s-faint); line-height: 1.4; }

.inspector-section {
  padding-block: 13px;
  border-bottom: 1px solid var(--s-line);
}

.inspector-section__label {
  font-family: var(--font-mono);
  font-size: 8px;
  letter-spacing: .09em;
  text-transform: uppercase;
  color: var(--s-faint);
  margin-bottom: 9px;
}

.inspector-field { margin-bottom: 10px; }
.inspector-field:last-child { margin-bottom: 0; }
.inspector-field > .field-s__label { margin-bottom: 4px; }

.visibility-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 5px;
}

.visibility-toggle {
  display: flex;
  align-items: center;
  gap: 5px;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  padding: 6px;
  font-size: 12px;
  color: var(--s-muted);
}

.visibility-toggle input { accent-color: var(--s-accent); }

.inspector-danger {
  padding-top: 13px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.builder-inspector__empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 30px;
  text-align: center;
  color: var(--s-faint);
}

.builder-inspector__empty p { font-size: 12px; font-weight: 650; color: var(--s-ink); }
.builder-inspector__empty span { font-size: 9.5px; line-height: 1.45; max-width: 26ch; }

:global(.stage--mobile) :deep(.bc-hero),
:global(.stage--mobile) :deep(.bc-split),
:global(.stage--mobile) :deep(.bc-project-hero),
:global(.stage--mobile) :deep(.bc-form) {
  grid-template-columns: 1fr;
}

:global(.stage--mobile) :deep(.bc-placeholder-grid),
:global(.stage--mobile) :deep(.bc-stats),
:global(.stage--mobile) :deep(.bc-tech-grid),
:global(.stage--mobile) :deep(.bc-offices),
:global(.stage--mobile) :deep(.bc-gallery) {
  grid-template-columns: 1fr 1fr;
}

:global(.stage--tablet) :deep(.bc-placeholder-grid),
:global(.stage--tablet) :deep(.bc-tech-grid),
:global(.stage--tablet) :deep(.bc-offices) {
  grid-template-columns: 1fr 1fr;
}

@media (max-width: 1300px) {
  .builder-body { grid-template-columns: 210px minmax(0, 1fr) 300px; }
}

@media (max-width: 1050px) {
  .builder-body { grid-template-columns: 190px minmax(0, 1fr); }
  .builder-inspector { display: none; }
  .builder-toolbar__center { display: none; }
}

@media (max-width: 700px) {
  .builder { margin: -18px -16px -40px; }
  .builder-body { grid-template-columns: 1fr; }
  .builder-library { display: none; }
}
</style>
