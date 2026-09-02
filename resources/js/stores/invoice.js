import { defineStore } from "pinia";
import axios from "axios";

export const useInvoiceStore = defineStore("invoice", {
    state: () => ({
        invoices: [],
        invoice: null,

        loading: false,
        error: null,

        currentPage: 1,
        lastPage: 1,
        total: 0,
        perPage: 10,

        search: "",
        status: "",
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
                    return "The requested invoice was not found.";

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

        // Fetch invoices
        async fetchInvoices(
            page = 1,
            search = this.search,
            status = this.status,
        ) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    "/api/invoices",
                    {
                        params: {
                            page,
                            search: search || undefined,
                            status: status || undefined,
                            per_page: this.perPage,
                        },
                    },
                );

                const data = response.data;

                // Read Laravel paginator
                this.invoices = Array.isArray(data?.data)
                    ? data.data
                    : [];

                this.currentPage =
                    Number(data?.current_page) || 1;

                this.lastPage =
                    Number(data?.last_page) || 1;

                this.total =
                    Number(data?.total) || 0;

                this.perPage =
                    Number(data?.per_page) || this.perPage;

                this.search = search;
                this.status = status;

                return data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load invoices.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single invoice
        async fetchInvoice(invoiceId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    `/api/invoices/${invoiceId}`,
                );

                this.invoice =
                    response.data.invoice ||
                    response.data.data ||
                    response.data;

                return this.invoice;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load invoice.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Create invoice
        async createInvoice(invoiceData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/invoices",
                    invoiceData,
                );

                const invoice =
                    response.data.invoice ||
                    response.data.data;

                if (invoice) {
                    this.invoices.unshift(invoice);
                    this.total += 1;
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to create invoice.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Delete invoice
        async deleteInvoice(invoiceId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.delete(
                    `/api/invoices/${invoiceId}`,
                );

                this.invoices = this.invoices.filter(
                    (invoice) => invoice.id !== invoiceId,
                );

                if (this.total > 0) {
                    this.total -= 1;
                }

                if (this.invoice?.id === invoiceId) {
                    this.invoice = null;
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to delete invoice.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Clear current invoice
        clearInvoice() {
            this.invoice = null;
        },

        // Clear store error
        clearError() {
            this.error = null;
        },
    },
});
