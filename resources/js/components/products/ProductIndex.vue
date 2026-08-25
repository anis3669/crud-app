<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useProductStore } from '../../stores/product'

import ProductList from './ProductList.vue'

import BaseModal from '../common/BaseModal.vue'
import BaseButton from '../common/BaseButton.vue'
import BaseCard from '../common/BaseCard.vue'

const router = useRouter()
const productStore = useProductStore()

// State

const showDeleteModal = ref(false)
const productToDelete = ref(null)
const deleting = ref(false)

const searchInput = ref('')

// Products

const products = computed(() => {
    return Array.isArray(productStore.products)
        ? productStore.products
        : []
})

// Loading

const loading = computed(() => {
    return productStore.loading
})

// Error

const error = computed(() => {
    return productStore.error
})

// Pagination

const currentPage = computed(() => {
    return productStore.currentPage
})

const lastPage = computed(() => {
    return productStore.lastPage
})

const total = computed(() => {
    return productStore.total
})

const perPage = computed(() => {
    return productStore.perPage
})

const hasPreviousPage = computed(() => {
    return currentPage.value > 1
})

const hasNextPage = computed(() => {
    return currentPage.value < lastPage.value
})

// Search

const search = computed(() => {
    return productStore.search
})

// Pagination information

const firstProductNumber = computed(() => {
    if (total.value === 0) {
        return 0
    }

    return (
        (currentPage.value - 1) *
            perPage.value +
        1
    )
})

const lastProductNumber = computed(() => {
    return Math.min(
        currentPage.value * perPage.value,
        total.value
    )
})

// Load Products

async function loadProducts() {
    try {
        await productStore.fetchProducts(
            productStore.currentPage,
            productStore.search
        )
    } catch (err) {
        console.error(
            'Failed to load products:',
            err
        )
    }
}

// Search Products

async function performSearch() {
    try {
        await productStore.searchProducts(
            searchInput.value.trim()
        )
    } catch (err) {
        console.error(
            'Search products error:',
            err
        )
    }
}

// Clear Search

async function clearSearch() {
    searchInput.value = ''

    try {
        await productStore.clearSearch()
    } catch (err) {
        console.error(
            'Clear search error:',
            err
        )
    }
}

// Go To Page

async function goToPage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return
    }

    try {
        await productStore.goToPage(page)
    } catch (err) {
        console.error(
            'Pagination error:',
            err
        )
    }
}

// Previous Page

async function previousPage() {
    if (!hasPreviousPage.value) {
        return
    }

    try {
        await productStore.previousPage()
    } catch (err) {
        console.error(
            'Previous page error:',
            err
        )
    }
}

// Next Page

async function nextPage() {
    if (!hasNextPage.value) {
        return
    }

    try {
        await productStore.nextPage()
    } catch (err) {
        console.error(
            'Next page error:',
            err
        )
    }
}

// Add Product

function addProduct() {
    router.push({
        name: 'products.create',
    })
}

// View Product

function viewProduct(product) {
    router.push({
        name: 'products.view',
        params: {
            id: product.id,
        },
    })
}

// Edit Product

function editProduct(product) {
    router.push({
        name: 'products.edit',
        params: {
            id: product.id,
        },
    })
}

// Delete Product

function openDeleteModal(product) {
    productToDelete.value = product
    showDeleteModal.value = true
}

function closeDeleteModal() {
    if (deleting.value) {
        return
    }

    showDeleteModal.value = false
    productToDelete.value = null
}

async function confirmDelete() {
    if (!productToDelete.value) {
        return
    }

    deleting.value = true

    try {
        await productStore.deleteProduct(
            productToDelete.value.id
        )

        showDeleteModal.value = false
        productToDelete.value = null
    } catch (err) {
        console.error(
            'Delete product error:',
            err
        )
    } finally {
        deleting.value = false
    }
}

// Bulk Delete

async function bulkDelete(productsToDelete) {
    if (!Array.isArray(productsToDelete)) {
        return
    }

    if (productsToDelete.length === 0) {
        return
    }

    try {
        await productStore.bulkDelete(
            productsToDelete
        )
    } catch (err) {
        console.error(
            'Bulk delete error:',
            err
        )
    }
}

