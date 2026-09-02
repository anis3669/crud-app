<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import { useProductStore } from "../../stores/product";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import BaseModal from "../common/BaseModal.vue";

const router = useRouter();

const productStore = useProductStore();
const toastStore = useToastStore();

const selectedProducts = ref([]);

const showDeleteModal = ref(false);
const productToDelete = ref(null);
const deleting = ref(false);

const products = computed(() => {
    return Array.isArray(productStore.trash)
        ? productStore.trash
        : [];
});

const loading = computed(() => {
    return productStore.trashLoading;
});

const error = computed(() => {
    return productStore.trashError;
});

const currentPage = computed(() => {
    return productStore.trashCurrentPage;
});

const lastPage = computed(() => {
    return productStore.trashLastPage;
});

const total = computed(() => {
    return productStore.trashTotal;
});

const perPage = computed(() => {
    return productStore.trashPerPage;
});

const allSelected = computed(() => {
    return (
        products.value.length > 0 &&
        selectedProducts.value.length === products.value.length
    );
});

const someSelected = computed(() => {
    return selectedProducts.value.length > 0;
});

const firstProductNumber = computed(() => {
    if (total.value === 0) {
        return 0;
    }

    return (currentPage.value - 1) * perPage.value + 1;
});

const lastProductNumber = computed(() => {
    return Math.min(
        currentPage.value * perPage.value,
        total.value,
    );
});

// Get category name safely

function categoryName(product) {
    if (product.category?.name) {
        return product.category.name;
    }

    if (typeof product.category === "string") {
        return product.category;
    }

    return "—";
}

// Get supplier name safely

function supplierName(product) {
    if (product.supplier?.name) {
        return product.supplier.name;
    }

    if (typeof product.supplier === "string") {
        return product.supplier;
    }

    return "—";
}

// Get image URL safely

function imageUrl(image) {
    if (!image) {
        return null;
    }

    const value = String(image);

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/storage/")
    ) {
        return value;
    }

    return `/storage/${value}`;
}

// Format price

function formatPrice(price) {
    const value = Number(price);

    if (Number.isNaN(value)) {
        return "0.00";
    }

    return value.toLocaleString("en-IN", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

// Format deleted date

function formatDeletedDate(date) {
    if (!date) {
        return "—";
    }

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return "—";
    }

    return parsedDate.toLocaleDateString();
}

// Toggle one product

function toggleProduct(productId) {
    if (selectedProducts.value.includes(productId)) {
        selectedProducts.value =
            selectedProducts.value.filter(
                (id) => id !== productId,
            );
    } else {
        selectedProducts.value.push(productId);
    }
}

// Toggle all products on current page

function toggleAll() {
    if (allSelected.value) {
        selectedProducts.value = [];
    } else {
        selectedProducts.value = products.value.map(
            (product) => product.id,
        );
    }
}

// Clear selection

function clearSelection() {
    selectedProducts.value = [];
}

// Restore one product

async function restoreProduct(product) {
    try {
        await productStore.restoreProduct(product.id);

        selectedProducts.value =
            selectedProducts.value.filter(
                (id) => id !== product.id,
            );

        toastStore.success(
            "Product restored successfully.",
        );
    } catch (err) {
        console.error(
            "Restore product error:",
            err,
        );
    }
}

// Restore selected products

async function bulkRestore() {
    if (selectedProducts.value.length === 0) {
        return;
    }

    try {
        await productStore.bulkRestore(
            selectedProducts.value,
        );

        selectedProducts.value = [];

        toastStore.success(
            "Products restored successfully.",
        );
    } catch (err) {
        console.error(
            "Bulk restore error:",
            err,
        );
    }
}

// Open permanent delete modal

function openDeleteModal(product) {
    productToDelete.value = product;
    showDeleteModal.value = true;
}

// Close permanent delete modal

function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
    productToDelete.value = null;
}

// Permanently delete one product

async function confirmPermanentDelete() {
    if (!productToDelete.value) {
        return;
    }

    deleting.value = true;

    const productId = productToDelete.value.id;

    try {
        await productStore.permanentlyDeleteProduct(
            productId,
        );

        selectedProducts.value =
            selectedProducts.value.filter(
                (id) => id !== productId,
            );

        toastStore.success(
            "Product permanently deleted.",
        );

        showDeleteModal.value = false;
        productToDelete.value = null;
    } catch (err) {
        console.error(
            "Permanent delete error:",
            err,
        );
    } finally {
        deleting.value = false;
    }
}

// Permanently delete selected products

async function bulkPermanentDelete() {
    if (selectedProducts.value.length === 0) {
        return;
    }

    try {
        await productStore.bulkPermanentDelete(
            selectedProducts.value,
        );

        selectedProducts.value = [];

        toastStore.success(
            "Products permanently deleted.",
        );
    } catch (err) {
        console.error(
            "Bulk permanent delete error:",
            err,
        );
    }
}

// Change trash page

