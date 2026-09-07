<script setup>
import Navbar from "./Navbar.vue";
</script>

<template>
    <div
        class="min-h-screen bg-gray-50 text-gray-900 transition-colors duration-200 dark:bg-gray-950 dark:text-gray-100"
    >
        <Navbar />

        <main class="min-h-screen pt-16 lg:ml-72 lg:pt-0">
            <RouterView />
        </main>
    </div>
</template>
