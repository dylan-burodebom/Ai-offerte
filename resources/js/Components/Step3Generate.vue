<script setup>
import { ref, onMounted } from 'vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  quoteId: { type: Number, required: true },
})

const emit = defineEmits(['sections-saved'])

const status       = ref('idle')
const streamText   = ref('')
const sections     = ref([])
const tokens       = ref(0)
const errorMessage = ref('')

let eventSource = null

function start() {
  status.value       = 'generating'
  streamText.value   = ''
  sections.value     = []
  errorMessage.value = ''
  tokens.value       = 0

  if (eventSource) eventSource.close()

  eventSource = new EventSource(route('quotes.generate', props.quoteId))

  eventSource.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type === 'token') {
      streamText.value += data.content
    } else if (data.type === 'done') {
      sections.value = data.sections
      tokens.value   = data.tokens
      status.value   = 'done'
      emit('sections-saved', data.sections)
      eventSource.close()
    } else if (data.type === 'error') {
      errorMessage.value = data.message
      status.value       = 'error'
      eventSource.close()
    }
  }

  eventSource.onerror = () => {
    if (status.value === 'generating') {
      errorMessage.value = 'Verbinding verbroken. Probeer het opnieuw.'
      status.value       = 'error'
      eventSource.close()
    }
  }
}

function retry() {
  start()
}

onMounted(() => start())
</script>

<template>
  <div class="space-y-6">

    <!-- Idle -->
    <div v-if="status === 'idle'" class="text-center py-8">
      <div class="mb-4 text-4xl">✦</div>
      <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-2">Klaar om te genereren</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
        Op basis van de projectbeschrijving en diensten schrijft de AI een complete offertetekst in de stijl van buro_deBom.
      </p>
      <PrimaryButton type="button" @click="start">
        ✦ Genereer offerte
      </PrimaryButton>
    </div>

    <!-- Generating: streaming preview -->
    <div v-else-if="status === 'generating'">
      <div class="flex items-center gap-2 mb-3">
        <span class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-pulse" />
        <span class="text-sm font-medium text-blue-700 dark:text-blue-400">Offerte wordt gegenereerd…</span>
      </div>
      <div
        class="font-mono text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 whitespace-pre-wrap min-h-[200px] max-h-[400px] overflow-y-auto"
      >{{ streamText }}<span class="inline-block w-0.5 h-4 bg-blue-500 animate-pulse ml-0.5 align-middle" /></div>
    </div>

    <!-- Done: sections preview -->
    <div v-else-if="status === 'done'">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <span class="text-green-500">✓</span>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Offerte gegenereerd</span>
          <span v-if="tokens" class="text-xs text-gray-400 dark:text-gray-500">({{ tokens }} tokens)</span>
        </div>
        <SecondaryButton type="button" class="text-xs py-1 px-3" @click="retry">
          ↺ Opnieuw genereren
        </SecondaryButton>
      </div>

      <div class="space-y-4">
        <div
          v-for="section in sections"
          :key="section.titel"
          class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 overflow-hidden"
        >
          <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ section.titel }}</h4>
          </div>
          <div class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
            {{ section.tekst }}
          </div>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="status === 'error'" class="text-center py-8">
      <div class="mb-3 text-3xl">⚠</div>
      <p class="text-sm font-medium text-red-600 dark:text-red-400 mb-4">{{ errorMessage }}</p>
      <PrimaryButton type="button" @click="retry">
        Probeer opnieuw
      </PrimaryButton>
    </div>

  </div>
</template>
