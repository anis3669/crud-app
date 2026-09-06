import { defineStore } from "pinia";
import axios from "axios";

export const useCategoryStore = defineStore("category", {
    state: () => ({
        categories: [],
        category: null,

        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,

        search: "",
        status: "all",

        loading: false,
        error: null,
    }),

    getters: {
        hasPreviousPage: (state) => state.currentPage > 1,
        hasNextPage: (state) => state.currentPage < state.lastPage,
    },

    actions: {
        // Fetch categories
        async fetchCategories(
            page = 1,
            search = this.search,
            status = this.status
        ) {
            this.loading = true;
            this.error = null;

            try {
                const params = {
                    page,
                    per_page: this.perPage,
                };

                if (search?.trim()) {
                    params.search = search.trim();
                }

                if (status === "active") {
                    params.is_active = true;
                }

                if (status === "inactive") {
                    params.is_active = false;
                }

                const response = await axios.get("/api/categories", {
                    params,
                });

                const data = response.data;

                this.categories = Array.isArray(data.data)
                    ? data.data
                    : [];

                this.currentPage = data.current_page || 1;
                this.lastPage = data.last_page || 1;
                this.perPage = data.per_page || this.perPage;
                this.total = data.total || 0;

                this.search = search;
                this.status = status;

                return this.categories;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to load categories.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single category
        async fetchCategory(id) {
            this.error = null;

            try {
                const response = await axios.get(`/api/categories/${id}`);

                this.category = response.data.category || null;

                return this.category;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to load category.";

                throw error;
            }
        },

        // Create category
        async createCategory(categoryData) {
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/categories",
                    categoryData
                );

                return response.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to create category.";

                throw error;
            }
        },

        // Update category
        async updateCategory(id, categoryData) {
            this.error = null;

            try {
                const response = await axios.put(
                    `/api/categories/${id}`,
                    categoryData
                );

                return response.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to update category.";

                throw error;
            }
        },

        // Delete category
        async deleteCategory(id) {
            this.error = null;

            try {
                const response = await axios.delete(
                    `/api/categories/${id}`
                );

                return response.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to delete category.";

                throw error;
            }
        },

        // Clear selected category
        clearCategory() {
            this.category = null;
        },

        // Clear error
        clearError() {
            this.error = null;
        },
    },
});
