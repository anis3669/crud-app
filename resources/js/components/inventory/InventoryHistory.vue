<script setup>
import { computed, onMounted } from "vue";

import BaseButton from "../common/BaseButton.vue";
import BaseTable from "../common/BaseTable.vue";
import { useInventoryStore } from "../../stores/inventory";

const inventoryStore = useInventoryStore();

const history = computed(() => inventoryStore.history);
const loading = computed(() => inventoryStore.historyLoading);
const error = computed(() => inventoryStore.historyError);
const currentPage = computed(() => inventoryStore.historyCurrentPage);
const lastPage = computed(() => inventoryStore.historyLastPage);

const hasPreviousPage = computed(() => currentPage.value > 1);
const hasNextPage = computed(() => currentPage.value < lastPage.value);

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

function movementType(record) {
    return String(record.type || "adjustment").replace("_", " ");
}

function productName(record) {
    return record.product?.name || "Deleted product";
}

function userName(record) {
    return record.user?.name || "System";
}

async function loadHistory(page = currentPage.value) {
    try {
        await inventoryStore.fetchHistory(null, page);
    } catch (requestError) {
        console.error("Failed to load inventory history:", requestError);
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

    await loadHistory(page);
}

onMounted(() => {
    loadHistory(1);
});
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)] bg-gray-50 px-4 py-6 dark:bg-gray-950 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <section class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-2xl">
                        Inventory history
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Review stock movements across all products.
                    </p>
                </div>

                <BaseButton
                    variant="secondary"
                    :disabled="loading"
                    @click="loadHistory(currentPage)"
                >
                    Refresh
                </BaseButton>
            </section>

            <div
                v-if="error"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-400"
            >
                {{ error }}
            </div>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div
                    v-if="loading && !history.length"
                    class="px-6 py-20 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                    Loading inventory history...
                </div>

                <BaseTable v-else min-width="960px">
                    <template #header>
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Date
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Product
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Type
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Change
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Before → After
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Reason
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Recorded by
                            </th>
                        </tr>
                    </template>

                    <template #body>
                        <tr
                            v-for="record in history"
                            :key="record.id"
                            class="border-t border-gray-100 transition-colors hover:bg-gray-50/70 dark:border-gray-800 dark:hover:bg-gray-800/70"
                        >
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ formatDate(record.created_at) }}
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ productName(record) }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                    {{ record.product?.sku || "No SKU" }}
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 capitalize text-sm text-gray-700 dark:text-gray-200">
                                {{ movementType(record) }}
                            </td>
                            <td
                                class="whitespace-nowrap px-5 py-4 text-sm font-semibold"
                                :class="Number(record.quantity_change) >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-red-600 dark:text-red-400'"
                            >
                                {{ signedChange(record) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ record.quantity_before }} → {{ record.quantity_after }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ record.reason || "—" }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ userName(record) }}
                            </td>
                        </tr>

                        <tr v-if="!history.length">
                            <td colspan="7" class="px-6 py-20 text-center">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    No inventory history found
                                </p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Stock movements will appear here after an adjustment is recorded.
                                </p>
                            </td>
                        </tr>
                    </template>
                </BaseTable>

                <div
                    v-if="lastPage > 1"
                    class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-end"
                >
                    <div class="flex items-center gap-2">
                        <BaseButton
                            variant="secondary"
                            :disabled="!hasPreviousPage || loading"
                            @click="goToPage(currentPage - 1)"
                        >
                            Previous
                        </BaseButton>

                        <div class="flex h-9 items-center rounded-lg bg-gray-900 px-3 text-sm font-semibold text-white dark:bg-white dark:text-gray-900">
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
    </div>
</template>
