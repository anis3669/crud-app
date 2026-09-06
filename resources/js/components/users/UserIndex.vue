<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import axios from "axios";

import { useUserStore } from "../../stores/user";
import { useRoleStore } from "../../stores/role";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseCard from "../common/BaseCard.vue";
import BaseModal from "../common/BaseModal.vue";

const userStore = useUserStore();
const roleStore = useRoleStore();
const authStore = useAuthStore();
const toastStore = useToastStore();

// Page state
const searchInput = ref("");
const selectedRole = ref("");

const showUserModal = ref(false);
const showDeleteModal = ref(false);

const editingUser = ref(null);
const userToDelete = ref(null);

const submitting = ref(false);
const deleting = ref(false);

const validationErrors = ref({});
const profilePreview = ref(null);
const profileFile = ref(null);

let searchTimeout = null;

// Form
const form = ref({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role_id: "",
    remove_profile_picture: false,
});

// Permissions
const canCreate = computed(() => authStore.can("users.create"));
const canUpdate = computed(() => authStore.can("users.update"));
const canDelete = computed(() => authStore.can("users.delete"));

// Users
const users = computed(() => userStore.users);
const roles = computed(() => roleStore.roles);

const loading = computed(() => userStore.loading);
const error = computed(() => userStore.error);

const currentPage = computed(() => Number(userStore.currentPage) || 1);
const lastPage = computed(() => Number(userStore.lastPage) || 1);
const total = computed(() => Number(userStore.total) || 0);
const perPage = computed(() => Number(userStore.perPage) || 10);

const hasPreviousPage = computed(() => currentPage.value > 1);
const hasNextPage = computed(() => currentPage.value < lastPage.value);

// Current page role counts
const adminUsers = computed(
    () => users.value.filter((user) => user.role?.slug === "admin").length,
);

const managerUsers = computed(
    () => users.value.filter((user) => user.role?.slug === "manager").length,
);

const staffUsers = computed(
    () => users.value.filter((user) => user.role?.slug === "staff").length,
);

// Pagination numbers
const firstUserNumber = computed(() => {
    if (!total.value) {
        return 0;
    }

    return (currentPage.value - 1) * perPage.value + 1;
});

const lastUserNumber = computed(() => {
    if (!total.value) {
        return 0;
    }

    return Math.min(currentPage.value * perPage.value, total.value);
});

// Pagination pages
const paginationPages = computed(() => {
    const pages = [];
    const current = currentPage.value;
    const last = lastPage.value;

    if (last <= 7) {
        for (let page = 1; page <= last; page++) {
            pages.push(page);
        }

        return pages;
    }

    pages.push(1);

    if (current > 4) {
        pages.push("...");
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let page = start; page <= end; page++) {
        pages.push(page);
    }

    if (current < last - 3) {
        pages.push("...");
    }

    pages.push(last);

    return pages;
});

// Modal
const modalTitle = computed(() =>
    editingUser.value ? "Edit User" : "Create User",
);

const submitButtonText = computed(() =>
    editingUser.value ? "Update User" : "Create User",
);

// Role label
function roleLabel(role) {
    return role?.name || "No role";
}

// Role badge
function roleBadgeClass(role) {
    switch (role?.slug) {
        case "admin":
            return "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300";

        case "manager":
            return "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300";

        case "staff":
            return "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300";

        default:
            return "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300";
    }
}

// Format date
function formatDate(date) {
    if (!date) {
        return "—";
    }

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return "—";
    }

    return new Intl.DateTimeFormat("en-NP", {
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(parsedDate);
}

// User initials
function userInitials(user) {
    if (!user?.name) {
        return "U";
    }

    return user.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join("");
}

// Profile picture
function profilePictureUrl(user) {
    return user?.profile_picture_url || null;
}

// Reset form
function resetForm() {
    if (profilePreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(profilePreview.value);
    }

    form.value = {
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        role_id: "",
        remove_profile_picture: false,
    };

    editingUser.value = null;
    validationErrors.value = {};
    profilePreview.value = null;
    profileFile.value = null;
}

// Load users
async function loadUsers(page = 1) {
    try {
        await userStore.fetchUsers(page, searchInput.value, selectedRole.value);
    } catch {
        // Store handles the error
    }
}

// Load roles
async function loadRoles() {
    try {
        await roleStore.fetchRoles();
    } catch {
        // Store handles the error
    }
}

// Search
function handleSearch() {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        loadUsers(1);
    }, 350);
}

