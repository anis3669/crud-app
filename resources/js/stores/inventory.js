import axios from "axios";
import { defineStore } from "pinia";

const emptyStats = () => ({
    total_products: 0,
    total_quantity: 0,
    low_stock: 0,
    out_of_stock: 0,
});

export const useInventoryStore = defineStore("inventory", {
    state: () => ({
        products: [],
        loading: false,
        error: null,
        search: "",
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,
        stats: emptyStats(),
        history: [],
        historyLoading: false,
        historyError: null,
        historyCurrentPage: 1,
        historyLastPage: 1,
        historyTotal: 0,
    }),

    actions: {
        messageFrom(error, fallback) {
            return error.response?.data?.message || fallback;
        },

        applyInventoryPage(payload) {
            const page = payload?.data ? payload : { data: payload };

            this.products = Array.isArray(page.data) ? page.data : [];
            this.currentPage = Number(page.current_page) || 1;
            this.lastPage = Number(page.last_page) || 1;
            this.perPage = Number(page.per_page) || this.perPage;
            this.total = Number(page.total) || 0;
            this.stats = {
                ...emptyStats(),
                ...(page.stats || page.meta?.stats || {}),
            };
        },

        async fetchInventory(page = 1, search = this.search) {
            this.loading = true;
            this.error = null;
            this.search = search;

            try {
                const response = await axios.get("/api/inventory", {
                    params: {
                        page,
                        search: this.search || undefined,
                    },
                });

                this.applyInventoryPage(response.data);

                return response.data;
            } catch (error) {
                this.error = this.messageFrom(
                    error,
                    "Failed to load inventory.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        async adjustStock(productId, adjustment) {
            this.error = null;

            try {
                const response = await axios.post(
                    `/api/inventory/${productId}/adjust`,
                    adjustment,
                );

                const updatedProduct =
                    response.data.product || response.data.data;

                const index = this.products.findIndex(
                    (product) => product.id === productId,
                );

                if (index !== -1 && updatedProduct) {
                    this.products[index] = {
                        ...this.products[index],
                        ...updatedProduct,
                    };
                }

                return response.data;
            } catch (error) {
                this.error = this.messageFrom(
                    error,
                    "Failed to adjust stock.",
                );

                throw error;
            }
        },

        async fetchHistory(productId = null, page = 1) {
            this.historyLoading = true;
            this.historyError = null;

            try {
                const response = await axios.get("/api/inventory/history", {
                    params: {
                        page,
                        product_id: productId || undefined,
                    },
                });

                const payload = response.data?.data
                    ? response.data
                    : { data: response.data };

                this.history = Array.isArray(payload.data)
                    ? payload.data
                    : [];

                this.historyCurrentPage =
                    Number(payload.current_page) || 1;

                this.historyLastPage =
                    Number(payload.last_page) || 1;

                this.historyTotal =
                    Number(payload.total) || this.history.length;

                return response.data;
            } catch (error) {
                this.historyError = this.messageFrom(
                    error,
                    "Failed to load inventory history.",
                );

                throw error;
            } finally {
                this.historyLoading = false;
            }
        },
    },
});
