import { defineStore } from "pinia";
import axios from "axios";

export const useRoleStore = defineStore("role", {
    state: () => ({
        roles: [],
        role: null,
        permissions: [],

        loading: false,
        error: null,
    }),

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
                    return "The requested role was not found.";

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

        // Fetch roles
        async fetchRoles() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/api/roles");

                this.roles = Array.isArray(response.data?.roles)
                    ? response.data.roles
                    : [];

                return this.roles;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load roles.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single role
        async fetchRole(roleId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    `/api/roles/${roleId}`,
                );

                this.role =
                    response.data?.role ||
                    response.data?.data ||
                    response.data;

                return this.role;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load role.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch permissions
        async fetchPermissions() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get(
                    "/api/permissions",
                );

                this.permissions = Array.isArray(
                    response.data?.permissions,
                )
                    ? response.data.permissions
                    : [];

                return this.permissions;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to load permissions.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Create role
        async createRole(roleData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/roles",
                    roleData,
                );

                const role =
                    response.data?.role ||
                    response.data?.data;

                if (role) {
                    this.roles.unshift(role);
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to create role.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Update role
        async updateRole(roleId, roleData) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.put(
                    `/api/roles/${roleId}`,
                    roleData,
                );

                const updatedRole =
                    response.data?.role ||
                    response.data?.data;

                if (updatedRole) {
                    const index = this.roles.findIndex(
                        (role) => role.id === roleId,
                    );

                    if (index !== -1) {
                        this.roles[index] = updatedRole;
                    }

                    this.role = updatedRole;
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to update role.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Delete role
        async deleteRole(roleId) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.delete(
                    `/api/roles/${roleId}`,
                );

                this.roles = this.roles.filter(
                    (role) => role.id !== roleId,
                );

                if (this.role?.id === roleId) {
                    this.role = null;
                }

                return response.data;
            } catch (error) {
                this.error = this.getErrorMessage(
                    error,
                    "Failed to delete role.",
                );

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Clear current role
        clearRole() {
            this.role = null;
        },

        // Clear store error
        clearError() {
            this.error = null;
        },
    },
});
