<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { useRouter } from "vue-router";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import BaseModal from "../common/BaseModal.vue";
import BaseTable from "../common/BaseTable.vue";

import { useInvoiceStore } from "../../stores/invoice";
import { useAuthStore } from "../../stores/auth";

const router = useRouter();
const invoiceStore = useInvoiceStore();
const authStore = useAuthStore();

// State
const searchInput = ref("");
const statusFilter = ref("");

const showDeleteModal = ref(false);
const invoiceToDelete = ref(null);
const deleting = ref(false);

const searchTimer = ref(null);

// Computed
const invoices = computed(() => invoiceStore.invoices);
const loading = computed(() => invoiceStore.loading);
const error = computed(() => invoiceStore.error);

const currentPage = computed(() => invoiceStore.currentPage);
const lastPage = computed(() => invoiceStore.lastPage);
const total = computed(() => invoiceStore.total);
const perPage = computed(() => invoiceStore.perPage);

const canCreate = computed(() => authStore.can("invoices.create"));

const canDelete = computed(() => authStore.can("invoices.delete"));

const hasPreviousPage = computed(() => currentPage.value > 1);

const hasNextPage = computed(() => currentPage.value < lastPage.value);

const firstInvoiceNumber = computed(() => {
    if (!total.value) return 0;

    return (currentPage.value - 1) * perPage.value + 1;
});

const lastInvoiceNumber = computed(() => {
    return Math.min(currentPage.value * perPage.value, total.value);
});

// Helpers
function getErrorMessage(error, fallback) {
    return error?.response?.data?.message || error?.message || fallback;
}

function formatCurrency(value) {
    const amount = Number(value) || 0;

    return new Intl.NumberFormat("en-NP", {
        style: "currency",
        currency: "NPR",
        maximumFractionDigits: 2,
    }).format(amount);
}

function formatDate(value) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "—";
    }

    return new Intl.DateTimeFormat("en", {
        day: "numeric",
        month: "short",
        year: "numeric",
    }).format(date);
}

function invoiceNumber(invoice) {
    return invoice.invoice_number || invoice.number || `#${invoice.id}`;
}

function customerName(invoice) {
    return (
        invoice.customer_name ||
        invoice.customer?.name ||
        invoice.customer ||
        "Walk-in Customer"
    );
}

function invoiceTotal(invoice) {
    return Number(invoice.total ?? invoice.grand_total ?? invoice.amount ?? 0);
}

function statusLabel(status) {
    if (!status) return "Unknown";

    return String(status)
        .replaceAll("_", " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function statusClass(status) {
    switch (String(status).toLowerCase()) {
        case "paid":
            return "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400";

        case "pending":
            return "bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400";

        case "cancelled":
        case "canceled":
            return "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400";

        case "draft":
            return "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300";

        default:
            return "bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400";
    }
}

// Data loading
async function loadInvoices(page = 1) {
    try {
        await invoiceStore.fetchInvoices(
            page,
            searchInput.value,
            statusFilter.value,
        );
    } catch (requestError) {
        console.error("Failed to load invoices:", requestError);
    }
}

function performSearch() {
    clearSearchTimer();

    loadInvoices(1);
}

function handleSearchInput() {
    clearSearchTimer();

    searchTimer.value = setTimeout(() => {
        loadInvoices(1);
    }, 350);
}

function clearSearch() {
    clearSearchTimer();

    searchInput.value = "";
    loadInvoices(1);
}

function changeStatus() {
    loadInvoices(1);
}

function clearFilters() {
    clearSearchTimer();

    searchInput.value = "";
    statusFilter.value = "";

    loadInvoices(1);
}

function clearSearchTimer() {
    if (searchTimer.value) {
        clearTimeout(searchTimer.value);
        searchTimer.value = null;
    }
}

// Pagination
async function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) {
        return;
    }

    await loadInvoices(page);
}

function previousPage() {
    if (hasPreviousPage.value) {
        goToPage(currentPage.value - 1);
    }
}

function nextPage() {
    if (hasNextPage.value) {
        goToPage(currentPage.value + 1);
    }
}

// Navigation
function createInvoice() {
    router.push({
        name: "invoices.create",
    });
}

function viewInvoice(invoice) {
    router.push({
        name: "invoices.show",
        params: {
            invoice: invoice.id,
        },
    });
}

// Delete
function openDeleteModal(invoice) {
    if (!canDelete.value) return;

    invoiceToDelete.value = invoice;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleting.value) return;

    showDeleteModal.value = false;
    invoiceToDelete.value = null;
}

