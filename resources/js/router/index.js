import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

// Auth
import Login from "../components/auth/Login.vue";
import Register from "../components/auth/Register.vue";

// Layout
import Applayout from "../components/layout/Applayout.vue";

// Products
import ProductIndex from "../components/products/ProductIndex.vue";
import ProductCreate from "../components/products/ProductCreate.vue";
import ProductView from "../components/products/ProductView.vue";
import ProductEdit from "../components/products/ProductEdit.vue";
import BulkEdit from "../components/products/BulkEdit.vue";
import Trash from "../components/products/Trash.vue";

// Profile & Dashboard
import Profile from "../components/profile/Profile.vue";
import Dashboard from "../components/dashboard/Dashboard.vue";

// Inventory
import InventoryIndex from "../components/inventory/InventoryIndex.vue";
import InventoryHistory from "../components/inventory/InventoryHistory.vue";

// Invoices
import InvoiceIndex from "../components/invoices/InvoiceIndex.vue";
import InvoiceCreate from "../components/invoices/InvoiceCreate.vue";
import InvoiceView from "../components/invoices/InvoiceView.vue";

const routes = [
    // Guest Routes

    {
        path: "/login",
        name: "login",
        component: Login,
        meta: {
            guest: true,
        },
    },

    {
        path: "/register",
        name: "register",
        component: Register,
        meta: {
            guest: true,
        },
    },

    // Authenticated Routes

    {
        path: "/",
        component: Applayout,
        meta: {
            requiresAuth: true,
        },

        children: [
            // Dashboard

            {
                path: "dashboard",
                name: "dashboard",
                component: Dashboard,
            },

            // Default

            {
                path: "",
                redirect: {
                    name: "products.index",
                },
            },

            // Products

            {
                path: "products",
                name: "products.index",
                component: ProductIndex,
                meta: {
                    permission: "products.view",
                },
            },

            {
                path: "products/create",
                name: "products.create",
                component: ProductCreate,
                meta: {
                    permission: "products.create",
                },
            },

            {
                path: "products/bulk-edit",
                name: "products.bulk-edit",
                component: BulkEdit,
                meta: {
                    permission: "products.update",
                },
            },

            {
                path: "products/:id/edit",
                name: "products.edit",
                component: ProductEdit,
                meta: {
                    permission: "products.update",
                },
            },

            {
                path: "products/:id",
                name: "products.view",
                component: ProductView,
                meta: {
                    permission: "products.view",
                },
            },

            // Trash

            {
                path: "trash",
                name: "trash",
                component: Trash,
                meta: {
                    permission: "products.delete",
                },
            },

            // Inventory

            {
                path: "inventory",
                name: "inventory",
                component: InventoryIndex,
                meta: {
                    permission: "inventory.view",
                },
            },

            {
                path: "inventory/history",
                name: "inventory.history",
                component: InventoryHistory,
                meta: {
                    permission: "inventory.history",
                },
            },

            // Invoices

            {
                path: "invoices",
                name: "invoices.index",
                component: InvoiceIndex,
                meta: {
                    permission: "invoices.view",
                },
            },

            {
                path: "invoices/create",
                name: "invoices.create",
                component: InvoiceCreate,
                meta: {
                    permission: "invoices.create",
                },
            },

            {
                path: "invoices/:invoice",
                name: "invoices.show",
                component: InvoiceView,
                meta: {
                    permission: "invoices.view",
                },
            },

            // Profile

            {
                path: "profile",
                name: "profile",
                component: Profile,
            },
        ],
    },

    // Unknown Routes

    {
        path: "/:pathMatch(.*)*",
        redirect: {
            name: "products.index",
        },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,

    scrollBehavior() {
        return {
            top: 0,
        };
    },
});

// Authentication + Permission Guard

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    // Check authentication once
    if (!authStore.initialized) {
        await authStore.checkAuth();
    }

    // Protected route
    if (to.meta.requiresAuth && !authStore.authenticated) {
        return {
            name: "login",
        };
    }

    // Guest route while already logged in
    if (to.meta.guest && authStore.authenticated) {
        return {
            name: "dashboard",
        };
    }

    // Permission protected route
    const permission = to.meta.permission;

    if (permission && !authStore.can(permission)) {
        return {
            name: "dashboard",
        };
    }

    return true;
});

export default router;
