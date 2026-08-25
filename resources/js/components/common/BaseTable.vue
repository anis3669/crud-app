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
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
    >

        <div class="overflow-x-auto">

            <table
                class="w-full text-left text-sm"
                :style="{ minWidth }"
            >

                <thead
                    class="border-b border-gray-200 bg-gray-50"
                >
                    <slot name="header"></slot>
                </thead>

                <tbody
                    class="divide-y divide-gray-100"
                >
                    <slot name="body"></slot>
                </tbody>

            </table>

        </div>

    </div>

</template>