// Bulk Edit

function bulkEdit(productsToEdit) {
    if (!Array.isArray(productsToEdit)) {
        return
    }

    if (productsToEdit.length === 0) {
        return
    }

    if (productsToEdit.length === 1) {
        editProduct(productsToEdit[0])
        return
    }

    router.push({
        name: 'products.bulk-edit',
        query: {
            selected_products: productsToEdit
                .map(product => product.id)
                .join(','),
        },
    })
}

// Refresh

async function refreshProducts() {
    await loadProducts()
}

// Initial Load

onMounted(() => {
    searchInput.value = productStore.search
    loadProducts()
})
</script>

<template>
    <div class="mx-auto w-full max-w-7xl px-3 sm:px-4 lg:px-6">

        <!-- Page Header -->

<div class="mb-6">
    <div
        class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white px-5 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
    >

        <!-- Title -->

        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-900"
                >
                    <svg
                        class="h-5 w-5 text-white"
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
                        class="truncate text-xl font-semibold tracking-tight text-gray-900 sm:text-2xl"
                    >
                        Product Management
                    </h1>
                </div>
            </div>
        </div>

        <!-- Actions -->

        <div
            class="flex w-full items-center gap-2 sm:w-auto"
        >

            <!-- Refresh -->

            <BaseButton
                type="button"
                variant="secondary"
                :disabled="loading"
                class="flex-1 sm:flex-none"
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
                    {{ loading ? 'Loading...' : 'Refresh' }}
                </span>
            </BaseButton>

            <!-- Add Product -->

            <BaseButton
                type="button"
                class="flex-1 sm:flex-none"
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

        </div>

    </div>
</div>

        <!-- Search -->

<BaseCard class="mb-6 overflow-hidden">

    <!-- Search Header -->

    <div
        class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
    >

        <div>
            <div class="flex items-center gap-2">

            </div>
        </div>

        <!-- Result Count -->

        <div
            v-if="!search"
            class="hidden text-xs text-gray-400 sm:block"
        >
            {{ total }} product{{ total === 1 ? '' : 's' }}
        </div>

    </div>

    <!-- Search Form -->

    <form
        class="flex flex-col gap-2 sm:flex-row"
        @submit.prevent="performSearch"
    >

        <!-- Search Input -->

        <div class="relative min-w-0 flex-1">

            <!-- Search Icon -->

            <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
            >
                <svg
                    class="h-5 w-5 text-gray-400 transition"
                    :class="{
                        'text-gray-700': searchInput,
                    }"
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
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-3.5 pl-12 pr-24 text-sm text-gray-900 outline-none transition duration-200 placeholder:text-gray-400 hover:border-gray-300 hover:bg-white focus:border-gray-400 focus:bg-white focus:ring-4 focus:ring-gray-100"
            />

            <!-- Right Side -->

            <div
                class="absolute inset-y-0 right-3 flex items-center gap-2"
            >

                <!-- Clear -->

                <button
                    v-if="searchInput"
                    type="button"
                    class="flex h-7 w-7 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-200 hover:text-gray-700"
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

        </div>

        <!-- Search Button -->

        <BaseButton
            type="submit"
            :disabled="loading"
            class="justify-center px-6 sm:min-w-[110px]"
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

            {{ loading ? 'Searching...' : 'Search' }}

        </BaseButton>

        <!-- Clear Button -->

        <BaseButton
            v-if="search"
            type="button"
            variant="secondary"
            :disabled="loading"
            class="justify-center"
            @click="clearSearch"
        >
            Clear
        </BaseButton>

    </form>

    <!-- Active Search -->

    <div
        v-if="search"
        class="mt-4 flex flex-wrap items-center gap-2"
    >

        <span
            class="text-xs font-medium text-gray-500"
        >
            Showing results for
        </span>

        <span
            class="inline-flex max-w-full items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700"
        >

            <span
                class="h-1.5 w-1.5 shrink-0 rounded-full bg-gray-900"
            ></span>

            <span class="max-w-[220px] truncate">
                "{{ search }}"
            </span>

            <button
                type="button"
                class="ml-0.5 flex h-4 w-4 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-200 hover:text-gray-700"
                @click="clearSearch"
            >
                <svg
                    class="h-3 w-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 6l12 12M18 6L6 18"
                    />
                </svg>
            </button>

        </span>

        <span class="text-xs text-gray-400">
            {{ total }}
            result{{ total === 1 ? '' : 's' }}
        </span>

    </div>

