<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import { useProductStore } from "../../stores/product";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import BaseModal from "../common/BaseModal.vue";

const route = useRoute();
const router = useRouter();

const productStore = useProductStore();
const toastStore = useToastStore();

const product = ref(null);
const loading = ref(true);
const error = ref("");

const showDeleteModal = ref(false);
const deleting = ref(false);

// Image URL

const imageUrl = computed(() => {
    const image = product.value?.image;

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
});

// Stock status

const stockStatus = computed(() => {
    if (!product.value) {
        return null;
    }

    const quantity = Number(product.value.quantity) || 0;

    if (quantity === 0) {
        return {
            label: "Out of stock",
            wrapper:
                "bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400",
            dot: "bg-red-500",
        };
    }

    if (quantity <= 5) {
        return {
            label: `${quantity} left`,
            wrapper:
                "bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400",
            dot: "bg-amber-500",
        };
    }

    return {
        label: `${quantity} in stock`,
        wrapper:
            "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400",
        dot: "bg-emerald-500",
    };
});

// Format price

function formatPrice(price) {
    const amount = Number(price);

    if (Number.isNaN(amount)) {
        return "0.00";
    }

    return amount.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

// Load product

async function fetchProduct() {
    loading.value = true;
    error.value = "";

    try {
        product.value = await productStore.fetchProduct(route.params.id);

        if (!product.value) {
            error.value = "Product not found.";
        }
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

        error.value = err.response?.data?.message || "Failed to load product.";
    } finally {
        loading.value = false;
    }
}

// Navigation

function backToProducts() {
    router.push({
        name: "products.index",
    });
}

function editProduct() {
    if (!product.value) {
        return;
    }

    router.push({
        name: "products.edit",
        params: {
            id: product.value.id,
        },
    });
}

// Delete

function openDeleteModal() {
    if (!product.value) {
        return;
    }

    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
}

async function confirmDelete() {
    if (!product.value) {
        return;
    }

    deleting.value = true;

    try {
        await productStore.deleteProduct(product.value.id);

        showDeleteModal.value = false;

        toastStore.success("Product deleted successfully.");

        await router.push({
            name: "products.index",
        });
    } catch (err) {
        console.error("Delete product error:", err);

        if (err.response?.status === 401) {
            router.push({
                name: "login",
            });

            return;
        }

        error.value =
            err.response?.data?.message || "Failed to delete product.";

        showDeleteModal.value = false;
    } finally {
        deleting.value = false;
    }
}

// Load

onMounted(fetchProduct);
</script>

<template>
    ```
    <div class="mx-auto w-full max-w-4xl">
        <!-- Loading -->

        <BaseCard v-if="loading" class="py-16">
            <div class="flex flex-col items-center justify-center">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900 dark:border-gray-700 dark:border-t-white"
                ></div>

                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    Loading product...
                </p>
            </div>
        </BaseCard>

        <!-- Error -->

        <BaseCard
            v-else-if="error"
            class="border-red-200 bg-red-50 dark:border-red-900/60 dark:bg-red-950/30"
        >
            <div class="py-6 text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/50"
                >
                    <svg
                        class="h-6 w-6 text-red-600 dark:text-red-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>

                <h2
                    class="mt-4 text-lg font-semibold text-red-800 dark:text-red-300"
                >
                    Unable to load product
                </h2>

                <p class="mt-1 text-sm text-red-700 dark:text-red-400">
                    {{ error }}
                </p>

                <div class="mt-5 flex justify-center gap-3">
                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="backToProducts"
                    >
                        Back to Products
                    </BaseButton>

                    <BaseButton type="button" @click="fetchProduct">
                        Try Again
                    </BaseButton>
                </div>
            </div>
        </BaseCard>

        <!-- Product -->

        <template v-else-if="product">
            <!-- Back -->

            <div class="mb-6">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                    @click="backToProducts"
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
            </div>

            <!-- Header -->

            <div
                class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 items-center gap-4">
                    <div
                        class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <img
                            v-if="imageUrl"
                            :src="imageUrl"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />

                        <span
                            v-else
                            class="text-xl font-bold text-gray-500 dark:text-gray-300"
                        >
                            {{ product.name?.charAt(0)?.toUpperCase() }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <h1
                            class="truncate text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                        >
                            {{ product.name }}
                        </h1>

                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <span
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                Product #{{ product.id }}
                            </span>

                            <span class="text-gray-300 dark:text-gray-700">
                                •
                            </span>

                            <span
                                v-if="stockStatus"
                                :class="stockStatus.wrapper"
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
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

                <!-- Actions -->

                <div class="flex shrink-0 items-center gap-3">
                    <BaseButton
                        type="button"
                        variant="secondary"
                        @click="editProduct"
                    >
                        Edit Product
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="danger"
                        @click="openDeleteModal"
                    >
                        Delete
                    </BaseButton>
                </div>
            </div>

            <!-- Product Information -->

            <BaseCard class="overflow-hidden p-0">
                <!-- Image -->

                <div
                    class="flex min-h-[350px] items-center justify-center border-b border-gray-100 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-950"
                >
                    <img
                        v-if="imageUrl"
                        :src="imageUrl"
                        :alt="product.name"
                        class="max-h-[400px] max-w-full rounded-xl object-contain"
                    />

                    <div
                        v-else
                        class="flex h-64 w-64 items-center justify-center rounded-xl bg-gray-200 text-7xl font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                    >
                        {{ product.name?.charAt(0)?.toUpperCase() }}
                    </div>
                </div>

                <!-- Description -->

                <div
                    class="border-b border-gray-100 px-6 py-6 dark:border-gray-800"
                >
                    <h2
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Description
                    </h2>

                    <p
                        class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400"
                    >
                        {{ product.description || "No description available." }}
                    </p>
                </div>

                <!-- Information -->

                <div
                    class="grid gap-px bg-gray-100 dark:bg-gray-800 sm:grid-cols-2"
                >
                    <!-- Price -->

                    <div class="bg-white px-6 py-6 dark:bg-gray-900">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            Price
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            Rs.
                            {{ formatPrice(product.price) }}
                        </p>
                    </div>

                    <!-- Quantity -->

                    <div class="bg-white px-6 py-6 dark:bg-gray-900">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            Quantity
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            {{ product.quantity }}
                        </p>
                    </div>

                    <!-- Stock Status -->

                    <div
                        class="bg-white px-6 py-6 dark:bg-gray-900 sm:col-span-2"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
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
            </BaseCard>
        </template>

        <!-- Delete Modal -->

        <BaseModal
            :show="showDeleteModal"
            title="Delete Product"
            size="sm"
            @close="closeDeleteModal"
        >
            <div class="text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/50"
                >
                    <svg
                        class="h-6 w-6 text-red-600 dark:text-red-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z"
                        />
                    </svg>
                </div>

                <h3
                    class="mt-4 text-lg font-semibold text-gray-900 dark:text-white"
                >
                    Delete Product?
                </h3>

                <p
                    class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"
                >
                    Are you sure you want to delete

                    <span
                        class="font-semibold text-gray-800 dark:text-gray-200"
                    >
                        {{ product?.name }}
                    </span>

                    ? This action cannot be undone.
                </p>
            </div>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="deleting"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="danger"
                        :disabled="deleting"
                        :loading="deleting"
                        @click="confirmDelete"
                    >
                        Delete Product
                    </BaseButton>
                </div>
            </template>
        </BaseModal>
    </div>
    ```
</template>
