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
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <h2 class="text-xl font-semibold text-gray-800">Prompt Beheer</h2>
          <span class="text-xs font-mono px-2 py-0.5 rounded bg-violet-100 text-violet-700">
            v{{ versie }}
          </span>
        </div>
        <div class="flex items-center gap-3">
          <button
            type="button"
            class="text-sm px-3 py-1.5 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors"
            @click="previewOpen = !previewOpen"
          >
            {{ previewOpen ? '✕ Preview sluiten' : '👁 Preview prompt' }}
          </button>
          <button
            type="button"
            :disabled="saving"
            class="px-4 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:bg-violet-700 disabled:opacity-50 transition-colors"
            @click="save"
          >
            {{ saving ? 'Opslaan…' : 'Opslaan' }}
          </button>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

        <!-- Opgeslagen melding -->
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
          <p v-if="savedAt && !saveError" class="text-xs text-green-600 text-right">
            Opgeslagen om {{ savedAt }} — versie nu v{{ versie }}
          </p>
        </Transition>
        <p v-if="saveError" class="text-xs text-red-600 text-right">{{ saveError }}</p>

        <div class="flex gap-6" :class="previewOpen ? 'items-start' : ''">

          <!-- Editor kolom -->
          <div class="flex-1 min-w-0 space-y-4">

            <!-- Tabs -->
            <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit">
              <button
                type="button"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                :class="activeTab === 'stijlgids'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-500 hover:text-gray-700'"
                @click="activeTab = 'stijlgids'"
              >
                Stijlgids
              </button>
              <button
                type="button"
                class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
                :class="activeTab === 'sectoren'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-500 hover:text-gray-700'"
                @click="activeTab = 'sectoren'"
              >
                Sectoren
              </button>
            </div>

            <!-- Sector kiezer -->
            <div v-if="activeTab === 'sectoren'" class="space-y-3">
              <div class="flex gap-2 flex-wrap items-center">
                <button
                  v-for="(label, slug) in sectorLabelsRef"
                  :key="slug"
                  type="button"
                  class="px-3 py-1.5 rounded-full text-sm border transition-colors"
                  :class="activeSector === slug
                    ? 'bg-violet-600 text-white border-violet-600'
                    : 'bg-white text-gray-600 border-gray-300 hover:border-violet-400'"
                  @click="activeSector = slug; addingSector = false"
                >
                  {{ label }}
                </button>
                <button
                  type="button"
                  class="px-3 py-1.5 rounded-full text-sm border border-dashed border-gray-300 text-gray-400 hover:border-violet-400 hover:text-violet-600 transition-colors"
                  @click="addingSector = !addingSector; newLabel = ''"
                >
                  {{ addingSector ? '✕' : '+ Sector' }}
                </button>
              </div>

              <!-- Nieuw sector form -->
              <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1" leave-to-class="opacity-0">
                <div v-if="addingSector" class="flex items-start gap-2 p-3 bg-violet-50 rounded-lg border border-violet-200">
                  <div class="flex-1 space-y-1.5">
                    <input
                      v-model="newLabel"
                      type="text"
                      placeholder="Naam (bijv. Retail)"
                      class="w-full rounded border-gray-300 text-sm focus:ring-violet-500 focus:border-violet-500"
                      @keydown.enter.prevent="saveSector"
                    />
                    <p class="text-xs text-gray-400 font-mono pl-0.5">
                      slug: <span class="text-violet-600">{{ newSlug || '…' }}</span>
                    </p>
                    <p v-if="sectorError" class="text-xs text-red-500">{{ sectorError }}</p>
                  </div>
                  <button
                    type="button"
                    :disabled="sectorSaving || !newSlug"
                    class="px-3 py-2 rounded bg-violet-600 text-white text-sm hover:bg-violet-700 disabled:opacity-50 transition-colors shrink-0"
                    @click="saveSector"
                  >
                    {{ sectorSaving ? '…' : 'Toevoegen' }}
                  </button>
                </div>
              </Transition>
            </div>

            <!-- Uitleg -->
            <div class="text-xs text-gray-400 space-y-0.5">
              <p v-if="activeTab === 'stijlgids'">
                Dit is de <strong class="text-gray-600">system prompt</strong> — de schrijfregels die de AI altijd krijgt, voor elke offerte.
              </p>
              <p v-else>
                Dit is de <strong class="text-gray-600">sectorcontext</strong> — extra instructies die worden toegevoegd voor offertes in de sector <em>{{ sectorLabelsRef[activeSector] }}</em>.
              </p>
              <p>Elke opslag verhoogt de versie met 0.1 en wordt gelogd bij alle nieuwe generaties.</p>
            </div>

            <!-- Textarea editor -->
            <textarea
              :key="activeTab + activeSector"
              v-model="currentContent"
              rows="28"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm font-mono focus:ring-violet-500 focus:border-violet-500 resize-y"
              spellcheck="false"
            />

          </div>

          <!-- Preview kolom -->
          <div v-if="previewOpen" class="w-96 shrink-0 space-y-4">
            <div class="bg-white shadow rounded-xl p-4 space-y-3">
              <h3 class="text-sm font-semibold text-gray-800">Prompt preview</h3>

              <div class="flex gap-2 items-center">
                <label class="text-xs text-gray-500 shrink-0">Sector:</label>
                <select
                  v-model="previewSector"
                  class="flex-1 rounded border-gray-300 text-xs focus:ring-violet-500 focus:border-violet-500"
                >
                  <option v-for="(label, slug) in sectorLabelsRef" :key="slug" :value="slug">
                    {{ label }}
                  </option>
                </select>
                <button
                  type="button"
                  :disabled="previewLoading"
                  class="px-3 py-1 rounded bg-violet-600 text-white text-xs hover:bg-violet-700 disabled:opacity-50 transition-colors shrink-0"
                  @click="loadPreview"
                >
                  {{ previewLoading ? '…' : 'Genereer' }}
                </button>
              </div>

              <template v-if="previewResult">
                <div>
                  <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">System prompt</p>
                  <pre class="text-xs text-gray-700 bg-gray-50 rounded p-3 overflow-auto max-h-48 whitespace-pre-wrap">{{ previewResult.system }}</pre>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">User prompt (voorbeeld)</p>
                  <pre class="text-xs text-gray-700 bg-gray-50 rounded p-3 overflow-auto max-h-64 whitespace-pre-wrap">{{ previewResult.user }}</pre>
                </div>
              </template>

              <p v-else class="text-xs text-gray-400 text-center py-4">
                Klik op Genereer om de huidige prompts te bekijken zoals ze naar OpenAI gaan.
              </p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
