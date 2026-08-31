import './bootstrap'

import '../css/app.css'

import { createApp } from 'vue'

import { createPinia } from 'pinia'

import { library } from '@fortawesome/fontawesome-svg-core'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import { fas } from '@fortawesome/free-solid-svg-icons'

import App from './App.vue'

import router from './router'

import { useThemeStore } from './stores/theme'

library.add(fas)

const app = createApp(App)

const pinia = createPinia()

app.use(pinia)

app.use(router)

app.component('font-awesome-icon', FontAwesomeIcon)

const themeStore = useThemeStore(pinia)

themeStore.initializeTheme()

app.mount('#app')
