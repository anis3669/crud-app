import { defineStore } from 'pinia'

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
        nextId: 1,
    }),

    actions: {
        add(message, type = 'info', duration = 3000) {
            const id = this.nextId++

            this.toasts.push({
                id,
                message,
                type,
            })

            if (duration > 0) {
                setTimeout(() => {
                    this.remove(id)
                }, duration)
            }

            return id
        },

        success(message, duration = 3000) {
            return this.add(
                message,
                'success',
                duration
            )
        },

        error(message, duration = 4000) {
            return this.add(
                message,
                'error',
                duration
            )
        },

        warning(message, duration = 3500) {
            return this.add(
                message,
                'warning',
                duration
            )
        },

        info(message, duration = 3000) {
            return this.add(
                message,
                'info',
                duration
            )
        },

        remove(id) {
            this.toasts =
                this.toasts.filter(
                    toast => toast.id !== id
                )
        },

        clear() {
            this.toasts = []
        },
    },
})