</BaseCard>

        <!-- Error -->

        <BaseCard
            v-if="error"
            class="mb-6 border-red-200 bg-red-50"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >

                <div class="flex gap-3">

                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100"
                    >
                        <svg
                            class="h-5 w-5 text-red-600"
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
                            class="text-sm font-semibold text-red-800"
                        >
                            Something went wrong
                        </h3>

                        <p
                            class="mt-1 text-sm text-red-700"
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

        <!-- Loading -->

        <BaseCard
            v-if="loading && products.length === 0"
            class="py-20"
        >
            <div
                class="flex flex-col items-center justify-center"
            >

                <div
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100"
                >
                    <svg
                        class="h-7 w-7 animate-spin text-gray-700"
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
                    class="mt-5 text-sm font-semibold text-gray-700"
                >
                    Loading products
                </p>

                <p
                    class="mt-1 text-xs text-gray-400"
                >
                    Please wait while we fetch your inventory.
                </p>

            </div>
        </BaseCard>

        <!-- Product List -->

        <div
            v-else
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <ProductList
                :products="products"
                @add-product="addProduct"
                @view-product="viewProduct"
                @edit-product="editProduct"
                @delete-product="openDeleteModal"
                @bulk-delete="bulkDelete"
                @bulk-edit="bulkEdit"
            />
        </div>

        <!-- Pagination -->

        <div
            v-if="total > 0"
            class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
        >

            <div
                class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between"
            >

                <!-- Information -->

                <div
                    class="flex flex-col gap-1"
                >

                    <p
                        class="text-sm font-semibold text-gray-800"
                    >
                        Products {{ firstProductNumber }}–{{ lastProductNumber }}
                    </p>

                    <p
                        class="text-xs text-gray-500"
                    >
                        Showing {{ products.length }} of {{ total }} products
                    </p>

                </div>

                <!-- Controls -->

                <div
                    class="flex flex-wrap items-center gap-2"
                >

                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="
                            !hasPreviousPage ||
                            loading
                        "
                        class="justify-center"
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

                        <span class="hidden sm:inline">
                            Previous
                        </span>
                    </BaseButton>

                    <!-- Page Numbers -->

                    <div
                        class="flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1"
                    >

                        <button
                            v-for="page in lastPage"
                            :key="page"
                            type="button"
                            :disabled="loading"
                            @click="goToPage(page)"
                            class="flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-semibold transition"
                            :class="
                                page === currentPage
                                    ? 'bg-gray-900 text-white shadow-sm'
                                    : 'text-gray-600 hover:bg-white hover:text-gray-900'
                            "
                        >
                            {{ page }}
                        </button>

                    </div>

                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="
                            !hasNextPage ||
                            loading
                        "
                        class="justify-center"
                        @click="nextPage"
                    >
                        <span class="hidden sm:inline">
                            Next
                        </span>

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

        <!-- Delete Confirmation Modal -->

        <BaseModal
            :show="showDeleteModal"
            title="Delete Product"
            size="sm"
            @close="closeDeleteModal"
        >

            <div class="text-center">

                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50"
                >
                    <svg
                        class="h-7 w-7 text-red-600"
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
                    class="mt-5 text-lg font-bold text-gray-900"
                >
                    Delete Product?
                </h3>

                <p
                    class="mx-auto mt-2 max-w-sm text-sm leading-6 text-gray-500"
                >
                    Are you sure you want to delete

                    <span
                        class="font-semibold text-gray-800"
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

                        {{
                            deleting
                                ? 'Deleting...'
                                : 'Delete Product'
                        }}
                    </BaseButton>

                </div>

            </template>

        </BaseModal>

    </div>
</template>
