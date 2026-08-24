<script setup>
import { computed, ref, watch } from "vue";

import BaseTable from "../common/BaseTable.vue";
import BaseButton from "../common/BaseButton.vue";

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
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

const selectedProducts = ref([]);

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

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
        selectedProducts.value.length === productCount.value
    );
});

/*
|--------------------------------------------------------------------------
| Keep selected products synchronized
|--------------------------------------------------------------------------
*/

watch(
    () => props.products,
    (products) => {
        const productIds = new Set(
            Array.isArray(products)
                ? products.map((product) => product.id)
                : [],
        );

        selectedProducts.value = selectedProducts.value.filter((id) =>
            productIds.has(id),
        );
    },
    {
        deep: true,
    },
);

/*
|--------------------------------------------------------------------------
| Selection
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Product Actions
|--------------------------------------------------------------------------
*/

function viewProduct(product) {
    emit("view-product", product);
}

function editProduct(product) {
    emit("edit-product", product);
}

function deleteProduct(product) {
    const confirmed = confirm(
        `Are you sure you want to delete "${product.name}"?`,
    );

    if (!confirmed) {
        return;
    }

    emit("delete-product", product);

    selectedProducts.value = selectedProducts.value.filter(
        (id) => id !== product.id,
    );
}

/*
|--------------------------------------------------------------------------
| Bulk Edit
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Bulk Delete
|--------------------------------------------------------------------------
*/

function bulkDelete() {
    if (!hasSelectedProducts.value) {
        return;
    }

    const confirmed = confirm(
        `Are you sure you want to delete ${selectedProducts.value.length} selected product(s)?`,
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

/*
|--------------------------------------------------------------------------
| Formatting
|--------------------------------------------------------------------------
*/

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

function stockStatus(quantity) {
    const amount = Number(quantity) || 0;

    if (amount === 0) {
        return {
            text: "Out of stock",
            wrapper: "bg-red-50 text-red-700",
            dot: "bg-red-500",
        };
    }

    if (amount <= 5) {
        return {
            text: `${amount} left`,
            wrapper: "bg-yellow-50 text-yellow-700",
            dot: "bg-yellow-500",
        };
    }

    return {
        text: `${amount} in stock`,
        wrapper: "bg-green-50 text-green-700",
        dot: "bg-green-500",
    };
}
</script>

<template>
    <div>
        <!-- header -->
        <div class="mb-8">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Products
                </h1>

                <span
                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                >
                    {{ productCount }}
                </span>
            </div>

            <p class="mt-1 text-sm text-gray-500">
                Manage your product inventory.
            </p>
        </div>

        <!-- =========================================================
             BULK ACTIONS
        ========================================================== -->

        <div
            v-if="hasSelectedProducts"
            class="mb-4 flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm"
        >
            <span class="text-sm font-semibold text-gray-700">
                {{ selectedCount }} selected
            </span>

            <div class="flex gap-2">
                <BaseButton type="button" variant="secondary" @click="bulkEdit">
                    Edit Selected
                </BaseButton>

                <BaseButton type="button" variant="danger" @click="bulkDelete">
                    Delete Selected
                </BaseButton>
            </div>
        </div>

        <!-- =========================================================
             REUSABLE TABLE
        ========================================================== -->

        <BaseTable>
            <!-- =====================================================
                 TABLE HEADER
            ====================================================== -->

            <template #header>
                <tr>
                    <!-- Select All -->
                    <th class="w-12 px-6 py-4">
                        <input
                            type="checkbox"
                            :checked="allSelected"
                            @change="toggleSelectAll"
                            class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                        />
                    </th>

                    <!-- Product -->
                    <th
                        class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Product
                    </th>

                    <!-- Description -->
                    <th
                        class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Description
                    </th>

                    <!-- Price -->
                    <th
                        class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Price
                    </th>

                    <!-- Stock -->
                    <th
                        class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Stock
                    </th>

                    <!-- Actions -->
                    <th
                        class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Actions
                    </th>
                </tr>
            </template>

            <!-- =====================================================
                 TABLE BODY
            ====================================================== -->

            <template #body>
                <!-- =================================================
                     PRODUCTS
                ================================================== -->

                <tr
                    v-for="(product, index) in props.products"
                    :key="product.id"
                    class="hover:bg-gray-50"
                >
                    <!-- Checkbox -->
                    <td class="px-6 py-4">
                        <input
                            type="checkbox"
                            :checked="selectedProducts.includes(product.id)"
                            @change="toggleProduct(product.id)"
                            class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                        />
                    </td>

                    <!-- Product -->
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <!-- Product Initial -->
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-700"
                            >
                                {{ product.name?.charAt(0)?.toUpperCase() }}
                            </div>

                            <div class="min-w-0">
                                <div
                                    class="truncate font-semibold text-gray-900"
                                >
                                    {{ product.name || "Unnamed Product" }}
                                </div>

                                <div class="mt-0.5 text-xs text-gray-400">
                                    Product #{{ index + 1 }}
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Description -->
                    <td class="max-w-xs px-6 py-4">
                        <p class="truncate text-gray-500">
                            {{ product.description || "No description" }}
                        </p>
                    </td>

                    <!-- Price -->
                    <td class="whitespace-nowrap px-6 py-4">
                        <span class="font-semibold text-gray-900">
                            Rs.
                            {{ formatPrice(product.price) }}
                        </span>
                    </td>

                    <!-- Stock -->
                    <td class="whitespace-nowrap px-6 py-4">
                        <span
                            :class="stockStatus(product.quantity).wrapper"
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                        >
                            <span
                                :class="stockStatus(product.quantity).dot"
                                class="h-1.5 w-1.5 rounded-full"
                            ></span>

                            {{ stockStatus(product.quantity).text }}
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-4">
                            <!-- View -->
                            <button
                                type="button"
                                @click="viewProduct(product)"
                                class="text-sm font-medium text-gray-500 hover:text-gray-900"
                            >
                                View
                            </button>

                            <!-- Edit -->
                            <button
                                type="button"
                                @click="editProduct(product)"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                Edit
                            </button>

                            <!-- Delete -->
                            <button
                                type="button"
                                @click="deleteProduct(product)"
                                class="text-sm font-medium text-red-600 hover:text-red-800"
                            >
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- =================================================
                     EMPTY STATE
                ================================================== -->

                <tr v-if="productCount === 0">
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="mx-auto max-w-sm">
                            <!-- Icon -->
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100"
                            >
                                <svg
                                    class="h-6 w-6 text-gray-500"
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
                                class="mt-4 text-base font-semibold text-gray-900"
                            >
                                No products found
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Get started by adding your first product.
                            </p>

                            <BaseButton
                                type="button"
                                class="mt-5"
                                @click="emit('add-product')"
                            >
                                Add Product
                            </BaseButton>
                        </div>
                    </td>
                </tr>
            </template>
        </BaseTable>
    </div>
</template>
