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

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const showDeleteModal = ref(false)
const productToDelete = ref(null)
const deleting = ref(false)

/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
*/

const products = computed(() => {
    return Array.isArray(productStore.products)
        ? productStore.products
        : []
})

/*
|--------------------------------------------------------------------------
| Loading
|--------------------------------------------------------------------------
*/

const loading = computed(() => {
    return productStore.loading
})

/*
|--------------------------------------------------------------------------
| Error
|--------------------------------------------------------------------------
*/

const error = computed(() => {
    return productStore.error
})

/*
|--------------------------------------------------------------------------
| Load Products
|--------------------------------------------------------------------------
*/

async function loadProducts() {
    try {
        await productStore.fetchProducts()
    } catch (err) {
        console.error(
            'Failed to load products:',
            err
        )
    }
}

/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

function addProduct() {
    router.push({
        name: 'products.create',
    })
}

/*
|--------------------------------------------------------------------------
| View Product
|--------------------------------------------------------------------------
*/

function viewProduct(product) {
    router.push({
        name: 'products.view',
        params: {
            id: product.id,
        },
    })
}

/*
|--------------------------------------------------------------------------
| Edit Product
|--------------------------------------------------------------------------
*/

function editProduct(product) {
    router.push({
        name: 'products.edit',
        params: {
            id: product.id,
        },
    })
}

/*
|--------------------------------------------------------------------------
| Delete Product
|--------------------------------------------------------------------------
*/

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

        await productStore.fetchProducts()

    } catch (err) {
        console.error(
            'Delete product error:',
            err
        )
    } finally {
        deleting.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Bulk Delete
|--------------------------------------------------------------------------
*/

async function bulkDelete(productsToDelete) {
    if (!Array.isArray(productsToDelete)) {
        return
    }

    if (productsToDelete.length === 0) {
        return
    }

    try {
        for (const product of productsToDelete) {
            await productStore.deleteProduct(
                product.id
            )
        }

        await productStore.fetchProducts()

    } catch (err) {
        console.error(
            'Bulk delete error:',
            err
        )
    }
}

/*
|--------------------------------------------------------------------------
| Bulk Edit
|--------------------------------------------------------------------------
*/

function bulkEdit(productsToEdit) {
    if (!Array.isArray(productsToEdit)) {
        return
    }

    if (productsToEdit.length === 0) {
        return
    }

    /*
    |--------------------------------------------------------------------------
    | One product
    |--------------------------------------------------------------------------
    |
    | If only one product is selected, use the normal edit page.
    |
    */

    if (productsToEdit.length === 1) {
        editProduct(productsToEdit[0])
        return
    }

    /*
    |--------------------------------------------------------------------------
    | Multiple products
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | BulkEdit.vue expects "selected_products".
    |
    */

    router.push({
        name: 'products.bulk-edit',
        query: {
            selected_products: productsToEdit
                .map(product => product.id)
                .join(','),
        },
    })
}

/*
|--------------------------------------------------------------------------
| Refresh
|--------------------------------------------------------------------------
*/

async function refreshProducts() {
    await loadProducts()
}

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    loadProducts()
})
</script>

<template>
    <div class="mx-auto max-w-7xl">

        <!-- Page Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900"
                >
                    Product Management
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your inventory and products.
                </p>
            </div>

            <div class="flex items-center gap-3">

                <!-- Refresh -->
                <BaseButton
                    type="button"
                    variant="secondary"
                    :disabled="loading"
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

                    {{ loading ? 'Loading...' : 'Refresh' }}
                </BaseButton>

                <!-- Add Product -->
                <BaseButton
                    type="button"
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

                    Add Product
                </BaseButton>

            </div>
        </div>

        <!-- Error -->
        <BaseCard
            v-if="error"
            class="mb-6 border-red-200 bg-red-50"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
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
            class="py-16"
        >
            <div
                class="flex flex-col items-center justify-center"
            >
                <svg
                    class="h-8 w-8 animate-spin text-gray-500"
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

                <p
                    class="mt-4 text-sm text-gray-500"
                >
                    Loading products...
                </p>
            </div>
        </BaseCard>

        <!-- Product List -->
        <ProductList
            v-else
            :products="products"
            @add-product="addProduct"
            @view-product="viewProduct"
            @edit-product="editProduct"
            @delete-product="openDeleteModal"
            @bulk-delete="bulkDelete"
            @bulk-edit="bulkEdit"
        />

        <!-- Delete Confirmation Modal -->
        <BaseModal
            :show="showDeleteModal"
            title="Delete Product"
            size="sm"
            @close="closeDeleteModal"
        >
            <!-- Modal Body -->
            <div class="text-center">

                <!-- Warning Icon -->
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100"
                >
                    <svg
                        class="h-6 w-6 text-red-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>

                <h3
                    class="mt-4 text-lg font-semibold text-gray-900"
                >
                    Delete Product?
                </h3>

                <p
                    class="mt-2 text-sm leading-6 text-gray-500"
                >
                    Are you sure you want to delete

                    <span
                        class="font-semibold text-gray-700"
                    >
                        {{ productToDelete?.name }}
                    </span>

                    ?

                    This action cannot be undone.
                </p>

            </div>

            <!-- Modal Footer -->
            <template #footer>

                <div
                    class="flex justify-end gap-3"
                >
                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="deleting"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="danger"
                        :disabled="deleting"
                        @click="confirmDelete"
                    >
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
