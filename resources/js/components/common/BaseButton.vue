<script setup>
defineProps({
    type: {
        type: String,
        default: "button",
    },

    variant: {
        type: String,
        default: "primary",
    },

    loading: {
        type: Boolean,
        default: false,
    },

    disabled: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1',

            // Primary
            variant === 'primary'
                ? 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-400 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 dark:focus:ring-gray-500'
                : '',

            // Secondary
            variant === 'secondary'
                ? 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-500'
                : '',

            // Danger
            variant === 'danger'
                ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-400 dark:bg-red-600 dark:text-white dark:hover:bg-red-500 dark:focus:ring-red-500'
                : '',

            // Blue
            variant === 'blue'
                ? 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-400 dark:bg-blue-600 dark:text-white dark:hover:bg-blue-500 dark:focus:ring-blue-500'
                : '',

            // Disabled / Loading
            disabled || loading
                ? 'cursor-not-allowed opacity-50'
                : '',
        ]"
    >
        <!-- Loading spinner -->

        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />

            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
        </svg>

        <!-- Loading text -->

        <span v-if="loading">
            Loading...
        </span>

        <!-- Normal content -->

        <slot v-else></slot>
    </button>
</template>
