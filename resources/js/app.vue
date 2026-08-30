<script setup>
import BaseToast from './components/common/BaseToast.vue'
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900 transition-colors duration-200 dark:bg-gray-950 dark:text-gray-100">
        <RouterView />

        <BaseToast />
    </div>
</template>
