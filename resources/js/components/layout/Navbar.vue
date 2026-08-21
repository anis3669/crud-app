<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const showProfileMenu = ref(false)
const profileMenuRef = ref(null)

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
*/

function goToProducts() {
    showProfileMenu.value = false

    router.push({
        name: 'products.index',
    })
}

function goToProfile() {
    showProfileMenu.value = false

    router.push({
        name: 'profile',
    })
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

async function logout() {
    showProfileMenu.value = false

    try {
        await authStore.logout()

        router.push({
            name: 'login',
        })
    } catch (error) {
        console.error('Logout failed:', error)

        // Even if the server logout fails,
        // the Pinia store clears the local auth state.
        router.push({
            name: 'login',
        })
    }
}

/*
|--------------------------------------------------------------------------
| Dropdown
|--------------------------------------------------------------------------
*/

function toggleProfileMenu() {
    showProfileMenu.value =
        !showProfileMenu.value
}

function closeProfileMenu(event) {
    if (
        profileMenuRef.value &&
        !profileMenuRef.value.contains(
            event.target
        )
    ) {
        showProfileMenu.value = false
    }
}

onMounted(() => {
    document.addEventListener(
        'click',
        closeProfileMenu
    )
})

onBeforeUnmount(() => {
    document.removeEventListener(
        'click',
        closeProfileMenu
    )
})
</script>

<template>
    <nav
        class="border-b border-gray-200 bg-white"
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >

            <!-- Logo -->
            <button
                type="button"
                @click="goToProducts"
                class="flex items-center gap-3"
            >
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-sm font-bold text-white"
                >
                    P
                </div>

                <span
                    class="text-lg font-bold tracking-tight text-gray-900"
                >
                    ProductApp
                </span>
            </button>

            <!-- Right Side -->
            <div
                class="flex items-center gap-4"
            >

                <!-- Products -->
                <button
                    type="button"
                    @click="goToProducts"
                    class="hidden text-sm font-medium text-gray-600 transition hover:text-gray-900 sm:block"
                >
                    Products
                </button>

                <!-- Profile -->
                <div
                    ref="profileMenuRef"
                    class="relative"
                >

                    <button
                        type="button"
                        @click="toggleProfileMenu"
                        class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-gray-100"
                    >

                        <!-- Avatar -->
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-sm font-semibold text-white"
                        >
                            {{
                                authStore.user?.name
                                    ?.charAt(0)
                                    ?.toUpperCase() ||
                                'U'
                            }}
                        </div>

                        <!-- User -->
                        <div
                            class="hidden text-left sm:block"
                        >
                            <p
                                class="text-sm font-semibold text-gray-900"
                            >
                                {{
                                    authStore.user?.name ||
                                    'User'
                                }}
                            </p>

                            <p
                                class="max-w-[150px] truncate text-xs text-gray-500"
                            >
                                {{
                                    authStore.user?.email ||
                                    ''
                                }}
                            </p>
                        </div>

                        <!-- Chevron -->
                        <svg
                            class="hidden h-4 w-4 text-gray-400 transition sm:block"
                            :class="{
                                'rotate-180':
                                    showProfileMenu,
                            }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>

                    </button>

                    <!-- Dropdown -->
                    <div
                        v-if="showProfileMenu"
                        class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
                    >

                        <!-- User Info -->
                        <div
                            class="border-b border-gray-100 px-4 py-4"
                        >
                            <div
                                class="flex items-center gap-3"
                            >

                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-900 text-sm font-semibold text-white"
                                >
                                    {{
                                        authStore.user?.name
                                            ?.charAt(0)
                                            ?.toUpperCase() ||
                                        'U'
                                    }}
                                </div>

                                <div
                                    class="min-w-0"
                                >
                                    <p
                                        class="truncate text-sm font-semibold text-gray-900"
                                    >
                                        {{
                                            authStore.user?.name ||
                                            'User'
                                        }}
                                    </p>

                                    <p
                                        class="truncate text-xs text-gray-500"
                                    >
                                        {{
                                            authStore.user?.email ||
                                            ''
                                        }}
                                    </p>
                                </div>

                            </div>
                        </div>

                        <!-- Dropdown Actions -->
                        <div class="p-1.5">

                            <!-- Profile -->
                            <button
                                type="button"
                                @click="goToProfile"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                <svg
                                    class="h-5 w-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M15 19a6 6 0 00-12 0m9-10a3 3 0 11-6 0 3 3 0 016 0zm3 1h6m-3-3v6"
                                    />
                                </svg>

                                Profile
                            </button>

                            <!-- Logout -->
                            <button
                                type="button"
                                @click="logout"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-red-600 transition hover:bg-red-50"
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
                                        d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4m-5-4l5-5-5-5m5 5H3"
                                    />
                                </svg>

                                Logout
                            </button>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </nav>
</template>