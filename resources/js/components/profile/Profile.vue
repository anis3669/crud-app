<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCamera } from "@fortawesome/free-solid-svg-icons";

const router = useRouter();
const authStore = useAuthStore();

const cameraIcon = faCamera;

// STATE

const user = computed(() => authStore.user);

const editing = ref(false);
const saving = ref(false);
const uploading = ref(false);
const deleting = ref(false);
const loading = ref(true);

const name = ref("");
const email = ref("");

const selectedFile = ref(null);
const previewUrl = ref(null);

const successMessage = ref("");
const errorMessage = ref("");

const fileInput = ref(null);

let messageTimer = null;

// PROFILE PICTURE


const profilePicture = computed(() => {
    if (user.value?.profile_picture_url) {
        return user.value.profile_picture_url;
    }

    if (user.value?.profile_picture) {
        if (
            user.value.profile_picture.startsWith("http://") ||
            user.value.profile_picture.startsWith("https://") ||
            user.value.profile_picture.startsWith("/storage/")
        ) {
            return user.value.profile_picture;
        }

        return `/storage/${user.value.profile_picture}`;
    }

    return null;
});

const userInitial = computed(() => {
    return user.value?.name?.charAt(0)?.toUpperCase() || "U";
});

// ==============================
// MESSAGES
// ==============================

function clearMessageTimer() {
    if (messageTimer) {
        clearTimeout(messageTimer);
        messageTimer = null;
    }
}

function showSuccess(message) {
    clearMessageTimer();

    successMessage.value = message;
    errorMessage.value = "";

    messageTimer = setTimeout(() => {
        successMessage.value = "";
    }, 3000);
}

function showError(message) {
    clearMessageTimer();

    errorMessage.value = message;
    successMessage.value = "";
}

// ==============================
// LOAD PROFILE
// ==============================

async function loadProfile() {
    loading.value = true;
    errorMessage.value = "";

    try {
        await authStore.loadProfile();

        name.value = user.value?.name || "";
        email.value = user.value?.email || "";
    } catch (error) {
        showError(
            error.response?.data?.message ||
                error.data?.message ||
                error.message ||
                "Unable to load profile.",
        );
    } finally {
        loading.value = false;
    }
}

// ==============================
// EDIT PROFILE
// ==============================

function startEditing() {
    name.value = user.value?.name || "";
    email.value = user.value?.email || "";

    editing.value = true;

    successMessage.value = "";
    errorMessage.value = "";
}

function cancelEditing() {
    name.value = user.value?.name || "";
    email.value = user.value?.email || "";

    editing.value = false;

    errorMessage.value = "";
}

// ==============================
// UPDATE PROFILE
// ==============================

async function saveProfile() {
    const trimmedName = name.value.trim();
    const trimmedEmail = email.value.trim();

    if (!trimmedName) {
        showError("Name is required.");
        return;
    }

    if (!trimmedEmail) {
        showError("Email is required.");
        return;
    }

    saving.value = true;

    successMessage.value = "";
    errorMessage.value = "";

    try {
        await authStore.updateProfile({
            name: trimmedName,
            email: trimmedEmail,
        });

        editing.value = false;

        showSuccess("Profile updated successfully.");
    } catch (error) {
        showError(
            error.response?.data?.message ||
                error.data?.message ||
                error.message ||
                "Unable to update profile.",
        );
    } finally {
        saving.value = false;
    }
}

// ==============================
// PROFILE IMAGE
// ==============================

function openFilePicker() {
    if (uploading.value || deleting.value) {
        return;
    }

    fileInput.value?.click();
}

function handleFileChange(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp",
    ];

    if (!allowedTypes.includes(file.type)) {
        showError("Please select a JPG, PNG, or WEBP image.");

        event.target.value = "";
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        showError("Profile picture must be smaller than 2MB.");

        event.target.value = "";
        return;
    }

    selectedFile.value = file;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    previewUrl.value = URL.createObjectURL(file);

    uploadProfilePicture();
}

async function uploadProfilePicture() {
    if (!selectedFile.value) {
        return;
    }

    uploading.value = true;

    successMessage.value = "";
    errorMessage.value = "";

    try {
        await authStore.uploadProfilePicture(selectedFile.value);

        showSuccess("Profile picture updated successfully.");

        selectedFile.value = null;

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
            previewUrl.value = null;
        }

        if (fileInput.value) {
            fileInput.value.value = "";
        }
    } catch (error) {
        showError(
            error.response?.data?.message ||
                error.data?.message ||
                error.message ||
                "Unable to upload profile picture.",
        );
    } finally {
        uploading.value = false;
    }
}

