<script setup>
import { watch } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },

    title: {
        type: String,
        default: '',
    },

    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
    },

    closeOnBackdrop: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits([
    'close',
])

const sizeClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
}

function close() {
    emit('close')
}

function handleBackdropClick() {
    if (props.closeOnBackdrop) {
        close()
    }
}

function handleKeydown(event) {
    if (event.key === 'Escape' && props.show) {
        close()
    }
}

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            document.addEventListener('keydown', handleKeydown)
            document.body.style.overflow = 'hidden'
        } else {
            document.removeEventListener('keydown', handleKeydown)
            document.body.style.overflow = ''
        }
    }
)
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="handleBackdropClick"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="handleBackdropClick"
                ></div>

                <!-- Modal -->
                <div
                    :class="sizeClasses[size]"
                    class="relative z-10 w-full overflow-hidden rounded-xl bg-white shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-gray-200 px-6 py-4"
                    >
                        <slot name="header">
                            <h2
                                class="text-lg font-semibold text-gray-900"
                            >
                                {{ title }}
                            </h2>
                        </slot>

                        <button
                            type="button"
                            @click="close"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Close modal"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-5">
                        <slot>
                            Modal content
                        </slot>
                    </div>

                    <!-- Footer -->
                    <div
                        v-if="$slots.footer"
                        class="border-t border-gray-100 bg-gray-50 px-6 py-4"
                    >
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
