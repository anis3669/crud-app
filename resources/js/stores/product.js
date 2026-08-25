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
        // =====================================================
        // GET ALL PRODUCTS
        // =====================================================

        async fetchProducts() {
            this.loading = true
            this.error = null

            try {
                const response = await axios.get(
                    '/api/products'
                )

                this.products = Array.isArray(response.data?.data)
                    ? response.data.data
                    : Array.isArray(response.data)
                        ? response.data
                        : []

                return this.products
            } catch (error) {
                console.error(
                    'Failed to fetch products:',
                    error
                )

                this.error =
                    error.response?.data?.message ||
                    'Failed to load products.'

                throw error
            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // GET SINGLE PRODUCT
        // =====================================================

        async fetchProduct(productId) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.get(
                    `/api/products/${productId}`
                )

                this.product =
                    response.data?.product ??
                    response.data?.data ??
                    response.data

                return this.product
            } catch (error) {
                console.error(
                    'Failed to fetch product:',
                    error
                )

                this.error =
                    error.response?.data?.message ||
                    'Failed to load product.'

                throw error
            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // CREATE PRODUCT
        // =====================================================

        async createProduct(productData) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.post(
                    '/api/products',
                    productData
                )

                const product =
                    response.data?.product ??
                    response.data?.data ??
                    response.data

                if (product) {
                    this.products.push(product)
                }

                return product
            } catch (error) {
                console.error(
                    'Failed to create product:',
                    error
                )

                this.error =
                    error.response?.data?.message ||
                    'Failed to create product.'

                throw error
            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // UPDATE SINGLE PRODUCT
        // =====================================================

        async updateProduct(productId, productData) {
            this.loading = true
            this.error = null

            try {
                /*
                 * productData is normally FormData because
                 * a product may contain a new image.
                 */

                const response = await axios.post(
                    `/api/products/${productId}`,
                    productData
                )

                const updatedProduct =
                    response.data?.product ??
                    response.data?.data ??
                    response.data

                // -------------------------------------------------
                // Update product inside store
                // -------------------------------------------------

                const index = this.products.findIndex(
                    product =>
                        Number(product.id) ===
                        Number(productId)
                )

                if (
                    index !== -1 &&
                    updatedProduct
                ) {
                    this.products[index] =
                        updatedProduct
                }

                // -------------------------------------------------
                // Update currently selected product
                // -------------------------------------------------

                if (
                    this.product &&
                    Number(this.product.id) ===
                        Number(productId)
                ) {
                    this.product =
                        updatedProduct
                }

                return updatedProduct

            } catch (error) {
                console.error(
                    'Failed to update product:',
                    error
                )

                // -------------------------------------------------
                // Laravel validation error
                // -------------------------------------------------

                if (
                    error.response?.status === 422
                ) {
                    const errors =
                        error.response.data?.errors

                    if (errors) {
                        this.error =
                            Object.values(errors)
                                .flat()
                                .join(' ')
                    } else {
                        this.error =
                            error.response.data?.message ||
                            'Validation failed.'
                    }
                } else {
                    this.error =
                        error.response?.data?.message ||
                        error.message ||
                        'Failed to update product.'
                }

                throw error

            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // DELETE SINGLE PRODUCT
        // =====================================================

        async deleteProduct(productId) {
            this.loading = true
            this.error = null

            try {
                await axios.delete(
                    `/api/products/${productId}`
                )

                this.products =
                    this.products.filter(
                        product =>
                            Number(product.id) !==
                            Number(productId)
                    )

                if (
                    this.product &&
                    Number(this.product.id) ===
                        Number(productId)
                ) {
                    this.product = null
                }

            } catch (error) {
                console.error(
                    'Failed to delete product:',
                    error
                )

                this.error =
                    error.response?.data?.message ||
                    'Failed to delete product.'

                throw error

            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // BULK DELETE
        // =====================================================

        async bulkDelete(productsToDelete) {
            if (
                !Array.isArray(productsToDelete) ||
                productsToDelete.length === 0
            ) {
                return
            }

            this.loading = true
            this.error = null

            try {
                await Promise.all(
                    productsToDelete.map(
                        product =>
                            axios.delete(
                                `/api/products/${product.id}`
                            )
                    )
                )

                const deletedIds =
                    productsToDelete.map(
                        product =>
                            Number(product.id)
                    )

                this.products =
                    this.products.filter(
                        product =>
                            !deletedIds.includes(
                                Number(product.id)
                            )
                    )

            } catch (error) {
                console.error(
                    'Failed to bulk delete products:',
                    error
                )

                this.error =
                    error.response?.data?.message ||
                    'Failed to delete products.'

                throw error

            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // BULK UPDATE
        // =====================================================

        async bulkUpdate(products) {
            this.loading = true
            this.error = null

            try {
                // -------------------------------------------------
                // Validate input
                // -------------------------------------------------

                if (
                    !Array.isArray(products) ||
                    products.length === 0
                ) {
                    throw new Error(
                        'No products were provided.'
                    )
                }

                // -------------------------------------------------
                // Create FormData
                // -------------------------------------------------

                const formData = new FormData()

                products.forEach(
                    (product, index) => {

                        // ID
                        formData.append(
                            `products[${index}][id]`,
                            String(product.id)
                        )

                        // Name
                        formData.append(
                            `products[${index}][name]`,
                            product.name ?? ''
                        )

                        // Description
                        formData.append(
                            `products[${index}][description]`,
                            product.description ?? ''
                        )

                        // Price
                        formData.append(
                            `products[${index}][price]`,
                            String(product.price ?? '')
                        )

                        // Quantity
                        formData.append(
                            `products[${index}][quantity]`,
                            String(product.quantity ?? '')
                        )

                        // Remove existing image
                        formData.append(
                            `products[${index}][remove_image]`,
                            product.removeImage === true
                                ? '1'
                                : '0'
                        )

                        // New image
                        if (
                            product.image instanceof File
                        ) {
                            formData.append(
                                `products[${index}][image]`,
                                product.image
                            )
                        }
                    }
                )

                // -------------------------------------------------
                // Send request
                // -------------------------------------------------

                const response =
                    await axios.post(
                        '/api/products/bulk-update',
                        formData
                    )

                // -------------------------------------------------
                // Refresh products
                // -------------------------------------------------

                await this.fetchProducts()

                return response.data

            } catch (error) {
                console.error(
                    'Failed to bulk update products:',
                    error
                )

                // -------------------------------------------------
                // Validation errors
                // -------------------------------------------------

                if (
                    error.response?.status === 422
                ) {
                    const errors =
                        error.response.data?.errors

                    if (errors) {
                        this.error =
                            Object.values(errors)
                                .flat()
                                .join(' ')
                    } else {
                        this.error =
                            error.response.data?.message ||
                            'Validation failed.'
                    }
                } else {
                    this.error =
                        error.response?.data?.message ||
                        error.message ||
                        'Failed to update products.'
                }

                throw error

            } finally {
                this.loading = false
            }
        },

        // =====================================================
        // CLEAR CURRENT PRODUCT
        // =====================================================

        clearProduct() {
            this.product = null
        },

        // =====================================================
        // CLEAR ERROR
        // =====================================================

        clearError() {
            this.error = null
        },
    },
})
