<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import { useProductStore } from "../../stores/product";
import { useToastStore } from "../../stores/toast";

import ProductList from "./ProductList.vue";

import BaseModal from "../common/BaseModal.vue";
import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";

const router = useRouter();

const productStore = useProductStore();
const toastStore = useToastStore();

const showDeleteModal = ref(false);
const productToDelete = ref(null);
const deleting = ref(false);

const searchInput = ref("");
const priceFilter = ref("all");

const products = computed(() => {
    return Array.isArray(productStore.products) ? productStore.products : [];
});

const loading = computed(() => {
    return productStore.loading;
});

const error = computed(() => {
    return productStore.error;
});

const currentPage = computed(() => {
    return productStore.currentPage;
});

const lastPage = computed(() => {
    return productStore.lastPage;
});

const total = computed(() => {
    return productStore.total;
});

const perPage = computed(() => {
    return productStore.perPage;
});

const hasPreviousPage = computed(() => {
    return currentPage.value > 1;
});

const hasNextPage = computed(() => {
    return currentPage.value < lastPage.value;
});

const search = computed(() => {
    return productStore.search;
});

const filter = computed(() => {
    return productStore.filter;
});

const stats = computed(() => {
    return (
        productStore.stats || {
            total_products: 0,
            in_stock: 0,
            low_stock: 0,
            out_of_stock: 0,
            total_quantity: 0,
        }
    );
});

const firstProductNumber = computed(() => {
    if (total.value === 0) {
        return 0;
    }

    return (currentPage.value - 1) * perPage.value + 1;
});

const lastProductNumber = computed(() => {
    return Math.min(currentPage.value * perPage.value, total.value);
});

const priceRange = computed(() => {
    switch (priceFilter.value) {
        case "under_1000":
            return {
                min: null,
                max: 1000,
            };

        case "1000_5000":
            return {
                min: 1000,
                max: 5000,
            };

        case "5000_10000":
            return {
                min: 5000,
                max: 10000,
            };

        case "above_10000":
            return {
                min: 10000,
                max: null,
            };

        default:
            return {
                min: null,
                max: null,
            };
    }
});

const hasSearch = computed(() => {
    return searchInput.value.trim() !== "";
});

const hasProductFilter = computed(() => {
    return filter.value !== "all";
});

const hasPriceFilter = computed(() => {
    return priceFilter.value !== "all";
});

const hasActiveFilters = computed(() => {
    return hasSearch.value || hasProductFilter.value || hasPriceFilter.value;
});

const filterLabel = computed(() => {
    const labels = {
        all: "All",
        latest: "Latest",
        oldest: "Oldest",
        in_stock: "In Stock",
        low_stock: "Low Stock",
        out_of_stock: "Out of Stock",
    };

    return labels[filter.value] || "All";
});

const priceFilterLabel = computed(() => {
    const labels = {
        all: "All prices",
        under_1000: "Under Rs. 1,000",
        1000_5000: "Rs. 1,000 – Rs. 5,000",
        5000_10000: "Rs. 5,000 – Rs. 10,000",
        above_10000: "Above Rs. 10,000",
    };

    return labels[priceFilter.value] || "All prices";
});

async function loadProducts(page = productStore.currentPage) {
    try {
        await productStore.fetchProducts(
            page,
            productStore.search,
            productStore.filter,
            priceRange.value.min,
            priceRange.value.max,
        );
    } catch (err) {
        console.error("Failed to load products:", err);
    }
}

async function performSearch() {
    try {
        await productStore.searchProducts(searchInput.value.trim());
    } catch (err) {
        console.error("Search products error:", err);
    }
}

async function clearSearch() {
    searchInput.value = "";

    try {
        await productStore.clearSearch();
    } catch (err) {
        console.error("Clear search error:", err);
    }
}

async function clearAllFilters() {
    searchInput.value = "";
    priceFilter.value = "all";

    try {
        await productStore.fetchProducts(1, "", "all", null, null);
    } catch (err) {
        console.error("Clear filters error:", err);
    }
}

async function changeFilter(event) {
    const selectedFilter = event.target.value;

    try {
        await productStore.filterProducts(selectedFilter);
    } catch (err) {
        console.error("Filter products error:", err);
    }
}

async function changePriceFilter() {
    try {
        await productStore.priceFilterProducts(
            priceRange.value.min,
            priceRange.value.max,
        );
    } catch (err) {
        console.error("Price filter error:", err);
    }
}

