<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../../stores/product'

const route = useRoute()
const router = useRouter()

const productStore = useProductStore()

const product = ref(null)
const loading = ref(true)
const error = ref(null)

/*
|--------------------------------------------------------------------------
| Stock Status
|--------------------------------------------------------------------------
*/

const stockStatus = computed(() => {
    if (!product.value) {
        return null
    }

    const quantity = Number(product.value.quantity)

    if (quantity === 0) {
        return {
            label: 'Out of stock',
            wrapper: 'bg-red-50 text-red-700',
            dot: 'bg-red-500',
        }
    }

    if (quantity <= 5) {
        return {
            label: `${quantity} left`,
            wrapper: 'bg-yellow-50 text-yellow-700',
            dot: 'bg-yellow-500',
        }
    }

    return {
        label: `${quantity} in stock`,
        wrapper: 'bg-green-50 text-green-700',
        dot: 'bg-green-500',
    }
})

/*
|--------------------------------------------------------------------------
| Fetch Product
|--------------------------------------------------------------------------
*/

async function fetchProduct() {
    loading.value = true
    error.value = null

    try {
        product.value = await productStore.fetchProduct(
            route.params.id
        )
    } catch (err) {
        console.error('Failed to load product:', err)

        if (err.response?.status === 404) {
            error.value = 'Product not found.'
        } else if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        } else {
            error.value = 'Failed to load product.'
        }
    } finally {
        loading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Back to Products
|--------------------------------------------------------------------------
*/

function back() {
    router.push({
        name: 'products.index',
    })
}

/*
|--------------------------------------------------------------------------
| Edit Product
|--------------------------------------------------------------------------
*/

function editProduct() {
    if (!product.value) {
        return
    }

    router.push({
        name: 'products.edit',
        params: {
            id: product.value.id,
        },
    })
}

/*
|--------------------------------------------------------------------------
| Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchProduct()
})
</script>

<template>
    <div class="mx-auto max-w-4xl">

        <!-- Loading -->
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm"
        >
            <p class="text-sm text-gray-500">
                Loading product...
            </p>
        </div>

        <!-- Error -->
        <div
            v-else-if="error"
            class="rounded-xl border border-red-200 bg-red-50 px-6 py-8 text-center"
        >
            <p class="text-sm font-medium text-red-700">
                {{ error }}
            </p>

            <button
                type="button"
                @click="back"
                class="mt-4 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
            >
                Back to Products
            </button>
        </div>

        <!-- Product -->
        <template v-else-if="product">

            <!-- Back -->
            <button
                type="button"
                @click="back"
                class="mb-6 inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
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

                Back to Products
            </button>

            <!-- Header -->
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="flex items-center gap-3">

                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-lg font-bold text-gray-700"
                    >
                        {{ product.name?.charAt(0)?.toUpperCase() }}
                    </div>

                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-gray-900"
                        >
                            {{ product.name }}
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            Product #{{ product.id }}
                        </p>
                    </div>

                </div>

                <!-- Edit -->
                <button
                    type="button"
                    @click="editProduct"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800"
                >
                    Edit Product
                </button>
            </div>

            <!-- Product Card -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >

                <!-- Description -->
                <div
                    class="border-b border-gray-100 px-6 py-6"
                >
                    <h2
                        class="text-sm font-semibold text-gray-900"
                    >
                        Description
                    </h2>

                    <p
                        class="mt-2 text-sm leading-6 text-gray-500"
                    >
                        {{
                            product.description ||
                            'No description available.'
                        }}
                    </p>
                </div>

                <!-- Product Information -->
                <div
                    class="grid gap-px bg-gray-100 sm:grid-cols-2"
                >

                    <!-- Price -->
                    <div class="bg-white px-6 py-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            Price
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900"
                        >
                            Rs.
                            {{
                                Number(product.price).toLocaleString(
                                    'en-US',
                                    {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    }
                                )
                            }}
                        </p>
                    </div>

                    <!-- Quantity -->
                    <div class="bg-white px-6 py-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            Quantity
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900"
                        >
                            {{ product.quantity }}
                        </p>
                    </div>

                    <!-- Stock Status -->
                    <div
                        class="bg-white px-6 py-6 sm:col-span-2"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            Stock Status
                        </p>

                        <span
                            v-if="stockStatus"
                            :class="stockStatus.wrapper"
                            class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                        >
                            <span
                                :class="stockStatus.dot"
                                class="h-1.5 w-1.5 rounded-full"
                            ></span>

                            {{ stockStatus.label }}
                        </span>
                    </div>

                </div>
            </div>

        </template>

    </div>
</template>
