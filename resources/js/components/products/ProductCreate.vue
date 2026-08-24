<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '../../stores/product'

import BaseButton from '../common/BaseButton.vue'
import BaseCard from '../common/BaseCard.vue'

const router = useRouter()
const productStore = useProductStore()

const form = ref({
    name: '',
    description: '',
    price: '',
    quantity: '',
})

const loading = ref(false)
const error = ref('')

async function submitForm() {
    error.value = ''

    // Validate product name
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

    loading.value = true

    try {
        await productStore.createProduct({
            name: form.value.name.trim(),
            description: form.value.description.trim(),
            price: Number(form.value.price),
            quantity: Number(form.value.quantity),
        })

        // Product successfully created
        router.push({
            name: 'products.index',
        })
    } catch (err) {
        console.error('Create product error:', err)

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

        // Unauthorized
        if (err.response?.status === 401) {
            router.push({
                name: 'login',
            })

            return
        }

        // Other errors
        error.value =
            err.response?.data?.message ||
            productStore.error ||
            'Failed to create product.'
    } finally {
        loading.value = false
    }
}

function cancel() {
    router.push({
        name: 'products.index',
    })
}
</script>

<template>
    <div class="mx-auto max-w-3xl">

        <!-- Header -->
        <div class="mb-8">
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900"
            >
                Add Product
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Create a new product for your inventory.
            </p>
        </div>

        <!-- Error -->
        <div
            v-if="error"
            class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <!-- Card -->
        <BaseCard>

            <!-- Form -->
            <form
                @submit.prevent="submitForm"
            >

                <!-- Name -->
                <div class="mb-5">
                    <label
                        for="name"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Product Name
                    </label>

                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Enter product name"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                    />
                </div>

                <!-- Description -->
                <div class="mb-5">
                    <label
                        for="description"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Description
                    </label>

                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Enter product description"
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                    ></textarea>
                </div>

                <!-- Price -->
                <div class="mb-5">
                    <label
                        for="price"
                        class="mb-2 block text-sm font-semibold text-gray-700"
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
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                    />
                </div>

                <!-- Quantity -->
                <div class="mb-8">
                    <label
                        for="quantity"
                        class="mb-2 block text-sm font-semibold text-gray-700"
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
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                    />
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">

                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="loading"
                        @click="cancel"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        type="submit"
                        :loading="loading"
                    >
                        Create Product
                    </BaseButton>

                </div>

            </form>

        </BaseCard>

    </div>
</template>
