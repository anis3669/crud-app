import { defineStore } from "pinia";
import axios from "axios";

export const useSupplierStore = defineStore("supplier", {
    state: () => ({
        suppliers: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchSuppliers() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/api/suppliers");

                const data =
                    response.data.suppliers ||
                    response.data.data ||
                    [];

                this.suppliers = Array.isArray(data)
                    ? data
                    : data.data || [];

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
    },
});
