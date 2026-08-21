<script setup>
import Navbar from './Navbar.vue'
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <Navbar />

        <main>
            <RouterView />
        </main>

    </div>
</template>