<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import { useRouter } from "vue-router";

import { useCategoryStore } from "../../stores/category";
import { useAuthStore } from "../../stores/auth";
import { useToastStore } from "../../stores/toast";

import BaseButton from "../common/BaseButton.vue";
import BaseModal from "../common/BaseModal.vue";
import BaseCard from "../common/BaseCard.vue";

const router = useRouter();

const categoryStore = useCategoryStore();
const authStore = useAuthStore();
const toastStore = useToastStore();

// UI state
const searchInput = ref("");
const showCategoryModal = ref(false);
const showDeleteModal = ref(false);

const editingCategory = ref(null);
const categoryToDelete = ref(null);

const saving = ref(false);
const deleting = ref(false);

// Form
const form = ref({
    name: "",
    description: "",
    is_active: true,
});

const validationErrors = ref({});

// Permissions
const canCreate = computed(() =>
    authStore.can("categories.create")
);

const canUpdate = computed(() =>
    authStore.can("categories.update")
);

const canDelete = computed(() =>
    authStore.can("categories.delete")
);

// Data
const categories = computed(() => categoryStore.categories);
const loading = computed(() => categoryStore.loading);
const error = computed(() => categoryStore.error);

const currentPage = computed(() => categoryStore.currentPage);
const lastPage = computed(() => categoryStore.lastPage);
const total = computed(() => categoryStore.total);
const perPage = computed(() => categoryStore.perPage);

const hasPreviousPage = computed(
    () => currentPage.value > 1
);

const hasNextPage = computed(
    () => currentPage.value < lastPage.value
);

const search = computed(() => categoryStore.search);
const status = computed(() => categoryStore.status);

// Stats
const totalCategories = computed(() => total.value);

const activeCategories = computed(() =>
    categories.value.filter(
        (category) => category.is_active === true
    ).length
);

const inactiveCategories = computed(() =>
    categories.value.filter(
        (category) => category.is_active === false
    ).length
);

const totalProducts = computed(() =>
    categories.value.reduce(
        (total, category) =>
            total + Number(category.products_count || 0),
        0
    )
);

// Pagination numbers
const firstCategoryNumber = computed(() => {
    if (!total.value || !categories.value.length) {
        return 0;
    }

    return (
        (currentPage.value - 1) * perPage.value + 1
    );
});

const lastCategoryNumber = computed(() => {
    if (!total.value || !categories.value.length) {
        return 0;
    }

    return Math.min(
        currentPage.value * perPage.value,
        total.value
    );
});

// Filters
const hasSearch = computed(() =>
    Boolean(search.value?.trim())
);

const hasStatusFilter = computed(
    () => status.value !== "all"
);

const hasActiveFilters = computed(
    () => hasSearch.value || hasStatusFilter.value
);

const statusFilterLabel = computed(() => {
    if (status.value === "active") {
        return "Active";
    }

    if (status.value === "inactive") {
        return "Inactive";
    }

    return "All statuses";
});

// Load categories
async function loadCategories(page = 1) {
    try {
        await categoryStore.fetchCategories(
            page,
            searchInput.value,
            categoryStore.status
        );
    } catch {
        // Store handles the error
    }
}

// Search
function performSearch() {
    loadCategories(1);
}

function clearSearch() {
    searchInput.value = "";
    loadCategories(1);
}

// Status filter
function changeStatusFilter(value) {
    categoryStore.status = value;
    loadCategories(1);
}

// Clear all filters
function clearAllFilters() {
    searchInput.value = "";
    categoryStore.status = "all";

    loadCategories(1);
}