// Clear search
function clearSearch() {
    searchInput.value = "";
    loadUsers(1);
}

// Role filter
function changeRole() {
    loadUsers(1);
}

// Previous page
function previousPage() {
    if (hasPreviousPage.value) {
        loadUsers(currentPage.value - 1);
    }
}

// Next page
function nextPage() {
    if (hasNextPage.value) {
        loadUsers(currentPage.value + 1);
    }
}

// Go to page
function goToPage(page) {
    if (
        typeof page !== "number" ||
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return;
    }

    loadUsers(page);
}

// Open create modal
function openCreateModal() {
    if (!canCreate.value) {
        return;
    }

    resetForm();

    const activeRole = roles.value.find((role) => role.is_active);

    if (activeRole) {
        form.value.role_id = activeRole.id;
    }

    showUserModal.value = true;
}

// Open edit modal
function openEditModal(user) {
    if (!canUpdate.value) {
        return;
    }

    resetForm();

    editingUser.value = user;

    form.value.name = user.name || "";
    form.value.email = user.email || "";
    form.value.role_id = user.role_id || user.role?.id || "";
    form.value.password = "";
    form.value.password_confirmation = "";
    form.value.remove_profile_picture = false;

    profilePreview.value = profilePictureUrl(user);

    showUserModal.value = true;
}

// Close user modal
function closeUserModal() {
    if (submitting.value) {
        return;
    }

    showUserModal.value = false;
    resetForm();
}

// Handle profile picture
function handleProfilePicture(event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    validationErrors.value.profile_picture = null;

    const allowedTypes = ["image/jpeg", "image/png", "image/jpg", "image/webp"];

    if (!allowedTypes.includes(file.type)) {
        validationErrors.value.profile_picture = [
            "The profile picture must be a JPEG, PNG, JPG, or WEBP image.",
        ];

        event.target.value = "";
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        validationErrors.value.profile_picture = [
            "The profile picture must not be larger than 5 MB.",
        ];

        event.target.value = "";
        return;
    }

    if (profilePreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(profilePreview.value);
    }

    profileFile.value = file;
    form.value.remove_profile_picture = false;
    profilePreview.value = URL.createObjectURL(file);
}

// Remove profile picture
function removeProfilePicture() {
    if (profilePreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(profilePreview.value);
    }

    profileFile.value = null;
    profilePreview.value = null;

    if (editingUser.value) {
        form.value.remove_profile_picture = true;
    }
}

// Field error
function fieldError(field) {
    const errors = validationErrors.value[field];

    if (!errors) {
        return "";
    }

    return Array.isArray(errors) ? errors[0] : errors;
}

// Validate form
function validateForm() {
    validationErrors.value = {};

    const errors = {};

    if (!form.value.name.trim()) {
        errors.name = ["Name is required."];
    }

    if (!form.value.email.trim()) {
        errors.email = ["Email is required."];
    }

    if (!form.value.role_id) {
        errors.role_id = ["Please select a role."];
    }

    if (!editingUser.value) {
        if (!form.value.password) {
            errors.password = ["Password is required."];
        }

        if (form.value.password && form.value.password.length < 8) {
            errors.password = ["Password must be at least 8 characters."];
        }

        if (form.value.password !== form.value.password_confirmation) {
            errors.password_confirmation = [
                "Password confirmation does not match.",
            ];
        }
    } else if (form.value.password) {
        if (form.value.password.length < 8) {
            errors.password = ["Password must be at least 8 characters."];
        }

        if (form.value.password !== form.value.password_confirmation) {
            errors.password_confirmation = [
                "Password confirmation does not match.",
            ];
        }
    }

    validationErrors.value = errors;

    return Object.keys(errors).length === 0;
}

// Build form data
function buildFormData() {
    const data = new FormData();

    data.append("name", form.value.name.trim());
    data.append("email", form.value.email.trim());
    data.append("role_id", String(form.value.role_id));

    if (form.value.password) {
        data.append("password", form.value.password);
        data.append("password_confirmation", form.value.password_confirmation);
    }

    if (profileFile.value) {
        data.append("profile_picture", profileFile.value);
    }

    if (editingUser.value && form.value.remove_profile_picture) {
        data.append("remove_profile_picture", "1");
    }

    return data;
}

