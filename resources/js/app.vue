<script setup>
import { onMounted, ref } from "vue";

import Login from "./components/auth/Login.vue";
import Register from "./components/auth/Register.vue";

import ProductList from "./components/products/ProductList.vue";
import ProductCreate from "./components/products/ProductCreate.vue";
import ProductEdit from "./components/products/ProductEdit.vue";
import ProductView from "./components/products/ProductView.vue";
import BulkEdit from "./components/products/BulkEdit.vue";

// ============================================================
// Authentication
// ============================================================

const authenticated = ref(false);
const checkingAuth = ref(true);
const authPage = ref("login");

// ============================================================
// Products
// ============================================================

const products = ref([]);

const loading = ref(true);
const error = ref("");

// ============================================================
// Navigation
// ============================================================

const currentPage = ref("list");

const selectedProduct = ref(null);

const selectedProductsForBulkEdit = ref([]);

// ============================================================
// Notification
// ============================================================

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

// ============================================================
// CSRF Helper
// ============================================================

async function getCsrfToken() {
    const response = await fetch("/sanctum/csrf-cookie", {
        method: "GET",
        credentials: "include",
        headers: {
            Accept: "application/json",
        },
    });

    if (!response.ok) {
        throw new Error(
            `Unable to initialize CSRF protection. Status: ${response.status}`,
        );
    }

    const cookie = document.cookie
        .split("; ")
        .find((row) => row.startsWith("XSRF-TOKEN="));

    if (!cookie) {
        throw new Error("CSRF token was not found.");
    }

    return decodeURIComponent(cookie.substring("XSRF-TOKEN=".length));
}

// ============================================================
// API Response Helper
// ============================================================

async function getResponseData(response) {
    const contentType = response.headers.get("content-type") || "";

    if (contentType.includes("application/json")) {
        return await response.json();
    }

    return {};
}

// ============================================================
// Authentication Check
// ============================================================

async function checkAuthentication() {
    checkingAuth.value = true;
    loading.value = true;
    error.value = "";

    try {
        const response = await fetch("/api/products", {
            method: "GET",
            credentials: "include",
            headers: {
                Accept: "application/json",
            },
        });

        const data = await getResponseData(response);

        if (response.ok) {
            authenticated.value = true;

            products.value = Array.isArray(data) ? data : data.products || [];

            return;
        }

        if (response.status === 401) {
            authenticated.value = false;
            products.value = [];
            return;
        }

        authenticated.value = false;
    } catch (err) {
        console.error("Authentication check failed:", err);

        authenticated.value = false;
        products.value = [];
    } finally {
        checkingAuth.value = false;
        loading.value = false;
    }
}

// ============================================================
// Login Success
// ============================================================

async function handleLoginSuccess() {
    authenticated.value = true;

    currentPage.value = "list";

    selectedProduct.value = null;

    selectedProductsForBulkEdit.value = [];

    await loadProducts();
}

// ============================================================
// Logout
// ============================================================

async function logout() {
    try {
        const xsrfToken = await getCsrfToken();

        const response = await fetch("/api/logout", {
            method: "POST",
            credentials: "include",
            headers: {
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
        });

        const data = await getResponseData(response);

        if (!response.ok) {
            throw new Error(
                data.message || `Logout failed. Status: ${response.status}`,
            );
        }

        // Clear authentication state
        authenticated.value = false;

        // Clear products
        products.value = [];

        // Reset navigation
        currentPage.value = "list";
        selectedProduct.value = null;
        selectedProductsForBulkEdit.value = [];

        showNotification("You have been logged out successfully.");
    } catch (err) {
        console.error("Logout failed:", err);

        showNotification(err.message || "Unable to logout.", "error");
    }
}
// ============================================================
// Load Products
// ============================================================

async function loadProducts() {
    loading.value = true;
    error.value = "";

    try {
        const response = await fetch("/api/products", {
            method: "GET",
            credentials: "include",
            headers: {
                Accept: "application/json",
            },
        });

        const data = await getResponseData(response);

        if (response.status === 401) {
            authenticated.value = false;
            products.value = [];
            return;
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                    `Failed to load products. Status: ${response.status}`,
            );
        }

        authenticated.value = true;

        products.value = Array.isArray(data) ? data : data.products || [];
    } catch (err) {
        console.error("Failed to load products:", err);

        error.value = "Unable to load products. Please try again.";

        showNotification(err.message || "Unable to load products.", "error");
    } finally {
        loading.value = false;
    }
}

// ============================================================
// Navigation Functions
// ============================================================
function showRegister() {
    currentPage.value = "register";
}

function showLogin() {
    currentPage.value = "login";
}

function showCreate() {
    currentPage.value = "create";

    selectedProduct.value = null;

    selectedProductsForBulkEdit.value = [];
}

function showList() {
    currentPage.value = "list";

    selectedProduct.value = null;

    selectedProductsForBulkEdit.value = [];
}

function showEdit(product) {
    selectedProduct.value = {
        ...product,
    };

    currentPage.value = "edit";
}