// Pagination
function goToPage(page) {
    if (
        page < 1 ||
        page > lastPage.value ||
        page === currentPage.value
    ) {
        return;
    }

    loadCategories(page);
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

// Modal form
function resetForm() {
    form.value = {
        name: "",
        description: "",
        is_active: true,
    };

    validationErrors.value = {};
}

function openCreateModal() {
    if (!canCreate.value || saving.value) {
        return;
    }

    editingCategory.value = null;
    resetForm();
    showCategoryModal.value = true;
}

function openEditModal(category) {
    if (!canUpdate.value || saving.value) {
        return;
    }

    editingCategory.value = category;

    form.value = {
        name: category.name || "",
        description: category.description || "",
        is_active: Boolean(category.is_active),
    };

    validationErrors.value = {};
    showCategoryModal.value = true;
}

function closeCategoryModal() {
    if (saving.value) {
        return;
    }

    showCategoryModal.value = false;
    editingCategory.value = null;
    resetForm();
}

// Validation
function getFieldError(field) {
    const errors = validationErrors.value;

    if (!errors || !errors[field]) {
        return "";
    }

    return Array.isArray(errors[field])
        ? errors[field][0]
        : errors[field];
}

// Save category
async function saveCategory() {
    if (saving.value) {
        return;
    }

    validationErrors.value = {};

    const name = form.value.name.trim();
    const description = form.value.description.trim();

    if (!name) {
        validationErrors.value.name = [
            "Category name is required.",
        ];

        return;
    }

    saving.value = true;

    const isEditing = Boolean(editingCategory.value);

    const categoryId = editingCategory.value?.id;

    const payload = {
        name,
        description: description || null,
        is_active: Boolean(form.value.is_active),
    };

    try {
        if (isEditing) {
            await categoryStore.updateCategory(
                categoryId,
                payload
            );

            toastStore.success(
                "Category updated successfully."
            );
        } else {
            await categoryStore.createCategory(payload);

            toastStore.success(
                "Category created successfully."
            );
        }

        closeCategoryModal();

        // Reload the category list
        await loadCategories(
            isEditing ? currentPage.value : 1
        );

        // Make sure the user is on the categories page
        if (router.currentRoute.value.name !== "categories.index") {
            await router.push({
                name: "categories.index",
            });
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            validationErrors.value =
                error.response.data.errors;
        } else {
            toastStore.error(
                error.response?.data?.message ||
                    "Failed to save category."
            );
        }
    } finally {
        saving.value = false;
    }
}

// Delete
function openDeleteModal(category) {
    if (!canDelete.value || deleting.value) {
        return;
    }

    categoryToDelete.value = category;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    if (deleting.value) {
        return;
    }

    showDeleteModal.value = false;
    categoryToDelete.value = null;
}

async function confirmDelete() {
    if (
        deleting.value ||
        !categoryToDelete.value
    ) {
        return;
    }

    deleting.value = true;

    try {
        await categoryStore.deleteCategory(
            categoryToDelete.value.id
        );

        toastStore.success(
            "Category deleted successfully."
        );

        closeDeleteModal();

        const targetPage =
            categories.value.length === 1 &&
            currentPage.value > 1
                ? currentPage.value - 1
                : currentPage.value;

        await loadCategories(targetPage);
    } catch (error) {
        const message =
            error.response?.data?.message ||
            "Failed to delete category.";

        toastStore.error(message);
    } finally {
        deleting.value = false;
    }
}

// Refresh
async function refreshCategories() {
    await loadCategories(currentPage.value);

    toastStore.success(
        "Categories refreshed successfully."
    );
}

// Search debounce
let searchTimeout;

watch(searchInput, () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        if (
            searchInput.value.trim() !==
            search.value.trim()
        ) {
            loadCategories(1);
        }
    }, 350);
});

onMounted(() => {
    searchInput.value = categoryStore.search || "";

    loadCategories(
        categoryStore.currentPage || 1
    );
});

onUnmounted(() => {
    clearTimeout(searchTimeout);
});
</script>

