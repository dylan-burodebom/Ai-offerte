<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  stijlgids:    { type: String,  default: '' },
  sectoren:     { type: Object,  default: () => ({}) },
  sectorLabels: { type: Object,  default: () => ({}) },
  meta:         { type: Object,  default: () => ({}) },
})

// ── Tabs ──────────────────────────────────────────────────────────────────────
const activeTab     = ref('stijlgids')
const activeSector  = ref(Object.keys(props.sectorLabels)[0] ?? 'bouw')

// ── Editor state ──────────────────────────────────────────────────────────────
const stijlgidsContent = ref(props.stijlgids)
const sectorContent    = ref({ ...props.sectoren })

const currentContent = computed({
  get: () => activeTab.value === 'stijlgids'
    ? stijlgidsContent.value
    : (sectorContent.value[activeSector.value] ?? ''),
  set: (v) => {
    if (activeTab.value === 'stijlgids') stijlgidsContent.value = v
    else sectorContent.value[activeSector.value] = v
  },
})

// ── Sectoren (dynamisch) ──────────────────────────────────────────────────────
const sectorLabelsRef  = ref({ ...props.sectorLabels })
const addingSector     = ref(false)
const newLabel         = ref('')
const newSlug          = computed(() =>
  newLabel.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '')
)
const sectorSaving     = ref(false)
const sectorError      = ref(null)

async function saveSector() {
  if (!newLabel.value.trim()) return
  sectorSaving.value = true
  sectorError.value  = null
  try {
    const res = await window.axios.post(route('admin.prompts.sector.store'), {
      slug:  newSlug.value,
      label: newLabel.value.trim(),
    })
    sectorLabelsRef.value             = res.data.sectoren
    sectorContent.value[newSlug.value] = ''
    activeSector.value                = newSlug.value
    versie.value                      = res.data.versie
    newLabel.value                    = ''
    addingSector.value                = false
  } catch (e) {
    sectorError.value = e.response?.data?.message ?? 'Toevoegen mislukt.'
  } finally {
    sectorSaving.value = false
  }
}

// ── Save ──────────────────────────────────────────────────────────────────────
const saving    = ref(false)
const versie    = ref(props.meta?.versie ?? '1.0')
const savedAt   = ref(null)
const saveError = ref(null)

async function save() {
  saving.value    = true
  saveError.value = null

  try {
    let res
    if (activeTab.value === 'stijlgids') {
      res = await window.axios.patch(route('admin.prompts.stijlgids'), {
        content: stijlgidsContent.value,
      })
    } else {
      res = await window.axios.patch(route('admin.prompts.sector', activeSector.value), {
        content: sectorContent.value[activeSector.value],
      })
    }
    versie.value  = res.data.versie
    savedAt.value = new Date().toLocaleTimeString('nl-NL')
  } catch (e) {
    saveError.value = 'Opslaan mislukt. Probeer het opnieuw.'
  } finally {
    saving.value = false
  }
}

// ── Preview ───────────────────────────────────────────────────────────────────
const previewOpen   = ref(false)
const previewSector = ref(activeSector.value)
const previewLoading = ref(false)
const previewResult  = ref(null)

async function loadPreview() {
  previewLoading.value = true
  previewResult.value  = null
  try {
    const res = await window.axios.post(route('admin.prompts.preview'), {
      sector: previewSector.value,
    })
    previewResult.value = res.data
  } finally {
    previewLoading.value = false
  }
}
</script>

