<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProductStore } from '../../stores/product'

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

        // Update successful
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

        // Other errors
        error.value =
            err.response?.data?.message ||
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
        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm"
        >
            <p class="text-sm text-gray-500">
                Loading product...
            </p>
        </div>

        <!-- Content -->
        <template v-else>

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Edit Product
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update the product information below.
                </p>
            </div>

            <!-- Error -->
            <div
                v-if="error"
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ error }}
            </div>

            <!-- Form Card -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >

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
                        <button
                            type="button"
                            @click="cancel"
                            :disabled="saving"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Cancel
                        </button>

                        <!-- Update -->
                        <button
                            type="submit"
                            :disabled="saving"
                            class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ saving ? 'Updating...' : 'Update Product' }}
                        </button>

                    </div>

                </form>
            </div>

        </template>
    </div>
</template>
