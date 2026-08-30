import { defineStore } from "pinia";
import axios from "axios";

export const useProductStore = defineStore("product", {
    state: () => ({
        products: [],
        product: null,

        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,

        search: "",
        filter: "all",

        minPrice: null,
        maxPrice: null,

        stats: {
            total_products: 0,
            in_stock: 0,
            out_of_stock: 0,
            total_quantity: 0,
            total_inventory_value: 0,
        },

        loading: false,
        error: null,
    }),

    actions: {
        // GET PRODUCTS

        async fetchProducts(
            page = 1,
            search = this.search,
            filter = this.filter,
            minPrice = this.minPrice,
            maxPrice = this.maxPrice,
        ) {
            this.loading = true;
            this.error = null;

            try {
                const safePage = Math.max(1, Number(page) || 1);
                const safePerPage = Math.min(
                    100,
                    Math.max(1, Number(this.perPage) || 10),
                );

                const response = await axios.get("/api/products", {
                    params: {
                        page: safePage,
                        per_page: safePerPage,
                        search: search || "",
                        filter: filter || "all",

                        ...(minPrice !== null &&
                        minPrice !== undefined &&
                        minPrice !== ""
                            ? { min_price: minPrice }
                            : {}),

                        ...(maxPrice !== null &&
                        maxPrice !== undefined &&
                        maxPrice !== ""
                            ? { max_price: maxPrice }
                            : {}),
                    },
                });

                const data = response.data || {};
                const productsData = data.products || {};

                this.products = Array.isArray(productsData.data)
                    ? productsData.data
                    : [];

                this.currentPage = Number(
                    productsData.current_page || safePage,
                );

                this.lastPage = Number(productsData.last_page || 1);

                this.perPage = Math.min(
                    100,
                    Math.max(
                        1,
                        Number(productsData.per_page || safePerPage),
                    ),
                );

                this.total = Number(
                    productsData.total || this.products.length,
                );

                this.stats = {
                    total_products: Number(
                        data.stats?.total_products || 0,
                    ),

                    in_stock: Number(
                        data.stats?.in_stock || 0,
                    ),

                    out_of_stock: Number(
                        data.stats?.out_of_stock || 0,
                    ),

                    total_quantity: Number(
                        data.stats?.total_quantity || 0,
                    ),

                    total_inventory_value: Number(
                        data.stats?.total_inventory_value || 0,
                    ),
                };

                this.search = search || "";
                this.filter = filter || "all";

                this.minPrice =
                    minPrice !== null &&
                    minPrice !== undefined &&
                    minPrice !== ""
                        ? minPrice
                        : null;

                this.maxPrice =
                    maxPrice !== null &&
                    maxPrice !== undefined &&
                    maxPrice !== ""
                        ? maxPrice
                        : null;

                return this.products;
            } catch (error) {
                console.error("Failed to fetch products:", error);

                this.error =
                    error.response?.data?.message ||
                    "Failed to load products.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // SEARCH

        async searchProducts(search) {
            this.search = search || "";

            return await this.fetchProducts(
                1,
                this.search,
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // PRODUCT FILTER

        async filterProducts(filter) {
            this.filter = filter || "all";

            return await this.fetchProducts(
                1,
                this.search,
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // PRICE FILTER

        async priceFilterProducts(minPrice = null, maxPrice = null) {
            this.minPrice = minPrice;
            this.maxPrice = maxPrice;

            return await this.fetchProducts(
                1,
                this.search,
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // GO TO PAGE

        async goToPage(page) {
            const targetPage = Number(page);

            if (
                !Number.isInteger(targetPage) ||
                targetPage < 1 ||
                targetPage > this.lastPage ||
                targetPage === this.currentPage
            ) {
                return;
            }

            return await this.fetchProducts(
                targetPage,
                this.search,
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // NEXT PAGE

        async nextPage() {
            if (this.currentPage >= this.lastPage) {
                return;
            }

            return await this.fetchProducts(
                this.currentPage + 1,
                this.search,
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // PREVIOUS PAGE

        async previousPage() {
            if (this.currentPage <= 1) {
                return;
            }

            return await this.fetchProducts(
                this.currentPage - 1,
                this.search,
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // GET SINGLE PRODUCT

        async fetchProduct(productId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    `/api/products/${productId}`,
                );

                this.product =
                    response.data?.product ??
                    response.data?.data ??
                    response.data;

                return this.product;
            } catch (error) {
                console.error("Failed to fetch product:", error);

                this.error =
                    error.response?.data?.message ||
                    "Failed to load product.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // CREATE PRODUCT

        async createProduct(productData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/products",
                    productData,
                );

                const product =
                    response.data?.product ??
                    response.data?.data ??
                    response.data;

                await this.fetchProducts(
                    this.currentPage,
                    this.search,
                    this.filter,
                    this.minPrice,
                    this.maxPrice,
                );

                return product;
            } catch (error) {
                console.error("Failed to create product:", error);

                this.error =
                    error.response?.data?.message ||
                    "Failed to create product.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // UPDATE PRODUCT

        async updateProduct(productId, productData) {
            this.loading = true;
            this.error = null;

            try {
                let requestData = productData;

                if (productData instanceof FormData) {
                    productData.append("_method", "PUT");
                }

                const response = await axios.post(
                    `/api/products/${productId}`,
                    requestData,
                );

                const updatedProduct =
                    response.data?.product ??
                    response.data?.data ??
                    response.data;

                if (
                    this.product &&
                    Number(this.product.id) === Number(productId)
                ) {
                    this.product = updatedProduct;
                }

                await this.fetchProducts(
                    this.currentPage,
                    this.search,
                    this.filter,
                    this.minPrice,
                    this.maxPrice,
                );

                return updatedProduct;
            } catch (error) {
                console.error("Failed to update product:", error);

                if (error.response?.status === 422) {
                    const errors = error.response.data?.errors;

                    if (errors) {
                        this.error = Object.values(errors)
                            .flat()
                            .join(" ");
                    } else {
                        this.error =
                            error.response.data?.message ||
                            "Validation failed.";
                    }
                } else {
                    this.error =
                        error.response?.data?.message ||
                        error.message ||
                        "Failed to update product.";
                }

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // DELETE PRODUCT

        async deleteProduct(productId) {
            this.loading = true;
            this.error = null;

            try {
                await axios.delete(
                    `/api/products/${productId}`,
                );

                if (
                    this.product &&
                    Number(this.product.id) === Number(productId)
                ) {
                    this.product = null;
                }

                await this.fetchProducts(
                    this.currentPage,
                    this.search,
                    this.filter,
                    this.minPrice,
                    this.maxPrice,
                );
            } catch (error) {
                console.error("Failed to delete product:", error);

                this.error =
                    error.response?.data?.message ||
                    "Failed to delete product.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // BULK DELETE

        async bulkDelete(productsToDelete) {
            if (
                !Array.isArray(productsToDelete) ||
                productsToDelete.length === 0
            ) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const ids = productsToDelete.map(
                    (product) => product.id,
                );

                await axios.delete(
                    "/api/products/bulk-delete",
                    {
                        data: {
                            ids,
                        },
                    },
                );

                await this.fetchProducts(
                    this.currentPage,
                    this.search,
                    this.filter,
                    this.minPrice,
                    this.maxPrice,
                );
            } catch (error) {
                console.error(
                    "Failed to bulk delete products:",
                    error,
                );

                this.error =
                    error.response?.data?.message ||
                    "Failed to delete products.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // BULK UPDATE

        async bulkUpdate(products) {
            if (
                !Array.isArray(products) ||
                products.length === 0
            ) {
                throw new Error(
                    "No products were provided.",
                );
            }

            this.loading = true;
            this.error = null;

            try {
                const formData = new FormData();

                products.forEach((product, index) => {
                    formData.append(
                        `products[${index}][id]`,
                        String(product.id),
                    );

                    formData.append(
                        `products[${index}][name]`,
                        product.name ?? "",
                    );

                    formData.append(
                        `products[${index}][description]`,
                        product.description ?? "",
                    );

                    formData.append(
                        `products[${index}][price]`,
                        String(product.price ?? ""),
                    );

                    formData.append(
                        `products[${index}][quantity]`,
                        String(product.quantity ?? ""),
                    );

                    formData.append(
                        `products[${index}][remove_image]`,
                        product.removeImage === true
                            ? "1"
                            : "0",
                    );

                    if (product.image instanceof File) {
                        formData.append(
                            `products[${index}][image]`,
                            product.image,
                        );
                    }
                });

                const response = await axios.post(
                    "/api/products/bulk-update",
                    formData,
                );

                await this.fetchProducts(
                    this.currentPage,
                    this.search,
                    this.filter,
                    this.minPrice,
                    this.maxPrice,
                );

                return response.data;
            } catch (error) {
                console.error(
                    "Failed to bulk update products:",
                    error,
                );

                if (error.response?.status === 422) {
                    const errors =
                        error.response.data?.errors;

                    if (errors) {
                        this.error = Object.values(errors)
                            .flat()
                            .join(" ");
                    } else {
                        this.error =
                            error.response.data?.message ||
                            "Validation failed.";
                    }
                } else {
                    this.error =
                        error.response?.data?.message ||
                        error.message ||
                        "Failed to update products.";
                }

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // CLEAR PRODUCT

        clearProduct() {
            this.product = null;
        },

        // CLEAR SEARCH

        async clearSearch() {
            this.search = "";

            return await this.fetchProducts(
                1,
                "",
                this.filter,
                this.minPrice,
                this.maxPrice,
            );
        },

        // CLEAR FILTER

        async clearFilter() {
            this.filter = "all";

            return await this.fetchProducts(
                1,
                this.search,
                "all",
                this.minPrice,
                this.maxPrice,
            );
        },

        // CLEAR PRICE FILTER

        async clearPriceFilter() {
            this.minPrice = null;
            this.maxPrice = null;

            return await this.fetchProducts(
                1,
                this.search,
                this.filter,
                null,
                null,
            );
        },

        // CLEAR ERROR

        clearError() {
            this.error = null;
        },
    },
});
