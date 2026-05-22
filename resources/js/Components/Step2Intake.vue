<script setup>
import { ref, reactive, watch } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ titel: '', projectbeschrijving: '', fireflies_samenvatting: '', diensten: [], geldig_tot: '' }),
  },
  dienstenLijst: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

const form = reactive({ ...props.modelValue, diensten: [...(props.modelValue.diensten ?? [])] })
const contextTab = ref(props.modelValue.fireflies_samenvatting ? 'fireflies' : 'omschrijving')

watch(form, () => emit('update:modelValue', { ...form, diensten: [...form.diensten] }), { deep: true })

function toggleDienst(dienst) {
  const i = form.diensten.indexOf(dienst)
  if (i === -1) form.diensten.push(dienst)
  else form.diensten.splice(i, 1)
}

function setGeldigheid(dagen) {
  const d = new Date()
  d.setDate(d.getDate() + dagen)
  form.geldig_tot = d.toISOString().slice(0, 10)
}

const today = new Date().toISOString().slice(0, 10)
</script>

<template>
  <div class="space-y-6">

    <!-- Titel -->
    <div>
      <InputLabel for="titel" value="Offertetitel *" />
      <TextInput
        id="titel"
        v-model="form.titel"
        class="mt-1 block w-full"
        placeholder="bijv. Website hernieuwing voor Bedrijf X"
      />
      <InputError :message="errors.titel" class="mt-1" />
    </div>

    <!-- Project context (gecombineerd blok) -->
    <div>
      <InputLabel value="Project context *" />
      <div class="mt-1 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
        <!-- Tab switcher -->
        <div class="flex border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
          <button
            type="button"
            class="flex-1 py-2 text-xs font-semibold transition-colors border-b-2"
            :class="contextTab === 'omschrijving'
              ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-800'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="contextTab = 'omschrijving'"
          >Omschrijving</button>
          <button
            type="button"
            class="flex-1 py-2 text-xs font-semibold transition-colors border-b-2 flex items-center justify-center gap-1.5"
            :class="contextTab === 'fireflies'
              ? 'border-violet-500 text-violet-600 dark:text-violet-400 bg-white dark:bg-gray-800'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
            @click="contextTab = 'fireflies'"
          >
            Fireflies
            <span class="text-xs px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400">Aanbevolen</span>
          </button>
        </div>

        <!-- Omschrijving textarea -->
        <textarea
          v-if="contextTab === 'omschrijving'"
          v-model="form.projectbeschrijving"
          rows="6"
          placeholder="Beschrijf de wensen, doelen en context van het project..."
          class="block w-full px-3 py-2.5 text-sm border-0 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 focus:ring-0 focus:outline-none resize-none"
        />

        <!-- Fireflies textarea -->
        <div v-else class="relative">
          <textarea
            v-model="form.fireflies_samenvatting"
            rows="6"
            placeholder="Plak hier de Fireflies samenvatting…"
            class="block w-full px-3 py-2.5 text-sm border-0 bg-violet-50/40 dark:bg-violet-900/10 dark:text-white dark:placeholder-gray-500 focus:ring-0 focus:outline-none resize-none"
          />
          <span
            v-if="form.fireflies_samenvatting"
            class="absolute top-2 right-2 text-xs px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400 font-medium pointer-events-none"
          >Fireflies</span>
        </div>
      </div>
      <InputError :message="errors.projectbeschrijving" class="mt-1" />
    </div>

    <!-- Diensten -->
    <div>
      <InputLabel value="Diensten *" />
      <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Selecteer alle van toepassing zijnde diensten.</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="dienst in dienstenLijst"
          :key="dienst"
          type="button"
          class="px-3 py-1.5 rounded-full text-sm border transition-colors"
          :class="form.diensten.includes(dienst)
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400'"
          @click="toggleDienst(dienst)"
        >
          {{ dienst }}
        </button>
      </div>
      <InputError :message="errors.diensten" class="mt-1" />
    </div>

    <!-- Geldigheidsduur -->
    <div>
      <InputLabel for="geldig_tot" value="Geldig tot *" />
      <div class="mt-1 flex items-center gap-3">
        <input
          id="geldig_tot"
          v-model="form.geldig_tot"
          type="date"
          :min="today"
          class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
        />
        <div class="flex gap-2">
          <button
            v-for="dagen in [14, 30, 60]"
            :key="dagen"
            type="button"
            class="px-2.5 py-1 rounded text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-blue-400 hover:text-blue-600 dark:hover:border-blue-500 dark:hover:text-blue-400 transition-colors"
            @click="setGeldigheid(dagen)"
          >
            +{{ dagen }} dagen
          </button>
        </div>
      </div>
      <InputError :message="errors.geldig_tot" class="mt-1" />
    </div>

  </div>
</template>