async function goToPage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return;
    }

    selectedProducts.value = [];

    await productStore.fetchTrash(page);
}

// Refresh trash

async function refreshTrash() {
    selectedProducts.value = [];

    await productStore.fetchTrash(
        currentPage.value,
    );
}

// Go back to products

function goBack() {
    router.push({
        name: "products.index",
    });
}

// Initial load

onMounted(async () => {
    await productStore.fetchTrash();
});
</script>

<template>
    <div
        class="min-h-[calc(100vh-4rem)] bg-gray-50 transition-colors duration-300 dark:bg-gray-950"
    >
        <div
            class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-4 sm:py-6 lg:px-6"
        >
            <!-- Header -->

            <div class="mb-6">
                <div
                    class="flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:px-6 sm:py-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 dark:bg-red-950/40"
                        >
                            <svg
                                class="h-5 w-5 text-red-600 dark:text-red-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h12"
                                />
                            </svg>
                        </div>

                        <div>
                            <h1
                                class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-2xl"
                            >
                                Trash
                            </h1>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Manage deleted products
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full gap-2 sm:w-auto">
                        <BaseButton
                            type="button"
                            variant="secondary"
                            class="flex-1 justify-center sm:flex-none"
                            @click="goBack"
                        >
                            Back
                        </BaseButton>

                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="loading"
                            class="flex-1 justify-center sm:flex-none"
                            @click="refreshTrash"
                        >
                            <svg
                                class="h-4 w-4"
                                :class="{
                                    'animate-spin': loading,
                                }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h5M20 20v-5h-5M5.07 9A7 7 0 0117.9 6.1L20 9M19 15a7 7 0 01-12.83 2.9L4 15"
                                />
                            </svg>

                            <span>
                                {{
                                    loading
                                        ? "Refreshing..."
                                        : "Refresh"
                                }}
                            </span>
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- Error -->

            <BaseCard
                v-if="error"
                class="mb-6 border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30"
            >
                <div
                    class="flex items-center justify-between gap-4"
                >
                    <p
                        class="text-sm text-red-700 dark:text-red-400"
                    >
                        {{ error }}
                    </p>

                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="refreshTrash"
                    >
                        Try Again
                    </BaseButton>
                </div>
            </BaseCard>

            <!-- Bulk Actions -->

            <div
                v-if="someSelected"
                class="mb-4 flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between"
            >
                <p
                    class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                    {{ selectedProducts.length }}
                    product<span
                        v-if="selectedProducts.length !== 1"
                    >s</span>
                    selected
                </p>

                <div class="flex flex-wrap gap-2">
                    <BaseButton
                        type="button"
                        :disabled="loading"
                        class="justify-center"
                        @click="bulkRestore"
                    >
                        Restore
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="danger"
                        :disabled="loading"
                        class="justify-center"
                        @click="bulkPermanentDelete"
                    >
                        Delete Forever
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="loading"
                        class="justify-center"
                        @click="clearSelection"
                    >
                        Clear
                    </BaseButton>
                </div>
            </div>

            <!-- Table -->

            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Loading -->

                <div
                    v-if="loading && products.length === 0"
                    class="flex flex-col items-center justify-center py-20"
                >
                    <svg
                        class="h-8 w-8 animate-spin text-gray-500"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-20"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="3"
                        />

                        <path
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"
                        />
                    </svg>

                    <p
                        class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400"
                    >
                        Loading trash...
                    </p>
                </div>

                <!-- Empty -->

                <div
                    v-else-if="products.length === 0"
                    class="flex flex-col items-center justify-center px-6 py-20 text-center"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                    >
                        <svg
                            class="h-7 w-7 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h12"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-5 text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Trash is empty
                    </h3>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Deleted products will appear here.
                    </p>
                </div>

                <!-- Products -->

                <div
                    v-else
                    class="overflow-x-auto"
                >
                    <table
                        class="min-w-full divide-y divide-gray-200 dark:divide-gray-800"
                    >
                        <thead
                            class="bg-gray-50 dark:bg-gray-800/50"
                        >
                            <tr>
                                <th class="w-12 px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400 dark:border-gray-600"
                                        @change="toggleAll"
                                    />
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Product
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    SKU
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Category
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Supplier
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Price
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Quantity
                                </th>

                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Deleted
                                </th>

                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-800"
                        >
                            <tr
                                v-for="product in products"
                                :key="product.id"
                                class="transition hover:bg-gray-50 dark:hover:bg-gray-800/40"
                            >
                                <!-- Checkbox -->

                                <td class="px-4 py-4">
                                    <input
                                        type="checkbox"
                                        :checked="
                                            selectedProducts.includes(
                                                product.id,
                                            )
                                        "
                                        class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-400 dark:border-gray-600"
                                        @change="
                                            toggleProduct(
                                                product.id,
                                            )
                                        "
                                    />
                                </td>

                                <!-- Product -->

                                <td class="px-4 py-4">
                                    <div
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
                                        >
                                            <img
                                                v-if="
                                                    imageUrl(
                                                        product.image,
                                                    )
                                                "
                                                :src="
                                                    imageUrl(
                                                        product.image,
                                                    )
                                                "
                                                :alt="
                                                    product.name
                                                "
                                                class="h-full w-full object-cover"
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center text-xs text-gray-400"
                                            >
                                                N/A
                                            </div>
                                        </div>

                                        <div
                                            class="min-w-0"
                                        >
                                            <p
                                                class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ product.name }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                ID #{{ product.id }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- SKU -->

                                <td
                                    class="px-4 py-4 text-sm font-mono text-gray-600 dark:text-gray-300"
                                >
                                    {{ product.sku || "—" }}
                                </td>

                                <!-- Category -->

                                <td
                                    class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ categoryName(product) }}
                                </td>

                                <!-- Supplier -->

                                <td
                                    class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ supplierName(product) }}
                                </td>

                                <!-- Price -->

                                <td
                                    class="whitespace-nowrap px-4 py-4 text-sm font-medium text-gray-900 dark:text-white"
                                >
                                    Rs. {{ formatPrice(product.price) }}
                                </td>

                                <!-- Quantity -->

                                <td
                                    class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ product.quantity }}
                                </td>

                                <!-- Deleted -->

                                <td
                                    class="whitespace-nowrap px-4 py-4 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        formatDeletedDate(
                                            product.deleted_at,
                                        )
                                    }}
                                </td>

                                <!-- Actions -->

                                <td class="px-4 py-4">
                                    <div
                                        class="flex justify-end gap-2"
                                    >
                                        <BaseButton
                                            type="button"
                                            :disabled="loading"
                                            class="justify-center"
                                            @click="
                                                restoreProduct(
                                                    product,
                                                )
                                            "
                                        >
                                            Restore
                                        </BaseButton>

                                        <BaseButton
                                            type="button"
                                            variant="danger"
                                            :disabled="
                                                loading ||
                                                deleting
                                            "
                                            class="justify-center"
                                            @click="
                                                openDeleteModal(
                                                    product,
                                                )
                                            "
                                        >
                                            Delete
                                        </BaseButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Loading overlay -->

                <div
                    v-if="
                        loading &&
                        products.length > 0
                    "
                    class="border-t border-gray-200 bg-white/70 px-4 py-3 text-center dark:border-gray-800 dark:bg-gray-900/70"
                >
                    <span
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Loading...
                    </span>
                </div>
            </div>

            <!-- Pagination -->

            <div
                v-if="total > 0"
                class="mt-6 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{ firstProductNumber }}–{{
                                lastProductNumber
                            }}
                        </span>

                        <span class="text-gray-400">
                            of
                        </span>

                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{ total }}
                        </span>

                        <span
                            class="text-gray-500 dark:text-gray-400"
                        >
                            deleted products
                        </span>
                    </p>

                    <div class="flex gap-2">
                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="
                                currentPage <= 1 ||
                                loading
                            "
                            @click="
                                goToPage(
                                    currentPage - 1,
                                )
                            "
                        >
                            Previous
                        </BaseButton>

                        <div
                            class="flex h-9 items-center rounded-lg bg-gray-900 px-3 text-sm font-semibold text-white dark:bg-white dark:text-gray-900"
                        >
                            {{ currentPage }}

                            <span
                                class="mx-1 text-gray-400"
                            >
                                /
                            </span>

                            {{ lastPage }}
                        </div>

                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="
                                currentPage >=
                                    lastPage ||
                                loading
                            "
                            @click="
                                goToPage(
                                    currentPage + 1,
                                )
                            "
                        >
                            Next
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- Permanent Delete Modal -->

            <BaseModal
                :show="showDeleteModal"
                title="Delete Forever"
                size="sm"
                @close="closeDeleteModal"
            >
                <div class="text-center">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 dark:bg-red-950/40"
                    >
                        <svg
                            class="h-7 w-7 text-red-600 dark:text-red-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-5 text-lg font-bold text-gray-900 dark:text-white"
                    >
                        Delete Forever?
                    </h3>

                    <p
                        class="mx-auto mt-2 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400"
                    >
                        Are you sure you want to permanently
                        delete

                        <span
                            class="font-semibold text-gray-800 dark:text-gray-200"
                        >
                            {{ productToDelete?.name }}
                        </span>

                        ?

                        <br />

                        This action cannot be undone.
                    </p>
                </div>

                <template #footer>
                    <div
                        class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="deleting"
                            class="justify-center"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </BaseButton>

                        <BaseButton
                            type="button"
                            variant="danger"
                            :disabled="deleting"
                            class="justify-center"
                            @click="
                                confirmPermanentDelete
                            "
                        >
                            {{
                                deleting
                                    ? "Deleting..."
                                    : "Delete Forever"
                            }}
                        </BaseButton>
                    </div>
                </template>
            </BaseModal>
        </div>
    </div>
</template>
