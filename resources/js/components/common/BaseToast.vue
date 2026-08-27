<script setup>

import { useToastStore } from '../../stores/toast'

const toastStore = useToastStore()

function iconPath(type) {

    switch (type) {

        case 'success':
            return 'M5 13l4 4L19 7'

        case 'error':
            return 'M6 18L18 6M6 6l12 12'

        case 'warning':
            return 'M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.5h15.6a2 2 0 001.73-3.14l-7.82-13.5a2 2 0 00-3.42 0z'

        default:
            return 'M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 100-20 10 10 0 000 20z'
    }

}

function iconClasses(type) {

    switch (type) {

        case 'success':
            return 'bg-green-100 text-green-600'

        case 'error':
            return 'bg-red-100 text-red-600'

        case 'warning':
            return 'bg-yellow-100 text-yellow-600'

        default:
            return 'bg-blue-100 text-blue-600'
    }

}

</script>

<template>

    <div
        class="pointer-events-none fixed inset-x-0 top-4 z-[9999] flex flex-col items-center gap-3 px-4 sm:inset-x-auto sm:right-5 sm:left-auto sm:top-5 sm:w-full sm:max-w-sm"
    >

        <TransitionGroup
            name="toast"
            tag="div"
            class="flex w-full flex-col gap-3"
        >

            <div
                v-for="toast in toastStore.toasts"
                :key="toast.id"
                class="pointer-events-auto flex w-full items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-lg shadow-gray-200/50"
            >

                <!-- Icon -->

                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                    :class="iconClasses(toast.type)"
                >

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="iconPath(toast.type)"
                        />

                    </svg>

                </div>

                <!-- Message -->

                <div class="min-w-0 flex-1 pt-0.5">

                    <p
                        class="text-sm font-semibold text-gray-900"
                    >

                        {{
                            toast.type === 'success'
                                ? 'Success'
                                : toast.type === 'error'
                                    ? 'Error'
                                    : toast.type === 'warning'
                                        ? 'Warning'
                                        : 'Information'
                        }}

                    </p>

                    <p
                        class="mt-0.5 text-sm leading-5 text-gray-600"
                    >
                        {{ toast.message }}
                    </p>

                </div>

                <!-- Close -->

                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    @click="toastStore.remove(toast.id)"
                >

                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />

                    </svg>

                </button>

            </div>

        </TransitionGroup>

    </div>

</template>

<style scoped>

.toast-enter-active,
.toast-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-15px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.toast-move {
    transition: transform 0.25s ease;
}

</style>
