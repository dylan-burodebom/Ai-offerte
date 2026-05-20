<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    href: { type: String, required: true },
    active: { type: Boolean },
})

const classes = computed(() =>
    props.active
        ? 'px-4 py-2 rounded-md text-sm font-medium transition bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
        : 'px-4 py-2 rounded-md text-sm font-medium transition text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700'
)
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
