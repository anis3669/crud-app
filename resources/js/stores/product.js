import { defineStore } from 'pinia'
import axios from 'axios'

export const useProductStore = defineStore('product', {
    state: () => ({
        products: [],
        product: null,
        loading: false,
        error: null,
    }),

    actions: {
        // =========================================================
        // GET ALL PRODUCTS
        // =========================================================
        async fetchProducts() {
            this.loading = true
            this.error = null

            try {
                const response = await axios.get('/api/products')

                this.products = Array.isArray(response.data?.data)
                    ? response.data.data
                    : Array.isArray(response.data)
                        ? response.data
                        : []

                return this.products
            } catch (err) {
                console.error(
                    'Failed to fetch products:',
                    err
                )

                if (err.response?.status === 401) {
                    throw err
                }

                this.error = 'Failed to load products.'
                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // GET SINGLE PRODUCT
        // =========================================================
        async fetchProduct(productId) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.get(
                    `/api/products/${productId}`
                )

                this.product =
                    response.data?.data ??
                    response.data

                return this.product
            } catch (err) {
                console.error(
                    'Failed to fetch product:',
                    err
                )

                if (err.response?.status === 401) {
                    throw err
                }

                this.error = 'Failed to load product.'
                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // CREATE PRODUCT
        // =========================================================
        async createProduct(productData) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.post(
                    '/api/products',
                    productData
                )

                const newProduct =
                    response.data?.data ??
                    response.data?.product ??
                    response.data

                // Add newly created product to store
                if (newProduct) {
                    this.products.push(newProduct)
                }

                return newProduct
            } catch (err) {
                console.error(
                    'Failed to create product:',
                    err
                )

                this.error =
                    err.response?.data?.message ||
                    'Failed to create product.'

                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // UPDATE SINGLE PRODUCT
        // =========================================================
        async updateProduct(productId, productData) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.put(
                    `/api/products/${productId}`,
                    productData
                )

                const updatedProduct =
                    response.data?.data ??
                    response.data?.product ??
                    response.data

                // Update product inside store
                const index = this.products.findIndex(
                    (product) =>
                        product.id === productId
                )

                if (index !== -1 && updatedProduct) {
                    this.products[index] =
                        updatedProduct
                }

                // Update currently viewed product
                if (
                    this.product &&
                    this.product.id === productId &&
                    updatedProduct
                ) {
                    this.product = updatedProduct
                }

                return updatedProduct
            } catch (err) {
                console.error(
                    'Failed to update product:',
                    err
                )

                this.error =
                    err.response?.data?.message ||
                    'Failed to update product.'

                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // DELETE SINGLE PRODUCT
        // =========================================================
        async deleteProduct(productId) {
            this.loading = true
            this.error = null

            try {
                await axios.delete(
                    `/api/products/${productId}`
                )

                this.products =
                    this.products.filter(
                        (product) =>
                            product.id !== productId
                    )

                if (
                    this.product?.id === productId
                ) {
                    this.product = null
                }
            } catch (err) {
                console.error(
                    'Failed to delete product:',
                    err
                )

                this.error =
                    'Failed to delete product.'

                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // BULK DELETE
        // =========================================================
        async bulkDelete(productsToDelete) {
            if (!Array.isArray(productsToDelete)) {
                return
            }

            if (productsToDelete.length === 0) {
                return
            }

            this.loading = true
            this.error = null

            try {
                await Promise.all(
                    productsToDelete.map(
                        (product) =>
                            axios.delete(
                                `/api/products/${product.id}`
                            )
                    )
                )

                const deletedIds =
                    productsToDelete.map(
                        (product) => product.id
                    )

                this.products =
                    this.products.filter(
                        (product) =>
                            !deletedIds.includes(
                                product.id
                            )
                    )
            } catch (err) {
                console.error(
                    'Failed to bulk delete products:',
                    err
                )

                this.error =
                    'Failed to delete products.'

                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // BULK UPDATE
        // =========================================================
        async bulkUpdate(products) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.put(
                    '/api/products/bulk-update',
                    {
                        products,
                    }
                )

                // Refresh store after bulk update
                await this.fetchProducts()

                return response.data
            } catch (err) {
                console.error(
                    'Failed to bulk update products:',
                    err
                )

                this.error =
                    'Failed to update products.'

                throw err
            } finally {
                this.loading = false
            }
        },

        // =========================================================
        // CLEAR CURRENT PRODUCT
        // =========================================================
        clearProduct() {
            this.product = null
        },

        // =========================================================
        // CLEAR ERROR
        // =========================================================
        clearError() {
            this.error = null
        },
    },
})
