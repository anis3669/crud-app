import { defineStore } from "pinia";
import axios from "axios";

export const useProductStore = defineStore("product", {
    state: () => ({
        products: [],
        product: null,

        trash: [],

        loading: false,
        trashLoading: false,

        error: null,
        trashError: null,

        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 10,

        search: "",
        filter: "all",

        stats: {
            total_products: 0,
            in_stock: 0,
            out_of_stock: 0,
            low_stock: 0,
            total_quantity: 0,
            total_inventory_value: 0,
        },

        trashCurrentPage: 1,
        trashLastPage: 1,
        trashTotal: 0,
        trashPerPage: 10,
    }),

    actions: {
        // Get normal error message
        getErrorMessage(error, fallback = "Something went wrong.") {
            const status = error.response?.status;
            const responseMessage =
                error.response?.data?.message;

            if (!error.response) {
                return "Unable to connect to the server. Please check your connection.";
            }

            switch (status) {
                case 401:
                    return "Your session has expired. Please login again.";

                case 403:
                    return "You are not authorized to perform this action.";

                case 404:
                    return "The requested resource was not found.";

                case 409:
                    return (
                        responseMessage ||
                        "This action could not be completed because of a conflict."
                    );

                case 422:
                    return (
                        responseMessage ||
                        "Please check the entered information."
                    );

                case 429:
                    return "Too many requests. Please try again later.";

                case 500:
                    return "A server error occurred. Please try again.";

                case 502:
                case 503:
                case 504:
                    return "The server is temporarily unavailable. Please try again later.";

                default:
                    return responseMessage || fallback;
            }
        },

        // Get validation errors
        getValidationErrors(error) {
            return error.response?.data?.errors || {};
        },

        // Fetch products
        async fetchProducts(
            page = 1,
            search = this.search,
            filter = this.filter,
            minPrice = null,
            maxPrice = null,
        ) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    "/api/products",
                    {
                        params: {
                            page,
                            search,
                            filter,
                            min_price: minPrice,
                            max_price: maxPrice,
                            per_page: this.perPage,
                        },
                    },
                );

                const data = response.data;

                // Read Laravel paginator
                if (
                    data?.products &&
                    Array.isArray(data.products.data)
                ) {
                    this.products = data.products.data;

                    this.currentPage =
                        data.products.current_page || 1;

                    this.lastPage =
                        data.products.last_page || 1;

                    this.total =
                        data.products.total || 0;

                    this.perPage =
                        data.products.per_page ||
                        this.perPage;
                } else {
                    this.products = [];
                    this.currentPage = 1;
                    this.lastPage = 1;
                    this.total = 0;
                }

                // Update inventory statistics
                if (data?.stats) {
                    this.stats = {
                        total_products:
                            data.stats.total_products || 0,

                        in_stock:
                            data.stats.in_stock || 0,

                        out_of_stock:
                            data.stats.out_of_stock || 0,

                        low_stock:
                            data.stats.low_stock || 0,

                        total_quantity:
                            data.stats.total_quantity || 0,

                        total_inventory_value:
                            data.stats.total_inventory_value || 0,
                    };
                }

                this.search = search;
                this.filter = filter;

                return data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load products.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single product
        async fetchProduct(productId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    `/api/products/${productId}`,
                );

                this.product =
                    response.data.product ||
                    response.data.data ||
                    response.data;

                return this.product;
            } catch (error) {
                if (error.response?.status === 404) {
                    this.error = "Product not found.";
                } else {
                    this.error = this.getErrorMessage(
                        error,
                        "Failed to load product.",
                    );
                }

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Create product
        async createProduct(productData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/products",
                    productData,
                );

                const product =
                    response.data.product ||
                    response.data.data;

                if (product) {
                    this.products.unshift(product);
                    this.total += 1;
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to create product.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Update product
        async updateProduct(productId, productData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.put(
                    `/api/products/${productId}`,
                    productData,
                );

                const updatedProduct =
                    response.data.product ||
                    response.data.data;

                const index = this.products.findIndex(
                    (product) =>
                        product.id === productId,
                );

                if (
                    index !== -1 &&
                    updatedProduct
                ) {
                    this.products[index] =
                        updatedProduct;
                }

                if (
                    this.product &&
                    this.product.id === productId &&
                    updatedProduct
                ) {
                    this.product = updatedProduct;
                }

                return response.data;
            } catch (error) {
                if (error.response?.status === 404) {
                    this.error = "Product not found.";
                } else {
                    this.error = this.getErrorMessage(
                        error,
                        "Failed to update product.",
                    );
                }

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Delete product
        async deleteProduct(productId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.delete(
                    `/api/products/${productId}`,
                );

                this.products =
                    this.products.filter(
                        (product) =>
                            product.id !== productId,
                    );

                this.total = Math.max(
                    0,
                    this.total - 1,
                );

                return response.data;
            } catch (error) {
                if (error.response?.status === 404) {
                    this.error = "Product not found.";
                } else {
                    this.error = this.getErrorMessage(
                        error,
                        "Failed to delete product.",
                    );
                }

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Bulk delete
        async bulkDelete(productsToDelete) {
            this.loading = true;
            this.error = null;

            try {
                const ids = productsToDelete.map(
                    (product) =>
                        typeof product === "object"
                            ? product.id
                            : product,
                );

                if (!ids.length) {
                    this.error =
                        "Please select at least one product.";

                    return;
                }

                const response = await axios.delete(
                    "/api/products/bulk-delete",
                    {
                        data: {
                            ids,
                        },
                    },
                );

                this.products =
                    this.products.filter(
                        (product) =>
                            !ids.includes(product.id),
                    );

                this.total = Math.max(
                    0,
                    this.total - ids.length,
                );

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to delete products.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Bulk update
        async bulkUpdate(products) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/products/bulk-update",
                    {
                        products,
                    },
                );

                await this.fetchProducts(
                    this.currentPage,
                    this.search,
                    this.filter,
                );

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to update products.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Search products
        async searchProducts(searchTerm) {
            this.search = searchTerm;

            return await this.fetchProducts(
                1,
                searchTerm,
                this.filter,
            );
        },

        // Clear search
        async clearSearch() {
            this.search = "";

            return await this.fetchProducts(
                1,
                "",
                this.filter,
            );
        },

        // Filter products
        async filterProducts(selectedFilter) {
            this.filter = selectedFilter;

            return await this.fetchProducts(
                1,
                this.search,
                selectedFilter,
            );
        },

        // Filter products by price
        async priceFilterProducts(
            minPrice,
            maxPrice,
        ) {
            return await this.fetchProducts(
                1,
                this.search,
                this.filter,
                minPrice,
                maxPrice,
            );
        },

        // Fetch trash
        async fetchTrash(page = 1) {
            this.trashLoading = true;
            this.trashError = null;

            try {
                const response = await axios.get(
                    "/api/trash",
                    {
                        params: {
                            page,
                            per_page: this.trashPerPage,
                        },
                    },
                );

                const data = response.data;

                // Read Laravel paginator
                if (
                    data &&
                    Array.isArray(data.data)
                ) {
                    this.trash = data.data;

                    this.trashCurrentPage =
                        data.current_page || 1;

                    this.trashLastPage =
                        data.last_page || 1;

                    this.trashTotal =
                        data.total || 0;

                    this.trashPerPage =
                        data.per_page ||
                        this.trashPerPage;
                } else {
                    this.trash = [];
                    this.trashCurrentPage = 1;
                    this.trashLastPage = 1;
                    this.trashTotal = 0;
                }

                return data;
            } catch (error) {
                this.trashError = this.getErrorMessage(
                    error,
                    "Failed to load trash.",
                );

                throw error;
            } finally {
                this.trashLoading = false;
            }
        },

        // Restore product
        async restoreProduct(productId) {
            this.trashLoading = true;
            this.trashError = null;

            try {
                const response = await axios.post(
                    `/api/trash/${productId}/restore`,
                );

                this.trash =
                    this.trash.filter(
                        (product) =>
                            product.id !== productId,
                    );

                this.trashTotal = Math.max(
                    0,
                    this.trashTotal - 1,
                );

                return response.data;
            } catch (error) {
                if (error.response?.status === 404) {
                    this.trashError =
                        "Deleted product not found.";
                } else {
                    this.trashError =
                        this.getErrorMessage(
                            error,
                            "Failed to restore product.",
                        );
                }

                throw error;
            } finally {
                this.trashLoading = false;
            }
        },

        // Permanently delete product
        async permanentlyDeleteProduct(
            productId,
        ) {
            this.trashLoading = true;
            this.trashError = null;

            try {
                const response = await axios.delete(
                    `/api/trash/${productId}`,
                );

                this.trash =
                    this.trash.filter(
                        (product) =>
                            product.id !== productId,
                    );

                this.trashTotal = Math.max(
                    0,
                    this.trashTotal - 1,
                );

                return response.data;
            } catch (error) {
                if (error.response?.status === 404) {
                    this.trashError =
                        "Deleted product not found.";
                } else {
                    this.trashError =
                        this.getErrorMessage(
                            error,
                            "Failed to permanently delete product.",
                        );
                }

                throw error;
            } finally {
                this.trashLoading = false;
            }
        },

        // Bulk restore
        async bulkRestore(productsToRestore) {
            this.trashLoading = true;
            this.trashError = null;

            try {
                const ids = productsToRestore.map(
                    (product) =>
                        typeof product === "object"
                            ? product.id
                            : product,
                );

                if (!ids.length) {
                    this.trashError =
                        "Please select at least one product.";

                    return;
                }

                for (const id of ids) {
                    await axios.post(
                        `/api/trash/${id}/restore`,
                    );
                }

                this.trash =
                    this.trash.filter(
                        (product) =>
                            !ids.includes(product.id),
                    );

                this.trashTotal = Math.max(
                    0,
                    this.trashTotal - ids.length,
                );

                return true;
            } catch (error) {
                this.trashError = this.getErrorMessage(
                    error,
                    "Failed to restore products.",
                );

                throw error;
            } finally {
                this.trashLoading = false;
            }
        },

        // Bulk permanently delete
        async bulkPermanentDelete(
            productsToDelete,
        ) {
            this.trashLoading = true;
            this.trashError = null;

            try {
                const ids = productsToDelete.map(
                    (product) =>
                        typeof product === "object"
                            ? product.id
                            : product,
                );

                if (!ids.length) {
                    this.trashError =
                        "Please select at least one product.";

                    return;
                }

                for (const id of ids) {
                    await axios.delete(
                        `/api/trash/${id}`,
                    );
                }

                this.trash =
                    this.trash.filter(
                        (product) =>
                            !ids.includes(product.id),
                    );

                this.trashTotal = Math.max(
                    0,
                    this.trashTotal - ids.length,
                );

                return true;
            } catch (error) {
                this.trashError =
                    this.getErrorMessage(
                        error,
                        "Failed to permanently delete products.",
                    );

                throw error;
            } finally {
                this.trashLoading = false;
            }
        },
    },
});
