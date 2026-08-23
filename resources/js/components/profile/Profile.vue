<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCamera } from '@fortawesome/free-solid-svg-icons'

const router = useRouter()
const authStore = useAuthStore()
const cameraIcon = faCamera

// STATE

const user = computed(() => authStore.user)

const editing = ref(false)
const saving = ref(false)
const uploading = ref(false)
const deleting = ref(false)

const name = ref('')
const email = ref('')

const selectedFile = ref(null)
const previewUrl = ref(null)

const successMessage = ref('')
const errorMessage = ref('')

const fileInput = ref(null)


// PROFILE PICTURE


const profilePicture = computed(() => {

    // If user has a picture from Laravel

    if (user.value?.profile_picture) {

        // If backend already gives full URL

        if (user.value.profile_picture.startsWith('http')) {
            return user.value.profile_picture
        }

        // Otherwise construct Laravel storage URL
        return `/storage/${user.value.profile_picture}`
    }

    return null
})

const userInitial = computed(() => {
    return (
        user.value?.name
            ?.charAt(0)
            ?.toUpperCase() || 'U'
    )
})


// LOAD PROFILE


async function loadProfile() {
    try {
        await authStore.loadProfile()

        name.value = user.value?.name || ''
        email.value = user.value?.email || ''
    } catch (error) {
        errorMessage.value =
            error.message || 'Unable to load profile.'
    }
}


// EDIT


function startEditing() {
    name.value = user.value?.name || ''
    email.value = user.value?.email || ''

    editing.value = true
    successMessage.value = ''
    errorMessage.value = ''
}

function cancelEditing() {
    name.value = user.value?.name || ''
    email.value = user.value?.email || ''

    editing.value = false
    errorMessage.value = ''
}


// UPDATE PROFILE


async function saveProfile() {
    if (!name.value.trim()) {
        errorMessage.value = 'Name is required.'
        return
    }

    if (!email.value.trim()) {
        errorMessage.value = 'Email is required.'
        return
    }

    saving.value = true
    successMessage.value = ''
    errorMessage.value = ''

    try {
        await authStore.updateProfile({
            name: name.value.trim(),
            email: email.value.trim(),
        })

        editing.value = false

        successMessage.value =
            'Profile updated successfully.'

        // Hide message after a few seconds
        setTimeout(() => {
            successMessage.value = ''
        }, 3000)
    } catch (error) {
        errorMessage.value =
            error.data?.message ||
            error.message ||
            'Unable to update profile.'
    } finally {
        saving.value = false
    }
}


// FILE INPUT


function openFilePicker() {
    fileInput.value?.click()
}

function handleFileChange(event) {
    const file = event.target.files?.[0]

    if (!file) {
        return
    }

    // Validate file type
    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ]

    if (!allowedTypes.includes(file.type)) {
        errorMessage.value =
            'Please select a JPG, PNG, or WEBP image.'

        event.target.value = ''
        return
    }

    // Validate size - 2MB
    if (file.size > 2 * 1024 * 1024) {
        errorMessage.value =
            'Profile picture must be smaller than 2MB.'

        event.target.value = ''
        return
    }

    selectedFile.value = file

    // Create preview
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
    }

    previewUrl.value = URL.createObjectURL(file)

    uploadProfilePicture()
}


// UPLOAD PICTURE


async function uploadProfilePicture() {
    if (!selectedFile.value) {
        return
    }

    uploading.value = true
    successMessage.value = ''
    errorMessage.value = ''

    try {
        await authStore.uploadProfilePicture(
            selectedFile.value
        )

        successMessage.value =
            'Profile picture updated successfully.'

        selectedFile.value = null

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value)
            previewUrl.value = null
        }

        // Reset input so same image can be selected again
        if (fileInput.value) {
            fileInput.value.value = ''
        }

        setTimeout(() => {
            successMessage.value = ''
        }, 3000)
    } catch (error) {
        errorMessage.value =
            error.data?.message ||
            error.message ||
            'Unable to upload profile picture.'
    } finally {
        uploading.value = false
    }
}


// DELETE PICTURE


async function removeProfilePicture() {
    if (!user.value?.profile_picture) {
        return
    }

    deleting.value = true
    successMessage.value = ''
    errorMessage.value = ''

    try {
        await authStore.deleteProfilePicture()

        successMessage.value =
            'Profile picture removed successfully.'

        setTimeout(() => {
            successMessage.value = ''
        }, 3000)
    } catch (error) {
        errorMessage.value =
            error.data?.message ||
            error.message ||
            'Unable to remove profile picture.'
    } finally {
        deleting.value = false
    }
}

// NAVIGATION

function goBack() {
    router.push({
        name: 'products.index',
    })
}

// INITIALIZE

onMounted(async () => {
    await loadProfile()
})
</script>

