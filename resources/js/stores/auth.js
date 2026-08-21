import { defineStore } from "pinia";

export const useAuthStore = defineStore("auth", {
    state: () => ({
        user: null,
        authenticated: false,
        loading: false,
        error: "",
        initialized: false,
    }),

    actions: {
        async getCsrfToken() {
            const response = await fetch("/sanctum/csrf-cookie", {
                credentials: "include",
                headers: {
                    Accept: "application/json",
                },
            });

            if (!response.ok) {
                throw new Error(
                    `Unable to initialize CSRF protection. Status: ${response.status}`,
                );
            }

            const cookie = document.cookie
                .split("; ")
                .find((row) => row.startsWith("XSRF-TOKEN="));

            if (!cookie) {
                throw new Error("CSRF token was not found.");
            }

            return decodeURIComponent(
                cookie.substring("XSRF-TOKEN=".length),
            );
        },

        async getResponseData(response) {
            const contentType =
                response.headers.get("content-type") || "";

            if (contentType.includes("application/json")) {
                return await response.json();
            }

            return {};
        },

        async login(email, password) {
            this.loading = true;
            this.error = "";

            try {
                const xsrfToken = await this.getCsrfToken();

                const response = await fetch("/api/login", {
                    method: "POST",
                    credentials: "include",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-XSRF-TOKEN": xsrfToken,
                    },
                    body: JSON.stringify({
                        email,
                        password,
                    }),
                });

                const data = await this.getResponseData(response);

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Login failed. Status: ${response.status}`,
                    );

                    error.status = response.status;
                    error.data = data;

                    throw error;
                }

                this.user = data.user || data;
                this.authenticated = true;

                return data;
            } catch (error) {
                this.error =
                    error.data?.message ||
                    error.message ||
                    "Unable to sign in.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        async register(formData) {
            this.loading = true;
            this.error = "";

            try {
                const xsrfToken = await this.getCsrfToken();

                const response = await fetch("/api/register", {
                    method: "POST",
                    credentials: "include",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-XSRF-TOKEN": xsrfToken,
                    },
                    body: JSON.stringify(formData),
                });

                const data = await this.getResponseData(response);

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Registration failed. Status: ${response.status}`,
                    );

                    error.status = response.status;
                    error.data = data;

                    throw error;
                }

                this.user = data.user || data;
                this.authenticated = true;

                return data;
            } catch (error) {
                this.error =
                    error.data?.message ||
                    error.message ||
                    "Unable to create your account.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        async checkAuth() {
            try {
                const response = await fetch("/api/user", {
                    credentials: "include",
                    headers: {
                        Accept: "application/json",
                    },
                });

                if (!response.ok) {
                    this.user = null;
                    this.authenticated = false;

                    return false;
                }

                const data = await response.json();

                this.user = data;
                this.authenticated = true;

                return true;
            } catch (error) {
                console.error("Auth check failed:", error);

                this.user = null;
                this.authenticated = false;

                return false;
            } finally {
                this.initialized = true;
            }
        },

        async logout() {
            try {
                const xsrfToken = await this.getCsrfToken();

                const response = await fetch("/api/logout", {
                    method: "POST",
                    credentials: "include",
                    headers: {
                        Accept: "application/json",
                        "X-XSRF-TOKEN": xsrfToken,
                    },
                });

                if (!response.ok) {
                    throw new Error("Logout failed.");
                }
            } finally {
                this.user = null;
                this.authenticated = false;
            }
        },
    },
});