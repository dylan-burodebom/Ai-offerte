<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import { watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Schrijf hier…' },
})
const emit = defineEmits(['update:modelValue', 'blur'])

const editor = useEditor({
  content: props.modelValue,
  extensions: [
    StarterKit,
    Link.configure({ openOnClick: false }),
    Placeholder.configure({ placeholder: props.placeholder }),
  ],
  editorProps: {
    attributes: { class: 'prose prose-sm max-w-none focus:outline-none min-h-[120px] px-4 py-3' },
  },
  onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
  onBlur: () => emit('blur'),
})

watch(() => props.modelValue, (val) => {
  if (editor.value && editor.value.getHTML() !== val) {
    editor.value.commands.setContent(val ?? '', false)
  }
})

onBeforeUnmount(() => editor.value?.destroy())

function setLink() {
  const url = window.prompt('URL', editor.value?.getAttributes('link').href ?? '')
  if (url === null) return
  if (url === '') {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run()
  } else {
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }
}
</script>

<template>
  <div class="border border-gray-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-0.5 border-b border-gray-200 bg-gray-50 px-2 py-1.5">
      <button
        v-for="{ action, label, active, title } in [
          { action: () => editor?.chain().focus().toggleBold().run(), label: 'B', active: editor?.isActive('bold'), title: 'Vet' },
          { action: () => editor?.chain().focus().toggleItalic().run(), label: 'I', active: editor?.isActive('italic'), title: 'Cursief' },
        ]"
        :key="label"
        type="button"
        :title="title"
        class="px-2 py-0.5 rounded text-sm font-medium transition-colors"
        :class="active ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-200'"
        @click="action"
      >{{ label }}</button>

      <div class="w-px h-4 bg-gray-300 mx-1" />

      <button
        v-for="level in [2, 3]"
        :key="level"
        type="button"
        :title="'Koptekst ' + level"
        class="px-2 py-0.5 rounded text-xs font-semibold transition-colors"
        :class="editor?.isActive('heading', { level }) ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-200'"
        @click="editor?.chain().focus().toggleHeading({ level }).run()"
      >H{{ level }}</button>

      <div class="w-px h-4 bg-gray-300 mx-1" />

      <button
        type="button"
        title="Opsomming"
        class="px-2 py-0.5 rounded text-sm transition-colors"
        :class="editor?.isActive('bulletList') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-200'"
        @click="editor?.chain().focus().toggleBulletList().run()"
      >≡</button>

      <button
        type="button"
        title="Genummerde lijst"
        class="px-2 py-0.5 rounded text-sm transition-colors"
        :class="editor?.isActive('orderedList') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-200'"
        @click="editor?.chain().focus().toggleOrderedList().run()"
      >1.</button>

      <button
        type="button"
        title="Link"
        class="px-2 py-0.5 rounded text-sm transition-colors"
        :class="editor?.isActive('link') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-200'"
        @click="setLink"
      >🔗</button>

      <div class="w-px h-4 bg-gray-300 mx-1" />

      <button
        type="button"
        title="Ongedaan maken"
        class="px-2 py-0.5 rounded text-sm text-gray-600 hover:bg-gray-200 disabled:opacity-30 transition-colors"
        :disabled="!editor?.can().undo()"
        @click="editor?.chain().focus().undo().run()"
      >↩</button>
      <button
        type="button"
        title="Opnieuw"
        class="px-2 py-0.5 rounded text-sm text-gray-600 hover:bg-gray-200 disabled:opacity-30 transition-colors"
        :disabled="!editor?.can().redo()"
        @click="editor?.chain().focus().redo().run()"
      >↪</button>
    </div>

    <!-- Editor content -->
    <EditorContent :editor="editor" />
  </div>
</template>

<style>
/* TipTap placeholder */
.tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  color: #9ca3af;
  float: left;
  height: 0;
  pointer-events: none;
}
/* Basic prose styling for the editor */
.tiptap ul { list-style-type: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
.tiptap ol { list-style-type: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
.tiptap li { margin: 0.25rem 0; }
.tiptap p  { margin: 0.4rem 0; }
.tiptap h2 { font-size: 1.1rem; font-weight: 700; margin: 0.75rem 0 0.25rem; }
.tiptap h3 { font-size: 1rem; font-weight: 600; margin: 0.5rem 0 0.25rem; }
.tiptap a  { color: #4f46e5; text-decoration: underline; }
</style>
