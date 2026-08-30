<script setup>

defineProps({
    minWidth: {
        type: String,
        default: '900px',
    },
})

</script>

<template>

    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm
               dark:border-gray-700 dark:bg-gray-900"
    >

        <div class="overflow-x-auto">

            <table
                class="w-full text-left text-sm text-gray-700 dark:text-gray-300"
                :style="{ minWidth }"
            >

                <!-- Header -->

                <thead
                    class="border-b border-gray-200 bg-gray-50
                           dark:border-gray-700 dark:bg-gray-800"
                >
                    <slot name="header"></slot>
                </thead>

                <!-- Body -->

                <tbody
                    class="divide-y divide-gray-100
                           dark:divide-gray-800"
                >
                    <slot name="body"></slot>
                </tbody>

            </table>

        </div>

    </div>

</template>