async function goToPage(page) {
    if (page < 1 || page > lastPage.value || page === currentPage.value) {
        return;
    }

    try {
        await loadProducts(page);
    } catch (err) {
        console.error("Pagination error:", err);
    }
}

async function previousPage() {
    if (!hasPreviousPage.value) {
        return;
    }

    await goToPage(currentPage.value - 1);
}

async function nextPage() {
    if (!hasNextPage.value) {
        return;
    }

    await goToPage(currentPage.value + 1);
}

function addProduct() {
    router.push({
        name: "products.create",
    });
}

function viewProduct(product) {
    router.push({
        name: "products.view",
        params: {
            id: product.id,
        },
    });
}

function editProduct(product) {
    router.push({
        name: "products.edit",
        params: {
            id: product.id,
        },
    });
}

function openDeleteModal(product) {
    productToDelete.value = product;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
    productToDelete.value = null;
}

async function confirmDelete() {
    if (!productToDelete.value) {
        return;
    }

    deleting.value = true;

    try {
        await productStore.deleteProduct(productToDelete.value.id);

        toastStore.success("Product deleted successfully.");

        showDeleteModal.value = false;
        productToDelete.value = null;
    } catch (err) {
        console.error("Delete product error:", err);
    } finally {
        deleting.value = false;
    }
}

async function bulkDelete(productsToDelete) {
    if (!Array.isArray(productsToDelete) || productsToDelete.length === 0) {
        return;
    }

    try {
        await productStore.bulkDelete(productsToDelete);

        toastStore.success("Products deleted successfully.");
    } catch (err) {
        console.error("Bulk delete error:", err);
    }
}

function bulkEdit(productsToEdit) {
    if (!Array.isArray(productsToEdit) || productsToEdit.length === 0) {
        return;
    }

    if (productsToEdit.length === 1) {
        editProduct(productsToEdit[0]);

        return;
    }

    router.push({
        name: "products.bulk-edit",
        query: {
            selected_products: productsToEdit
                .map((product) => product.id)
                .join(","),
        },
    });
}

async function refreshProducts() {
    await loadProducts(currentPage.value);
}

onMounted(() => {
    searchInput.value = productStore.search;

    loadProducts(productStore.currentPage);
});
</script>

