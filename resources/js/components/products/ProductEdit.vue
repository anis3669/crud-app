<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import { useProductStore } from "../../stores/product";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import ImageUpload from "../common/ImageUpload.vue";

const route = useRoute();
const router = useRouter();

const productStore = useProductStore();
const toastStore = useToastStore();

const loading = ref(true);
const saving = ref(false);
const error = ref("");

const form = ref({
    name: "",
    description: "",
    price: "",
    quantity: "",
    image: null,
});

const existingImage = ref(null);
const removeImage = ref(false);

// Image URL

function getImageUrl(image) {
    if (!image) {
        return null;
    }

    if (
        image.startsWith("http://") ||
        image.startsWith("https://") ||
        image.startsWith("/storage/")
    ) {
        return image;
    }

    return `/storage/${image}`;
}

// Load product

async function fetchProduct() {
    loading.value = true;
    error.value = "";

    try {
        const product = await productStore.fetchProduct(
            route.params.id,
        );

        if (!product) {
            error.value = "Product not found.";
            return;
        }

        form.value = {
            name: product.name ?? "",
            description: product.description ?? "",
            price: product.price ?? "",
            quantity: product.quantity ?? "",
            image: null,
        };

        existingImage.value = getImageUrl(product.image);
        removeImage.value = false;
    } catch (err) {
        console.error("Failed to load product:", err);

        if (err.response?.status === 401) {
            router.push({
                name: "login",
            });

            return;
        }

        if (err.response?.status === 404) {
            error.value = "Product not found.";
            return;
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to load product.";
    } finally {
        loading.value = false;
    }
}

// Remove existing image

function handleRemoveExistingImage() {
    removeImage.value = true;
}

// Submit form

async function submitForm() {
    error.value = "";

    if (!form.value.name.trim()) {
        error.value = "Please enter a product name.";
        return;
    }

    if (
        form.value.price === "" ||
        Number(form.value.price) < 0
    ) {
        error.value = "Please enter a valid price.";
        return;
    }

    if (
        form.value.quantity === "" ||
        Number(form.value.quantity) < 0
    ) {
        error.value = "Please enter a valid quantity.";
        return;
    }

    saving.value = true;

    try {
        const data = new FormData();

        data.append(
            "name",
            form.value.name.trim(),
        );

        data.append(
            "description",
            form.value.description?.trim() || "",
        );

        data.append(
            "price",
            String(Number(form.value.price)),
        );

        data.append(
            "quantity",
            String(Number(form.value.quantity)),
        );

        data.append(
            "remove_image",
            removeImage.value ? "1" : "0",
        );

        if (form.value.image instanceof File) {
            data.append(
                "image",
                form.value.image,
            );
        }

        data.append("_method", "PUT");

        await productStore.updateProduct(
            route.params.id,
            data,
        );

        toastStore.success(
            "Product updated successfully.",
        );

        await router.push({
            name: "products.view",
            params: {
                id: route.params.id,
            },
        });
    } catch (err) {
        console.error("Failed to update product:", err);

        if (err.response?.status === 401) {
            router.push({
                name: "login",
            });

            return;
        }

        if (err.response?.status === 422) {
            const validationErrors =
                err.response.data?.errors;

            if (validationErrors) {
                error.value = Object.values(
                    validationErrors,
                )
                    .flat()
                    .join(" ");
            } else {
                error.value =
                    err.response.data?.message ||
                    "Validation failed.";
            }

            return;
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            productStore.error ||
            "Failed to update product.";
    } finally {
        saving.value = false;
    }
}

// Cancel

function cancel() {
    router.push({
        name: "products.view",
        params: {
            id: route.params.id,
        },
    });
}

// Load

onMounted(fetchProduct);
</script>

<template>
    <div class="mx-auto w-full max-w-3xl">
        <!-- Loading -->

        <BaseCard
            v-if="loading"
            class="py-16"
        >
            <div
                class="flex flex-col items-center justify-center"
            >
                <div
                    class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900 dark:border-gray-700 dark:border-t-white"
                ></div>

                <p
                    class="mt-4 text-sm text-gray-500 dark:text-gray-400"
                >
                    Loading product...
                </p>
            </div>
        </BaseCard>

        <template v-else>
            <!-- Header -->

            <div class="mb-8">
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Edit Product
                </h1>

                <p
                    class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    Update the product information below.
                </p>
            </div>

            <!-- Error -->

            <BaseCard
                v-if="error"
                class="mb-6 border-red-200 bg-red-50 dark:border-red-900/60 dark:bg-red-950/30"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div
                        class="flex items-start gap-3"
                    >
                        <div
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600 dark:bg-red-900/40 dark:text-red-400"
                        >
                            !
                        </div>

                        <p
                            class="text-sm font-medium text-red-700 dark:text-red-400"
                        >
                            {{ error }}
                        </p>
                    </div>

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
                    class="space-y-6"
                    @submit.prevent="submitForm"
                >
                    <!-- Product name -->

                    <div>
                        <label
                            for="name"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Product Name
                        </label>

                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Enter product name"
                            :disabled="saving"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white dark:disabled:bg-gray-700"
                        />
                    </div>

                    <!-- Description -->

                    <div>
                        <label
                            for="description"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Description
                        </label>

                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Enter product description"
                            :disabled="saving"
                            class="mt-2 block w-full resize-none rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white dark:disabled:bg-gray-700"
                        ></textarea>
                    </div>

                    <!-- Price -->

                    <div>
                        <label
                            for="price"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
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
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white dark:disabled:bg-gray-700"
                        />
                    </div>

                    <!-- Quantity -->

                    <div>
                        <label
                            for="quantity"
                            class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
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
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white dark:disabled:bg-gray-700"
                        />
                    </div>

                    <!-- Image -->

                    <div>
                        <ImageUpload
                            v-model="form.image"
                            :existing-image="existingImage"
                            label="Product Image"
                            @remove-existing="handleRemoveExistingImage"
                        />
                    </div>

                    <!-- Actions -->

                    <div
                        class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 dark:border-gray-700 sm:flex-row sm:justify-end"
                    >
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
                            Update Product
                        </BaseButton>
                    </div>
                </form>
            </BaseCard>
        </template>
    </div>
</template>
