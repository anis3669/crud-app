<script setup>
import { computed, ref, watch } from "vue";

import BaseTable from "../common/BaseTable.vue";
import BaseButton from "../common/BaseButton.vue";

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },

    currentPage: {
        type: Number,
        default: 1,
    },

    perPage: {
        type: Number,
        default: 10,
    },
});

const emit = defineEmits([
    "add-product",
    "view-product",
    "edit-product",
    "delete-product",
    "bulk-delete",
    "bulk-edit",
]);

// Selection

const selectedProducts = ref([]);

const productCount = computed(() => {
    return Array.isArray(props.products) ? props.products.length : 0;
});

const selectedCount = computed(() => {
    return selectedProducts.value.length;
});

const hasSelectedProducts = computed(() => {
    return selectedCount.value > 0;
});

const allSelected = computed(() => {
    return (
        productCount.value > 0 &&
        selectedCount.value === productCount.value
    );
});

// Keep selection in sync

watch(
    () => props.products,
    (products) => {
        const ids = new Set(
            Array.isArray(products)
                ? products.map((product) => product.id)
                : [],
        );

        selectedProducts.value = selectedProducts.value.filter((id) =>
            ids.has(id),
        );
    },
    {
        deep: true,
    },
);

// Selection actions

function toggleSelectAll() {
    if (allSelected.value) {
        selectedProducts.value = [];
        return;
    }

    selectedProducts.value = props.products.map((product) => product.id);
}

function toggleProduct(productId) {
    if (selectedProducts.value.includes(productId)) {
        selectedProducts.value = selectedProducts.value.filter(
            (id) => id !== productId,
        );

        return;
    }

    selectedProducts.value.push(productId);
}

// Product actions

function viewProduct(product) {
    emit("view-product", product);
}

function editProduct(product) {
    emit("edit-product", product);
}

function deleteProduct(product) {
    emit("delete-product", product);

    selectedProducts.value = selectedProducts.value.filter(
        (id) => id !== product.id,
    );
}

// Bulk edit

function bulkEdit() {
    if (!hasSelectedProducts.value) {
        return;
    }

    const productsToEdit = props.products.filter((product) =>
        selectedProducts.value.includes(product.id),
    );

    if (productsToEdit.length === 1) {
        emit("edit-product", productsToEdit[0]);
        return;
    }

    emit("bulk-edit", productsToEdit);
}

// Bulk delete

function bulkDelete() {
    if (!hasSelectedProducts.value) {
        return;
    }

    const confirmed = window.confirm(
        `Are you sure you want to delete ${selectedCount.value} selected product(s)?`,
    );

    if (!confirmed) {
        return;
    }

    const productsToDelete = props.products.filter((product) =>
        selectedProducts.value.includes(product.id),
    );

    emit("bulk-delete", productsToDelete);

    selectedProducts.value = [];
}

// Formatting

