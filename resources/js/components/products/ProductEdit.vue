<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

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

async function fetchProduct() {
    loading.value = true
    error.value = null

    try {
        const response = await axios.get(
            `/api/products/${route.params.id}`
        )

        product.value = response.data.data ?? response.data

        form.value = {
            name: product.value.name ?? '',
            description: product.value.description ?? '',
            price: product.value.price ?? '',
            quantity: product.value.quantity ?? '',
        }
    } catch (err) {
        console.error(err)
        error.value = 'Failed to load product.'
    } finally {
        loading.value = false
    }
}

async function submitForm() {
    error.value = null

    if (!form.value.name.trim()) {
        error.value = 'Please enter a product name.'
        return
    }

    if (form.value.price === '') {
        error.value = 'Please enter a price.'
        return
    }

    if (form.value.quantity === '') {
        error.value = 'Please enter the quantity.'
        return
    }

    saving.value = true

    try {
        await axios.put(
            `/api/products/${route.params.id}`,
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
        console.error(err)

        error.value =
            err.response?.data?.message ||
            'Failed to update product.'
    } finally {
        saving.value = false
    }
}

function cancel() {
    router.push({
        name: 'products.view',
        params: {
            id: route.params.id,
        },
    })
}

onMounted(fetchProduct)
</script>

<template>
    <div class="mx-auto max-w-3xl">

        <div
            v-if="loading"
            class="rounded-xl border border-gray-200 bg-white px-6 py-16 text-center shadow-sm"
        >
            <p class="text-sm text-gray-500">
                Loading product...
            </p>
        </div>

        <template v-else>

            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Edit Product
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update the product information below.
                </p>
            </div>

            <div
                v-if="error"
                class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ error }}
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <form
                    @submit.prevent="submitForm"
                    class="space-y-6"
                >

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">
                            Product Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">
                            Description
                        </label>

                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">
                            Price
                        </label>

                        <input
                            v-model="form.price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700">
                            Quantity
                        </label>

                        <input
                            v-model="form.quantity"
                            type="number"
                            min="0"
                            class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                        />
                    </div>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">

                        <button
                            type="button"
                            @click="cancel"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="saving"
                            class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            {{ saving ? 'Updating...' : 'Update Product' }}
                        </button>

                    </div>

                </form>
            </div>

        </template>
    </div>
</template>