<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    href: { type: String, required: true },
    active: { type: Boolean },
})

const classes = computed(() =>
    props.active
        ? 'px-4 py-2 rounded-md text-sm font-medium transition bg-gray-100 text-gray-900'
        : 'px-4 py-2 rounded-md text-sm font-medium transition text-gray-500 hover:text-gray-900 hover:bg-gray-50'
)
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
