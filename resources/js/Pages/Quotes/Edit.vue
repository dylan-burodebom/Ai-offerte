<script setup>
import { ref, reactive, computed, nextTick, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import draggable from 'vuedraggable'
import { defineAsyncComponent } from 'vue'
const VuePdfEmbed = defineAsyncComponent(() => import('vue-pdf-embed'))

const props = defineProps({
  quote: { type: Object, required: true },
})

// ── Tabs ──────────────────────────────────────────────────────────────────────
const activeTab     = ref('opbouw')
const previewDevice = ref('desktop')

const TABS = [
  { key: 'opbouw',       label: 'Opbouw' },
  { key: 'inhoud',       label: 'Inhoud' },
  { key: 'instellingen', label: 'Instellingen' },
  { key: 'activiteiten', label: 'Activiteiten' },
]

// ── Blok types (linkerpaneel) ─────────────────────────────────────────────────
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

// ── Meta ──────────────────────────────────────────────────────────────────────
const meta = reactive({
  titel:           props.quote.titel,
  geldig_tot:      props.quote.geldig_tot?.slice(0, 10) ?? '',
  pdf_blok_ruimte: props.quote.pdf_blok_ruimte ?? 10,
  inv_volgorde:    props.quote.inv_volgorde ?? 9999,
})
const metaStatus = ref(null)
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

async function saveAll() {
  metaStatus.value = 'saving'
  try {
    await window.axios.patch(route('quotes.meta', props.quote.id), meta)
    await window.axios.post(route('quotes.investments', props.quote.id), {
      rows: rows.value.map(r => ({
        omschrijving: r.omschrijving,
        bedrag: parseFloat(String(r.bedrag).replace(',', '.')) || 0,
      })),
      btw_tarief: btw.value,
    })
    metaStatus.value = 'saved'
    setTimeout(() => { metaStatus.value = null }, 2000)
  } catch {
    metaStatus.value = 'error'
  }
}

// ── Collapsed state ───────────────────────────────────────────────────────────
const COLLAPSED_KEY = `q_${props.quote.id}_collapsed`
function loadCollapsed() {
  try { return JSON.parse(localStorage.getItem(COLLAPSED_KEY) || '{}') } catch { return {} }
}
function isCollapsed(id) { return loadCollapsed()[id] ?? false }
function saveCollapsed(id, value) {
  try { const s = loadCollapsed(); s[id] = value; localStorage.setItem(COLLAPSED_KEY, JSON.stringify(s)) } catch {}
}
function toggleCollapsed(block) {
  block.collapsed = !block.collapsed
  saveCollapsed(block.id, block.collapsed)
}

// ── Secties ───────────────────────────────────────────────────────────────────
const sections = ref(
  props.quote.sections.map(s => ({
    ...s,
    html:             s.content?.html ?? '',
    hasAi:            !!(s.content?.ai_html),
    blockType:        s.content?.block_type ?? null,
    status:           null,
    collapsed:        isCollapsed(s.id),
  }))
)

function buildBlocks() {
  const items = sections.value.map(s => ({ ...s, _type: 'section' }))
  const pos = Math.min(meta.inv_volgorde, items.length)
  items.splice(pos, 0, { _type: 'investering', id: '__inv__', titel: 'Investering', collapsed: isCollapsed('__inv__') })
  return items
}

const blocks = ref(buildBlocks())

function syncSectionsFromBlocks() {
  sections.value = blocks.value.filter(b => b._type !== 'investering').map(({ _type, ...s }) => s)
}

const sectionRefs = {}

function openBlockEditor(block) {
  activeTab.value = 'inhoud'
  setTimeout(() => {
    const el = sectionRefs[block.id]
    if (!el) return
    const container = el.closest('.overflow-y-auto')
    if (container) {
      container.scrollTo({ top: el.offsetTop - 24, behavior: 'smooth' })
    } else {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 80)
}

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

async function saveTitel(section) {
  if (!section.titel?.trim()) return
  try {
    await window.axios.patch(
      route('quotes.sections.update', { quote: props.quote.id, section: section.id }),
      { titel: section.titel }
    )
  } catch {}
}

async function restoreAi(section) {
  if (!confirm(`"${section.titel}" terugzetten naar de AI-versie?`)) return
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

async function saveReorder() {
  const order = sections.value.filter(s => s.id && !String(s.id).startsWith('temp_')).map(s => s.id)
  try { await window.axios.patch(route('quotes.sections.reorder', props.quote.id), { order }) } catch {}
}

async function onBlocksDragged() {
  await nextTick()
  syncSectionsFromBlocks()
  const invIdx = blocks.value.findIndex(b => b._type === 'investering')
  meta.inv_volgorde = invIdx
  await saveReorder()
  try {
    await window.axios.patch(route('quotes.meta', props.quote.id), {
      titel: meta.titel, geldig_tot: meta.geldig_tot || null,
      pdf_blok_ruimte: meta.pdf_blok_ruimte, inv_volgorde: invIdx,
    })
  } catch {}
}

function cloneBlock(block) {
  return { _type: 'section', id: `temp_${Date.now()}`, titel: block.titel, type: block.type, blockType: block.type, html: '', hasAi: false, status: null, collapsed: false, _pending: true }
}

async function onAddFromSidebar(event) {
  const idx  = event.newIndex
  const item = blocks.value[idx]
  item.status = 'saving'
  const volgorde = blocks.value.slice(0, idx).filter(b => b._type !== 'investering').length
  try {
    const res = await window.axios.post(route('quotes.sections.store', props.quote.id), { titel: item.titel, volgorde, block_type: item.blockType ?? null })
    item.id = res.data.id; item._pending = false; item.status = null
    syncSectionsFromBlocks()
    await saveReorder()
  } catch {
    blocks.value.splice(idx, 1)
    syncSectionsFromBlocks()
  }
}

async function deleteSectionItem(section) {
  if (!confirm(`"${section.titel}" verwijderen?`)) return
  try {
    await window.axios.delete(route('quotes.sections.destroy', { quote: props.quote.id, section: section.id }))
    const idx = blocks.value.findIndex(b => b.id === section.id)
    if (idx !== -1) blocks.value.splice(idx, 1)
    syncSectionsFromBlocks()
  } catch {
    alert('Fout bij verwijderen sectie.')
  }
}

async function duplicateBlock(block) {
  const idx = blocks.value.findIndex(b => b.id === block.id)
  const newBlock = { _type: 'section', id: `temp_${Date.now()}`, titel: block.titel + ' (kopie)', type: block.type, html: block.html || '', hasAi: false, status: null, collapsed: false, _pending: true }
  blocks.value.splice(idx + 1, 0, newBlock)
  syncSectionsFromBlocks()
  const volgorde = blocks.value.slice(0, idx + 1).filter(b => b._type !== 'investering').length
  try {
    const res = await window.axios.post(route('quotes.sections.store', props.quote.id), { titel: newBlock.titel, html: newBlock.html, volgorde })
    const actual = blocks.value.find(b => b.id === newBlock.id)
    if (actual) { actual.id = res.data.id; actual._pending = false }
    syncSectionsFromBlocks()
    await saveReorder()
  } catch {
    const di = blocks.value.findIndex(b => b.id === newBlock.id)
    if (di !== -1) blocks.value.splice(di, 1)
    syncSectionsFromBlocks()
  }
}

// ── Investeringen ─────────────────────────────────────────────────────────────
let _rowKey = 0
const rows = ref(
  (props.quote.investments.length
    ? props.quote.investments.map(i => ({ ...i, bedrag: String(i.bedrag) }))
    : [{ id: null, omschrijving: '', bedrag: '' }]
  ).map(r => ({ ...r, _key: ++_rowKey }))
)
const btw = ref(props.quote.btw_tarief ?? '21%')
const invStatus = ref(null)
let invTimer = null
const BTW_OPTIES = ['21%', '0%', 'vrijgesteld']

const subtotaal = computed(() => rows.value.reduce((s, r) => s + (parseFloat(String(r.bedrag).replace(',', '.')) || 0), 0))
const btwBedrag  = computed(() => btw.value === '21%' ? subtotaal.value * 0.21 : 0)
const eindtotaal = computed(() => subtotaal.value + btwBedrag.value)
const fmt = v => new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(v || 0)

function addRow() { rows.value.push({ id: null, omschrijving: '', bedrag: '', _key: ++_rowKey }) }
function removeRow(i) { if (rows.value.length > 1) rows.value.splice(i, 1) }

function onInvChange() {
  invStatus.value = null
  clearTimeout(invTimer)
  invTimer = setTimeout(saveInvestments, 3000)
}

async function saveInvestments() {
  invStatus.value = 'saving'
  try {
    await window.axios.post(route('quotes.investments', props.quote.id), {
      rows: rows.value.map(r => ({ omschrijving: r.omschrijving, bedrag: parseFloat(String(r.bedrag).replace(',', '.')) || 0 })),
      btw_tarief: btw.value,
    })
    invStatus.value = 'saved'
    setTimeout(() => { invStatus.value = null }, 2000)
  } catch {
    invStatus.value = 'error'
  }
}


// ── Status beheer ─────────────────────────────────────────────────────────────
const GELDIGE_OVERGANGEN = { concept: ['verzonden'], verzonden: ['gewonnen', 'verloren', 'concept'], gewonnen: [], verloren: [] }
const huidigStatus  = ref(props.quote.status)
const statusHistory = ref(props.quote.status_history ?? [])
const statusSaving  = ref(false)
const statusError   = ref(null)

const huidigeReden = computed(() => {
  if (!['gewonnen', 'verloren'].includes(huidigStatus.value)) return null
  return statusHistory.value.find(h => h.nieuwe_status === huidigStatus.value && h.reden)?.reden ?? null
})

const redenPanel = ref(null)
const redenTekst = ref('')
function kanNaar(status) { return (GELDIGE_OVERGANGEN[huidigStatus.value] ?? []).includes(status) }

async function updateStatus(nieuweStatus, reden = null) {
  statusSaving.value = true; statusError.value = null
  const prevStatus  = huidigStatus.value
  const prevHistory = [...statusHistory.value]
  huidigStatus.value = nieuweStatus  // optimistisch updaten — voelt instant
  redenPanel.value = null; redenTekst.value = ''
  try {
    const res = await window.axios.patch(route('quotes.status', props.quote.id), { status: nieuweStatus, reden: reden || null })
    statusHistory.value = res.data.status_history
  } catch (e) {
    huidigStatus.value = prevStatus  // rollback
    statusHistory.value = prevHistory
    redenPanel.value = nieuweStatus.includes('gewonnen') || nieuweStatus.includes('verloren') ? nieuweStatus : null
    statusError.value = e.response?.data?.message ?? 'Fout bij statuswijziging.'
  } finally {
    statusSaving.value = false
  }
}

function openRedenPanel(status) { redenPanel.value = status; redenTekst.value = '' }
function cancelReden() { redenPanel.value = null; redenTekst.value = '' }
function bevestigReden() { if (redenPanel.value) updateStatus(redenPanel.value, redenTekst.value) }

// ── PDF modal ─────────────────────────────────────────────────────────────────
const pdfPreviewOpen = ref(false)
const pdfData        = ref(null)
const pdfLoading     = ref(false)
const pdfPageCount   = ref(0)
const pdfContainer   = ref(null)
const pdfViewerWidth = ref(850)

function updatePdfWidth() {
  if (pdfContainer.value) pdfViewerWidth.value = Math.min(pdfContainer.value.clientWidth - 48, 1000)
}

async function openPdfPreview() {
  pdfPreviewOpen.value = true; pdfPageCount.value = 0
  await nextTick(); updatePdfWidth()
  if (pdfData.value) return
  pdfLoading.value = true
  try {
    const res = await window.axios.get(route('quotes.pdf.preview', props.quote.id), { responseType: 'arraybuffer' })
    pdfData.value = new Uint8Array(res.data)
  } catch { alert('PDF kon niet worden geladen.'); pdfPreviewOpen.value = false }
  finally { pdfLoading.value = false }
}

// ── PDF rechter paneel ────────────────────────────────────────────────────────
const rightPdfData      = ref(null)
const rightPdfLoading   = ref(false)
const rightPdfPageCount = ref(0)
const rightPdfContainer = ref(null)
const rightPdfWidth     = ref(800)

function updateRightPdfWidth() {
  if (!rightPdfContainer.value) return
  const w = rightPdfContainer.value.clientWidth - 48
  rightPdfWidth.value = previewDevice.value === 'desktop' ? w : previewDevice.value === 'tablet' ? Math.min(550, w) : Math.min(375, w)
}

async function loadRightPdf() {
  if (rightPdfLoading.value || rightPdfData.value) return
  rightPdfLoading.value = true
  try {
    const res = await window.axios.get(route('quotes.pdf.preview', props.quote.id), { responseType: 'arraybuffer' })
    rightPdfData.value = new Uint8Array(res.data)
  } catch {} finally { rightPdfLoading.value = false }
}

onMounted(() => {
  window.addEventListener('resize', updatePdfWidth)
  window.addEventListener('resize', updateRightPdfWidth)
  loadRightPdf()
  nextTick(updateRightPdfWidth)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', updatePdfWidth)
  window.removeEventListener('resize', updateRightPdfWidth)
})

watch(activeTab, (newTab) => {
  if (newTab === 'opbouw') {
    rightPdfData.value = null
    nextTick(() => {
      updateRightPdfWidth()
      loadRightPdf()
    })
  }
})

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
function formatDatum(iso) {
  if (!iso) return ''
  return new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(iso))
}
function blockOmschrijving(block) {
  if (block._type === 'investering') return 'Investering blok'
  return BLOK_TYPES.find(b => b.type === block.type)?.omschrijving ?? 'Tekstblok'
}
</script>

<template>
  <Head :title="quote.offerte_nummer" />
  <AuthenticatedLayout>

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <template #header>
      <div class="flex items-center justify-between w-full gap-3">

        <!-- Breadcrumb + status -->
        <div class="flex items-center gap-2 min-w-0">
          <a :href="route('quotes.index')" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors shrink-0">Offertes</a>
          <svg class="w-3.5 h-3.5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          <span class="text-sm font-semibold font-mono text-gray-800 dark:text-white truncate">{{ quote.offerte_nummer }}</span>
          <StatusBadge :status="huidigStatus" />
          <span v-if="huidigeReden" class="text-xs px-2 py-0.5 rounded-full italic max-w-[140px] truncate shrink-0" :class="huidigStatus === 'gewonnen' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400'" :title="huidigeReden">{{ huidigeReden }}</span>
        </div>

        <!-- Action buttons -->
        <div class="flex items-center gap-1.5 shrink-0">
          <!-- Save status indicator -->
          <span class="text-xs mr-1" :class="statusClass(metaStatus)">{{ statusLabel(metaStatus) }}</span>

          <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" @click="openPdfPreview">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Preview
          </button>
          <a :href="route('quotes.pdf', quote.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            PDF
          </a>
          <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" @click="saveAll">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Opslaan
          </button>
          <button type="button" :disabled="versioning" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors" @click="createVersion">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            {{ versioning ? 'Bezig…' : 'Nieuwe versie' }}
          </button>
        </div>

      </div>
    </template>

    <!-- ── Tab bar ────────────────────────────────────────────────────────── -->
    <div class="sticky top-14 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-4 h-11 shrink-0">
      <!-- Tabs -->
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

    <!-- ── Drie-kolom editor body ──────────────────────────────────────────── -->
    <div class="flex overflow-hidden" style="height: calc(100vh - 6.25rem)">

      <!-- ── LINKER SIDEBAR (opbouw & inhoud tabs) ── -->
      <aside
        v-if="activeTab === 'opbouw' || activeTab === 'inhoud'"
        class="w-60 shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-white dark:bg-gray-800 overflow-hidden"
      >
        <!-- Blokken tab -->
        <div class="flex-1 flex flex-col overflow-hidden">
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
                  <!-- Icon placeholder -->
                  <div class="w-7 h-7 rounded-lg bg-white dark:bg-gray-600 border border-gray-200 dark:border-gray-500 flex items-center justify-center shrink-0 group-hover:border-blue-300 dark:group-hover:border-blue-500 transition-colors">
                    <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-400 group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors truncate">{{ blok.titel }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ blok.omschrijving }}</p>
                  </div>
                </div>
              </template>
            </draggable>
          </div>
          <!-- Tip box -->
          <div class="m-3 mt-0 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/40">
            <p class="text-xs text-blue-600 dark:text-blue-400 leading-relaxed">Sleep blokken naar de editor of laat AI ze voor je plaatsen.</p>
          </div>
        </div>

      </aside>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- OPBOUW tab: compacte blokkenlijst + preview                       -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <template v-if="activeTab === 'opbouw'">

        <!-- Midden: Offerte structuur -->
        <div class="w-[22rem] shrink-0 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900 overflow-hidden">

          <!-- Header -->
          <div class="px-5 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Offerte structuur</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Sleep blokken om de volgorde aan te passen</p>
          </div>

          <!-- Draggable compact block list -->
          <div class="flex-1 overflow-y-auto">
            <draggable
              v-model="blocks"
              item-key="id"
              handle=".drag-handle"
              :group="{ name: 'sections', pull: false, put: true }"
              ghost-class="opacity-40"
              @update="onBlocksDragged"
              @add="onAddFromSidebar"
            >
              <template #item="{ element: block }">
                <div
                  class="flex items-center gap-3 px-4 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 group transition-colors"
                  :class="block.blockType === 'einde_pagina'
                    ? 'py-2'
                    : 'py-3.5 hover:bg-blue-50 dark:hover:bg-blue-900/10 cursor-default'"
                >
                  <!-- Drag handle (shared) -->
                  <div class="drag-handle cursor-grab text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors shrink-0 active:cursor-grabbing">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 12 12"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>
                  </div>

                  <!-- Einde pagina inhoud -->
                  <template v-if="block.blockType === 'einde_pagina'">
                    <div class="flex-1 flex items-center gap-2">
                      <div class="flex-1 border-t-2 border-dashed border-gray-300 dark:border-gray-600" />
                      <span class="text-xs font-medium text-gray-400 dark:text-gray-500 shrink-0">Einde pagina</span>
                      <div class="flex-1 border-t-2 border-dashed border-gray-300 dark:border-gray-600" />
                    </div>
                    <button type="button" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors opacity-0 group-hover:opacity-100 shrink-0" @click="deleteSectionItem(block)">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </template>

                  <!-- Normaal blok inhoud -->
                  <template v-else>
                  <div class="w-8 h-8 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 flex items-center justify-center shrink-0 group-hover:border-blue-200 dark:group-hover:border-blue-700 transition-colors">
                    <svg v-if="block._type === 'investering'" class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg v-else class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ block.titel }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ blockOmschrijving(block) }}</p>
                  </div>
                  <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                    <button v-if="block._type === 'section'" type="button" title="Bewerken" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors" @click="openBlockEditor(block)">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button v-if="block._type === 'investering'" type="button" title="Bewerken" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors" @click="activeTab = 'inhoud'">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button v-if="block._type === 'section'" type="button" title="Dupliceren" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="duplicateBlock(block)">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </button>
                    <button v-if="block._type === 'section'" type="button" title="Verwijderen" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" @click="deleteSectionItem(block)">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                  </template>
                </div>
              </template>
            </draggable>

            <!-- Empty state -->
            <div v-if="!sections.length && blocks.length <= 1" class="flex flex-col items-center justify-center py-16 px-6 text-center">
              <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
              </div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Geen blokken</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Sleep blokken vanuit het linkerpaneel</p>
            </div>
          </div>

          <!-- + Blok toevoegen -->
          <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0 bg-white dark:bg-gray-800">
            <button
              type="button"
              class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-600 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
              @click="sidebarTab = 'blokken'"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
              Blok toevoegen
            </button>
          </div>
        </div>

        <!-- Rechts: PDF preview -->
        <div ref="rightPdfContainer" class="flex-1 flex flex-col bg-gray-100 dark:bg-gray-950 overflow-hidden">
          <!-- Preview header -->
          <div class="flex items-center justify-between px-5 py-2.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shrink-0">
            <!-- Preview refresh knop -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
              :class="rightPdfLoading ? 'pointer-events-none text-blue-500 border-blue-300 dark:border-blue-700' : ''"
              @click="rightPdfData = null; loadRightPdf()"
            >
              <svg class="w-3.5 h-3.5" :class="rightPdfLoading ? 'animate-spin' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              Preview
            </button>
            <!-- Device toggle -->
            <div class="flex items-center gap-0.5 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
              <button
                v-for="d in [{ key:'desktop', label:'Desktop' }, { key:'tablet', label:'Tablet' }, { key:'mobiel', label:'Mobiel' }]"
                :key="d.key"
                type="button"
                class="px-3 py-1 rounded-md text-xs font-medium transition-colors"
                :class="previewDevice === d.key ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                @click="previewDevice = d.key; updateRightPdfWidth()"
              >{{ d.label }}</button>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500">Pagina 1 van {{ rightPdfPageCount || '…' }}</span>
          </div>
          <!-- PDF content -->
          <div class="flex-1 overflow-y-auto flex justify-center p-6">
            <div v-if="rightPdfLoading" class="flex flex-col items-center justify-center mt-20 gap-3">
              <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
              <p class="text-sm text-gray-400 dark:text-gray-500">PDF laden…</p>
            </div>
            <div v-else-if="!rightPdfData" class="flex flex-col items-center justify-center mt-20 gap-3">
              <p class="text-sm text-gray-400 dark:text-gray-500">Geen preview beschikbaar.</p>
            </div>
            <VuePdfEmbed
              v-else
              :source="rightPdfData"
              :width="rightPdfWidth"
              class="pdf-preview shadow-2xl"
              @loaded="p => rightPdfPageCount = p.numPages"
            />
          </div>
        </div>

      </template>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- INHOUD tab: volledige editors                                     -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'inhoud'" class="flex-1 overflow-y-auto py-6">
        <div class="max-w-2xl mx-auto px-6 space-y-5">

          <!-- Reden panel -->
          <div v-if="redenPanel" class="rounded-xl border-2 p-5 space-y-3" :class="redenPanel === 'gewonnen' ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20' : 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20'">
            <h3 class="text-sm font-semibold" :class="redenPanel === 'gewonnen' ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400'">
              {{ redenPanel === 'gewonnen' ? 'Offerte gewonnen' : 'Offerte verloren' }} — wat is de reden?
            </h3>
            <input v-model="redenTekst" type="text" maxlength="500" :placeholder="redenPanel === 'gewonnen' ? 'Bijv. prijs, snelheid, vertrouwen…' : 'Bijv. klant koos voor lagere prijs…'" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" @keydown.enter="bevestigReden" @keydown.esc="cancelReden" />
            <div class="flex items-center gap-2">
              <button type="button" :disabled="statusSaving || !redenTekst.trim()" class="px-4 py-1.5 rounded-lg text-sm font-medium text-white disabled:opacity-50 transition-colors" :class="redenPanel === 'gewonnen' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'" @click="bevestigReden">{{ statusSaving ? 'Opslaan…' : 'Bevestigen' }}</button>
              <button type="button" class="px-4 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="cancelReden">Annuleren</button>
            </div>
          </div>

          <p v-if="statusError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl px-4 py-3">{{ statusError }}</p>

          <!-- Block editors -->
          <draggable
            v-model="blocks"
            item-key="id"
            handle=".drag-handle"
            ghost-class="opacity-40"
            class="space-y-5"
            @update="onBlocksDragged"
          >
          <template #item="{ element: block }">
          <div
            :ref="block._type === 'section' ? (el => { sectionRefs[block.id] = el }) : undefined"
            class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6"
          >
            <!-- Header -->
            <div class="flex items-center gap-2" :class="block.collapsed ? '' : 'mb-4'">
              <div class="drag-handle p-1 cursor-grab rounded text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shrink-0 active:cursor-grabbing">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 12 12"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>
              </div>
              <button type="button" class="p-1 rounded text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shrink-0" @click="toggleCollapsed(block)">
                <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="block.collapsed ? '-rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div v-if="block.blockType === 'custom' && block._type === 'section'" class="flex items-center gap-1.5 flex-1 min-w-0 group/title">
                <input
                  v-model="block.titel"
                  type="text"
                  class="text-base font-semibold text-gray-900 dark:text-white flex-1 min-w-0 bg-transparent border-b border-dashed border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-400 focus:border-blue-500 focus:border-solid focus:outline-none px-0"
                  placeholder="Bloktitel…"
                  @blur="saveTitel(block)"
                  @keydown.enter.prevent="$event.target.blur()"
                />
                <svg class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover/title:text-gray-400 dark:group-hover/title:text-gray-400 shrink-0 transition-colors pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
              </div>
              <h3 v-else class="text-base font-semibold text-gray-900 dark:text-white flex-1 min-w-0 truncate cursor-pointer" @click="toggleCollapsed(block)">{{ block.titel }}</h3>
              <span class="text-xs shrink-0" :class="statusClass(block._type === 'investering' ? invStatus : block.status)">{{ statusLabel(block._type === 'investering' ? invStatus : block.status) }}</span>
              <button v-if="block._type === 'section' && block.hasAi" type="button" class="inline-flex items-center gap-1 text-xs text-blue-500 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors shrink-0" @click="restoreAi(block)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                AI herschrijven
              </button>
              <button v-if="block._type === 'section'" type="button" class="p-1 rounded text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0" @click="deleteSectionItem(block)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- Investering content -->
            <template v-if="block._type === 'investering'">
              <div v-show="!block.collapsed">
                <div class="mb-3">
                  <draggable v-model="rows" item-key="_key" handle=".inv-drag-handle" ghost-class="opacity-40" class="space-y-2" @update="onInvChange">
                    <template #item="{ element: row, index: i }">
                      <div class="flex gap-2 items-center">
                        <div class="inv-drag-handle p-1 cursor-grab rounded text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 transition-colors shrink-0 active:cursor-grabbing">
                          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 12 12"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>
                        </div>
                        <input v-model="row.omschrijving" type="text" placeholder="Omschrijving" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" @input="onInvChange" @blur="saveInvestments" />
                        <div class="relative">
                          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400 pointer-events-none">€</span>
                          <input v-model="row.bedrag" type="text" inputmode="decimal" placeholder="0,00" class="w-32 pl-7 pr-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm text-right focus:outline-none focus:ring-1 focus:ring-blue-500" @input="onInvChange" @blur="saveInvestments" />
                        </div>
                        <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 dark:text-gray-600 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0" :class="rows.length === 1 ? 'opacity-0 pointer-events-none' : ''" @click="removeRow(i); onInvChange()">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </div>
                    </template>
                  </draggable>
                  <button type="button" class="mt-2 w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600 py-2.5 text-sm text-gray-400 dark:text-gray-500 hover:border-blue-400 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" @click="addRow">+ Regel toevoegen</button>
                </div>
                <div class="flex items-center gap-3 mt-5">
                  <span class="text-sm text-gray-600 dark:text-gray-300 font-medium">BTW:</span>
                  <div class="flex gap-1.5">
                    <button v-for="optie in BTW_OPTIES" :key="optie" type="button" class="px-4 py-1.5 rounded-full text-sm border transition-colors" :class="btw === optie ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-blue-400 dark:hover:border-blue-500'" @click="btw = optie; onInvChange()">{{ optie }}</button>
                  </div>
                </div>
                <div class="mt-5 rounded-xl border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700 text-sm overflow-hidden">
                  <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300"><span>Subtotaal</span><span>{{ fmt(subtotaal) }}</span></div>
                  <div class="flex justify-between px-5 py-3 text-gray-600 dark:text-gray-300"><span>BTW ({{ btw }})</span><span>{{ btw === '21%' ? fmt(btwBedrag) : '—' }}</span></div>
                  <div class="flex justify-between px-5 py-3.5 font-semibold text-gray-900 dark:text-white"><span>Totaal</span><span class="text-blue-600 dark:text-blue-400">{{ fmt(eindtotaal) }}</span></div>
                </div>
              </div>
            </template>

            <!-- Einde pagina marker -->
            <div v-else-if="block.blockType === 'einde_pagina'" v-show="!block.collapsed">
              <div class="flex items-center gap-3 px-4 py-6 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700">
                <div class="flex-1 border-t-2 border-dashed border-gray-300 dark:border-gray-600" />
                <span class="text-sm text-gray-400 dark:text-gray-500 shrink-0">Het volgende blok start op een nieuwe pagina</span>
                <div class="flex-1 border-t-2 border-dashed border-gray-300 dark:border-gray-600" />
              </div>
            </div>

            <!-- Sectie content -->
            <div v-else v-show="!block.collapsed">
              <RichTextEditor
                v-model="block.html"
                :placeholder="`Schrijf hier de ${block.titel.toLowerCase()}…`"
                @update:model-value="onSectionChange(block)"
                @blur="saveOnBlur(block)"
              />
            </div>
          </div>
          </template>
          </draggable>

          <!-- Lege staat -->
          <div v-if="!sections.length" class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 py-12 text-center">
            <p class="text-sm text-gray-400 dark:text-gray-500">Sleep een blok vanuit het linkerpaneel (Opbouw tab) om te beginnen.</p>
          </div>

        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- INSTELLINGEN tab                                                  -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'instellingen'" class="flex-1 overflow-y-auto py-6">
        <div class="max-w-xl mx-auto px-6 space-y-5">

          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
            <div class="flex items-center justify-between mb-5">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Offerte details</h3>
            </div>
            <div class="grid grid-cols-2 gap-5 mb-5">
              <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Klant</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ quote.client?.naam }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ quote.client?.contactpersoon }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Geldig tot</p>
                <input v-model="meta.geldig_tot" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" @change="onMetaChange" />
              </div>
            </div>
            <div class="mb-5">
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Titel</p>
              <input v-model="meta.titel" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" @input="onMetaChange" />
            </div>
            <div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mb-1.5">Ruimte tussen blokken (PDF)</p>
              <div class="flex items-center gap-2">
                <input v-model.number="meta.pdf_blok_ruimte" type="number" min="0" max="60" class="w-24 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" @change="onMetaChange" />
                <span class="text-xs text-gray-400 dark:text-gray-500">mm</span>
              </div>
            </div>
            <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
              <span class="text-xs" :class="statusClass(metaStatus)">{{ statusLabel(metaStatus) }}</span>
              <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors disabled:opacity-50" :disabled="metaStatus === 'saving'" @click="saveMeta">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ metaStatus === 'saving' ? 'Opslaan…' : 'Opslaan' }}
              </button>
            </div>
          </div>


        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- ACTIVITEITEN tab                                                  -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <div v-else-if="activeTab === 'activiteiten'" class="flex-1 overflow-y-auto py-6">
        <div class="max-w-xl mx-auto px-6 space-y-5">

          <!-- Status kaart -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Status</h3>

            <!-- Huidige status: gewonnen of verloren -->
            <div
              v-if="huidigStatus === 'gewonnen' || huidigStatus === 'verloren'"
              class="flex items-start gap-4 p-4 rounded-xl mb-4"
              :class="huidigStatus === 'gewonnen' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'"
            >
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                :class="huidigStatus === 'gewonnen' ? 'bg-green-100 dark:bg-green-800/40' : 'bg-red-100 dark:bg-red-800/40'"
              >
                <svg v-if="huidigStatus === 'gewonnen'" class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <svg v-else class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold" :class="huidigStatus === 'gewonnen' ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'">
                  Offerte {{ huidigStatus }}
                </p>
                <p v-if="huidigeReden" class="text-xs mt-1 italic" :class="huidigStatus === 'gewonnen' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                  "{{ huidigeReden }}"
                </p>
                <p v-else class="text-xs mt-1" :class="huidigStatus === 'gewonnen' ? 'text-green-500 dark:text-green-500' : 'text-red-500 dark:text-red-500'">Geen reden opgegeven.</p>
              </div>
            </div>

            <p v-if="statusError" class="text-sm text-red-600 dark:text-red-400 mb-3">{{ statusError }}</p>

            <!-- Reden invoer -->
            <div v-if="redenPanel" class="rounded-xl border-2 p-4 space-y-3 mb-4" :class="redenPanel === 'gewonnen' ? 'border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20' : 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20'">
              <p class="text-sm font-semibold" :class="redenPanel === 'gewonnen' ? 'text-green-800 dark:text-green-400' : 'text-red-800 dark:text-red-400'">Reden voor {{ redenPanel }}</p>
              <input v-model="redenTekst" type="text" maxlength="500" placeholder="Geef een reden op…" class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" @keydown.enter="bevestigReden" @keydown.esc="cancelReden" />
              <div class="flex gap-2">
                <button type="button" :disabled="statusSaving || !redenTekst.trim()" class="px-4 py-1.5 rounded-lg text-sm font-medium text-white disabled:opacity-50" :class="redenPanel === 'gewonnen' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'" @click="bevestigReden">Bevestigen</button>
                <button type="button" class="px-4 py-1.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" @click="cancelReden">Annuleren</button>
              </div>
            </div>

            <!-- Actieknoppen -->
            <div class="flex flex-wrap gap-2">
              <button v-if="kanNaar('verzonden')" type="button" :disabled="statusSaving" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors" @click="updateStatus('verzonden')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Verzenden
              </button>
              <button v-if="kanNaar('concept')" type="button" :disabled="statusSaving" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors" @click="updateStatus('concept')">Terug naar concept</button>
              <button v-if="kanNaar('gewonnen')" type="button" :disabled="statusSaving" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 disabled:opacity-50 transition-colors" @click="openRedenPanel('gewonnen')">Gewonnen</button>
              <button v-if="kanNaar('verloren')" type="button" :disabled="statusSaving" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition-colors" @click="openRedenPanel('verloren')">Verloren</button>
            </div>
          </div>

          <!-- Status historie -->
          <div v-if="statusHistory.length" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
              <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Activiteiten</h3>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
              <li v-for="(item, i) in statusHistory" :key="i" class="flex items-start gap-3 px-6 py-3">
                <div class="mt-0.5 shrink-0"><StatusBadge :status="item.nieuwe_status" /></div>
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

          <div v-else class="text-center py-10">
            <p class="text-sm text-gray-400 dark:text-gray-500">Nog geen activiteiten geregistreerd.</p>
          </div>

        </div>
      </div>

    </div>
  </AuthenticatedLayout>

  <!-- ── PDF Preview Modal ─────────────────────────────────────────────────── -->
  <Teleport to="body">
    <div v-if="pdfPreviewOpen" class="fixed inset-0 z-50 flex flex-col bg-black/90" @keydown.esc="pdfPreviewOpen = false">
      <div class="flex items-center justify-between px-4 py-2 bg-gray-900 flex-shrink-0">
        <span class="text-white text-sm font-mono">{{ quote.offerte_nummer }} — PDF Preview</span>
        <div class="flex items-center gap-3">
          <span v-if="pdfPageCount" class="text-xs text-gray-400">{{ pdfPageCount }} pagina's</span>
          <a :href="route('quotes.pdf', quote.id)" class="text-xs px-3 py-1 rounded bg-gray-700 text-gray-200 hover:bg-gray-600 transition-colors">↓ Downloaden</a>
          <button type="button" class="text-gray-400 hover:text-white text-xl leading-none transition-colors" @click="pdfPreviewOpen = false">✕</button>
        </div>
      </div>
      <div ref="pdfContainer" class="flex-1 overflow-y-auto bg-[#3a3a3a] flex flex-col items-center py-8">
        <div v-if="pdfLoading" class="text-gray-200 text-sm mt-20">PDF laden…</div>
        <VuePdfEmbed v-else-if="pdfData" :source="pdfData" :width="pdfViewerWidth" class="pdf-preview" @loaded="p => pdfPageCount = p.numPages" />
      </div>
    </div>
  </Teleport>
</template>

<style>
.pdf-preview > div:not(:last-child) {
  border-bottom: 14px solid #111;
}
</style>
