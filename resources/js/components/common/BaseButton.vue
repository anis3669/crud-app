<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },

    variant: {
        type: String,
        default: 'primary',
    },

    loading: {
        type: Boolean,
        default: false,
    },

    disabled: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition',
            {
                'bg-gray-900 text-white hover:bg-gray-800':
                    variant === 'primary',

                'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50':
                    variant === 'secondary',

                'bg-red-600 text-white hover:bg-red-700':
                    variant === 'danger',

                'bg-blue-600 text-white hover:bg-blue-700':
                    variant === 'blue',

                'cursor-not-allowed opacity-50':
                    disabled || loading,
            },
        ]"
    >
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

        <span v-if="loading">
            Loading...
        </span>

        <slot v-else />
    </button>
</template>
