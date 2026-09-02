<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

import { useInvoiceStore } from "../../stores/invoice";
import { useToastStore } from "../../stores/toast";
import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/Basecard.vue";

const router = useRouter();
const invoiceStore = useInvoiceStore();
const toastStore = useToastStore();

const loadingProducts = ref(false);
const productError = ref(null);

const searchInput = ref("");
const products = ref([]);
const selectedProductId = ref("");
const selectedQuantity = ref(1);

const customer = ref({
    name: "",
    email: "",
    phone: "",
});

const items = ref([]);

const taxPercentage = ref(0);
const discountPercentage = ref(0);

const submitting = computed(() => invoiceStore.loading);

const currency = new Intl.NumberFormat("en-NP", {
    style: "currency",
    currency: "NPR",
    minimumFractionDigits: 2,
});

const filteredProducts = computed(() => {
    const search = searchInput.value.trim().toLowerCase();

    if (!search) {
        return products.value;
    }

    return products.value.filter((product) => {
        return (
            String(product.name || "")
                .toLowerCase()
                .includes(search) ||
            String(product.description || "")
                .toLowerCase()
                .includes(search)
        );
    });
});

const selectedProduct = computed(() => {
    return (
        products.value.find(
            (product) => Number(product.id) === Number(selectedProductId.value),
        ) || null
    );
});

const selectedProductAlreadyAdded = computed(() => {
    if (!selectedProduct.value) {
        return false;
    }

    return items.value.some(
        (item) => Number(item.product_id) === Number(selectedProduct.value.id),
    );
});

const subtotal = computed(() => {
    return items.value.reduce((total, item) => {
        return total + Number(item.subtotal || 0);
    }, 0);
});

// Calculate tax amount from percentage for display only.
// Backend remains the source of truth.
const taxAmount = computed(() => {
    const percentage = Math.max(
        0,
        Math.min(100, Number(taxPercentage.value) || 0),
    );

    return (subtotal.value * percentage) / 100;
});

// Calculate discount amount from percentage for display only.
// Backend remains the source of truth.
const discountAmount = computed(() => {
    const percentage = Math.max(
        0,
        Math.min(100, Number(discountPercentage.value) || 0),
    );

    return (subtotal.value * percentage) / 100;
});

const total = computed(() => {
    return Math.max(0, subtotal.value + taxAmount.value - discountAmount.value);
});

const canAddProduct = computed(() => {
    if (!selectedProduct.value) {
        return false;
    }

    if (selectedProductAlreadyAdded.value) {
        return false;
    }

    if (Number(selectedProduct.value.quantity) <= 0) {
        return false;
    }

    const quantity = Number(selectedQuantity.value);

    return quantity >= 1 && quantity <= Number(selectedProduct.value.quantity);
});

async function loadProducts() {
    loadingProducts.value = true;
    productError.value = null;

    try {
        const response = await axios.get("/api/products", {
            params: {
                page: 1,
                per_page: 100,
                filter: "all",
            },
        });

        const data = response.data;

        if (data?.products && Array.isArray(data.products.data)) {
            products.value = data.products.data;
        } else {
            products.value = [];
        }
    } catch (error) {
        console.error("Load invoice products error:", error);

        productError.value =
            error.response?.data?.message ||
            "Failed to load products. Please try again.";

        toastStore.error(productError.value);
    } finally {
        loadingProducts.value = false;
    }
}

function handleProductChange() {
    selectedQuantity.value = 1;
}

function addProduct() {
    const product = selectedProduct.value;

    if (!product || !canAddProduct.value) {
        return;
    }

    const quantity = Number(selectedQuantity.value);
    const unitPrice = Number(product.price || 0);

    items.value.push({
        product_id: Number(product.id),
        product_name: product.name,
        quantity,
        unit_price: unitPrice,
        subtotal: quantity * unitPrice,
        available_quantity: Number(product.quantity || 0),
    });

    selectedProductId.value = "";
    selectedQuantity.value = 1;
    searchInput.value = "";
}

function removeItem(index) {
    items.value.splice(index, 1);
}

function increaseQuantity(index) {
    const item = items.value[index];

    if (!item) {
        return;
    }

    if (item.quantity >= item.available_quantity) {
        return;
    }

    item.quantity += 1;
    updateItemSubtotal(item);
}

