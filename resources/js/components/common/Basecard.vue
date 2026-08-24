<script setup>
defineProps({
    padding: {
        type: String,
        default: 'p-6',
    },
})
</script>

<template>
    <div
        class="rounded-xl border border-gray-200 bg-white shadow-sm"
        :class="padding"
    >
        <!-- Optional Header -->
        <div
            v-if="$slots.header"
            class="mb-6 border-b border-gray-100 pb-4"
        >
            <slot name="header" />
        </div>

        <!-- Main Content -->
        <slot />

        <!-- Optional Footer -->
        <div
            v-if="$slots.footer"
            class="mt-6 border-t border-gray-100 pt-4"
        >
            <slot name="footer" />
        </div>
    </div>
</template>
