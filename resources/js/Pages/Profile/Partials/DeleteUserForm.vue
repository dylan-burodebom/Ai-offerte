<script setup>
import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const showConfirm  = ref(false)
const passwordInput = ref(null)

const form = useForm({ password: '' })

function openConfirm() {
    showConfirm.value = true
    nextTick(() => passwordInput.value?.focus())
}

function closeConfirm() {
    showConfirm.value = false
    form.clearErrors()
    form.reset()
}

function deleteUser() {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeConfirm(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Zodra je account is verwijderd, worden alle gegevens permanent gewist. Download eerst alles wat je wilt bewaren.
        </p>

        <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 transition-colors"
            @click="openConfirm"
        >
            Account verwijderen
        </button>

        <!-- Bevestigingsdialoog -->
        <Teleport to="body">
            <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" @keydown.esc="closeConfirm">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl w-full max-w-md p-6 space-y-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Account verwijderen?</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dit kan niet ongedaan worden gemaakt. Voer je wachtwoord in om te bevestigen.
                    </p>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Wachtwoord</label>
                        <input
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Jouw wachtwoord"
                            class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-red-500"
                            @keyup.enter="deleteUser"
                        />
                        <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            class="px-3.5 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            @click="closeConfirm"
                        >Annuleren</button>
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="px-3.5 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition-colors"
                            @click="deleteUser"
                        >{{ form.processing ? 'Verwijderen…' : 'Ja, verwijder account' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
