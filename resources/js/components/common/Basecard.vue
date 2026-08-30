<script setup>
defineProps({
    padding: {
        type: String,
        default: "p-6",
    },
})
</script>

<template>
    <div
        class="rounded-xl border border-gray-200 bg-white text-gray-900 shadow-sm transition-colors duration-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        :class="padding"
    >
        <!-- Header -->

        <div
            v-if="$slots.header"
            class="mb-6 border-b border-gray-100 pb-4 dark:border-gray-700"
        >
            <slot name="header"></slot>
        </div>

        <!-- Content -->

        <slot></slot>

        <!-- Footer -->

        <div
            v-if="$slots.footer"
            class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-700"
        >
            <slot name="footer"></slot>
        </div>
    </div>
</template>
