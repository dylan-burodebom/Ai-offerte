<script setup>
defineProps({
  status: { type: String, required: true },
})

const CONFIG = {
  concept:   { label: 'Concept',   classes: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600' },
  verzonden: { label: 'Verzonden', classes: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800' },
  gewonnen:  { label: 'Gewonnen',  classes: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 border border-green-200 dark:border-green-800' },
  verloren:  { label: 'Verloren',  classes: 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 border border-red-200 dark:border-red-800' },
}
</script>

<template>
  <span
    class="inline-flex items-center text-xs px-2 py-0.5 rounded-full font-medium capitalize"
    :class="CONFIG[status]?.classes ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
  >
    {{ CONFIG[status]?.label ?? status }}
  </span>
</template>
