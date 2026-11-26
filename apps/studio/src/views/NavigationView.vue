<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Plus, Trash2, ChevronUp, ChevronDown, Save, Navigation, ExternalLink } from 'lucide-vue-next'
import PageHead from '@/components/PageHead.vue'
import { api, apiErrorMessage } from '@/api/client'
import { toast } from '@/stores/toast'

const loading = ref(true)
const saving = ref(false)
const locale = ref('en')
const menus = ref<any[]>([])
const pages = ref<any[]>([])
const selectedMenuId = ref<number | null>(null)
const items = ref<any[]>([])
const dirty = ref(false)

const locales = [
  { code: 'en', label: 'English' },
  { code: 'th', label: 'ไทย' },
  { code: 'zh-CN', label: '简体中文' },
]

const selectedMenu = computed(() => menus.value.find((menu) => menu.id === selectedMenuId.value))

function cloneItems(source: any[]): any[] {
  return source.map((item) => ({
    ...item,
    label: typeof item.label === 'string' ? { en: item.label, th: item.label, 'zh-CN': item.label } : { ...item.label },
    children: cloneItems(item.children || []),
  }))
}

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/navigation', { params: { locale: locale.value } })
    menus.value = data.data
    pages.value = data.pages
    if (!selectedMenuId.value || !menus.value.some((menu) => menu.id === selectedMenuId.value)) {
      selectedMenuId.value = menus.value.find((menu) => menu.key === 'main')?.id || menus.value[0]?.id || null
    }
    const menu = menus.value.find((item) => item.id === selectedMenuId.value)
    items.value = menu ? cloneItems(menu.items) : []
    dirty.value = false
  } catch (err) {
    toast.error('Could not load navigation', apiErrorMessage(err))
  } finally {
    loading.value = false
  }
}

function selectMenu(id: number) {
  selectedMenuId.value = id
  const menu = menus.value.find((item) => item.id === id)
  items.value = menu ? cloneItems(menu.items) : []
  dirty.value = false
}

function addItem(parentIndex: number | string | null = null) {
  const parent = parentIndex === null ? null : Number(parentIndex)
  const item = { id: null, label: { en: 'New item', th: 'รายการใหม่', 'zh-CN': '新项目' }, url: '', page_id: null, target: '_self', is_visible: true, children: [] }
  if (parent === null) items.value.push(item)
  else items.value[parent].children.push(item)
  dirty.value = true
}

function removeItem(parentIndex: number | string | null, index: number | string) {
  const parent = parentIndex === null ? null : Number(parentIndex)
  const position = Number(index)
  if (parent === null) items.value.splice(position, 1)
  else items.value[parent].children.splice(position, 1)
  dirty.value = true
}

function move(parentIndex: number | string | null, index: number | string, direction: -1 | 1) {
  const parent = parentIndex === null ? null : Number(parentIndex)
  const position = Number(index)
  const list = parent === null ? items.value : items.value[parent].children
  const target = position + direction
  if (target < 0 || target >= list.length) return
  const [item] = list.splice(position, 1)
  list.splice(target, 0, item)
  dirty.value = true
}

function labelValue(item: any) {
  if (typeof item.label === 'string') item.label = { en: item.label, th: item.label, 'zh-CN': item.label }
  return item.label[locale.value] || ''
}

function setLabel(item: any, value: string) {
  if (typeof item.label === 'string') item.label = { en: item.label, th: item.label, 'zh-CN': item.label }
  item.label[locale.value] = value
  dirty.value = true
}

function applyPage(item: any, event: Event) {
  const pageId = Number((event.target as HTMLSelectElement).value)
  const page = pages.value.find((p) => p.id === pageId)
  if (page) {
    item.page_id = page.id
    item.url = `/${locale.value === 'zh-CN' ? 'zh-cn' : locale.value}/${page.slug}`
  }
  dirty.value = true
}

async function save() {
  if (!selectedMenu.value) return
  saving.value = true
  try {
    const strip = (list: any[]): any[] => list.map((item) => ({
      label: item.label,
      url: item.url || null,
      page_id: item.page_id || null,
      target: item.target || '_self',
      is_visible: item.is_visible,
      children: strip(item.children || []),
    }))
    await api.put(`/navigation/${selectedMenu.value.id}`, { name: selectedMenu.value.name, items: strip(items.value) })
    toast.success('Navigation saved')
    await load()
  } catch (err) {
    toast.error('Could not save navigation', apiErrorMessage(err))
  } finally {
    saving.value = false
  }
}

watch(locale, load)
onMounted(load)
</script>

