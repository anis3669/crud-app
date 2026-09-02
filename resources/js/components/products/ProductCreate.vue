<script setup>
import { computed, onMounted, ref } from "vue";
import { storeToRefs } from "pinia";
import { useRouter } from "vue-router";

import { useProductStore } from "../../stores/product";
import { useCategoryStore } from "../../stores/category";
import { useSupplierStore } from "../../stores/supplier";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import ImageUpload from "../common/ImageUpload.vue";

const router = useRouter();

const productStore = useProductStore();
const categoryStore = useCategoryStore();
const supplierStore = useSupplierStore();
const toastStore = useToastStore();

const { categories } = storeToRefs(categoryStore);
const { suppliers } = storeToRefs(supplierStore);

// Form

const form = ref({
    name: "",
    sku: "",
    category_id: "",
    supplier_id: "",
    description: "",
    price: "",
    quantity: "",
    image: null,
});

// State

const loading = ref(false);
const loadingFormData = ref(false);
const error = ref("");

// Form status

const formDataLoading = computed(
    () => categoryStore.loading || supplierStore.loading,
);

// Load categories and suppliers

async function loadFormData() {
    loadingFormData.value = true;
    error.value = "";

    try {
        await Promise.all([
            categoryStore.fetchCategories(),
            supplierStore.fetchSuppliers(),
        ]);
    } catch (err) {
        console.error("Failed to load product form data:", err);

        if (err.response?.status === 401) {
            await router.push({
                name: "login",
            });

            return;
        }

        error.value =
            err.response?.data?.message ||
            "Failed to load categories and suppliers.";
    } finally {
        loadingFormData.value = false;
    }
}

// Validation

function validateForm() {
    if (!form.value.name.trim()) {
        error.value = "Please enter a product name.";
        return false;
    }

    if (!form.value.sku.trim()) {
        error.value = "Please enter a SKU.";
        return false;
    }

    if (!form.value.category_id) {
        error.value = "Please select a category.";
        return false;
    }

    if (!form.value.supplier_id) {
        error.value = "Please select a supplier.";
        return false;
    }

    if (form.value.price === "" || Number(form.value.price) < 0) {
        error.value = "Please enter a valid price.";
        return false;
    }

    if (
        form.value.quantity === "" ||
        Number(form.value.quantity) < 0 ||
        !Number.isInteger(Number(form.value.quantity))
    ) {
        error.value = "Please enter a valid whole-number quantity.";
        return false;
    }

    return true;
}

// Create product

async function submitForm() {
    error.value = "";

    if (!validateForm()) {
        return;
    }

    loading.value = true;

    try {
        const productData = new FormData();

        productData.append("name", form.value.name.trim());
        productData.append("sku", form.value.sku.trim());
        productData.append("category_id", String(form.value.category_id));
        productData.append("supplier_id", String(form.value.supplier_id));
        productData.append("description", form.value.description?.trim() || "");
        productData.append("price", String(Number(form.value.price)));
        productData.append("quantity", String(Number(form.value.quantity)));

        if (form.value.image instanceof File) {
            productData.append("image", form.value.image);
        }

        await productStore.createProduct(productData);

        toastStore.success("Product created successfully.");

        await router.push({
            name: "products.index",
        });
    } catch (err) {
        console.error("Create product error:", err);

        if (err.response?.status === 401) {
            await router.push({
                name: "login",
            });

            return;
        }

        if (err.response?.status === 422) {
            const validationErrors = err.response.data?.errors;

            if (validationErrors) {
                error.value = Object.values(validationErrors).flat().join(" ");
            } else {
                error.value =
                    err.response.data?.message || "Validation failed.";
            }

            toastStore.error("Failed to create product.");
            return;
        }

        error.value =
            err.response?.data?.message ||
            err.message ||
            productStore.error ||
            "Failed to create product.";

        toastStore.error("Failed to create product.");
    } finally {
        loading.value = false;
    }
}

// Cancel

function cancel() {
    router.push({
        name: "products.index",
    });
}

onMounted(loadFormData);
</script>

<template>
    <div class="mx-auto w-full max-w-3xl">
        <!-- Header -->

        <div class="mb-8">
            <h1
                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
            >
                Add Product
            </h1>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Create a new product for your inventory.
            </p>
        </div>

        <!-- Error -->

        <BaseCard
            v-if="error"
            class="mb-6 border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30"
        >
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                {{ error }}
            </p>
        </BaseCard>

        <!-- Form -->

        <BaseCard>
            <form class="space-y-6" @submit.prevent="submitForm">
                <!-- Product Name -->

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
                        maxlength="255"
                        placeholder="Enter product name"
                        :disabled="loading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                    />
                </div>

                <!-- SKU -->

                <div>
                    <label
                        for="sku"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        SKU
                    </label>

                    <input
                        id="sku"
                        v-model="form.sku"
                        type="text"
                        maxlength="100"
                        placeholder="e.g. PROD-001"
                        :disabled="loading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm uppercase text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                    />

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        SKU must be unique.
                    </p>
                </div>

                <!-- Category -->

                <div>
                    <label
                        for="category"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Category
                    </label>

                    <select
                        id="category"
                        v-model="form.category_id"
                        :disabled="loading || formDataLoading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-white dark:focus:ring-white dark:disabled:bg-gray-700"
                    >
                        <option value="">
                            {{
                                categoryStore.loading
                                    ? "Loading categories..."
                                    : "Select a category"
                            }}
                        </option>

                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Supplier -->

                <div>
                    <label
                        for="supplier"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Supplier
                    </label>

                    <select
                        id="supplier"
                        v-model="form.supplier_id"
                        :disabled="loading || formDataLoading"
                        class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-white dark:focus:ring-white dark:disabled:bg-gray-700"
                    >
                        <option value="">
                            {{
                                supplierStore.loading
                                    ? "Loading suppliers..."
                                    : "Select a supplier"
                            }}
                        </option>

                        <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.name }}
                        </option>
                    </select>
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
                        maxlength="1000"
                        placeholder="Enter product description"
                        :disabled="loading"
                        class="mt-2 block w-full resize-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                    ></textarea>
                </div>

                <!-- Price and Quantity -->

                <div class="grid gap-6 sm:grid-cols-2">
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
                            :disabled="loading"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
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
                            :disabled="loading"
                            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-gray-300 dark:focus:ring-gray-300 dark:disabled:bg-gray-800"
                        />
                    </div>
                </div>

                <!-- Product Image -->

                <div>
                    <ImageUpload v-model="form.image" label="Product Image" />
                </div>

                <!-- Actions -->

                <div
                    class="flex justify-end gap-3 border-t border-gray-100 pt-6 dark:border-gray-700"
                >
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
                        :disabled="formDataLoading"
                    >
                        Create Product
                    </BaseButton>
                </div>
            </form>
        </BaseCard>
    </div>
</template>
