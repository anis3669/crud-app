<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

import BaseButton from "../common/BaseButton.vue";
import BaseModal from "../common/BaseModal.vue";
import BaseTable from "../common/BaseTable.vue";

import { useInventoryStore } from "../../stores/inventory";
import { useToastStore } from "../../stores/toast";
import { useAuthStore } from "../../stores/auth";

const inventoryStore = useInventoryStore();
const toastStore = useToastStore();
const authStore = useAuthStore();

// State
const searchInput = ref("");
const selectedProduct = ref(null);

const showAdjustmentModal = ref(false);
const showHistoryModal = ref(false);

const submittingAdjustment = ref(false);

const adjustment = ref({
    type: "stock_in",
    quantity: null,
    reason: "",
});

let searchTimer = null;

// Store data
const products = computed(() => inventoryStore.products);
const loading = computed(() => inventoryStore.loading);
const error = computed(() => inventoryStore.error);

const currentPage = computed(() => inventoryStore.currentPage);
const lastPage = computed(() => inventoryStore.lastPage);
const perPage = computed(() => inventoryStore.perPage);
const total = computed(() => inventoryStore.total);

const stats = computed(() => inventoryStore.stats);

const history = computed(() => inventoryStore.history);
const historyLoading = computed(() => inventoryStore.historyLoading);
const historyError = computed(() => inventoryStore.historyError);
const historyCurrentPage = computed(
    () => inventoryStore.historyCurrentPage,
);
const historyLastPage = computed(
    () => inventoryStore.historyLastPage,
);

// Permissions
const canAdjustStock = computed(() => {
    return authStore.can("inventory.adjust");
});

const canViewHistory = computed(() => {
    return authStore.can("inventory.history");
});

// Pagination
const hasPreviousPage = computed(() => {
    return currentPage.value > 1;
});

const hasNextPage = computed(() => {
    return currentPage.value < lastPage.value;
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

// Helpers
function stockQuantity(product) {
    return Math.max(0, Number(product?.quantity) || 0);
}

function stockStatus(product) {
    const quantity = stockQuantity(product);

    if (quantity === 0) {
        return {
            label: "Out of stock",
            classes:
                "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400",
            dot: "bg-red-500",
        };
    }

    if (quantity <= 10) {
        return {
            label: "Low stock",
            classes:
                "bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400",
            dot: "bg-amber-500",
        };
    }

    return {
        label: "In stock",
        classes:
            "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400",
        dot: "bg-emerald-500",
    };
}

function categoryName(product) {
    return (
        product?.category?.name ||
        product?.category ||
        "Uncategorized"
    );
}

function supplierName(product) {
    return (
        product?.supplier?.name ||
        product?.supplier ||
        "—"
    );
}

function formatDate(value) {
    if (!value) {
        return "—";
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "—";
    }

    return new Intl.DateTimeFormat("en", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    }).format(date);
}

function signedChange(record) {
    const amount = Number(record?.quantity_change) || 0;

    return amount > 0 ? `+${amount}` : amount;
}

function movementLabel(type) {
    return String(type || "adjustment")
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) =>
            character.toUpperCase(),
        );
}

// Inventory
async function loadInventory(
    page = inventoryStore.currentPage,
) {
    try {
        await inventoryStore.fetchInventory(
            page,
            searchInput.value.trim(),
        );
    } catch (requestError) {
        console.error(
            "Failed to load stock:",
            requestError,
        );
    }
}

async function refreshInventory() {
    await loadInventory(currentPage.value);
}

async function goToPage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value ||
        loading.value
    ) {
        return;
    }

    await loadInventory(page);
}

function clearSearch() {
    searchInput.value = "";
}

// Adjustment
function resetAdjustment() {
    adjustment.value = {
        type: "stock_in",
        quantity: null,
        reason: "",
    };
}

function openAdjustmentModal(product) {
    if (!canAdjustStock.value || submittingAdjustment.value) {
        return;
    }

    selectedProduct.value = product;
    resetAdjustment();

    showAdjustmentModal.value = true;
}

