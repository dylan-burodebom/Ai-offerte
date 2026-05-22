<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ClientForm from '@/Components/ClientForm.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
  clients: Object,
  filters: Object,
  sectoren: Array,
  relatie_statussen: Array,
})

const search        = ref(props.filters.search ?? '')
const sector        = ref(props.filters.sector ?? '')
const relatieStatus = ref(props.filters.relatie_status ?? '')
const showForm      = ref(false)
const editingClient = ref(null)

let searchTimeout = null
watch([search, sector, relatieStatus], () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('clients.index'), {
      search: search.value,
      sector: sector.value,
      relatie_status: relatieStatus.value,
    }, { preserveState: true, replace: true })
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

function getInitials(naam) {
  return (naam ?? '').split(' ').filter(Boolean).map(w => w[0]).join('').substring(0, 2).toUpperCase()
}
</script>

<template>
  <Head title="Bedrijven" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h2 class="text-base font-semibold text-gray-800 dark:text-white">Bedrijven</h2>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors"
          @click="openCreate"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nieuw bedrijf
        </button>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-6 space-y-4">

        <!-- Filter bar -->
        <div class="flex gap-3">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
              v-model="search"
              type="text"
              placeholder="Zoek op naam, contactpersoon of e-mail..."
              class="w-full pl-9 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white dark:placeholder-gray-400"
            />
          </div>
          <select
            v-model="sector"
            class="pl-3 pr-8 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white text-gray-600 dark:text-gray-300"
          >
            <option value="">Alle sectoren</option>
            <option v-for="s in sectoren" :key="s" :value="s">{{ s }}</option>
          </select>
          <select
            v-model="relatieStatus"
            class="pl-3 pr-8 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 dark:text-white text-gray-600 dark:text-gray-300"
          >
            <option value="">Alle statussen</option>
            <option v-for="s in relatie_statussen" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
          </select>
        </div>

        <!-- Tabel -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
          <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-700">
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">Naam</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden lg:table-cell">Contactpersoon</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden md:table-cell">E-mail</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden lg:table-cell">Sector</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide hidden xl:table-cell">Stad</th>
                <th class="sticky right-0 bg-white dark:bg-gray-800 px-4 py-3 border-l border-gray-100 dark:border-gray-700 w-20" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-if="clients.data.length === 0">
                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400 dark:text-gray-500">Geen bedrijven gevonden.</td>
              </tr>
              <tr
                v-for="client in clients.data"
                :key="client.id"
                class="group hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors cursor-pointer"
                @click="router.visit(route('clients.show', client.id))"
              >
                <!-- Naam + badge + avatar -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-bold shrink-0 select-none overflow-hidden">
                      <img v-if="client.logo_url" :src="client.logo_url" class="w-full h-full object-contain" alt="" />
                      <span v-else>{{ getInitials(client.naam) }}</span>
                    </div>
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900 dark:text-white truncate">{{ client.naam }}</p>
                      <span
                        v-if="client.relatie_status"
                        class="text-xs px-2 py-0.5 rounded-full capitalize"
                        :class="{
                          'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': client.relatie_status === 'klant',
                          'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': client.relatie_status === 'prospect',
                          'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400': client.relatie_status === 'inactief',
                        }"
                      >{{ client.relatie_status }}</span>
                    </div>
                  </div>
                </td>

                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ client.contactpersoon || client.contactpersonen?.[0]?.naam || '—' }}</td>

                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 max-w-[200px] truncate hidden md:table-cell">{{ client.email }}</td>

                <td class="px-6 py-4 hidden lg:table-cell">
                  <span v-if="client.sector" class="inline-block px-2.5 py-0.5 rounded-full text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">{{ client.sector }}</span>
                  <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                </td>

                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 hidden xl:table-cell">{{ client.stad ?? '—' }}</td>

                <td class="sticky right-0 px-4 py-4 border-l border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 group-hover:bg-gray-50 dark:group-hover:bg-gray-700/40 transition-colors">
                  <div class="flex items-center justify-end gap-1" @click.stop>
                    <a
                      :href="route('clients.show', client.id)"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                      title="Bekijken"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </a>
                    <button
                      type="button"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                      title="Bewerken"
                      @click="openEdit(client)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Paginering -->
        <div v-if="clients.last_page > 1" class="flex justify-center gap-1">
          <component
            v-for="link in clients.links"
            :key="link.label"
            :is="link.url ? 'a' : 'span'"
            :href="link.url ?? undefined"
            v-html="link.label"
            class="px-3 py-1.5 rounded-lg text-sm border transition-colors"
            :class="link.active
              ? 'bg-blue-600 text-white border-blue-600'
              : link.url
                ? 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                : 'bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 border-gray-200 dark:border-gray-700 cursor-default'"
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
