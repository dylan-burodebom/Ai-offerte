<script setup>
defineProps({
  status: { type: String, required: true },
})

const CONFIG = {
  concept:   { label: 'Concept',   classes: 'bg-gray-100 text-gray-600' },
  verzonden: { label: 'Verzonden', classes: 'bg-blue-100 text-blue-700' },
  gewonnen:  { label: 'Gewonnen',  classes: 'bg-green-100 text-green-700' },
  verloren:  { label: 'Verloren',  classes: 'bg-red-100 text-red-600' },
}
</script>

<template>
  <span
    class="inline-flex items-center text-xs px-2 py-0.5 rounded-full font-medium capitalize"
    :class="CONFIG[status]?.classes ?? 'bg-gray-100 text-gray-600'"
  >
    {{ CONFIG[status]?.label ?? status }}
  </span>
</template>
