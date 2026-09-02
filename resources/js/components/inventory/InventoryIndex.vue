<script setup>
import { computed, onMounted, ref, watch } from "vue";

import BaseButton from "../common/BaseButton.vue";
import BaseModal from "../common/BaseModal.vue";
import BaseTable from "../common/BaseTable.vue";
import { useInventoryStore } from "../../stores/inventory";
import { useToastStore } from "../../stores/toast";

const inventoryStore = useInventoryStore();
const toastStore = useToastStore();

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

let searchTimer;

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
const historyCurrentPage = computed(() => inventoryStore.historyCurrentPage);
const historyLastPage = computed(() => inventoryStore.historyLastPage);

const hasPreviousPage = computed(() => currentPage.value > 1);
const hasNextPage = computed(() => currentPage.value < lastPage.value);

const firstProductNumber = computed(() => {
    if (total.value === 0) return 0;

    return (currentPage.value - 1) * perPage.value + 1;
});

const lastProductNumber = computed(() => {
    return Math.min(currentPage.value * perPage.value, total.value);
});

const visibleUnits = computed(() => {
    return products.value.reduce(
        (sum, product) => sum + stockQuantity(product),
        0,
    );
});

const visibleLowStock = computed(() => {
    return products.value.filter((product) => {
        const quantity = stockQuantity(product);

        return quantity > 0 && quantity <= 10;
    }).length;
});

const visibleOutOfStock = computed(() => {
    return products.value.filter(
        (product) => stockQuantity(product) === 0,
    ).length;
});

function stockQuantity(product) {
    return Math.max(0, Number(product.quantity) || 0);
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
    return product.category?.name || product.category || "Uncategorized";
}

function supplierName(product) {
    return product.supplier?.name || product.supplier || "—";
}

function formatDate(value) {
    if (!value) return "—";

    return new Intl.DateTimeFormat("en", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
    }).format(new Date(value));
}

function signedChange(record) {
    const amount = Number(record.quantity_change) || 0;

    return amount > 0 ? `+${amount}` : amount;
}

async function loadInventory(page = inventoryStore.currentPage) {
    try {
        await inventoryStore.fetchInventory(
            page,
            searchInput.value.trim(),
        );
    } catch (requestError) {
        console.error("Failed to load inventory:", requestError);
    }
}

async function goToPage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return;
    }

    await loadInventory(page);
}

async function refreshInventory() {
    await loadInventory(currentPage.value);
}

function clearSearch() {
    searchInput.value = "";
}

function openAdjustmentModal(product) {
    selectedProduct.value = product;

    adjustment.value = {
        type: "stock_in",
        quantity: null,
        reason: "",
    };

    showAdjustmentModal.value = true;
}

function closeAdjustmentModal() {
    if (submittingAdjustment.value) return;

    showAdjustmentModal.value = false;
    selectedProduct.value = null;
}

async function submitAdjustment() {
    if (
        !selectedProduct.value ||
        !adjustment.value.quantity ||
        !adjustment.value.reason.trim()
    ) {
        return;
    }

    submittingAdjustment.value = true;

    try {
        const response = await inventoryStore.adjustStock(
            selectedProduct.value.id,
            {
                type: adjustment.value.type,
                quantity: Number(adjustment.value.quantity),
                reason: adjustment.value.reason.trim(),
            },
        );

        toastStore.success(
            response.message || "Inventory updated successfully.",
        );

        showAdjustmentModal.value = false;
        selectedProduct.value = null;

        await refreshInventory();
    } catch (requestError) {
        console.error("Failed to adjust inventory:", requestError);
    } finally {
        submittingAdjustment.value = false;
    }
}

async function openHistoryModal(product) {
    selectedProduct.value = product;
    showHistoryModal.value = true;

    await loadHistory(1);
}

function closeHistoryModal() {
    showHistoryModal.value = false;
    selectedProduct.value = null;
}

async function loadHistory(page) {
    if (!selectedProduct.value) return;

    try {
        await inventoryStore.fetchHistory(
            selectedProduct.value.id,
            page,
        );
    } catch (requestError) {
        console.error("Failed to load inventory history:", requestError);
    }
}

