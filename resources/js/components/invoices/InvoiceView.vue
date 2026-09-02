<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import html2pdf from "html2pdf.js";
import { useInvoiceStore } from "../../stores/invoice";
import { useToastStore } from "../../stores/toast";

const route = useRoute();
const router = useRouter();
const invoiceStore = useInvoiceStore();
const toastStore = useToastStore();

const invoice = computed(() => invoiceStore.invoice);
const loading = computed(() => invoiceStore.loading);
const error = computed(() => invoiceStore.error);

const downloading = ref(false);

function goBack() {
    router.push({ name: "invoices.index" });
}

function printInvoice() {
    if (!invoice.value) {
        return;
    }

    window.print();
}

async function downloadInvoice() {
    if (!invoice.value || downloading.value) {
        return;
    }

    const element = document.getElementById("invoice-document");

    if (!element) {
        console.error("Invoice document element was not found.");
        toastStore.error("Invoice document could not be found.");
        return;
    }

    downloading.value = true;

    try {
        // Wait for invoice images to finish loading
        const images = element.querySelectorAll("img");

        await Promise.all(
            Array.from(images).map((image) => {
                if (image.complete) {
                    return Promise.resolve();
                }

                return new Promise((resolve) => {
                    image.onload = resolve;
                    image.onerror = resolve;
                });
            }),
        );

        const invoiceNumber = invoice.value.invoice_number || "invoice";

        const options = {
            margin: 0,

            filename: `${invoiceNumber}.pdf`,

            image: {
                type: "jpeg",
                quality: 0.98,
            },

            html2canvas: {
                scale: 1.5,
                useCORS: true,
                allowTaint: false,
                backgroundColor: "#ffffff",
                logging: false,
                scrollX: 0,
                scrollY: 0,

                onclone: (clonedDocument) => {
                    const clonedInvoice =
                        clonedDocument.getElementById("invoice-document");

                    if (clonedInvoice) {
                        clonedInvoice.style.width = "190mm";
                        clonedInvoice.style.maxWidth = "190mm";
                        clonedInvoice.style.minHeight = "0";
                        clonedInvoice.style.height = "auto";
                        clonedInvoice.style.margin = "0 auto";
                        clonedInvoice.style.padding = "0";
                        clonedInvoice.style.background = "#ffffff";
                        clonedInvoice.style.boxShadow = "none";
                    }

                    // Replace unsupported OKLCH colors
                    clonedDocument.querySelectorAll("*").forEach((el) => {
                        const style =
                            clonedDocument.defaultView.getComputedStyle(el);

                        if (style.color && style.color.includes("oklch")) {
                            el.style.color = "#111827";
                        }

                        if (
                            style.backgroundColor &&
                            style.backgroundColor.includes("oklch")
                        ) {
                            el.style.backgroundColor = "#ffffff";
                        }

                        if (
                            style.borderColor &&
                            style.borderColor.includes("oklch")
                        ) {
                            el.style.borderColor = "#e5e7eb";
                        }
                    });
                },
            },

            jsPDF: {
                unit: "mm",
                format: "a4",
                orientation: "portrait",
                compress: true,
            },

            pagebreak: {
                mode: [],
            },
        };

        console.log("Starting invoice PDF generation...");

        await html2pdf().set(options).from(element).save();

        console.log("Invoice PDF downloaded successfully.");

        toastStore.success(
            `Invoice ${invoiceNumber} downloaded successfully.`,
        );
    } catch (error) {
        console.error("Failed to download invoice:", error);

        toastStore.error("Unable to download the invoice PDF.");
    } finally {
        downloading.value = false;
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat("en-NP", {
        style: "currency",
        currency: "NPR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
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

function formatDateTime(date) {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleString("en-NP", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

function statusClass(status) {
    switch (status) {
        case "completed":
            return "text-emerald-700";

        case "cancelled":
            return "text-red-700";

        default:
            return "text-amber-700";
    }
}

async function loadInvoice() {
    try {
        await invoiceStore.fetchInvoice(route.params.invoice);
    } catch (error) {
        console.error("Failed to load invoice:", error);
    }
}

onMounted(async () => {
    await loadInvoice();
});
</script>

<template>
    <div class="invoice-page min-h-screen bg-gray-100 py-6 sm:py-10">
        <!-- Toolbar -->
        <div
            v-if="invoice"
            class="invoice-toolbar mx-auto mb-5 flex w-full max-w-[210mm] items-center justify-between px-4 sm:px-0"
        >
            <button
                type="button"
                class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 transition hover:text-gray-900"
                @click="goBack"
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
                        stroke-width="2"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>

                Back
            </button>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="printInvoice"
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
                            stroke-width="2"
                            d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5h-2M6 14h12v8H6v-8z"
                        />
                    </svg>

                    Print
                </button>

                <button
                    type="button"
                    :disabled="downloading"
                    class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="downloadInvoice"
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
                            stroke-width="2"
                            d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"
                        />
                    </svg>

                    {{ downloading ? "Downloading..." : "Download PDF" }}
                </button>
            </div>
        </div>

        <!-- Error -->
        <div
            v-if="error"
            class="mx-auto w-full max-w-[210mm] px-4 sm:px-0"
        >
            <div
                class="rounded-lg border border-red-200 bg-red-50 p-5 text-red-700"
            >
                <p class="font-semibold">Unable to load invoice</p>

                <p class="mt-1 text-sm">
                    {{ error }}
                </p>

                <button
                    type="button"
                    class="mt-3 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="loadInvoice"
                >
                    Try Again
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div
            v-else-if="loading"
            class="mx-auto w-full max-w-[210mm] px-4 text-center sm:px-0"
        >
            <div
                class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-gray-900"
            ></div>

            <p class="mt-3 text-sm text-gray-500">Loading invoice...</p>
        </div>

        <!-- Invoice -->
        <div
            v-else-if="invoice"
            id="invoice-document"
            class="invoice-paper mx-auto w-full max-w-[210mm] bg-white text-gray-900 shadow-sm"
        >
            <!-- Invoice Content -->
            <div class="px-7 py-8 sm:px-12 sm:py-10">
                <!-- Header -->
                <div
                    class="flex flex-col gap-6 border-b border-gray-200 pb-7 sm:flex-row sm:items-start sm:justify-between"
                >
                    <!-- Company -->
                    <div class="flex items-start gap-4">
                        <img
                            src="http://127.0.0.1:8000/storage/images/volcoussoft-logo.jpeg"
                            alt="Volcous Soft"
                            class="h-20 w-20 object-contain"
                        />

                        <div class="pt-1">
                            <h2
                                class="text-xl font-bold tracking-tight text-gray-900"
                            >
                                Volcous Soft
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Programming the future...
                            </p>

                            <div
                                class="mt-3 text-xs leading-5 text-gray-500"
                            >
                                <p>Kathmandu, Nepal</p>
                                <p>Software & Technology Solutions</p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Information -->
                    <div class="sm:text-right">
                        <p
                            class="text-3xl font-bold uppercase tracking-tight text-gray-900"
                        >
                            Invoice
                        </p>

                        <p class="mt-2 text-sm font-semibold text-gray-700">
                            {{ invoice.invoice_number }}
                        </p>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ formatDate(invoice.created_at) }}
                        </p>

                        <p
                            class="mt-3 text-sm font-semibold capitalize"
                            :class="statusClass(invoice.status)"
                        >
                            {{ invoice.status }}
                        </p>
                    </div>
                </div>

                <!-- Customer Information -->
                <div
                    class="grid gap-7 border-b border-gray-200 py-7 sm:grid-cols-2"
                >
                    <!-- Bill To -->
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                        >
                            Bill To
                        </p>

                        <p
                            class="mt-3 text-base font-semibold text-gray-900"
                        >
                            {{ invoice.customer_name }}
                        </p>

                        <p
                            v-if="invoice.customer_email"
                            class="mt-1 text-sm text-gray-500"
                        >
                            {{ invoice.customer_email }}
                        </p>

                        <p
                            v-if="invoice.customer_phone"
                            class="mt-1 text-sm text-gray-500"
                        >
                            {{ invoice.customer_phone }}
                        </p>
                    </div>

                    <!-- Invoice Details -->
                    <div class="sm:text-right">
                        <p
                            class="text-xs font-semibold uppercase tracking-wider text-gray-400"
                        >
                            Invoice Details
                        </p>

                        <div class="mt-3 space-y-1 text-sm text-gray-600">
                            <p>
                                <span class="font-medium text-gray-900">
                                    Invoice Date:
                                </span>

                                {{ formatDate(invoice.created_at) }}
                            </p>

                            <p>
                                <span class="font-medium text-gray-900">
                                    Prepared By:
                                </span>

                                {{ invoice.user?.name || "-" }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="py-7">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr
                                class="border-b-2 border-gray-900 text-left"
                            >
                                <th
                                    class="pb-3 pr-3 text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    #
                                </th>

                                <th
                                    class="pb-3 text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Description
                                </th>

                                <th
                                    class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Qty
                                </th>

                                <th
                                    class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Unit Price
                                </th>

                                <th
                                    class="pb-3 pl-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                                >
                                    Amount
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(item, index) in invoice.items || []"
                                :key="item.id"
                                class="border-b border-gray-100"
                            >
                                <td
                                    class="py-4 pr-3 text-sm text-gray-400"
                                >
                                    {{ index + 1 }}
                                </td>

                                <td class="py-4">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ item.product_name }}
                                    </p>

                                    <p
                                        v-if="item.product"
                                        class="mt-1 text-xs text-gray-400"
                                    >
                                        Product #{{ item.product.id }}
                                    </p>
                                </td>

                                <td
                                    class="whitespace-nowrap py-4 text-right text-sm text-gray-600"
                                >
                                    {{ item.quantity }}
                                </td>

                                <td
                                    class="whitespace-nowrap py-4 text-right text-sm text-gray-600"
                                >
                                    {{ formatCurrency(item.unit_price) }}
                                </td>

                                <td
                                    class="whitespace-nowrap py-4 pl-3 text-right text-sm font-semibold text-gray-900"
                                >
                                    {{ formatCurrency(item.subtotal) }}
                                </td>
                            </tr>

                            <tr v-if="!invoice.items?.length">
                                <td
                                    colspan="5"
                                    class="py-8 text-center text-sm text-gray-500"
                                >
                                    No items found for this invoice.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div
                    class="flex justify-end border-t border-gray-200 pt-7"
                >
                    <div class="w-full sm:w-[320px]">
                        <div
                            class="flex items-center justify-between py-1.5 text-sm"
                        >
                            <span class="text-gray-500"> Subtotal </span>

                            <span class="font-medium text-gray-900">
                                {{ formatCurrency(invoice.subtotal) }}
                            </span>
                        </div>

                        <div
                            v-if="Number(invoice.tax || 0) > 0"
                            class="flex items-center justify-between py-1.5 text-sm"
                        >
                            <span class="text-gray-500"> Tax </span>

                            <span class="font-medium text-gray-900">
                                {{ formatCurrency(invoice.tax) }}
                            </span>
                        </div>

                        <div
                            v-if="Number(invoice.discount || 0) > 0"
                            class="flex items-center justify-between py-1.5 text-sm"
                        >
                            <span class="text-gray-500"> Discount </span>

                            <span class="font-medium text-gray-700">
                                -{{ formatCurrency(invoice.discount) }}
                            </span>
                        </div>

                        <div
                            class="mt-3 flex items-center justify-between border-t-2 border-gray-900 pt-4"
                        >
                            <span
                                class="text-base font-bold text-gray-900"
                            >
                                Total
                            </span>

                            <span
                                class="text-xl font-bold text-gray-900"
                            >
                                {{ formatCurrency(invoice.total) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="mt-12 border-t border-gray-200 pt-6">
                    <p
                        class="text-center text-sm font-medium text-gray-700"
                    >
                        Thank you for your business.
                    </p>

                    <p
                        class="mt-1 text-center text-xs text-gray-400"
                    >
                        This is a computer-generated invoice.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    html,
    body {
        width: 100%;
        min-width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
        color: #111827 !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    #app {
        width: 100% !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    .invoice-page {
        width: 100% !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    .invoice-toolbar {
        display: none !important;
    }

    .invoice-paper {
        width: 100% !important;
        max-width: none !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #ffffff !important;
    }

    #invoice-document {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        background: #ffffff !important;
    }

    #invoice-document > div {
        padding-top: 0 !important;
    }

    /* Keep invoice sections together where possible */
    .invoice-paper > div > div {
        break-inside: avoid;
    }

    /* Table can continue naturally across pages */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
        page-break-inside: auto !important;
    }

    thead {
        display: table-header-group;
    }

    tbody {
        page-break-inside: auto !important;
    }

    tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        page-break-after: auto !important;
    }

    th,
    td {
        break-inside: avoid !important;
    }

    /* Keep totals together */
    .invoice-paper .border-t-2 {
        break-inside: avoid !important;
    }

    /* Keep footer with invoice when possible */
    .invoice-paper .mt-12 {
        break-inside: avoid !important;
    }

    /* Remove unnecessary screen-only effects */
    img {
        max-width: 100% !important;
    }

    button,
    a {
        display: none !important;
    }
}
</style>
