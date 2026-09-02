import { defineStore } from "pinia";
import axios from "axios";

export const useCategoryStore = defineStore("category", {
    state: () => ({
        categories: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchCategories() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/api/categories");

                const data =
                    response.data.categories ||
                    response.data.data ||
                    [];

                this.categories = Array.isArray(data)
                    ? data
                    : data.data || [];

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
    },
});
