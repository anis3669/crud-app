<script setup>
import { ref } from 'vue';

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits([
    'cancel',
    'products-updated',
]);

const formProducts = ref(
    props.products.map(product => ({
        ...product,
    }))
);

const showConfirmation = ref(false);

function submitBulkEdit() {
    showConfirmation.value = true;
}

function cancelConfirmation() {
    showConfirmation.value = false;
}

function confirmBulkEdit() {
    const updatedProducts = formProducts.value.map(product => ({
        ...product,
        name: product.name.trim(),
        description: product.description?.trim() || '',
        price: Number(product.price),
        quantity: Number(product.quantity),
    }));

    showConfirmation.value = false;

    emit('products-updated', updatedProducts);
}
</script>

<template>
    <div class="mx-auto max-w-5xl">

        <!-- Header -->
        <div class="mb-8">

            <button
                type="button"
                @click="emit('cancel')"
                class="mb-5 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
            >
                <svg
                    class="mr-2 h-4 w-4"
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

            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Edit Selected Products
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Update each selected product individually.
            </p>

        </div>


        <!-- Edit Form -->
        <form @submit.prevent="submitBulkEdit">

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >

                <!-- Table -->
                <div class="overflow-x-auto">

                    <table class="w-full min-w-[850px] text-left text-sm">

                        <!-- Table Header -->
                        <thead
                            class="border-b border-gray-200 bg-gray-50"
                        >

                            <tr>

                                <th
                                    class="px-6 py-4 font-semibold text-gray-900"
                                >
                                    Product Name
                                </th>

                                <th
                                    class="px-6 py-4 font-semibold text-gray-900"
                                >
                                    Description
                                </th>

                                <th
                                    class="px-6 py-4 font-semibold text-gray-900"
                                >
                                    Price
                                </th>

                                <th
                                    class="px-6 py-4 font-semibold text-gray-900"
                                >
                                    Quantity
                                </th>

                            </tr>

                        </thead>


                        <!-- Table Body -->
                        <tbody class="divide-y divide-gray-100">

                            <tr
                                v-for="(product, index) in formProducts"
                                :key="product.id"
                            >

                                <!-- Product Name -->
                                <td class="px-6 py-5">

                                    <label
                                        :for="`name-${product.id}`"
                                        class="sr-only"
                                    >
                                        Product Name
                                    </label>

                                    <input
                                        :id="`name-${product.id}`"
                                        v-model="formProducts[index].name"
                                        type="text"
                                        maxlength="255"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                                    />

                                    <p
                                        class="mt-1 text-xs text-gray-400"
                                    >
                                        Product #{{ product.id }}
                                    </p>

                                </td>


                                <!-- Description -->
                                <td class="px-6 py-5">

                                    <label
                                        :for="`description-${product.id}`"
                                        class="sr-only"
                                    >
                                        Description
                                    </label>

                                    <input
                                        :id="`description-${product.id}`"
                                        v-model="
                                            formProducts[index].description
                                        "
                                        type="text"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                                    />

                                </td>


                                <!-- Price -->
                                <td class="px-6 py-5">

                                    <label
                                        :for="`price-${product.id}`"
                                        class="sr-only"
                                    >
                                        Price
                                    </label>

                                    <div class="relative">

                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500"
                                        >
                                            Rs.
                                        </span>

                                        <input
                                            :id="`price-${product.id}`"
                                            v-model="formProducts[index].price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            class="w-full rounded-lg border border-gray-300 py-2.5 pl-11 pr-3 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                                        />

                                    </div>

                                </td>


                                <!-- Quantity -->
                                <td class="px-6 py-5">

                                    <label
                                        :for="`quantity-${product.id}`"
                                        class="sr-only"
                                    >
                                        Quantity
                                    </label>

                                    <input
                                        :id="`quantity-${product.id}`"
                                        v-model="
                                            formProducts[index].quantity
                                        "
                                        type="number"
                                        min="0"
                                        step="1"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                                    />

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>


                <!-- Footer -->
                <div
                    class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4"
                >

                    <p class="text-sm text-gray-500">
                        {{ formProducts.length }} product(s) selected
                    </p>


                    <div class="flex items-center gap-3">

                        <button
                            type="button"
                            @click="emit('cancel')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                        >
                            Update Products
                        </button>

                    </div>

                </div>

            </div>

        </form>


        <!-- Confirmation Modal -->
        <div
            v-if="showConfirmation"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >

            <div
                class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
            >

                <h2 class="text-lg font-bold text-gray-900">
                    Confirm Update
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Are you sure you want to update
                    <span class="font-semibold text-gray-900">
                        {{ formProducts.length }} product(s)
                    </span>
                    ?
                </p>

                <div class="mt-6 flex justify-end gap-3">

                    <button
                        type="button"
                        @click="cancelConfirmation"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="confirmBulkEdit"
                        class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Confirm Update
                    </button>

                </div>

            </div>

        </div>

    </div>
</template>
