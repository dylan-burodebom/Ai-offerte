<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const passwordInput        = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

function updatePassword() {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation')
                passwordInput.value?.focus()
            }
            if (form.errors.current_password) {
                form.reset('current_password')
                currentPasswordInput.value?.focus()
            }
        },
    })
}
</script>

<template>
    <form @submit.prevent="updatePassword" class="space-y-4">
        <div>
            <label for="current_password" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Huidig wachtwoord</label>
            <input
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                type="password"
                autocomplete="current-password"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.current_password" class="text-xs text-red-500 mt-1">{{ form.errors.current_password }}</p>
        </div>

        <div>
            <label for="password" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Nieuw wachtwoord</label>
            <input
                id="password"
                ref="passwordInput"
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">Wachtwoord bevestigen</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
            />
            <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ form.errors.password_confirmation }}</p>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
            >
                {{ form.processing ? 'Opslaan…' : 'Wachtwoord wijzigen' }}
            </button>
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="text-xs text-green-600 dark:text-green-400">Opgeslagen.</span>
            </Transition>
        </div>
    </form>
</template>
