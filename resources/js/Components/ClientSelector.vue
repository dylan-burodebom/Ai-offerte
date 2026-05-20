<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: [Number, null],
  selectedClient: Object,
})

const emit = defineEmits(['update:modelValue', 'select'])

const query    = ref(props.selectedClient?.naam ?? '')
const results  = ref([])
const isOpen   = ref(false)
const loading  = ref(false)
let debounceTimer = null

async function fetchClients(q = '') {
  loading.value = true
  try {
    const res = await fetch(route('clients.search') + '?q=' + encodeURIComponent(q))
    results.value = await res.json()
  } finally {
    loading.value = false
  }
}

async function onFocus() {
  if (results.value.length === 0) await fetchClients('')
  isOpen.value = true
}

watch(query, (val) => {
  clearTimeout(debounceTimer)
  if (!val) {
    fetchClients('').then(() => { isOpen.value = true })
    return
  }
  debounceTimer = setTimeout(() => {
    fetchClients(val).then(() => { isOpen.value = results.value.length > 0 })
  }, 250)
})

function select(client) {
  query.value  = client.naam
  isOpen.value = false
  emit('update:modelValue', client.id)
  emit('select', client)
}

function clear() {
  query.value   = ''
  isOpen.value  = false
  results.value = []
  emit('update:modelValue', null)
  emit('select', null)
}

function onBlur() {
  setTimeout(() => { isOpen.value = false }, 150)
}

const filtered = ref([])
watch([query, results], () => {
  if (!query.value) {
    filtered.value = results.value
  } else {
    const q = query.value.toLowerCase()
    filtered.value = results.value.filter(
      (c) =>
        c.naam.toLowerCase().includes(q) ||
        c.contactpersoon?.toLowerCase().includes(q) ||
        c.email?.toLowerCase().includes(q),
    )
  }
})
</script>

<template>
  <div class="relative">
    <div class="flex items-center gap-2">
      <div class="relative flex-1">
        <input
          v-model="query"
          type="text"
          placeholder="Zoek of kies een klant…"
          autocomplete="off"
          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 pr-8"
          @focus="onFocus"
          @blur="onBlur"
        />
        <button
          type="button"
          tabindex="-1"
          class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
          @mousedown.prevent="isOpen ? (isOpen = false) : onFocus()"
        >
          <svg class="w-4 h-4 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
      </div>
      <button v-if="modelValue" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg leading-none" @click="clear">×</button>
    </div>

    <!-- Dropdown -->
    <ul
      v-if="isOpen"
      class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-64 overflow-auto text-sm"
    >
      <li v-if="loading" class="px-3 py-2 text-gray-400 dark:text-gray-500 italic">Laden…</li>

      <li v-else-if="filtered.length === 0" class="px-3 py-2 text-gray-400 dark:text-gray-500 italic">
        Geen klanten gevonden.
      </li>

      <li
        v-for="client in filtered"
        :key="client.id"
        class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20"
        :class="modelValue === client.id ? 'bg-blue-50 dark:bg-blue-900/20' : ''"
        @mousedown.prevent="select(client)"
      >
        <div>
          <span class="font-medium text-gray-900 dark:text-white">{{ client.naam }}</span>
          <span v-if="client.contactpersoon" class="text-gray-500 dark:text-gray-400 ml-1.5 text-xs">{{ client.contactpersoon }}</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
          <span v-if="client.sector" class="text-xs px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">{{ client.sector }}</span>
          <svg v-if="modelValue === client.id" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
      </li>
    </ul>
  </div>
</template>
