<script setup>
import { ref, computed, nextTick, defineAsyncComponent } from 'vue'
import { Head } from '@inertiajs/vue3'
import KlantLayout from '@/Layouts/KlantLayout.vue'

const VuePdfEmbed = defineAsyncComponent(() => import('vue-pdf-embed'))

const props = defineProps({
  client: { type: Object, required: true },
  quotes: { type: Array,  default: () => [] },
})

const STATUS_LABELS = {
  verzonden: 'Binnengekomen',
  gewonnen:  'Afgerond',
  verloren:  'Niet afgerond',
  vervallen: 'Vervallen',
}

const visibleQuotes = computed(() => props.quotes.filter(q => q.status !== 'concept'))

const STATUS_CLASSES = {
  concept:   'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  verzonden: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
  gewonnen:  'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
  verloren:  'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400',
  vervallen: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
}

function fmtBedrag(v) {
  if (!v) return '—'
  return new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v)
}

function fmtDatum(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso))
}

// ── PDF modal ──────────────────────────────────────────────────────────────────
const pdfOpen      = ref(false)
const pdfData      = ref(null)
const pdfLoading   = ref(false)
const pdfPageCount = ref(0)
const pdfContainer = ref(null)
const pdfWidth     = ref(850)
const activeQuote  = ref(null)

function updatePdfWidth() {
  if (pdfContainer.value) pdfWidth.value = Math.min(pdfContainer.value.clientWidth - 48, 1000)
}

async function openPdf(quote) {
  activeQuote.value = quote
  pdfOpen.value     = true
  pdfPageCount.value = 0
  pdfData.value     = null
  await nextTick(); updatePdfWidth()
  pdfLoading.value  = true
  try {
    const res = await window.axios.get(route('klant.quotes.pdf', quote.id), { responseType: 'arraybuffer' })
    pdfData.value = new Uint8Array(res.data)
  } catch {
    alert('PDF kon niet worden geladen.')
    pdfOpen.value = false
  } finally {
    pdfLoading.value = false
  }
}

function closePdf() {
  pdfOpen.value = false
}
</script>

<template>
  <Head title="Mijn offertes" />
  <KlantLayout>
    <template #title>{{ client.naam }}</template>

    <div class="max-w-4xl mx-auto px-3 sm:px-6 py-6 sm:py-8">

      <!-- Header -->
      <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg font-bold overflow-hidden shrink-0">
          <img v-if="client.logo_url" :src="client.logo_url" class="w-full h-full object-contain" alt="" />
          <span v-else>{{ client.naam.charAt(0).toUpperCase() }}</span>
        </div>
        <div>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ client.naam }}</h1>
          <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Uw offertes</p>
        </div>
      </div>

      <!-- Quotes -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">

        <div v-if="visibleQuotes.length === 0" class="px-6 py-16 text-center">
          <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="text-sm text-gray-400 dark:text-gray-500">Nog geen offertes beschikbaar.</p>
        </div>

        <div class="overflow-x-auto">
        <table v-if="visibleQuotes.length" class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
              <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Nummer</th>
              <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden sm:table-cell">Omschrijving</th>
              <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Status</th>
              <th class="px-4 sm:px-6 py-3.5 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden sm:table-cell">Bedrag</th>
              <th class="px-4 sm:px-6 py-3.5 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden md:table-cell">Datum</th>
              <th class="px-4 sm:px-6 py-3.5 text-right text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden lg:table-cell">Geldig tot</th>
              <th class="px-4 sm:px-6 py-3.5 w-12" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr
              v-for="q in visibleQuotes"
              :key="q.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer transition-colors group"
              @click="openPdf(q)"
            >
              <td class="px-4 sm:px-6 py-4 font-mono text-xs text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">
                {{ q.offerte_nummer }}
                <span v-if="q.versie > 1" class="ml-1 text-gray-400 text-[10px]">v{{ q.versie }}</span>
              </td>
              <td class="px-4 sm:px-6 py-4 text-gray-700 dark:text-gray-300 max-w-xs truncate hidden sm:table-cell">{{ q.titel }}</td>
              <td class="px-4 sm:px-6 py-4">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="STATUS_CLASSES[q.status] ?? 'bg-gray-100 text-gray-600'"
                >{{ STATUS_LABELS[q.status] ?? q.status }}</span>
              </td>
              <td class="px-4 sm:px-6 py-4 text-right font-mono text-gray-700 dark:text-gray-300 whitespace-nowrap hidden sm:table-cell">{{ fmtBedrag(q.totaal) }}</td>
              <td class="px-4 sm:px-6 py-4 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap hidden md:table-cell">{{ fmtDatum(q.created_at) }}</td>
              <td class="px-4 sm:px-6 py-4 text-right text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap hidden lg:table-cell">{{ fmtDatum(q.geldig_tot) }}</td>
              <td class="px-4 sm:px-6 py-4 text-right">
                <span class="inline-flex items-center gap-1 text-xs text-blue-500 dark:text-blue-400 group-hover:opacity-100 opacity-50 sm:opacity-0 transition-opacity font-medium whitespace-nowrap">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  <span class="hidden sm:inline">Bekijken</span>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <p class="text-center text-xs text-gray-300 dark:text-gray-600 mt-8">
        Vragen over een offerte? Neem contact met ons op.
      </p>
    </div>
  </KlantLayout>

  <!-- ── PDF Preview Modal ────────────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="pdfOpen" class="fixed inset-0 z-50 flex flex-col bg-black/90" @keydown.esc="closePdf" tabindex="-1">
      <div class="flex items-center justify-between px-4 py-2 bg-gray-900 flex-shrink-0">
        <span class="text-white text-sm font-mono">
          {{ activeQuote?.offerte_nummer }}
          <span v-if="activeQuote?.versie > 1" class="text-gray-400 text-xs ml-1">v{{ activeQuote.versie }}</span>
          — {{ activeQuote?.titel }}
        </span>
        <div class="flex items-center gap-3">
          <span v-if="pdfPageCount" class="text-xs text-gray-400">{{ pdfPageCount }} pagina's</span>
          <button type="button" class="text-gray-400 hover:text-white text-xl leading-none transition-colors" @click="closePdf">✕</button>
        </div>
      </div>
      <div ref="pdfContainer" class="flex-1 overflow-y-auto bg-[#3a3a3a] flex flex-col items-center py-8">
        <div v-if="pdfLoading" class="text-gray-200 text-sm mt-20">PDF laden…</div>
        <VuePdfEmbed
          v-else-if="pdfData"
          :source="pdfData"
          :width="pdfWidth"
          class="pdf-preview"
          @loaded="p => pdfPageCount = p.numPages"
        />
      </div>
    </div>
  </Teleport>
</template>

<style>
.pdf-preview > div:not(:last-child) {
  border-bottom: 14px solid #111;
}
</style>
