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

function sortIcon(kolom) {
  if (sort.value !== kolom) return '↕'
  return direction.value === 'asc' ? '↑' : '↓'
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

const actiemenu = ref(null)

function toggleActiemenu(id) {
  actiemenu.value = actiemenu.value === id ? null : id
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
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Offertes</h2>
        <div class="flex items-center gap-2">
          <a
            :href="route('quotes.export', { status: filters.status, sector: filters.sector })"
            class="text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            ↓ CSV export
          </a>
          <a
            :href="route('quotes.create')"
            class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
          >
            + Nieuwe offerte
          </a>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <div v-if="actiemenu !== null" class="fixed inset-0 z-10" @click="actiemenu = null" />
    </Teleport>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4">
          <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <input
              v-model="search"
              type="text"
              placeholder="Zoek nummer, titel, klant…"
              class="col-span-2 md:col-span-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
            />
            <select v-model="status" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Alle statussen</option>
              <option v-for="s in statussen" :key="s" :value="s">{{ STATUS_LABELS[s] ?? s }}</option>
            </select>
            <select v-model="sector" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500">
              <option value="">Alle sectoren</option>
              <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
            </select>
            <input
              v-model="datumVan"
              type="date"
              title="Aangemaakt vanaf"
              class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
            />
            <div class="flex gap-2">
              <input
                v-model="datumTot"
                type="date"
                title="Aangemaakt tot"
                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
              />
              <button
                v-if="hasFilters"
                type="button"
                title="Filters wissen"
                class="px-2 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm transition-colors"
                @click="resetFilters"
              >✕</button>
            </div>
          </div>
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
        <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
              <tr>
                <th class="px-4 py-3 w-10">
                  <input
                    type="checkbox"
                    :checked="alleGeselecteerd"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                    @change="toggleAlles"
                  />
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 select-none whitespace-nowrap"
                  @click="setSort('offerte_nummer')"
                >Nummer <span class="ml-0.5 text-gray-400">{{ sortIcon('offerte_nummer') }}</span></th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Klant</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Titel</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 select-none"
                  @click="setSort('status')"
                >Status <span class="ml-0.5 text-gray-400">{{ sortIcon('status') }}</span></th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 select-none whitespace-nowrap"
                  @click="setSort('totaal')"
                >Bedrag <span class="ml-0.5 text-gray-400">{{ sortIcon('totaal') }}</span></th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 select-none whitespace-nowrap"
                  @click="setSort('geldig_tot')"
                >Geldig tot <span class="ml-0.5 text-gray-400">{{ sortIcon('geldig_tot') }}</span></th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 select-none whitespace-nowrap"
                  @click="setSort('created_at')"
                >Aangemaakt <span class="ml-0.5 text-gray-400">{{ sortIcon('created_at') }}</span></th>
                <th class="px-4 py-3 w-10" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-if="quotes.data.length === 0">
                <td colspan="9" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                  Geen offertes gevonden.
                  <a :href="route('quotes.create')" class="text-blue-600 dark:text-blue-400 hover:underline ml-1">Maak de eerste aan.</a>
                </td>
              </tr>
              <tr
                v-for="q in quotes.data"
                :key="q.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                :class="selected.has(q.id) ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''"
              >
                <td class="px-4 py-3" @click.stop>
                  <input
                    type="checkbox"
                    :checked="selected.has(q.id)"
                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
                    @change="toggleRij(q.id)"
                  />
                </td>

                <td class="px-4 py-3 font-mono text-xs text-blue-700 dark:text-blue-400 font-medium whitespace-nowrap cursor-pointer" @click="router.visit(route('quotes.edit', q.id))">
                  {{ q.offerte_nummer }}
                </td>

                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap cursor-pointer" @click="router.visit(route('quotes.edit', q.id))">
                  {{ q.client?.naam ?? '—' }}
                </td>

                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[180px] truncate cursor-pointer" @click="router.visit(route('quotes.edit', q.id))">
                  {{ q.titel }}
                </td>

                <td class="px-4 py-3">
                  <StatusBadge :status="q.status" />
                </td>

                <td class="px-4 py-3 text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ fmt(q.totaal) }}</td>

                <td class="px-4 py-3 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap">{{ fmtDatum(q.geldig_tot) }}</td>

                <td class="px-4 py-3 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap">{{ fmtDatum(q.created_at) }}</td>

                <!-- Actiemenu -->
                <td class="px-4 py-3 relative" @click.stop>
                  <button
                    type="button"
                    class="w-7 h-7 flex items-center justify-center rounded text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 transition-colors text-lg leading-none"
                    @click="toggleActiemenu(q.id)"
                  >⋮</button>

                  <div
                    v-if="actiemenu === q.id"
                    class="absolute right-8 top-2 z-20 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 w-44"
                  >
                    <a
                      :href="route('quotes.pdf.preview', q.id)"
                      target="_blank"
                      class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                      @click="actiemenu = null"
                    >👁 Bekijken</a>
                    <button
                      type="button"
                      class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                      @click="router.visit(route('quotes.edit', q.id)); actiemenu = null"
                    >✏ Bewerken</button>
                    <a
                      :href="route('quotes.pdf', q.id)"
                      class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                      @click="actiemenu = null"
                    >↓ PDF downloaden</a>
                    <button
                      type="button"
                      class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                      @click="dupliceer(q)"
                    >⧉ Dupliceren (V{{ (q.versie ?? 1) + 1 }})</button>
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1" />
                    <button
                      type="button"
                      class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                      @click="verwijder(q)"
                    >✕ Verwijderen</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginering + telling -->
        <div class="flex items-center justify-between">
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
              class="px-3 py-1 rounded text-sm border"
              :class="link.active
                ? 'bg-blue-600 text-white border-blue-600'
                : link.url
                  ? 'text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                  : 'text-gray-300 dark:text-gray-600 border-gray-200 dark:border-gray-700 cursor-not-allowed'"
            />
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
