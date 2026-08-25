import { defineStore } from 'pinia'
import axios from 'axios'

export const useProductStore = defineStore('product', {
    state: () => ({
        // =====================================================
        // PRODUCTS
        // =====================================================

        products: [],
        product: null,

        // =====================================================
        // PAGINATION
        // =====================================================

        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,

        // =====================================================
        // SEARCH
        // =====================================================

        search: '',

        // =====================================================
        // STATE
        // =====================================================

        loading: false,
        error: null,
    }),

    actions: {

        // =====================================================
        // GET PRODUCTS
        // =====================================================

        async fetchProducts(page = 1, search = this.search) {
            this.loading = true
            this.error = null

            try {
                const response = await axios.get(
                    '/api/products',
                    {
                        params: {
                            page,
                            search,
                            per_page: this.perPage,
                        },
                    }
                )

                const data = response.data

                // -------------------------------------------------
                // Laravel pagination response
                // -------------------------------------------------

                this.products = Array.isArray(data?.data)
                    ? data.data
                    : Array.isArray(data)
                        ? data
                        : []

                // -------------------------------------------------
                // Pagination information
                // -------------------------------------------------

                this.currentPage =
                    data?.current_page ?? 1

                this.lastPage =
                    data?.last_page ?? 1

                this.perPage =
                    data?.per_page ?? this.perPage

                this.total =
                    data?.total ?? this.products.length

                // -------------------------------------------------
                // Save current search
                // -------------------------------------------------

                this.search = search

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
        // SEARCH PRODUCTS
        // =====================================================

        async searchProducts(search) {

            this.search = search

            return await this.fetchProducts(
                1,
                search
            )
        },


        // =====================================================
        // CHANGE PAGE
        // =====================================================

        async goToPage(page) {

            if (
                page < 1 ||
                page > this.lastPage ||
                page === this.currentPage
            ) {
                return
            }

            return await this.fetchProducts(
                page,
                this.search
            )
        },


        // =====================================================
        // NEXT PAGE
        // =====================================================

        async nextPage() {

            if (
                this.currentPage <
                this.lastPage
            ) {
                return await this.fetchProducts(
                    this.currentPage + 1,
                    this.search
                )
            }
        },


        // =====================================================
        // PREVIOUS PAGE
        // =====================================================

        async previousPage() {

            if (
                this.currentPage > 1
            ) {
                return await this.fetchProducts(
                    this.currentPage - 1,
                    this.search
                )
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

                /*
                 * Instead of manually pushing the product,
                 * refresh the current pagination.
                 *
                 * This keeps pagination and total count correct.
                 */

                await this.fetchProducts(
                    this.currentPage,
                    this.search
                )

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

        async updateProduct(
            productId,
            productData
        ) {

            this.loading = true
            this.error = null

            try {

                /*
                 * productData is normally FormData
                 * because products can contain images.
                 *
                 * Laravel PUT + multipart/form-data can be
                 * problematic, so we use POST with _method=PUT
                 * when FormData is being used.
                 */

                let requestData = productData

                if (
                    productData instanceof FormData
                ) {

                    productData.append(
                        '_method',
                        'PUT'
                    )

                }

                const response = await axios.post(
                    `/api/products/${productId}`,
                    requestData
                )

                const updatedProduct =
                    response.data?.product ??
                    response.data?.data ??
                    response.data

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

                // -------------------------------------------------
                // Refresh current page
                // -------------------------------------------------

                await this.fetchProducts(
                    this.currentPage,
                    this.search
                )

                return updatedProduct

            } catch (error) {

                console.error(
                    'Failed to update product:',
                    error
                )

                // -------------------------------------------------
                // Laravel validation errors
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

                if (
                    this.product &&
                    Number(this.product.id) ===
                        Number(productId)
                ) {

                    this.product = null
                }

                /*
                 * Refresh products so:
                 *
                 * - total is correct
                 * - pagination is correct
                 * - empty pages are handled
                 */

                await this.fetchProducts(
                    this.currentPage,
                    this.search
                )

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

        async bulkDelete(
            productsToDelete
        ) {

            if (
                !Array.isArray(
                    productsToDelete
                ) ||
                productsToDelete.length === 0
            ) {
                return
            }

            this.loading = true
            this.error = null

            try {

                /*
                 * Use the existing backend bulk-delete endpoint.
                 */

                const ids =
                    productsToDelete.map(
                        product =>
                            product.id
                    )

                await axios.delete(
                    '/api/products/bulk-delete',
                    {
                        data: {
                            ids,
                        },
                    }
                )

                /*
                 * Refresh current page.
                 */

                await this.fetchProducts(
                    this.currentPage,
                    this.search
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

                // Validate input

                if (
                    !Array.isArray(products) ||
                    products.length === 0
                ) {

                    throw new Error(
                        'No products were provided.'
                    )
                }

                // Create FormData

                const formData =
                    new FormData()

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
                            String(
                                product.price ?? ''
                            )
                        )

                        // Quantity

                        formData.append(
                            `products[${index}][quantity]`,
                            String(
                                product.quantity ?? ''
                            )
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

                // Send request

                const response =
                    await axios.post(
                        '/api/products/bulk-update',
                        formData
                    )

                // Refresh products

                await this.fetchProducts(
                    this.currentPage,
                    this.search
                )

                return response.data

            } catch (error) {

                console.error(
                    'Failed to bulk update products:',
                    error
                )

                // Validation errors

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



        // CLEAR CURRENT PRODUCT


        clearProduct() {
            this.product = null
        },


        // CLEAR SEARCH

        async clearSearch() {

            this.search = ''

            return await this.fetchProducts(
                1,
                ''
            )
        },


        // CLEAR ERROR


        clearError() {
            this.error = null
        },
    },
})