<template>
    <div class="min-h-full bg-gray-50 dark:bg-gray-950">
        <div
            class="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8"
        >
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl"
                    >
                        Categories
                    </h1>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        Manage product categories and their
                        status.
                    </p>
                </div>

                <div
                    class="flex flex-col gap-2 sm:flex-row"
                >
                    <BaseButton
                        variant="secondary"
                        :loading="loading"
                        :disabled="loading"
                        @click="refreshCategories"
                    >
                        Refresh
                    </BaseButton>

                    <BaseButton
                        v-if="canCreate"
                        variant="primary"
                        @click="openCreateModal"
                    >
                        Add Category
                    </BaseButton>
                </div>
            </div>

            <!-- Stats -->
            <div
                class="grid grid-cols-2 gap-4 lg:grid-cols-4"
            >
                <BaseCard padding="p-5">
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Total Categories
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ totalCategories }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-5">
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Active
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ activeCategories }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-5">
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Inactive
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ inactiveCategories }}
                    </p>
                </BaseCard>

                <BaseCard padding="p-5">
                    <p
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Products
                    </p>

                    <p
                        class="mt-2 text-2xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ totalProducts }}
                    </p>
                </BaseCard>
            </div>

            <!-- Filters -->
            <BaseCard>
                <div
                    class="flex flex-col gap-4 lg:flex-row lg:items-end"
                >
                    <div class="flex-1">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Search
                        </label>

                        <div class="relative">
                            <input
                                v-model="searchInput"
                                type="text"
                                placeholder="Search categories..."
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                @keyup.enter="performSearch"
                            />

                            <button
                                v-if="searchInput"
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:hover:text-white"
                                @click="clearSearch"
                            >
                                ×
                            </button>
                        </div>
                    </div>

                    <div class="w-full lg:w-52">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Status
                        </label>

                        <select
                            :value="status"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            @change="
                                changeStatusFilter(
                                    $event.target.value
                                )
                            "
                        >
                            <option value="all">
                                All statuses
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>
                        </select>
                    </div>

                    <BaseButton
                        v-if="hasActiveFilters"
                        variant="secondary"
                        @click="clearAllFilters"
                    >
                        Clear Filters
                    </BaseButton>
                </div>

                <div
                    v-if="hasActiveFilters"
                    class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700"
                >
                    <div
                        class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span
                            v-if="hasSearch"
                            class="rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-800"
                        >
                            Search: "{{ search }}"
                        </span>

                        <span
                            v-if="hasStatusFilter"
                            class="rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-800"
                        >
                            Status: {{ statusFilterLabel }}
                        </span>
                    </div>
                </div>
            </BaseCard>

            <!-- Error -->
            <BaseCard
                v-if="error && !loading"
                padding="p-4"
            >
                <div
                    class="flex items-center justify-between gap-4 rounded-lg bg-red-50 p-4 text-red-700 dark:bg-red-900/20 dark:text-red-400"
                >
                    <div>
                        <p class="font-medium">
                            Unable to load categories
                        </p>

                        <p class="mt-1 text-sm">
                            {{ error }}
                        </p>
                    </div>

                    <BaseButton
                        variant="secondary"
                        @click="loadCategories(currentPage)"
                    >
                        Retry
                    </BaseButton>
                </div>
            </BaseCard>

            <!-- Category list -->
            <BaseCard padding="p-0">
                <div
                    class="border-b border-gray-100 px-5 py-4 dark:border-gray-700"
                >
                    <h2
                        class="font-semibold text-gray-900 dark:text-white"
                    >
                        Category List
                    </h2>

                    <p
                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <template v-if="total">
                            Showing {{ firstCategoryNumber }}–{{
                                lastCategoryNumber
                            }}
                            of {{ total }} categories
                        </template>

                        <template v-else>
                            No categories found
                        </template>
                    </p>
                </div>

                <!-- Loading -->
                <div
                    v-if="loading && !categories.length"
                    class="flex min-h-64 items-center justify-center p-8"
                >
                    <div
                        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-gray-700"
                    ></div>
                </div>

                <!-- Empty -->
                <div
                    v-else-if="!categories.length"
                    class="flex min-h-64 flex-col items-center justify-center p-8 text-center"
                >
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        No categories found
                    </h3>

                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        {{
                            hasActiveFilters
                                ? "Try adjusting your search or filters."
                                : "Create your first category to get started."
                        }}
                    </p>

                    <BaseButton
                        v-if="canCreate && !hasActiveFilters"
                        class="mt-4"
                        @click="openCreateModal"
                    >
                        Add Category
                    </BaseButton>

                    <BaseButton
                        v-else-if="hasActiveFilters"
                        class="mt-4"
                        variant="secondary"
                        @click="clearAllFilters"
                    >
                        Clear Filters
                    </BaseButton>
                </div>

                <!-- Desktop table -->
                <div
                    v-else
                    class="hidden overflow-x-auto md:block"
                >
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="border-b border-gray-100 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-400"
                            >
                                <th class="px-5 py-3.5">
                                    Category
                                </th>

                                <th class="px-5 py-3.5">
                                    Description
                                </th>

                                <th
                                    class="px-5 py-3.5 text-center"
                                >
                                    Products
                                </th>

                                <th class="px-5 py-3.5">
                                    Status
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
                                v-for="category in categories"
                                :key="category.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/40"
                            >
                                <td class="px-5 py-4">
                                    <div
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-sm font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                        >
                                            {{
                                                category.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase()
                                            }}
                                        </div>

                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ category.name }}
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ category.slug }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td
                                    class="max-w-xs px-5 py-4"
                                >
                                    <p
                                        class="truncate text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        {{
                                            category.description ||
                                            "No description"
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
                                            category.products_count ??
                                            0
                                        }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        :class="
                                            category.is_active
                                                ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                        "
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                    >
                                        {{
                                            category.is_active
                                                ? "Active"
                                                : "Inactive"
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
                                                    category
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
                                                    category
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

                <!-- Mobile cards -->
                <div
                    v-if="categories.length"
                    class="divide-y divide-gray-100 md:hidden dark:divide-gray-700"
                >
                    <div
                        v-for="category in categories"
                        :key="category.id"
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
                                        category.name
                                            ?.charAt(0)
                                            ?.toUpperCase()
                                    }}
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="truncate font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ category.name }}
                                    </p>

                                    <p
                                        class="truncate text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ category.slug }}
                                    </p>
                                </div>
                            </div>

                            <span
                                :class="
                                    category.is_active
                                        ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                "
                                class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                            >
                                {{
                                    category.is_active
                                        ? "Active"
                                        : "Inactive"
                                }}
                            </span>
                        </div>

                        <p
                            class="mt-3 text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{
                                category.description ||
                                "No description"
                            }}
                        </p>

                        <div
                            class="mt-4 flex items-center justify-between"
                        >
                            <div>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Products
                                </p>

                                <p
                                    class="mt-1 font-semibold text-gray-900 dark:text-white"
                                >
                                    {{
                                        category.products_count ??
                                        0
                                    }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <BaseButton
                                    v-if="canUpdate"
                                    variant="secondary"
                                    @click="
                                        openEditModal(category)
                                    "
                                >
                                    Edit
                                </BaseButton>

                                <BaseButton
                                    v-if="canDelete"
                                    variant="danger"
                                    @click="
                                        openDeleteModal(category)
                                    "
                                >
                                    Delete
                                </BaseButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading overlay -->
                <div
                    v-if="loading && categories.length"
                    class="flex items-center justify-center border-t border-gray-100 bg-white/70 px-5 py-3 dark:border-gray-700 dark:bg-gray-900/70"
                >
                    <span
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Updating...
                    </span>
                </div>

                <!-- Pagination -->
                <div
                    v-if="total > 0"
                    class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
                >
                    <p
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Showing
                        <span
                            class="font-medium text-gray-900 dark:text-white"
                        >
                            {{ firstCategoryNumber }}
                        </span>
                        to
                        <span
                            class="font-medium text-gray-900 dark:text-white"
                        >
                            {{ lastCategoryNumber }}
                        </span>
                        of
                        <span
                            class="font-medium text-gray-900 dark:text-white"
                        >
                            {{ total }}
                        </span>
                    </p>

                    <div class="flex items-center gap-2">
                        <BaseButton
                            variant="secondary"
                            :disabled="
                                !hasPreviousPage || loading
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
                                !hasNextPage || loading
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
            :show="showCategoryModal"
            :title="
                editingCategory
                    ? 'Edit Category'
                    : 'Create Category'
            "
            size="md"
            :close-on-backdrop="!saving"
            @close="closeCategoryModal"
        >
            <form
                class="space-y-5"
                @submit.prevent="saveCategory"
            >
                <div>
                    <label
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Category Name
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        maxlength="255"
                        placeholder="e.g. Electronics"
                        :disabled="saving"
                        :class="
                            getFieldError('name')
                                ? 'border-red-500'
                                : ''
                        "
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    />

                    <p
                        v-if="getFieldError('name')"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{ getFieldError("name") }}
                    </p>
                </div>

                <div>
                    <label
                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Description
                    </label>

                    <textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="1000"
                        placeholder="Describe this category..."
                        :disabled="saving"
                        class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-gray-500 focus:ring-2 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    ></textarea>

                    <p
                        v-if="getFieldError('description')"
                        class="mt-1.5 text-xs text-red-600"
                    >
                        {{
                            getFieldError(
                                "description"
                            )
                        }}
                    </p>
                </div>

                <div
                    class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50"
                >
                    <label
                        class="flex cursor-pointer items-start gap-3"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            :disabled="saving"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300"
                        />

                        <span>
                            <span
                                class="block text-sm font-medium text-gray-900 dark:text-white"
                            >
                                Active category
                            </span>

                            <span
                                class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400"
                            >
                                Active categories can be
                                assigned to products.
                            </span>
                        </span>
                    </label>
                </div>
            </form>

            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <BaseButton
                        variant="secondary"
                        :disabled="saving"
                        @click="closeCategoryModal"
                    >
                        Cancel
                    </BaseButton>

                    <BaseButton
                        variant="primary"
                        :loading="saving"
                        :disabled="saving"
                        @click="saveCategory"
                    >
                        {{
                            editingCategory
                                ? "Update Category"
                                : "Create Category"
                        }}
                    </BaseButton>
                </div>
            </template>
        </BaseModal>

        <!-- Delete Modal -->
        <BaseModal
            :show="showDeleteModal"
            title="Delete Category"
            size="sm"
            :close-on-backdrop="!deleting"
            @close="closeDeleteModal"
        >
            <div class="text-center">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-white"
                >
                    Delete this category?
                </h3>

                <p
                    class="mt-2 text-sm text-gray-500 dark:text-gray-400"
                >
                    You are about to delete
                    <span
                        class="font-semibold text-gray-900 dark:text-white"
                    >
                        {{ categoryToDelete?.name }}
                    </span>
                    .
                </p>

                <p
                    v-if="categoryToDelete?.products_count"
                    class="mt-3 rounded-lg bg-yellow-50 p-3 text-xs text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400"
                >
                    This category has
                    {{ categoryToDelete.products_count }}
                    product(s) assigned to it and may not be
                    deleted.
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
                        :disabled="deleting"
                        @click="confirmDelete"
                    >
                        Delete Category
                    </BaseButton>
                </div>
            </template>
        </BaseModal>
    </div>
</template>
