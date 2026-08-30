import { defineStore } from "pinia";

export const useThemeStore = defineStore("theme", {
    state: () => ({
        dark: localStorage.getItem("theme") === "dark",
    }),

    actions: {
        toggleTheme() {
            this.dark = !this.dark;

            if (this.dark) {
                document.documentElement.classList.add("dark");
                localStorage.setItem("theme", "dark");
            } else {
                document.documentElement.classList.remove("dark");
                localStorage.setItem("theme", "light");
            }
        },

        initializeTheme() {
            if (this.dark) {
                document.documentElement.classList.add("dark");
            } else {
                document.documentElement.classList.remove("dark");
            }
        },
    },
});
