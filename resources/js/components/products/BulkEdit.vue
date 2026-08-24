<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../../stores/product'

const route = useRoute()
const router = useRouter()
const productStore = useProductStore()

const products = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')

// Load selected products
async function fetchProducts() {
    loading.value = true
    error.value = ''

    try {
        let ids = route.query.selected_products

        if (!ids) {
            error.value = 'No products were selected.'
            return
        }

        if (!Array.isArray(ids)) {
            ids = String(ids).split(',')
        }

        ids = ids
            .map(id => Number(id))
            .filter(id => !Number.isNaN(id))

        if (ids.length === 0) {
            error.value = 'No valid products were selected.'
            return
        }

        // Load products through Pinia store
        await productStore.fetchProducts()

        // Get only the selected products
        products.value = productStore.products
            .filter(product =>
                ids.includes(Number(product.id))
            )
            .map(product => ({
                id: product.id,
                name: product.name ?? '',
                description: product.description ?? '',
                price: product.price ?? '',
                quantity: product.quantity ?? '',
            }))

        if (products.value.length === 0) {
            error.value = 'Selected products could not be found.'
        }
    } catch (err) {
        console.error('Failed to load products:', err)

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            'Failed to load selected products.'
    } finally {
        loading.value = false
    }
}

// Save bulk changes
async function saveChanges() {
    error.value = ''

    if (products.value.length === 0) {
        error.value = 'There are no products to update.'
        return
    }

    // Basic validation
    for (const product of products.value) {
        if (!product.name.trim()) {
            error.value = 'Product name cannot be empty.'
            return
        }

        if (
            product.price === '' ||
            Number(product.price) < 0
        ) {
            error.value =
                `Please enter a valid price for "${product.name}".`
            return
        }

        if (
            product.quantity === '' ||
            Number(product.quantity) < 0
        ) {
            error.value =
                `Please enter a valid quantity for "${product.name}".`
            return
        }
    }

    saving.value = true

    try {
        // Prepare data for bulk update
        const updatedProducts = products.value.map(product => ({
            id: product.id,
            name: product.name,
            description: product.description,
            price: Number(product.price),
            quantity: Number(product.quantity),
        }))

        // Use Pinia bulkUpdate action
        await productStore.bulkUpdate(updatedProducts)

        // Go back to products after successful update
        await router.push({
            name: 'products.index',
        })
    } catch (err) {
        console.error('Bulk update failed:', err)

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        if (err.response?.status === 422) {
            const validationErrors =
                err.response.data?.errors

            if (validationErrors) {
                error.value = Object.values(
                    validationErrors
                )
                    .flat()
                    .join(' ')
            } else {
                error.value =
                    err.response.data?.message ||
                    'Validation failed.'
            }

            return
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            'Failed to update selected products.'
    } finally {
        saving.value = false
    }
}

// Cancel
function cancel() {
    router.push({
        name: 'products.index',
    })
}

// Lifecycle
onMounted(() => {
    fetchProducts()
})
</script>

<template>
    <div class="mx-auto max-w-5xl">

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900"
                >
                    Bulk Edit
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update the selected products.
                </p>
            </div>

            <button
                type="button"
                @click="cancel"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Back to Products
            </button>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm"
        >
            <div
                class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900"
            ></div>

            <p class="text-sm text-gray-500">
                Loading selected products...
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

        <!-- Edit Form -->
        <form
            v-else
            @submit.prevent="saveChanges"
            class="space-y-4"
        >

            <!-- Products -->
            <div
                v-for="(product, index) in products"
                :key="product.id"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >

                <!-- Product Header -->
                <div
                    class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4"
                >
                    <div class="flex items-center gap-3">

                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-sm font-semibold text-white"
                        >
                            {{ index + 1 }}
                        </div>

                        <div>
                            <h2
                                class="text-sm font-semibold text-gray-900"
                            >
                                Product #{{ product.id }}
                            </h2>

                            <p
                                class="text-xs text-gray-500"
                            >
                                Edit product information
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Name -->
                <div class="mb-5">
                    <label
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Product Name
                    </label>

                    <input
                        v-model="product.name"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                    />
                </div>

                <!-- Description -->
                <div class="mb-5">
                    <label
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Description
                    </label>

                    <textarea
                        v-model="product.description"
                        rows="3"
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                    ></textarea>
                </div>

                <!-- Price / Quantity -->
                <div
                    class="grid gap-5 sm:grid-cols-2"
                >

                    <!-- Price -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Price
                        </label>

                        <input
                            v-model="product.price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                        />
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Quantity
                        </label>

                        <input
                            v-model="product.quantity"
                            type="number"
                            min="0"
                            step="1"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                        />
                    </div>

                </div>
            </div>

            <!-- Bottom Actions -->
            <div
                class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-6 py-4 shadow-sm"
            >
                <p class="text-sm text-gray-500">
                    {{ products.length }}
                    product{{ products.length !== 1 ? 's' : '' }}
                    selected
                </p>

                <div class="flex gap-3">

                    <button
                        type="button"
                        @click="cancel"
                        :disabled="saving"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="saving"
                        class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            saving
                                ? 'Saving Changes...'
                                : 'Save Changes'
                        }}
                    </button>

                </div>
            </div>

        </form>

    </div>
</template>