<template>
  <Head title="Prompt Beheer" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full gap-4">
        <div class="flex items-center gap-2.5 min-w-0">
          <h2 class="text-base font-semibold text-gray-800 dark:text-white">Prompt Beheer</h2>
          <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-400">v{{ versie }}</span>
          <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
            <span v-if="savedAt && !saveError" class="text-xs text-green-600 dark:text-green-400 font-medium">
              Opgeslagen om {{ savedAt }}
            </span>
          </Transition>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            @click="previewOpen = !previewOpen"
          >
            <svg v-if="!previewOpen" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ previewOpen ? 'Preview sluiten' : 'Preview prompt' }}
          </button>
          <button
            type="button"
            :disabled="saving"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 disabled:opacity-50 transition-colors"
            @click="save"
          >
            <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ saving ? 'Opslaan…' : 'Opslaan' }}
          </button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-6xl mx-auto px-6 space-y-5">

        <!-- Foutmelding -->
        <p v-if="saveError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl px-4 py-3">
          {{ saveError }}
        </p>

        <div class="flex gap-6" :class="previewOpen ? 'items-start' : ''">

          <!-- Editor kolom -->
          <div class="flex-1 min-w-0 space-y-4">

            <!-- Tabs -->
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg w-fit">
              <button
                type="button"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                :class="activeTab === 'stijlgids'
                  ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                @click="activeTab = 'stijlgids'"
              >Stijlgids</button>
              <button
                type="button"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                :class="activeTab === 'sectoren'
                  ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                @click="activeTab = 'sectoren'"
              >Sectoren</button>
            </div>

            <!-- Sector kiezer -->
            <div v-if="activeTab === 'sectoren'" class="space-y-3">
              <div class="flex gap-2 flex-wrap items-center">
                <button
                  v-for="(label, slug) in sectorLabelsRef"
                  :key="slug"
                  type="button"
                  class="px-3.5 py-1.5 rounded-lg text-sm border font-medium transition-colors"
                  :class="activeSector === slug
                    ? 'bg-violet-600 text-white border-violet-600'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-600 hover:text-violet-700 dark:hover:text-violet-400'"
                  @click="activeSector = slug; addingSector = false"
                >{{ label }}</button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm border-2 border-dashed border-gray-200 dark:border-gray-600 text-gray-400 dark:text-gray-500 hover:border-violet-400 dark:hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                  @click="addingSector = !addingSector; newLabel = ''"
                >
                  <svg v-if="!addingSector" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                  </svg>
                  <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  {{ addingSector ? 'Annuleren' : 'Sector toevoegen' }}
                </button>
              </div>

              <!-- Nieuw sector form -->
              <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-to-class="opacity-0">
                <div v-if="addingSector" class="bg-white dark:bg-gray-800 border border-violet-200 dark:border-violet-700 rounded-xl p-4 space-y-3">
                  <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Nieuwe sector</p>
                  <div class="flex items-start gap-3">
                    <div class="flex-1 space-y-1.5">
                      <input
                        v-model="newLabel"
                        type="text"
                        placeholder="Naam (bijv. Retail)"
                        class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 text-sm focus:outline-none focus:ring-1 focus:ring-violet-500"
                        @keydown.enter.prevent="saveSector"
                      />
                      <p class="text-xs text-gray-400 font-mono pl-0.5">
                        slug: <span class="text-violet-600 dark:text-violet-400">{{ newSlug || '…' }}</span>
                      </p>
                      <p v-if="sectorError" class="text-xs text-red-500">{{ sectorError }}</p>
                    </div>
                    <button
                      type="button"
                      :disabled="sectorSaving || !newSlug"
                      class="px-3.5 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 disabled:opacity-50 transition-colors shrink-0"
                      @click="saveSector"
                    >{{ sectorSaving ? 'Bezig…' : 'Toevoegen' }}</button>
                  </div>
                </div>
              </Transition>
            </div>

            <!-- Uitleg -->
            <p class="text-xs text-gray-400 dark:text-gray-500">
              <template v-if="activeTab === 'stijlgids'">
                Dit is de <strong class="text-gray-600 dark:text-gray-300">system prompt</strong> — schrijfregels die de AI altijd krijgt, voor elke offerte.
              </template>
              <template v-else>
                Dit is de <strong class="text-gray-600 dark:text-gray-300">sectorcontext</strong> voor <em>{{ sectorLabelsRef[activeSector] }}</em> — extra instructies bovenop de stijlgids.
              </template>
              Elke opslag verhoogt de versie met 0.1.
            </p>

            <!-- Textarea editor -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
              <textarea
                :key="activeTab + activeSector"
                v-model="currentContent"
                rows="28"
                class="w-full px-5 py-4 text-sm font-mono bg-white dark:bg-gray-800 dark:text-gray-100 border-0 focus:outline-none focus:ring-0 resize-y leading-relaxed block"
                spellcheck="false"
              />
            </div>

          </div>

          <!-- Preview kolom -->
          <div v-if="previewOpen" class="w-96 shrink-0">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
              <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Prompt preview</h3>
              </div>
              <div class="p-4 space-y-3">
                <div class="flex gap-2 items-center">
                  <label class="text-xs text-gray-500 dark:text-gray-400 shrink-0">Sector:</label>
                  <div class="relative flex-1">
                    <select
                      v-model="previewSector"
                      class="appearance-none w-full pl-3 pr-8 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs focus:outline-none focus:ring-1 focus:ring-violet-500"
                    >
                      <option v-for="(label, slug) in sectorLabelsRef" :key="slug" :value="slug">{{ label }}</option>
                    </select>
                    <svg class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </div>
                  <button
                    type="button"
                    :disabled="previewLoading"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-violet-600 text-white text-xs font-medium hover:bg-violet-700 disabled:opacity-50 transition-colors shrink-0"
                    @click="loadPreview"
                  >
                    <svg v-if="previewLoading" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    {{ previewLoading ? 'Laden…' : 'Genereer' }}
                  </button>
                </div>

                <template v-if="previewResult">
                  <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">System prompt</p>
                    <pre class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 overflow-auto max-h-48 whitespace-pre-wrap leading-relaxed">{{ previewResult.system }}</pre>
                  </div>
                  <div>
                    <p class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1.5">User prompt (voorbeeld)</p>
                    <pre class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 overflow-auto max-h-64 whitespace-pre-wrap leading-relaxed">{{ previewResult.user }}</pre>
                  </div>
                </template>

                <p v-else class="text-xs text-gray-400 dark:text-gray-500 text-center py-6">
                  Klik op Genereer om de huidige prompts te bekijken zoals ze naar de AI gaan.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
