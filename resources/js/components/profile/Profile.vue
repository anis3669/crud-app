<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
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
const loading = ref(true)

const name = ref('')
const email = ref('')

const selectedFile = ref(null)
const previewUrl = ref(null)

const successMessage = ref('')
const errorMessage = ref('')

const fileInput = ref(null)

let messageTimer = null


// PROFILE PICTURE

const profilePicture = computed(() => {
    if (user.value?.profile_picture_url) {
        return user.value.profile_picture_url
    }

    if (user.value?.profile_picture) {
        if (
            user.value.profile_picture.startsWith('http://') ||
            user.value.profile_picture.startsWith('https://') ||
            user.value.profile_picture.startsWith('/storage/')
        ) {
            return user.value.profile_picture
        }

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


// MESSAGE HELPERS

function clearMessageTimer() {
    if (messageTimer) {
        clearTimeout(messageTimer)
        messageTimer = null
    }
}

function showSuccess(message) {
    clearMessageTimer()

    successMessage.value = message
    errorMessage.value = ''

    messageTimer = setTimeout(() => {
        successMessage.value = ''
    }, 3000)
}

function showError(message) {
    clearMessageTimer()

    errorMessage.value = message
    successMessage.value = ''
}


// LOAD PROFILE

async function loadProfile() {
    loading.value = true
    errorMessage.value = ''

    try {
        await authStore.loadProfile()

        name.value = user.value?.name || ''
        email.value = user.value?.email || ''
    } catch (error) {
        showError(
            error.response?.data?.message ||
            error.data?.message ||
            error.message ||
            'Unable to load profile.'
        )
    } finally {
        loading.value = false
    }
}


// EDIT PROFILE

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
    const trimmedName = name.value.trim()
    const trimmedEmail = email.value.trim()

    if (!trimmedName) {
        showError('Name is required.')
        return
    }

    if (!trimmedEmail) {
        showError('Email is required.')
        return
    }

    saving.value = true

    successMessage.value = ''
    errorMessage.value = ''

    try {
        await authStore.updateProfile({
            name: trimmedName,
            email: trimmedEmail,
        })

        editing.value = false

        showSuccess('Profile updated successfully.')
    } catch (error) {
        showError(
            error.response?.data?.message ||
            error.data?.message ||
            error.message ||
            'Unable to update profile.'
        )
    } finally {
        saving.value = false
    }
}


// FILE INPUT

function openFilePicker() {
    if (uploading.value) {
        return
    }

    fileInput.value?.click()
}

function handleFileChange(event) {
    const file = event.target.files?.[0]

    if (!file) {
        return
    }

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ]

    if (!allowedTypes.includes(file.type)) {
        showError(
            'Please select a JPG, PNG, or WEBP image.'
        )

        event.target.value = ''
        return
    }

    if (file.size > 2 * 1024 * 1024) {
        showError(
            'Profile picture must be smaller than 2MB.'
        )

        event.target.value = ''
        return
    }

    selectedFile.value = file

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
    }

    previewUrl.value = URL.createObjectURL(file)

    uploadProfilePicture()
}


// UPLOAD PROFILE PICTURE

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

        showSuccess(
            'Profile picture updated successfully.'
        )

        selectedFile.value = null

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value)
            previewUrl.value = null
        }

        if (fileInput.value) {
            fileInput.value.value = ''
        }
    } catch (error) {
        showError(
            error.response?.data?.message ||
            error.data?.message ||
            error.message ||
            'Unable to upload profile picture.'
        )
    } finally {
        uploading.value = false
    }
}


// DELETE PROFILE PICTURE

