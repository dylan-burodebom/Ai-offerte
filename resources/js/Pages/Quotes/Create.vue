<script setup>
import { ref, reactive, computed, nextTick, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import WizardProgress from '@/Components/WizardProgress.vue'
import Step1Client from '@/Components/Step1Client.vue'
import Step2Intake from '@/Components/Step2Intake.vue'
import Step3Generate from '@/Components/Step3Generate.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import draggable from 'vuedraggable'
import { defineAsyncComponent } from 'vue'
const VuePdfEmbed = defineAsyncComponent(() => import('vue-pdf-embed'))

const props = defineProps({
  sectoren:          { type: Array,  default: () => [] },
  dienstenLijst:     { type: Array,  default: () => [] },
  preselectedClient: { type: Object, default: null },
})

// ── Wizard ────────────────────────────────────────────────────────────────────
const STEPS = ['Klant', 'Offerte', 'Generatie']

const currentStep = ref(props.preselectedClient ? 2 : 1)
const saving      = ref(false)
const step2Errors    = ref({})
const step2SaveError = ref(null)
const finalized      = ref(false)
const editorMode     = ref(false)

const wizard = reactive({
  clientId:               props.preselectedClient?.id ?? null,
  selectedClient:         props.preselectedClient ?? null,
  quoteId:                null,
  offerteNummer:          null,
  titel:                  '',
  projectbeschrijving:    '',
  fireflies_samenvatting: '',
  diensten:               [],
  geldig_tot:             '',
  generatedSections:      [],
})

const step2Data = computed({
  get: () => ({
    titel:                  wizard.titel,
    projectbeschrijving:    wizard.projectbeschrijving,
    fireflies_samenvatting: wizard.fireflies_samenvatting,
    diensten:               wizard.diensten,
    geldig_tot:             wizard.geldig_tot,
  }),
  set: (v) => Object.assign(wizard, v),
})

const canProceed = computed(() => {
  if (currentStep.value === 1) return wizard.clientId !== null
  if (currentStep.value === 2) {
    const heeftContext = wizard.projectbeschrijving.trim().length > 0
      || wizard.fireflies_samenvatting.trim().length > 0
    return wizard.titel.trim().length >= 3
      && heeftContext
      && wizard.diensten.length > 0
      && wizard.geldig_tot !== ''
  }
  if (currentStep.value === 3) return wizard.generatedSections.length > 0
  return true
})

const validationMessage = computed(() => {
  if (currentStep.value === 1 && !wizard.clientId)
    return 'Selecteer of maak een klant aan om verder te gaan.'
  if (currentStep.value === 2) {
    if (wizard.titel.trim().length < 3)      return 'Vul een titel in (minimaal 3 tekens).'
    if (!wizard.projectbeschrijving.trim() && !wizard.fireflies_samenvatting.trim())
      return 'Vul een projectbeschrijving in of plak een Fireflies samenvatting.'
    if (wizard.diensten.length === 0)        return 'Selecteer minimaal één dienst.'
    if (!wizard.geldig_tot)                  return 'Kies een geldigheidsdatum.'
  }
  if (currentStep.value === 3 && wizard.generatedSections.length === 0)
    return 'Genereer eerst de offertetekst om verder te gaan.'
  return null
})

async function saveDraft() {
  saving.value = true
  step2Errors.value = {}
  step2SaveError.value = null
  const payload = {
    client_id:               wizard.clientId,
    titel:                   wizard.titel,
    projectbeschrijving:     wizard.projectbeschrijving,
    fireflies_samenvatting:  wizard.fireflies_samenvatting || null,
    diensten:                wizard.diensten,
    geldig_tot:              wizard.geldig_tot,
  }
  try {
    if (wizard.quoteId) {
      const res = await window.axios.patch(route('quotes.draft.update', wizard.quoteId), payload)
      wizard.offerteNummer = res.data.offerte_nummer
    } else {
      const res = await window.axios.post(route('quotes.draft'), payload)
      wizard.quoteId       = res.data.id
      wizard.offerteNummer = res.data.offerte_nummer
    }
    return true
  } catch (e) {
    if (e.response?.status === 422) {
      step2Errors.value = e.response.data.errors ?? {}
      const firstError = Object.values(step2Errors.value).flat()[0]
      if (firstError) step2SaveError.value = firstError
    } else {
      step2SaveError.value = 'Er is een fout opgetreden. Controleer de verbinding en probeer opnieuw.'
      console.error('saveDraft fout:', e)
    }
    return false
  } finally {
    saving.value = false
  }
}

async function next() {
  if (!canProceed.value) return
  if (currentStep.value === 2) {
    const ok = await saveDraft()
    if (!ok) return
  }
  currentStep.value++
}

function prev() {
  if (editorMode.value) {
    editorMode.value = false
    currentStep.value = 3
    return
  }
  if (currentStep.value > 1) currentStep.value--
}

// ── Editor tabs & preview ─────────────────────────────────────────────────────
const activeTab     = ref('opbouw')
const sidebarTab    = ref('blokken')
const previewDevice = ref('desktop')

const TABS = [
  { key: 'opbouw', label: 'Opbouw' },
  { key: 'inhoud', label: 'Inhoud' },
]

const BLOK_TYPES = [
  { type: 'over_ons',    titel: 'Over ons',       omschrijving: 'Bedrijfsprofiel' },
  { type: 'referenties', titel: 'Referenties',    omschrijving: 'Eerdere projecten' },
  { type: 'werkwijze',   titel: 'Werkwijze',      omschrijving: 'Aanpak & proces' },
  { type: 'planning',    titel: 'Planning',       omschrijving: 'Tijdlijn' },
  { type: 'garantie',    titel: 'Garantie',       omschrijving: 'Voorwaarden' },
  { type: 'faq',         titel: 'FAQ',            omschrijving: 'Veelgestelde vragen' },
  { type: 'custom',       titel: 'Aangepast blok', omschrijving: 'Eigen inhoud' },
  { type: 'einde_pagina', titel: 'Einde pagina',   omschrijving: 'Volgende blok op nieuwe pagina' },
]

const rightPdfData      = ref(null)
const rightPdfLoading   = ref(false)
const rightPdfPageCount = ref(0)
const rightPdfContainer = ref(null)
const rightPdfWidth     = ref(800)

function updateRightPdfWidth() {
  if (!rightPdfContainer.value) return
  const w = rightPdfContainer.value.clientWidth - 48
  rightPdfWidth.value = previewDevice.value === 'desktop' ? w
    : previewDevice.value === 'tablet' ? Math.min(550, w)
    : Math.min(375, w)
}

async function loadRightPdf() {
  if (!wizard.quoteId || rightPdfLoading.value || rightPdfData.value) return
  rightPdfLoading.value = true
  try {
    const res = await window.axios.get(route('quotes.pdf.preview', wizard.quoteId), { responseType: 'arraybuffer' })
    rightPdfData.value = new Uint8Array(res.data)
  } catch {} finally { rightPdfLoading.value = false }
}

onMounted(() => window.addEventListener('resize', updateRightPdfWidth))
onBeforeUnmount(() => window.removeEventListener('resize', updateRightPdfWidth))

watch(activeTab, (newTab) => {
  if (newTab === 'opbouw' && editorMode.value) {
    rightPdfData.value = null
    nextTick(() => { updateRightPdfWidth(); loadRightPdf() })
  }
})

// ── Editor: secties ───────────────────────────────────────────────────────────
const editorSections = ref([])
const sectionRefs    = {}
const timers         = {}

function onGenerationDone(sections) {
  wizard.generatedSections = sections
  editorSections.value = sections.map(s => ({
    id:        s.id,
    titel:     s.titel,
    html:      s.content?.html ?? '',
    hasAi:     !!(s.content?.ai_html),
    status:    null,
    collapsed: false,
  }))
  currentStep.value = 4
}

async function saveInvAndProceed() {
  if (!editorInvCanProceed.value) {
    editorSaveError.value = 'Vul minimaal één regel in met omschrijving en bedrag groter dan 0.'
    return
  }
  saving.value = true
  editorSaveError.value = null
  try {
    await window.axios.post(route('quotes.investments', wizard.quoteId), {
      rows: editorInvRows.value.map(r => ({
        omschrijving: r.omschrijving,
        bedrag: parseFloat(String(r.bedrag).replace(',', '.')) || 0,
      })),
      btw_tarief: editorBtw.value,
    })
    editorMode.value = true
    nextTick(() => { updateRightPdfWidth(); loadRightPdf() })
  } catch {
    editorSaveError.value = 'Fout bij opslaan. Probeer opnieuw.'
  } finally {
    saving.value = false
  }
}

function scrollToSection(id) {
  sectionRefs[id]?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function openBlockEditor(section) {
  activeTab.value = 'inhoud'
  nextTick(() => sectionRefs[section.id]?.scrollIntoView({ behavior: 'smooth', block: 'start' }))
}

function onSectionChange(section) {
  section.status = null
  clearTimeout(timers[section.id])
  timers[section.id] = setTimeout(() => saveSection(section), 3000)
}

async function saveSection(section) {
  section.status = 'saving'
  try {
    await window.axios.patch(
      route('quotes.sections.update', { quote: wizard.quoteId, section: section.id }),
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
      route('quotes.sections.restore', { quote: wizard.quoteId, section: section.id })
    )
    section.html = res.data.html
    section.status = 'saved'
    setTimeout(() => { section.status = null }, 2000)
  } catch {
    alert('Geen AI-versie beschikbaar.')
  }
}

async function saveReorder() {
  const order = editorSections.value
    .filter(s => s.id && !String(s.id).startsWith('temp_'))
    .map(s => s.id)
  try { await window.axios.patch(route('quotes.sections.reorder', wizard.quoteId), { order }) } catch {}
}

function cloneBlock(block) {
  return { id: `temp_${Date.now()}`, titel: block.titel, type: block.type, blockType: block.type, html: '', hasAi: false, status: null, collapsed: false, _pending: true }
}

async function onAddFromSidebar(event) {
  const idx  = event.newIndex
  const item = editorSections.value[idx]
  if (item.type === 'custom') {
    const titel = window.prompt('Titel voor dit blok:') ?? ''
    if (!titel.trim()) { editorSections.value.splice(idx, 1); return }
    item.titel = titel.trim()
  }
  item.status = 'saving'
  try {
    const res = await window.axios.post(route('quotes.sections.store', wizard.quoteId), { titel: item.titel, volgorde: idx, block_type: item.blockType ?? null })
    item.id = res.data.id; item._pending = false; item.status = null
    await saveReorder()
  } catch {
    editorSections.value.splice(idx, 1)
  }
}

async function deleteSectionItem(section) {
  if (!confirm(`"${section.titel}" verwijderen?`)) return
  try {
    await window.axios.delete(route('quotes.sections.destroy', { quote: wizard.quoteId, section: section.id }))
    const idx = editorSections.value.findIndex(s => s.id === section.id)
    if (idx !== -1) editorSections.value.splice(idx, 1)
  } catch {
    alert('Fout bij verwijderen sectie.')
  }
}

// ── Editor: investering ───────────────────────────────────────────────────────
let _editorRowKey = 0
const editorInvRows   = ref([{ id: null, omschrijving: '', bedrag: '', _key: ++_editorRowKey }])
const editorBtw       = ref('21%')
const BTW_OPTIES      = ['21%', '0%', 'vrijgesteld']
const editorSaveError = ref(null)
const invCollapsed    = ref(false)

const editorSubtotaal = computed(() =>
  editorInvRows.value.reduce((s, r) => s + (parseFloat(String(r.bedrag).replace(',', '.')) || 0), 0)
)
const editorBtwBedrag  = computed(() => editorBtw.value === '21%' ? editorSubtotaal.value * 0.21 : 0)
const editorEindtotaal = computed(() => editorSubtotaal.value + editorBtwBedrag.value)
const editorInvCanProceed = computed(() =>
  editorInvRows.value.length > 0 &&
  editorInvRows.value.every(r => {
    const b = parseFloat(String(r.bedrag).replace(',', '.')) || 0
    return r.omschrijving?.trim() && b > 0
  })
)

const fmt = v => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(v || 0)

function addEditorRow() { editorInvRows.value.push({ id: null, omschrijving: '', bedrag: '', _key: ++_editorRowKey }) }
function removeEditorRow(i) { if (editorInvRows.value.length > 1) editorInvRows.value.splice(i, 1) }

async function saveEditor() {
  if (!editorInvCanProceed.value) {
    editorSaveError.value = 'Vul minimaal één regel in met omschrijving en bedrag groter dan 0.'
    return
  }
  saving.value = true
  editorSaveError.value = null
  try {
    await window.axios.post(route('quotes.investments', wizard.quoteId), {
      rows: editorInvRows.value.map(r => ({
        omschrijving: r.omschrijving,
        bedrag: parseFloat(String(r.bedrag).replace(',', '.')) || 0,
      })),
      btw_tarief: editorBtw.value,
    })
    finalized.value = true
  } catch {
    editorSaveError.value = 'Fout bij opslaan. Probeer opnieuw.'
  } finally {
    saving.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
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
function blockOmschrijving(section) {
  return BLOK_TYPES.find(b => b.type === section.type)?.omschrijving ?? 'Tekstblok'
}
</script>

<template>
  <Head title="Nieuwe offerte" />
  <AuthenticatedLayout>

    <!-- ── Header ──────────────────────────────────────────────────────────── -->
    <template #header>
      <!-- Editor mode header -->
      <div v-if="editorMode && !finalized" class="flex items-center justify-between w-full gap-3">
        <div class="flex items-center gap-2 min-w-0">
          <a :href="route('quotes.index')" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0">Offertes</a>
          <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          <span class="text-sm font-semibold font-mono text-gray-800 dark:text-white truncate">{{ wizard.offerteNummer }}</span>
          <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 font-medium shrink-0">concept</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
          <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" @click="prev">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Opnieuw genereren
          </button>
          <button type="button" :disabled="saving" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors" @click="saveEditor">
            <svg v-if="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            {{ saving ? 'Opslaan…' : 'Offerte opslaan' }}
          </button>
        </div>
      </div>

      <!-- Stappen 1+2+3 header -->
      <div v-else class="flex items-center justify-between w-full gap-3">
        <div class="flex items-center gap-2 min-w-0">
          <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Nieuwe offerte</h2>
          <span v-if="wizard.offerteNummer" class="text-xs font-mono px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">{{ wizard.offerteNummer }}</span>
        </div>
        <!-- Stap indicator -->
        <div class="flex items-center gap-1">
          <template v-for="(label, i) in ['Klant', 'Project details', 'AI Generatie', 'Investering']" :key="i">
            <div class="flex items-center gap-1.5">
              <div class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                :class="(i + 1) < currentStep ? 'bg-green-500 text-white' : (i + 1) === currentStep ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-400'"
              >
                <svg v-if="(i + 1) < currentStep" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span v-else>{{ i + 1 }}</span>
              </div>
              <span class="text-xs font-medium hidden sm:block" :class="(i + 1) === currentStep ? 'text-gray-800 dark:text-white' : 'text-gray-400 dark:text-gray-500'">{{ label }}</span>
            </div>
            <svg v-if="i < 3" class="w-3 h-3 text-gray-300 dark:text-gray-600 mx-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </template>
        </div>
        <div class="w-24" />
      </div>
    </template>

    <!-- ── Succes ───────────────────────────────────────────────────────────── -->
    <div v-if="finalized" class="py-8">
      <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-10 text-center">
          <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Offerte opgeslagen</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-1">
            <span class="font-mono text-blue-700 dark:text-blue-400">{{ wizard.offerteNummer }}</span> is opgeslagen als concept.
          </p>
          <p class="text-sm text-gray-400 dark:text-gray-500 mb-8">Open de editor om de tekst verder te verfijnen en de offerte te versturen.</p>
          <div class="flex justify-center gap-3">
            <button type="button" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors" @click="router.visit(route('quotes.edit', wizard.quoteId))">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              Offerte bewerken
            </button>
            <button type="button" class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" @click="router.visit(route('quotes.index'))">Naar offertes</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Editor mode (na generatie) ──────────────────────────────────────── -->
    <div v-else-if="editorMode">

      <!-- Tab bar -->
      <div class="sticky top-14 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-4 h-11 shrink-0">
        <div class="flex items-center h-full">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            class="h-full px-4 text-sm font-medium border-b-2 transition-colors"
            :class="activeTab === tab.key
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="activeTab = tab.key"
          >{{ tab.label }}</button>
        </div>
      </div>

      <!-- Drie-kolom body -->
      <div class="flex overflow-hidden" style="height: calc(100vh - 6.25rem)">

        <!-- ── Linker sidebar ── -->
        <aside class="w-60 shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-white dark:bg-gray-800 overflow-hidden">
          <!-- Blokken | Bibliotheek tabs -->
          <div class="flex border-b border-gray-200 dark:border-gray-700 shrink-0">
            <button
              v-for="t in ['blokken', 'bibliotheek']"
              :key="t"
              type="button"
              class="flex-1 py-2.5 text-xs font-semibold transition-colors"
              :class="sidebarTab === t ? 'text-blue-600 dark:text-blue-400 border-b-2 border-blue-500' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
              @click="sidebarTab = t"
            >{{ t.charAt(0).toUpperCase() + t.slice(1) }}</button>
          </div>

          <!-- Blokken -->
          <div v-if="sidebarTab === 'blokken'" class="flex-1 flex flex-col overflow-hidden">
            <div class="flex-1 overflow-y-auto p-3">
              <draggable
                :list="BLOK_TYPES"
                item-key="type"
                :group="{ name: 'sections', pull: 'clone', put: false }"
                :clone="cloneBlock"
                :sort="false"
                ghost-class="opacity-50"
              >
                <template #item="{ element: blok }">
                  <div class="flex items-center gap-3 px-3 py-2.5 mb-1.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 cursor-grab hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors select-none group">
                    <div class="w-7 h-7 rounded-lg bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 flex items-center justify-center shrink-0 group-hover:border-blue-300 dark:group-hover:border-blue-500 transition-colors">
                      <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors truncate">{{ blok.titel }}</p>
                      <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ blok.omschrijving }}</p>
                    </div>
                  </div>
                </template>
              </draggable>
            </div>
            <div class="m-3 mt-0 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40">
              <p class="text-xs text-blue-600 dark:text-blue-400 leading-relaxed">Sleep blokken naar de editor of laat AI ze voor je plaatsen.</p>
            </div>
          </div>

          <!-- Bibliotheek -->
          <div v-else class="flex-1 flex items-center justify-center p-6">
            <div class="text-center">
              <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
              </div>
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Bibliotheek</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Binnenkort beschikbaar</p>
            </div>
          </div>
        </aside>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- OPBOUW tab: compacte structuurlijst                           -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <template v-if="activeTab === 'opbouw'">
          <div class="w-[22rem] shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden">

            <div class="px-5 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
              <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Offerte structuur</h2>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Sleep blokken om de volgorde aan te passen</p>
            </div>

            <div class="flex-1 overflow-y-auto">
              <!-- Draggable secties -->
              <draggable
                v-model="editorSections"
                item-key="id"
                handle=".drag-handle"
                :group="{ name: 'sections', pull: false, put: true }"
                ghost-class="opacity-40"
                @update="saveReorder"
                @add="onAddFromSidebar"
              >
                <template #item="{ element: section }">
                  <div class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 group hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors">
                    <div class="drag-handle cursor-grab text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors shrink-0 active:cursor-grabbing">
                      <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 12 12"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>
                    </div>
                    <div class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 flex items-center justify-center shrink-0 group-hover:border-blue-200 dark:group-hover:border-blue-700 transition-colors">
                      <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ section.titel }}</p>
                      <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ blockOmschrijving(section) }}</p>
                    </div>
                    <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                      <button type="button" title="Bewerken" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors" @click="openBlockEditor(section)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      </button>
                      <button type="button" title="Verwijderen" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" @click="deleteSectionItem(section)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </div>
                </template>
              </draggable>

              <!-- Investering (vaste rij onderaan) -->
              <div class="flex items-center gap-3 px-4 py-3.5 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 group hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors">
                <div class="w-3.5 h-3.5 shrink-0" />
                <div class="w-8 h-8 rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                  <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">Investering</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 truncate">Investering blok</p>
                </div>
                <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                  <button type="button" title="Bewerken" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors" @click="activeTab = 'inhoud'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                </div>
              </div>

              <!-- Lege staat -->
              <div v-if="!editorSections.length" class="flex flex-col items-center justify-center py-12 px-6 text-center">
                <p class="text-sm text-gray-400 dark:text-gray-500">Sleep blokken vanuit het linkerpaneel om te beginnen.</p>
              </div>
            </div>

            <!-- + Blok toevoegen -->
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0 bg-white dark:bg-gray-800">
              <button type="button" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-600 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="sidebarTab = 'blokken'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Blok toevoegen
              </button>
            </div>
          </div>

          <!-- Rechts: PDF preview -->
          <div ref="rightPdfContainer" class="flex-1 flex flex-col bg-gray-100 dark:bg-gray-950 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-2.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
              <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Preview</span>
              <div class="flex items-center gap-0.5 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <button v-for="d in [{ key:'desktop', label:'Desktop' }, { key:'tablet', label:'Tablet' }, { key:'mobiel', label:'Mobiel' }]" :key="d.key" type="button" class="px-3 py-1 rounded-md text-xs font-medium transition-colors" :class="previewDevice === d.key ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'" @click="previewDevice = d.key; updateRightPdfWidth()">{{ d.label }}</button>
              </div>
              <span class="text-xs text-gray-400 dark:text-gray-500">Pagina 1 van {{ rightPdfPageCount || '…' }}</span>
            </div>
            <div class="flex-1 overflow-y-auto flex justify-center p-6">
              <div v-if="rightPdfLoading" class="flex flex-col items-center justify-center mt-20 gap-3">
                <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin" />
                <p class="text-sm text-gray-400 dark:text-gray-500">PDF laden…</p>
              </div>
              <div v-else-if="!rightPdfData" class="flex flex-col items-center justify-center mt-20 gap-3">
                <p class="text-sm text-gray-400 dark:text-gray-500">Preview verschijnt na het opslaan.</p>
              </div>
              <VuePdfEmbed v-else :source="rightPdfData" :width="rightPdfWidth" class="pdf-preview shadow-2xl" @loaded="p => rightPdfPageCount = p.numPages" />
            </div>
          </div>
        </template>

        <!-- ══════════════════════════════════════════════════════════════ -->
        <!-- INHOUD tab: volledige editors                                 -->
        <!-- ══════════════════════════════════════════════════════════════ -->
        <div v-else-if="activeTab === 'inhoud'" class="flex-1 overflow-y-auto py-6">
          <div class="max-w-2xl mx-auto px-6 space-y-5">

            <!-- Secties editors -->
            <div
              v-for="section in editorSections"
              :key="section.id"
              :ref="el => sectionRefs[section.id] = el"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6"
            >
              <div class="flex items-center gap-2" :class="section.collapsed ? '' : 'mb-4'">
                <button type="button" class="p-1 rounded text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shrink-0" @click="section.collapsed = !section.collapsed">
                  <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="section.collapsed ? '-rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex-1 min-w-0 truncate cursor-pointer" @click="section.collapsed = !section.collapsed">{{ section.titel }}</h3>
                <span class="text-xs shrink-0" :class="statusClass(section.status)">{{ statusLabel(section.status) }}</span>
                <button v-if="section.hasAi" type="button" class="inline-flex items-center gap-1 text-xs text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors shrink-0" @click="restoreAi(section)">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  AI herschrijven
                </button>
                <button type="button" class="p-1 rounded text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0" @click="deleteSectionItem(section)">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
              <div v-show="!section.collapsed">
                <RichTextEditor
                  v-model="section.html"
                  :placeholder="`Schrijf hier de ${section.titel.toLowerCase()}…`"
                  @update:model-value="onSectionChange(section)"
                  @blur="saveOnBlur(section)"
                />
              </div>
            </div>

            <div v-if="!editorSections.length" class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 py-12 text-center">
              <p class="text-sm text-gray-400 dark:text-gray-500">Sleep een blok vanuit het linkerpaneel (Opbouw tab) om te beginnen.</p>
            </div>

            <!-- Investering -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
              <div class="flex items-center gap-2" :class="invCollapsed ? '' : 'mb-5'">
                <button type="button" class="p-1 rounded text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shrink-0" @click="invCollapsed = !invCollapsed">
                  <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="invCollapsed ? '-rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white flex-1 cursor-pointer" @click="invCollapsed = !invCollapsed">Investering</h3>
              </div>

              <div v-show="!invCollapsed">
                <div class="mb-3">
                  <draggable v-model="editorInvRows" item-key="_key" handle=".inv-drag-handle" ghost-class="opacity-40" class="space-y-2">
                    <template #item="{ element: row, index: i }">
                      <div class="flex gap-2 items-center">
                        <div class="inv-drag-handle p-1 cursor-grab rounded text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors shrink-0 active:cursor-grabbing">
                          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 12 12"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>
                        </div>
                        <input v-model="row.omschrijving" type="text" placeholder="Omschrijving" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        <div class="relative">
                          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">€</span>
                          <input v-model="row.bedrag" type="text" inputmode="decimal" placeholder="0,00" class="w-32 pl-7 pr-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm text-right focus:outline-none focus:ring-1 focus:ring-blue-500" />
                        </div>
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0" :class="editorInvRows.length === 1 ? 'opacity-0 pointer-events-none' : ''" @click="removeEditorRow(i)">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </div>
                    </template>
                  </draggable>
                  <button type="button" class="mt-2 w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 py-2.5 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="addEditorRow">+ Regel toevoegen</button>
                </div>

                <div class="flex items-center gap-3 mt-5">
                  <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">BTW:</span>
                  <div class="flex gap-1.5">
                    <button v-for="optie in BTW_OPTIES" :key="optie" type="button" class="px-4 py-1.5 rounded-full text-sm border transition-colors" :class="editorBtw === optie ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-400'" @click="editorBtw = optie">{{ optie }}</button>
                  </div>
                </div>

                <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 text-sm overflow-hidden">
                  <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300"><span>Subtotaal</span><span>{{ fmt(editorSubtotaal) }}</span></div>
                  <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300"><span>BTW ({{ editorBtw }})</span><span>{{ editorBtw === '21%' ? fmt(editorBtwBedrag) : '—' }}</span></div>
                  <div class="flex justify-between px-5 py-3.5 font-semibold text-gray-900 dark:text-white"><span>Totaal</span><span class="text-blue-600 dark:text-blue-400">{{ fmt(editorEindtotaal) }}</span></div>
                </div>

                <div class="pt-5 flex items-center gap-3">
                  <button type="button" :disabled="saving" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors" @click="saveEditor">
                    {{ saving ? 'Opslaan…' : 'Offerte opslaan' }}
                  </button>
                  <p v-if="editorSaveError" class="text-xs text-red-500">{{ editorSaveError }}</p>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>

    <!-- ── Stappen 1+2+3: in editor shell ────────────────────────────────── -->
    <template v-else>

      <!-- Info balk -->
      <div class="sticky top-14 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-4 h-11 shrink-0">
        <span class="text-sm text-gray-500 dark:text-gray-400">
          {{ currentStep === 1 ? 'Selecteer de klant waarvoor je een offerte maakt.'
           : currentStep === 2 ? 'Vul de projectdetails in om de AI te starten.'
           : currentStep === 3 ? 'De AI genereert de offertetekst op basis van jouw input.'
           : 'Vul de investering in om door te gaan naar de editor.' }}
        </span>
      </div>

      <div class="flex overflow-hidden" style="height: calc(100vh - 6.25rem)">

        <!-- Linker sidebar (decoratief) -->
        <aside class="w-60 shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-white dark:bg-gray-800 overflow-hidden">
          <div class="flex border-b border-gray-200 dark:border-gray-700 shrink-0">
            <div class="flex-1 py-2.5 text-xs font-semibold text-center text-blue-600 dark:text-blue-400 border-b-2 border-blue-500">Blokken</div>
            <div class="flex-1 py-2.5 text-xs font-semibold text-center text-gray-400 dark:text-gray-500">Bibliotheek</div>
          </div>
          <div class="flex-1 overflow-y-auto p-3">
            <div
              v-for="blok in BLOK_TYPES"
              :key="blok.type"
              class="flex items-center gap-3 px-3 py-2.5 mb-1.5 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 opacity-50 select-none"
            >
              <div class="w-7 h-7 rounded-lg bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 flex items-center justify-center shrink-0">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate">{{ blok.titel }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ blok.omschrijving }}</p>
              </div>
            </div>
          </div>
          <div class="m-3 mt-0 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40">
            <p class="text-xs text-blue-600 dark:text-blue-400 leading-relaxed">Blokken worden beschikbaar na het genereren.</p>
          </div>
        </aside>

        <!-- Midden: formulier per stap -->
        <div class="flex-1 overflow-y-auto py-8">
          <div class="max-w-xl mx-auto px-6">

            <!-- Stap 1: Klant selecteren -->
            <div v-if="currentStep === 1">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Selecteer een klant</h2>
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <Step1Client v-model="wizard.clientId" v-model:selected-client="wizard.selectedClient" :sectoren="sectoren" />
              </div>
            </div>

            <!-- Stap 2: Project intake -->
            <div v-else-if="currentStep === 2">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Project details</h2>
              <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">voor <span class="font-medium text-gray-600 dark:text-gray-300">{{ wizard.selectedClient?.naam }}</span></p>
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <Step2Intake v-model="step2Data" :diensten-lijst="dienstenLijst" :errors="step2Errors" />
              </div>
            </div>

            <!-- Stap 3: AI Generatie -->
            <div v-else-if="currentStep === 3">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">AI Generatie</h2>
              <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">De AI schrijft de offerte op basis van jouw projectdetails.</p>
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                <Step3Generate :quote-id="wizard.quoteId" @sections-saved="onGenerationDone" />
              </div>
            </div>

            <!-- Stap 4: Investering -->
            <div v-else-if="currentStep === 4">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Investering</h2>
              <p class="text-sm text-gray-400 dark:text-gray-500 mb-6">Vul de bedragen in voor deze offerte.</p>
              <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 space-y-4">
                <draggable v-model="editorInvRows" item-key="_key" handle=".inv-drag-handle" ghost-class="opacity-40" class="space-y-2">
                  <template #item="{ element: row, index: i }">
                    <div class="flex gap-2 items-center">
                      <div class="inv-drag-handle p-1 cursor-grab rounded text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors shrink-0 active:cursor-grabbing">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 12 12"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>
                      </div>
                      <input v-model="row.omschrijving" type="text" placeholder="Omschrijving" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" />
                      <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">€</span>
                        <input v-model="row.bedrag" type="text" inputmode="decimal" placeholder="0,00" class="w-32 pl-7 pr-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm text-right focus:outline-none focus:ring-1 focus:ring-blue-500" />
                      </div>
                      <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0" :class="editorInvRows.length === 1 ? 'opacity-0 pointer-events-none' : ''" @click="removeEditorRow(i)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                      </button>
                    </div>
                  </template>
                </draggable>
                <button type="button" class="w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 py-2.5 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="addEditorRow">+ Regel toevoegen</button>

                <div class="flex items-center gap-3 pt-2">
                  <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">BTW:</span>
                  <div class="flex gap-1.5">
                    <button v-for="optie in BTW_OPTIES" :key="optie" type="button" class="px-4 py-1.5 rounded-full text-sm border transition-colors" :class="editorBtw === optie ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-400'" @click="editorBtw = optie">{{ optie }}</button>
                  </div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 text-sm overflow-hidden">
                  <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300"><span>Subtotaal</span><span>{{ fmt(editorSubtotaal) }}</span></div>
                  <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300"><span>BTW ({{ editorBtw }})</span><span>{{ editorBtw === '21%' ? fmt(editorBtwBedrag) : '—' }}</span></div>
                  <div class="flex justify-between px-5 py-3.5 font-semibold text-gray-900 dark:text-white"><span>Totaal</span><span class="text-blue-600 dark:text-blue-400">{{ fmt(editorEindtotaal) }}</span></div>
                </div>

                <p v-if="editorSaveError" class="text-xs text-red-500">{{ editorSaveError }}</p>
              </div>
            </div>

            <!-- Navigatie -->
            <div class="flex items-center justify-between mt-6">
              <button
                v-if="currentStep > 1"
                type="button"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                @click="prev"
              >← Vorige</button>
              <div v-else />
              <div class="flex items-center gap-3">
                <p v-if="step2SaveError && currentStep === 2" class="text-xs text-red-600 dark:text-red-400">{{ step2SaveError }}</p>
                <p v-else-if="validationMessage && currentStep < 3" class="text-xs text-amber-600 dark:text-amber-400">{{ validationMessage }}</p>
                <button
                  v-if="currentStep === 1 || currentStep === 2"
                  type="button"
                  :disabled="!canProceed || saving"
                  class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  @click="next"
                >{{ saving ? 'Opslaan…' : currentStep === 2 ? 'Genereren →' : 'Volgende →' }}</button>
                <button
                  v-else-if="currentStep === 4"
                  type="button"
                  :disabled="!editorInvCanProceed || saving"
                  class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  @click="saveInvAndProceed"
                >{{ saving ? 'Opslaan…' : 'Naar editor →' }}</button>
              </div>
            </div>

          </div>
        </div>

      </div>
    </template>

  </AuthenticatedLayout>
</template>

<style>
.pdf-preview > div:not(:last-child) {
  border-bottom: 14px solid #111;
}
</style>