function decreaseQuantity(index) {
    const item = items.value[index];

    if (!item) {
        return;
    }

    if (item.quantity <= 1) {
        return;
    }

    item.quantity -= 1;
    updateItemSubtotal(item);
}

function updateQuantity(index, event) {
    const item = items.value[index];

    if (!item) {
        return;
    }

    let quantity = Number(event.target.value);

    if (!Number.isFinite(quantity)) {
        quantity = 1;
    }

    quantity = Math.floor(quantity);

    quantity = Math.max(1, Math.min(quantity, item.available_quantity));

    item.quantity = quantity;

    updateItemSubtotal(item);
}

function updateItemSubtotal(item) {
    item.subtotal = Number(item.quantity) * Number(item.unit_price);
}

function formatCurrency(value) {
    return currency.format(Number(value || 0));
}

function cancel() {
    router.push({
        name: "invoices.index",
    });
}

function clearForm() {
    customer.value = {
        name: "",
        email: "",
        phone: "",
    };

    items.value = [];

    taxPercentage.value = 0;
    discountPercentage.value = 0;

    selectedProductId.value = "";
    selectedQuantity.value = 1;
    searchInput.value = "";

    invoiceStore.clearError();
}

async function submitInvoice() {
    invoiceStore.clearError();

    if (!customer.value.name.trim()) {
        invoiceStore.error = "Customer name is required.";
        toastStore.error("Customer name is required.");
        return;
    }

    if (items.value.length === 0) {
        invoiceStore.error = "Please add at least one product to the invoice.";
        toastStore.error("Please add at least one product to the invoice.");
        return;
    }

    const tax = Number(taxPercentage.value) || 0;
    const discount = Number(discountPercentage.value) || 0;

    if (tax < 0 || tax > 100) {
        invoiceStore.error = "Tax percentage must be between 0% and 100%.";
        toastStore.error("Tax percentage must be between 0% and 100%.");
        return;
    }

    if (discount < 0 || discount > 100) {
        invoiceStore.error = "Discount percentage must be between 0% and 100%.";
        toastStore.error("Discount percentage must be between 0% and 100%.");
        return;
    }

    const payload = {
        customer_name: customer.value.name.trim(),
        customer_email: customer.value.email.trim() || null,
        customer_phone: customer.value.phone.trim() || null,

        tax_percentage: tax,
        discount_percentage: discount,

        items: items.value.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
        })),
    };

    try {
        await invoiceStore.createInvoice(payload);

        toastStore.success("Invoice created successfully.");

        router.push({
            name: "invoices.index",
        });
    } catch (error) {
        console.error("Create invoice error:", error);

        toastStore.error(invoiceStore.error || "Failed to create invoice.");
    }
}

onMounted(() => {
    loadProducts();
});
</script>

