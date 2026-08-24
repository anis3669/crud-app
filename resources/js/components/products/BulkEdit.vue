<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useProductStore } from '../../stores/product'

import BaseButton from '../common/BaseButton.vue'
import BaseCard from '../common/BaseCard.vue'

const route = useRoute()
const router = useRouter()
const productStore = useProductStore()

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const products = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')

/*
|--------------------------------------------------------------------------
| Load Selected Products
|--------------------------------------------------------------------------
*/

async function fetchProducts() {
    loading.value = true
    error.value = ''

    try {
        /*
        |--------------------------------------------------------------------------
        | Get selected product IDs
        |--------------------------------------------------------------------------
        |
        | ProductIndex.vue sends:
        |
        | ?selected_products=1,2,3
        |
        */

        let ids = route.query.selected_products

        /*
        |--------------------------------------------------------------------------
        | Check selection
        |--------------------------------------------------------------------------
        */

        if (!ids) {
            error.value = 'No products were selected.'
            return
        }

        /*
        |--------------------------------------------------------------------------
        | Convert query parameter to array
        |--------------------------------------------------------------------------
        */

        if (!Array.isArray(ids)) {
            ids = String(ids).split(',')
        }

        /*
        |--------------------------------------------------------------------------
        | Convert IDs to numbers
        |--------------------------------------------------------------------------
        */

        ids = ids
            .map(id => Number(id))
            .filter(id => !Number.isNaN(id))

        if (ids.length === 0) {
            error.value =
                'No valid products were selected.'
            return
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch all products through Pinia
        |--------------------------------------------------------------------------
        */

        await productStore.fetchProducts()

        /*
        |--------------------------------------------------------------------------
        | Select only requested products
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Check result
        |--------------------------------------------------------------------------
        */

        if (products.value.length === 0) {
            error.value =
                'Selected products could not be found.'
        }

    } catch (err) {
        console.error(
            'Failed to load products:',
            err
        )

        /*
        |--------------------------------------------------------------------------
        | Unauthorized
        |--------------------------------------------------------------------------
        */

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        /*
        |--------------------------------------------------------------------------
        | Other Error
        |--------------------------------------------------------------------------
        */

        error.value =
            err.response?.data?.message ||
            err.message ||
            productStore.error ||
            'Failed to load selected products.'

    } finally {
        loading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Save Changes
|--------------------------------------------------------------------------
*/

async function saveChanges() {
    error.value = ''

    /*
    |--------------------------------------------------------------------------
    | Check Products
    |--------------------------------------------------------------------------
    */

    if (products.value.length === 0) {
        error.value =
            'There are no products to update.'
        return
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    for (const product of products.value) {

        /*
        |----------------------------------------------------------------------
        | Name
        |----------------------------------------------------------------------
        */

        if (!product.name.trim()) {
            error.value =
                'Product name cannot be empty.'
            return
        }

        /*
        |----------------------------------------------------------------------
        | Price
        |----------------------------------------------------------------------
        */

        if (
            product.price === '' ||
            Number(product.price) < 0
        ) {
            error.value =
                `Please enter a valid price for "${product.name}".`

            return
        }

        /*
        |----------------------------------------------------------------------
        | Quantity
        |----------------------------------------------------------------------
        */

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
        /*
        |--------------------------------------------------------------------------
        | Prepare Data
        |--------------------------------------------------------------------------
        */

        const updatedProducts =
            products.value.map(product => ({
                id: product.id,
                name: product.name.trim(),
                description:
                    product.description?.trim() || '',
                price: Number(product.price),
                quantity: Number(product.quantity),
            }))

        /*
        |--------------------------------------------------------------------------
        | Update Through Pinia
        |--------------------------------------------------------------------------
        */

        await productStore.bulkUpdate(
            updatedProducts
        )

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        await router.push({
            name: 'products.index',
        })

    } catch (err) {
        console.error(
            'Bulk update failed:',
            err
        )

        /*
        |--------------------------------------------------------------------------
        | Unauthorized
        |--------------------------------------------------------------------------
        */

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Error
        |--------------------------------------------------------------------------
        */

        if (err.response?.status === 422) {
            const validationErrors =
                err.response.data?.errors

            if (validationErrors) {
                error.value =
                    Object.values(
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

        /*
        |--------------------------------------------------------------------------
        | Other Error
        |--------------------------------------------------------------------------
        */

        error.value =
            err.response?.data?.message ||
            err.message ||
            productStore.error ||
            'Failed to update selected products.'

    } finally {
        saving.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

function cancel() {
    router.push({
        name: 'products.index',
    })
}

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(() => {
    fetchProducts()
})
</script>

<template>
    <div class="mx-auto max-w-5xl">

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
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

            <!-- Back -->
            <BaseButton
                type="button"
                variant="secondary"
                :disabled="saving"
                @click="cancel"
            >
                Back to Products
            </BaseButton>
        </div>

        <!-- Loading -->
        <BaseCard
            v-if="loading"
            class="py-16"
        >
            <div
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900"
                ></div>

                <p
                    class="mt-4 text-sm text-gray-500"
                >
                    Loading selected products...
                </p>
            </div>
        </BaseCard>

        <!-- Error -->
        <BaseCard
            v-else-if="error"
            class="border-red-200 bg-red-50"
        >
            <div class="text-center">

                <!-- Error Icon -->
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
                            d="M12 9v4m0 4h.01M10.29 3.86l-8.09 14a2 2 0 001.73 3h16.14a2 2 0 001.73 3l-8.09-14a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>

                <p
                    class="mt-3 text-sm font-medium text-red-700"
                >
                    {{ error }}
                </p>

                <BaseButton
                    type="button"
                    class="mt-4"
                    @click="fetchProducts"
                >
                    Try Again
                </BaseButton>

            </div>
        </BaseCard>

        <!-- Edit Form -->
        <form
            v-else
            @submit.prevent="saveChanges"
            class="space-y-4"
        >

            <!-- Products -->
            <BaseCard
                v-for="(product, index) in products"
                :key="product.id"
                class="p-6"
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
                        :for="`name-${product.id}`"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Product Name
                    </label>

                    <input
                        :id="`name-${product.id}`"
                        v-model="product.name"
                        type="text"
                        :disabled="saving"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                    />
                </div>

                <!-- Description -->
                <div class="mb-5">
                    <label
                        :for="`description-${product.id}`"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Description
                    </label>

                    <textarea
                        :id="`description-${product.id}`"
                        v-model="product.description"
                        rows="3"
                        :disabled="saving"
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                    ></textarea>
                </div>

                <!-- Price / Quantity -->
                <div
                    class="grid gap-5 sm:grid-cols-2"
                >

                    <!-- Price -->
                    <div>
                        <label
                            :for="`price-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Price
                        </label>

                        <input
                            :id="`price-${product.id}`"
                            v-model="product.price"
                            type="number"
                            min="0"
                            step="0.01"
                            :disabled="saving"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                        />
                    </div>

                    <!-- Quantity -->
                    <div>
                        <label
                            :for="`quantity-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Quantity
                        </label>

                        <input
                            :id="`quantity-${product.id}`"
                            v-model="product.quantity"
                            type="number"
                            min="0"
                            step="1"
                            :disabled="saving"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                        />
                    </div>

                </div>

            </BaseCard>

            <!-- Bottom Actions -->
            <BaseCard
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >

                <p class="text-sm text-gray-500">
                    {{ products.length }}
                    product{{ products.length !== 1 ? 's' : '' }}
                    selected
                </p>

                <div class="flex justify-end gap-3">

                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="saving"
                        @click="cancel"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        type="submit"
                        :loading="saving"
                    >
                        Save Changes
                    </BaseButton>

                </div>

            </BaseCard>

        </form>

    </div>
</template>
