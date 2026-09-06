<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

import { useSupplierStore } from "../../stores/supplier";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseModal from "../common/BaseModal.vue";
import BaseCard from "../common/BaseCard.vue";

const supplierStore = useSupplierStore();
const authStore = useAuthStore();
const toastStore = useToastStore();

// Stores
const suppliers = computed(() => supplierStore.suppliers);
const loading = computed(() => supplierStore.loading);
const error = computed(() => supplierStore.error);

const currentPage = computed(() => supplierStore.currentPage);
const lastPage = computed(() => supplierStore.lastPage);
const total = computed(() => supplierStore.total);
const perPage = computed(() => supplierStore.perPage);

const search = computed(() => supplierStore.search);

// Permissions
const canCreate = computed(() =>
    authStore.can("suppliers.create")
);

const canUpdate = computed(() =>
    authStore.can("suppliers.update")
);

const canDelete = computed(() =>
    authStore.can("suppliers.delete")
);

// UI state
const searchInput = ref("");
const showSupplierModal = ref(false);
const showDeleteModal = ref(false);

const editingSupplier = ref(null);
const supplierToDelete = ref(null);

const saving = ref(false);
const deleting = ref(false);

const validationErrors = ref({});

let searchTimeout = null;

// Form
const form = ref({
    name: "",
    company_name: "",
    email: "",
    phone: "",
    address: "",
});

// Stats
const totalSuppliers = computed(() => total.value);

const suppliersWithProducts = computed(() =>
    suppliers.value.filter(
        (supplier) =>
            Number(supplier.products_count || 0) > 0
    ).length
);

const suppliersWithoutProducts = computed(() =>
    suppliers.value.filter(
        (supplier) =>
            Number(supplier.products_count || 0) === 0
    ).length
);

const totalProducts = computed(() =>
    suppliers.value.reduce(
        (sum, supplier) =>
            sum + Number(supplier.products_count || 0),
        0
    )
);

// Filters
const hasSearch = computed(() =>
    Boolean(search.value?.trim())
);

const hasActiveFilters = computed(() =>
    hasSearch.value
);

// Pagination
const hasPreviousPage = computed(
    () => currentPage.value > 1
);

const hasNextPage = computed(
    () => currentPage.value < lastPage.value
);

const firstSupplierNumber = computed(() => {
    if (!total.value || !suppliers.value.length) {
        return 0;
    }

    return (
        (currentPage.value - 1) * perPage.value + 1
    );
});

const lastSupplierNumber = computed(() => {
    if (!total.value || !suppliers.value.length) {
        return 0;
    }

    return Math.min(
        currentPage.value * perPage.value,
        total.value
    );
});

// Error helpers
function getErrorMessage(error, fallback) {
    return (
        error?.response?.data?.message ||
        error?.message ||
        fallback
    );
}

function setValidationErrors(error) {
    const errors = error?.response?.data?.errors;

    if (!errors || typeof errors !== "object") {
        return false;
    }

    validationErrors.value = errors;

    return true;
}

function clearValidationErrors() {
    validationErrors.value = {};
}

function getFieldError(field) {
    const errors = validationErrors.value?.[field];

    if (!errors) {
        return "";
    }

    return Array.isArray(errors)
        ? errors[0]
        : errors;
}

// Load suppliers
async function loadSuppliers(page = 1) {
    try {
        await supplierStore.fetchSuppliers(
            page,
            searchInput.value
        );

        return true;
    } catch (error) {
        toastStore.error(
            getErrorMessage(
                error,
                "Unable to load suppliers."
            )
        );

        return false;
    }
}

// Search
function performSearch() {
    clearTimeout(searchTimeout);

    loadSuppliers(1);
}

function clearSearch() {
    clearTimeout(searchTimeout);

    searchInput.value = "";

    loadSuppliers(1);
}

function clearAllFilters() {
    clearTimeout(searchTimeout);

    searchInput.value = "";

    loadSuppliers(1);
}

// Pagination
function goToPage(page) {
    if (
        loading.value ||
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return;
    }

    loadSuppliers(page);
}

function previousPage() {
    if (hasPreviousPage.value) {
        goToPage(currentPage.value - 1);
    }
}

