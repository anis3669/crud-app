<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: [File, String, null],
        default: null,
    },

    existingImage: {
        type: String,
        default: null,
    },

    label: {
        type: String,
        default: 'Image',
    },

    accept: {
        type: String,
        default: 'image/jpeg,image/png,image/jpg,image/webp',
    },

    maxSize: {
        type: Number,
        default: 5,
    },
})

const emit = defineEmits(['update:modelValue'])

const preview = ref(null)
const error = ref('')

const createPreview = (file) => {
    if (!file) {
        preview.value = null
        return
    }

    if (typeof file === 'string') {
        preview.value = file
        return
    }

    preview.value = URL.createObjectURL(file)
}

watch(
    () => props.modelValue,
    (value) => {
        createPreview(value)
    },
    { immediate: true }
)

const handleFileChange = (event) => {
    const file = event.target.files[0]

    error.value = ''

    if (!file) {
        return
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
        error.value = 'Please select a valid image.'
        event.target.value = ''
        return
    }

    // Validate file size
    if (file.size > props.maxSize * 1024 * 1024) {
        error.value = `Image must be smaller than ${props.maxSize}MB.`
        event.target.value = ''
        return
    }

    emit('update:modelValue', file)
    createPreview(file)
}

const removeImage = () => {
    preview.value = null
    error.value = ''

    emit('update:modelValue', null)
}
</script>

<template>
    <div class="space-y-3">

        <!-- Label -->
        <label class="block text-sm font-medium text-gray-700">
            {{ label }}
        </label>

        <!-- Image preview -->
        <div
            v-if="preview || existingImage"
            class="relative w-full max-w-sm overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
        >
            <img
                :src="preview || existingImage"
                alt="Image preview"
                class="block h-64 w-full object-contain"
            />

            <button
                type="button"
                @click="removeImage"
                class="absolute right-2 top-2 rounded-full bg-red-500 px-3 py-1 text-sm text-white shadow hover:bg-red-600"
            >
                Remove
            </button>
        </div>

        <!-- Upload area -->
        <label
            class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center transition hover:border-gray-400 hover:bg-gray-100"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mb-2 h-8 w-8 text-gray-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 016.828 0L20 16m-2-8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>

            <span class="text-sm font-medium text-gray-700">
                Click to upload an image
            </span>

            <span class="mt-1 text-xs text-gray-500">
                JPG, PNG, JPEG or WebP · Max {{ maxSize }}MB
            </span>

            <input
                type="file"
                class="hidden"
                :accept="accept"
                @change="handleFileChange"
            />
        </label>

        <!-- Error -->
        <p
            v-if="error"
            class="text-sm text-red-500"
        >
            {{ error }}
        </p>

    </div>
</template>
