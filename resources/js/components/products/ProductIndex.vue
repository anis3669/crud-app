<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

import ProductList from './ProductList.vue'

const router = useRouter()

const products = ref([])
const loading = ref(true)
const error = ref(null)

/*
|--------------------------------------------------------------------------
| Fetch Products
|--------------------------------------------------------------------------
*/

async function fetchProducts() {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get('/api/products')

        products.value = Array.isArray(response.data?.data)
            ? response.data.data
            : Array.isArray(response.data)
                ? response.data
                : []
    } catch (err) {
        console.error('Failed to fetch products:', err)

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        error.value = 'Failed to load products.'
    } finally {
        loading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function addProduct() {
    router.push({
        name: 'products.create',
    })
}

function viewProduct(product) {
    if (!product?.id) {
        return
    }

    router.push({
        name: 'products.view',
        params: {
            id: product.id,
        },
    })
}

function editProduct(product) {
    if (!product?.id) {
        return
    }

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

async function deleteProduct(product) {
    if (!product?.id) {
        return
    }

    try {
        await axios.delete(
            `/api/products/${product.id}`
        )

        products.value = products.value.filter(
            item => item.id !== product.id
        )
    } catch (err) {
        console.error('Failed to delete product:', err)

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        alert('Failed to delete product.')
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
        await Promise.all(
            productsToDelete.map(product =>
                axios.delete(
                    `/api/products/${product.id}`
                )
            )
        )

        const deletedIds = productsToDelete.map(
            product => product.id
        )

        products.value = products.value.filter(
            product => !deletedIds.includes(product.id)
        )
    } catch (err) {
        console.error(
            'Failed to bulk delete products:',
            err
        )

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        alert('Failed to delete selected products.')

        // Refresh in case some requests succeeded
        await fetchProducts()
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

    const ids = productsToEdit
        .filter(product => product?.id)
        .map(product => product.id)

    if (ids.length === 0) {
        return
    }

    router.push({
        name: 'products.bulk-edit',
        query: {
            selected_products: ids,
        },
    })
}

/*
|--------------------------------------------------------------------------
| Lifecycle
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchProducts()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Navbar -->
        <Navbar />

        <!-- Main Content -->
        <main
            class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
        >

            <!-- Loading -->
            <div
                v-if="loading"
                class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm"
            >
                <div
                    class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900"
                ></div>

                <p class="text-sm text-gray-500">
                    Loading products...
                </p>
            </div>

            <!-- Error -->
            <div
                v-else-if="error"
                class="rounded-xl border border-red-200 bg-red-50 px-6 py-8 text-center"
            >
                <div
                    class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-red-100"
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
                            stroke-width="2"
                            d="M12 9v4m0 4h.01M10.29 3.86l-8.09 14a2 2 0 001.73 3h16.14a2 2 0 001.73-3l-8.09-14a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>

                <p class="mt-3 text-sm font-medium text-red-700">
                    {{ error }}
                </p>

                <button
                    type="button"
                    @click="fetchProducts"
                    class="mt-4 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                >
                    Try Again
                </button>
            </div>

            <!-- Product List -->
            <ProductList
                v-else
                :products="products"
                @add-product="addProduct"
                @view-product="viewProduct"
                @edit-product="editProduct"
                @delete-product="deleteProduct"
                @bulk-delete="bulkDelete"
                @bulk-edit="bulkEdit"
            />

        </main>

    </div>
</template>