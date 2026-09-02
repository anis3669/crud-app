<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useInvoiceStore } from "../../stores/invoice";
import { useToastStore } from "../../stores/toast";

const router = useRouter();
const invoiceStore = useInvoiceStore();
const toastStore = useToastStore();

const searchInput = ref("");

const showDeleteModal = ref(false);
const invoiceToDelete = ref(null);
const deleting = ref(false);

const invoices = computed(() => {
    return Array.isArray(invoiceStore.invoices) ? invoiceStore.invoices : [];
});

const loading = computed(() => invoiceStore.loading);
const error = computed(() => invoiceStore.error);

const currentPage = computed(() => invoiceStore.currentPage);
const lastPage = computed(() => invoiceStore.lastPage);
const total = computed(() => invoiceStore.total);

const hasPreviousPage = computed(() => {
    return currentPage.value > 1;
});

const hasNextPage = computed(() => {
    return currentPage.value < lastPage.value;
});

async function loadInvoices(page = 1) {
    try {
        await invoiceStore.fetchInvoices(
            page,
            invoiceStore.search,
            invoiceStore.status,
        );
    } catch (error) {
        console.error("Failed to load invoices:", error);
    }
}

async function performSearch() {
    await invoiceStore.fetchInvoices(
        1,
        searchInput.value.trim(),
        invoiceStore.status,
    );
}

async function clearSearch() {
    searchInput.value = "";

    await invoiceStore.fetchInvoices(1, "", invoiceStore.status);
}

async function changeStatus(event) {
    await invoiceStore.fetchInvoices(
        1,
        searchInput.value.trim(),
        event.target.value,
    );
}

async function previousPage() {
    if (hasPreviousPage.value) {
        await loadInvoices(currentPage.value - 1);
    }
}

async function nextPage() {
    if (hasNextPage.value) {
        await loadInvoices(currentPage.value + 1);
    }
}

async function goToPage(page) {
    if (page >= 1 && page <= lastPage.value) {
        await loadInvoices(page);
    }
}

function viewInvoice(invoice) {
    router.push({
        name: "invoices.show",
        params: {
            invoice: invoice.id,
        },
    });
}

function openDeleteModal(invoice) {
    invoiceToDelete.value = invoice;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
    invoiceToDelete.value = null;
}

async function confirmDelete() {
    if (!invoiceToDelete.value || deleting.value) {
        return;
    }

    deleting.value = true;

    try {
        const invoiceNumber = invoiceToDelete.value.invoice_number;

        await invoiceStore.deleteInvoice(invoiceToDelete.value.id);

        showDeleteModal.value = false;
        invoiceToDelete.value = null;

        toastStore.success(
            `Invoice ${invoiceNumber} was deleted successfully.`,
        );

        await loadInvoices(currentPage.value);
    } catch (error) {
        console.error("Failed to delete invoice:", error);

        toastStore.error(invoiceStore.error || "Failed to delete invoice.");
    } finally {
        deleting.value = false;
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat("en-NP", {
        style: "currency",
        currency: "NPR",
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
}

function formatDate(date) {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleDateString("en-NP", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function statusClass(status) {
    switch (status) {
        case "completed":
            return "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400";

        case "cancelled":
            return "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400";

        default:
            return "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400";
    }
}

onMounted(async () => {
    searchInput.value = invoiceStore.search;

    await loadInvoices(1);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Invoices
                </h1>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage your invoices and sales records.
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div
            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="flex flex-col gap-3 md:flex-row">
                <div class="flex-1">
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Search invoice number or customer..."
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        @keyup.enter="performSearch"
                    />
                </div>

                <select
                    :value="invoiceStore.status"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    @change="changeStatus"
                >
                    <option value="">All status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>

                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700"
                    @click="performSearch"
                >
                    Search
                </button>

                <button
                    v-if="searchInput"
                    type="button"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    @click="clearSearch"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Error -->
        <div
            v-if="error"
            class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400"
        >
            {{ error }}
        </div>

        <!-- Table -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Invoice
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Customer
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Date
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Total
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Status
                            </th>

                            <th
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <!-- Loading -->
                        <tr v-if="loading">
                            <td
                                colspan="6"
                                class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                Loading invoices...
                            </td>
                        </tr>

                        <!-- Empty -->
                        <tr v-else-if="invoices.length === 0">
                            <td
                                colspan="6"
                                class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                No invoices found.
                            </td>
                        </tr>

                        <!-- Data -->
                        <tr
                            v-for="invoice in invoices"
                            :key="invoice.id"
                            class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ invoice.invoice_number }}
                                </div>

                                <div
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    #{{ invoice.id }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div
                                    class="font-medium text-gray-900 dark:text-white"
                                >
                                    {{ invoice.customer_name }}
                                </div>

                                <div
                                    v-if="invoice.customer_email"
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ invoice.customer_email }}
                                </div>
                            </td>

                            <td
                                class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300"
                            >
                                {{ formatDate(invoice.created_at) }}
                            </td>

                            <td
                                class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white"
                            >
                                {{ formatCurrency(invoice.total) }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                    :class="statusClass(invoice.status)"
                                >
                                    {{ invoice.status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                        @click="viewInvoice(invoice)"
                                    >
                                        View
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-900/20"
                                        :disabled="deleting"
                                        @click="openDeleteModal(invoice)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="total > 0"
                class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing page
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ currentPage }}
                    </span>
                    of
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ lastPage }}
                    </span>
                    · {{ total }} invoice{{ total === 1 ? "" : "s" }}
                </p>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        :disabled="!hasPreviousPage || loading"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        @click="previousPage"
                    >
                        Previous
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white"
                    >
                        {{ currentPage }}
                    </button>

                    <button
                        type="button"
                        :disabled="!hasNextPage || loading"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        @click="nextPage"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 backdrop-blur-sm"
            @click.self="closeDeleteModal"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30"
                >
                    <svg
                        class="h-6 w-6 text-red-600 dark:text-red-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                    </svg>
                </div>

                <div class="mt-4 text-center">
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        Delete Invoice?
                    </h2>

                    <p
                        class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"
                    >
                        Are you sure you want to delete
                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{ invoiceToDelete?.invoice_number }}
                        </span>
                        ?
                    </p>

                    <p
                        class="mt-2 text-sm font-medium text-red-600 dark:text-red-400"
                    >
                        This will permanently delete the invoice and restore its
                        sold stock.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        :disabled="deleting"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="deleting"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="confirmDelete"
                    >
                        <svg
                            v-if="deleting"
                            class="h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>

                        {{ deleting ? "Deleting..." : "Delete Invoice" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
