<script setup>
import { reactive, watch } from 'vue'
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

    <!-- Projectbeschrijving -->
    <div>
      <InputLabel for="projectbeschrijving" value="Projectbeschrijving" />
      <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Optioneel als je een Fireflies samenvatting invult. Wordt gebruikt als aanvulling voor de AI.</p>
      <textarea
        id="projectbeschrijving"
        v-model="form.projectbeschrijving"
        rows="5"
        placeholder="Beschrijf de wensen, doelen en context van het project..."
        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
      />
      <InputError :message="errors.projectbeschrijving" class="mt-1" />
    </div>

    <!-- Fireflies samenvatting -->
    <div>
      <div class="flex items-center gap-2 mb-1">
        <InputLabel for="fireflies_samenvatting" value="Fireflies samenvatting" class="mb-0" />
        <span class="text-xs px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 font-medium">Aanbevolen</span>
      </div>
      <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">
        Plak hier de samenvatting van je Fireflies gesprekopname. De AI gebruikt dit als primaire input voor de offerte.
      </p>
      <div class="relative">
        <textarea
          id="fireflies_samenvatting"
          v-model="form.fireflies_samenvatting"
          rows="5"
          placeholder="Plak hier de Fireflies samenvatting…"
          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-violet-900/10 dark:text-white dark:placeholder-gray-500 shadow-sm text-sm focus:ring-violet-500 focus:border-violet-500 bg-violet-50/40 placeholder-gray-400"
        />
        <span
          v-if="form.fireflies_samenvatting"
          class="absolute top-2 right-2 text-xs px-1.5 py-0.5 rounded bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400 font-medium pointer-events-none"
        >
          Fireflies
        </span>
      </div>
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
