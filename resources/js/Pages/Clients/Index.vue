<script setup>
import { ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ClientForm from '@/Components/ClientForm.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  clients: Object,
  filters: Object,
  sectoren: Array,
})

const search = ref(props.filters.search ?? '')
const sector = ref(props.filters.sector ?? '')
const showForm = ref(false)
const editingClient = ref(null)

let searchTimeout = null
watch([search, sector], () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('clients.index'), { search: search.value, sector: sector.value }, {
      preserveState: true,
      replace: true,
    })
  }, 300)
})

function openCreate() {
  editingClient.value = null
  showForm.value = true
}

function openEdit(client) {
  editingClient.value = { ...client }
  showForm.value = true
}

function destroy(client) {
  if (confirm(`Klant "${client.naam}" verwijderen?`)) {
    router.delete(route('clients.destroy', client.id))
  }
}
</script>

<template>
  <Head title="Klanten" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Klanten</h2>
        <PrimaryButton @click="openCreate">+ Nieuwe klant</PrimaryButton>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Zoek & filter -->
        <div class="flex gap-3 mb-6">
          <input
            v-model="search"
            type="text"
            placeholder="Zoek op naam, contactpersoon of e-mail..."
            class="flex-1 rounded-md border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
          />
          <select
            v-model="sector"
            class="rounded-md border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Alle sectoren</option>
            <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

        <!-- Tabel -->
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Naam</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Contactpersoon</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">E-mail</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Sector</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500">Stad</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="clients.data.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">Geen klanten gevonden.</td>
              </tr>
              <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-900">{{ client.naam }}</td>
                <td class="px-4 py-3 text-gray-600">{{ client.contactpersoon ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ client.email }}</td>
                <td class="px-4 py-3">
                  <span v-if="client.sector" class="inline-block px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">
                    {{ client.sector }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ client.stad ?? '—' }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                  <a
                    :href="route('clients.show', client.id)"
                    class="inline-flex items-center px-2 py-1 rounded border border-gray-300 text-xs text-gray-600 hover:bg-gray-50 transition-colors"
                    @click.stop
                  >Bekijken</a>
                  <SecondaryButton class="text-xs py-1 px-2" @click="openEdit(client)">Bewerken</SecondaryButton>
                  <DangerButton class="text-xs py-1 px-2" @click="destroy(client)">Verwijderen</DangerButton>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginering -->
        <div v-if="clients.last_page > 1" class="mt-4 flex justify-center gap-1">
          <component
            v-for="link in clients.links"
            :key="link.label"
            :is="link.url ? 'a' : 'span'"
            :href="link.url ?? undefined"
            v-html="link.label"
            class="px-3 py-1 rounded text-sm border"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 border-gray-300 hover:bg-gray-50'"
          />
        </div>
      </div>
    </div>

    <ClientForm
      :show="showForm"
      :client="editingClient"
      :sectoren="sectoren"
      @close="showForm = false"
    />
  </AuthenticatedLayout>
</template>
