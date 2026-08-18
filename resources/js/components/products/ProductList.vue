```vue
<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits([
    'add-product',
    'edit-product',
    'delete-product',
]);

const selectedProducts = ref([]);

const allSelected = computed(() => {
    return (
        props.products.length > 0 &&
        selectedProducts.value.length === props.products.length
    );
});

const selectedCount = computed(() => {
    return selectedProducts.value.length;
});

function toggleSelectAll() {
    if (allSelected.value) {
        selectedProducts.value = [];
    } else {
        selectedProducts.value = props.products.map(
            product => product.id
        );
    }
}

function toggleProduct(productId) {
    if (selectedProducts.value.includes(productId)) {
        selectedProducts.value = selectedProducts.value.filter(
            id => id !== productId
        );
    } else {
        selectedProducts.value.push(productId);
    }
}

function deleteProduct(product) {
    const confirmed = confirm(
        `Are you sure you want to delete "${product.name}"?`
    );

    if (!confirmed) {
        return;
    }

    emit('delete-product', product);

    selectedProducts.value = selectedProducts.value.filter(
        id => id !== product.id
    );
}

function bulkEdit() {
    if (selectedProducts.value.length === 0) {
        return;
    }

    const productsToEdit = props.products.filter(product =>
        selectedProducts.value.includes(product.id)
    );

    if (productsToEdit.length === 1) {
        emit('edit-product', productsToEdit[0]);
        return;
    }

    alert(
        `${productsToEdit.length} products selected. Bulk edit will be added next.`
    );
}

function bulkDelete() {
    if (selectedProducts.value.length === 0) {
        return;
    }

    const confirmed = confirm(
        `Are you sure you want to delete ${selectedProducts.value.length} selected product(s)?`
    );

    if (!confirmed) {
        return;
    }

    const productsToDelete = props.products.filter(product =>
        selectedProducts.value.includes(product.id)
    );

    productsToDelete.forEach(product => {
        emit('delete-product', product);
    });

    selectedProducts.value = [];
}
</script>

<template>
    <div class="mx-auto max-w-7xl">

        <!-- Header -->
        <div
            class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-3">

                    <h1
                        class="text-2xl font-bold tracking-tight text-gray-900"
                    >
                        Products
                    </h1>

                    <span
                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600"
                    >
                        {{ products.length }}
                    </span>

                </div>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your product inventory.
                </p>
            </div>

            <!-- Add Product -->
            <button
                type="button"
                @click="emit('add-product')"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Add Product
            </button>
        </div>

        <!-- Bulk Actions -->
        <div
            v-if="selectedCount > 0"
            class="mb-4 flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm"
        >
            <div class="flex items-center gap-3">

                <span
                    class="text-sm font-semibold text-gray-700"
                >
                    {{ selectedCount }} selected
                </span>

            </div>

            <div class="flex items-center gap-2">

                <!-- Bulk Edit -->
                <button
                    type="button"
                    @click="bulkEdit"
                    class="rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                >
                    Edit Selected
                </button>

                <!-- Bulk Delete -->
                <button
                    type="button"
                    @click="bulkDelete"
                    class="rounded-lg bg-red-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                >
                    Delete Selected
                </button>

            </div>
        </div>

        <!-- Product Table -->
        <div
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
        >
            <div class="overflow-x-auto">

                <table class="w-full min-w-[900px] text-left text-sm">

                    <!-- Table Header -->
                    <thead class="border-b border-gray-200 bg-gray-50">

                        <tr>

                            <!-- Select All -->
                            <th class="w-12 px-6 py-4">

                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                />

                            </th>

                            <th
                                class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Product
                            </th>

                            <th
                                class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Description
                            </th>

                            <th
                                class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Price
                            </th>

                            <th
                                class="px-6 py-4 text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Stock
                            </th>

                            <th
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <!-- Table Body -->
                    <tbody class="divide-y divide-gray-100">

                        <tr
                            v-for="product in products"
                            :key="product.id"
                            class="group hover:bg-gray-50"
                        >

                            <!-- Product Checkbox -->
                            <td class="px-6 py-4">

                                <input
                                    type="checkbox"
                                    :checked="selectedProducts.includes(product.id)"
                                    @change="toggleProduct(product.id)"
                                    class="product-checkbox h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                />

                            </td>

                            <!-- Product -->
                            <td class="px-6 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-700"
                                    >
                                        {{ product.name.charAt(0).toUpperCase() }}
                                    </div>

                                    <div class="min-w-0">

                                        <div
                                            class="truncate font-semibold text-gray-900"
                                        >
                                            {{ product.name }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-xs text-gray-400"
                                        >
                                            Product #{{ product.id }}
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <!-- Description -->
                            <td class="max-w-xs px-6 py-4">

                                <p class="truncate text-gray-500">
                                    {{ product.description || 'No description' }}
                                </p>

                            </td>

                            <!-- Price -->
                            <td class="whitespace-nowrap px-6 py-4">

                                <span
                                    class="font-semibold text-gray-900"
                                >
                                    Rs.
                                    {{ Number(product.price).toLocaleString() }}
                                </span>

                            </td>

                            <!-- Stock -->
                            <td class="whitespace-nowrap px-6 py-4">

                                <!-- Out of stock -->
                                <span
                                    v-if="product.quantity === 0"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-red-500"
                                    ></span>

                                    Out of stock
                                </span>

                                <!-- Low stock -->
                                <span
                                    v-else-if="product.quantity <= 5"
                                    class="inline-flex items-center gap-1.5 rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-700"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-yellow-500"
                                    ></span>

                                    {{ product.quantity }} left
                                </span>

                                <!-- In stock -->
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-green-500"
                                    ></span>

                                    {{ product.quantity }} in stock
                                </span>

                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">

                                <div
                                    class="flex items-center justify-end gap-4"
                                >

                                    <!-- View -->
                                    <button
                                        type="button"
                                        class="text-sm font-medium text-gray-500 hover:text-gray-900"
                                    >
                                        View
                                    </button>

                                    <!-- Edit -->
                                    <button
                                        type="button"
                                        @click="emit('edit-product', product)"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        Edit
                                    </button>

                                    <!-- Delete -->
                                    <button
                                        type="button"
                                        @click="deleteProduct(product)"
                                        class="text-sm font-medium text-red-600 hover:text-red-800"
                                    >
                                        Delete
                                    </button>

                                </div>

                            </td>

                        </tr>

                        <!-- Empty State -->
                        <tr v-if="products.length === 0">

                            <td
                                colspan="6"
                                class="px-6 py-16 text-center"
                            >

                                <div class="mx-auto max-w-sm">

                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100"
                                    >
                                        <svg
                                            class="h-6 w-6 text-gray-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </div>

                                    <h3
                                        class="mt-4 text-base font-semibold text-gray-900"
                                    >
                                        No products found
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Get started by adding your first product.
                                    </p>

                                    <button
                                        type="button"
                                        @click="emit('add-product')"
                                        class="mt-5 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800"
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
                                                d="M12 4v16m8-8H4"
                                            />
                                        </svg>

                                        Add Product
                                    </button>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>
        </div>

    </div>
</template>
```
