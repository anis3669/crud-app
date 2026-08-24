<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../../stores/product'

import BaseButton from '../common/BaseButton.vue'
import BaseCard from '../common/BaseCard.vue'

const route = useRoute()
const router = useRouter()
const productStore = useProductStore()

const product = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)

const form = ref({
    name: '',
    description: '',
    price: '',
    quantity: '',
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
        const data = await productStore.fetchProduct(
            route.params.id
        )

        product.value = data

        form.value = {
            name: data.name ?? '',
            description: data.description ?? '',
            price: data.price ?? '',
            quantity: data.quantity ?? '',
        }
    } catch (err) {
        console.error('Failed to load product:', err)

        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        if (err.response?.status === 404) {
            error.value = 'Product not found.'
            return
        }

        error.value =
            err.response?.data?.message ||
            'Failed to load product.'
    } finally {
        loading.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Submit / Update Product
|--------------------------------------------------------------------------
*/

async function submitForm() {
    error.value = null

    // Validate name
    if (!form.value.name.trim()) {
        error.value = 'Please enter a product name.'
        return
    }

    // Validate price
    if (
        form.value.price === '' ||
        Number(form.value.price) < 0
    ) {
        error.value = 'Please enter a valid price.'
        return
    }

    // Validate quantity
    if (
        form.value.quantity === '' ||
        Number(form.value.quantity) < 0
    ) {
        error.value = 'Please enter a valid quantity.'
        return
    }

    saving.value = true

    try {
        await productStore.updateProduct(
            route.params.id,
            {
                name: form.value.name.trim(),
                description: form.value.description.trim(),
                price: Number(form.value.price),
                quantity: Number(form.value.quantity),
            }
        )

        router.push({
            name: 'products.view',
            params: {
                id: route.params.id,
            },
        })
    } catch (err) {
        console.error('Update product error:', err)

        // Unauthorized
        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        // Validation error
        if (err.response?.status === 422) {
            const errors = err.response.data?.errors

            if (errors) {
                error.value = Object.values(errors)
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
            productStore.error ||
            'Failed to update product.'
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
        name: 'products.view',
        params: {
            id: route.params.id,
        },
    })
}

/*
|--------------------------------------------------------------------------
| Initial Load
|--------------------------------------------------------------------------
*/

onMounted(fetchProduct)
</script>

<template>
    <div class="mx-auto max-w-3xl">

        <!-- Loading -->
        <BaseCard
            v-if="loading"
            class="py-16"
        >
            <div class="flex flex-col items-center justify-center">

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

                <p class="mt-4 text-sm text-gray-500">
                    Loading product...
                </p>

            </div>
        </BaseCard>

        <!-- Content -->
        <template v-else>

            <!-- Header -->
            <div class="mb-8">

                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900"
                >
                    Edit Product
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update the product information below.
                </p>

            </div>

            <!-- Error -->
            <BaseCard
                v-if="error"
                class="mb-6 border-red-200 bg-red-50"
            >
                <div
                    class="flex items-center justify-between gap-4"
                >

                    <p
                        class="text-sm font-medium text-red-700"
                    >
                        {{ error }}
                    </p>

                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="fetchProduct"
                    >
                        Try Again
                    </BaseButton>

                </div>
            </BaseCard>

            <!-- Form -->
            <BaseCard v-else>

                <form
                    @submit.prevent="submitForm"
                    class="space-y-6"
                >

                    <!-- Product Name -->
                    <div>

                        <label
                            for="name"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Product Name
                        </label>

                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Enter product name"
                            :disabled="saving"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                        />

                    </div>

                    <!-- Description -->
                    <div>

                        <label
                            for="description"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Description
                        </label>

                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Enter product description"
                            :disabled="saving"
                            class="mt-2 block w-full resize-none rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                        ></textarea>

                    </div>

                    <!-- Price -->
                    <div>

                        <label
                            for="price"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Price
                        </label>

                        <input
                            id="price"
                            v-model="form.price"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            :disabled="saving"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                        />

                    </div>

                    <!-- Quantity -->
                    <div>

                        <label
                            for="quantity"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Quantity
                        </label>

                        <input
                            id="quantity"
                            v-model="form.quantity"
                            type="number"
                            min="0"
                            step="1"
                            placeholder="0"
                            :disabled="saving"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                        />

                    </div>

                    <!-- Actions -->
                    <div
                        class="flex justify-end gap-3 border-t border-gray-100 pt-6"
                    >

                        <!-- Cancel -->
                        <BaseButton
                            type="button"
                            variant="secondary"
                            :disabled="saving"
                            @click="cancel"
                        >
                            Cancel
                        </BaseButton>

                        <!-- Update -->
                        <BaseButton
                            type="submit"
                            :loading="saving"
                        >
                            Update Product
                        </BaseButton>

                    </div>

                </form>

            </BaseCard>

        </template>

    </div>
</template>