<template>
    <div
        class="min-h-[calc(100vh-4rem)] bg-gray-50 transition-colors duration-300 dark:bg-gray-950"
    >
        <div class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-4 sm:py-6 lg:px-6">
            <div class="mb-6">
                <div
                    class="flex flex-col gap-5 rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900 sm:px-6 sm:py-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex min-w-0 items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-900 shadow-sm dark:bg-white"
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
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h1
                                class="truncate text-xl font-semibold tracking-tight text-gray-900 transition-colors dark:text-white sm:text-2xl"
                            >
                                Products
                            </h1>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                Manage your inventory
                            </p>
                        </div>
                    </div>

                    <div class="flex w-full items-center gap-2 sm:w-auto">
                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="loading"
                            class="flex-1 justify-center sm:flex-none"
                            @click="refreshProducts"
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

                            <span class="hidden sm:inline">
                                {{ loading ? "Refreshing..." : "Refresh" }}
                            </span>
                        </BaseButton>

                        <BaseButton
                            type="button"
                            class="flex-1 justify-center sm:flex-none"
                            @click="addProduct"
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>

                            <span>Add Product</span>
                        </BaseButton>
                        <!-- trash button -->
                        <BaseButton
                            type="button"
                            variant="secondary"
                            @click="router.push({ name: 'trash' })"
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
                                    stroke-width="1.8"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h12"
                                />
                            </svg>

                            <span>Trash</span>
                        </BaseButton>
                    </div>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800"
                        >
                            <svg
                                class="h-4 w-4 text-gray-600 dark:text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                        </div>

                        <p
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            Products
                        </p>
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ stats.total_products }}
                    </p>
                </div>

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950/40"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-green-500"
                            ></span>
                        </div>

                        <p
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            In Stock
                        </p>
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ stats.in_stock }}
                    </p>
                </div>

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950/40"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-amber-500"
                            ></span>
                        </div>

                        <p
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            Low Stock
                        </p>
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ stats.low_stock }}
                    </p>
                </div>

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 dark:bg-red-950/40"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-red-500"
                            ></span>
                        </div>

                        <p
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            Out of Stock
                        </p>
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ stats.out_of_stock }}
                    </p>
                </div>

                <div
                    class="group rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950/40"
                        >
                            <svg
                                class="h-4 w-4 text-blue-600 dark:text-blue-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 7h16M4 12h16M4 17h16"
                                />
                            </svg>
                        </div>

                        <p
                            class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                        >
                            Total Units
                        </p>
                    </div>

                    <p
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ stats.total_quantity }}
                    </p>
                </div>
            </div>

            <BaseCard class="mb-6 overflow-visible">
                <div class="space-y-4">
                    <form
                        class="flex flex-col gap-2 sm:flex-row"
                        @submit.prevent="performSearch"
                    >
                        <div class="relative min-w-0 flex-1">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                            >
                                <svg
                                    class="h-5 w-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z"
                                    />
                                </svg>
                            </div>

                            <input
                                v-model="searchInput"
                                type="text"
                                placeholder="Search products..."
                                autocomplete="off"
                                class="h-12 w-full rounded-xl border border-gray-200 bg-gray-50 pl-12 pr-11 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-400 hover:border-gray-300 hover:bg-white focus:border-gray-400 focus:bg-white focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:focus:border-gray-500 dark:focus:bg-gray-800 dark:focus:ring-gray-700/50"
                            />

                            <button
                                v-if="searchInput"
                                type="button"
                                aria-label="Clear search"
                                class="absolute right-3 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                @click="clearSearch"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>

                        <BaseButton
                            type="submit"
                            :disabled="loading"
                            class="h-12 w-full justify-center px-6 sm:w-auto"
                        >
                            <svg
                                v-if="!loading"
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z"
                                />
                            </svg>

                            <svg
                                v-else
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

                            <span>
                                {{ loading ? "Searching..." : "Search" }}
                            </span>
                        </BaseButton>
                    </form>

                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <select
                                    :value="filter"
                                    :disabled="loading"
                                    aria-label="Product filter"
                                    class="h-10 min-w-[150px] appearance-none rounded-lg border border-gray-200 bg-white pl-3 pr-9 text-sm font-medium text-gray-700 outline-none transition hover:border-gray-300 focus:border-gray-400 focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-600 dark:focus:border-gray-500 dark:focus:ring-gray-700/50"
                                    @change="changeFilter"
                                >
                                    <option value="all">All Products</option>

                                    <option value="latest">Latest Added</option>

                                    <option value="oldest">Oldest Added</option>

                                    <option value="in_stock">In Stock</option>

                                    <option value="low_stock">Low Stock</option>

                                    <option value="out_of_stock">
                                        Out of Stock
                                    </option>
                                </select>

                                <svg
                                    class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="m6 9 6 6 6-6"
                                    />
                                </svg>
                            </div>

                            <div class="relative">
                                <select
                                    v-model="priceFilter"
                                    :disabled="loading"
                                    aria-label="Price filter"
                                    class="h-10 min-w-[125px] appearance-none rounded-lg border border-gray-200 bg-white pl-3 pr-9 text-sm font-medium text-gray-700 outline-none transition hover:border-gray-300 focus:border-gray-400 focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-600 dark:focus:border-gray-500 dark:focus:ring-gray-700/50"
                                    @change="changePriceFilter"
                                >
                                    <option value="all">All Prices</option>

                                    <option value="under_1000">
                                        Under Rs. 1,000
                                    </option>

                                    <option value="1000_5000">
                                        Rs. 1,000 – Rs. 5,000
                                    </option>

                                    <option value="5000_10000">
                                        Rs. 5,000 – Rs. 10,000
                                    </option>

                                    <option value="above_10000">
                                        Above Rs. 10,000
                                    </option>
                                </select>

                                <svg
                                    class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="m6 9 6 6 6-6"
                                    />
                                </svg>
                            </div>
                        </div>

                        <div
                            v-if="hasActiveFilters"
                            class="flex flex-wrap items-center gap-2"
                        >
                            <span
                                v-if="hasSearch"
                                class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                <svg
                                    class="h-3 w-3 shrink-0 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="m21 21-4.35-4.35m2.1-5.15a7.25 7.25 0 1 1-14.5 0 7.25 7.25 0 0 1 14.5 0Z"
                                    />
                                </svg>

                                <span class="max-w-[150px] truncate">
                                    {{ searchInput }}
                                </span>
                            </span>

                            <span
                                v-if="hasProductFilter"
                                class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ filterLabel }}
                            </span>

                            <span
                                v-if="hasPriceFilter"
                                class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{ priceFilterLabel }}
                            </span>

                            <button
                                type="button"
                                aria-label="Clear all filters"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                @click="clearAllFilters"
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
                                        stroke-width="1.8"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </BaseCard>

            <BaseCard
                v-if="error"
                class="mb-6 border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40"
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
                                    d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z"
                                />
                            </svg>
                        </div>

                        <div>
                            <h3
                                class="text-sm font-semibold text-red-800 dark:text-red-300"
                            >
                                Something went wrong
                            </h3>

                            <p
                                class="mt-1 text-sm text-red-700 dark:text-red-400"
                            >
                                {{ error }}
                            </p>
                        </div>
                    </div>

                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="refreshProducts"
                    >
                        Try Again
                    </BaseButton>
                </div>
            </BaseCard>

            <BaseCard v-if="loading && products.length === 0" class="py-20">
                <div class="flex flex-col items-center justify-center">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                    >
                        <svg
                            class="h-7 w-7 animate-spin text-gray-700 dark:text-gray-300"
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
                    </div>

                    <p
                        class="mt-5 text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        Loading products
                    </p>
                </div>
            </BaseCard>

            <div
                v-else
                class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900"
            >
                <ProductList
                    :products="products"
                    :current-page="currentPage"
                    :per-page="perPage"
                    @add-product="addProduct"
                    @view-product="viewProduct"
                    @edit-product="editProduct"
                    @delete-product="openDeleteModal"
                    @bulk-delete="bulkDelete"
                    @bulk-edit="bulkEdit"
                />

                <div
                    v-if="loading"
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[1px] dark:bg-gray-950/60"
                >
                    <div
                        class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <svg
                            class="h-5 w-5 animate-spin text-gray-700 dark:text-gray-300"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-20"
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="3"
                            />

                            <path
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"
                            />
                        </svg>

                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Loading...
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-if="total > 0"
                class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between"
                >
                    <p
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{ firstProductNumber }}–{{ lastProductNumber }}
                        </span>

                        <span class="text-gray-400"> of </span>

                        <span
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{ total }}
                        </span>

                        <span class="text-gray-500 dark:text-gray-400">
                            products
                        </span>
                    </p>

                    <div
                        class="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-end"
                    >
                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="!hasPreviousPage || loading"
                            class="h-9 justify-center"
                            @click="previousPage"
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

                            <span class="hidden sm:inline">Previous</span>
                        </BaseButton>

                        <div
                            class="flex h-9 items-center rounded-lg bg-gray-900 px-3 text-sm font-semibold text-white dark:bg-white dark:text-gray-900"
                        >
                            {{ currentPage }}

                            <span class="mx-1 text-gray-400">/</span>

                            {{ lastPage }}
                        </div>

                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="!hasNextPage || loading"
                            class="h-9 justify-center"
                            @click="nextPage"
                        >
                            <span class="hidden sm:inline">Next</span>

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
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </BaseButton>
                    </div>
                </div>
            </div>

            <BaseModal
                :show="showDeleteModal"
                title="Delete Product"
                size="sm"
                @close="closeDeleteModal"
            >
                <div class="text-center">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 dark:bg-red-950/40"
                    >
                        <svg
                            class="h-7 w-7 text-red-600 dark:text-red-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-5 text-lg font-bold text-gray-900 dark:text-white"
                    >
                        Delete Product?
                    </h3>

                    <p
                        class="mx-auto mt-2 max-w-sm text-sm leading-6 text-gray-500 dark:text-gray-400"
                    >
                        Are you sure you want to delete

                        <span
                            class="font-semibold text-gray-800 dark:text-gray-200"
                        >
                            {{ productToDelete?.name }}
                        </span>

                        ?

                        <br />

                        This action cannot be undone.
                    </p>
                </div>

                <template #footer>
                    <div
                        class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="deleting"
                            class="justify-center"
                            @click="closeDeleteModal"
                        >
                            Cancel
                        </BaseButton>

                        <BaseButton
                            type="button"
                            variant="danger"
                            :disabled="deleting"
                            class="justify-center"
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

                            {{ deleting ? "Deleting..." : "Delete Product" }}
                        </BaseButton>
                    </div>
                </template>
            </BaseModal>
        </div>
    </div>
</template>
