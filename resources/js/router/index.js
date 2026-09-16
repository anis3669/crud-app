import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

const routes = [
    {
        path: "/login",
        name: "login",
        component: () => import("../components/auth/Login.vue"),
        meta: {
            guest: true,
        },
    },
    {
        path: "/register",
        name: "register",
        component: () => import("../components/auth/Register.vue"),
        meta: {
            guest: true,
        },
    },
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
            {
                path: "",
                redirect: {
                    name: "products.index",
                },
            },
            {
                path: "products",
                name: "products.index",
                component: () => import("../components/products/ProductIndex.vue"),
                meta: {
                    permission: "products.view",
                },
            },
            {
                path: "products/create",
                name: "products.create",
                component: () => import("../components/products/ProductCreate.vue"),
                meta: {
                    permission: "products.create",
                },
            },
            {
                path: "products/bulk-edit",
                name: "products.bulk-edit",
                component: () => import("../components/products/BulkEdit.vue"),
                meta: {
                    permission: "products.update",
                },
            },
            {
                path: "products/:id/edit",
                name: "products.edit",
                component: () => import("../components/products/ProductEdit.vue"),
                meta: {
                    permission: "products.update",
                },
            },
            {
                path: "products/:id",
                name: "products.view",
                component: () => import("../components/products/ProductView.vue"),
                meta: {
                    permission: "products.view",
                },
            },
            {
                path: "trash",
                name: "trash",
                component: () => import("../components/products/Trash.vue"),
                meta: {
                    permission: "products.delete",
                },
            },
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
                meta: {
                    permission: "inventory.view",
                },
            },
            {
                path: "inventory/history",
                name: "inventory.history",
                component: () => import("../components/inventory/InventoryHistory.vue"),
                meta: {
                    permission: "inventory.history",
                },
            },
            {
                path: "invoices",
                name: "invoices.index",
                component: () => import("../components/invoices/InvoiceIndex.vue"),
                meta: {
                    permission: "invoices.view",
                },
            },
            {
                path: "invoices/create",
                name: "invoices.create",
                component: () => import("../components/invoices/InvoiceCreate.vue"),
                meta: {
                    permission: "invoices.create",
                },
            },
            {
                path: "invoices/:invoice",
                name: "invoices.show",
                component: () => import("../components/invoices/InvoiceView.vue"),
                meta: {
                    permission: "invoices.view",
                },
            },
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

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    if (!authStore.initialized) {
        await authStore.checkAuth();
    }

    if (to.meta.requiresAuth && !authStore.authenticated) {
        return {
            name: "login",
        };
    }

    if (to.meta.guest && authStore.authenticated) {
        return {
            name: "dashboard",
        };
    }

    const permission = to.meta.permission;

    if (permission && !authStore.can(permission)) {
        return {
            name: "dashboard",
        };
    }

    return true;
});

export default router;