function nextPage() {
    if (hasNextPage.value) {
        goToPage(currentPage.value + 1);
    }
}

// Form
function resetForm() {
    form.value = {
        name: "",
        company_name: "",
        email: "",
        phone: "",
        address: "",
    };

    clearValidationErrors();
}

// Create
function openCreateModal() {
    if (!canCreate.value || saving.value) {
        return;
    }

    editingSupplier.value = null;

    resetForm();

    showSupplierModal.value = true;
}

// Edit
function openEditModal(supplier) {
    if (!canUpdate.value || saving.value) {
        return;
    }

    editingSupplier.value = supplier;

    form.value = {
        name: supplier.name || "",
        company_name: supplier.company_name || "",
        email: supplier.email || "",
        phone: supplier.phone || "",
        address: supplier.address || "",
    };

    clearValidationErrors();

    showSupplierModal.value = true;
}

// Close modal
function closeSupplierModal() {
    if (saving.value) {
        return;
    }

    showSupplierModal.value = false;
    editingSupplier.value = null;

    resetForm();
}

// Save
async function saveSupplier() {
    if (saving.value) {
        return;
    }

    clearValidationErrors();

    const name = form.value.name.trim();
    const companyName = form.value.company_name.trim();
    const email = form.value.email.trim();
    const phone = form.value.phone.trim();
    const address = form.value.address.trim();

    if (!name) {
        validationErrors.value = {
            name: ["Supplier name is required."],
        };

        return;
    }

    saving.value = true;

    const isEditing = Boolean(editingSupplier.value);
    const supplierId = editingSupplier.value?.id;

    const payload = {
        name,
        company_name: companyName || null,
        email: email || null,
        phone: phone || null,
        address: address || null,
    };

    try {
        if (isEditing) {
            await supplierStore.updateSupplier(
                supplierId,
                payload
            );

            toastStore.success(
                "Supplier updated successfully."
            );
        } else {
            await supplierStore.createSupplier(payload);

            toastStore.success(
                "Supplier created successfully."
            );
        }

        const pageToLoad = isEditing
            ? currentPage.value
            : 1;

        showSupplierModal.value = false;
        editingSupplier.value = null;
        resetForm();

        await loadSuppliers(pageToLoad);
    } catch (error) {
        if (setValidationErrors(error)) {
            return;
        }

        const statusCode = error?.response?.status;

        if (statusCode === 403) {
            toastStore.error(
                "You do not have permission to perform this action."
            );
        } else if (statusCode === 404) {
            toastStore.error(
                "Supplier could not be found."
            );
        } else if (statusCode === 409) {
            toastStore.error(
                getErrorMessage(
                    error,
                    "This supplier already exists."
                )
            );
        } else {
            toastStore.error(
                getErrorMessage(
                    error,
                    isEditing
                        ? "Failed to update supplier."
                        : "Failed to create supplier."
                )
            );
        }
    } finally {
        saving.value = false;
    }
}

// Delete
function openDeleteModal(supplier) {
    if (!canDelete.value || deleting.value) {
        return;
    }

    supplierToDelete.value = supplier;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
    supplierToDelete.value = null;
}

async function confirmDelete() {
    if (
        deleting.value ||
        !supplierToDelete.value
    ) {
        return;
    }

    deleting.value = true;

    try {
        await supplierStore.deleteSupplier(
            supplierToDelete.value.id
        );

        toastStore.success(
            "Supplier deleted successfully."
        );

        const shouldGoBack =
            suppliers.value.length === 1 &&
            currentPage.value > 1;

        const pageToLoad = shouldGoBack
            ? currentPage.value - 1
            : currentPage.value;

        showDeleteModal.value = false;
        supplierToDelete.value = null;

        await loadSuppliers(pageToLoad);
    } catch (error) {
        const statusCode = error?.response?.status;

        if (statusCode === 403) {
            toastStore.error(
                "You do not have permission to delete suppliers."
            );
        } else if (statusCode === 404) {
            toastStore.error(
                "Supplier could not be found."
            );
        } else if (
            statusCode === 409 ||
            statusCode === 422
        ) {
            toastStore.error(
                getErrorMessage(
                    error,
                    "This supplier cannot be deleted because it has products assigned."
                )
            );
        } else {
            toastStore.error(
                getErrorMessage(
                    error,
                    "Failed to delete supplier."
                )
            );
        }
    } finally {
        deleting.value = false;
    }
}

