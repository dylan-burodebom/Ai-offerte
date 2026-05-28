<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import { Head } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
})
</script>

<template>
    <Head title="Mijn profiel" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-semibold text-gray-800 dark:text-white">Mijn profiel</h2>
        </template>

        <div class="py-6">
            <div class="max-w-2xl mx-auto px-3 sm:px-6 space-y-5">

                <!-- Profielfoto + gegevens -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Profielgegevens</h3>
                    </div>
                    <div class="p-6">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>
                </div>

                <!-- Wachtwoord -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-white">Wachtwoord wijzigen</h3>
                    </div>
                    <div class="p-6">
                        <UpdatePasswordForm />
                    </div>
                </div>

                <!-- Account verwijderen -->
                <div class="bg-white dark:bg-gray-800 border border-red-100 dark:border-red-900/40 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-red-100 dark:border-red-900/40">
                        <h3 class="text-sm font-semibold text-red-600 dark:text-red-400">Account verwijderen</h3>
                    </div>
                    <div class="p-6">
                        <DeleteUserForm />
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