<template>
  <div>
    <PageHead eyebrow="INFORMATION ARCHITECTURE" title="Navigation" description="Header and footer menus, per locale. URLs are stored explicitly so a translated page can have its own path.">
      <template #actions>
        <select v-model="locale" class="select-s" style="width:130px">
          <option v-for="item in locales" :key="item.code" :value="item.code">{{ item.label }}</option>
        </select>
        <button type="button" class="btn-s btn-s--primary" :disabled="saving || !dirty" @click="save"><Save :size="13" /> {{ saving ? 'Saving…' : dirty ? 'Save navigation' : 'Saved' }}</button>
      </template>
    </PageHead>

    <div v-if="loading" class="skeleton" style="height:420px" />
    <div v-else class="nav-layout">
      <aside class="panel">
        <div class="panel__head"><p class="panel__title">Menus</p></div>
        <div class="panel__body menu-list">
          <button v-for="menu in menus" :key="menu.id" type="button" class="menu-button" :class="{ 'is-active': menu.id === selectedMenuId }" @click="selectMenu(menu.id)">
            <Navigation :size="13" />
            <span>{{ menu.name }}</span>
            <span class="mono faint">{{ menu.key }}</span>
          </button>
          <p v-if="!menus.length" class="muted" style="font-size: 12px">No menus for this locale.</p>
        </div>
      </aside>

      <section class="panel">
        <div class="panel__head">
          <div><p class="panel__title">{{ selectedMenu?.name }}</p><p class="mono muted" style="margin-top:2px">{{ selectedMenu?.key }} · {{ locale }}</p></div>
          <button type="button" class="btn-s btn-s--sm" @click="addItem(null)"><Plus :size="12" /> Add item</button>
        </div>
        <div class="panel__body">
          <div v-if="!items.length" class="empty-s"><p class="empty-s__title">This menu is empty</p><p class="empty-s__body">Add a top-level item to begin.</p></div>
          <div v-for="(item, index) in items" :key="index" class="nav-editor-item">
            <div class="nav-editor-row">
              <span class="mono faint">{{ String(index + 1).padStart(2, '0') }}</span>
              <input :value="labelValue(item)" class="input-s" placeholder="Label" @input="setLabel(item, ($event.target as HTMLInputElement).value)">
              <input v-model="item.url" class="input-s" placeholder="/path or https://…" @input="dirty = true">
              <select class="select-s" style="width:120px" @change="applyPage(item, $event)">
                <option value="">Link to page…</option>
                <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.title }}</option>
              </select>
              <div class="nav-editor-actions">
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :disabled="index === 0" @click="move(null, index, -1)"><ChevronUp :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :disabled="index === items.length - 1" @click="move(null, index, 1)"><ChevronDown :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="addItem(index)"><Plus :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="removeItem(null, index)"><Trash2 :size="12" /></button>
              </div>
            </div>

            <div v-for="(child, childIndex) in item.children" :key="childIndex" class="nav-editor-row nav-editor-row--child">
              <span class="mono faint">↳</span>
              <input :value="labelValue(child)" class="input-s" placeholder="Label" @input="setLabel(child, ($event.target as HTMLInputElement).value)">
              <input v-model="child.url" class="input-s" placeholder="/path" @input="dirty = true">
              <select class="select-s" style="width:120px" @change="applyPage(child, $event)">
                <option value="">Link to page…</option>
                <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.title }}</option>
              </select>
              <div class="nav-editor-actions">
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :disabled="childIndex === 0" @click="move(index, childIndex, -1)"><ChevronUp :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" :disabled="childIndex === item.children.length - 1" @click="move(index, childIndex, 1)"><ChevronDown :size="12" /></button>
                <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" @click="removeItem(index, childIndex)"><Trash2 :size="12" /></button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.nav-layout { display: grid; grid-template-columns: 230px minmax(0, 1fr); gap: 14px; align-items: start; }
.menu-list { display: flex; flex-direction: column; gap: 2px; }
.menu-button { border: 0; background: transparent; border-radius: var(--s-radius); padding: 8px 9px; display: flex; align-items: center; gap: 8px; font-size: 12px; text-align: left; color: var(--s-muted); }
.menu-button:hover { background: var(--s-bg); }
.menu-button.is-active { background: var(--s-ink); color: #f5f3ee; }
.menu-button .mono { margin-left: auto; font-size: 7px; }
.nav-editor-item { border-bottom: 1px solid var(--s-line); padding-bottom: 9px; margin-bottom: 9px; }
.nav-editor-row { display: grid; grid-template-columns: 24px 1fr 1.1fr 120px auto; gap: 7px; align-items: center; }
.nav-editor-row--child { margin-top: 6px; padding-left: 16px; }
.nav-editor-actions { display: flex; gap: 1px; }
@media (max-width: 1100px) { .nav-layout { grid-template-columns: 1fr; } .nav-editor-row { grid-template-columns: 20px 1fr 1fr; } .nav-editor-row select, .nav-editor-actions { grid-column: 2 / -1; } }
</style>
