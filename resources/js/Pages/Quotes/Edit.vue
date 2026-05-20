<script setup>
import { ref, reactive, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { defineAsyncComponent } from 'vue'
const VuePdfEmbed = defineAsyncComponent(() => import('vue-pdf-embed'))

const props = defineProps({
  quote: { type: Object, required: true },
})

// ── Meta ─────────────────────────────────────────────────────────────────────
const meta = reactive({
  titel:      props.quote.titel,
  geldig_tot: props.quote.geldig_tot?.slice(0, 10) ?? '',
})
const metaStatus = ref(null) // null | 'saving' | 'saved' | 'error'
let metaTimer = null

async function saveMeta() {
  metaStatus.value = 'saving'
  try {
    await window.axios.patch(route('quotes.meta', props.quote.id), meta)
    metaStatus.value = 'saved'
    setTimeout(() => { metaStatus.value = null }, 2000)
  } catch {
    metaStatus.value = 'error'
  }
}

function onMetaChange() {
  clearTimeout(metaTimer)
  metaTimer = setTimeout(saveMeta, 2000)
}

// ── Secties ───────────────────────────────────────────────────────────────────
const sections = reactive(
  props.quote.sections.map((s) => ({
    ...s,
    html: s.content?.html ?? '',
    hasAi: !!(s.content?.ai_html),
    status: null,
  }))
)

const timers = {}

function onSectionChange(section) {
  section.status = null
  clearTimeout(timers[section.id])
  timers[section.id] = setTimeout(() => saveSection(section), 3000)
}

async function saveSection(section) {
  section.status = 'saving'
  try {
    await window.axios.patch(
      route('quotes.sections.update', { quote: props.quote.id, section: section.id }),
      { html: section.html }
    )
    section.status = 'saved'
    setTimeout(() => { section.status = null }, 2000)
  } catch {
    section.status = 'error'
  }
}

async function saveOnBlur(section) {
  clearTimeout(timers[section.id])
  if (section.status !== 'saved') await saveSection(section)
}

async function restoreAi(section) {
  if (!confirm(`"${section.titel}" terugzetten naar de AI-versie? Jouw wijzigingen gaan verloren.`)) return
  try {
    const res = await window.axios.post(
      route('quotes.sections.restore', { quote: props.quote.id, section: section.id })
    )
    section.html = res.data.html
    section.status = 'saved'
    setTimeout(() => { section.status = null }, 2000)
  } catch {
    alert('Geen AI-versie beschikbaar voor deze sectie.')
  }
}

// ── Investeringen ─────────────────────────────────────────────────────────────
const rows = reactive(
  props.quote.investments.length
    ? props.quote.investments.map((i) => ({ ...i, bedrag: String(i.bedrag) }))
    : [{ id: null, omschrijving: '', bedrag: '' }]
)
const btw = ref(props.quote.btw_tarief ?? '21%')
const invStatus = ref(null)
let invTimer = null

const BTW_OPTIES = ['21%', '0%', 'vrijgesteld']

const subtotaal = computed(() =>
  rows.reduce((s, r) => s + (parseFloat(String(r.bedrag).replace(',', '.')) || 0), 0)
)
const btwBedrag  = computed(() => btw.value === '21%' ? subtotaal.value * 0.21 : 0)
const eindtotaal = computed(() => subtotaal.value + btwBedrag.value)
const fmt = (v) => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(v || 0)

function addRow() { rows.push({ id: null, omschrijving: '', bedrag: '' }) }
function removeRow(i) { if (rows.length > 1) rows.splice(i, 1) }

function onInvChange() {
  invStatus.value = null
  clearTimeout(invTimer)
  invTimer = setTimeout(saveInvestments, 3000)
}

async function saveInvestments() {
  invStatus.value = 'saving'
  try {
    await window.axios.post(route('quotes.investments', props.quote.id), {
      rows: rows.map((r) => ({
        omschrijving: r.omschrijving,
        bedrag: parseFloat(String(r.bedrag).replace(',', '.')) || 0,
      })),
      btw_tarief: btw.value,
    })
    invStatus.value = 'saved'
    setTimeout(() => { invStatus.value = null }, 2000)
  } catch {
    invStatus.value = 'error'
  }
}

// ── Versiebeheer ──────────────────────────────────────────────────────────────
const versioning = ref(false)

async function createVersion() {
  if (!confirm('Een nieuwe versie aanmaken? De huidige versie blijft bewaard.')) return
  versioning.value = true
  try {
    const res = await window.axios.post(route('quotes.version', props.quote.id))
    router.visit(route('quotes.edit', res.data.id))
  } catch {
    alert('Fout bij aanmaken nieuwe versie.')
    versioning.value = false
  }
}

// ── Status beheer ─────────────────────────────────────────────────────────────
const GELDIGE_OVERGANGEN = {
  concept:   ['verzonden'],
  verzonden: ['gewonnen', 'verloren', 'concept'],
  gewonnen:  [],
  verloren:  [],
}

const huidigStatus   = ref(props.quote.status)
const statusHistory  = ref(props.quote.status_history ?? [])
const statusSaving   = ref(false)
const statusError    = ref(null)

const huidigeReden = computed(() => {
  if (!['gewonnen', 'verloren'].includes(huidigStatus.value)) return null
  return statusHistory.value.find(
    h => h.nieuwe_status === huidigStatus.value && h.reden
  )?.reden ?? null
})

// Gewonnen/Verloren reden panel
const redenPanel     = ref(null) // null | 'gewonnen' | 'verloren'
const redenTekst     = ref('')

function kanNaar(status) {
  return (GELDIGE_OVERGANGEN[huidigStatus.value] ?? []).includes(status)
}

async function updateStatus(nieuweStatus, reden = null) {
  statusSaving.value = true
  statusError.value  = null
  try {
    const res = await window.axios.patch(route('quotes.status', props.quote.id), {
      status: nieuweStatus,
      reden:  reden || null,
    })
    huidigStatus.value  = res.data.status
    statusHistory.value = res.data.status_history
    redenPanel.value    = null
    redenTekst.value    = ''
  } catch (e) {
    statusError.value = e.response?.data?.message ?? 'Fout bij statuswijziging.'
  } finally {
    statusSaving.value = false
  }
}

function openRedenPanel(status) {
  redenPanel.value = status
  redenTekst.value = ''
}

function cancelReden() {
  redenPanel.value = null
  redenTekst.value = ''
}

function bevestigReden() {
  if (!redenPanel.value) return
  updateStatus(redenPanel.value, redenTekst.value)
}

// ── PDF ───────────────────────────────────────────────────────────────────────
const pdfPreviewOpen  = ref(false)
const pdfData         = ref(null)
const pdfLoading      = ref(false)
const pdfPageCount    = ref(0)

function onPdfLoaded(pdf) {
  pdfPageCount.value = pdf.numPages
}
const pdfContainer    = ref(null)
const pdfViewerWidth  = ref(850)

function updatePdfWidth() {
  if (pdfContainer.value) {
    pdfViewerWidth.value = Math.min(pdfContainer.value.clientWidth - 48, 1000)
  }
}

async function openPdfPreview() {
  pdfPreviewOpen.value = true
  pdfPageCount.value   = 0
  await nextTick()
  updatePdfWidth()

  if (pdfData.value) return   // al geladen

  pdfLoading.value = true
  try {
    const res = await window.axios.get(route('quotes.pdf.preview', props.quote.id), {
      responseType: 'arraybuffer',
    })
    pdfData.value = new Uint8Array(res.data)
  } catch {
    alert('PDF kon niet worden geladen.')
    pdfPreviewOpen.value = false
  } finally {
    pdfLoading.value = false
  }
}

onMounted(() => window.addEventListener('resize', updatePdfWidth))
onBeforeUnmount(() => window.removeEventListener('resize', updatePdfWidth))

// ── Status helpers ─────────────────────────────────────────────────────────────
function statusClass(s) {
  if (s === 'saving') return 'text-amber-500'
  if (s === 'saved')  return 'text-green-600'
  if (s === 'error')  return 'text-red-500'
  return 'text-transparent'
}
function statusLabel(s) {
  if (s === 'saving') return 'Opslaan…'
  if (s === 'saved')  return '✓ Opgeslagen'
  if (s === 'error')  return '✗ Fout bij opslaan'
  return '·'
}

function formatDatum(iso) {
  if (!iso) return ''
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}
</script>

<template>
  <Head :title="quote.offerte_nummer" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-semibold text-gray-800 dark:text-white font-mono">{{ quote.offerte_nummer }}</h2>
          <StatusBadge :status="huidigStatus" />
          <span class="text-xs text-gray-400 dark:text-gray-500">v{{ quote.versie }}</span>
          <span
            v-if="huidigeReden"
            class="text-xs px-2 py-0.5 rounded-full italic max-w-xs truncate"
            :class="huidigStatus === 'gewonnen' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400'"
            :title="huidigeReden"
          >{{ huidigeReden }}</span>
        </div>
        <div class="flex items-center gap-2">
          <!-- Verzenden knop (concept → verzonden) -->
          <button
            v-if="kanNaar('verzonden')"
            type="button"
            :disabled="statusSaving"
            class="text-sm px-3 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
            @click="updateStatus('verzonden')"
          >
            {{ statusSaving ? 'Bezig…' : '✉ Verzenden' }}
          </button>

          <!-- Terug naar concept (verzonden → concept) -->
          <button
            v-if="kanNaar('concept')"
            type="button"
            :disabled="statusSaving"
            class="text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
            @click="updateStatus('concept')"
          >
            ← Concept
          </button>

          <!-- Gewonnen (verzonden → gewonnen) -->
          <button
            v-if="kanNaar('gewonnen')"
            type="button"
            :disabled="statusSaving"
            class="text-sm px-3 py-1.5 rounded bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 transition-colors"
            @click="openRedenPanel('gewonnen')"
          >
            ✓ Gewonnen
          </button>

          <!-- Verloren (verzonden → verloren) -->
          <button
            v-if="kanNaar('verloren')"
            type="button"
            :disabled="statusSaving"
            class="text-sm px-3 py-1.5 rounded bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 transition-colors"
            @click="openRedenPanel('verloren')"
          >
            ✗ Verloren
          </button>

          <!-- PDF preview -->
          <button
            type="button"
            class="text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            @click="openPdfPreview"
          >
            👁 Preview
          </button>

          <!-- PDF download -->
          <a
            :href="route('quotes.pdf', quote.id)"
            class="text-sm px-3 py-1.5 rounded bg-gray-800 text-white hover:bg-gray-900 transition-colors inline-flex items-center gap-1"
          >
            ↓ PDF
          </a>

          <button
            type="button"
            class="text-sm px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
            :disabled="versioning"
            @click="createVersion"
          >
            {{ versioning ? 'Bezig…' : '+ Nieuwe versie' }}
          </button>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Reden panel (gewonnen / verloren) -->
        <div
          v-if="redenPanel"
          class="rounded-lg border-2 p-5 space-y-3"
          :class="redenPanel === 'gewonnen' ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20' : 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20'"
        >
          <h3 class="text-sm font-semibold" :class="redenPanel === 'gewonnen' ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400'">
            {{ redenPanel === 'gewonnen' ? '✓ Offerte gewonnen' : '✗ Offerte verloren' }} — wat is de reden?
          </h3>
          <input
            v-model="redenTekst"
            type="text"
            maxlength="500"
            :placeholder="redenPanel === 'gewonnen' ? 'Bijv. prijs, snelheid, vertrouwen…' : 'Bijv. klant koos voor lagere prijs, project uitgesteld…'"
            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
            @keydown.enter="bevestigReden"
            @keydown.esc="cancelReden"
          />
          <div class="flex items-center gap-2">
            <button
              type="button"
              :disabled="statusSaving || !redenTekst.trim()"
              class="px-4 py-1.5 rounded text-sm font-medium text-white disabled:opacity-50 transition-colors"
              :class="redenPanel === 'gewonnen' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
              @click="bevestigReden"
            >
              {{ statusSaving ? 'Opslaan…' : 'Bevestigen' }}
            </button>
            <button
              type="button"
              class="px-4 py-1.5 rounded text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              @click="cancelReden"
            >
              Annuleren
            </button>
          </div>
        </div>

        <!-- Status foutmelding -->
        <p v-if="statusError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg px-4 py-2">
          {{ statusError }}
        </p>

        <!-- ── Meta ─────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">Offerte details</h3>
            <span class="text-xs" :class="statusClass(metaStatus)">{{ statusLabel(metaStatus) }}</span>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Klant</label>
              <p class="text-sm text-gray-900 dark:text-white font-medium">{{ quote.client?.naam }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500">{{ quote.client?.contactpersoon }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Geldig tot</label>
              <input
                v-model="meta.geldig_tot"
                type="date"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 w-full"
                @change="onMetaChange"
              />
            </div>
            <div class="col-span-2">
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Titel</label>
              <input
                v-model="meta.titel"
                type="text"
                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 w-full"
                @input="onMetaChange"
              />
            </div>
          </div>
        </div>

        <!-- ── Secties ───────────────────────────────────────────── -->
        <div
          v-for="section in sections"
          :key="section.id"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
        >
          <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ section.titel }}</h3>
            <div class="flex items-center gap-3">
              <span class="text-xs" :class="statusClass(section.status)">{{ statusLabel(section.status) }}</span>
              <button
                v-if="section.hasAi"
                type="button"
                class="text-xs text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                title="Terug naar AI-versie"
                @click="restoreAi(section)"
              >↺ AI-versie</button>
            </div>
          </div>
          <div class="p-4">
            <RichTextEditor
              v-model="section.html"
              :placeholder="`Schrijf hier de ${section.titel.toLowerCase()}…`"
              @update:model-value="onSectionChange(section)"
              @blur="saveOnBlur(section)"
            />
          </div>
        </div>

        <!-- ── Investering ───────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Investering</h3>
            <span class="text-xs" :class="statusClass(invStatus)">{{ statusLabel(invStatus) }}</span>
          </div>
          <div class="p-6 space-y-4">

            <div class="space-y-2">
              <div
                v-for="(row, i) in rows"
                :key="i"
                class="grid grid-cols-[1fr_auto_auto] gap-2 items-center"
              >
                <input
                  v-model="row.omschrijving"
                  type="text"
                  placeholder="Omschrijving"
                  class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
                  @input="onInvChange"
                  @blur="saveInvestments"
                />
                <input
                  v-model="row.bedrag"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00"
                  class="w-28 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm text-right focus:ring-blue-500 focus:border-blue-500"
                  @input="onInvChange"
                  @blur="saveInvestments"
                />
                <button
                  type="button"
                  class="w-7 h-7 flex items-center justify-center rounded text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                  :disabled="rows.length === 1"
                  :class="rows.length === 1 ? 'opacity-30 cursor-not-allowed' : ''"
                  @click="removeRow(i); onInvChange()"
                >×</button>
              </div>

              <button
                type="button"
                class="w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 py-2 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                @click="addRow"
              >+ Regel toevoegen</button>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-sm text-gray-600 dark:text-gray-300">BTW:</span>
              <div class="flex gap-2">
                <button
                  v-for="optie in BTW_OPTIES"
                  :key="optie"
                  type="button"
                  class="px-3 py-1 rounded-full text-xs border transition-colors"
                  :class="btw === optie ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'"
                  @click="btw = optie; onInvChange()"
                >{{ optie }}</button>
              </div>
            </div>

            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
              <div class="flex justify-between px-4 py-2 text-gray-600 dark:text-gray-300">
                <span>Subtotaal</span><span class="font-mono">{{ fmt(subtotaal) }}</span>
              </div>
              <div class="flex justify-between px-4 py-2 text-gray-600 dark:text-gray-300">
                <span>BTW ({{ btw }})</span>
                <span class="font-mono">{{ btw === '21%' ? fmt(btwBedrag) : '—' }}</span>
              </div>
              <div class="flex justify-between px-4 py-2.5 font-semibold text-gray-900 dark:text-white">
                <span>Totaal</span><span class="font-mono text-blue-700 dark:text-blue-400">{{ fmt(eindtotaal) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Status Historie ────────────────────────────────────── -->
        <div v-if="statusHistory.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Status historie</h3>
          </div>
          <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            <li
              v-for="(item, i) in statusHistory"
              :key="i"
              class="flex items-start gap-3 px-6 py-3"
            >
              <div class="mt-0.5 flex-shrink-0">
                <StatusBadge :status="item.nieuwe_status" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  van <span class="font-medium text-gray-700 dark:text-gray-300">{{ item.oude_status }}</span>
                  naar <span class="font-medium text-gray-700 dark:text-gray-300">{{ item.nieuwe_status }}</span>
                  <span v-if="item.user" class="ml-1">door {{ item.user.name }}</span>
                </p>
                <p v-if="item.reden" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 italic">{{ item.reden }}</p>
              </div>
              <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{ formatDatum(item.datum) }}</span>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>

  <!-- PDF Preview Modal (PDF.js canvas — werkt ongeacht browser-instellingen) -->
  <Teleport to="body">
    <div
      v-if="pdfPreviewOpen"
      class="fixed inset-0 z-50 flex flex-col bg-black/90"
      @keydown.esc="pdfPreviewOpen = false"
    >
      <!-- Topbalk -->
      <div class="flex items-center justify-between px-4 py-2 bg-gray-900 flex-shrink-0">
        <span class="text-white text-sm font-mono">{{ quote.offerte_nummer }} — PDF Preview</span>
        <div class="flex items-center gap-3">
          <span v-if="pdfPageCount" class="text-xs text-gray-400">{{ pdfPageCount }} pagina's</span>
          <a
            :href="route('quotes.pdf', quote.id)"
            class="text-xs px-3 py-1 rounded bg-gray-700 text-gray-200 hover:bg-gray-600 transition-colors"
          >↓ Downloaden</a>
          <button
            type="button"
            class="text-gray-400 hover:text-white text-xl leading-none transition-colors"
            @click="pdfPreviewOpen = false"
          >✕</button>
        </div>
      </div>

      <!-- PDF canvas -->
      <div ref="pdfContainer" class="flex-1 overflow-y-auto bg-[#3a3a3a] flex flex-col items-center py-8">
        <div v-if="pdfLoading" class="text-gray-200 text-sm mt-20">PDF laden…</div>

        <VuePdfEmbed
          v-else-if="pdfData"
          :source="pdfData"
          :width="pdfViewerWidth"
          class="pdf-preview"
          @loaded="onPdfLoaded"
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
