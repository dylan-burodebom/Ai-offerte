<script setup>
import { ref, computed, watch } from 'vue'
import draggable from 'vuedraggable'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ rows: [], btw: '21%' }),
  },
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

let nextId = 1

const rows = ref(
  props.modelValue.rows.length
    ? props.modelValue.rows.map((r) => ({ ...r, _id: nextId++ }))
    : [{ _id: nextId++, omschrijving: '', bedrag: '' }]
)
const btw = ref(props.modelValue.btw ?? '21%')

const BTW_OPTIES = ['21%', '0%', 'vrijgesteld']

watch([rows, btw], () => {
  emit('update:modelValue', {
    rows: rows.value.map(({ omschrijving, bedrag }) => ({ omschrijving, bedrag: parseFloat(bedrag) || 0 })),
    btw: btw.value,
  })
}, { deep: true })

function addRow() {
  rows.value.push({ _id: nextId++, omschrijving: '', bedrag: '' })
}

function removeRow(index) {
  if (rows.value.length > 1) rows.value.splice(index, 1)
}

const fmt = (v) =>
  new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(v || 0)

function parseBedrag(str) {
  if (typeof str === 'number') return str
  const cleaned = String(str).replace(/[^\d,.-]/g, '').replace(',', '.')
  return parseFloat(cleaned) || 0
}

const subtotaal = computed(() =>
  rows.value.reduce((sum, r) => sum + parseBedrag(r.bedrag), 0)
)

const btwFactor = computed(() => (btw.value === '21%' ? 0.21 : 0))
const btwBedrag = computed(() => subtotaal.value * btwFactor.value)
const eindtotaal = computed(() => subtotaal.value + btwBedrag.value)

const canProceed = computed(() =>
  rows.value.length > 0 &&
  rows.value.every((r) => r.omschrijving.trim() && parseBedrag(r.bedrag) > 0)
)

defineExpose({ canProceed })
</script>

<template>
  <div class="space-y-5">

    <!-- Regels -->
    <div>
      <div class="grid grid-cols-[auto_1fr_auto_auto] gap-2 mb-2 px-1">
        <span class="text-xs text-gray-400 dark:text-gray-500 w-5" />
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Omschrijving</span>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-32 text-right">Bedrag (€)</span>
        <span class="w-7" />
      </div>

      <draggable
        v-model="rows"
        item-key="_id"
        handle=".drag-handle"
        ghost-class="opacity-40"
        class="space-y-2"
      >
        <template #item="{ element: row, index }">
          <div class="grid grid-cols-[auto_1fr_auto_auto] gap-2 items-center bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 px-3 py-2">
            <span class="drag-handle cursor-grab text-gray-300 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-300 select-none text-lg leading-none">⠿</span>

            <input
              v-model="row.omschrijving"
              type="text"
              placeholder="bijv. Website ontwikkeling"
              class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400 text-sm shadow-sm focus:ring-blue-500 focus:border-blue-500"
            />

            <input
              v-model="row.bedrag"
              type="text"
              inputmode="decimal"
              placeholder="1.500,00"
              class="w-32 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white dark:placeholder-gray-400 text-sm shadow-sm text-right focus:ring-blue-500 focus:border-blue-500"
            />

            <button
              type="button"
              class="w-7 h-7 flex items-center justify-center rounded text-gray-300 dark:text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
              :disabled="rows.length === 1"
              :class="rows.length === 1 ? 'opacity-30 cursor-not-allowed' : ''"
              @click="removeRow(index)"
            >
              ×
            </button>
          </div>
        </template>
      </draggable>

      <InputError :message="errors.rows" class="mt-1" />

      <button
        type="button"
        class="mt-3 w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 py-2.5 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400 transition-colors"
        @click="addRow"
      >
        + Regel toevoegen
      </button>
    </div>

    <!-- BTW toggle -->
    <div>
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-2">BTW</label>
      <div class="flex gap-2">
        <button
          v-for="optie in BTW_OPTIES"
          :key="optie"
          type="button"
          class="px-4 py-1.5 rounded-full text-sm border transition-colors"
          :class="btw === optie
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'"
          @click="btw = optie"
        >
          {{ optie }}
        </button>
      </div>
    </div>

    <!-- Totalen -->
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
      <div class="flex justify-between px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
        <span>Subtotaal</span>
        <span class="font-mono">{{ fmt(subtotaal) }}</span>
      </div>

      <div v-if="btw === '21%'" class="flex justify-between px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">
        <span>BTW (21%)</span>
        <span class="font-mono">{{ fmt(btwBedrag) }}</span>
      </div>
      <div v-else class="flex justify-between px-4 py-2.5 text-sm text-gray-400 dark:text-gray-500 italic">
        <span>BTW</span>
        <span>{{ btw === '0%' ? '0% (verlegd)' : 'Vrijgesteld' }}</span>
      </div>

      <div class="flex justify-between px-4 py-3 font-semibold text-gray-900 dark:text-white">
        <span>Totaal incl. BTW</span>
        <span class="font-mono text-blue-700 dark:text-blue-400">{{ fmt(eindtotaal) }}</span>
      </div>
    </div>

    <p v-if="!canProceed" class="text-xs text-amber-600 dark:text-amber-400">
      Vul minimaal één regel in met een omschrijving en bedrag groter dan 0.
    </p>
  </div>
</template>