function formatPrice(price) {
    const amount = Number(price);

    if (Number.isNaN(amount)) {
        return "0.00";
    }

    return amount.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

// Stock status

function stockStatus(quantity) {
    const amount = Number(quantity) || 0;

    if (amount === 0) {
        return {
            text: "Out of stock",
            wrapper:
                "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400",
            dot: "bg-red-500",
        };
    }

    if (amount <= 5) {
        return {
            text: `${amount} left`,
            wrapper:
                "bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400",
            dot: "bg-amber-500",
        };
    }

    return {
        text: `${amount} in stock`,
        wrapper:
            "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400",
        dot: "bg-emerald-500",
    };
}

// Image URL

function imageUrl(image) {
    if (!image) {
        return null;
    }

    if (
        image.startsWith("http://") ||
        image.startsWith("https://") ||
        image.startsWith("/storage/")
    ) {
        return image;
    }

    return `/storage/${image}`;
}

// Product number

function productNumber(index) {
    return (props.currentPage - 1) * props.perPage + index + 1;
}
</script>

<template>
    <div class="w-full">

        <!-- Bulk actions -->

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
        >
            <div
                v-if="hasSelectedProducts"
                class="mb-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div
                    class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                >
                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-gray-900 px-2 text-xs font-bold text-white dark:bg-white dark:text-gray-900"
                        >
                            {{ selectedCount }}
                        </div>

                        <div>
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                Products selected
                            </p>

                            <p
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                Choose an action for the selected products.
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full gap-2 sm:w-auto">
                        <BaseButton
                            type="button"
                            variant="secondary"
                            class="flex-1 justify-center sm:flex-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                            @click="bulkEdit"
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
                                    stroke-width="1.8"
                                    d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3.75 19.5l1.037-4.5L16.862 3.487z"
                                />
                            </svg>

                            Edit
                        </BaseButton>

                        <BaseButton
                            type="button"
                            variant="danger"
                            class="flex-1 justify-center sm:flex-none"
                            @click="bulkDelete"
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
                                    stroke-width="1.8"
                                    d="M6 7h12m-9 4v5m6-5v5M9 7V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m-9 0l.75 12.25A1.75 1.75 0 008.5 21h7a1.75 1.75 0 001.75-1.75L18 7M4.5 7h15"
                                />
                            </svg>

                            Delete
                        </BaseButton>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Product table -->

        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="overflow-x-auto">

                <BaseTable>

                    <template #header>
                        <tr
                            class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
                        >
                            <th class="w-12 px-4 py-4 sm:px-5">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:ring-gray-500"
                                    aria-label="Select all products"
                                />
                            </th>

                            <th
                                class="whitespace-nowrap px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:px-5"
                            >
                                Product
                            </th>

                            <th
                                class="hidden px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 md:table-cell sm:px-5"
                            >
                                Description
                            </th>

                            <th
                                class="whitespace-nowrap px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:px-5"
                            >
                                Price
                            </th>

                            <th
                                class="whitespace-nowrap px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:px-5"
                            >
                                Stock
                            </th>

                            <th
                                class="whitespace-nowrap px-4 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:px-5"
                            >
                                Actions
                            </th>
                        </tr>
                    </template>

                    <template #body>

                        <tr
                            v-for="(product, index) in props.products"
                            :key="product.id"
                            class="group border-t border-gray-100 bg-white transition-colors hover:bg-gray-50/70 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700/60"
                            :class="{
                                'bg-gray-50/40 dark:bg-gray-700/80':
                                    selectedProducts.includes(product.id),
                            }"
                        >
                            <td class="px-4 py-4 sm:px-5">
                                <input
                                    type="checkbox"
                                    :checked="
                                        selectedProducts.includes(
                                            product.id,
                                        )
                                    "
                                    @change="
                                        toggleProduct(
                                            product.id,
                                        )
                                    "
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-900 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:ring-gray-500"
                                    :aria-label="`Select ${product.name}`"
                                />
                            </td>

                            <td class="px-4 py-4 sm:px-5">
                                <div
                                    class="flex min-w-[190px] items-center gap-3"
                                >
                                    <div
                                        class="relative h-11 w-11 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-600 dark:bg-gray-700"
                                    >
                                        <img
                                            v-if="imageUrl(product.image)"
                                            :src="imageUrl(product.image)"
                                            :alt="
                                                product.name ||
                                                'Product image'
                                            "
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />

                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center bg-gray-100 text-sm font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                product.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() ||
                                                "P"
                                            }}
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                        >
                                            {{
                                                product.name ||
                                                "Unnamed Product"
                                            }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                                        >
                                            Product #{{
                                                productNumber(index)
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td
                                class="hidden max-w-xs px-4 py-4 md:table-cell sm:px-5"
                            >
                                <p
                                    class="truncate text-sm text-gray-500 dark:text-gray-400"
                                    :title="
                                        product.description ||
                                        'No description'
                                    "
                                >
                                    {{
                                        product.description ||
                                        "No description"
                                    }}
                                </p>
                            </td>

                            <td
                                class="whitespace-nowrap px-4 py-4 sm:px-5"
                            >
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Rs.
                                    {{
                                        formatPrice(product.price)
                                    }}
                                </p>
                            </td>

                            <td
                                class="whitespace-nowrap px-4 py-4 sm:px-5"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-xs font-semibold"
                                    :class="
                                        stockStatus(
                                            product.quantity,
                                        ).wrapper
                                    "
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="
                                            stockStatus(
                                                product.quantity,
                                            ).dot
                                        "
                                    ></span>

                                    {{
                                        stockStatus(
                                            product.quantity,
                                        ).text
                                    }}
                                </span>
                            </td>

                            <td
                                class="whitespace-nowrap px-4 py-4 sm:px-5"
                            >
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <button
                                        type="button"
                                        title="View product"
                                        aria-label="View product"
                                        @click="viewProduct(product)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-600"
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
                                                stroke-width="1.8"
                                                d="M2.75 12s3.25-6 9.25-6 9.25 6 9.25 6-3.25 6-9.25 6-9.25-6-9.25-6z"
                                            />

                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="2.5"
                                                stroke-width="1.8"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        title="Edit product"
                                        aria-label="Edit product"
                                        @click="editProduct(product)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-blue-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:text-gray-500 dark:hover:bg-blue-950/40 dark:hover:text-blue-400 dark:focus:ring-blue-900"
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
                                                stroke-width="1.8"
                                                d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3.75 19.5l1.037-4.5L16.862 3.487z"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        title="Delete product"
                                        aria-label="Delete product"
                                        @click="deleteProduct(product)"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-100 dark:text-gray-500 dark:hover:bg-red-950/40 dark:hover:text-red-400 dark:focus:ring-red-900"
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
                                                stroke-width="1.8"
                                                d="M6 7h12m-9 4v5m6-5v5M9 7V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m-9 0l.75 12.25A1.75 1.75 0 008.5 21h7a1.75 1.75 0 001.75-1.75L18 7M4.5 7h15"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="productCount === 0">
                            <td
                                colspan="6"
                                class="px-6 py-20 text-center"
                            >
                                <div
                                    class="mx-auto flex max-w-sm flex-col items-center"
                                >
                                    <div
                                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700"
                                    >
                                        <svg
                                            class="h-7 w-7 text-gray-400 dark:text-gray-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </div>

                                    <h3
                                        class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        No products found
                                    </h3>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Try changing your search or filters.
                                    </p>

                                    <BaseButton
                                        type="button"
                                        class="mt-5"
                                        @click="emit('add-product')"
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
                                                stroke-width="1.8"
                                                d="M12 5v14m-7-7h14"
                                            />
                                        </svg>

                                        Add Product
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>

                    </template>

                </BaseTable>

            </div>
        </div>

    </div>
</template>