<template>
    <div
        class="min-h-full bg-gray-50 px-4 py-6 dark:bg-gray-950 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-7xl">
            <!-- Header -->

            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        Create Invoice
                    </h1>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create a new invoice and add products to the order.
                    </p>
                </div>

                <BaseButton type="button" variant="secondary" @click="cancel">
                    Cancel
                </BaseButton>
            </div>

            <!-- Error -->

            <div
                v-if="invoiceStore.error"
                class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
            >
                {{ invoiceStore.error }}
            </div>

            <!-- Main layout -->

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Left side -->

                <div class="space-y-6 lg:col-span-2">
                    <!-- Customer Information -->

                    <BaseCard>
                        <div
                            class="mb-5 border-b border-gray-100 pb-4 dark:border-gray-700"
                        >
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Customer Information
                            </h2>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Enter the customer's contact information.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    for="customer-name"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >
                                    Customer Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input
                                    id="customer-name"
                                    v-model="customer.name"
                                    type="text"
                                    placeholder="Enter customer name"
                                    :disabled="submitting"
                                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                                />
                            </div>

                            <div>
                                <label
                                    for="customer-email"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >
                                    Email
                                </label>

                                <input
                                    id="customer-email"
                                    v-model="customer.email"
                                    type="email"
                                    placeholder="customer@example.com"
                                    :disabled="submitting"
                                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                                />
                            </div>

                            <div>
                                <label
                                    for="customer-phone"
                                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                >
                                    Phone
                                </label>

                                <input
                                    id="customer-phone"
                                    v-model="customer.phone"
                                    type="tel"
                                    placeholder="98XXXXXXXX"
                                    :disabled="submitting"
                                    class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                                />
                            </div>
                        </div>
                    </BaseCard>

                    <!-- Add Products -->

                    <BaseCard>
                        <div
                            class="mb-5 border-b border-gray-100 pb-4 dark:border-gray-700"
                        >
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Add Products
                            </h2>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Select products and specify the quantity.
                            </p>
                        </div>

                        <!-- Product loading -->

                        <div
                            v-if="loadingProducts"
                            class="rounded-lg border border-gray-200 bg-gray-50 p-5 text-center dark:border-gray-700 dark:bg-gray-800/50"
                        >
                            <div
                                class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900 dark:border-gray-600 dark:border-t-white"
                            ></div>

                            <p
                                class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Loading products...
                            </p>
                        </div>

                        <!-- Product error -->

                        <div
                            v-else-if="productError"
                            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300"
                        >
                            {{ productError }}

                            <button
                                type="button"
                                class="ml-2 font-semibold underline"
                                @click="loadProducts"
                            >
                                Retry
                            </button>
                        </div>

                        <!-- Product selection -->

                        <div v-else class="space-y-4">
                            <div
                                class="grid gap-4 md:grid-cols-[1fr_140px_auto]"
                            >
                                <div>
                                    <label
                                        for="product-search"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                    >
                                        Product
                                    </label>

                                    <input
                                        id="product-search"
                                        v-model="searchInput"
                                        type="text"
                                        placeholder="Search product..."
                                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300"
                                    />

                                    <select
                                        v-model="selectedProductId"
                                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300"
                                        @change="handleProductChange"
                                    >
                                        <option value="">
                                            Select a product
                                        </option>

                                        <option
                                            v-for="product in filteredProducts"
                                            :key="product.id"
                                            :value="product.id"
                                            :disabled="
                                                Number(product.quantity) <= 0 ||
                                                items.some(
                                                    (item) =>
                                                        Number(
                                                            item.product_id,
                                                        ) ===
                                                        Number(product.id),
                                                )
                                            "
                                        >
                                            {{ product.name }}
                                            —
                                            {{ formatCurrency(product.price) }}
                                            — Stock: {{ product.quantity }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        for="product-quantity"
                                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                                    >
                                        Quantity
                                    </label>

                                    <input
                                        id="product-quantity"
                                        v-model.number="selectedQuantity"
                                        type="number"
                                        min="1"
                                        :max="
                                            selectedProduct
                                                ? selectedProduct.quantity
                                                : 1
                                        "
                                        :disabled="!selectedProduct"
                                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                                    />

                                    <p
                                        v-if="selectedProduct"
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Available:
                                        {{ selectedProduct.quantity }}
                                    </p>
                                </div>

                                <div class="flex items-end">
                                    <BaseButton
                                        type="button"
                                        class="w-full md:w-auto"
                                        :disabled="!canAddProduct"
                                        @click="addProduct"
                                    >
                                        Add Product
                                    </BaseButton>
                                </div>
                            </div>

                            <div
                                v-if="selectedProductAlreadyAdded"
                                class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300"
                            >
                                This product is already in the invoice. Adjust
                                its quantity below instead.
                            </div>

                            <!-- Invoice items -->

                            <div
                                class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700"
                            >
                                <div
                                    v-if="items.length === 0"
                                    class="px-5 py-10 text-center"
                                >
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                                    >
                                        <svg
                                            class="h-6 w-6 text-gray-400"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 14.25l6-6m4.5 2.25a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 10.5v8.25A2.25 2.25 0 006.75 21h10.5a2.25 2.25 0 002.25-2.25V10.5z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M8.25 7.5V6a3.75 3.75 0 017.5 0v1.5"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        No products added
                                    </p>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Select a product above to add it to the
                                        invoice.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="divide-y divide-gray-200 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="(item, index) in items"
                                        :key="item.product_id"
                                        class="p-4"
                                    >
                                        <div
                                            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-semibold text-gray-900 dark:text-white"
                                                >
                                                    {{ item.product_name }}
                                                </p>

                                                <p
                                                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            item.unit_price,
                                                        )
                                                    }}
                                                    each · Stock:
                                                    {{
                                                        item.available_quantity
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                class="flex flex-wrap items-center gap-3"
                                            >
                                                <div
                                                    class="flex items-center rounded-lg border border-gray-300 dark:border-gray-600"
                                                >
                                                    <button
                                                        type="button"
                                                        class="px-3 py-2 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-800"
                                                        :disabled="
                                                            item.quantity <= 1
                                                        "
                                                        @click="
                                                            decreaseQuantity(
                                                                index,
                                                            )
                                                        "
                                                    >
                                                        −
                                                    </button>

                                                    <input
                                                        :value="item.quantity"
                                                        type="number"
                                                        min="1"
                                                        :max="
                                                            item.available_quantity
                                                        "
                                                        class="w-16 border-x border-gray-300 bg-transparent px-2 py-2 text-center text-sm font-semibold text-gray-900 outline-none dark:border-gray-600 dark:text-white"
                                                        @input="
                                                            updateQuantity(
                                                                index,
                                                                $event,
                                                            )
                                                        "
                                                    />

                                                    <button
                                                        type="button"
                                                        class="px-3 py-2 text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-800"
                                                        :disabled="
                                                            item.quantity >=
                                                            item.available_quantity
                                                        "
                                                        @click="
                                                            increaseQuantity(
                                                                index,
                                                            )
                                                        "
                                                    >
                                                        +
                                                    </button>
                                                </div>

                                                <div
                                                    class="w-28 text-right font-semibold text-gray-900 dark:text-white"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            item.subtotal,
                                                        )
                                                    }}
                                                </div>

                                                <button
                                                    type="button"
                                                    class="rounded-lg p-2 text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                                                    title="Remove product"
                                                    @click="removeItem(index)"
                                                >
                                                    <svg
                                                        class="h-5 w-5"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M6 7.5h12M9.75 7.5V5.25h4.5V7.5m-6.75 0l.75 11.25h6l.75-11.25M10.5 11.25v4.5m3-4.5v4.5"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </BaseCard>
                </div>

                <!-- Summary -->

                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-6">
                        <BaseCard>
                            <div
                                class="mb-5 border-b border-gray-100 pb-4 dark:border-gray-700"
                            >
                                <h2
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    Invoice Summary
                                </h2>
                            </div>

                            <div class="space-y-4">
                                <div
                                    class="flex justify-between text-sm text-gray-600 dark:text-gray-400"
                                >
                                    <span> Items </span>

                                    <span
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ items.length }}
                                    </span>
                                </div>

                                <div
                                    class="flex justify-between text-sm text-gray-600 dark:text-gray-400"
                                >
                                    <span> Subtotal </span>

                                    <span
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ formatCurrency(subtotal) }}
                                    </span>
                                </div>

                                <div>
                                    <label
                                        for="tax"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Tax Percentage
                                    </label>

                                    <div class="relative mt-2">
                                        <input
                                            id="tax"
                                            v-model.number="taxPercentage"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            placeholder="0"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 pr-10 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300"
                                        />

                                        <span
                                            class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400"
                                        >
                                            %
                                        </span>
                                    </div>

                                    <p
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Tax amount:
                                        {{ formatCurrency(taxAmount) }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        for="discount"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Discount Percentage
                                    </label>

                                    <div class="relative mt-2">
                                        <input
                                            id="discount"
                                            v-model.number="discountPercentage"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            placeholder="0"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 pr-10 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300"
                                        />

                                        <span
                                            class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400"
                                        >
                                            %
                                        </span>
                                    </div>

                                    <p
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Discount amount:
                                        {{ formatCurrency(discountAmount) }}
                                    </p>
                                </div>

                                <div
                                    class="border-t border-gray-200 pt-4 dark:border-gray-700"
                                >
                                    <div class="flex items-end justify-between">
                                        <span
                                            class="text-sm font-medium text-gray-600 dark:text-gray-400"
                                        >
                                            Total
                                        </span>

                                        <span
                                            class="text-2xl font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ formatCurrency(total) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-3 pt-2">
                                    <BaseButton
                                        type="button"
                                        class="w-full"
                                        :loading="submitting"
                                        :disabled="
                                            items.length === 0 ||
                                            !customer.name.trim()
                                        "
                                        @click="submitInvoice"
                                    >
                                        Create Invoice
                                    </BaseButton>

                                    <BaseButton
                                        type="button"
                                        variant="secondary"
                                        class="w-full"
                                        :disabled="submitting"
                                        @click="clearForm"
                                    >
                                        Clear
                                    </BaseButton>
                                </div>
                            </div>
                        </BaseCard>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
