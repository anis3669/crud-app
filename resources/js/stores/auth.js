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

        // USER


        setUser(user, profilePictureUrl = undefined) {
            if (!user) {
                this.user = null;
                this.authenticated = false;
                return;
            }

            const picture =
                profilePictureUrl !== undefined
                    ? profilePictureUrl
                    : user.profile_picture_url ?? null;

            this.user = {
                ...user,
                profile_picture_url: picture,
            };

            this.authenticated = true;
        },


        // CSRF


        async getCsrfToken() {
            const response = await fetch(
                "/sanctum/csrf-cookie",
                {
                    credentials: "include",
                    headers: {
                        Accept: "application/json",
                    },
                }
            );

            if (!response.ok) {
                throw new Error(
                    `Unable to initialize CSRF protection. Status: ${response.status}`
                );
            }

            const cookie = document.cookie
                .split("; ")
                .find((row) =>
                    row.startsWith("XSRF-TOKEN=")
                );

            if (!cookie) {
                throw new Error(
                    "CSRF token was not found."
                );
            }

            return decodeURIComponent(
                cookie.substring("XSRF-TOKEN=".length)
            );
        },


        // RESPONSE


        async getResponseData(response) {
            const contentType =
                response.headers.get("content-type") || "";

            if (
                contentType.includes(
                    "application/json"
                )
            ) {
                return await response.json();
            }

            return {};
        },


        // LOGIN


        async login(email, password) {
            this.loading = true;
            this.error = "";

            try {
                const xsrfToken =
                    await this.getCsrfToken();

                const response = await fetch(
                    "/api/login",
                    {
                        method: "POST",
                        credentials: "include",
                        headers: {
                            "Content-Type":
                                "application/json",
                            Accept:
                                "application/json",
                            "X-XSRF-TOKEN":
                                xsrfToken,
                        },
                        body: JSON.stringify({
                            email,
                            password,
                        }),
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Login failed. Status: ${response.status}`
                    );

                    error.status =
                        response.status;

                    error.data = data;

                    throw error;
                }

                this.setUser(
                    data.user ?? data,
                    data.profile_picture_url
                );

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


        // REGISTER


        async register(formData) {
            this.loading = true;
            this.error = "";

            try {
                const xsrfToken =
                    await this.getCsrfToken();

                const response = await fetch(
                    "/api/register",
                    {
                        method: "POST",
                        credentials: "include",
                        headers: {
                            "Content-Type":
                                "application/json",
                            Accept:
                                "application/json",
                            "X-XSRF-TOKEN":
                                xsrfToken,
                        },
                        body: JSON.stringify(
                            formData
                        ),
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Registration failed. Status: ${response.status}`
                    );

                    error.status =
                        response.status;

                    error.data = data;

                    throw error;
                }

                this.setUser(
                    data.user ?? data,
                    data.profile_picture_url
                );

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


        // CHECK AUTH


        async checkAuth() {
            try {
                const response = await fetch(
                    "/api/user",
                    {
                        credentials: "include",
                        headers: {
                            Accept:
                                "application/json",
                        },
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    this.setUser(null);

                    return false;
                }

                this.setUser(
                    data.user ?? data,
                    data.profile_picture_url
                );

                return true;
            } catch (error) {
                console.error(
                    "Auth check failed:",
                    error
                );

                this.setUser(null);

                return false;
            } finally {
                this.initialized = true;
            }
        },


        // LOAD PROFILE


        async loadProfile() {
            this.loading = true;
            this.error = "";

            try {
                const response = await fetch(
                    "/api/profile",
                    {
                        method: "GET",
                        credentials: "include",
                        headers: {
                            Accept:
                                "application/json",
                        },
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Unable to load profile. Status: ${response.status}`
                    );

                    error.status =
                        response.status;

                    error.data = data;

                    throw error;
                }

                this.setUser(
                    data.user,
                    data.profile_picture_url
                );

                return data;
            } catch (error) {
                this.error =
                    error.data?.message ||
                    error.message ||
                    "Unable to load profile.";

                throw error;
            } finally {
                this.loading = false;
            }
        },


        // UPDATE PROFILE


        async updateProfile({
            name,
            email,
        }) {
            this.loading = true;
            this.error = "";

            try {
                const xsrfToken =
                    await this.getCsrfToken();

                const response = await fetch(
                    "/api/profile",
                    {
                        method: "PUT",
                        credentials: "include",
                        headers: {
                            "Content-Type":
                                "application/json",
                            Accept:
                                "application/json",
                            "X-XSRF-TOKEN":
                                xsrfToken,
                        },
                        body: JSON.stringify({
                            name,
                            email,
                        }),
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Profile update failed. Status: ${response.status}`
                    );

                    error.status =
                        response.status;

                    error.data = data;

                    throw error;
                }

                /*
                 * Laravel returns the updated user
                 * and the current picture URL.
                 */
                this.setUser(
                    data.user ?? this.user,
                    data.profile_picture_url !==
                        undefined
                        ? data.profile_picture_url
                        : this.user?.profile_picture_url
                );

                return data;
            } catch (error) {
                this.error =
                    error.data?.message ||
                    error.message ||
                    "Unable to update profile.";

                throw error;
            } finally {
                this.loading = false;
            }
        },


        // UPLOAD PROFILE PICTURE


        async uploadProfilePicture(file) {
            if (!(file instanceof File)) {
                throw new Error(
                    "Please select a valid image."
                );
            }

            this.loading = true;
            this.error = "";

            try {
                const xsrfToken =
                    await this.getCsrfToken();

                const formData =
                    new FormData();

                formData.append(
                    "profile_picture",
                    file
                );

                const response = await fetch(
                    "/api/profile/picture",
                    {
                        method: "POST",
                        credentials: "include",
                        headers: {
                            Accept:
                                "application/json",
                            "X-XSRF-TOKEN":
                                xsrfToken,
                        },
                        body: formData,
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Profile picture upload failed. Status: ${response.status}`
                    );

                    error.status =
                        response.status;

                    error.data = data;

                    throw error;
                }

                /*
                 * Immediately update the global user.
                 *
                 * Navbar will react automatically
                 * because it uses authStore.user.
                 */
                this.setUser(
                    data.user ?? this.user,
                    data.profile_picture_url
                );

                return data;
            } catch (error) {
                this.error =
                    error.data?.message ||
                    error.message ||
                    "Unable to upload profile picture.";

                throw error;
            } finally {
                this.loading = false;
            }
        },


        // DELETE PROFILE PICTURE


        async deleteProfilePicture() {
            this.loading = true;
            this.error = "";

            try {
                const xsrfToken =
                    await this.getCsrfToken();

                const response = await fetch(
                    "/api/profile/picture",
                    {
                        method: "DELETE",
                        credentials: "include",
                        headers: {
                            Accept:
                                "application/json",
                            "X-XSRF-TOKEN":
                                xsrfToken,
                        },
                    }
                );

                const data =
                    await this.getResponseData(
                        response
                    );

                if (!response.ok) {
                    const error = new Error(
                        data.message ||
                            `Unable to remove profile picture. Status: ${response.status}`
                    );

                    error.status =
                        response.status;

                    error.data = data;

                    throw error;
                }

                /*
                 * Explicitly set picture URL to null.
                 *
                 * This makes the navbar immediately
                 * return to the user's initial.
                 */
                this.setUser(
                    data.user ?? this.user,
                    null
                );

                return data;
            } catch (error) {
                this.error =
                    error.data?.message ||
                    error.message ||
                    "Unable to remove profile picture.";

                throw error;
            } finally {
                this.loading = false;
            }
        },


        // LOGOUT


        async logout() {
            try {
                const xsrfToken =
                    await this.getCsrfToken();

                const response = await fetch(
                    "/api/logout",
                    {
                        method: "POST",
                        credentials: "include",
                        headers: {
                            Accept:
                                "application/json",
                            "X-XSRF-TOKEN":
                                xsrfToken,
                        },
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        "Logout failed."
                    );
                }
            } finally {
                this.user = null;
                this.authenticated = false;
                this.error = "";
            }
        },
    },
});
