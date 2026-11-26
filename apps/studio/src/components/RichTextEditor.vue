<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Placeholder from '@tiptap/extension-placeholder'
import { Table, TableRow, TableHeader, TableCell } from '@tiptap/extension-table'
import {
  Bold, Italic, Heading2, Heading3, List, ListOrdered, Quote, Link2, Image as ImageIcon,
  Code2, Undo2, Redo2, Table2,
} from 'lucide-vue-next'

const props = withDefaults(defineProps<{
  modelValue: any
  placeholder?: string
  minHeight?: string
  format?: 'html' | 'json'
}>(), {
  placeholder: 'Write the content…',
  minHeight: '320px',
  format: 'html',
})
const emit = defineEmits<{ 'update:modelValue': [value: any] }>()

const syncing = ref(false)

const editor = useEditor({
  content: props.format === 'json' ? (props.modelValue || { type: 'doc', content: [] }) : (props.modelValue || ''),
  extensions: [
    StarterKit.configure({ heading: { levels: [2, 3, 4] } }),
    Link.configure({ openOnClick: false, autolink: true, HTMLAttributes: { rel: 'noopener noreferrer' } }),
    Image,
    Placeholder.configure({ placeholder: props.placeholder }),
    Table.configure({ resizable: false }),
    TableRow,
    TableHeader,
    TableCell,
  ],
  editorProps: {
    attributes: { class: 'tiptap', style: `min-height:${props.minHeight}` },
  },
  onUpdate: ({ editor: instance }) => {
    if (syncing.value) return
    emit('update:modelValue', props.format === 'json' ? instance.getJSON() : instance.getHTML())
  },
})

watch(() => props.modelValue, (value) => {
  if (!editor.value) return
  const current = props.format === 'json' ? JSON.stringify(editor.value.getJSON()) : editor.value.getHTML()
  const next = props.format === 'json' ? JSON.stringify(value || { type: 'doc', content: [] }) : (value || '')
  if (current === next) return
  syncing.value = true
  editor.value.commands.setContent(props.format === 'json' ? (value || { type: 'doc', content: [] }) : (value || ''), { emitUpdate: false })
  syncing.value = false
}, { deep: true })

function setLink() {
  const previous = editor.value?.getAttributes('link').href || ''
  const url = window.prompt('Link URL', previous)
  if (url === null) return
  if (url === '') editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
  else editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

function addImage() {
  const url = window.prompt('Image URL')
  if (url) editor.value?.chain().focus().setImage({ src: url }).run()
}

onBeforeUnmount(() => editor.value?.destroy())
</script>

<template>
  <div class="editor-shell">
    <div v-if="editor" class="editor-toolbar" role="toolbar" aria-label="Formatting">
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('bold') }" title="Bold" @click="editor.chain().focus().toggleBold().run()"><Bold :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('italic') }" title="Italic" @click="editor.chain().focus().toggleItalic().run()"><Italic :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" title="Heading 2" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"><Heading2 :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" title="Heading 3" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"><Heading3 :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('bulletList') }" title="Bullet list" @click="editor.chain().focus().toggleBulletList().run()"><List :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('orderedList') }" title="Numbered list" @click="editor.chain().focus().toggleOrderedList().run()"><ListOrdered :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('blockquote') }" title="Quote" @click="editor.chain().focus().toggleBlockquote().run()"><Quote :size="14" /></button>
      <button type="button" class="editor-toolbar__button" :class="{ 'is-active': editor.isActive('codeBlock') }" title="Code block" @click="editor.chain().focus().toggleCodeBlock().run()"><Code2 :size="14" /></button>
      <button type="button" class="editor-toolbar__button" title="Link" @click="setLink"><Link2 :size="14" /></button>
      <button type="button" class="editor-toolbar__button" title="Image" @click="addImage"><ImageIcon :size="14" /></button>
      <button type="button" class="editor-toolbar__button" title="Insert table" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()"><Table2 :size="14" /></button>
      <span style="width:1px;background:var(--s-line);margin:0 4px" />
      <button type="button" class="editor-toolbar__button" title="Undo" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()"><Undo2 :size="14" /></button>
      <button type="button" class="editor-toolbar__button" title="Redo" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()"><Redo2 :size="14" /></button>
    </div>
    <EditorContent :editor="editor" />
  </div>
</template>
