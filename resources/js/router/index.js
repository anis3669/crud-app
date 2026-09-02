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
import Profile from "../components/profile/Profile.vue";
import Dashboard from "../components/dashboard/Dashboard.vue";
import Trash from "../components/products/Trash.vue";
import InventoryIndex from "../components/inventory/InventoryIndex.vue";
import InventoryHistory from "../components/inventory/InventoryHistory.vue";

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
            // / → /products
            {
                path: "",
                redirect: {
                    name: "products.index",
                },
            },

            // /products
            {
                path: "products",
                name: "products.index",
                component: ProductIndex,
            },

            // /products/create
            {
                path: "products/create",
                name: "products.create",
                component: ProductCreate,
            },

            // /products/bulk-edit
            {
                path: "products/bulk-edit",
                name: "products.bulk-edit",
                component: BulkEdit,
            },

            // /products/:id/edit
            {
                path: "products/:id/edit",
                name: "products.edit",
                component: ProductEdit,
            },

            // /products/:id
            {
                path: "products/:id",
                name: "products.view",
                component: ProductView,
            },
            // /profile
            {
                path: "profile",
                name: "profile",
                component: Profile,
            },
            // dashboard
            {
                path: "dashboard",
                name: "dashboard",
                component: Dashboard,
            },
            // trash
            {
                path: "/trash",
                name: "trash",
                component: Trash,
                meta: {
                    requiresAuth: true,
                },
            },
            {
                path: "/inventory",
                name: "inventory",
                component: InventoryIndex,
            },
            {
                path: "/inventory/history",
                name: "inventory.history",
                component: InventoryHistory,
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

// Authentication Guard

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
            name: "products.index",
        };
    }

    return true;
});

export default router;