function closeAdjustmentModal() {
    if (submittingAdjustment.value) {
        return;
    }

    showAdjustmentModal.value = false;
    selectedProduct.value = null;

    resetAdjustment();
}

async function submitAdjustment() {
    if (submittingAdjustment.value) {
        return;
    }

    if (!selectedProduct.value) {
        return;
    }

    const quantity = Number(adjustment.value.quantity);
    const reason = adjustment.value.reason.trim();

    if (!Number.isInteger(quantity) || quantity <= 0) {
        return;
    }

    if (!reason) {
        return;
    }

    submittingAdjustment.value = true;

    try {
        const response = await inventoryStore.adjustStock(
            selectedProduct.value.id,
            {
                type: adjustment.value.type,
                quantity,
                reason,
            },
        );

        toastStore.success(
            response?.message ||
                "Stock updated successfully.",
        );

        // Close modal immediately after success
        showAdjustmentModal.value = false;

        selectedProduct.value = null;

        resetAdjustment();

        // Refresh current stock page
        await refreshInventory();
    } catch (requestError) {
        console.error(
            "Failed to adjust stock:",
            requestError,
        );
    } finally {
        submittingAdjustment.value = false;
    }
}

// History
async function openHistoryModal(product) {
    if (!canViewHistory.value || historyLoading.value) {
        return;
    }

    selectedProduct.value = product;
    showHistoryModal.value = true;

    await loadHistory(1);
}

function closeHistoryModal() {
    showHistoryModal.value = false;
    selectedProduct.value = null;
}

async function loadHistory(page) {
    if (
        !selectedProduct.value ||
        historyLoading.value
    ) {
        return;
    }

    if (
        page < 1 ||
        page > historyLastPage.value
    ) {
        return;
    }

    try {
        await inventoryStore.fetchHistory(
            selectedProduct.value.id,
            page,
        );
    } catch (requestError) {
        console.error(
            "Failed to load stock history:",
            requestError,
        );
    }
}

// Search
watch(searchInput, () => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        loadInventory(1);
    }, 350);
});

// Lifecycle
onMounted(() => {
    searchInput.value = inventoryStore.search;

    loadInventory(1);
});

