<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'

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

const emit = defineEmits([
    'update:modelValue',
    'remove-existing',
])

const preview = ref(null)
const error = ref('')
const removed = ref(false)

let objectUrl = null

// =========================================================
// PREVIEW
// =========================================================

function clearObjectUrl() {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl)
        objectUrl = null
    }
}

function createPreview(value) {
    clearObjectUrl()

    preview.value = null

    if (!value) {
        return
    }

    // Existing URL
    if (typeof value === 'string') {
        preview.value = value
        return
    }

    // New uploaded file
    if (value instanceof File) {
        objectUrl = URL.createObjectURL(value)
        preview.value = objectUrl
    }
}

// =========================================================
// WATCH MODEL
// =========================================================

watch(
    () => props.modelValue,
    (value) => {
        createPreview(value)
    },
    {
        immediate: true,
    },
)

// =========================================================
// WATCH EXISTING IMAGE
// =========================================================

watch(
    () => props.existingImage,
    (value) => {
        if (value) {
            removed.value = false
        }

        if (!props.modelValue && !removed.value) {
            createPreview(value)
        }
    },
)

// =========================================================
// FILE CHANGE
// =========================================================

function handleFileChange(event) {
    const file = event.target.files?.[0]

    error.value = ''

    if (!file) {
        return
    }

    // Validate type
    if (!file.type.startsWith('image/')) {
        error.value = 'Please select a valid image.'
        event.target.value = ''
        return
    }

    // Validate size
    if (file.size > props.maxSize * 1024 * 1024) {
        error.value =
            `Image must be smaller than ${props.maxSize}MB.`

        event.target.value = ''
        return
    }

    // New image replaces removal state
    removed.value = false

    emit(
        'update:modelValue',
        file,
    )

    createPreview(file)
}

// =========================================================
// REMOVE IMAGE
// =========================================================

function removeImage() {
    error.value = ''

    // ---------------------------------------------------------
    // New image that hasn't been saved yet
    // ---------------------------------------------------------

    if (props.modelValue instanceof File) {
        emit(
            'update:modelValue',
            null,
        )

        preview.value = null

        return
    }

    // ---------------------------------------------------------
    // Existing server image
    // ---------------------------------------------------------

    if (props.existingImage) {
        removed.value = true

        emit('remove-existing')
    }

    emit(
        'update:modelValue',
        null,
    )

    preview.value = null
}

// =========================================================
// CLEANUP
// =========================================================

onBeforeUnmount(() => {
    clearObjectUrl()
})
</script>

<template>
    <div class="space-y-3">

        <!-- Label -->

        <label
            class="block text-sm font-semibold text-gray-700"
        >
            {{ label }}
        </label>


        <!-- =====================================================
             PREVIEW
        ====================================================== -->

        <div
            v-if="
                preview ||
                (
                    existingImage &&
                    !removed
                )
            "
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
                class="absolute right-2 top-2 rounded-full bg-red-500 px-3 py-1 text-sm font-medium text-white shadow transition hover:bg-red-600"
            >
                Remove
            </button>

        </div>


        <!-- =====================================================
             UPLOAD AREA
        ====================================================== -->

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
                    d="M4 16l4.586-4.586a2 2 0 016.828 0L20 16m-2-8h.01M6 20h12a2 2 0 002 2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>

            <span
                class="text-sm font-medium text-gray-700"
            >
                Click to upload an image
            </span>

            <span
                class="mt-1 text-xs text-gray-500"
            >
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
            class="text-sm font-medium text-red-500"
        >
            {{ error }}
        </p>

    </div>
</template>
