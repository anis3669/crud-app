<script setup>
import { ref } from "vue";

const emit = defineEmits(["login-success", "show-register"]);

const email = ref("");
const password = ref("");

const loading = ref(false);
const error = ref("");

const emailError = ref("");
const passwordError = ref("");

//  Get Sanctum CSRF Token

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

    const token = cookie.substring("XSRF-TOKEN=".length);

    return decodeURIComponent(token);
}

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/
async function login() {
    error.value = "";
    emailError.value = "";
    passwordError.value = "";

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (!email.value.trim()) {
        emailError.value = "Please enter your email address.";
    }

    if (!password.value) {
        passwordError.value = "Please enter your password.";
    }

    if (emailError.value || passwordError.value) {
        return;
    }

    loading.value = true;

    try {
        /*
        |--------------------------------------------------------------------------
        | 1. Get Sanctum CSRF Cookie
        |--------------------------------------------------------------------------
        */
        const xsrfToken = await getCsrfToken();

        /*
        |--------------------------------------------------------------------------
        | 2. Login
        |--------------------------------------------------------------------------
        */
        const response = await fetch("/api/login", {
            method: "POST",
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-XSRF-TOKEN": xsrfToken,
            },
            body: JSON.stringify({
                email: email.value.trim(),
                password: password.value,
            }),
        });

        /*
        |--------------------------------------------------------------------------
        | Read Response
        |--------------------------------------------------------------------------
        */
        const contentType =
            response.headers.get("content-type") || "";

        const data = contentType.includes("application/json")
            ? await response.json()
            : {};

        /*
        |--------------------------------------------------------------------------
        | Login Failed
        |--------------------------------------------------------------------------
        */
        if (!response.ok) {
            throw new Error(
                data.message ||
                    "The email or password you entered is incorrect.",
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Login Successful
        |
        | App.vue receives this event and changes:
        |
        | isAuthenticated = true
        |
        | Then App.vue loads /api/products.
        |--------------------------------------------------------------------------
        */
        emit("login-success", data.user || null);

    } catch (err) {
        console.error("Login failed:", err);

        error.value =
            err.message ||
            "Unable to login. Please check your email and password.";
    } finally {
        loading.value = false;
    }
}
</script>
<template>
    <div class="min-h-screen bg-gray-50 px-4 py-12">

        <div class="mx-auto max-w-md">

            <!-- Header -->
            <div class="mb-8 text-center">

                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900"
                >
                    Welcome Back
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Sign in to your account to continue.
                </p>

            </div>

            <!-- Login Card -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"
            >

                <!-- General Error -->
                <div
                    v-if="error"
                    class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3"
                >
                    <p class="text-sm font-medium text-red-700">
                        {{ error }}
                    </p>
                </div>

                <!-- Login Form -->
                <form
                    @submit.prevent="login"
                    class="space-y-5"
                >

                    <!-- Email -->
                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Email Address
                        </label>

                        <input
                            id="email"
                            v-model="email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            placeholder="name@example.com"
                            :disabled="loading"
                            class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:ring-1"
                            :class="
                                emailError
                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                    : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                            "
                        />

                        <p
                            v-if="emailError"
                            class="mt-1.5 text-sm text-red-600"
                        >
                            {{ emailError }}
                        </p>

                    </div>

                    <!-- Password -->
                    <div>

                        <label
                            for="password"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Password
                        </label>

                        <input
                            id="password"
                            v-model="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            :disabled="loading"
                            class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:ring-1"
                            :class="
                                passwordError
                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                    : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                            "
                        />

                        <p
                            v-if="passwordError"
                            class="mt-1.5 text-sm text-red-600"
                        >
                            {{ passwordError }}
                        </p>

                    </div>

                    <!-- Login Button -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >

                        <!-- Spinner -->
                        <svg
                            v-if="loading"
                            class="h-4 w-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>

                        <span>
                            {{ loading ? "Signing In..." : "Sign In" }}
                        </span>

                    </button>

                </form>

                <!-- Register Link -->
                <div class="mt-6 border-t border-gray-100 pt-5 text-center">

                    <p class="text-sm text-gray-500">
                        Don't have an account?

                        <button
                            type="button"
                            @click="emit('show-register')"
                            class="font-medium text-gray-900 underline-offset-4 hover:underline"
                        >
                            Register
                        </button>
                    </p>

                </div>

            </div>

        </div>

    </div>
</template>