onUnmounted(() => {
    clearTimeout(searchTimer);
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
            <section
                class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
            >
                <div
                    class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div
                        class="flex min-w-0 items-center gap-4"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-900 text-white shadow-sm dark:bg-white dark:text-gray-900"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10l8 4"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h1
                                class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-2xl"
                            >
                                Stock
                            </h1>

                            <p
                                class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-400"
                            >
                                Monitor stock levels and manage
                                inventory movements.
                            </p>
                        </div>
                    </div>

                    <BaseButton
                        variant="secondary"
                        :disabled="loading"
                        class="w-full sm:w-auto"
                        @click="refreshInventory"
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

                        {{ loading ? "Refreshing..." : "Refresh" }}
                    </BaseButton>
                </div>
            </section>

            <!-- Stats -->
            <section
                class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4"
            >
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Products
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ stats.total_products ?? total }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Total units
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ stats.total_quantity ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Low stock
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400"
                    >
                        {{ stats.low_stock ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
                >
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Out of stock
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400"
                    >
                        {{ stats.out_of_stock ?? 0 }}
                    </p>
                </div>
            </section>

            <!-- Search -->
            <section
                class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
            >
                <label
                    for="stock-search"
                    class="sr-only"
                >
                    Search stock
                </label>

                <div class="relative w-full max-w-xl">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                            stroke-width="2"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-width="2"
                            d="m20 20-3.5-3.5"
                        />
                    </svg>

                    <input
                        id="stock-search"
                        v-model="searchInput"
                        type="search"
                        placeholder="Search by product, SKU, category, or supplier..."
                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-400 dark:focus:ring-gray-800"
                    />

                    <button
                        v-if="searchInput"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        ×
                    </button>
                </div>
            </section>

            <!-- Error -->
            <div
                v-if="error"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-400"
            >
                {{ error }}
            </div>

            <!-- Stock table -->
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Loading -->
                <div
                    v-if="loading && !products.length"
                    class="px-6 py-20 text-center"
                >
                    <div
                        class="mx-auto mb-3 h-7 w-7 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900 dark:border-gray-700 dark:border-t-white"
                    ></div>

                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Loading stock...
                    </p>
                </div>

                <BaseTable
                    v-else
                    min-width="960px"
                >
                    <template #header>
                        <tr>
                            <th
                                class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Product
                            </th>

                            <th
                                class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                SKU
                            </th>

                            <th
                                class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Category
                            </th>

                            <th
                                class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Supplier
                            </th>

                            <th
                                class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Stock
                            </th>

                            <th
                                class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </template>

                    <template #body>
                        <tr
                            v-for="product in products"
                            :key="product.id"
                            class="border-t border-gray-100 transition-colors hover:bg-gray-50/70 dark:border-gray-800 dark:hover:bg-gray-800/70"
                        >
                            <!-- Product -->
                            <td class="px-5 py-4">
                                <p
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ product.name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    #{{ product.id }}
                                </p>
                            </td>

                            <!-- SKU -->
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ product.sku || "—" }}
                            </td>

                            <!-- Category -->
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"
                            >
                                {{ categoryName(product) }}
                            </td>

                            <!-- Supplier -->
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"
                            >
                                {{ supplierName(product) }}
                            </td>

                            <!-- Stock -->
                            <td
                                class="whitespace-nowrap px-5 py-4"
                            >
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="min-w-8 text-lg font-bold text-gray-900 dark:text-white"
                                    >
                                        {{ stockQuantity(product) }}
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="
                                            stockStatus(product)
                                                .classes
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                stockStatus(product)
                                                    .dot
                                            "
                                        ></span>

                                        {{
                                            stockStatus(product)
                                                .label
                                        }}
                                    </span>
                                </div>
                            </td>

                            <!-- Actions -->
                            <td
                                class="whitespace-nowrap px-5 py-4 text-right"
                            >
                                <div
                                    class="flex justify-end gap-2"
                                >
                                    <button
                                        v-if="canViewHistory"
                                        type="button"
                                        class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                                        @click="
                                            openHistoryModal(
                                                product,
                                            )
                                        "
                                    >
                                        History
                                    </button>

                                    <BaseButton
                                        v-if="canAdjustStock"
                                        class="px-3 py-2"
                                        @click="
                                            openAdjustmentModal(
                                                product,
                                            )
                                        "
                                    >
                                        Adjust
                                    </BaseButton>

                                    <span
                                        v-if="
                                            !canViewHistory &&
                                            !canAdjustStock
                                        "
                                        class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500"
                                    >
                                        View only
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!products.length">
                            <td
                                colspan="6"
                                class="px-6 py-20 text-center"
                            >
                                <div
                                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <svg
                                        class="h-6 w-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"
                                        />
                                    </svg>
                                </div>

                                <p
                                    class="mt-4 font-semibold text-gray-900 dark:text-white"
                                >
                                    No stock items found
                                </p>

                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Try changing your search or
                                    check the Products section.
                                </p>
                            </td>
                        </tr>
                    </template>
                </BaseTable>

                <!-- Pagination -->
                <div
                    v-if="total > 0"
                    class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Showing
                        {{ firstProductNumber }}–{{
                            lastProductNumber
                        }}
                        of {{ total }} products
                    </p>

                    <div
                        class="flex items-center justify-between gap-2 sm:justify-end"
                    >
                        <BaseButton
                            variant="secondary"
                            :disabled="
                                !hasPreviousPage || loading
                            "
                            @click="
                                goToPage(currentPage - 1)
                            "
                        >
                            Previous
                        </BaseButton>

                        <div
                            class="flex h-9 shrink-0 items-center rounded-lg bg-gray-900 px-3 text-sm font-semibold text-white dark:bg-white dark:text-gray-900"
                        >
                            {{ currentPage }}

                            <span class="mx-1 text-gray-400">
                                /
                            </span>

                            {{ lastPage }}
                        </div>

                        <BaseButton
                            variant="secondary"
                            :disabled="
                                !hasNextPage || loading
                            "
                            @click="
                                goToPage(currentPage + 1)
                            "
                        >
                            Next
                        </BaseButton>
                    </div>
                </div>
            </section>
        </div>

        <!-- Stock adjustment modal -->
        <BaseModal
            :show="showAdjustmentModal"
            title="Adjust stock"
            @close="closeAdjustmentModal"
        >
            <div
                v-if="selectedProduct"
                class="space-y-5"
            >
                <!-- Product summary -->
                <div
                    class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate font-semibold text-gray-900 dark:text-white"
                            >
                                {{ selectedProduct.name }}
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{
                                    selectedProduct.sku ||
                                    "No SKU"
                                }}
                            </p>
                        </div>

                        <div
                            class="text-left sm:text-right"
                        >
                            <p
                                class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Current stock
                            </p>

                            <p
                                class="mt-1 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                {{
                                    stockQuantity(
                                        selectedProduct,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Movement type -->
                <div>
                    <label
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Movement type
                    </label>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label
                            class="cursor-pointer rounded-xl border p-3 text-sm font-medium transition"
                            :class="
                                adjustment.type === 'stock_in'
                                    ? 'border-gray-900 bg-gray-50 text-gray-900 dark:border-white dark:bg-gray-800 dark:text-white'
                                    : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                v-model="adjustment.type"
                                type="radio"
                                value="stock_in"
                                class="sr-only"
                                :disabled="
                                    submittingAdjustment
                                "
                            />

                            Stock in

                            <span
                                class="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400"
                            >
                                Add units to stock
                            </span>
                        </label>

                        <label
                            class="cursor-pointer rounded-xl border p-3 text-sm font-medium transition"
                            :class="
                                adjustment.type === 'stock_out'
                                    ? 'border-gray-900 bg-gray-50 text-gray-900 dark:border-white dark:bg-gray-800 dark:text-white'
                                    : 'border-gray-200 text-gray-600 hover:border-gray-300 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600'
                            "
                        >
                            <input
                                v-model="adjustment.type"
                                type="radio"
                                value="stock_out"
                                class="sr-only"
                                :disabled="
                                    submittingAdjustment
                                "
                            />

                            Stock out

                            <span
                                class="mt-1 block text-xs font-normal text-gray-500 dark:text-gray-400"
                            >
                                Remove units from stock
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label
                        for="adjustment-quantity"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Quantity
                    </label>

                    <input
                        id="adjustment-quantity"
                        v-model.number="adjustment.quantity"
                        min="1"
                        step="1"
                        type="number"
                        inputmode="numeric"
                        placeholder="Enter quantity"
                        :disabled="submittingAdjustment"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-400 dark:focus:ring-gray-800 dark:disabled:bg-gray-900"
                    />
                </div>

                <!-- Reason -->
                <div>
                    <label
                        for="adjustment-reason"
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Reason
                    </label>

                    <textarea
                        id="adjustment-reason"
                        v-model="adjustment.reason"
                        rows="3"
                        placeholder="For example: New delivery received"
                        :disabled="submittingAdjustment"
                        class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-400 dark:focus:ring-gray-800 dark:disabled:bg-gray-900"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3"
                >
                    <BaseButton
                        variant="secondary"
                        :disabled="submittingAdjustment"
                        class="w-full sm:w-auto"
                        @click="closeAdjustmentModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        :loading="submittingAdjustment"
                        :disabled="
                            submittingAdjustment ||
                            !adjustment.quantity ||
                            Number(adjustment.quantity) <= 0 ||
                            !adjustment.reason.trim()
                        "
                        class="w-full sm:w-auto"
                        @click="submitAdjustment"
                    >
                        Save adjustment
                    </BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Stock history modal -->
        <BaseModal
            :show="showHistoryModal"
            title="Stock history"
            size="xl"
            @close="closeHistoryModal"
        >
            <div v-if="selectedProduct">
                <!-- Product summary -->
                <div
                    class="mb-5 flex flex-col gap-2 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p
                            class="truncate font-semibold text-gray-900 dark:text-white"
                        >
                            {{ selectedProduct.name }}
                        </p>

                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{
                                selectedProduct.sku ||
                                "No SKU"
                            }}
                        </p>
                    </div>

                    <div
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Current stock:
                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{
                                stockQuantity(
                                    selectedProduct,
                                )
                            }}
                        </span>
                    </div>
                </div>

                <!-- History error -->
                <div
                    v-if="historyError"
                    class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ historyError }}
                </div>

                <!-- History loading -->
                <div
                    v-if="historyLoading"
                    class="py-10 text-center"
                >
                    <div
                        class="mx-auto mb-3 h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900 dark:border-gray-700 dark:border-t-white"
                    ></div>

                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Loading history...
                    </p>
                </div>

                <!-- History table -->
                <div
                    v-else
                    class="overflow-x-auto"
                >
                    <table
                        class="w-full min-w-[700px] text-left text-sm"
                    >
                        <thead
                            class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400"
                        >
                            <tr>
                                <th class="pb-3">
                                    Date
                                </th>

                                <th class="pb-3">
                                    Type
                                </th>

                                <th class="pb-3">
                                    Change
                                </th>

                                <th class="pb-3">
                                    Stock
                                </th>

                                <th class="pb-3">
                                    Reason
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-800"
                        >
                            <tr
                                v-for="record in history"
                                :key="record.id"
                            >
                                <td
                                    class="py-3 text-gray-600 dark:text-gray-300"
                                >
                                    {{
                                        formatDate(
                                            record.created_at,
                                        )
                                    }}
                                </td>

                                <td class="py-3">
                                    <span
                                        class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        {{
                                            movementLabel(
                                                record.type,
                                            )
                                        }}
                                    </span>
                                </td>

                                <td
                                    class="py-3 font-semibold"
                                    :class="
                                        Number(
                                            record.quantity_change,
                                        ) >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-600 dark:text-red-400'
                                    "
                                >
                                    {{ signedChange(record) }}
                                </td>

                                <td
                                    class="py-3 text-gray-600 dark:text-gray-300"
                                >
                                    {{ record.quantity_before }}
                                    →
                                    {{ record.quantity_after }}
                                </td>

                                <td
                                    class="max-w-xs py-3 text-gray-600 dark:text-gray-300"
                                >
                                    {{ record.reason || "—" }}
                                </td>
                            </tr>

                            <tr v-if="!history.length">
                                <td
                                    colspan="5"
                                    class="py-10 text-center text-gray-500 dark:text-gray-400"
                                >
                                    No stock history recorded
                                    for this product.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- History pagination -->
                <div
                    v-if="historyLastPage > 1"
                    class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end"
                >
                    <div
                        class="flex items-center justify-between gap-2 sm:justify-end"
                    >
                        <BaseButton
                            variant="secondary"
                            :disabled="
                                historyCurrentPage === 1 ||
                                historyLoading
                            "
                            @click="
                                loadHistory(
                                    historyCurrentPage - 1,
                                )
                            "
                        >
                            Previous
                        </BaseButton>

                        <span
                            class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ historyCurrentPage }}
                            /
                            {{ historyLastPage }}
                        </span>

                        <BaseButton
                            variant="secondary"
                            :disabled="
                                historyCurrentPage ===
                                    historyLastPage ||
                                historyLoading
                            "
                            @click="
                                loadHistory(
                                    historyCurrentPage + 1,
                                )
                            "
                        >
                            Next
                        </BaseButton>
                    </div>
                </div>
            </div>
        </BaseModal>
    </div>
</template>
