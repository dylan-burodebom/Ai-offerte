<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

const props = defineProps({
  quotes:    { type: Object, required: true },
  filters:   { type: Object, default: () => ({}) },
  statussen: { type: Array,  default: () => [] },
  sectoren:  { type: Array,  default: () => [] },
})

const search    = ref(props.filters.search     ?? '')
const status    = ref(props.filters.status     ?? '')
const sector    = ref(props.filters.sector     ?? '')
const datumVan  = ref(props.filters.datum_van  ?? '')
const datumTot  = ref(props.filters.datum_tot  ?? '')
const sort      = ref(props.filters.sort       ?? 'created_at')
const direction = ref(props.filters.direction  ?? 'desc')

let filterTimer = null
function applyFilters() {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    router.get(route('quotes.index'), {
      search:     search.value    || undefined,
      status:     status.value    || undefined,
      sector:     sector.value    || undefined,
      datum_van:  datumVan.value  || undefined,
      datum_tot:  datumTot.value  || undefined,
      sort:       sort.value,
      direction:  direction.value,
    }, { preserveState: true, replace: true })
  }, 300)
}

watch([search, status, sector, datumVan, datumTot], applyFilters)

function setSort(kolom) {
  if (sort.value === kolom) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value = kolom
    direction.value = 'desc'
  }
  applyFilters()
}

function sortState(kolom) {
  if (sort.value !== kolom) return 'none'
  return direction.value === 'asc' ? 'asc' : 'desc'
}

function resetFilters() {
  search.value = ''
  status.value = ''
  sector.value = ''
  datumVan.value = ''
  datumTot.value = ''
  sort.value = 'created_at'
  direction.value = 'desc'
  applyFilters()
}

const hasFilters = computed(() =>
  search.value || status.value || sector.value || datumVan.value || datumTot.value
)

const selected   = ref(new Set())
const bulkStatus = ref('')
const bulkBezig  = ref(false)

const alleGeselecteerd = computed(() =>
  props.quotes.data.length > 0 &&
  props.quotes.data.every(q => selected.value.has(q.id))
)

function toggleAlles() {
  if (alleGeselecteerd.value) {
    props.quotes.data.forEach(q => selected.value.delete(q.id))
  } else {
    props.quotes.data.forEach(q => selected.value.add(q.id))
  }
}

function toggleRij(id) {
  if (selected.value.has(id)) selected.value.delete(id)
  else selected.value.add(id)
}

watch(() => props.quotes.data, () => selected.value.clear())

async function doBulkActie(actie) {
  if (selected.value.size === 0) return
  if (actie === 'verwijderen' && !confirm(`${selected.value.size} offerte(s) verwijderen?`)) return
  if (actie === 'status' && !bulkStatus.value) return

  bulkBezig.value = true
  try {
    await window.axios.post(route('quotes.bulk'), {
      ids:    [...selected.value],
      actie,
      status: actie === 'status' ? bulkStatus.value : undefined,
    })
    selected.value.clear()
    bulkStatus.value = ''
    router.reload({ only: ['quotes'] })
  } finally {
    bulkBezig.value = false
  }
}

const actiemenu      = ref(null)
const actiemenuQuote = ref(null)
const actiemenuPos   = ref({ top: 0, right: 0 })

function toggleActiemenu(quote, event) {
  if (actiemenu.value === quote.id) {
    actiemenu.value = null
    actiemenuQuote.value = null
    return
  }
  actiemenu.value = quote.id
  actiemenuQuote.value = quote
  const rect = event.currentTarget.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  actiemenuPos.value = spaceBelow >= 220
    ? { top: rect.bottom + 4 + 'px', bottom: 'auto', right: window.innerWidth - rect.right + 'px' }
    : { top: 'auto', bottom: window.innerHeight - rect.top + 4 + 'px', right: window.innerWidth - rect.right + 'px' }
}

async function dupliceer(quote) {
  actiemenu.value = null
  try {
    const res = await window.axios.post(route('quotes.version', quote.id))
    router.visit(route('quotes.edit', res.data.id))
  } catch {
    alert('Dupliceren mislukt.')
  }
}

async function verwijder(quote) {
  actiemenu.value = null
  if (!confirm(`Offerte "${quote.offerte_nummer}" verwijderen?`)) return
  try {
    await window.axios.delete(route('quotes.destroy', quote.id))
    router.reload({ only: ['quotes'] })
  } catch {
    alert('Verwijderen mislukt.')
  }
}

const STATUS_LABELS = { concept: 'Concept', verzonden: 'Verzonden', gewonnen: 'Gewonnen', verloren: 'Verloren' }

const fmt = (v) => v
  ? new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v)
  : '—'

function fmtDatum(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}
</script>