// Refresh
async function refreshSuppliers() {
    const success = await loadSuppliers(
        currentPage.value
    );

    if (success) {
        toastStore.success(
            "Suppliers refreshed successfully."
        );
    }
}

// Search debounce
watch(searchInput, (value) => {
    clearTimeout(searchTimeout);

    const newSearch = value.trim();
    const currentSearch = search.value.trim();

    if (newSearch === currentSearch) {
        return;
    }

    searchTimeout = setTimeout(() => {
        loadSuppliers(1);
    }, 350);
});

// Initial load
onMounted(() => {
    searchInput.value = supplierStore.search || "";

    loadSuppliers(
        supplierStore.currentPage || 1
    );
});

// Cleanup
onUnmounted(() => {
    clearTimeout(searchTimeout);
});
</script>

<template>
    <div class="min-h-full bg-gray-50 dark:bg-gray-950">
        <div
            class="mx-auto w-full max-w-7xl space-y-5 p-4 sm:p-6 lg:p-8"
        >
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <h1
                        class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                    >
                        Suppliers
                    </h1>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Manage your suppliers and their product relationships.
                    </p>
                </div>

                <div
                    class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0"
                >
                    <BaseButton
                        variant="secondary"
                        :loading="loading"
                        :disabled="loading"
                        @click="refreshSuppliers"
                    >
                        Refresh
                    </BaseButton>

                    <BaseButton
                        v-if="canCreate"
                        variant="primary"
                        @click="openCreateModal"
                    >
                        Add Supplier
                    </BaseButton>
                </div>
            </div>

            <!-- Stats -->
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-4"
            >
                <BaseCard padding="p-4 sm:p-5">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Total
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ totalSuppliers }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-4 sm:p-5">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        With Products
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ suppliersWithProducts }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-4 sm:p-5">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Empty
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ suppliersWithoutProducts }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-4 sm:p-5">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 sm:text-sm"
                    >
                        Products
                    </p>

                    <p
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                    >
                        {{ totalProducts }}
                    </p>
                </BaseCard>
            </div>

            <!-- Filters -->
            <BaseCard>
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-end"
                >
                    <div class="min-w-0 flex-1">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Search
                        </label>

                        <div class="relative">
                            <input
                                v-model="searchInput"
                                type="text"
                                placeholder="Search suppliers..."
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-10 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                @keyup.enter="performSearch"
                            />

                            <button
                                v-if="searchInput"
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-lg leading-none text-gray-400 transition hover:text-gray-700 dark:hover:text-white"
                                @click="clearSearch"
                            >
                                ×
                            </button>
                        </div>
                    </div>

                    <BaseButton
                        v-if="hasActiveFilters"
                        variant="secondary"
                        class="w-full sm:w-auto"
                        @click="clearAllFilters"
                    >
                        Clear
                    </BaseButton>
                </div>

                <div
                    v-if="hasActiveFilters"
                    class="mt-3 flex flex-wrap gap-2 border-t border-gray-100 pt-3 dark:border-gray-700"
                >
                    <span
                        v-if="hasSearch"
                        class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        Search: "{{ search }}"
                    </span>
                </div>
            </BaseCard>

            <!-- Error -->
            <BaseCard
                v-if="error && !loading"
                padding="p-0"
            >
                <div
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p
                            class="font-medium text-red-700 dark:text-red-400"
                        >
                            Unable to load suppliers
                        </p>

                        <p
                            class="mt-1 break-words text-sm text-red-600 dark:text-red-400"
                        >
                            {{ error }}
                        </p>
                    </div>

                    <BaseButton
                        variant="secondary"
                        class="shrink-0"
                        @click="loadSuppliers(currentPage)"
                    >
                        Retry
                    </BaseButton>
                </div>
            </BaseCard>

            <!-- Supplier List -->
            <BaseCard padding="p-0">
                <div
                    class="flex flex-col gap-1 border-b border-gray-100 px-4 py-4 sm:px-5 dark:border-gray-700"
                >
                    <div
                        class="flex items-center justify-between gap-3"
                    >
                        <div>
                            <h2
                                class="font-semibold text-gray-900 dark:text-white"
                            >
                                Supplier List
                            </h2>

                            <p
                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <template v-if="total">
                                    Showing
                                    {{ firstSupplierNumber }}–{{
                                        lastSupplierNumber
                                    }}
                                    of {{ total }}
                                </template>

                                <template v-else>
                                    No suppliers found
                                </template>
                            </p>
                        </div>

                        <span
                            v-if="loading && suppliers.length"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            Updating...
                        </span>
                    </div>
                </div>

                <!-- Loading -->
                <div
                    v-if="loading && !suppliers.length"
                    class="flex min-h-60 items-center justify-center p-8"
                >
                    <div
                        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-gray-700 dark:border-gray-700 dark:border-t-gray-300"
                    ></div>
                </div>

                <!-- Empty -->
                <div
                    v-else-if="!suppliers.length"
                    class="flex min-h-60 flex-col items-center justify-center p-6 text-center"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
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
                                stroke-width="1.8"
                                d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M8 9h1m6 0h1m-8 4h1m6 0h1"
                            />
                        </svg>
                    </div>

                    <h3
                        class="mt-4 text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        No suppliers found
                    </h3>

                    <p
                        class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{
                            hasActiveFilters
                                ? "Try adjusting your search."
                                : "Create your first supplier to get started."
                        }}
                    </p>

                    <BaseButton
                        v-if="canCreate && !hasActiveFilters"
                        class="mt-4"
                        @click="openCreateModal"
                    >
                        Add Supplier
                    </BaseButton>

                    <BaseButton
                        v-else-if="hasActiveFilters"
                        class="mt-4"
                        variant="secondary"
                        @click="clearAllFilters"
                    >
                        Clear Search
                    </BaseButton>
                </div>

                <!-- Desktop -->
                <div
                    v-else
                    class="hidden overflow-x-auto md:block"
                >
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400"
                            >
                                <th class="px-5 py-3.5">
                                    Supplier
                                </th>

                                <th class="px-5 py-3.5">
                                    Contact
                                </th>

                                <th class="px-5 py-3.5">
                                    Address
                                </th>

                                <th
                                    class="px-5 py-3.5 text-center"
                                >
                                    Products
                                </th>

                                <th
                                    class="px-5 py-3.5 text-right"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                class="transition hover:bg-gray-50 dark:hover:bg-gray-800/40"
                            >
                                <td class="px-5 py-4">
                                    <div
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                        >
                                            {{
                                                supplier.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase()
                                            }}
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ supplier.name }}
                                            </p>

                                            <p
                                                v-if="supplier.company_name"
                                                class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{
                                                    supplier.company_name
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <p
                                            v-if="supplier.email"
                                            class="truncate text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            {{ supplier.email }}
                                        </p>

                                        <p
                                            v-if="supplier.phone"
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {{ supplier.phone }}
                                        </p>

                                        <span
                                            v-if="
                                                !supplier.email &&
                                                !supplier.phone
                                            "
                                            class="text-sm text-gray-400"
                                        >
                                            No contact details
                                        </span>
                                    </div>
                                </td>

                                <td
                                    class="max-w-xs px-5 py-4"
                                >
                                    <p
                                        class="truncate text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{
                                            supplier.address ||
                                            "No address"
                                        }}
                                    </p>
                                </td>

                                <td
                                    class="px-5 py-4 text-center"
                                >
                                    <span
                                        class="inline-flex min-w-10 items-center justify-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        {{
                                            supplier.products_count ??
                                            0
                                        }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div
                                        class="flex justify-end gap-2"
                                    >
                                        <BaseButton
                                            v-if="canUpdate"
                                            variant="secondary"
                                            @click="
                                                openEditModal(
                                                    supplier
                                                )
                                            "
                                        >
                                            Edit
                                        </BaseButton>

                                        <BaseButton
                                            v-if="canDelete"
                                            variant="danger"
                                            @click="
                                                openDeleteModal(
                                                    supplier
                                                )
                                            "
                                        >
                                            Delete
                                        </BaseButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile -->
                <div
                    v-if="suppliers.length"
                    class="divide-y divide-gray-100 md:hidden dark:divide-gray-700"
                >
                    <div
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        class="p-4"
                    >
                        <div
                            class="flex items-start justify-between gap-3"
                        >
                            <div
                                class="flex min-w-0 items-center gap-3"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                >
                                    {{
                                        supplier.name
                                            ?.charAt(0)
                                            ?.toUpperCase()
                                    }}
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="truncate font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ supplier.name }}
                                    </p>

                                    <p
                                        v-if="supplier.company_name"
                                        class="truncate text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{
                                            supplier.company_name
                                        }}
                                    </p>
                                </div>
                            </div>

                            <span
                                class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                {{
                                    supplier.products_count ?? 0
                                }}
                                products
                            </span>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div
                                v-if="supplier.email"
                                class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400"
                            >
                                <span
                                    class="w-16 shrink-0 text-xs font-medium text-gray-400"
                                >
                                    Email
                                </span>

                                <span class="break-all">
                                    {{ supplier.email }}
                                </span>
                            </div>

                            <div
                                v-if="supplier.phone"
                                class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400"
                            >
                                <span
                                    class="w-16 shrink-0 text-xs font-medium text-gray-400"
                                >
                                    Phone
                                </span>

                                <span>
                                    {{ supplier.phone }}
                                </span>
                            </div>

                            <div
                                v-if="supplier.address"
                                class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400"
                            >
                                <span
                                    class="w-16 shrink-0 text-xs font-medium text-gray-400"
                                >
                                    Address
                                </span>

                                <span>
                                    {{ supplier.address }}
                                </span>
                            </div>

                            <p
                                v-if="
                                    !supplier.email &&
                                    !supplier.phone &&
                                    !supplier.address
                                "
                                class="text-sm text-gray-400"
                            >
                                No contact details available.
                            </p>
                        </div>

                        <div
                            class="mt-4 flex justify-end gap-2"
                        >
                            <BaseButton
                                v-if="canUpdate"
                                variant="secondary"
                                @click="
                                    openEditModal(supplier)
                                "
                            >
                                Edit
                            </BaseButton>

                            <BaseButton
                                v-if="canDelete"
                                variant="danger"
                                @click="
                                    openDeleteModal(supplier)
                                "
                            >
                                Delete
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="total > 1 && lastPage > 1"
                    class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-gray-700"
                >
                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Showing
                        <span
                            class="font-medium text-gray-900 dark:text-white"
                        >
                            {{ firstSupplierNumber }}
                        </span>
                        to
                        <span
                            class="font-medium text-gray-900 dark:text-white"
                        >
                            {{ lastSupplierNumber }}
                        </span>
                        of
                        <span
                            class="font-medium text-gray-900 dark:text-white"
                        >
                            {{ total }}
                        </span>
                    </p>

                    <div
                        class="flex items-center gap-2"
                    >
                        <BaseButton
                            variant="secondary"
                            :disabled="
                                !hasPreviousPage ||
                                loading
                            "
                            @click="previousPage"
                        >
                            Previous
                        </BaseButton>

                        <span
                            class="px-2 text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{ currentPage }} /
                            {{ lastPage }}
                        </span>

                        <BaseButton
                            variant="secondary"
                            :disabled="
                                !hasNextPage ||
                                loading
                            "
                            @click="nextPage"
                        >
                            Next
                        </BaseButton>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Create / Edit Modal -->
        <BaseModal
            :show="showSupplierModal"
            :title="
                editingSupplier
                    ? 'Edit Supplier'
                    : 'Create Supplier'
            "
            size="md"
            :close-on-backdrop="!saving"
            @close="closeSupplierModal"
        >
            <form
                class="space-y-5"
                @submit.prevent="saveSupplier"
            >
                <!-- Name -->
                <div>
                    <label
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Supplier Name
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        maxlength="255"
                        placeholder="e.g. John Traders"
                        :disabled="saving"
                        :class="{
                            'border-red-500 focus:border-red-500 focus:ring-red-100':
                                getFieldError('name'),
                        }"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    />

                    <p
                        v-if="getFieldError('name')"
                        class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ getFieldError("name") }}
                    </p>
                </div>

                <!-- Company -->
                <div>
                    <label
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Company Name
                    </label>

                    <input
                        v-model="form.company_name"
                        type="text"
                        maxlength="255"
                        placeholder="e.g. ABC Trading Pvt. Ltd."
                        :disabled="saving"
                        :class="{
                            'border-red-500 focus:border-red-500 focus:ring-red-100':
                                getFieldError('company_name'),
                        }"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    />

                    <p
                        v-if="getFieldError('company_name')"
                        class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                    >
                        {{
                            getFieldError(
                                "company_name"
                            )
                        }}
                    </p>
                </div>

                <!-- Contact -->
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                >
                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Email
                        </label>

                        <input
                            v-model="form.email"
                            type="email"
                            maxlength="255"
                            placeholder="supplier@example.com"
                            :disabled="saving"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-100':
                                    getFieldError('email'),
                            }"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        />

                        <p
                            v-if="getFieldError('email')"
                            class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ getFieldError("email") }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Phone
                        </label>

                        <input
                            v-model="form.phone"
                            type="text"
                            maxlength="50"
                            placeholder="+977 98XXXXXXXX"
                            :disabled="saving"
                            :class="{
                                'border-red-500 focus:border-red-500 focus:ring-red-100':
                                    getFieldError('phone'),
                            }"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                        />

                        <p
                            v-if="getFieldError('phone')"
                            class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ getFieldError("phone") }}
                        </p>
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <label
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Address
                    </label>

                    <textarea
                        v-model="form.address"
                        rows="3"
                        maxlength="500"
                        placeholder="Supplier address..."
                        :disabled="saving"
                        :class="{
                            'border-red-500 focus:border-red-500 focus:ring-red-100':
                                getFieldError('address'),
                        }"
                        class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                    ></textarea>

                    <p
                        v-if="getFieldError('address')"
                        class="mt-1.5 text-xs text-red-600 dark:text-red-400"
                    >
                        {{ getFieldError("address") }}
                    </p>
                </div>
            </form>

            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <BaseButton
                        variant="secondary"
                        :disabled="saving"
                        @click="closeSupplierModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        variant="primary"
                        :loading="saving"
                        :disabled="saving"
                        @click="saveSupplier"
                    >
                        {{
                            editingSupplier
                                ? "Update Supplier"
                                : "Create Supplier"
                        }}
                    </BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Delete Modal -->
        <BaseModal
            :show="showDeleteModal"
            title="Delete Supplier"
            size="sm"
            :close-on-backdrop="!deleting"
            @close="closeDeleteModal"
        >
            <div class="text-center">
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400"
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
                            stroke-width="1.8"
                            d="M6 7h12m-9 0V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7m-7 0 .75 12.25A1.5 1.5 0 0 0 10.25 20.5h3.5a1.5 1.5 0 0 0 1.5-1.25L16 7m-6 4v5m4-5v5"
                        />
                    </svg>
                </div>

                <h3
                    class="mt-4 text-lg font-semibold text-gray-900 dark:text-white"
                >
                    Delete this supplier?
                </h3>

                <p
                    class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400"
                >
                    You are about to delete
                    <span
                        class="font-semibold text-gray-900 dark:text-white"
                    >
                        {{ supplierToDelete?.name }}
                    </span>
                    .
                </p>

                <p
                    v-if="supplierToDelete?.products_count"
                    class="mt-3 rounded-lg bg-yellow-50 p-3 text-left text-xs leading-5 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400"
                >
                    This supplier has
                    {{ supplierToDelete.products_count }}
                    product(s) assigned to it and cannot be
                    deleted until those products are reassigned.
                </p>

                <p
                    v-else
                    class="mt-3 rounded-lg bg-gray-50 p-3 text-left text-xs leading-5 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                >
                    This supplier has no products assigned and
                    can be safely deleted.
                </p>
            </div>

            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <BaseButton
                        variant="secondary"
                        :disabled="deleting"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        variant="danger"
                        :loading="deleting"
                        :disabled="
                            deleting ||
                            supplierToDelete?.products_count > 0
                        "
                        @click="confirmDelete"
                    >
                        Delete Supplier
                    </BaseButton>
                </div>
            </template>
        </BaseModal>
    </div>
</template>
