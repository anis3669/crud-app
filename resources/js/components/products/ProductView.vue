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
const error = ref(null);

const showDeleteModal = ref(false);
const deleting = ref(false);

const imageUrl = computed(() => {
    if (!product.value?.image) {
        return null;
    }

    const image = product.value.image;

    if (
        image.startsWith("http://") ||
        image.startsWith("https://") ||
        image.startsWith("/storage/")
    ) {
        return image;
    }

    return `/storage/${image}`;
});

const stockStatus = computed(() => {
    if (!product.value) {
        return null;
    }

    const quantity = Number(product.value.quantity) || 0;

    if (quantity === 0) {
        return {
            label: "Out of stock",
            wrapper: "bg-red-50 text-red-700",
            dot: "bg-red-500",
        };
    }

    if (quantity <= 5) {
        return {
            label: `${quantity} left`,
            wrapper: "bg-yellow-50 text-yellow-700",
            dot: "bg-yellow-500",
        };
    }

    return {
        label: `${quantity} in stock`,
        wrapper: "bg-green-50 text-green-700",
        dot: "bg-green-500",
    };
});

async function fetchProduct() {
    loading.value = true;
    error.value = null;

    try {
        product.value = await productStore.fetchProduct(route.params.id);
    } catch (err) {
        console.error("Failed to load product:", err);

        if (err.response?.status === 404) {
            error.value = "Product not found.";
        } else if (err.response?.status === 401) {
            router.push({
                name: "login",
            });

            return;
        } else {
            error.value =
                err.response?.data?.message || "Failed to load product.";
        }
    } finally {
        loading.value = false;
    }
}

function back() {
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

        router.push({
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

onMounted(() => {
    fetchProduct();
});
</script>

<template>
    <div class="mx-auto max-w-4xl">
        <!-- Loading -->
        <BaseCard v-if="loading" class="py-16">
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

                <p class="mt-4 text-sm text-gray-500">Loading product...</p>
            </div>
        </BaseCard>

        <!-- Error -->
        <BaseCard v-else-if="error" class="border-red-200 bg-red-50">
            <div class="py-4 text-center">
                <h2 class="text-lg font-semibold text-red-800">
                    Unable to load product
                </h2>

                <p class="mt-1 text-sm text-red-700">
                    {{ error }}
                </p>

                <div class="mt-5 flex justify-center gap-3">
                    <BaseButton type="button" variant="secondary" @click="back">
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
                <BaseButton type="button" variant="secondary" @click="back">
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
                </BaseButton>
            </div>

            <!-- Header -->
            <div
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <!-- Small image -->
                    <div
                        class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-100"
                    >
                        <img
                            v-if="imageUrl"
                            :src="imageUrl"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />

                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-lg font-bold text-gray-700"
                        >
                            {{ product.name?.charAt(0)?.toUpperCase() }}
                        </div>
                    </div>

                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-gray-900"
                        >
                            {{ product.name }}
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            Product #{{ product.id }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <BaseButton type="button" @click="editProduct">
                        Edit Product
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="danger"
                        @click="openDeleteModal"
                    >
                        Delete Product
                    </BaseButton>
                </div>
            </div>

            <!-- IMAGE + INFORMATION -->
            <BaseCard class="overflow-hidden p-0">
                <!-- Product Image -->
                <div
                    class="flex min-h-[350px] items-center justify-center border-b border-gray-100 bg-gray-50 p-6"
                >
                    <img
                        v-if="imageUrl"
                        :src="imageUrl"
                        :alt="product.name"
                        class="max-h-[400px] max-w-full rounded-xl object-contain"
                    />

                    <div
                        v-else
                        class="flex h-64 w-64 items-center justify-center rounded-xl bg-gray-200 text-7xl font-bold text-gray-500"
                    >
                        {{ product.name?.charAt(0)?.toUpperCase() }}
                    </div>
                </div>

                <!-- Description -->
                <div class="border-b border-gray-100 px-6 py-6">
                    <h2 class="text-sm font-semibold text-gray-900">
                        Description
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        {{ product.description || "No description available." }}
                    </p>
                </div>

                <!-- Information -->
                <div class="grid gap-px bg-gray-100 sm:grid-cols-2">
                    <!-- Price -->
                    <div class="bg-white px-6 py-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            Price
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            Rs.
                            {{ formatPrice(product.price) }}
                        </p>
                    </div>

                    <!-- Quantity -->
                    <div class="bg-white px-6 py-6">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            Quantity
                        </p>

                        <p class="mt-2 text-2xl font-bold text-gray-900">
                            {{ product.quantity }}
                        </p>
                    </div>

                    <!-- Stock -->
                    <div class="bg-white px-6 py-6 sm:col-span-2">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500"
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
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100"
                >
                    <svg
                        class="h-6 w-6 text-red-600"
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

                <h3 class="mt-4 text-lg font-semibold text-gray-900">
                    Delete Product?
                </h3>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Are you sure you want to delete

                    <span class="font-semibold text-gray-700">
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
</template>
