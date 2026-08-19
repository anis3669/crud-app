<script setup>
import { ref } from "vue";

import ProductList from "./components/products/ProductList.vue";
import ProductCreate from "./components/products/ProductCreate.vue";
import ProductEdit from "./components/products/ProductEdit.vue";
import ProductView from "./components/products/ProductView.vue";
import BulkEdit from "./components/products/BulkEdit.vue";
const products = ref([
    {
        id: 1,
        name: "Laptop",
        description: "High performance laptop",
        price: 85000,
        quantity: 10,
    },
    {
        id: 2,
        name: "Keyboard",
        description: "Mechanical keyboard",
        price: 4500,
        quantity: 5,
    },
    {
        id: 3,
        name: "Mouse",
        description: "Wireless mouse",
        price: 2500,
        quantity: 0,
    },
    {
        id: 4,
        name: "Monitor",
        description: "4K UHD monitor",
        price: 30000,
        quantity: 3,
    },
]);

const currentPage = ref("list");

const selectedProduct = ref(null);
function showView(product) {
    selectedProduct.value = {
        ...product,
    };

    currentPage.value = "view";
}
const selectedProductsForBulkEdit = ref([]);

function showBulkEdit(productsToEdit) {
    selectedProductsForBulkEdit.value = productsToEdit;
    currentPage.value = "bulk-edit";
}

function updateBulkProducts(updatedProducts) {
    updatedProducts.forEach((updatedProduct) => {
        const index = products.value.findIndex(
            (product) => product.id === updatedProduct.id,
        );

        if (index !== -1) {
            products.value[index] = {
                ...updatedProduct,
            };
        }
    });

    showList();

    showNotification(
        `${updatedProducts.length} product(s) updated successfully.`,
    );
}
// notification

const notification = ref({
    show: false,
    message: "",
    type: "success",
});

let notificationTimer = null;

function showNotification(message, type = "success") {
    notification.value = {
        show: true,
        message,
        type,
    };

    clearTimeout(notificationTimer);

    notificationTimer = setTimeout(() => {
        notification.value.show = false;
    }, 3000);
}

function closeNotification() {
    notification.value.show = false;
}

// navigations

function showCreate() {
    currentPage.value = "create";
    selectedProduct.value = null;
}

function showList() {
    currentPage.value = "list";
    selectedProduct.value = null;
}

function showEdit(product) {
    selectedProduct.value = {
        ...product,
    };

    currentPage.value = "edit";
}

// create the product

function addProduct(product) {
    products.value.push({
        id: Date.now(),
        ...product,
    });

    showList();

    showNotification(`Product "${product.name}" created successfully.`);
}
// update the product
function updateProduct(updatedProduct) {
    const index = products.value.findIndex(
        (product) => product.id === updatedProduct.id,
    );

    if (index !== -1) {
        products.value[index] = {
            ...updatedProduct,
        };

        showList();

        showNotification(
            `Product "${updatedProduct.name}" updated successfully.`,
        );
    }
}

// delete  the product

function deleteProduct(product) {
    products.value = products.value.filter((item) => item.id !== product.id);

    showNotification(`Product "${product.name}" deleted successfully.`);
}
// bulk delete the product
function bulkDelete(productsToDelete) {
    const ids = productsToDelete.map((product) => product.id);

    products.value = products.value.filter(
        (product) => !ids.includes(product.id),
    );

    showNotification(
        `${productsToDelete.length} product(s) deleted successfully.`,
    );
}
</script>

<template>
    <!-- for notification -->

    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="notification.show"
            class="fixed right-5 top-5 z-50 w-full max-w-sm"
        >
            <div
                class="flex items-start gap-3 rounded-xl border border-green-200 bg-white px-4 py-4 shadow-lg"
            >
                <!-- Success Icon -->
                <div
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100"
                >
                    <svg
                        class="h-5 w-5 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <!-- Message -->
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900">Success</p>

                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ notification.message }}
                    </p>
                </div>

                <!-- Close -->
                <button
                    type="button"
                    @click="closeNotification"
                    class="shrink-0 text-gray-400 hover:text-gray-600"
                    aria-label="Close notification"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>

    <!-- product list -->
    <ProductList
        v-if="currentPage === 'list'"
        :products="products"
        @add-product="showCreate"
        @view-product="showView"
        @edit-product="showEdit"
        @delete-product="deleteProduct"
        @bulk-delete="bulkDelete"
        @bulk-edit="showBulkEdit"
    />
    <!-- product create -->

    <ProductCreate
        v-else-if="currentPage === 'create'"
        @cancel="showList"
        @product-created="addProduct"
    />
    <!-- product edit -->
    <ProductEdit
        v-else-if="currentPage === 'edit' && selectedProduct"
        :product="selectedProduct"
        @cancel="showList"
        @product-updated="updateProduct"
    />
    <!-- bulk edit -->
    <BulkEdit
        v-else-if="
            currentPage === 'bulk-edit' &&
            selectedProductsForBulkEdit.length > 0
        "
        :products="selectedProductsForBulkEdit"
        @cancel="showList"
        @products-updated="updateBulkProducts"
    />
    <!-- product view -->
    <ProductView
        v-else-if="currentPage === 'view' && selectedProduct"
        :product="selectedProduct"
        @back="showList"
        @edit-product="showEdit"
    />
</template>
