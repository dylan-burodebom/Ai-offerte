<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import WizardProgress from '@/Components/WizardProgress.vue'
import Step1Client from '@/Components/Step1Client.vue'
import Step2Intake from '@/Components/Step2Intake.vue'
import Step3Generate from '@/Components/Step3Generate.vue'
import Step4Investment from '@/Components/Step4Investment.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  sectoren:      { type: Array, default: () => [] },
  dienstenLijst: { type: Array, default: () => [] },
})

const STEPS = ['Klant', 'Offerte', 'Generatie', 'Investering']

const currentStep    = ref(1)
const saving         = ref(false)
const step2Errors    = ref({})
const step4Errors    = ref({})
const finalized      = ref(false)

const wizard = reactive({
  // stap 1
  clientId:       null,
  selectedClient: null,
  // stap 2
  quoteId:        null,
  offerteNummer:  null,
  titel:                   '',
  projectbeschrijving:     '',
  fireflies_samenvatting:  '',
  diensten:                [],
  geldig_tot:          '',
  // stap 3
  generatedSections: [],
  // stap 4
  investering: { rows: [], btw: '21%' },
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

const step4CanProceed = computed(() =>
  wizard.investering.rows.length > 0 &&
  wizard.investering.rows.every((r) => {
    const b = parseFloat(String(r.bedrag).replace(',', '.')) || 0
    return r.omschrijving?.trim() && b > 0
  })
)

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
  if (currentStep.value === 4) return step4CanProceed.value
  return true
})

const validationMessage = computed(() => {
  if (currentStep.value === 1 && !wizard.clientId)
    return 'Selecteer of maak een klant aan om verder te gaan.'
  if (currentStep.value === 2) {
    if (wizard.titel.trim().length < 3)     return 'Vul een titel in (minimaal 3 tekens).'
    if (!wizard.projectbeschrijving.trim() && !wizard.fireflies_samenvatting.trim())
      return 'Vul een projectbeschrijving in of plak een Fireflies samenvatting.'
    if (wizard.diensten.length === 0)       return 'Selecteer minimaal één dienst.'
    if (!wizard.geldig_tot)                 return 'Kies een geldigheidsdatum.'
  }
  if (currentStep.value === 3 && wizard.generatedSections.length === 0)
    return 'Genereer eerst de offertetekst om verder te gaan.'
  if (currentStep.value === 4 && !step4CanProceed.value)
    return 'Vul minimaal één regel in met omschrijving en bedrag groter dan 0.'
  return null
})

async function saveDraft() {
  saving.value = true
  step2Errors.value = {}

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
    if (e.response?.status === 422) step2Errors.value = e.response.data.errors ?? {}
    return false
  } finally {
    saving.value = false
  }
}

async function finalize() {
  if (!canProceed.value) return
  saving.value = true
  step4Errors.value = {}

  const rows = wizard.investering.rows.map((r) => ({
    omschrijving: r.omschrijving,
    bedrag: parseFloat(String(r.bedrag).replace(',', '.')) || 0,
  }))

  try {
    await window.axios.post(route('quotes.investments', wizard.quoteId), {
      rows,
      btw_tarief: wizard.investering.btw,
    })
    finalized.value = true
  } catch (e) {
    if (e.response?.status === 422) step4Errors.value = e.response.data.errors ?? {}
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
  if (currentStep.value > 1) currentStep.value--
}
</script>

<template>
  <Head title="Nieuwe offerte" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center gap-3">
        <h2 class="text-xl font-semibold text-gray-800">Nieuwe offerte</h2>
        <span
          v-if="wizard.offerteNummer"
          class="text-xs font-mono px-2 py-0.5 rounded bg-indigo-100 text-indigo-700"
        >
          {{ wizard.offerteNummer }}
        </span>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Succes na finalisatie -->
        <div v-if="finalized" class="bg-white shadow rounded-lg p-10 text-center">
          <div class="text-5xl mb-4">✓</div>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Offerte opgeslagen</h3>
          <p class="text-gray-500 mb-1">
            <span class="font-mono text-indigo-700">{{ wizard.offerteNummer }}</span> is opgeslagen als concept.
          </p>
          <p class="text-sm text-gray-400 mb-8">Open de editor om de tekst te verfijnen en de offerte te versturen.</p>
          <div class="flex justify-center gap-3">
            <button
              type="button"
              class="inline-flex items-center px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors"
              @click="router.visit(route('quotes.edit', wizard.quoteId))"
            >
              ✏ Offerte bewerken
            </button>
            <button
              type="button"
              class="inline-flex items-center px-5 py-2.5 rounded-lg bg-white border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors"
              @click="router.visit(route('dashboard'))"
            >
              Naar dashboard
            </button>
          </div>
        </div>

        <template v-else>
          <WizardProgress :current-step="currentStep" :steps="STEPS" />

          <div class="bg-white shadow rounded-lg p-6">

            <!-- Stap 1: Klant -->
            <div v-if="currentStep === 1">
              <h3 class="text-base font-semibold text-gray-900 mb-5">Stap 1 — Selecteer een klant</h3>
              <Step1Client
                v-model="wizard.clientId"
                v-model:selected-client="wizard.selectedClient"
                :sectoren="sectoren"
              />
            </div>

            <!-- Stap 2: Project intake -->
            <div v-else-if="currentStep === 2">
              <h3 class="text-base font-semibold text-gray-900 mb-5">
                Stap 2 — Project details
                <span class="font-normal text-sm text-gray-400 ml-1">voor {{ wizard.selectedClient?.naam }}</span>
              </h3>
              <Step2Intake
                v-model="step2Data"
                :diensten-lijst="dienstenLijst"
                :errors="step2Errors"
              />
            </div>

            <!-- Stap 3: AI Generatie -->
            <div v-else-if="currentStep === 3">
              <h3 class="text-base font-semibold text-gray-900 mb-5">Stap 3 — AI Generatie</h3>
              <Step3Generate
                :quote-id="wizard.quoteId"
                @sections-saved="(s) => { wizard.generatedSections = s }"
              />
            </div>

            <!-- Stap 4: Investering -->
            <div v-else-if="currentStep === 4">
              <h3 class="text-base font-semibold text-gray-900 mb-5">Stap 4 — Investering</h3>
              <Step4Investment
                v-model="wizard.investering"
                :errors="step4Errors"
              />
            </div>

            <!-- Navigatieknoppen -->
            <div class="flex justify-between mt-8 pt-5 border-t border-gray-100">
              <SecondaryButton v-if="currentStep > 1" type="button" @click="prev">
                ← Vorige
              </SecondaryButton>
              <div v-else />

              <PrimaryButton
                v-if="currentStep < STEPS.length"
                type="button"
                :disabled="!canProceed || saving"
                :class="(!canProceed || saving) ? 'opacity-50 cursor-not-allowed' : ''"
                @click="next"
              >
                {{ saving ? 'Opslaan…' : 'Volgende →' }}
              </PrimaryButton>
              <PrimaryButton
                v-else
                type="button"
                :disabled="!canProceed || saving"
                :class="(!canProceed || saving) ? 'opacity-50 cursor-not-allowed' : ''"
                @click="finalize"
              >
                {{ saving ? 'Opslaan…' : 'Offerte opslaan' }}
              </PrimaryButton>
            </div>

            <!-- Validatieboodschap -->
            <p v-if="validationMessage" class="mt-3 text-center text-xs text-amber-600">
              {{ validationMessage }}
            </p>
          </div>
        </template>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
