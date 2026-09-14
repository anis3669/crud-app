import { defineStore } from "pinia";
import axios from "axios";

export const useNotificationStore = defineStore("notification", {
    state: () => ({
        notifications: [],
        unreadCount: 0,
        currentPage: 1,
        lastPage: 1,
        total: 0,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchNotifications(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/api/notifications", {
                    params: {
                        page,
                    },
                });

                const data = response.data.notifications;

                this.notifications = data.data;
                this.currentPage = data.current_page;
                this.lastPage = data.last_page;
                this.total = data.total;

                this.unreadCount = this.notifications.filter(
                    (notification) => !notification.read_at,
                ).length;

                return this.notifications;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to load notifications.";

                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchUnreadNotifications() {
            try {
                const response = await axios.get("/api/notifications/unread");

                const notifications = response.data.notifications;

                this.unreadCount = notifications.length;

                return notifications;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to load unread notifications.";

                throw error;
            }
        },

        async markAsRead(id) {
            try {
                await axios.patch(`/api/notifications/${id}/read`);

                const notification = this.notifications.find(
                    (item) => item.id === id,
                );

                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();

                    if (this.unreadCount > 0) {
                        this.unreadCount--;
                    }
                }
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to mark notification as read.";

                throw error;
            }
        },
    },
});
