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
      <div class="flex items-center justify-between w-full gap-4">

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 min-w-0">
          <a :href="route('quotes.index')" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0">Offertes</a>
          <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
          <span class="text-sm font-semibold font-mono text-gray-800 dark:text-white truncate">{{ quote.offerte_nummer }}</span>
          <StatusBadge :status="huidigStatus" />
          <span
            v-if="huidigeReden"
            class="text-xs px-2 py-0.5 rounded-full italic max-w-[160px] truncate shrink-0"
            :class="huidigStatus === 'gewonnen' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400'"
            :title="huidigeReden"
          >{{ huidigeReden }}</span>
        </div>

        <!-- Acties -->
        <div class="flex items-center gap-2 shrink-0">

          <!-- Concept → Verzonden -->
          <button
            v-if="kanNaar('verzonden')"
            type="button"
            :disabled="statusSaving"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
            @click="updateStatus('verzonden')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
            </svg>
            {{ statusSaving ? 'Bezig…' : 'Verzenden' }}
          </button>

          <!-- Verzonden → Concept -->
          <button
            v-if="kanNaar('concept')"
            type="button"
            :disabled="statusSaving"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
            @click="updateStatus('concept')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            Concept
          </button>

          <!-- Verzonden → Gewonnen -->
          <button
            v-if="kanNaar('gewonnen')"
            type="button"
            :disabled="statusSaving"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 disabled:opacity-50 transition-colors"
            @click="openRedenPanel('gewonnen')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            Gewonnen
          </button>

          <!-- Verzonden → Verloren -->
          <button
            v-if="kanNaar('verloren')"
            type="button"
            :disabled="statusSaving"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition-colors"
            @click="openRedenPanel('verloren')"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Verloren
          </button>

          <!-- Preview -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            @click="openPdfPreview"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            Preview
          </button>

          <!-- PDF download -->
          <a
            :href="route('quotes.pdf', quote.id)"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            PDF
          </a>

          <!-- Nieuwe versie -->
          <button
            type="button"
            :disabled="versioning"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
            @click="createVersion"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ versioning ? 'Bezig…' : 'Nieuwe versie' }}
          </button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-3xl mx-auto px-6 space-y-5">

        <!-- Reden panel -->
        <div
          v-if="redenPanel"
          class="rounded-xl border-2 p-5 space-y-3"
          :class="redenPanel === 'gewonnen' ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20' : 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20'"
        >
          <h3 class="text-sm font-semibold" :class="redenPanel === 'gewonnen' ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400'">
            {{ redenPanel === 'gewonnen' ? 'Offerte gewonnen' : 'Offerte verloren' }} — wat is de reden?
          </h3>
          <input
            v-model="redenTekst"
            type="text"
            maxlength="500"
            :placeholder="redenPanel === 'gewonnen' ? 'Bijv. prijs, snelheid, vertrouwen…' : 'Bijv. klant koos voor lagere prijs, project uitgesteld…'"
            class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            @keydown.enter="bevestigReden"
            @keydown.esc="cancelReden"
          />
          <div class="flex items-center gap-2">
            <button
              type="button"
              :disabled="statusSaving || !redenTekst.trim()"
              class="px-4 py-1.5 rounded-lg text-sm font-medium text-white disabled:opacity-50 transition-colors"
              :class="redenPanel === 'gewonnen' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'"
              @click="bevestigReden"
            >{{ statusSaving ? 'Opslaan…' : 'Bevestigen' }}</button>
            <button
              type="button"
              class="px-4 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              @click="cancelReden"
            >Annuleren</button>
          </div>
        </div>

        <!-- Status fout -->
        <p v-if="statusError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl px-4 py-3">
          {{ statusError }}
        </p>

        <!-- ── Offerte details ────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
          <div class="flex items-center justify-between mb-5">
            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Offerte details</h3>
            <span class="text-xs" :class="statusClass(metaStatus)">{{ statusLabel(metaStatus) }}</span>
          </div>
          <div class="grid grid-cols-2 gap-6 mb-5">
            <div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Klant</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ quote.client?.naam }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ quote.client?.contactpersoon }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Geldig tot</p>
              <input
                v-model="meta.geldig_tot"
                type="date"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                @change="onMetaChange"
              />
            </div>
          </div>
          <div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Titel</p>
            <input
              v-model="meta.titel"
              type="text"
              class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
              @input="onMetaChange"
            />
          </div>
        </div>

        <!-- ── Secties ────────────────────────────────────────── -->
        <div
          v-for="section in sections"
          :key="section.id"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6"
        >
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ section.titel }}</h3>
            <div class="flex items-center gap-3">
              <span class="text-xs" :class="statusClass(section.status)">{{ statusLabel(section.status) }}</span>
              <button
                v-if="section.hasAi"
                type="button"
                class="inline-flex items-center gap-1 text-sm text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                title="Terug naar AI-versie"
                @click="restoreAi(section)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                AI herschrijven
              </button>
            </div>
          </div>
          <RichTextEditor
            v-model="section.html"
            :placeholder="`Schrijf hier de ${section.titel.toLowerCase()}…`"
            @update:model-value="onSectionChange(section)"
            @blur="saveOnBlur(section)"
          />
        </div>

        <!-- ── Investering ────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
          <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Investering</h3>
            <span class="text-xs" :class="statusClass(invStatus)">{{ statusLabel(invStatus) }}</span>
          </div>

          <!-- Regels -->
          <div class="space-y-2 mb-3">
            <div
              v-for="(row, i) in rows"
              :key="i"
              class="flex gap-2 items-center"
            >
              <input
                v-model="row.omschrijving"
                type="text"
                placeholder="Omschrijving"
                class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
                @input="onInvChange"
                @blur="saveInvestments"
              />
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">€</span>
                <input
                  v-model="row.bedrag"
                  type="text"
                  inputmode="decimal"
                  placeholder="0,00"
                  class="w-32 pl-7 pr-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm text-right focus:outline-none focus:ring-1 focus:ring-blue-500"
                  @input="onInvChange"
                  @blur="saveInvestments"
                />
              </div>
              <button
                type="button"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0"
                :disabled="rows.length === 1"
                :class="rows.length === 1 ? 'opacity-0 pointer-events-none' : ''"
                @click="removeRow(i); onInvChange()"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <button
              type="button"
              class="w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 py-2.5 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
              @click="addRow"
            >+ Regel toevoegen</button>
          </div>

          <!-- BTW -->
          <div class="flex items-center gap-3 mt-5">
            <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">BTW:</span>
            <div class="flex gap-1.5">
              <button
                v-for="optie in BTW_OPTIES"
                :key="optie"
                type="button"
                class="px-4 py-1.5 rounded-full text-sm border transition-colors"
                :class="btw === optie
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'"
                @click="btw = optie; onInvChange()"
              >{{ optie }}</button>
            </div>
          </div>

          <!-- Totalen -->
          <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 text-sm overflow-hidden">
            <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300">
              <span>Subtotaal</span>
              <span>{{ fmt(subtotaal) }}</span>
            </div>
            <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300">
              <span>BTW ({{ btw }})</span>
              <span>{{ btw === '21%' ? fmt(btwBedrag) : '—' }}</span>
            </div>
            <div class="flex justify-between px-5 py-3.5 font-semibold text-gray-900 dark:text-white">
              <span>Totaal</span>
              <span class="text-blue-600 dark:text-blue-400">{{ fmt(eindtotaal) }}</span>
            </div>
          </div>
        </div>

        <!-- ── Status Historie ─────────────────────────────────── -->
        <div v-if="statusHistory.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Status historie</h3>
          </div>
          <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            <li
              v-for="(item, i) in statusHistory"
              :key="i"
              class="flex items-start gap-3 px-6 py-3"
            >
              <div class="mt-0.5 shrink-0">
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
              <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">{{ formatDatum(item.datum) }}</span>
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