function showView(product) {
    selectedProduct.value = {
        ...product,
    };

    currentPage.value = "view";
}

function showBulkEdit(productsToEdit) {
    selectedProductsForBulkEdit.value = productsToEdit.map((product) => ({
        ...product,
    }));

    currentPage.value = "bulk-edit";
}

// ============================================================
// Create Product
// ============================================================

async function addProduct(product) {
    try {
        const xsrfToken = await getCsrfToken();

        const response = await fetch("/api/products", {
            method: "POST",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
            body: JSON.stringify({
                name: product.name,
                description: product.description,
                price: Number(product.price),
                quantity: Number(product.quantity),
            }),
        });

        const data = await getResponseData(response);

        if (response.status === 401) {
            authenticated.value = false;
            return;
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                    `Failed to create product. Status: ${response.status}`,
            );
        }

        const createdProduct = data.product || data;

        products.value.push(createdProduct);

        showList();

        showNotification(
            `Product "${createdProduct.name}" created successfully.`,
        );
    } catch (err) {
        console.error("Failed to create product:", err);

        showNotification(err.message || "Unable to create product.", "error");
    }
}

// ============================================================
// Update Single Product
// ============================================================

async function updateProduct(updatedProduct) {
    try {
        const xsrfToken = await getCsrfToken();

        const response = await fetch(`/api/products/${updatedProduct.id}`, {
            method: "PUT",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
            body: JSON.stringify({
                name: updatedProduct.name,
                description: updatedProduct.description,
                price: Number(updatedProduct.price),
                quantity: Number(updatedProduct.quantity),
            }),
        });

        const data = await getResponseData(response);

        if (response.status === 401) {
            authenticated.value = false;
            return;
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                    `Failed to update product. Status: ${response.status}`,
            );
        }

        const updated = data.product || data;

        const index = products.value.findIndex(
            (product) => product.id === updated.id,
        );

        if (index !== -1) {
            products.value[index] = {
                ...updated,
            };
        }

        showList();

        showNotification(`Product "${updated.name}" updated successfully.`);
    } catch (err) {
        console.error("Failed to update product:", err);

        showNotification(err.message || "Unable to update product.", "error");
    }
}

// ============================================================
// Delete Single Product
// ============================================================

async function deleteProduct(product) {
    const confirmed = window.confirm(
        `Are you sure you want to delete "${product.name}"?`,
    );

    if (!confirmed) {
        return;
    }

    try {
        const xsrfToken = await getCsrfToken();

        const response = await fetch(`/api/products/${product.id}`, {
            method: "DELETE",
            credentials: "include",
            headers: {
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
        });

        const data = await getResponseData(response);

        if (response.status === 401) {
            authenticated.value = false;
            return;
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                    `Failed to delete product. Status: ${response.status}`,
            );
        }

        products.value = products.value.filter(
            (item) => item.id !== product.id,
        );

        showNotification(`Product "${product.name}" deleted successfully.`);
    } catch (err) {
        console.error("Failed to delete product:", err);

        showNotification(err.message || "Unable to delete product.", "error");
    }
}

// ============================================================
// Bulk Delete
// ============================================================

async function bulkDelete(productsToDelete) {
    if (!productsToDelete || productsToDelete.length === 0) {
        return;
    }

    const confirmed = window.confirm(
        `Are you sure you want to delete ${productsToDelete.length} selected product(s)?`,
    );

    if (!confirmed) {
        return;
    }

    try {
        const xsrfToken = await getCsrfToken();

        const ids = productsToDelete.map((product) => product.id);

        const response = await fetch("/api/products/bulk-delete", {
            method: "DELETE",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
            body: JSON.stringify({
                selected_products: ids,
            }),
        });

        const data = await getResponseData(response);

        if (response.status === 401) {
            authenticated.value = false;
            return;
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                    `Failed to delete products. Status: ${response.status}`,
            );
        }

        products.value = products.value.filter(
            (product) => !ids.includes(product.id),
        );

        showNotification(
            `${productsToDelete.length} product(s) deleted successfully.`,
        );
    } catch (err) {
        console.error("Failed to bulk delete products:", err);

        showNotification(
            err.message || "Unable to delete selected products.",
            "error",
        );
    }
}

// ============================================================
// Bulk Update
// ============================================================

