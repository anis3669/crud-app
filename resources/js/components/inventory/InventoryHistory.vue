<script setup>
import { computed, onMounted } from "vue";

import BaseButton from "../common/BaseButton.vue";
import BaseTable from "../common/BaseTable.vue";
import BaseCard from "../common/BaseCard.vue";

import { useInventoryStore } from "../../stores/inventory";

const inventoryStore = useInventoryStore();

const history = computed(() => inventoryStore.history);
const loading = computed(() => inventoryStore.historyLoading);
const error = computed(() => inventoryStore.historyError);

const currentPage = computed(() => inventoryStore.historyCurrentPage);

const lastPage = computed(() => inventoryStore.historyLastPage);

const total = computed(() => inventoryStore.historyTotal || 0);

const perPage = computed(() => inventoryStore.historyPerPage || 20);

const hasPreviousPage = computed(() => currentPage.value > 1);

const hasNextPage = computed(() => currentPage.value < lastPage.value);

const firstRecordNumber = computed(() => {
    if (!history.value.length) {
        return 0;
    }

    return (currentPage.value - 1) * perPage.value + 1;
});

const lastRecordNumber = computed(() => {
    if (!history.value.length) {
        return 0;
    }

    return Math.min(currentPage.value * perPage.value, total.value);
});

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
    const amount = Number(record.quantity_change) || 0;

    if (amount > 0) {
        return `+${amount}`;
    }

    return String(amount);
}

function movementType(record) {
    return String(record.type || "adjustment")
        .replaceAll("_", " ")
        .replace(/^\w/, (letter) => letter.toUpperCase());
}

function productName(record) {
    return record.product?.name || "Deleted product";
}

function productSku(record) {
    return record.product?.sku || "No SKU";
}

function userName(record) {
    return record.user?.name || "System";
}

function movementClass(type) {
    switch (type) {
        case "stock_in":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400";

        case "stock_out":
            return "bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400";

        case "adjustment":
            return "bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400";

        default:
            return "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300";
    }
}

function changeClass(record) {
    const amount = Number(record.quantity_change) || 0;

    if (amount > 0) {
        return "text-emerald-600 dark:text-emerald-400";
    }

    if (amount < 0) {
        return "text-red-600 dark:text-red-400";
    }

    return "text-gray-500 dark:text-gray-400";
}

async function loadHistory(page = 1) {
    try {
        await inventoryStore.fetchHistory(null, page);
    } catch (requestError) {
        console.error("Failed to load inventory history:", requestError);
    }
}

async function refreshHistory() {
    await loadHistory(currentPage.value);
}

