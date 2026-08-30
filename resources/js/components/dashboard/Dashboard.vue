<script setup>
import { computed, onMounted } from "vue";
import { useProductStore } from "../../stores/product";
import InventoryChart from "../dashboard/InventoryChart.vue";

const productStore = useProductStore();

onMounted(async () => {
    try {
        await productStore.fetchProducts();
    } catch (error) {
        console.error("Failed to load dashboard:", error);
    }
});

const products = computed(() =>
    Array.isArray(productStore.products)
        ? productStore.products
        : [],
);

const loading = computed(() => productStore.loading);

const stats = computed(() => ({
    total_products: 0,
    in_stock: 0,
    out_of_stock: 0,
    total_quantity: 0,
    total_inventory_value: 0,
    ...productStore.stats,
}));

const totalProducts = computed(() =>
    Number(stats.value.total_products || 0),
);

const inStock = computed(() =>
    Number(stats.value.in_stock || 0),
);

const outOfStock = computed(() =>
    Number(stats.value.out_of_stock || 0),
);

const totalQuantity = computed(() =>
    Number(stats.value.total_quantity || 0),
);

const inventoryValue = computed(() =>
    Number(stats.value.total_inventory_value || 0),
);

const inStockPercentage = computed(() => {
    if (totalProducts.value === 0) {
        return 0;
    }

    return Math.round(
        (inStock.value / totalProducts.value) * 100,
    );
});

const outOfStockPercentage = computed(() => {
    if (totalProducts.value === 0) {
        return 0;
    }

    return Math.round(
        (outOfStock.value / totalProducts.value) * 100,
    );
});

const recentProducts = computed(() =>
    [...products.value]
        .sort(
            (a, b) =>
                new Date(b.created_at) -
                new Date(a.created_at),
        )
        .slice(0, 5),
);

function formatNumber(value) {
    return Number(value || 0).toLocaleString("en-US");
}

