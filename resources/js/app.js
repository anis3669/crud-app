import "./bootstrap";

import "../css/app.css";

import { createApp } from "vue";

import { createPinia } from "pinia";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

import App from "./App.vue";

import router from "./router";

import { useThemeStore } from "./stores/theme";

const app = createApp(App);

const pinia = createPinia();

app.use(pinia);

app.use(router);

app.component("font-awesome-icon", FontAwesomeIcon);

const themeStore = useThemeStore(pinia);

themeStore.initializeTheme();

app.mount("#app");
