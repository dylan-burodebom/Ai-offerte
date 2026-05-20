<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ClientForm from '@/Components/ClientForm.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  clients: Object,
  filters: Object,
  sectoren: Array,
  relatie_statussen: Array,
})

const search = ref(props.filters.search ?? '')
const sector = ref(props.filters.sector ?? '')
const relatieStatus = ref(props.filters.relatie_status ?? '')
const showForm = ref(false)
const editingClient = ref(null)

let searchTimeout = null
watch([search, sector, relatieStatus], () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('clients.index'), { search: search.value, sector: sector.value, relatie_status: relatieStatus.value }, {
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
  <Head title="Bedrijven" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Bedrijven</h2>
        <PrimaryButton @click="openCreate">+ Nieuwe bedrijf</PrimaryButton>
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
            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
          />
          <select
            v-model="sector"
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Alle sectoren</option>
            <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
          </select>
          <select
            v-model="relatieStatus"
            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Alle statussen</option>
            <option v-for="s in relatie_statussen" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
          </select>
        </div>

        <!-- Tabel -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Naam</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Contactpersoon</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">E-mail</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Sector</th>
                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Stad</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-if="clients.data.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">Geen bedrijfen gevonden.</td>
              </tr>
              <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                  <div class="flex items-center gap-2">
                    {{ client.naam }}
                    <span
                      class="text-xs px-1.5 py-0.5 rounded font-normal"
                      :class="{
                        'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': client.relatie_status === 'klant',
                        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': client.relatie_status === 'prospect',
                        'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400': client.relatie_status === 'inactief',
                      }"
                    >{{ client.relatie_status }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ client.contactpersoon ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ client.email }}</td>
                <td class="px-4 py-3">
                  <span v-if="client.sector" class="inline-block px-2 py-0.5 rounded text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">
                    {{ client.sector }}
                  </span>
                  <span v-else class="text-gray-400 dark:text-gray-500">—</span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ client.stad ?? '—' }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="inline-flex gap-1.5">
                    <a
                      :href="route('clients.show', client.id)"
                      class="px-3 py-1.5 rounded border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >Bekijken</a>
                    <button
                      class="px-3 py-1.5 rounded border border-gray-400 dark:border-gray-600 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                      @click="openEdit(client)"
                    >Bewerken</button>
                    <button
                      class="px-3 py-1.5 rounded border border-red-500 dark:border-red-700 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                      @click="destroy(client)"
                    >Verwijderen</button>
                  </div>
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
            :class="link.active
              ? 'bg-blue-600 text-white border-blue-600'
              : 'text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'"
          />
        </div>
      </div>
    </div>

    <ClientForm
      :show="showForm"
      :client="editingClient"
      :sectoren="sectoren"
      :relatie_statussen="relatie_statussen"
      @close="showForm = false"
    />
  </AuthenticatedLayout>
</template>
