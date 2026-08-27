<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useProductStore } from "../../stores/product";
import { useToastStore } from '../../stores/toast';

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import ImageUpload from "../common/ImageUpload.vue";

const router = useRouter();
const productStore = useProductStore();
const toastStore = useToastStore();
// FORM

const form = ref({
    name: "",
    description: "",
    price: "",
    quantity: "",
    image: null,
});

// STATE

const loading = ref(false);
const error = ref("");

// VALIDATION

function validateForm() {
    if (!form.value.name.trim()) {
        error.value = "Please enter a product name.";
        return false;
    }

    if (form.value.price === "" || Number(form.value.price) < 0) {
        error.value = "Please enter a valid price.";
        return false;
    }

    if (form.value.quantity === "" || Number(form.value.quantity) < 0) {
        error.value = "Please enter a valid quantity.";
        return false;
    }

    return true;
}

// CREATE PRODUCT

async function submitForm() {
    error.value = "";

    if (!validateForm()) {
        return;
    }

    loading.value = true;

    try {
        // CREATE FORMDATA

        const productData = new FormData();

        productData.append("name", form.value.name.trim());

        productData.append("description", form.value.description?.trim() || "");

        productData.append("price", String(Number(form.value.price)));

        productData.append("quantity", String(Number(form.value.quantity)));

        // IMAGE

        if (form.value.image instanceof File) {
            productData.append("image", form.value.image);
        }

        // SEND TO PINIA

        await productStore.createProduct(productData);
        toastStore.success('Product created successfully.')

        // SUCCESS

        await router.push({
            name: "products.index",
        });
    } catch (err) {
        console.error("Create product error:", err);
        toastStore.error('Failed to create product.')

        // UNAUTHORIZED

        if (err.response?.status === 401) {
            router.push({
                name: "login",
            });

            return;
        }

        // VALIDATION ERROR


        if (err.response?.status === 422) {
            const validationErrors = err.response.data?.errors;

            if (validationErrors) {
                error.value = Object.values(validationErrors).flat().join(" ");
            } else {
                error.value =
                    err.response.data?.message || "Validation failed.";
            }

            return;
        }
// other errors

        error.value =
            err.response?.data?.message ||
            err.message ||
            productStore.error ||
            "Failed to create product.";
    } finally {
        loading.value = false;
    }
}

// cancel

function cancel() {
    router.push({
        name: "products.index",
    });
}
</script>

<template>
    <div class="mx-auto max-w-3xl">
       <!-- header -->

        <div class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Add Product
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Create a new product for your inventory.
            </p>
        </div>

       <!-- error -->

        <BaseCard v-if="error" class="mb-6 border-red-200 bg-red-50">
            <p class="text-sm font-medium text-red-700">
                {{ error }}
            </p>
        </BaseCard>

      <!-- form card -->

        <BaseCard>
            <form class="space-y-6" @submit.prevent="submitForm">
                <!-- product name -->

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
                        :disabled="loading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                    />
                </div>

                <!-- description -->

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
                        :disabled="loading"
                        class="mt-2 block w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                    ></textarea>
                </div>

                <!-- price -->
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
                        :disabled="loading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                    />
                </div>
                <!-- quantity -->
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
                        :disabled="loading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100"
                    />
                </div>

                <!-- product image -->

                <div>
                    <ImageUpload v-model="form.image" label="Product Image" />
                </div>

                <!-- actions -->

                <div
                    class="flex justify-end gap-3 border-t border-gray-100 pt-6"
                >
                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="loading"
                        @click="cancel"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton type="submit" :loading="loading">
                        Create Product
                    </BaseButton>
                </div>
            </form>
        </BaseCard>
    </div>
</template>