async function updateBulkProducts(updatedProducts) {
    if (!updatedProducts || updatedProducts.length === 0) {
        return;
    }

    try {
        const xsrfToken = await getCsrfToken();

        const payload = {
            products: updatedProducts.map((product) => ({
                id: product.id,
                name: product.name,
                description: product.description,
                price: Number(product.price),
                quantity: Number(product.quantity),
            })),
        };

        console.log("Bulk update payload:", payload);

        const response = await fetch("/api/products/bulk-update", {
            method: "PUT",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await getResponseData(response);

        if (response.status === 401) {
            authenticated.value = false;
            return;
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                    `Failed to update products. Status: ${response.status}`,
            );
        }

        await loadProducts();

        showList();

        showNotification(
            `${updatedProducts.length} product(s) updated successfully.`,
        );
    } catch (err) {
        console.error("Failed to bulk update products:", err);

        showNotification(
            err.message || "Unable to update selected products.",
            "error",
        );
    }
}

// ============================================================
// Initial Application Load
// ============================================================

onMounted(() => {
    checkAuthentication();
});
</script>

<template>
    <!-- ====================================================== -->
    <!-- Authentication Check -->
    <!-- ====================================================== -->

    <div
        v-if="checkingAuth"
        class="flex min-h-screen items-center justify-center bg-gray-50"
    >
        <div class="text-center">
            <div
                class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"
            ></div>

            <p class="mt-4 text-sm text-gray-500">Loading...</p>
        </div>
    </div>

    <!-- login and register -->

    <Login
        v-else-if="!authenticated && currentPage === 'login'"
        @login-success="handleLoginSuccess"
        @show-register="showRegister"
    />

    <Register
        v-else-if="!authenticated && currentPage === 'register'"
        @show-login="showLogin"
    />

    <!-- ====================================================== -->
    <!-- Authenticated Application -->
    <!-- ====================================================== -->

    <div v-else class="min-h-screen bg-gray-50">
        <!-- ================================================== -->
        <!-- Application Header -->
        <!-- ================================================== -->

        <header class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <div>
                    <h1 class="text-lg font-bold text-gray-900">
                        Product Management
                    </h1>
                </div>

                <!-- Logout -->
                <button
                    type="button"
                    @click="logout"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                >
                    Logout
                </button>
            </div>
        </header>

        <!-- ================================================== -->
        <!-- Main Application -->
        <!-- ================================================== -->

        <main class="mx-auto max-w-7xl px-6 py-8">
            <!-- ================================================== -->
            <!-- Notification -->
            <!-- ================================================== -->

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
                        class="flex items-start gap-3 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-lg"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                            :class="
                                notification.type === 'error'
                                    ? 'bg-red-100'
                                    : 'bg-green-100'
                            "
                        >
                            <svg
                                v-if="notification.type !== 'error'"
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

                            <svg
                                v-else
                                class="h-5 w-5 text-red-600"
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
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">
                                {{
                                    notification.type === "error"
                                        ? "Error"
                                        : "Success"
                                }}
                            </p>

                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ notification.message }}
                            </p>
                        </div>

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

            <!-- ================================================== -->
            <!-- Loading Products -->
            <!-- ================================================== -->

            <div v-if="loading" class="mx-auto max-w-7xl">
                <div class="flex min-h-[300px] items-center justify-center">
                    <div class="text-center">
                        <div
                            class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"
                        ></div>

                        <p class="mt-4 text-sm text-gray-500">
                            Loading products...
                        </p>
                    </div>
                </div>
            </div>

            <!-- ================================================== -->
            <!-- Product Error -->
            <!-- ================================================== -->

            <div v-else-if="error" class="mx-auto max-w-7xl">
                <div class="rounded-xl border border-red-200 bg-red-50 p-6">
                    <h2 class="text-sm font-semibold text-red-800">
                        Unable to load products
                    </h2>

                    <p class="mt-1 text-sm text-red-600">
                        {{ error }}
                    </p>

                    <button
                        type="button"
                        @click="loadProducts"
                        class="mt-4 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800"
                    >
                        Try Again
                    </button>
                </div>
            </div>

            <!-- ================================================== -->
            <!-- Product List -->
            <!-- ================================================== -->

            <ProductList
                v-else-if="currentPage === 'list'"
                :products="products"
                @add-product="showCreate"
                @view-product="showView"
                @edit-product="showEdit"
                @delete-product="deleteProduct"
                @bulk-delete="bulkDelete"
                @bulk-edit="showBulkEdit"
                @logout="logout"
            />

            <!-- ================================================== -->
            <!-- Product Create -->
            <!-- ================================================== -->

            <ProductCreate
                v-else-if="currentPage === 'create'"
                @cancel="showList"
                @product-created="addProduct"
            />

            <!-- ================================================== -->
            <!-- Product Edit -->
            <!-- ================================================== -->

            <ProductEdit
                v-else-if="currentPage === 'edit' && selectedProduct"
                :product="selectedProduct"
                @cancel="showList"
                @product-updated="updateProduct"
            />

            <!-- ================================================== -->
            <!-- Bulk Edit -->
            <!-- ================================================== -->

            <BulkEdit
                v-else-if="
                    currentPage === 'bulk-edit' &&
                    selectedProductsForBulkEdit.length > 0
                "
                :products="selectedProductsForBulkEdit"
                @cancel="showList"
                @products-updated="updateBulkProducts"
            />

            <!-- ================================================== -->
            <!-- Product View -->
            <!-- ================================================== -->

            <ProductView
                v-else-if="currentPage === 'view' && selectedProduct"
                :product="selectedProduct"
                @back="showList"
                @edit-product="showEdit"
            />
        </main>
    </div>
</template>