<template>
  <Head title="Offertes" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Offertes</h2>
        <div class="flex items-center gap-2">
          <a
            :href="route('quotes.export', { status: filters.status, sector: filters.sector })"
            class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span class="hidden sm:inline">CSV export</span>
          </a>
          <a
            :href="route('quotes.create')"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nieuwe offerte
          </a>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="actiemenu !== null" class="fixed inset-0 z-40" @click="actiemenu = null" />
      <div
        v-if="actiemenu !== null && actiemenuQuote"
        class="fixed z-50 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 w-48"
        :style="actiemenuPos"
      >
        <a
          :href="route('quotes.pdf.preview', actiemenuQuote.id)"
          target="_blank"
          class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
          @click="actiemenu = null"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          Bekijken
        </a>
        <button
          type="button"
          class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
          @click="router.visit(route('quotes.edit', actiemenuQuote.id)); actiemenu = null"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
          Bewerken
        </button>
        <a
          :href="route('quotes.pdf', actiemenuQuote.id)"
          class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
          @click="actiemenu = null"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          PDF downloaden
        </a>
        <button
          type="button"
          class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
          @click="dupliceer(actiemenuQuote)"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          Dupliceren (V{{ (actiemenuQuote.versie ?? 1) + 1 }})
        </button>
        <div class="border-t border-gray-100 dark:border-gray-700 my-1" />
        <button
          type="button"
          class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
          @click="verwijder(actiemenuQuote)"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0H7m2-3h6a1 1 0 011 1H8a1 1 0 011-1z"/></svg>
          Verwijderen
        </button>
      </div>
    </Teleport>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 space-y-4">

        <!-- Filters -->
        <div class="flex gap-3 flex-wrap">
          <div class="relative flex-1 min-w-40 sm:min-w-48">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
              v-model="search"
              type="text"
              placeholder="Zoek nummer, titel, klant…"
              class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white dark:placeholder-gray-400"
            />
          </div>
          <div class="relative">
            <select v-model="status" class="appearance-none pl-3 pr-8 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white text-gray-600 dark:text-gray-300">
              <option value="">Alle statussen</option>
              <option v-for="s in statussen" :key="s" :value="s">{{ STATUS_LABELS[s] ?? s }}</option>
            </select>
            <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          </div>
          <div class="relative">
            <select v-model="sector" class="appearance-none pl-3 pr-8 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white text-gray-600 dark:text-gray-300">
              <option value="">Alle sectoren</option>
              <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
            </select>
            <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          </div>
          <input
            v-model="datumVan"
            type="date"
            title="Aangemaakt vanaf"
            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white dark:text-gray-300"
          />
          <input
            v-model="datumTot"
            type="date"
            title="Aangemaakt tot"
            class="px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white dark:text-gray-300"
          />
          <button
            v-if="hasFilters"
            type="button"
            title="Filters wissen"
            class="px-3 py-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm transition-colors"
            @click="resetFilters"
          >✕</button>
        </div>

        <!-- Bulk actiebalk -->
        <Transition
          enter-active-class="transition-all duration-150"
          enter-from-class="opacity-0 -translate-y-2"
          leave-active-class="transition-all duration-150"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="selected.size > 0"
            class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl px-4 py-3 flex items-center gap-4"
          >
            <span class="text-sm font-medium text-blue-700 dark:text-blue-400">{{ selected.size }} geselecteerd</span>
            <div class="flex items-center gap-2 flex-1">
              <select
                v-model="bulkStatus"
                class="rounded-md border-blue-300 dark:border-blue-700 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500 py-1"
              >
                <option value="">Status wijzigen naar…</option>
                <option v-for="s in statussen" :key="s" :value="s">{{ STATUS_LABELS[s] ?? s }}</option>
              </select>
              <button
                type="button"
                :disabled="!bulkStatus || bulkBezig"
                class="px-3 py-1 rounded bg-blue-600 text-white text-sm disabled:opacity-40 hover:bg-blue-700 transition-colors"
                @click="doBulkActie('status')"
              >Toepassen</button>
            </div>
            <button
              type="button"
              :disabled="bulkBezig"
              class="px-3 py-1 rounded border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 text-sm hover:bg-red-50 dark:hover:bg-red-900/20 disabled:opacity-40 transition-colors"
              @click="doBulkActie('verwijderen')"
            >Verwijderen</button>
          </div>
        </Transition>

        <!-- Tabel -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50/60 dark:bg-gray-700/30 border-b border-gray-100 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3.5 w-10">
                  <input
                    type="checkbox"
                    :checked="alleGeselecteerd"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                    @change="toggleAlles"
                  />
                </th>
                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none whitespace-nowrap" @click="setSort('offerte_nummer')">
                  <span class="inline-flex items-center gap-1">Nummer
                    <svg class="w-3 h-3" :class="sortState('offerte_nummer') !== 'none' ? 'text-blue-500' : 'text-gray-300 dark:text-gray-600'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path v-if="sortState('offerte_nummer') === 'asc'" stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                      <path v-else-if="sortState('offerte_nummer') === 'desc'" stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                    </svg>
                  </span>
                </th>
                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 hidden sm:table-cell">Klant</th>
                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 hidden md:table-cell">Titel</th>
                <th class="px-4 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none" @click="setSort('status')">
                  <span class="inline-flex items-center gap-1">Status
                    <svg class="w-3 h-3" :class="sortState('status') !== 'none' ? 'text-blue-500' : 'text-gray-300 dark:text-gray-600'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path v-if="sortState('status') === 'asc'" stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                      <path v-else-if="sortState('status') === 'desc'" stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                    </svg>
                  </span>
                </th>
                <th class="px-4 py-3.5 text-right text-xs font-medium text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none whitespace-nowrap hidden sm:table-cell" @click="setSort('totaal')">
                  <span class="inline-flex items-center justify-end gap-1">Bedrag
                    <svg class="w-3 h-3" :class="sortState('totaal') !== 'none' ? 'text-blue-500' : 'text-gray-300 dark:text-gray-600'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path v-if="sortState('totaal') === 'asc'" stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                      <path v-else-if="sortState('totaal') === 'desc'" stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                    </svg>
                  </span>
                </th>
                <th class="px-4 py-3.5 text-right text-xs font-medium text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none whitespace-nowrap hidden lg:table-cell" @click="setSort('geldig_tot')">
                  <span class="inline-flex items-center justify-end gap-1">Geldig tot
                    <svg class="w-3 h-3" :class="sortState('geldig_tot') !== 'none' ? 'text-blue-500' : 'text-gray-300 dark:text-gray-600'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path v-if="sortState('geldig_tot') === 'asc'" stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                      <path v-else-if="sortState('geldig_tot') === 'desc'" stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                    </svg>
                  </span>
                </th>
                <th class="px-4 py-3.5 text-right text-xs font-medium text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 select-none whitespace-nowrap hidden lg:table-cell" @click="setSort('created_at')">
                  <span class="inline-flex items-center justify-end gap-1">Aangemaakt
                    <svg class="w-3 h-3" :class="sortState('created_at') !== 'none' ? 'text-blue-500' : 'text-gray-300 dark:text-gray-600'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path v-if="sortState('created_at') === 'asc'" stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                      <path v-else-if="sortState('created_at') === 'desc'" stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" d="M8 9l4-4 4 4M8 15l4 4 4-4"/>
                    </svg>
                  </span>
                </th>
                <th class="px-4 py-3.5 w-10" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
              <tr v-if="quotes.data.length === 0">
                <td colspan="9" class="px-4 py-14 text-center text-gray-400 dark:text-gray-500 whitespace-nowrap">
                  Geen offertes gevonden.
                  <a :href="route('quotes.create')" class="text-blue-600 dark:text-blue-400 hover:underline ml-1">Maak de eerste aan.</a>
                </td>
              </tr>
              <tr
                v-for="q in quotes.data"
                :key="q.id"
                class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors"
                :class="selected.has(q.id) ? 'bg-blue-50/40 dark:bg-blue-900/10' : ''"
              >
                <td class="px-4 py-4" @click.stop>
                  <input
                    type="checkbox"
                    :checked="selected.has(q.id)"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                    @change="toggleRij(q.id)"
                  />
                </td>

                <td class="px-4 py-4 font-mono text-xs text-blue-600 dark:text-blue-400 font-semibold whitespace-nowrap cursor-pointer" @click="router.visit(route('quotes.edit', q.id))">
                  {{ q.offerte_nummer }}
                </td>

                <td class="px-4 py-4 font-medium text-gray-800 dark:text-gray-200 whitespace-nowrap cursor-pointer hidden sm:table-cell" @click="router.visit(route('quotes.edit', q.id))">
                  {{ q.client?.naam ?? '—' }}
                </td>

                <td class="px-4 py-4 text-gray-500 dark:text-gray-400 max-w-[180px] truncate cursor-pointer hidden md:table-cell" @click="router.visit(route('quotes.edit', q.id))">
                  {{ q.titel }}
                </td>

                <td class="px-4 py-4">
                  <StatusBadge :status="q.status" />
                </td>

                <td class="px-4 py-4 text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap hidden sm:table-cell">{{ fmt(q.totaal) }}</td>

                <td class="px-4 py-4 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap hidden lg:table-cell">{{ fmtDatum(q.geldig_tot) }}</td>

                <td class="px-4 py-4 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap hidden lg:table-cell">{{ fmtDatum(q.created_at) }}</td>

                <!-- Actiemenu -->
                <td class="px-4 py-3" @click.stop>
                  <button
                    type="button"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    @click="toggleActiemenu(q, $event)"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Paginering + telling -->
        <div class="flex items-center justify-between px-1">
          <p class="text-xs text-gray-400 dark:text-gray-500">
            {{ quotes.from }}–{{ quotes.to }} van {{ quotes.total }} offertes
          </p>
          <div v-if="quotes.last_page > 1" class="flex gap-1">
            <component
              v-for="link in quotes.links"
              :key="link.label"
              :is="link.url ? 'a' : 'span'"
              :href="link.url ?? undefined"
              v-html="link.label"
              class="px-3 py-1.5 rounded-lg text-xs border transition-colors"
              :class="link.active
                ? 'bg-blue-600 text-white border-blue-600'
                : link.url
                  ? 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                  : 'bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 border-gray-200 dark:border-gray-700 cursor-default'"
            />
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