function formatCurrency(value) {
    return Number(value || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function imageUrl(image) {
    if (!image) {
        return null;
    }

    if (
        image.startsWith("http://") ||
        image.startsWith("https://") ||
        image.startsWith("/storage/")
    ) {
        return image;
    }

    return `/storage/${image}`;
}

function stockStatus(quantity) {
    const amount = Number(quantity) || 0;

    if (amount === 0) {
        return {
            text: "Out of stock",
            wrapper: "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400",
            dot: "bg-red-500",
        };
    }

    if (amount <= 5) {
        return {
            text: `${amount} left`,
            wrapper: "bg-yellow-50 text-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-400",
            dot: "bg-yellow-500",
        };
    }

    return {
        text: `${amount} in stock`,
        wrapper: "bg-green-50 text-green-700 dark:bg-green-950/40 dark:text-green-400",
        dot: "bg-green-500",
    };
}
</script>

<template>
    <div
        class="min-h-full w-full bg-gray-50 text-gray-900 transition-colors duration-200 dark:bg-gray-950 dark:text-gray-100"
    >
        <div
            class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-4 sm:py-6 lg:px-6"
        >
            <!-- HEADER -->

            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-900 shadow-sm dark:bg-white"
                        >
                            <svg
                                class="h-5 w-5 text-white dark:text-gray-900"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-4H3v4Zm10-10h8V3h-8v8Z"
                                />
                            </svg>
                        </div>

                        <div>
                            <h1
                                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                            >
                                Dashboard
                            </h1>

                            <p
                                class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Inventory overview
                            </p>
                        </div>
                    </div>
                </div>

                <RouterLink
                    to="/products"
                    class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    Products

                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </RouterLink>
            </div>

            <!-- STATS -->

            <div
                class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4"
            >
                <!-- TOTAL PRODUCTS -->

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20 sm:p-5"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                        >
                            <svg
                                class="h-5 w-5 text-gray-700 dark:text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                        </div>
                    </div>

                    <p
                        class="mt-4 text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Total Products
                    </p>

                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                    >
                        {{ formatNumber(totalProducts) }}
                    </p>
                </div>

                <!-- IN STOCK -->

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20 sm:p-5"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950/40"
                        >
                            <svg
                                class="h-5 w-5 text-green-600 dark:text-green-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M3.27 6.96 12 12l8.73-5.04M12 22V12"
                                />
                            </svg>
                        </div>
                    </div>

                    <p
                        class="mt-4 text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        In Stock
                    </p>

                    <div class="mt-1 flex items-end gap-2">
                        <p
                            class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                        >
                            {{ formatNumber(inStock) }}
                        </p>

                        <span
                            class="mb-1 text-xs font-semibold text-green-600 dark:text-green-400"
                        >
                            {{ inStockPercentage }}%
                        </span>
                    </div>
                </div>

                <!-- OUT OF STOCK -->

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20 sm:p-5"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 dark:bg-red-950/40"
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
                                    d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M8 10h8M8 14h5"
                                />
                            </svg>
                        </div>
                    </div>

                    <p
                        class="mt-4 text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Out of Stock
                    </p>

                    <div class="mt-1 flex items-end gap-2">
                        <p
                            class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                        >
                            {{ formatNumber(outOfStock) }}
                        </p>

                        <span
                            class="mb-1 text-xs font-semibold text-red-600 dark:text-red-400"
                        >
                            {{ outOfStockPercentage }}%
                        </span>
                    </div>
                </div>

                <!-- TOTAL UNITS -->

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20 sm:p-5"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                        >
                            <svg
                                class="h-5 w-5 text-gray-700 dark:text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="m4 7.5 8 4.5 8-4.5M12 12v9"
                                />
                            </svg>
                        </div>
                    </div>

                    <p
                        class="mt-4 text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Total Units
                    </p>

                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                    >
                        {{ formatNumber(totalQuantity) }}
                    </p>
                </div>
            </div>

            <!-- INVENTORY OVERVIEW -->

            <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- INVENTORY VALUE -->

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20 sm:p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                            >
                                Inventory Value
                            </p>

                            <h2
                                class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                            >
                                Rs. {{ formatCurrency(inventoryValue) }}
                            </h2>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-900 dark:bg-white"
                        >
                            <svg
                                class="h-5 w-5 text-white dark:text-gray-900"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10v1m0 10v1m7-6a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div
                        class="mt-5 h-px bg-gray-100 dark:bg-gray-800"
                    ></div>

                    <p
                        class="mt-4 text-xs text-gray-400 dark:text-gray-500"
                    >
                        Current value of your inventory
                    </p>
                </div>

                <!-- STOCK OVERVIEW -->

                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20 sm:p-6 lg:col-span-2"
                >
                    <div>
                        <h2
                            class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg"
                        >
                            Stock Overview
                        </h2>

                        <p
                            class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            Current stock distribution
                        </p>
                    </div>

                    <div class="mt-6 space-y-5">
                        <!-- IN STOCK -->

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2 w-2 rounded-full bg-green-500"
                                    ></span>

                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        In Stock
                                    </span>
                                </div>

                                <span
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ inStock }}

                                    <span
                                        class="font-normal text-gray-400 dark:text-gray-500"
                                    >
                                        ({{ inStockPercentage }}%)
                                    </span>
                                </span>
                            </div>

                            <div
                                class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                            >
                                <div
                                    class="h-full rounded-full bg-green-500 transition-all duration-500"
                                    :style="{
                                        width: `${inStockPercentage}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <!-- OUT OF STOCK -->

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2 w-2 rounded-full bg-red-500"
                                    ></span>

                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Out of Stock
                                    </span>
                                </div>

                                <span
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ outOfStock }}

                                    <span
                                        class="font-normal text-gray-400 dark:text-gray-500"
                                    >
                                        ({{ outOfStockPercentage }}%)
                                    </span>
                                </span>
                            </div>

                            <div
                                class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                            >
                                <div
                                    class="h-full rounded-full bg-red-500 transition-all duration-500"
                                    :style="{
                                        width: `${outOfStockPercentage}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INVENTORY CHART -->

            <div
                class="mb-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20"
            >
                <InventoryChart :stats="productStore.stats" />
            </div>

            <!-- RECENT PRODUCTS -->

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:shadow-black/20"
            >
                <div
                    class="flex flex-col gap-3 border-b border-gray-100 px-5 py-5 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                >
                    <div>
                        <h2
                            class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg"
                        >
                            Recent Products
                        </h2>

                        <p
                            class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            Latest additions to inventory
                        </p>
                    </div>

                    <RouterLink
                        to="/products"
                        class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                    >
                        View all

                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </RouterLink>
                </div>

                <!-- LOADING -->

                <div
                    v-if="loading && recentProducts.length === 0"
                    class="flex flex-col items-center justify-center px-6 py-16"
                >
                    <svg
                        class="h-7 w-7 animate-spin text-gray-500 dark:text-gray-400"
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
                        class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Loading products
                    </p>
                </div>

                <!-- PRODUCTS -->

                <div
                    v-else-if="recentProducts.length > 0"
                    class="divide-y divide-gray-100 dark:divide-gray-800"
                >
                    <div
                        v-for="product in recentProducts"
                        :key="product.id"
                        class="flex flex-col gap-4 px-5 py-4 transition hover:bg-gray-50 dark:hover:bg-gray-800/50 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="h-11 w-11 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800"
                            >
                                <img
                                    v-if="imageUrl(product.image)"
                                    :src="imageUrl(product.image)"
                                    :alt="
                                        product.name ||
                                        'Product image'
                                    "
                                    class="h-full w-full object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-sm font-bold text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        product.name
                                            ?.charAt(0)
                                            ?.toUpperCase() ||
                                        "P"
                                    }}
                                </div>
                            </div>

                            <div class="min-w-0">
                                <h3
                                    class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{
                                        product.name ||
                                        "Unnamed Product"
                                    }}
                                </h3>

                                <p
                                    class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Rs.
                                    {{
                                        formatCurrency(
                                            product.price,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4 sm:justify-end"
                        >
                            <div class="text-right">
                                <p
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Quantity
                                </p>

                                <p
                                    class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    {{
                                        formatNumber(
                                            product.quantity,
                                        )
                                    }}
                                </p>
                            </div>

                            <span
                                class="inline-flex min-w-[92px] items-center justify-center gap-1.5 rounded-full px-2.5 py-1.5 text-xs font-semibold"
                                :class="
                                    stockStatus(product.quantity)
                                        .wrapper
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        stockStatus(
                                            product.quantity,
                                        ).dot
                                    "
                                ></span>

                                {{
                                    stockStatus(product.quantity)
                                        .text
                                }}
                            </span>

                            <RouterLink
                                :to="`/products/${product.id}`"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                View
                            </RouterLink>
                        </div>
                    </div>
                </div>

                <!-- EMPTY -->

                <div
                    v-else
                    class="flex flex-col items-center justify-center px-6 py-16 text-center"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                    >
                        <svg
                            class="h-6 w-6 text-gray-400 dark:text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        No products yet
                    </h3>

                    <p
                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        Add your first product to see it here.
                    </p>

                    <RouterLink
                        to="/products/create"
                        class="mt-4 inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    >
                        Add Product
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