async function removeProfilePicture() {
    if (!user.value?.profile_picture) {
        return;
    }

    deleting.value = true;

    successMessage.value = "";
    errorMessage.value = "";

    try {
        await authStore.deleteProfilePicture();

        showSuccess("Profile picture removed successfully.");
    } catch (error) {
        showError(
            error.response?.data?.message ||
                error.data?.message ||
                error.message ||
                "Unable to remove profile picture.",
        );
    } finally {
        deleting.value = false;
    }
}

// ==============================
// NAVIGATION
// ==============================

function goBack() {
    router.push({
        name: "products.index",
    });
}

// ==============================
// LIFECYCLE
// ==============================

onMounted(async () => {
    await loadProfile();
});

onBeforeUnmount(() => {
    clearMessageTimer();

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <div
        class="min-h-full bg-gray-50 text-gray-900 transition-colors duration-200 dark:bg-gray-950 dark:text-gray-100"
    >
        <!-- ================================= -->
        <!-- PAGE HEADER -->
        <!-- ================================= -->

        <div
            class="border-b border-gray-200 bg-white transition-colors duration-200 dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-900 text-sm font-bold text-white dark:bg-white dark:text-gray-900"
                        >
                            P
                        </div>

                        <span
                            class="text-sm font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Account
                        </span>
                    </div>

                    <h1
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        My Profile
                    </h1>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Manage your personal information and account settings.
                    </p>
                </div>

                <button
                    type="button"
                    @click="goBack"
                    class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-750 dark:hover:text-white"
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
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>

                    Back to Products
                </button>
            </div>
        </div>

        <!-- ================================= -->
        <!-- MAIN -->
        <!-- ================================= -->

        <main
            class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
        >
            <!-- ================================= -->
            <!-- LOADING -->
            <!-- ================================= -->

            <div
                v-if="loading"
                class="rounded-2xl border border-gray-200 bg-white p-12 shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex flex-col items-center justify-center">
                    <div
                        class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900 dark:border-gray-700 dark:border-t-white"
                    ></div>

                    <p
                        class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Loading profile...
                    </p>
                </div>
            </div>

            <!-- ================================= -->
            <!-- CONTENT -->
            <!-- ================================= -->

            <template v-else>
                <!-- ================================= -->
                <!-- MESSAGES -->
                <!-- ================================= -->

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-1 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-1 opacity-0"
                >
                    <div
                        v-if="successMessage || errorMessage"
                        class="mb-5"
                    >
                        <!-- SUCCESS -->

                        <div
                            v-if="successMessage"
                            class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/60 dark:bg-emerald-950/40"
                        >
                            <div
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white"
                            >
                                <svg
                                    class="h-3 w-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>

                            <p
                                class="text-sm font-medium text-emerald-700 dark:text-emerald-400"
                            >
                                {{ successMessage }}
                            </p>
                        </div>

                        <!-- ERROR -->

                        <div
                            v-else
                            class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/60 dark:bg-red-950/40"
                        >
                            <div
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
                            >
                                !
                            </div>

                            <p
                                class="text-sm font-medium text-red-700 dark:text-red-400"
                            >
                                {{ errorMessage }}
                            </p>
                        </div>
                    </div>
                </Transition>

                <!-- ================================= -->
                <!-- PROFILE CARD -->
                <!-- ================================= -->

                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors duration-200 dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- BANNER -->

                    <div
                        class="relative h-28 overflow-hidden bg-gray-900 sm:h-36 dark:bg-black"
                    >
                        <div
                            class="absolute -right-16 -top-24 h-64 w-64 rounded-full border border-white/10"
                        ></div>

                        <div
                            class="absolute -bottom-28 -left-16 h-64 w-64 rounded-full border border-white/10"
                        ></div>

                        <div
                            class="absolute inset-0 bg-gradient-to-r from-black/40 via-transparent to-gray-700/20"
                        ></div>
                    </div>

                    <!-- PROFILE HEADER -->

                    <div class="px-5 pb-6 sm:px-8">
                        <div
                            class="-mt-10 flex flex-col gap-5 sm:-mt-12 sm:flex-row sm:items-end sm:justify-between"
                        >
                            <!-- AVATAR -->

                            <div class="relative w-fit">
                                <div
                                    class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-gray-900 text-3xl font-bold text-white shadow-lg dark:border-gray-900 dark:bg-gray-800 sm:h-28 sm:w-28 sm:text-4xl"
                                >
                                    <img
                                        v-if="previewUrl"
                                        :src="previewUrl"
                                        alt="Profile preview"
                                        class="h-full w-full object-cover"
                                    />

                                    <img
                                        v-else-if="profilePicture"
                                        :src="profilePicture"
                                        alt="Profile picture"
                                        class="h-full w-full object-cover"
                                    />

                                    <span v-else>
                                        {{ userInitial }}
                                    </span>
                                </div>

                                <!-- CAMERA -->

                                <button
                                    type="button"
                                    @click="openFilePicker"
                                    :disabled="uploading || deleting"
                                    title="Change profile picture"
                                    class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-xl border-2 border-white bg-gray-900 text-white shadow-md transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-900 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                                >
                                    <font-awesome-icon
                                        :icon="cameraIcon"
                                        class="text-sm"
                                    />
                                </button>

                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handleFileChange"
                                />
                            </div>

                            <!-- ACTIONS -->

                            <div
                                class="flex flex-wrap items-center gap-2"
                            >
                                <!-- REMOVE PHOTO -->

                                <button
                                    v-if="profilePicture"
                                    type="button"
                                    @click="removeProfilePicture"
                                    :disabled="deleting || uploading"
                                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900/60 dark:bg-gray-900 dark:text-red-400 dark:hover:bg-red-950/40"
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
                                            d="M6 7h12m-9 4v5m6-5v5M9 7V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m-9 0l.75 12.25A1.75 1.75 0 008.5 21h7a1.75 1.75 0 001.75-1.75L18 7M4.5 7h15"
                                        />
                                    </svg>

                                    {{
                                        deleting
                                            ? "Removing..."
                                            : "Remove Photo"
                                    }}
                                </button>

                                <!-- EDIT -->

                                <button
                                    v-if="!editing"
                                    type="button"
                                    @click="startEditing"
                                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-700 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
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
                                            d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3.75 19.5l1.037-4.5L16.862 3.487z"
                                        />
                                    </svg>

                                    Edit Profile
                                </button>
                            </div>
                        </div>

                        <!-- IDENTITY -->

                        <div class="mt-5">
                            <div
                                class="flex flex-wrap items-center gap-2"
                            >
                                <h2
                                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                                >
                                    {{ user?.name || "User" }}
                                </h2>

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    ></span>

                                    Active
                                </span>
                            </div>

                            <p
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ user?.email || "No email available" }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ================================= -->
                <!-- ACCOUNT INFORMATION -->
                <!-- ================================= -->

                <div
                    class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors duration-200 dark:border-gray-800 dark:bg-gray-900"
                >
                    <!-- HEADER -->

                    <div
                        class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-gray-800"
                    >
                        <h3
                            class="text-base font-semibold text-gray-900 dark:text-white"
                        >
                            Account Information
                        </h3>

                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Your basic account details.
                        </p>
                    </div>

                    <!-- ================================= -->
                    <!-- EDIT MODE -->
                    <!-- ================================= -->

                    <div
                        v-if="editing"
                        class="px-5 py-6 sm:px-6"
                    >
                        <div class="grid gap-5 sm:grid-cols-2">
                            <!-- NAME -->

                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Full Name
                                </label>

                                <input
                                    v-model="name"
                                    type="text"
                                    placeholder="Enter your name"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white"
                                />
                            </div>

                            <!-- EMAIL -->

                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Email Address
                                </label>

                                <input
                                    v-model="email"
                                    type="email"
                                    placeholder="Enter your email"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-white dark:focus:ring-white"
                                />
                            </div>
                        </div>

                        <!-- EDIT ACTIONS -->

                        <div
                            class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                        >
                            <button
                                type="button"
                                @click="cancelEditing"
                                :disabled="saving"
                                class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-750"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                @click="saveProfile"
                                :disabled="saving"
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                            >
                                {{ saving ? "Saving..." : "Save Changes" }}
                            </button>
                        </div>
                    </div>

                    <!-- ================================= -->
                    <!-- VIEW MODE -->
                    <!-- ================================= -->

                    <div
                        v-else
                        class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6"
                    >
                        <!-- NAME -->

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Full Name
                            </p>

                            <p
                                class="mt-3 text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ user?.name || "Not available" }}
                            </p>
                        </div>

                        <!-- EMAIL -->

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Email Address
                            </p>

                            <p
                                class="mt-3 break-all text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ user?.email || "Not available" }}
                            </p>
                        </div>

                        <!-- USER ID -->

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                User ID
                            </p>

                            <p
                                class="mt-3 text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                #{{ user?.id || "N/A" }}
                            </p>
                        </div>

                        <!-- STATUS -->

                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-800/50"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Account Status
                            </p>

                            <div
                                class="mt-3 flex items-center gap-2"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-emerald-500"
                                ></span>

                                <span
                                    class="text-sm font-semibold text-emerald-700 dark:text-emerald-400"
                                >
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================================= -->
                <!-- ACCOUNT SETTINGS -->
                <!-- ================================= -->

                <div
                    class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-colors duration-200 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-gray-800"
                    >
                        <h3
                            class="text-base font-semibold text-gray-900 dark:text-white"
                        >
                            Account Settings
                        </h3>

                        <p
                            class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        >
                            Manage your account information.
                        </p>
                    </div>

                    <div>
                        <div
                            class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Profile Information
                                </p>

                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Update your name and email address.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="startEditing"
                                class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                            >
                                Edit

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
                                        d="M9 18l6-6-6-6"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>
