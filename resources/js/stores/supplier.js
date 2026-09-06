import { defineStore } from "pinia";
import axios from "axios";

export const useSupplierStore = defineStore("supplier", {
    state: () => ({
        suppliers: [],
        supplier: null,

        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,

        search: "",

        loading: false,
        error: null,
    }),

    getters: {
        hasPreviousPage: (state) => state.currentPage > 1,

        hasNextPage: (state) =>
            state.currentPage < state.lastPage,
    },

    actions: {
        // Fetch suppliers
        async fetchSuppliers(
            page = 1,
            search = this.search
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

                const response = await axios.get(
                    "/api/suppliers",
                    { params }
                );

                const data = response.data;

                this.suppliers = Array.isArray(data.data)
                    ? data.data
                    : [];

                this.currentPage = data.current_page || 1;
                this.lastPage = data.last_page || 1;
                this.perPage =
                    data.per_page || this.perPage;
                this.total = data.total || 0;

                this.search = search;

                return this.suppliers;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to load suppliers.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single supplier
        async fetchSupplier(id) {
            this.error = null;

            try {
                const response = await axios.get(
                    `/api/suppliers/${id}`
                );

                this.supplier = response.data;

                return this.supplier;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to load supplier.";

                throw error;
            }
        },

        // Create supplier
        async createSupplier(supplierData) {
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/suppliers",
                    supplierData
                );

                return response.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to create supplier.";

                throw error;
            }
        },

        // Update supplier
        async updateSupplier(id, supplierData) {
            this.error = null;

            try {
                const response = await axios.put(
                    `/api/suppliers/${id}`,
                    supplierData
                );

                return response.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to update supplier.";

                throw error;
            }
        },

        // Delete supplier
        async deleteSupplier(id) {
            this.error = null;

            try {
                const response = await axios.delete(
                    `/api/suppliers/${id}`
                );

                return response.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to delete supplier.";

                throw error;
            }
        },

        // Clear selected supplier
        clearSupplier() {
            this.supplier = null;
        },

        // Clear error
        clearError() {
            this.error = null;
        },
    },
});
