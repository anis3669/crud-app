<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useThemeStore } from "../../stores/theme";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const themeStore = useThemeStore();

const sidebarOpen = ref(false);
const showProfileMenu = ref(false);
const profileMenuRef = ref(null);

// Navigation

function closeSidebar() {
    sidebarOpen.value = false;
}

function goToDashboard() {
    closeSidebar();

    router.push({
        name: "dashboard",
    });
}

function goToProducts() {
    closeSidebar();

    router.push({
        name: "products.index",
    });
}

function goToCategories() {
    closeSidebar();

    router.push({
        name: "categories.index",
    });
}

function goToSuppliers() {
    closeSidebar();

    router.push({
        name: "suppliers.index",
    });
}

function goToInventory() {
    closeSidebar();

    router.push({
        name: "inventory",
    });
}

function goToInventoryHistory() {
    closeSidebar();

    router.push({
        name: "inventory.history",
    });
}

function goToInvoices() {
    closeSidebar();

    router.push({
        name: "invoices.index",
    });
}

function goToProfile() {
    showProfileMenu.value = false;
    closeSidebar();

    router.push({
        name: "profile",
    });
}

// Active navigation

function isActive(name) {
    return route.name === name;
}

function isProductsActive() {
    return [
        "products.index",
        "products.create",
        "products.edit",
        "products.view",
        "products.bulk-edit",
        "trash",
    ].includes(route.name);
}

function isCategoriesActive() {
    return route.name === "categories.index";
}

function isSuppliersActive() {
    return route.name === "suppliers.index";
}

function isInventoryActive() {
    return ["inventory", "inventory.history"].includes(route.name);
}

function isInvoicesActive() {
    return ["invoices.index", "invoices.create", "invoices.show"].includes(
        route.name,
    );
}

// Permissions

function can(permission) {
    return authStore.can(permission);
}

// Logout

async function logout() {
    showProfileMenu.value = false;
    closeSidebar();

    try {
        await authStore.logout();

        router.push({
            name: "login",
        });
    } catch (error) {
        console.error("Logout failed:", error);

        router.push({
            name: "login",
        });
    }
}

// Profile menu

function toggleProfileMenu() {
    showProfileMenu.value = !showProfileMenu.value;
}

function closeProfileMenu(event) {
    if (profileMenuRef.value && !profileMenuRef.value.contains(event.target)) {
        showProfileMenu.value = false;
    }
}

// Close sidebar when route changes

function handleRouteChange() {
    sidebarOpen.value = false;
    showProfileMenu.value = false;
}

onMounted(() => {
    document.addEventListener("click", closeProfileMenu);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeProfileMenu);
});
</script>