// Submit user
async function submitUser() {
    if (submitting.value) {
        return;
    }

    if (!validateForm()) {
        return;
    }

    submitting.value = true;
    validationErrors.value = {};

    const isEditing = Boolean(editingUser.value);

    try {
        const formData = buildFormData();

        let response;

        if (isEditing) {
            formData.append("_method", "PUT");

            response = await axios.post(
                `/api/users/${editingUser.value.id}`,
                formData,
            );
        } else {
            response = await axios.post("/api/users", formData);
        }

        const message =
            response.data?.message ||
            (isEditing
                ? "User updated successfully."
                : "User created successfully.");

        toastStore.success(message);

        showUserModal.value = false;
        resetForm();

        await loadUsers(isEditing ? currentPage.value : 1);
    } catch (error) {
        validationErrors.value = userStore.getValidationErrors(error);

        const message =
            error.response?.data?.message ||
            userStore.getErrorMessage(
                error,
                isEditing ? "Failed to update user." : "Failed to create user.",
            );

        toastStore.error(message);
    } finally {
        submitting.value = false;
    }
}

// Open delete modal
function openDeleteModal(user) {
    if (!canDelete.value) {
        return;
    }

    if (user.id === authStore.user?.id) {
        return;
    }

    userToDelete.value = user;
    showDeleteModal.value = true;
}

// Close delete modal
function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
    userToDelete.value = null;
}

// Confirm delete
async function confirmDelete() {
    if (deleting.value || !userToDelete.value) {
        return;
    }

    deleting.value = true;

    try {
        const deletedId = userToDelete.value.id;

        const response = await userStore.deleteUser(deletedId);

        toastStore.success(response?.message || "User deleted successfully.");

        showDeleteModal.value = false;
        userToDelete.value = null;

        if (users.value.length === 1 && currentPage.value > 1) {
            await loadUsers(currentPage.value - 1);
        } else {
            await loadUsers(currentPage.value);
        }
    } catch (error) {
        toastStore.error(
            userStore.getErrorMessage(error, "Failed to delete user."),
        );
    } finally {
        deleting.value = false;
    }
}

// Refresh
async function refreshUsers() {
    await loadUsers(currentPage.value);
}

// Initial load
onMounted(async () => {
    searchInput.value = userStore.search || "";
    selectedRole.value = userStore.role || "";

    await Promise.all([loadUsers(1), loadRoles()]);
});

