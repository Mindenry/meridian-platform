<script setup lang="ts">
import { computed, ref } from 'vue'
import { Plus, Trash2, Image as ImageIcon, Link2 } from 'lucide-vue-next'
import RichTextEditor from '@/components/RichTextEditor.vue'
import MediaPicker from '@/components/MediaPicker.vue'
import type { BlockField, MediaAsset } from '@/types'

const props = defineProps<{ field: BlockField; modelValue: any }>()
const emit = defineEmits<{ 'update:modelValue': [value: any] }>()

const pickerOpen = ref(false)

const value = computed({
  get: () => props.modelValue,
  set: (next) => emit('update:modelValue', next),
})

function updateRepeaterItem(index: number, next: any) {
  const list = Array.isArray(props.modelValue) ? [...props.modelValue] : []
  list[index] = next
  emit('update:modelValue', list)
}

function addRepeaterItem() {
  const list = Array.isArray(props.modelValue) ? [...props.modelValue] : []
  const item: Record<string, any> = {}
  for (const key of props.field.fields || []) {
    item[key.key] = key.type === 'repeater' ? [] : key.type === 'toggle' ? false : key.default ?? ''
  }
  list.push(item)
  emit('update:modelValue', list)
}

function removeRepeaterItem(index: number) {
  const list = Array.isArray(props.modelValue) ? [...props.modelValue] : []
  list.splice(index, 1)
  emit('update:modelValue', list)
}

function selectImage(asset: MediaAsset) {
  pickerOpen.value = false
  emit('update:modelValue', {
    media_id: asset.id,
    url: asset.url,
    alt: asset.alt?.en || asset.original_name,
  })
}

function updateLink(key: string, next: any) {
  emit('update:modelValue', { ...(props.modelValue || {}), [key]: next })
}
</script>

<template>
  <div class="field-editor">
    <template v-if="field.type === 'text' || field.type === 'url'">
      <input v-model="value" class="input-s" :placeholder="field.label">
    </template>

    <template v-else-if="field.type === 'textarea'">
      <textarea v-model="value" class="textarea-s" :rows="field.rows || 3" />
    </template>

    <template v-else-if="field.type === 'richtext'">
      <RichTextEditor v-model="value" :min-height="'180px'" />
    </template>

    <template v-else-if="field.type === 'number'">
      <input v-model.number="value" type="number" class="input-s" :min="field.min" :max="field.max">
    </template>

    <template v-else-if="field.type === 'toggle'">
      <label class="switch-row">
        <input v-model="value" type="checkbox">
        <span>{{ value ? 'Enabled' : 'Disabled' }}</span>
      </label>
    </template>

    <template v-else-if="field.type === 'select'">
      <select v-model="value" class="select-s">
        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
      </select>
    </template>

    <template v-else-if="field.type === 'link'">
      <div class="link-editor">
        <div class="field-s">
          <label class="field-s__label">Label</label>
          <input :value="modelValue?.label" class="input-s" @input="updateLink('label', ($event.target as HTMLInputElement).value)">
        </div>
        <div class="field-s">
          <label class="field-s__label">URL</label>
          <input :value="modelValue?.url" class="input-s" placeholder="/contact" @input="updateLink('url', ($event.target as HTMLInputElement).value)">
        </div>
        <div class="field-s">
          <label class="field-s__label">Style</label>
          <select :value="modelValue?.style || 'primary'" class="select-s" @change="updateLink('style', ($event.target as HTMLSelectElement).value)">
            <option value="primary">Primary</option>
            <option value="secondary">Secondary</option>
            <option value="text">Text link</option>
          </select>
        </div>
      </div>
    </template>

    <template v-else-if="field.type === 'image'">
      <button type="button" class="image-field" @click="pickerOpen = true">
        <img v-if="modelValue?.url" :src="modelValue.url" :alt="modelValue.alt || ''">
        <span v-else class="image-field__empty"><ImageIcon :size="16" /> Choose image</span>
        <span class="image-field__edit"><Link2 :size="12" /> Change</span>
      </button>
      <div v-if="modelValue?.url" class="field-s" style="margin-top:8px">
        <label class="field-s__label">Alt text</label>
        <input :value="modelValue?.alt" class="input-s" @input="emit('update:modelValue', { ...modelValue, alt: ($event.target as HTMLInputElement).value })">
      </div>
      <MediaPicker :open="pickerOpen" :title="`Choose ${field.label}`" @close="pickerOpen = false" @select="selectImage" />
    </template>

    <template v-else-if="field.type === 'repeater'">
      <div class="repeater">
        <div v-for="(item, index) in (Array.isArray(modelValue) ? modelValue : [])" :key="index" class="repeater-item">
          <div class="repeater-item__head">
            <span class="mono">ITEM {{ String(index + 1).padStart(2, '0') }}</span>
            <button type="button" class="btn-s btn-s--ghost btn-s--icon btn-s--sm" aria-label="Remove item" @click="removeRepeaterItem(index)">
              <Trash2 :size="12" />
            </button>
          </div>
          <div class="repeater-item__fields">
            <div v-for="subField in field.fields" :key="subField.key" class="field-s">
              <label class="field-s__label">{{ subField.label }}</label>
              <FieldEditor
                :field="subField"
                :model-value="item?.[subField.key]"
                @update:model-value="(next) => updateRepeaterItem(index, { ...item, [subField.key]: next })"
              />
            </div>
          </div>
        </div>
        <button type="button" class="btn-s btn-s--sm" @click="addRepeaterItem"><Plus :size="12" /> Add item</button>
      </div>
    </template>

    <template v-else>
      <textarea :value="JSON.stringify(modelValue, null, 2)" class="textarea-s" rows="4" @change="emit('update:modelValue', JSON.parse(($event.target as HTMLTextAreaElement).value))" />
    </template>
  </div>
</template>

<style scoped>
.field-editor :deep(.editor-shell) {
  border-color: var(--s-line);
}

.switch-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--s-muted);
}

.switch-row input {
  accent-color: var(--s-accent);
}

.image-field {
  width: 100%;
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  background: var(--s-white);
  padding: 0;
  overflow: hidden;
  position: relative;
  min-height: 100px;
}

.image-field img {
  width: 100%;
  aspect-ratio: 16 / 9;
  object-fit: cover;
  display: block;
}

.image-field__empty {
  aspect-ratio: 16 / 9;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: var(--s-faint);
  font-size: 12px;
}

.image-field__edit {
  position: absolute;
  right: 6px;
  bottom: 6px;
  background: rgba(15, 27, 41, 0.85);
  color: #f5f3ee;
  font-size: 8px;
  padding: 3px 5px;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.link-editor {
  display: flex;
  flex-direction: column;
  gap: 8px;
  border: 1px solid var(--s-line);
  padding: 8px;
  border-radius: var(--s-radius);
}

.repeater {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.repeater-item {
  border: 1px solid var(--s-line);
  border-radius: var(--s-radius);
  background: var(--s-white);
  overflow: hidden;
}

.repeater-item__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 5px 7px;
  border-bottom: 1px solid var(--s-line);
  background: var(--s-bg);
  color: var(--s-faint);
}

.repeater-item__fields {
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
</style>