async function goToPage(page) {
    if (
        loading.value ||
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
    <div
        class="min-h-[calc(100vh-4rem)] bg-gray-50 px-4 py-5 dark:bg-gray-950 sm:px-6 sm:py-6 lg:px-8"
    >
        <div class="mx-auto w-full max-w-7xl space-y-5">
            <!-- Header -->
            <section
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <h1
                        class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        Stock History
                    </h1>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Review every stock movement recorded in your inventory.
                    </p>
                </div>

                <BaseButton
                    variant="secondary"
                    :disabled="loading"
                    @click="refreshHistory"
                >
                    Refresh
                </BaseButton>
            </section>

            <!-- Summary -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <BaseCard padding="p-4 sm:p-5">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Total movements
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ total }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-4 sm:p-5">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Showing
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ firstRecordNumber }}–{{ lastRecordNumber }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-4 sm:p-5" class="col-span-2 sm:col-span-1">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Current page
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ currentPage }}
                        <span class="text-sm font-medium text-gray-400">
                            / {{ lastPage }}
                        </span>
                    </p>
                </BaseCard>
            </div>

            <!-- Error -->
            <BaseCard v-if="error" padding="p-0">
                <div
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <p class="font-medium text-red-700 dark:text-red-400">
                            Unable to load stock history
                        </p>

                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ error }}
                        </p>
                    </div>

                    <BaseButton
                        variant="secondary"
                        :disabled="loading"
                        @click="refreshHistory"
                    >
                        Retry
                    </BaseButton>
                </div>
            </BaseCard>

            <!-- History -->
            <BaseCard padding="p-0">
                <!-- Section header -->
                <div
                    class="flex flex-col gap-1 border-b border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-5"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="font-semibold text-gray-900 dark:text-white"
                            >
                                Movement History
                            </h2>

                            <p
                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                            >
                                Latest stock changes appear first.
                            </p>
                        </div>

                        <span
                            v-if="loading && history.length"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            Updating...
                        </span>
                    </div>
                </div>

                <!-- Loading -->
                <div
                    v-if="loading && !history.length"
                    class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center"
                >
                    <div
                        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-gray-700 dark:border-gray-700 dark:border-t-gray-300"
                    ></div>

                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        Loading stock history...
                    </p>
                </div>

                <!-- Empty -->
                <div
                    v-else-if="!history.length"
                    class="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
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
                                d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        No stock history yet
                    </h3>

                    <p
                        class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400"
                    >
                        Stock movements will appear here after inventory changes
                        are recorded.
                    </p>
                </div>

                <!-- Desktop table -->
                <div v-else class="hidden overflow-x-auto md:block">
                    <BaseTable min-width="1000px">
                        <template #header>
                            <tr>
                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Date
                                </th>

                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Product
                                </th>

                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Movement
                                </th>

                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Change
                                </th>

                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Stock
                                </th>

                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Reason
                                </th>

                                <th
                                    class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Recorded by
                                </th>
                            </tr>
                        </template>

                        <template #body>
                            <tr
                                v-for="record in history"
                                :key="record.id"
                                class="border-t border-gray-100 transition hover:bg-gray-50/70 dark:border-gray-800 dark:hover:bg-gray-800/50"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ formatDate(record.created_at) }}
                                </td>

                                <td class="px-5 py-4">
                                    <p
                                        class="font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ productName(record) }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        {{ productSku(record) }}
                                    </p>
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="movementClass(record.type)"
                                    >
                                        {{ movementType(record) }}
                                    </span>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm font-bold"
                                    :class="changeClass(record)"
                                >
                                    {{ signedChange(record) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    <span class="font-medium">
                                        {{ record.quantity_before }}
                                    </span>

                                    <span class="mx-1.5 text-gray-400">
                                        →
                                    </span>

                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ record.quantity_after }}
                                    </span>
                                </td>

                                <td
                                    class="max-w-xs px-5 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    <p
                                        class="truncate"
                                        :title="record.reason || ''"
                                    >
                                        {{
                                            record.reason ||
                                            "No reason provided"
                                        }}
                                    </p>
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ userName(record) }}
                                </td>
                            </tr>
                        </template>
                    </BaseTable>
                </div>

                <!-- Mobile cards -->
                <div
                    v-if="history.length"
                    class="divide-y divide-gray-100 md:hidden dark:divide-gray-800"
                >
                    <article
                        v-for="record in history"
                        :key="record.id"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="truncate font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ productName(record) }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                                >
                                    {{ productSku(record) }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="movementClass(record.type)"
                            >
                                {{ movementType(record) }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div
                                class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70"
                            >
                                <p
                                    class="text-[11px] font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                                >
                                    Change
                                </p>

                                <p
                                    class="mt-1 text-lg font-bold"
                                    :class="changeClass(record)"
                                >
                                    {{ signedChange(record) }}
                                </p>
                            </div>

                            <div
                                class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800/70"
                            >
                                <p
                                    class="text-[11px] font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500"
                                >
                                    Stock
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ record.quantity_before }}
                                    →
                                    {{ record.quantity_after }}
                                </p>
                            </div>
                        </div>

                        <dl class="mt-4 space-y-2.5 text-sm">
                            <div class="flex gap-3">
                                <dt
                                    class="w-20 shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500"
                                >
                                    Date
                                </dt>

                                <dd class="text-gray-600 dark:text-gray-300">
                                    {{ formatDate(record.created_at) }}
                                </dd>
                            </div>

                            <div class="flex gap-3">
                                <dt
                                    class="w-20 shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500"
                                >
                                    Reason
                                </dt>

                                <dd
                                    class="min-w-0 break-words text-gray-600 dark:text-gray-300"
                                >
                                    {{ record.reason || "No reason provided" }}
                                </dd>
                            </div>

                            <div class="flex gap-3">
                                <dt
                                    class="w-20 shrink-0 text-xs font-medium text-gray-400 dark:text-gray-500"
                                >
                                    By
                                </dt>

                                <dd class="text-gray-600 dark:text-gray-300">
                                    {{ userName(record) }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <!-- Pagination -->
                <div
                    v-if="lastPage > 1"
                    class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-5"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ firstRecordNumber }}
                        </span>
                        to
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ lastRecordNumber }}
                        </span>
                        of
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ total }}
                        </span>
                    </p>

                    <div
                        class="flex items-center justify-between gap-2 sm:justify-end"
                    >
                        <BaseButton
                            variant="secondary"
                            :disabled="!hasPreviousPage || loading"
                            @click="goToPage(currentPage - 1)"
                        >
                            Previous
                        </BaseButton>

                        <div
                            class="flex h-9 min-w-16 items-center justify-center rounded-lg bg-gray-900 px-3 text-sm font-semibold text-white dark:bg-white dark:text-gray-900"
                        >
                            {{ currentPage }}
                            <span class="mx-1 text-gray-400"> / </span>
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
            </BaseCard>
        </div>
    </div>
</template>
