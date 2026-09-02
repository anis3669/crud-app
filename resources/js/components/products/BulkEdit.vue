<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";

import { useProductStore } from "../../stores/product";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import ImageUpload from "../common/ImageUpload.vue";

const route = useRoute();
const router = useRouter();

const productStore = useProductStore();
const toastStore = useToastStore();

const products = ref([]);

const categories = ref([]);
const suppliers = ref([]);

const loading = ref(true);
const optionsLoading = ref(true);
const saving = ref(false);
const error = ref("");

// Extract collection from API response

function extractCollection(data, key) {
    const payload = data?.[key] ?? data?.data ?? data;

    if (Array.isArray(payload)) {
        return payload;
    }

    if (Array.isArray(payload?.data)) {
        return payload.data;
    }

    return [];
}

// Image URL

function imageUrl(image) {
    if (!image) {
        return null;
    }

    const value = String(image);

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("/storage/")
    ) {
        return value;
    }

    return `/storage/${value}`;
}

// Load categories and suppliers

async function fetchOptions() {
    optionsLoading.value = true;

    try {
        const [categoryResponse, supplierResponse] = await Promise.all([
            axios.get("/api/categories"),
            axios.get("/api/suppliers"),
        ]);

        categories.value = extractCollection(
            categoryResponse.data,
            "categories",
        );

        suppliers.value = extractCollection(supplierResponse.data, "suppliers");

        categories.value.sort((a, b) =>
            String(a.name || "").localeCompare(String(b.name || "")),
        );

        suppliers.value.sort((a, b) =>
            String(a.name || "").localeCompare(String(b.name || "")),
        );
    } catch (err) {
        console.error("Failed to load product options:", err);

        if (err.response?.status === 401) {
            await router.push({
                name: "login",
            });

            return;
        }

        if (err.response?.status === 403) {
            error.value =
                "You do not have permission to load categories or suppliers.";

            return;
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to load categories and suppliers.";
    } finally {
        optionsLoading.value = false;
    }
}

// Load selected products

async function fetchProducts() {
    loading.value = true;
    error.value = "";

    try {
        let ids = route.query.selected_products;

        if (!ids) {
            error.value = "No products were selected.";
            return;
        }

        if (!Array.isArray(ids)) {
            ids = String(ids).split(",");
        }

        ids = ids.map((id) => Number(id)).filter((id) => !Number.isNaN(id));

        if (ids.length === 0) {
            error.value = "No valid products were selected.";
            return;
        }

        await productStore.fetchProducts();

        products.value = productStore.products
            .filter((product) => ids.includes(Number(product.id)))
            .map((product) => ({
                id: product.id,

                name: product.name ?? "",

                sku: product.sku ?? "",

                category_id: product.category_id ?? product.category?.id ?? "",

                supplier_id: product.supplier_id ?? product.supplier?.id ?? "",

                description: product.description ?? "",

                price: product.price ?? "",

                quantity: product.quantity ?? "",

                existingImage: imageUrl(product.image),

                image: null,

                removeImage: false,
            }));

        if (products.value.length === 0) {
            error.value = "Selected products could not be found.";
        }
    } catch (err) {
        console.error("Failed to load products:", err);

        if (err.response?.status === 401) {
            await router.push({
                name: "login",
            });

            return;
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to load selected products.";
    } finally {
        loading.value = false;
    }
}

// Validate product

function validateProduct(product) {
    const productName = product.name?.trim() || `Product #${product.id}`;

    if (!product.name?.trim()) {
        return `Product name cannot be empty for ${productName}.`;
    }

    if (!product.sku?.trim()) {
        return `SKU cannot be empty for "${productName}".`;
    }

    if (
        product.category_id === "" ||
        product.category_id === null ||
        product.category_id === undefined
    ) {
        return `Please select a category for "${productName}".`;
    }

    if (
        product.supplier_id === "" ||
        product.supplier_id === null ||
        product.supplier_id === undefined
    ) {
        return `Please select a supplier for "${productName}".`;
    }

    if (
        product.price === "" ||
        product.price === null ||
        product.price === undefined ||
        Number.isNaN(Number(product.price)) ||
        Number(product.price) < 0
    ) {
        return `Please enter a valid price for "${productName}".`;
    }

    if (
        product.quantity === "" ||
        product.quantity === null ||
        product.quantity === undefined ||
        Number.isNaN(Number(product.quantity)) ||
        Number(product.quantity) < 0 ||
        !Number.isInteger(Number(product.quantity))
    ) {
        return `Please enter a valid quantity for "${productName}".`;
    }

    return null;
}

// Save changes

async function saveChanges() {
    error.value = "";

    if (products.value.length === 0) {
        error.value = "There are no products to update.";
        return;
    }

    for (const product of products.value) {
        const validationError = validateProduct(product);

        if (validationError) {
            error.value = validationError;
            return;
        }
    }

    saving.value = true;

    try {
        const updatedProducts = products.value.map((product) => ({
            id: product.id,

            name: product.name.trim(),

            sku: product.sku.trim(),

            category_id: Number(product.category_id),

            supplier_id: Number(product.supplier_id),

            description: product.description?.trim() || "",

            price: Number(product.price),

            quantity: Number(product.quantity),

            image: product.image instanceof File ? product.image : null,

            removeImage: product.removeImage === true,
        }));

        await productStore.bulkUpdate(updatedProducts);

        toastStore.success("Products updated successfully.");

        await router.push({
            name: "products.index",
        });
    } catch (err) {
        console.error("Bulk update failed:", err);

        if (err.response?.status === 401) {
            await router.push({
                name: "login",
            });

            return;
        }

        if (err.response?.status === 403) {
            error.value =
                err.response?.data?.message ||
                "You do not have permission to update products.";

            return;
        }

        if (err.response?.status === 422) {
            const errors = err.response.data?.errors;

            error.value = errors
                ? Object.values(errors).flat().join(" ")
                : err.response.data?.message || "Validation failed.";

            return;
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to update selected products.";
    } finally {
        saving.value = false;
    }
}

// Cancel

function cancel() {
    router.push({
        name: "products.index",
    });
}

// Load

onMounted(async () => {
    await Promise.all([fetchOptions(), fetchProducts()]);
});
</script>

<template>
    <div class="mx-auto max-w-5xl">
        <!-- Header -->

        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Bulk Edit
                </h1>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Update the selected products.
                </p>
            </div>

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

        <BaseCard v-if="loading || optionsLoading" class="py-16">
            <div class="flex flex-col items-center justify-center">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900 dark:border-gray-700 dark:border-t-white"
                ></div>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        optionsLoading
                            ? "Loading categories and suppliers..."
                            : "Loading selected products..."
                    }}
                </p>
            </div>
        </BaseCard>

        <!-- Error -->

        <BaseCard
            v-else-if="error"
            class="border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30"
        >
            <div class="text-center">
                <p class="text-sm font-medium text-red-700 dark:text-red-400">
                    {{ error }}
                </p>

                <BaseButton type="button" class="mt-4" @click="fetchProducts">
                    Try Again
                </BaseButton>
            </div>
        </BaseCard>

        <!-- Form -->

        <form v-else class="space-y-4" @submit.prevent="saveChanges">
            <BaseCard
                v-for="(product, index) in products"
                :key="product.id"
                class="p-6"
            >
                <!-- Product Header -->

                <div
                    class="mb-6 flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-sm font-semibold text-white dark:bg-white dark:text-gray-900"
                    >
                        {{ index + 1 }}
                    </div>

                    <div>
                        <h2
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Product #{{ product.id }}
                        </h2>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Edit product information
                        </p>
                    </div>
                </div>

                <!-- Image -->

                <div class="mb-6">
                    <ImageUpload
                        v-model="product.image"
                        :existing-image="product.existingImage"
                        label="Product Image"
                        @remove-existing="product.removeImage = true"
                    />
                </div>

                <!-- Name and SKU -->

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label
                            :for="`name-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Product Name
                        </label>

                        <input
                            :id="`name-${product.id}`"
                            v-model="product.name"
                            type="text"
                            maxlength="255"
                            :disabled="saving"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        />
                    </div>

                    <div>
                        <label
                            :for="`sku-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            SKU
                        </label>

                        <input
                            :id="`sku-${product.id}`"
                            v-model="product.sku"
                            type="text"
                            maxlength="255"
                            :disabled="saving"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-mono text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        />
                    </div>
                </div>

                <!-- Category and Supplier -->

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label
                            :for="`category-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Category
                        </label>

                        <select
                            :id="`category-${product.id}`"
                            v-model="product.category_id"
                            :disabled="saving"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        >
                            <option value="">Select category</option>

                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            :for="`supplier-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Supplier
                        </label>

                        <select
                            :id="`supplier-${product.id}`"
                            v-model="product.supplier_id"
                            :disabled="saving"
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        >
                            <option value="">Select supplier</option>

                            <option
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                :value="supplier.id"
                            >
                                {{ supplier.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Description -->

                <div class="mt-5">
                    <label
                        :for="`description-${product.id}`"
                        class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Description
                    </label>

                    <textarea
                        :id="`description-${product.id}`"
                        v-model="product.description"
                        rows="3"
                        :disabled="saving"
                        class="w-full resize-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                    ></textarea>
                </div>

                <!-- Price and Quantity -->

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label
                            :for="`price-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
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
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        />
                    </div>

                    <div>
                        <label
                            :for="`quantity-${product.id}`"
                            class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-200"
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
                            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        />
                    </div>
                </div>
            </BaseCard>

            <!-- Actions -->

            <BaseCard
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ products.length }}
                    product{{ products.length !== 1 ? "s" : "" }}
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

                    <BaseButton type="submit" :loading="saving">
                        Save Changes
                    </BaseButton>
                </div>
            </BaseCard>
        </form>
    </div>
</template>