async function confirmDelete() {
    if (deleting.value || !invoiceToDelete.value) {
        return;
    }

    deleting.value = true;

    try {
        await invoiceStore.deleteInvoice(invoiceToDelete.value.id);

        showDeleteModal.value = false;
        invoiceToDelete.value = null;

        // Reload current page if it became empty
        if (!invoices.value.length && currentPage.value > 1) {
            await loadInvoices(currentPage.value - 1);
        }
    } catch (deleteError) {
        console.error("Failed to delete invoice:", deleteError);

        alert(getErrorMessage(deleteError, "Failed to delete invoice."));
    } finally {
        deleting.value = false;
    }
}

onMounted(() => {
    searchInput.value = invoiceStore.search || "";
    statusFilter.value = invoiceStore.status || "";

    loadInvoices(1);
});

onUnmounted(() => {
    clearSearchTimer();
});
</script>

<template>
    <div
        class="min-h-[calc(100vh-4rem)] bg-gray-50 px-4 py-6 dark:bg-gray-950 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <section
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-2xl"
                    >
                        Invoices
                    </h1>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage sales invoices and customer transactions.
                    </p>
                </div>

                <BaseButton
                    v-if="canCreate"
                    variant="primary"
                    @click="createInvoice"
                >
                    <span class="mr-2 text-lg leading-none">+</span>
                    Create Invoice
                </BaseButton>
            </section>

            <!-- Error -->
            <div
                v-if="error"
                class="mb-6 flex items-start justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/70 dark:bg-red-950/30 dark:text-red-400"
            >
                <span>{{ error }}</span>

                <button
                    type="button"
                    class="shrink-0 font-medium underline"
                    @click="invoiceStore.clearError"
                >
                    Dismiss
                </button>
            </div>

            <!-- Stats -->
            <section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <BaseCard>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Total invoices
                    </div>

                    <div
                        class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ total }}
                    </div>
                </BaseCard>

                <BaseCard>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Current page
                    </div>

                    <div
                        class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ currentPage }}
                        <span class="text-base font-normal text-gray-400">
                            / {{ lastPage }}
                        </span>
                    </div>
                </BaseCard>

                <BaseCard>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Showing
                    </div>

                    <div
                        class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white"
                    >
                        {{ firstInvoiceNumber }}–{{ lastInvoiceNumber }}
                    </div>
                </BaseCard>
            </section>

            <!-- Filters -->
            <section
                class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <!-- Search -->
                    <div class="relative flex-1">
                        <svg
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                            />
                        </svg>

                        <input
                            v-model="searchInput"
                            type="search"
                            placeholder="Search invoices..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-10 text-sm text-gray-900 outline-none transition focus:border-gray-400 focus:bg-white focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-gray-600 dark:focus:bg-gray-800 dark:focus:ring-gray-700"
                            @input="handleSearchInput"
                            @keyup.enter="performSearch"
                        />

                        <button
                            v-if="searchInput"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                            @click="clearSearch"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Status -->
                    <select
                        v-model="statusFilter"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-gray-400 focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:focus:border-gray-600 dark:focus:ring-gray-700"
                        @change="changeStatus"
                    >
                        <option value="">All statuses</option>

                        <option value="paid">Paid</option>

                        <option value="pending">Pending</option>

                        <option value="draft">Draft</option>

                        <option value="cancelled">Cancelled</option>
                    </select>

                    <BaseButton
                        variant="secondary"
                        :disabled="loading"
                        @click="loadInvoices(currentPage)"
                    >
                        Refresh
                    </BaseButton>

                    <button
                        v-if="searchInput || statusFilter"
                        type="button"
                        class="text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                </div>
            </section>

            <!-- Content -->
            <section
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <!-- Loading -->
                <div
                    v-if="loading && !invoices.length"
                    class="px-6 py-20 text-center"
                >
                    <div
                        class="mx-auto mb-3 h-7 w-7 animate-spin rounded-full border-2 border-gray-300 border-t-gray-800 dark:border-gray-700 dark:border-t-white"
                    ></div>

                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Loading invoices...
                    </p>
                </div>

                <!-- Desktop -->
                <div v-else class="hidden md:block">
                    <BaseTable min-width="900px">
                        <template #header>
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Invoice
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Customer
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Date
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Total
                                </th>

                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                                >
                                    Actions
                                </th>
                            </tr>
                        </template>

                        <template #body>
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="border-t border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800/50"
                            >
                                <td class="px-6 py-4">
                                    <button
                                        type="button"
                                        class="font-medium text-gray-900 hover:underline dark:text-white"
                                        @click="viewInvoice(invoice)"
                                    >
                                        {{ invoiceNumber(invoice) }}
                                    </button>
                                </td>

                                <td
                                    class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300"
                                >
                                    {{ customerName(invoice) }}
                                </td>

                                <td
                                    class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        formatDate(
                                            invoice.created_at ||
                                                invoice.invoice_date ||
                                                invoice.date,
                                        )
                                    }}
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass(invoice.status)"
                                    >
                                        {{ statusLabel(invoice.status) }}
                                    </span>
                                </td>

                                <td
                                    class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ formatCurrency(invoiceTotal(invoice)) }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <BaseButton
                                            variant="secondary"
                                            size="sm"
                                            @click="viewInvoice(invoice)"
                                        >
                                            View
                                        </BaseButton>

                                        <BaseButton
                                            v-if="canDelete"
                                            variant="danger"
                                            size="sm"
                                            @click="openDeleteModal(invoice)"
                                        >
                                            Delete
                                        </BaseButton>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty -->
                            <tr v-if="!invoices.length">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        No invoices found
                                    </div>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Try changing your search or filters.
                                    </p>
                                </td>
                            </tr>
                        </template>
                    </BaseTable>
                </div>

                <!-- Mobile -->
                <div
                    v-if="!loading"
                    class="divide-y divide-gray-100 dark:divide-gray-800 md:hidden"
                >
                    <div
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="p-4"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <button
                                    type="button"
                                    class="truncate text-sm font-semibold text-gray-900 hover:underline dark:text-white"
                                    @click="viewInvoice(invoice)"
                                >
                                    {{ invoiceNumber(invoice) }}
                                </button>

                                <p
                                    class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ customerName(invoice) }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusClass(invoice.status)"
                            >
                                {{ statusLabel(invoice.status) }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-gray-400">Date</div>

                                <div
                                    class="mt-1 text-gray-700 dark:text-gray-300"
                                >
                                    {{
                                        formatDate(
                                            invoice.created_at ||
                                                invoice.invoice_date ||
                                                invoice.date,
                                        )
                                    }}
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="text-xs text-gray-400">Total</div>

                                <div
                                    class="mt-1 font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ formatCurrency(invoiceTotal(invoice)) }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <BaseButton
                                variant="secondary"
                                size="sm"
                                @click="viewInvoice(invoice)"
                            >
                                View
                            </BaseButton>

                            <BaseButton
                                v-if="canDelete"
                                variant="danger"
                                size="sm"
                                @click="openDeleteModal(invoice)"
                            >
                                Delete
                            </BaseButton>
                        </div>
                    </div>

                    <!-- Mobile Empty -->
                    <div v-if="!invoices.length" class="px-6 py-16 text-center">
                        <div
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            No invoices found
                        </div>

                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Try changing your search or filters.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="lastPage > 1"
                    class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ firstInvoiceNumber }}–{{ lastInvoiceNumber }}
                        </span>
                        of
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ total }}
                        </span>
                        invoices
                    </p>

                    <div class="flex items-center gap-2">
                        <BaseButton
                            variant="secondary"
                            size="sm"
                            :disabled="!hasPreviousPage || loading"
                            @click="previousPage"
                        >
                            Previous
                        </BaseButton>

                        <span
                            class="min-w-20 text-center text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{ currentPage }}
                            /
                            {{ lastPage }}
                        </span>

                        <BaseButton
                            variant="secondary"
                            size="sm"
                            :disabled="!hasNextPage || loading"
                            @click="nextPage"
                        >
                            Next
                        </BaseButton>
                    </div>
                </div>
            </section>
        </div>

        <!-- Delete Modal -->
        <BaseModal
            :show="showDeleteModal"
            title="Delete invoice"
            @close="closeDeleteModal"
        >
            <div class="space-y-4">
                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Are you sure you want to delete
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{
                            invoiceToDelete
                                ? invoiceNumber(invoiceToDelete)
                                : "this invoice"
                        }}
                    </span>
                    ?
                </p>

                <p
                    class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-400"
                >
                    This action cannot be undone.
                </p>

                <div class="flex justify-end gap-3 pt-2">
                    <BaseButton
                        variant="secondary"
                        :disabled="deleting"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        variant="danger"
                        :disabled="deleting"
                        @click="confirmDelete"
                    >
                        {{ deleting ? "Deleting..." : "Delete Invoice" }}
                    </BaseButton>
                </div>
            </div>
        </BaseModal>
    </div>
</template>