<template>
    <div class="min-h-screen bg-gray-50">

        <!-- Page Header -->
        <div class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-5xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8"
            >
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-gray-900"
                    >
                        My Profile
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage and view your account information.
                    </p>
                </div>

                <button
                    type="button"
                    @click="goBack"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    ← Back to Products
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <main
            class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8"
        >

            <!-- Success Message -->
            <div
                v-if="successMessage"
                class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
            >
                ✓ {{ successMessage }}
            </div>

            <!-- Error Message -->
            <div
                v-if="errorMessage"
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
            >
                {{ errorMessage }}
            </div>

            <!-- Profile Card -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >

                <!-- Profile Banner -->
                <div class="h-32 bg-gray-900"></div>

                <!-- Profile Information -->
                <div class="px-6 pb-6 sm:px-8">

                    <!-- Avatar + Actions -->
                    <div
                        class="-mt-12 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                    >

                        <div class="relative">

                            <!-- Profile Image -->
                            <div
                                class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-white bg-gray-900 text-3xl font-bold text-white shadow-md"
                            >

                                <!-- Preview -->
                                <img
                                    v-if="previewUrl"
                                    :src="previewUrl"
                                    alt="Profile preview"
                                    class="h-full w-full object-cover"
                                />

                                <!-- Existing Image -->
                                <img
                                    v-else-if="profilePicture"
                                    :src="profilePicture"
                                    alt="Profile picture"
                                    class="h-full w-full object-cover"
                                />

                                <!-- Initial -->
                                <span v-else>
                                    {{ userInitial }}
                                </span>

                            </div>

                            <!-- Camera Button -->
                            <button
                                type="button"
                                @click="openFilePicker"
                                :disabled="uploading"
                                class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-gray-900 text-white shadow transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                title="Change profile picture"
                            >
                               <font-awesome-icon :icon="cameraIcon" />
                            </button>

                            <!-- Hidden Input -->
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="handleFileChange"
                            />

                        </div>

                        <!-- Profile Buttons -->
                        <div class="flex gap-2">

                            <button
                                v-if="profilePicture"
                                type="button"
                                @click="removeProfilePicture"
                                :disabled="deleting"
                                class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                            >
                                {{
                                    deleting
                                        ? 'Removing...'
                                        : 'Remove Photo'
                                }}
                            </button>

                            <button
                                v-if="!editing"
                                type="button"
                                @click="startEditing"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                            >
                                 Edit Profile
                            </button>

                        </div>

                    </div>

                    <!-- Name / Email -->
                    <div class="mt-5">

                        <h2
                            class="text-2xl font-bold text-gray-900"
                        >
                            {{ user?.name || 'User' }}
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ user?.email || 'No email available' }}
                        </p>

                    </div>

                    <!-- Divider -->
                    <div
                        class="my-6 border-t border-gray-100"
                    ></div>

                    <!-- Edit Profile -->
                    <div v-if="editing">

                        <h3
                            class="text-lg font-semibold text-gray-900"
                        >
                            Edit Profile
                        </h3>

                        <div
                            class="mt-5 grid gap-5 sm:grid-cols-2"
                        >

                            <!-- Name -->
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Full Name
                                </label>

                                <input
                                    v-model="name"
                                    type="text"
                                    placeholder="Enter your name"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                />
                            </div>

                            <!-- Email -->
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700"
                                >
                                    Email Address
                                </label>

                                <input
                                    v-model="email"
                                    type="email"
                                    placeholder="Enter your email"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                />
                            </div>

                        </div>

                        <!-- Edit Buttons -->
                        <div
                            class="mt-6 flex justify-end gap-3"
                        >

                            <button
                                type="button"
                                @click="cancelEditing"
                                :disabled="saving"
                                class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                @click="saveProfile"
                                :disabled="saving"
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{
                                    saving
                                        ? 'Saving...'
                                        : 'Save Changes'
                                }}
                            </button>

                        </div>

                    </div>

                    <!-- Account Information -->
                    <div v-else>

                        <h3
                            class="text-lg font-semibold text-gray-900"
                        >
                            Account Information
                        </h3>

                        <div
                            class="mt-5 grid gap-5 sm:grid-cols-2"
                        >

                            <!-- Name -->
                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Full Name
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold text-gray-900"
                                >
                                    {{ user?.name || 'Not available' }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Email Address
                                </p>

                                <p
                                    class="mt-2 break-all text-sm font-semibold text-gray-900"
                                >
                                    {{ user?.email || 'Not available' }}
                                </p>
                            </div>

                            <!-- User ID -->
                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    User ID
                                </p>

                                <p
                                    class="mt-2 text-sm font-semibold text-gray-900"
                                >
                                    #{{ user?.id || 'N/A' }}
                                </p>
                            </div>

                            <!-- Account Status -->
                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-500"
                                >
                                    Account Status
                                </p>

                                <div
                                    class="mt-2 flex items-center gap-2"
                                >
                                    <span
                                        class="h-2.5 w-2.5 rounded-full bg-green-500"
                                    ></span>

                                    <span
                                        class="text-sm font-semibold text-green-700"
                                    >
                                        Active
                                    </span>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Account Section -->
                    <div class="mt-8">

                        <h3
                            class="text-lg font-semibold text-gray-900"
                        >
                            Account
                        </h3>

                        <div
                            class="mt-4 rounded-xl border border-gray-200"
                        >

                            <div
                                class="flex items-center justify-between p-4"
                            >

                                <div>
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Profile Information
                                    </p>

                                    <p
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        Your personal account details
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    @click="startEditing"
                                    class="text-sm font-medium text-gray-700 hover:text-gray-900"
                                >
                                    Edit →
                                </button>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </main>
    </div>
</template>