watch(searchInput, () => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => loadInventory(1), 350);
});

onMounted(() => {
    searchInput.value = inventoryStore.search;

    loadInventory(1);
});
</script>

<template>
    <div
        class="min-h-[calc(100vh-4rem)] bg-gray-50 transition-colors duration-300 dark:bg-gray-950"
    >
        <div
            class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-4 sm:py-6 lg:px-6"
        >
            <section
                class="mb-6 flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900 sm:px-6 sm:py-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex min-w-0 items-center gap-4">
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

                    <div>
                        <h1
                            class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-2xl"
                        >
                            Inventory
                        </h1>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Monitor stock levels and record every movement.
                        </p>
                    </div>
                </div>

                <BaseButton
                    variant="secondary"
                    :disabled="loading"
                    @click="refreshInventory"
                >
                    <svg
                        class="h-4 w-4"
                        :class="{ 'animate-spin': loading }"
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
            </section>

            <section class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Products
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total_products || total }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Units shown
                    </p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats.total_quantity || visibleUnits }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Low stock
                    </p>
                    <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                        {{ stats.low_stock || visibleLowStock }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Out of stock
                    </p>
                    <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ stats.out_of_stock || visibleOutOfStock }}
                    </p>
                </div>
            </section>

            <section
                class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
            >
                <label for="inventory-search" class="sr-only">
                    Search inventory
                </label>

                <div class="relative max-w-xl">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <circle cx="11" cy="11" r="7" stroke-width="2" />
                        <path
                            stroke-linecap="round"
                            stroke-width="2"
                            d="m20 20-3.5-3.5"
                        />
                    </svg>

                    <input
                        id="inventory-search"
                        v-model="searchInput"
                        type="search"
                        placeholder="Search by product, SKU, category, or supplier..."
                        class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-400 dark:focus:ring-gray-800"
                    />

                    <button
                        v-if="searchInput"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        ×
                    </button>
                </div>
            </section>

            <div
                v-if="error"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-400"
            >
                {{ error }}
            </div>

            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    v-if="loading && !products.length"
                    class="px-6 py-20 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                    Loading inventory...
                </div>

                <BaseTable v-else min-width="960px">
                    <template #header>
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Product
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                SKU
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Category
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Supplier
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Stock
                            </th>
                            <th class="px-5 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
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
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ product.name }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    #{{ product.id }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ product.sku || "—" }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ categoryName(product) }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ supplierName(product) }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ stockQuantity(product) }}
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="stockStatus(product).classes"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="stockStatus(product).dot"
                                        ></span>
                                        {{ stockStatus(product).label }}
                                    </span>
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                                        @click="openHistoryModal(product)"
                                    >
                                        History
                                    </button>

                                    <BaseButton
                                        class="px-3 py-2"
                                        @click="openAdjustmentModal(product)"
                                    >
                                        Adjust
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!products.length">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    No inventory items found
                                </p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Try a different search term.
                                </p>
                            </td>
                        </tr>
                    </template>
                </BaseTable>

                <div
                    v-if="total > 0"
                    class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ firstProductNumber }}–{{ lastProductNumber }} of {{ total }} products
                    </p>

                    <div class="flex items-center gap-2">
                        <BaseButton
                            variant="secondary"
                            :disabled="!hasPreviousPage || loading"
                            @click="goToPage(currentPage - 1)"
                        >
                            Previous
                        </BaseButton>

                        <div
                            class="flex h-9 items-center rounded-lg bg-gray-900 px-3 text-sm font-semibold text-white dark:bg-white dark:text-gray-900"
                        >
                            {{ currentPage }}
                            <span class="mx-1 text-gray-400">/</span>
                            {{ lastPage }}
                        </div>

                        <BaseButton
                            variant="secondary"
                            :disabled="!hasNextPage || loading"
                            @click="goToPage(currentPage + 1)"
                        >
                            Next
                        </BaseButton>
                    </div>
                </div>
            </section>
        </div>

        <BaseModal
            :show="showAdjustmentModal"
            title="Adjust stock"
            @close="closeAdjustmentModal"
        >
            <div v-if="selectedProduct" class="space-y-5">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="font-semibold text-gray-900 dark:text-white">
                        {{ selectedProduct.name }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Current stock: {{ stockQuantity(selectedProduct) }}
                    </p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Movement type
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="cursor-pointer rounded-xl border p-3 text-sm font-medium"
                            :class="adjustment.type === 'stock_in'
                                ? 'border-gray-900 bg-gray-50 text-gray-900 dark:border-white dark:bg-gray-800 dark:text-white'
                                : 'border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-300'"
                        >
                            <input
                                v-model="adjustment.type"
                                type="radio"
                                value="stock_in"
                                class="sr-only"
                            />
                            Stock in
                        </label>

                        <label
                            class="cursor-pointer rounded-xl border p-3 text-sm font-medium"
                            :class="adjustment.type === 'stock_out'
                                ? 'border-gray-900 bg-gray-50 text-gray-900 dark:border-white dark:bg-gray-800 dark:text-white'
                                : 'border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-300'"
                        >
                            <input
                                v-model="adjustment.type"
                                type="radio"
                                value="stock_out"
                                class="sr-only"
                            />
                            Stock out
                        </label>
                    </div>
                </div>

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
                        type="number"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-800"
                    />
                </div>

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
                        class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-800"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <BaseButton
                        variant="secondary"
                        :disabled="submittingAdjustment"
                        @click="closeAdjustmentModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        :loading="submittingAdjustment"
                        :disabled="!adjustment.quantity || !adjustment.reason.trim()"
                        @click="submitAdjustment"
                    >
                        Save adjustment
                    </BaseButton>
                </div>
            </template>
        </BaseModal>

        <BaseModal
            :show="showHistoryModal"
            title="Inventory history"
            size="xl"
            @close="closeHistoryModal"
        >
            <div v-if="selectedProduct">
                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">
                    {{ selectedProduct.name }} · {{ selectedProduct.sku || "No SKU" }}
                </p>

                <div
                    v-if="historyError"
                    class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-400"
                >
                    {{ historyError }}
                </div>

                <div
                    v-if="historyLoading"
                    class="py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                    Loading history...
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-sm">
                        <thead
                            class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400"
                        >
                            <tr>
                                <th class="pb-3">Date</th>
                                <th class="pb-3">Type</th>
                                <th class="pb-3">Change</th>
                                <th class="pb-3">Before → After</th>
                                <th class="pb-3">Reason</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="record in history" :key="record.id">
                                <td class="py-3 text-gray-600 dark:text-gray-300">
                                    {{ formatDate(record.created_at) }}
                                </td>
                                <td class="py-3 capitalize text-gray-700 dark:text-gray-200">
                                    {{ String(record.type || "adjustment").replace("_", " ") }}
                                </td>
                                <td
                                    class="py-3 font-semibold"
                                    :class="Number(record.quantity_change) >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ signedChange(record) }}
                                </td>
                                <td class="py-3 text-gray-600 dark:text-gray-300">
                                    {{ record.quantity_before }} → {{ record.quantity_after }}
                                </td>
                                <td class="py-3 text-gray-600 dark:text-gray-300">
                                    {{ record.reason || "—" }}
                                </td>
                            </tr>

                            <tr v-if="!history.length">
                                <td
                                    colspan="5"
                                    class="py-10 text-center text-gray-500 dark:text-gray-400"
                                >
                                    No history recorded for this product.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="historyLastPage > 1"
                    class="mt-5 flex items-center justify-end gap-2"
                >
                    <BaseButton
                        variant="secondary"
                        :disabled="historyCurrentPage === 1"
                        @click="loadHistory(historyCurrentPage - 1)"
                    >
                        Previous
                    </BaseButton>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ historyCurrentPage }} / {{ historyLastPage }}
                    </span>

                    <BaseButton
                        variant="secondary"
                        :disabled="historyCurrentPage === historyLastPage"
                        @click="loadHistory(historyCurrentPage + 1)"
                    >
                        Next
                    </BaseButton>
                </div>
            </div>
        </BaseModal>
    </div>
</template>
