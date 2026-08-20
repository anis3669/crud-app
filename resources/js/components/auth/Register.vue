<script setup>
import { ref } from "vue";

const emit = defineEmits(["registered", "show-login"]);

const name = ref("");
const email = ref("");
const password = ref("");
const passwordConfirmation = ref("");

const loading = ref(false);
const error = ref("");

const nameError = ref("");
const emailError = ref("");
const passwordError = ref("");
const passwordConfirmationError = ref("");

async function register() {
    // We will add the registration API logic in the next step.
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
                    Create Your Account
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Register to start managing your products.
                </p>
            </div>

            <!-- Register Card -->
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

                <!-- Register Form -->
                <form
                    @submit.prevent="register"
                    class="space-y-5"
                >

                    <!-- Name -->
                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Name
                        </label>

                        <input
                            id="name"
                            v-model="name"
                            type="text"
                            name="name"
                            autocomplete="name"
                            placeholder="Enter your name"
                            :disabled="loading"
                            class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:ring-1"
                            :class="
                                nameError
                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                    : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                            "
                        />

                        <p
                            v-if="nameError"
                            class="mt-1.5 text-sm text-red-600"
                        >
                            {{ nameError }}
                        </p>
                    </div>

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
                            autocomplete="new-password"
                            placeholder="Minimum 8 characters"
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

                    <!-- Confirm Password -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Confirm Password
                        </label>

                        <input
                            id="password_confirmation"
                            v-model="passwordConfirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Re-enter your password"
                            :disabled="loading"
                            class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:ring-1"
                            :class="
                                passwordConfirmationError
                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                    : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900'
                            "
                        />

                        <p
                            v-if="passwordConfirmationError"
                            class="mt-1.5 text-sm text-red-600"
                        >
                            {{ passwordConfirmationError }}
                        </p>
                    </div>

                    <!-- Register Button -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    >
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
                            {{ loading ? "Creating Account..." : "Create Account" }}
                        </span>
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 border-t border-gray-100 pt-5 text-center">
                    <p class="text-sm text-gray-500">
                        Already have an account?

                        <button
                            type="button"
                            @click="emit('show-login')"
                            class="font-medium text-gray-900 underline-offset-4 hover:underline"
                        >
                            Sign in
                        </button>
                    </p>
                </div>

            </div>
        </div>
    </div>
</template>