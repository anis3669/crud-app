<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";

const router = useRouter();
const auth = useAuthStore();

const email = ref("");
const password = ref("");

const error = ref("");
const emailError = ref("");
const passwordError = ref("");

// Clear errors
function clearErrors() {
    error.value = "";
    emailError.value = "";
    passwordError.value = "";
}

// Login
async function login() {
    clearErrors();

    if (!email.value.trim()) {
        emailError.value = "Please enter your email address.";
        return;
    }

    if (!password.value) {
        passwordError.value = "Please enter your password.";
        return;
    }

    try {
        await auth.login(
            email.value.trim(),
            password.value,
        );

        await router.push("/products");
    } catch (err) {
        console.error("Login failed:", err);

        if (err.status === 422) {
            const errors = err.data?.errors || {};

            emailError.value =
                errors.email?.[0] || "";

            passwordError.value =
                errors.password?.[0] || "";

            error.value =
                err.data?.message || "";

            return;
        }

        error.value =
            auth.error || "Unable to sign in.";
    }
}

// Go to register
function showRegister() {
    router.push("/register");
}
</script>

<template>
    <div
        class="min-h-screen bg-gray-50 px-4 py-12 transition-colors dark:bg-gray-950"
    >
        <div class="mx-auto max-w-md">

            <!-- Header -->
            <div class="mb-8 text-center">
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Welcome Back
                </h1>

                <p
                    class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                >
                    Sign in to your account to continue.
                </p>
            </div>

            <!-- Login Card -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >

                <!-- General Error -->
                <div
                    v-if="error"
                    class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/60 dark:bg-red-950/40"
                >
                    <p
                        class="text-sm font-medium text-red-700 dark:text-red-400"
                    >
                        {{ error }}
                    </p>
                </div>

                <!-- Login Form -->
                <form
                    class="space-y-5"
                    @submit.prevent="login"
                >

                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200"
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
                            :disabled="auth.loading"
                            class="block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-950 dark:text-white dark:placeholder:text-gray-600"
                            :class="
                                emailError
                                    ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                    : 'border-gray-300 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-700 dark:focus:border-gray-400 dark:focus:ring-gray-400'
                            "
                        />

                        <p
                            v-if="emailError"
                            class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ emailError }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200"
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
                            :disabled="auth.loading"
                            class="block w-full rounded-lg border bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-950 dark:text-white dark:placeholder:text-gray-600"
                            :class="
                                passwordError
                                    ? 'border-red-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-red-500'
                                    : 'border-gray-300 focus:border-gray-900 focus:ring-1 focus:ring-gray-400 dark:border-gray-700 dark:focus:border-gray-400 dark:focus:ring-gray-400'
                            "
                        />

                        <p
                            v-if="passwordError"
                            class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ passwordError }}
                        </p>
                    </div>

                    <!-- Login Button -->
                    <button
                        type="submit"
                        :disabled="auth.loading"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 dark:focus:ring-gray-400 dark:focus:ring-offset-gray-900"
                    >
                        <!-- Spinner -->
                        <svg
                            v-if="auth.loading"
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
                            {{
                                auth.loading
                                    ? "Signing In..."
                                    : "Sign In"
                            }}
                        </span>
                    </button>
                </form>

                <!-- Register Link -->
                <div
                    class="mt-6 border-t border-gray-100 pt-5 text-center dark:border-gray-800"
                >
                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Don't have an account?

                        <button
                            type="button"
                            @click="showRegister"
                            class="font-medium text-gray-900 underline-offset-4 transition hover:underline dark:text-white"
                        >
                            Create an account
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
