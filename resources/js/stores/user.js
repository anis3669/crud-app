import { defineStore } from "pinia";
import axios from "axios";

export const useUserStore = defineStore("user", {
    state: () => ({
        users: [],
        user: null,

        loading: false,
        error: null,

        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0,

        search: "",
        role: "",
    }),

    getters: {
        hasPreviousPage: (state) => state.currentPage > 1,

        hasNextPage: (state) =>
            state.currentPage < state.lastPage,
    },

    actions: {
        // Get error message
        getErrorMessage(
            error,
            fallback = "Something went wrong.",
        ) {
            const status = error.response?.status;
            const message = error.response?.data?.message;

            if (!error.response) {
                return "Unable to connect to the server. Please check your connection.";
            }

            switch (status) {
                case 401:
                    return "Your session has expired. Please login again.";

                case 403:
                    return "You are not authorized to perform this action.";

                case 404:
                    return "The requested user was not found.";

                case 409:
                    return (
                        message ||
                        "This action could not be completed because of a conflict."
                    );

                case 422:
                    return (
                        message ||
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
                    return message || fallback;
            }
        },

        // Get validation errors
        getValidationErrors(error) {
            return error.response?.data?.errors || {};
        },

        // Fetch users
        async fetchUsers(
            page = 1,
            search = this.search,
            role = this.role,
        ) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    "/api/users",
                    {
                        params: {
                            page,
                            search: search?.trim() || undefined,
                            role: role || undefined,
                            per_page: this.perPage,
                        },
                    },
                );

                const data = response.data;

                this.users = Array.isArray(data?.data)
                    ? data.data
                    : [];

                this.currentPage =
                    Number(data?.current_page) || 1;

                this.lastPage =
                    Number(data?.last_page) || 1;

                this.perPage =
                    Number(data?.per_page) || this.perPage;

                this.total =
                    Number(data?.total) || 0;

                this.search = search || "";
                this.role = role || "";

                return data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load users.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single user
        async fetchUser(userId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    `/api/users/${userId}`,
                );

                this.user =
                    response.data?.user ||
                    response.data?.data ||
                    response.data;

                return this.user;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load user.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Create user
        async createUser(userData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/users",
                    userData,
                );

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to create user.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Update user
        async updateUser(userId, userData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.put(
                    `/api/users/${userId}`,
                    userData,
                );

                this.user =
                    response.data?.user ||
                    response.data?.data ||
                    this.user;

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to update user.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Delete user
        async deleteUser(userId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.delete(
                    `/api/users/${userId}`,
                );

                this.users = this.users.filter(
                    (user) => user.id !== userId,
                );

                if (this.total > 0) {
                    this.total -= 1;
                }

                if (this.user?.id === userId) {
                    this.user = null;
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to delete user.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Clear current user
        clearUser() {
            this.user = null;
        },

        // Clear store error
        clearError() {
            this.error = null;
        },
    },
});