<template>
    <div>
        <!-- Mobile Header -->

        <header
            class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-gray-200 bg-white/95 px-4 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-gray-950/95 lg:hidden"
        >
            <!-- Menu Button -->

            <button
                type="button"
                @click="sidebarOpen = true"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white dark:focus:ring-gray-700"
                aria-label="Open navigation"
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
                        stroke-width="1.8"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>

            <!-- Mobile Brand -->

            <button
                type="button"
                @click="goToDashboard"
                class="flex items-center gap-2.5"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gray-900 shadow-sm dark:bg-white"
                >
                    <svg
                        class="h-5 w-5 text-white dark:text-gray-900"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M3 7l9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"
                        />
                    </svg>
                </div>

                <span
                    class="text-base font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    ProductApp
                </span>
            </button>

            <!-- Mobile Profile -->

            <button
                type="button"
                @click="goToProfile"
                class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-gray-900 text-sm font-semibold text-white shadow-sm dark:bg-white dark:text-gray-900"
                aria-label="Profile"
            >
                <img
                    v-if="authStore.user?.profile_picture_url"
                    :src="authStore.user.profile_picture_url"
                    :alt="authStore.user?.name || 'User'"
                    class="h-full w-full object-cover"
                />

                <span v-else>
                    {{ authStore.user?.name?.charAt(0)?.toUpperCase() || "U" }}
                </span>
            </button>
        </header>

        <!-- Mobile Overlay -->

        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarOpen"
                @click="closeSidebar"
                class="fixed inset-0 z-40 bg-black/40 backdrop-blur-[2px] lg:hidden"
            ></div>
        </Transition>

        <!-- Sidebar -->

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-gray-200 bg-white transition-transform duration-300 dark:border-gray-800 dark:bg-gray-950 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand -->

            <div
                class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5 dark:border-gray-800"
            >
                <button
                    type="button"
                    @click="goToDashboard"
                    class="group flex items-center gap-3"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-900 shadow-sm transition duration-200 group-hover:scale-105 dark:bg-white"
                    >
                        <svg
                            class="h-5 w-5 text-white dark:text-gray-900"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M3 7l9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10"
                            />
                        </svg>
                    </div>

                    <div class="text-left">
                        <p
                            class="text-base font-bold tracking-tight text-gray-900 dark:text-white"
                        >
                            ProductApp
                        </p>

                        <p
                            class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                        >
                            Inventory
                        </p>
                    </div>
                </button>

                <!-- Mobile Close -->

                <button
                    type="button"
                    @click="closeSidebar"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white lg:hidden"
                    aria-label="Close navigation"
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
                            stroke-width="1.8"
                            d="M6 6l12 12M18 6 6 18"
                        />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->

            <div class="flex-1 overflow-y-auto px-3 py-5">
                <!-- Main -->

                <p
                    class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                >
                    Main
                </p>

                <!-- Dashboard -->

                <button
                    v-if="authStore.authenticated"
                    type="button"
                    @click="goToDashboard"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isActive('dashboard')
                            ? 'bg-gray-900 text-white shadow-sm dark:bg-white dark:text-gray-900'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isActive('dashboard')
                                ? 'bg-white/10 text-white dark:bg-gray-900/10 dark:text-gray-900'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-4H3v4Zm10-10h8V3h-8v8Z"
                            />
                        </svg>
                    </span>

                    <span>Dashboard</span>
                </button>

                <!-- Inventory Section -->

                <p
                    class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                >
                    Inventory
                </p>

                <!-- Products -->

                <button
                    v-if="can('products.view')"
                    type="button"
                    @click="goToProducts"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isProductsActive()
                            ? 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isProductsActive()
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9ZM12 12l9-4.5M12 12 3 7.5M12 12v9"
                            />
                        </svg>
                    </span>

                    <span>Products</span>
                </button>

                <!-- Categories -->

                <button
                    v-if="can('categories.view')"
                    type="button"
                    @click="goToCategories"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isCategoriesActive()
                            ? 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isCategoriesActive()
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M4 6h16M4 12h16M4 18h16M8 6v12"
                            />
                        </svg>
                    </span>

                    <span>Categories</span>
                </button>

                <!-- Suppliers -->

                <button
                    v-if="can('suppliers.view')"
                    type="button"
                    @click="goToSuppliers"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isSuppliersActive()
                            ? 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isSuppliersActive()
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M8 9h1m6 0h1m-8 4h1m6 0h1"
                            />
                        </svg>
                    </span>

                    <span>Suppliers</span>
                </button>

                <!-- Stock -->

                <button
                    v-if="can('inventory.view')"
                    type="button"
                    @click="goToInventory"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isInventoryActive()
                            ? 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isInventoryActive()
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M4 7h16M4 12h16M4 17h16M7 4v16"
                            />
                        </svg>
                    </span>

                    <span>Stock</span>
                </button>

                <!-- Stock History -->

                <button
                    v-if="can('inventory.history')"
                    type="button"
                    @click="goToInventoryHistory"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isActive('inventory.history')
                            ? 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isActive('inventory.history')
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M12 8v4l2.5 2.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </span>

                    <span>Stock History</span>
                </button>

                <!-- Sales -->

                <p
                    class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500"
                >
                    Sales
                </p>

                <!-- Invoices -->

                <button
                    v-if="can('invoices.view')"
                    type="button"
                    @click="goToInvoices"
                    class="group mb-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        isInvoicesActive()
                            ? 'bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-white'
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white'
                    "
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            isInvoicesActive()
                                ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white'
                                : 'text-gray-400 group-hover:text-gray-700 dark:text-gray-500 dark:group-hover:text-gray-300'
                        "
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
                                stroke-width="1.8"
                                d="M6 3h12a1 1 0 0 1 1 1v16l-7-3-7 3V4a1 1 0 0 1 1-1Zm3 5h6M9 12h6"
                            />
                        </svg>
                    </span>

                    <span>Invoices</span>
                </button>
            </div>

            <!-- Bottom Area -->

            <div
                class="shrink-0 border-t border-gray-200 p-3 dark:border-gray-800"
            >
                <!-- Theme -->

                <button
                    type="button"
                    @click="themeStore.toggleTheme"
                    class="mb-2 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-900 dark:hover:text-white"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-400"
                    >
                        <!-- Sun -->

                        <svg
                            v-if="themeStore.dark"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M12 3v1.5M12 19.5V21M4.22 4.22l1.06 1.06M18.72 18.72l1.06 1.06M3 12h1.5M19.5 12H21M4.22 19.78l1.06-1.06M18.72 5.28l1.06-1.06M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"
                            />
                        </svg>

                        <!-- Moon -->

                        <svg
                            v-else
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"
                            />
                        </svg>
                    </span>

                    <span>
                        {{ themeStore.dark ? "Light mode" : "Dark mode" }}
                    </span>
                </button>

                <!-- Profile -->

                <div ref="profileMenuRef" class="relative">
                    <button
                        type="button"
                        @click="toggleProfileMenu"
                        class="flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-left transition hover:border-gray-200 hover:bg-gray-50 dark:hover:border-gray-800 dark:hover:bg-gray-900"
                        :aria-expanded="showProfileMenu"
                    >
                        <div
                            class="h-9 w-9 shrink-0 overflow-hidden rounded-full bg-gray-900 text-sm font-semibold text-white shadow-sm dark:bg-white dark:text-gray-900"
                        >
                            <img
                                v-if="authStore.user?.profile_picture_url"
                                :src="authStore.user.profile_picture_url"
                                :alt="authStore.user?.name || 'User'"
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                {{
                                    authStore.user?.name
                                        ?.charAt(0)
                                        ?.toUpperCase() || "U"
                                }}
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ authStore.user?.name || "User" }}
                            </p>

                            <p
                                class="truncate text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                {{ authStore.role?.name || "Account" }}
                            </p>
                        </div>

                        <svg
                            class="h-4 w-4 shrink-0 text-gray-400 transition duration-200"
                            :class="{
                                'rotate-180': showProfileMenu,
                            }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="m6 9 6 6 6-6"
                            />
                        </svg>
                    </button>

                    <!-- Profile Menu -->

                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="translate-y-1 scale-95 opacity-0"
                        enter-to-class="translate-y-0 scale-100 opacity-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="translate-y-0 scale-100 opacity-100"
                        leave-to-class="translate-y-1 scale-95 opacity-0"
                    >
                        <div
                            v-if="showProfileMenu"
                            class="absolute bottom-full left-0 right-0 z-50 mb-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900"
                        >
                            <!-- Profile -->

                            <button
                                type="button"
                                @click="goToProfile"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
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
                                            stroke-width="1.8"
                                            d="M15 19a6 6 0 0 0-12 0m9-10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 1h6m-3-3v6"
                                        />
                                    </svg>
                                </span>

                                Profile
                            </button>

                            <div
                                class="mx-3 h-px bg-gray-100 dark:bg-gray-800"
                            ></div>

                            <!-- Logout -->

                            <button
                                type="button"
                                @click="logout"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 dark:bg-red-950/30"
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
                                            stroke-width="1.8"
                                            d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-5-4 5-5-5-5m5 5H3"
                                        />
                                    </svg>
                                </span>

                                Logout
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </aside>
    </div>
</template>
