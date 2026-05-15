<script setup>
import { ref, reactive } from 'vue'
import ClientSelector from '@/Components/ClientSelector.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
  modelValue: { type: Number, default: null },
  selectedClient: { type: Object, default: null },
  sectoren: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'update:selectedClient'])

const showNewForm = ref(false)
const saving = ref(false)
const errors = reactive({})

const form = reactive({
  naam: '',
  email: '',
  contactpersoon: '',
  sector: '',
  telefoon: '',
})

function onClientSelected(client) {
  emit('update:modelValue', client?.id ?? null)
  emit('update:selectedClient', client ?? null)
  if (client) showNewForm.value = false
}

function toggleNewForm() {
  showNewForm.value = !showNewForm.value
  if (showNewForm.value) {
    emit('update:modelValue', null)
    emit('update:selectedClient', null)
    Object.assign(form, { naam: '', email: '', contactpersoon: '', sector: '', telefoon: '' })
    Object.keys(errors).forEach((k) => delete errors[k])
  }
}

async function submitNewClient() {
  saving.value = true
  Object.keys(errors).forEach((k) => delete errors[k])

  try {
    const res = await window.axios.post(route('clients.store-inline'), form)
    const client = res.data
    emit('update:modelValue', client.id)
    emit('update:selectedClient', client)
    showNewForm.value = false
    Object.assign(form, { naam: '', email: '', contactpersoon: '', sector: '', telefoon: '' })
  } catch (e) {
    if (e.response?.status === 422) {
      Object.assign(errors, e.response.data.errors)
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Geselecteerde klant banner -->
    <div
      v-if="selectedClient"
      class="flex items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3"
    >
      <div>
        <p class="font-semibold text-indigo-900">{{ selectedClient.naam }}</p>
        <p class="text-sm text-indigo-600">
          {{ selectedClient.contactpersoon ?? selectedClient.email }}
          <span
            v-if="selectedClient.sector"
            class="ml-2 inline-block rounded px-1.5 py-0.5 text-xs bg-indigo-100 text-indigo-700"
          >{{ selectedClient.sector }}</span>
        </p>
      </div>
      <button
        type="button"
        class="text-indigo-400 hover:text-indigo-600 text-xl leading-none"
        @click="onClientSelected(null)"
      >×</button>
    </div>

    <!-- Zoekbalk (verborgen als al geselecteerd) -->
    <div v-if="!selectedClient">
      <InputLabel value="Bestaande klant zoeken" class="mb-1.5" />
      <ClientSelector
        :model-value="modelValue"
        @update:model-value="(id) => emit('update:modelValue', id)"
        @select="onClientSelected"
      />
    </div>

    <!-- Scheidingslijn -->
    <div v-if="!selectedClient" class="flex items-center gap-3 text-sm text-gray-400">
      <div class="flex-1 h-px bg-gray-200" />
      of
      <div class="flex-1 h-px bg-gray-200" />
    </div>

    <!-- Nieuwe klant knop / formulier -->
    <div v-if="!selectedClient">
      <button
        v-if="!showNewForm"
        type="button"
        class="w-full rounded-lg border-2 border-dashed border-gray-300 py-4 text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors"
        @click="toggleNewForm"
      >
        + Nieuwe klant aanmaken
      </button>

      <div v-else class="rounded-lg border border-gray-200 bg-gray-50 p-5 space-y-4">
        <div class="flex items-center justify-between mb-1">
          <h3 class="text-sm font-semibold text-gray-700">Nieuwe klant</h3>
          <button type="button" class="text-gray-400 hover:text-gray-600" @click="toggleNewForm">×</button>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="nc_naam" value="Bedrijfsnaam *" />
            <TextInput id="nc_naam" v-model="form.naam" class="mt-1 block w-full" />
            <InputError :message="errors.naam?.[0]" class="mt-1" />
          </div>
          <div>
            <InputLabel for="nc_email" value="E-mailadres *" />
            <TextInput id="nc_email" type="email" v-model="form.email" class="mt-1 block w-full" />
            <InputError :message="errors.email?.[0]" class="mt-1" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="nc_contact" value="Contactpersoon" />
            <TextInput id="nc_contact" v-model="form.contactpersoon" class="mt-1 block w-full" />
            <InputError :message="errors.contactpersoon?.[0]" class="mt-1" />
          </div>
          <div>
            <InputLabel for="nc_telefoon" value="Telefoon" />
            <TextInput id="nc_telefoon" v-model="form.telefoon" class="mt-1 block w-full" />
          </div>
        </div>

        <div>
          <InputLabel for="nc_sector" value="Sector" />
          <select
            id="nc_sector"
            v-model="form.sector"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">— Kies sector —</option>
            <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-1">
          <SecondaryButton type="button" @click="toggleNewForm">Annuleren</SecondaryButton>
          <PrimaryButton type="button" :disabled="saving" @click="submitNewClient">
            {{ saving ? 'Opslaan…' : 'Klant aanmaken & selecteren' }}
          </PrimaryButton>
        </div>
      </div>
    </div>
  </div>
</template>
