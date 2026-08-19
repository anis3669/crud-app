<script setup>
import { computed } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits([
    'back',
    'edit-product',
]);

const stockStatus = computed(() => {
    if (props.product.quantity === 0) {
        return {
            label: 'Out of stock',
            wrapper: 'bg-red-50 text-red-700',
            dot: 'bg-red-500',
        };
    }

    if (props.product.quantity <= 5) {
        return {
            label: `${props.product.quantity} left`,
            wrapper: 'bg-yellow-50 text-yellow-700',
            dot: 'bg-yellow-500',
        };
    }

    return {
        label: `${props.product.quantity} in stock`,
        wrapper: 'bg-green-50 text-green-700',
        dot: 'bg-green-500',
    };
});
</script>

<template>
    <div class="mx-auto max-w-4xl">

        <!-- Back -->
        <button
            type="button"
            @click="emit('back')"
            class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"
                />
            </svg>

            Back to Products
        </button>

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-3">

                    <!-- Product Initial -->
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-lg font-bold text-gray-700"
                    >
                        {{ product.name.charAt(0).toUpperCase() }}
                    </div>

                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-gray-900"
                        >
                            {{ product.name }}
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            Product #{{ product.id }}
                        </p>
                    </div>

                </div>
            </div>

            <!-- Edit -->
            <button
                type="button"
                @click="emit('edit-product', product)"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.121 2.121 0 013 3L12 14l-4 1 1-4 8.5-8.5z"
                    />
                </svg>

                Edit Product
            </button>
        </div>

        <!-- Product Details -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <!-- Description -->
            <div class="border-b border-gray-100 px-6 py-6">

                <h2 class="text-sm font-semibold text-gray-900">
                    Description
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    {{ product.description || 'No description available.' }}
                </p>

            </div>

            <!-- Information -->
            <div class="grid gap-px bg-gray-100 sm:grid-cols-2">

                <!-- Price -->
                <div class="bg-white px-6 py-6">

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Price
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rs. {{ Number(product.price).toLocaleString() }}
                    </p>

                </div>

                <!-- Quantity -->
                <div class="bg-white px-6 py-6">

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Quantity
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ product.quantity }}
                    </p>

                </div>

                <!-- Stock -->
                <div class="bg-white px-6 py-6 sm:col-span-2">

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Stock Status
                    </p>

                    <span
                        :class="stockStatus.wrapper"
                        class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                    >
                        <span
                            :class="stockStatus.dot"
                            class="h-1.5 w-1.5 rounded-full"
                        ></span>

                        {{ stockStatus.label }}
                    </span>

                </div>

            </div>

        </div>

    </div>
</template>