// Cleanup
onBeforeUnmount(() => {
    clearTimeout(searchTimeout);

    if (profilePreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(profilePreview.value);
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1
                    class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Users
                </h1>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage users, roles, and account access.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <BaseButton
                    variant="secondary"
                    :disabled="loading"
                    @click="refreshUsers"
                >
                    <svg
                        class="h-4 w-4"
                        :class="loading ? 'animate-spin' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h5M20 20v-5h-5M5.05 15A7 7 0 0017.9 17.9L20 15M19 9A7 7 0 006.1 6.1L4 9"
                        />
                    </svg>

                    Refresh
                </BaseButton>

                <BaseButton
                    v-if="canCreate"
                    variant="primary"
                    @click="openCreateModal"
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
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>

                    Add User
                </BaseButton>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <BaseCard padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Total Users
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            {{ total }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200"
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
                                stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m7-8a4 4 0 11-8 0 4 4 0 018 0zm6 4a3 3 0 10-6 0"
                            />
                        </svg>
                    </div>
                </div>
            </BaseCard>

            <BaseCard padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Admins
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            {{ adminUsers }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-300"
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
                                stroke-width="2"
                                d="M12 3l7 4v5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V7l7-4z"
                            />
                        </svg>
                    </div>
                </div>
            </BaseCard>

            <BaseCard padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Managers
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            {{ managerUsers }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300"
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
                                stroke-width="2"
                                d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"
                            />
                        </svg>
                    </div>
                </div>
            </BaseCard>

            <BaseCard padding="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Staff
                        </p>

                        <p
                            class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                        >
                            {{ staffUsers }}
                        </p>
                    </div>

                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
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
                                stroke-width="2"
                                d="M15 19a6 6 0 00-12 0m6-8a4 4 0 100-8 4 4 0 000 8zm9 8a5 5 0 00-5-5m2-3a3 3 0 100-6"
                            />
                        </svg>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Filters -->
        <BaseCard padding="p-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative flex-1">
                    <svg
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                        />
                    </svg>

                    <input
                        v-model="searchInput"
                        type="search"
                        placeholder="Search by name or email..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-700"
                        @input="handleSearch"
                    />

                    <button
                        v-if="searchInput"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xl leading-none text-gray-400 hover:text-gray-700 dark:hover:text-white"
                        @click="clearSearch"
                    >
                        ×
                    </button>
                </div>

                <select
                    v-model="selectedRole"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-gray-400 dark:focus:ring-gray-700"
                    @change="changeRole"
                >
                    <option value="">All Roles</option>

                    <option
                        v-for="role in roles"
                        :key="role.id"
                        :value="role.slug"
                    >
                        {{ role.name }}
                    </option>
                </select>
            </div>
        </BaseCard>

        <!-- Error -->
        <div
            v-if="error"
            class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <span>{{ error }}</span>

                <BaseButton variant="secondary" @click="refreshUsers">
                    Try Again
                </BaseButton>
            </div>
        </div>

        <!-- Desktop table -->
        <BaseCard padding="p-0" class="hidden overflow-hidden lg:block">
            <div class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead class="bg-gray-50 dark:bg-gray-800/70">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                User
                            </th>

                            <th
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Role
                            </th>

                            <th
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Status
                            </th>

                            <th
                                class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Joined
                            </th>

                            <th
                                class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody
                        class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900"
                    >
                        <template v-if="loading">
                            <tr v-for="row in 5" :key="`loading-${row}`">
                                <td colspan="5" class="px-6 py-5">
                                    <div
                                        class="h-10 animate-pulse rounded-lg bg-gray-100 dark:bg-gray-800"
                                    ></div>
                                </td>
                            </tr>
                        </template>

                        <template v-else-if="!users.length">
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div
                                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800"
                                    >
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m7-8a4 4 0 11-8 0 4 4 0 018 0zm6 4a3 3 0 10-6 0"
                                            />
                                        </svg>
                                    </div>

                                    <h3
                                        class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
                                    >
                                        No users found
                                    </h3>

                                    <p
                                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        Try changing your search or role filter.
                                    </p>
                                </td>
                            </tr>
                        </template>

                        <template v-else>
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                        >
                                            <img
                                                v-if="profilePictureUrl(user)"
                                                :src="profilePictureUrl(user)"
                                                :alt="user.name"
                                                class="h-full w-full object-cover"
                                            />

                                            <span v-else>
                                                {{ userInitials(user) }}
                                            </span>
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ user.name }}
                                            </p>

                                            <p
                                                class="truncate text-sm text-gray-500 dark:text-gray-400"
                                            >
                                                {{ user.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                        :class="roleBadgeClass(user.role)"
                                    >
                                        {{ roleLabel(user.role) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-green-500"
                                        ></span>
                                        Active
                                    </span>
                                </td>

                                <td
                                    class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ formatDate(user.created_at) }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <BaseButton
                                            v-if="canUpdate"
                                            variant="secondary"
                                            @click="openEditModal(user)"
                                        >
                                            Edit
                                        </BaseButton>

                                        <BaseButton
                                            v-if="canDelete"
                                            variant="danger"
                                            :disabled="
                                                user.id === authStore.user?.id
                                            "
                                            @click="openDeleteModal(user)"
                                        >
                                            Delete
                                        </BaseButton>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Desktop pagination -->
            <div
                v-if="total > perPage"
                class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
            >
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ firstUserNumber }}
                    </span>
                    to
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ lastUserNumber }}
                    </span>
                    of
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ total }}
                    </span>
                    users
                </p>

                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="!hasPreviousPage"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        @click="previousPage"
                    >
                        Previous
                    </button>

                    <template
                        v-for="(page, index) in paginationPages"
                        :key="`${page}-${index}`"
                    >
                        <span v-if="page === '...'" class="px-2 text-gray-400">
                            ...
                        </span>

                        <button
                            v-else
                            type="button"
                            class="min-w-9 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                page === currentPage
                                    ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                    : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                            "
                            @click="goToPage(page)"
                        >
                            {{ page }}
                        </button>
                    </template>

                    <button
                        type="button"
                        :disabled="!hasNextPage"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                        @click="nextPage"
                    >
                        Next
                    </button>
                </div>
            </div>
        </BaseCard>

        <!-- Mobile cards -->
        <div class="space-y-3 lg:hidden">
            <template v-if="loading">
                <div
                    v-for="row in 5"
                    :key="`mobile-loading-${row}`"
                    class="h-28 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"
                ></div>
            </template>

            <BaseCard
                v-else-if="!users.length"
                padding="p-6"
                class="text-center"
            >
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m7-8a4 4 0 11-8 0 4 4 0 018 0zm6 4a3 3 0 10-6 0"
                        />
                    </svg>
                </div>

                <h3 class="mt-4 font-semibold text-gray-900 dark:text-white">
                    No users found
                </h3>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try changing your search or role filter.
                </p>
            </BaseCard>

            <template v-else>
                <BaseCard v-for="user in users" :key="user.id" padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            >
                                <img
                                    v-if="profilePictureUrl(user)"
                                    :src="profilePictureUrl(user)"
                                    :alt="user.name"
                                    class="h-full w-full object-cover"
                                />

                                <span v-else>
                                    {{ userInitials(user) }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="truncate font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ user.name }}
                                </p>

                                <p
                                    class="truncate text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="roleBadgeClass(user.role)"
                        >
                            {{ roleLabel(user.role) }}
                        </span>
                    </div>

                    <div
                        class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-100 pt-4 dark:border-gray-700"
                    >
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Status
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-green-600 dark:text-green-400"
                            >
                                Active
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Joined
                            </p>

                            <p
                                class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                            >
                                {{ formatDate(user.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="canUpdate || canDelete" class="mt-4 flex gap-2">
                        <BaseButton
                            v-if="canUpdate"
                            variant="secondary"
                            class="flex-1"
                            @click="openEditModal(user)"
                        >
                            Edit
                        </BaseButton>

                        <BaseButton
                            v-if="canDelete"
                            variant="danger"
                            class="flex-1"
                            :disabled="user.id === authStore.user?.id"
                            @click="openDeleteModal(user)"
                        >
                            Delete
                        </BaseButton>
                    </div>
                </BaseCard>
            </template>

            <!-- Mobile pagination -->
            <BaseCard v-if="total > perPage" padding="p-4">
                <div class="flex items-center justify-between gap-3">
                    <BaseButton
                        variant="secondary"
                        :disabled="!hasPreviousPage"
                        @click="previousPage"
                    >
                        Previous
                    </BaseButton>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ currentPage }} / {{ lastPage }}
                    </span>

                    <BaseButton
                        variant="secondary"
                        :disabled="!hasNextPage"
                        @click="nextPage"
                    >
                        Next
                    </BaseButton>
                </div>
            </BaseCard>
        </div>

        <!-- Create/Edit modal -->
        <BaseModal
            :show="showUserModal"
            :title="modalTitle"
            size="lg"
            :close-on-backdrop="!submitting"
            @close="closeUserModal"
        >
            <form id="user-form" class="space-y-5" @submit.prevent="submitUser">
                <!-- Profile -->
                <div class="flex flex-col items-center gap-4 sm:flex-row">
                    <div
                        class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-xl font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <img
                            v-if="profilePreview"
                            :src="profilePreview"
                            alt="Profile preview"
                            class="h-full w-full object-cover"
                        />

                        <span v-else>
                            {{
                                form.name
                                    ? userInitials({
                                          name: form.name,
                                      })
                                    : "U"
                            }}
                        </span>
                    </div>

                    <div class="text-center sm:text-left">
                        <p
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Profile Picture
                        </p>

                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            JPEG, PNG, JPG or WEBP. Maximum 5 MB.
                        </p>

                        <div
                            class="mt-3 flex flex-wrap justify-center gap-2 sm:justify-start"
                        >
                            <label
                                class="inline-flex cursor-pointer items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                Choose Image

                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                    class="hidden"
                                    @change="handleProfilePicture"
                                />
                            </label>

                            <button
                                v-if="profilePreview"
                                type="button"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                @click="removeProfilePicture"
                            >
                                Remove
                            </button>
                        </div>

                        <p
                            v-if="fieldError('profile_picture')"
                            class="mt-2 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ fieldError("profile_picture") }}
                        </p>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label
                        for="user-name"
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Full Name
                    </label>

                    <input
                        id="user-name"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        placeholder="Enter full name"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition"
                        :class="
                            fieldError('name')
                                ? 'border-red-400 bg-red-50 text-gray-900 focus:ring-2 focus:ring-red-100 dark:border-red-500 dark:bg-red-900/10 dark:text-white'
                                : 'border-gray-300 bg-white text-gray-900 focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-700'
                        "
                    />

                    <p
                        v-if="fieldError('name')"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ fieldError("name") }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label
                        for="user-email"
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Email Address
                    </label>

                    <input
                        id="user-email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="user@example.com"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition"
                        :class="
                            fieldError('email')
                                ? 'border-red-400 bg-red-50 text-gray-900 focus:ring-2 focus:ring-red-100 dark:border-red-500 dark:bg-red-900/10 dark:text-white'
                                : 'border-gray-300 bg-white text-gray-900 focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-700'
                        "
                    />

                    <p
                        v-if="fieldError('email')"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ fieldError("email") }}
                    </p>
                </div>

                <!-- Role -->
                <div>
                    <label
                        for="user-role"
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Role
                    </label>

                    <select
                        id="user-role"
                        v-model="form.role_id"
                        class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition"
                        :class="
                            fieldError('role_id')
                                ? 'border-red-400 bg-red-50 text-gray-900 focus:ring-2 focus:ring-red-100 dark:border-red-500 dark:bg-red-900/10 dark:text-white'
                                : 'border-gray-300 bg-white text-gray-900 focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-700'
                        "
                    >
                        <option value="">Select a role</option>

                        <option
                            v-for="role in roles"
                            :key="role.id"
                            :value="role.id"
                            :disabled="!role.is_active"
                        >
                            {{ role.name }}
                            {{ !role.is_active ? " (Inactive)" : "" }}
                        </option>
                    </select>

                    <p
                        v-if="fieldError('role_id')"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ fieldError("role_id") }}
                    </p>
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="user-password"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Password

                            <span
                                v-if="editingUser"
                                class="font-normal text-gray-400"
                            >
                                (leave blank to keep)
                            </span>
                        </label>

                        <input
                            id="user-password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Minimum 8 characters"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition"
                            :class="
                                fieldError('password')
                                    ? 'border-red-400 bg-red-50 text-gray-900 focus:ring-2 focus:ring-red-100 dark:border-red-500 dark:bg-red-900/10 dark:text-white'
                                    : 'border-gray-300 bg-white text-gray-900 focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-700'
                            "
                        />

                        <p
                            v-if="fieldError('password')"
                            class="mt-1 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ fieldError("password") }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="user-password-confirmation"
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Confirm Password
                        </label>

                        <input
                            id="user-password-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                            class="w-full rounded-lg border px-3 py-2.5 text-sm outline-none transition"
                            :class="
                                fieldError('password_confirmation')
                                    ? 'border-red-400 bg-red-50 text-gray-900 focus:ring-2 focus:ring-red-100 dark:border-red-500 dark:bg-red-900/10 dark:text-white'
                                    : 'border-gray-300 bg-white text-gray-900 focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-gray-400 dark:focus:ring-gray-700'
                            "
                        />

                        <p
                            v-if="fieldError('password_confirmation')"
                            class="mt-1 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ fieldError("password_confirmation") }}
                        </p>
                    </div>
                </div>
            </form>

            <!-- Modal footer -->
            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="submitting"
                        @click="closeUserModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        type="submit"
                        form="user-form"
                        variant="primary"
                        :loading="submitting"
                    >
                        {{ submitButtonText }}
                    </BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Delete modal -->
        <BaseModal
            :show="showDeleteModal"
            title="Delete User"
            size="sm"
            :close-on-backdrop="!deleting"
            @close="closeDeleteModal"
        >
            <div class="space-y-4">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-8 0h10"
                        />
                    </svg>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Are you sure you want to delete

                        <strong
                            class="font-semibold text-gray-900 dark:text-white"
                        >
                            {{ userToDelete?.name }}
                        </strong>

                        ?
                    </p>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <BaseButton
                        type="button"
                        variant="secondary"
                        :disabled="deleting"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        type="button"
                        variant="danger"
                        :loading="deleting"
                        @click="confirmDelete"
                    >
                        Delete User
                    </BaseButton>
                </div>
            </template>
        </BaseModal>
    </div>
</template>
