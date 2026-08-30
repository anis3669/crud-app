<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const showProfileMenu = ref(false);
const profileMenuRef = ref(null);

// Navigation

function goToDashboard() {
    showProfileMenu.value = false;

    router.push({
        name: "dashboard",
    });
}

function goToProducts() {
    showProfileMenu.value = false;

    router.push({
        name: "products.index",
    });
}

function goToProfile() {
    showProfileMenu.value = false;

    router.push({
        name: "profile",
    });
}

// Active Navigation

function isActive(name) {
    return route.name === name;
}

// Logout

async function logout() {
    showProfileMenu.value = false;

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

// Dropdown

function toggleProfileMenu() {
    showProfileMenu.value = !showProfileMenu.value;
}

function closeProfileMenu(event) {
    if (
        profileMenuRef.value &&
        !profileMenuRef.value.contains(event.target)
    ) {
        showProfileMenu.value = false;
    }
}

// Lifecycle

onMounted(() => {
    document.addEventListener("click", closeProfileMenu);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", closeProfileMenu);
});
</script>

<template>
    <nav
        class="sticky top-0 z-50 border-b border-gray-200 bg-white/95 shadow-sm backdrop-blur"
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-3 sm:px-6 lg:px-8"
        >
            <!-- Brand -->

            <button
                type="button"
                @click="goToDashboard"
                class="group flex shrink-0 items-center gap-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-200"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gray-900 shadow-sm transition duration-200 group-hover:scale-105 group-hover:shadow-md"
                >
                    <svg
                        class="h-5 w-5 text-white"
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

                <div class="hidden text-left sm:block">
                    <p
                        class="text-base font-bold leading-tight tracking-tight text-gray-900"
                    >
                        ProductApp
                    </p>

                    <p
                        class="mt-0.5 text-[10px] font-medium uppercase tracking-wider text-gray-400"
                    >
                        Inventory
                    </p>
                </div>
            </button>

            <!-- Desktop Navigation -->

            <div class="hidden items-center gap-1 md:flex">
                <!-- Dashboard -->

                <button
                    type="button"
                    @click="goToDashboard"
                    class="group inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition"
                    :class="
                        isActive('dashboard')
                            ? 'bg-gray-100 text-gray-900'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    <svg
                        class="h-4.5 w-4.5 transition"
                        :class="
                            isActive('dashboard')
                                ? 'text-gray-900'
                                : 'text-gray-400 group-hover:text-gray-700'
                        "
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

                    Dashboard
                </button>

                <!-- Products -->

                <button
                    type="button"
                    @click="goToProducts"
                    class="group inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition"
                    :class="
                        isActive('products.index') ||
                        isActive('products.create') ||
                        isActive('products.edit') ||
                        isActive('products.view')
                            ? 'bg-gray-100 text-gray-900'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    <svg
                        class="h-4.5 w-4.5 transition"
                        :class="
                            isActive('products.index') ||
                            isActive('products.create') ||
                            isActive('products.edit') ||
                            isActive('products.view')
                                ? 'text-gray-900'
                                : 'text-gray-400 group-hover:text-gray-700'
                        "
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

                    Products
                </button>
            </div>

            <!-- Right Side -->

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Mobile Products -->

                <button
                    type="button"
                    @click="goToProducts"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 md:hidden"
                    title="Products"
                    aria-label="Products"
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
                </button>

                <!-- Divider -->

                <div class="hidden h-7 w-px bg-gray-200 sm:block"></div>

                <!-- Profile -->

                <div
                    ref="profileMenuRef"
                    class="relative"
                >
                    <button
                        type="button"
                        @click="toggleProfileMenu"
                        class="group flex items-center gap-2 rounded-xl border border-transparent px-1.5 py-1.5 transition hover:border-gray-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 sm:px-2"
                        :aria-expanded="showProfileMenu"
                    >
                        <!-- Avatar -->

                        <div
                            class="relative h-9 w-9 shrink-0 overflow-hidden rounded-full bg-gray-900 ring-2 ring-white shadow-sm"
                        >
                            <img
                                v-if="
                                    authStore.user?.profile_picture_url
                                "
                                :src="
                                    authStore.user.profile_picture_url
                                "
                                :alt="
                                    authStore.user?.name || 'User'
                                "
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-sm font-semibold text-white"
                            >
                                {{
                                    authStore.user?.name
                                        ?.charAt(0)
                                        ?.toUpperCase() || "U"
                                }}
                            </div>
                        </div>

                        <!-- User Information -->

                        <div
                            class="hidden max-w-[150px] text-left lg:block"
                        >
                            <p
                                class="truncate text-sm font-semibold leading-tight text-gray-900"
                            >
                                {{
                                    authStore.user?.name ||
                                    "User"
                                }}
                            </p>

                            <p
                                class="mt-0.5 truncate text-[11px] text-gray-400"
                            >
                                {{
                                    authStore.user?.email ||
                                    ""
                                }}
                            </p>
                        </div>

                        <!-- Chevron -->

                        <svg
                            class="hidden h-4 w-4 text-gray-400 transition duration-200 lg:block"
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

                    <!-- Profile Dropdown -->

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
                            class="absolute right-0 z-50 mt-2 w-[280px] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl shadow-gray-200/50"
                        >
                            <!-- User Header -->

                            <div
                                class="border-b border-gray-100 bg-gray-50/70 px-4 py-4"
                            >
                                <div
                                    class="flex items-center gap-3"
                                >
                                    <div
                                        class="h-11 w-11 shrink-0 overflow-hidden rounded-full bg-gray-900 shadow-sm"
                                    >
                                        <img
                                            v-if="
                                                authStore.user
                                                    ?.profile_picture_url
                                            "
                                            :src="
                                                authStore.user
                                                    .profile_picture_url
                                            "
                                            :alt="
                                                authStore.user?.name ||
                                                'User'
                                            "
                                            class="h-full w-full object-cover"
                                        />

                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center text-sm font-semibold text-white"
                                        >
                                            {{
                                                authStore.user?.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase() ||
                                                "U"
                                            }}
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-sm font-semibold text-gray-900"
                                        >
                                            {{
                                                authStore.user?.name ||
                                                "User"
                                            }}
                                        </p>

                                        <p
                                            class="mt-0.5 truncate text-xs text-gray-500"
                                        >
                                            {{
                                                authStore.user?.email ||
                                                ""
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Dropdown Actions -->

                            <div class="p-2">
                                <!-- Dashboard -->

                                <button
                                    type="button"
                                    @click="goToDashboard"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition"
                                    :class="
                                        isActive('dashboard')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-700 hover:bg-gray-50'
                                    "
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg"
                                        :class="
                                            isActive('dashboard')
                                                ? 'bg-white text-gray-900 shadow-sm'
                                                : 'bg-gray-100 text-gray-500'
                                        "
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
                                                d="M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-4H3v4Zm10-10h8V3h-8v8Z"
                                            />
                                        </svg>
                                    </span>

                                    <span class="flex-1">
                                        Dashboard
                                    </span>

                                    <svg
                                        class="h-4 w-4 text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="m9 5 7 7-7 7"
                                        />
                                    </svg>
                                </button>

                                <!-- Products -->

                                <button
                                    type="button"
                                    @click="goToProducts"
                                    class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition"
                                    :class="
                                        isActive('products.index')
                                            ? 'bg-gray-100 text-gray-900'
                                            : 'text-gray-700 hover:bg-gray-50'
                                    "
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
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
                                                d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9ZM12 12l9-4.5M12 12 3 7.5M12 12v9"
                                            />
                                        </svg>
                                    </span>

                                    <span class="flex-1">
                                        Products
                                    </span>

                                    <svg
                                        class="h-4 w-4 text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="m9 5 7 7-7 7"
                                        />
                                    </svg>
                                </button>

                                <!-- Profile -->

                                <button
                                    type="button"
                                    @click="goToProfile"
                                    class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
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

                                    <span class="flex-1">
                                        Profile
                                    </span>

                                    <svg
                                        class="h-4 w-4 text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="m9 5 7 7-7 7"
                                        />
                                    </svg>
                                </button>

                                <!-- Divider -->

                                <div
                                    class="my-2 h-px bg-gray-100"
                                ></div>

                                <!-- Logout -->

                                <button
                                    type="button"
                                    @click="logout"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50"
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50"
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

                                    <span class="flex-1">
                                        Logout
                                    </span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </nav>
</template>