async function removeProfilePicture() {
    if (!user.value?.profile_picture) {
        return
    }

    deleting.value = true

    successMessage.value = ''
    errorMessage.value = ''

    try {
        await authStore.deleteProfilePicture()

        showSuccess(
            'Profile picture removed successfully.'
        )
    } catch (error) {
        showError(
            error.response?.data?.message ||
            error.data?.message ||
            error.message ||
            'Unable to remove profile picture.'
        )
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


// CLEANUP

onMounted(async () => {
    await loadProfile()
})

onBeforeUnmount(() => {
    clearMessageTimer()

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
    }
})
</script>

<template>
    <div class="min-h-full bg-gray-50">

        <!-- Page Header -->

        <div class="border-b border-gray-200 bg-white">
            <div
                class="mx-auto flex max-w-6xl flex-col gap-4 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-900 text-sm font-bold text-white"
                        >
                            P
                        </div>

                        <span
                            class="text-sm font-semibold text-gray-500"
                        >
                            Account
                        </span>
                    </div>

                    <h1
                        class="mt-3 text-2xl font-bold tracking-tight text-gray-900"
                    >
                        My Profile
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Manage your personal information and account settings.
                    </p>
                </div>

                <button
                    type="button"
                    @click="goBack"
                    class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
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
                            stroke-width="1.8"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>

                    Back to Products
                </button>
            </div>
        </div>


        <!-- Main -->

        <main
            class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8"
        >

            <!-- Loading -->

            <div
                v-if="loading"
                class="rounded-2xl border border-gray-200 bg-white p-12 shadow-sm"
            >
                <div class="flex flex-col items-center justify-center">
                    <div
                        class="h-8 w-8 animate-spin rounded-full border-2 border-gray-200 border-t-gray-900"
                    ></div>

                    <p
                        class="mt-4 text-sm font-medium text-gray-500"
                    >
                        Loading profile...
                    </p>
                </div>
            </div>


            <!-- Content -->

            <template v-else>

                <!-- Messages -->

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-1 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-1 opacity-0"
                >
                    <div
                        v-if="successMessage || errorMessage"
                        class="mb-5"
                    >
                        <div
                            v-if="successMessage"
                            class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                        >
                            <div
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white"
                            >
                                <svg
                                    class="h-3 w-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>

                            <p
                                class="text-sm font-medium text-emerald-700"
                            >
                                {{ successMessage }}
                            </p>
                        </div>

                        <div
                            v-else-if="errorMessage"
                            class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3"
                        >
                            <div
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"
                            >
                                !
                            </div>

                            <p
                                class="text-sm font-medium text-red-700"
                            >
                                {{ errorMessage }}
                            </p>
                        </div>
                    </div>
                </Transition>


                <!-- Profile Card -->

                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >

                    <!-- Banner -->

                    <div
                        class="relative h-28 overflow-hidden bg-gray-900 sm:h-36"
                    >
                        <div
                            class="absolute -right-16 -top-24 h-64 w-64 rounded-full border border-white/10"
                        ></div>

                        <div
                            class="absolute -bottom-28 -left-16 h-64 w-64 rounded-full border border-white/10"
                        ></div>

                        <div
                            class="absolute inset-0 bg-gradient-to-r from-gray-950/40 via-transparent to-gray-700/20"
                        ></div>
                    </div>


                    <!-- Profile Header -->

                    <div class="px-5 pb-6 sm:px-8">

                        <div
                            class="-mt-10 flex flex-col gap-5 sm:-mt-12 sm:flex-row sm:items-end sm:justify-between"
                        >

                            <!-- Avatar -->

                            <div class="relative w-fit">

                                <div
                                    class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-gray-900 text-3xl font-bold text-white shadow-lg sm:h-28 sm:w-28 sm:text-4xl"
                                >

                                    <img
                                        v-if="previewUrl"
                                        :src="previewUrl"
                                        alt="Profile preview"
                                        class="h-full w-full object-cover"
                                    />

                                    <img
                                        v-else-if="profilePicture"
                                        :src="profilePicture"
                                        alt="Profile picture"
                                        class="h-full w-full object-cover"
                                    />

                                    <span v-else>
                                        {{ userInitial }}
                                    </span>

                                </div>


                                <!-- Camera -->

                                <button
                                    type="button"
                                    @click="openFilePicker"
                                    :disabled="uploading"
                                    class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-xl border-2 border-white bg-gray-900 text-white shadow-md transition hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Change profile picture"
                                >
                                    <font-awesome-icon
                                        :icon="cameraIcon"
                                        class="text-sm"
                                    />
                                </button>

                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    class="hidden"
                                    @change="handleFileChange"
                                />

                            </div>


                            <!-- Actions -->

                            <div
                                class="flex flex-wrap items-center gap-2"
                            >

                                <button
                                    v-if="profilePicture"
                                    type="button"
                                    @click="removeProfilePicture"
                                    :disabled="deleting || uploading"
                                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
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
                                            stroke-width="1.8"
                                            d="M6 7h12m-9 4v5m6-5v5M9 7V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m-9 0l.75 12.25A1.75 1.75 0 008.5 21h7a1.75 1.75 0 001.75-1.75L18 7M4.5 7h15"
                                        />
                                    </svg>

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
                                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-700"
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
                                            stroke-width="1.8"
                                            d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3.75 19.5l1.037-4.5L16.862 3.487z"
                                        />
                                    </svg>

                                    Edit Profile
                                </button>

                            </div>

                        </div>


                        <!-- Identity -->

                        <div class="mt-5">

                            <div
                                class="flex flex-wrap items-center gap-2"
                            >
                                <h2
                                    class="text-2xl font-bold tracking-tight text-gray-900"
                                >
                                    {{ user?.name || 'User' }}
                                </h2>

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    ></span>

                                    Active
                                </span>
                            </div>

                            <p
                                class="mt-1 text-sm text-gray-500"
                            >
                                {{ user?.email || 'No email available' }}
                            </p>

                        </div>

                    </div>

                </div>


                <!-- Lower Content -->

                <div
                    class="mt-5 grid gap-5 lg:grid-cols-3"
                >

                    <!-- Account Information -->

                    <div
                        class="rounded-2xl border border-gray-200 bg-white shadow-sm lg:col-span-2"
                    >

                        <div
                            class="border-b border-gray-100 px-5 py-5 sm:px-6"
                        >
                            <h3
                                class="text-base font-semibold text-gray-900"
                            >
                                Account Information
                            </h3>

                            <p
                                class="mt-1 text-sm text-gray-500"
                            >
                                Your basic account details.
                            </p>
                        </div>


                        <!-- Edit -->

                        <div
                            v-if="editing"
                            class="px-5 py-6 sm:px-6"
                        >

                            <div
                                class="grid gap-5 sm:grid-cols-2"
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
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
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
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
                                    />
                                </div>

                            </div>


                            <!-- Edit Actions -->

                            <div
                                class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                            >

                                <button
                                    type="button"
                                    @click="cancelEditing"
                                    :disabled="saving"
                                    class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
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


                        <!-- Information -->

                        <div
                            v-else
                            class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6"
                        >

                            <!-- Name -->

                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm"
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
                                                stroke-width="1.8"
                                                d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                    >
                                        Full Name
                                    </p>
                                </div>

                                <p
                                    class="mt-4 text-sm font-semibold text-gray-900"
                                >
                                    {{ user?.name || 'Not available' }}
                                </p>
                            </div>


                            <!-- Email -->

                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm"
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
                                                stroke-width="1.8"
                                                d="M3 7.5l9 6 9-6M4.5 19.5h15A1.5 1.5 0 0021 18V6a1.5 1.5 0 00-1.5-1.5h-15A1.5 1.5 0 003 6v12a1.5 1.5 0 001.5 1.5z"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                    >
                                        Email Address
                                    </p>
                                </div>

                                <p
                                    class="mt-4 break-all text-sm font-semibold text-gray-900"
                                >
                                    {{ user?.email || 'Not available' }}
                                </p>
                            </div>


                            <!-- User ID -->

                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm"
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
                                                stroke-width="1.8"
                                                d="M15 7.5V6a3 3 0 00-6 0v1.5M6 7.5h12l.75 12H5.25L6 7.5z"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                    >
                                        User ID
                                    </p>
                                </div>

                                <p
                                    class="mt-4 text-sm font-semibold text-gray-900"
                                >
                                    #{{ user?.id || 'N/A' }}
                                </p>
                            </div>


                            <!-- Status -->

                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50/70 p-4"
                            >
                                <div
                                    class="flex items-center gap-2"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-emerald-600 shadow-sm"
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
                                                stroke-width="1.8"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>

                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                    >
                                        Account Status
                                    </p>
                                </div>

                                <div
                                    class="mt-4 flex items-center gap-2"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-emerald-500"
                                    ></span>

                                    <span
                                        class="text-sm font-semibold text-emerald-700"
                                    >
                                        Active
                                    </span>
                                </div>
                            </div>

                        </div>

                    </div>


                    <!-- Profile Picture Card -->

                    <div
                        class="rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >

                        <div
                            class="border-b border-gray-100 px-5 py-5"
                        >
                            <h3
                                class="text-base font-semibold text-gray-900"
                            >
                                Profile Picture
                            </h3>

                            <p
                                class="mt-1 text-sm text-gray-500"
                            >
                                Keep your profile recognizable.
                            </p>
                        </div>

                        <div class="p-5">

                            <div
                                class="flex items-center gap-4"
                            >

                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-900 text-xl font-bold text-white"
                                >
                                    <img
                                        v-if="previewUrl"
                                        :src="previewUrl"
                                        alt="Profile preview"
                                        class="h-full w-full object-cover"
                                    />

                                    <img
                                        v-else-if="profilePicture"
                                        :src="profilePicture"
                                        alt="Profile picture"
                                        class="h-full w-full object-cover"
                                    />

                                    <span v-else>
                                        {{ userInitial }}
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{ user?.name || 'User' }}
                                    </p>

                                    <p
                                        class="mt-1 text-xs leading-5 text-gray-500"
                                    >
                                        JPG, PNG or WEBP
                                        <br />
                                        Maximum 2MB
                                    </p>
                                </div>

                            </div>


                            <button
                                type="button"
                                @click="openFilePicker"
                                :disabled="uploading"
                                class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <font-awesome-icon
                                    :icon="cameraIcon"
                                    class="text-xs"
                                />

                                {{
                                    uploading
                                        ? 'Uploading...'
                                        : 'Change Photo'
                                }}
                            </button>

                        </div>

                    </div>

                </div>


                <!-- Account Settings -->

                <div
                    class="mt-5 rounded-2xl border border-gray-200 bg-white shadow-sm"
                >

                    <div
                        class="border-b border-gray-100 px-5 py-5 sm:px-6"
                    >
                        <h3
                            class="text-base font-semibold text-gray-900"
                        >
                            Account Settings
                        </h3>

                        <p
                            class="mt-1 text-sm text-gray-500"
                        >
                            Manage your account information.
                        </p>
                    </div>

                    <div class="divide-y divide-gray-100">

                        <div
                            class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                        >
                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-900"
                                >
                                    Profile Information
                                </p>

                                <p
                                    class="mt-1 text-xs text-gray-500"
                                >
                                    Update your name and email address.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="startEditing"
                                class="inline-flex w-fit items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 hover:text-gray-900"
                            >
                                Edit

                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M9 18l6-6-6-6"
                                    />
                                </svg>
                            </button>

                        </div>

                    </div>

                </div>

            </template>

        </main>

    </div>
</template>
