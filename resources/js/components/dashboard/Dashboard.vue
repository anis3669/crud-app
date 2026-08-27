<script setup>
import { computed, onMounted } from "vue";
import { useProductStore } from "../../stores/product";

const productStore = useProductStore();

onMounted(async () => {
    await productStore.fetchProducts();
});


// INVENTORY STATS


const totalProducts = computed(() => {
    return productStore.stats.total_products;
});

const inStock = computed(() => {
    return productStore.stats.in_stock;
});

const outOfStock = computed(() => {
    return productStore.stats.out_of_stock;
});

const totalQuantity = computed(() => {
    return productStore.stats.total_quantity;
});


// INVENTORY VALUE


const inventoryValue = computed(() => {
    return productStore.products.reduce((total, product) => {
        return (
            total +
            Number(product.price || 0) *
                Number(product.quantity || 0)
        );
    }, 0);
});
</script>

<template>
    <div class="p-6">

        <!-- HEADER -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Dashboard
            </h1>

            <p class="mt-1 text-gray-500">
                Overview of your inventory
            </p>
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">

            <!-- TOTAL PRODUCTS -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500">
                    Total Products
                </p>

                <div class="mt-3 flex items-end justify-between">
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ totalProducts }}
                    </h2>

                </div>
            </div>

            <!-- IN STOCK -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500">
                    In Stock
                </p>

                <div class="mt-3 flex items-end justify-between">
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ inStock }}
                    </h2>

                </div>
            </div>

            <!-- OUT OF STOCK -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500">
                    Out of Stock
                </p>

                <div class="mt-3 flex items-end justify-between">
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ outOfStock }}
                    </h2>

                </div>
            </div>

            <!-- TOTAL QUANTITY -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500">
                    Total Units
                </p>

                <div class="mt-3 flex items-end justify-between">
                    <h2 class="text-3xl font-bold text-gray-900">
                        {{ totalQuantity }}
                    </h2>

                </div>
            </div>

        </div>

        <!-- LOWER SECTION -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- INVENTORY VALUE -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <p class="text-sm font-medium text-gray-500">
                    Current Page Inventory Value
                </p>

                <h2 class="mt-3 text-3xl font-bold text-gray-900">
                    Rs. {{ inventoryValue.toLocaleString() }}
                </h2>

                <p class="mt-2 text-sm text-gray-400">
                    Based on products currently loaded
                </p>
            </div>

            <!-- STOCK SUMMARY -->
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100 lg:col-span-2">

                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Stock Summary
                        </h2>

                        <p class="text-sm text-gray-500">
                            Current inventory status
                        </p>
                    </div>
                </div>

                <div class="mt-6 space-y-5">

                    <!-- IN STOCK -->
                    <div>
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="font-medium text-gray-700">
                                In Stock
                            </span>

                            <span class="text-gray-500">
                                {{ inStock }}
                            </span>
                        </div>

                        <div class="h-2 rounded-full bg-gray-100">
                            <div
                                class="h-2 rounded-full bg-green-500"
                                :style="{
                                    width:
                                        totalProducts > 0
                                            ? `${(inStock / totalProducts) * 100}%`
                                            : '0%'
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- OUT OF STOCK -->
                    <div>
                        <div class="mb-2 flex justify-between text-sm">
                            <span class="font-medium text-gray-700">
                                Out of Stock
                            </span>

                            <span class="text-gray-500">
                                {{ outOfStock }}
                            </span>
                        </div>

                        <div class="h-2 rounded-full bg-gray-100">
                            <div
                                class="h-2 rounded-full bg-red-500"
                                :style="{
                                    width:
                                        totalProducts > 0
                                            ? `${(outOfStock / totalProducts) * 100}%`
                                            : '0%'
                                }"
                            ></div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

    </div>
</template>
