import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

<<<<<<< HEAD
const routes = [
    {
        path: "/login",
        name: "login",
        component: () => import("../components/auth/Login.vue"),
=======
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
import UserIndex from "../components/users/UserIndex.vue";

// Profile & Dashboard
import Profile from "../components/profile/Profile.vue";
import Dashboard from "../components/dashboard/Dashboard.vue";

// categories
import CategoryIndex from "../components/categories/CategoryIndex.vue";
// suppliers
import SupplierIndex from "../components/suppliers/SupplierIndex.vue";

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
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
        meta: {
            guest: true,
        },
    },
<<<<<<< HEAD
    {
        path: "/register",
        name: "register",
        component: () => import("../components/auth/Register.vue"),
=======

    {
        path: "/register",
        name: "register",
        component: Register,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
        meta: {
            guest: true,
        },
    },
<<<<<<< HEAD
    {
        path: "/",
        component: () => import("../components/layout/Applayout.vue"),
        meta: {
            requiresAuth: true,
        },
        children: [
            {
                path: "dashboard",
                name: "dashboard",
                component: () => import("../components/dashboard/Dashboard.vue"),
            },
=======

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

>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
            {
                path: "",
                redirect: {
                    name: "products.index",
                },
            },
<<<<<<< HEAD
            {
                path: "products",
                name: "products.index",
                component: () => import("../components/products/ProductIndex.vue"),
=======

            // Products

            {
                path: "products",
                name: "products.index",
                component: ProductIndex,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "products.view",
                },
            },
<<<<<<< HEAD
            {
                path: "products/create",
                name: "products.create",
                component: () => import("../components/products/ProductCreate.vue"),
=======

            {
                path: "products/create",
                name: "products.create",
                component: ProductCreate,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "products.create",
                },
            },
<<<<<<< HEAD
            {
                path: "products/bulk-edit",
                name: "products.bulk-edit",
                component: () => import("../components/products/BulkEdit.vue"),
=======

            {
                path: "products/bulk-edit",
                name: "products.bulk-edit",
                component: BulkEdit,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "products.update",
                },
            },
<<<<<<< HEAD
            {
                path: "products/:id/edit",
                name: "products.edit",
                component: () => import("../components/products/ProductEdit.vue"),
=======

            {
                path: "products/:id/edit",
                name: "products.edit",
                component: ProductEdit,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "products.update",
                },
            },
<<<<<<< HEAD
            {
                path: "products/:id",
                name: "products.view",
                component: () => import("../components/products/ProductView.vue"),
=======

            {
                path: "products/:id",
                name: "products.view",
                component: ProductView,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "products.view",
                },
            },
<<<<<<< HEAD
            {
                path: "trash",
                name: "trash",
                component: () => import("../components/products/Trash.vue"),
=======

            // Trash

            {
                path: "trash",
                name: "trash",
                component: Trash,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "products.delete",
                },
            },
<<<<<<< HEAD
            {
                path: "categories",
                name: "categories.index",
                component: () => import("../components/categories/CategoryIndex.vue"),
                meta: {
                    permission: "categories.view",
                },
            },
            {
                path: "suppliers",
                name: "suppliers.index",
                component: () => import("../components/suppliers/SupplierIndex.vue"),
                meta: {
                    permission: "suppliers.view",
                },
            },
            {
                path: "inventory",
                name: "inventory",
                component: () => import("../components/inventory/InventoryIndex.vue"),
=======
            // Categories
            {
                path: "categories",
                name: "categories.index",
                component: CategoryIndex,
                meta: { permission: "categories.view" },
            },
            // Suppliers
            {
                path: "suppliers",
                name: "suppliers.index",
                component: SupplierIndex,
                meta: { permission: "suppliers.view" },
            },

            // Inventory

            {
                path: "inventory",
                name: "inventory",
                component: InventoryIndex,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "inventory.view",
                },
            },
<<<<<<< HEAD
            {
                path: "inventory/history",
                name: "inventory.history",
                component: () => import("../components/inventory/InventoryHistory.vue"),
=======

            {
                path: "inventory/history",
                name: "inventory.history",
                component: InventoryHistory,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "inventory.history",
                },
            },
<<<<<<< HEAD
            {
                path: "invoices",
                name: "invoices.index",
                component: () => import("../components/invoices/InvoiceIndex.vue"),
=======

            // Invoices

            {
                path: "invoices",
                name: "invoices.index",
                component: InvoiceIndex,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "invoices.view",
                },
            },
<<<<<<< HEAD
            {
                path: "invoices/create",
                name: "invoices.create",
                component: () => import("../components/invoices/InvoiceCreate.vue"),
=======

            {
                path: "invoices/create",
                name: "invoices.create",
                component: InvoiceCreate,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "invoices.create",
                },
            },
<<<<<<< HEAD
            {
                path: "invoices/:invoice",
                name: "invoices.show",
                component: () => import("../components/invoices/InvoiceView.vue"),
=======

            {
                path: "invoices/:invoice",
                name: "invoices.show",
                component: InvoiceView,
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
                meta: {
                    permission: "invoices.view",
                },
            },
<<<<<<< HEAD
            {
                path: "profile",
                name: "profile",
                component: () => import("../components/profile/Profile.vue"),
            },
            {
                path: "users",
                name: "users.index",
                component: () => import("../components/users/UserIndex.vue"),
                meta: {
                    permission: "users.view",
                },
            },
        ],
    },
=======

            // Profile

            {
                path: "profile",
                name: "profile",
                component: Profile,
            },
            // users
            {
                path: "users",
                name: "users.index",
                component: UserIndex,
                meta: { permission: "users.view" },
            },
        ],
    },

    // Unknown Routes

>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
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
<<<<<<< HEAD
=======

>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
    scrollBehavior() {
        return {
            top: 0,
        };
    },
});

<<<<<<< HEAD
router.beforeEach(async (to) => {
    const authStore = useAuthStore();

=======
// Authentication + Permission Guard

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    // Check authentication once
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
    if (!authStore.initialized) {
        await authStore.checkAuth();
    }

<<<<<<< HEAD
=======
    // Protected route
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
    if (to.meta.requiresAuth && !authStore.authenticated) {
        return {
            name: "login",
        };
    }

<<<<<<< HEAD
=======
    // Guest route while already logged in
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
    if (to.meta.guest && authStore.authenticated) {
        return {
            name: "dashboard",
        };
    }

<<<<<<< HEAD
=======
    // Permission protected route
>>>>>>> 221143f2b39ac51f31f45ba5cadb756a51ec9357
    const permission = to.meta.permission;

    if (permission && !authStore.can(permission)) {
        return {
            name: "dashboard",
        };
    }

    return true;
});

export default router;
