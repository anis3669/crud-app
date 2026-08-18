<script setup>
import { ref } from "vue";

const emit = defineEmits(["cancel", "product-created"]);

const form = ref({
    name: "",
    description: "",
    price: "",
    quantity: "",
});

function submitForm() {
    if (!form.value.name.trim()) {
        alert("Please enter a product name.");
        return;
    }

    if (form.value.price === "") {
        alert("Please enter a price.");
        return;
    }

    if (form.value.quantity === "") {
        alert("Please enter the quantity.");
        return;
    }

    const product = {
        name: form.value.name.trim(),
        description: form.value.description.trim(),
        price: Number(form.value.price),
        quantity: Number(form.value.quantity),
    };

    console.log("Submitting product:", product);

    emit("product-created", product);
}
</script>

<template>
    <div class="mx-auto max-w-3xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Add Product
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Add a new product to your inventory.
            </p>
        </div>

        <!-- Form Card -->
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Product Name -->
                <div>
                    <label
                        for="name"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Product Name
                    </label>

                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="Enter product name"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                    />
                </div>

                <!-- Description -->
                <div>
                    <label
                        for="description"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Description
                    </label>

                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Enter product description"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                    ></textarea>
                </div>

                <!-- Price -->
                <div>
                    <label
                        for="price"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Price
                    </label>

                    <input
                        v-model="form.price"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                    />
                </div>

                <!-- Quantity -->
                <div>
                    <label
                        for="quantity"
                        class="block text-sm font-semibold text-gray-700"
                    >
                        Quantity
                    </label>

                    <input
                        id="quantity"
                        v-model="form.quantity"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="mt-2 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900"
                    />
                </div>

                <!-- Buttons -->
                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6"
                >
                    <button
                        type="button"
                        @click="emit('cancel')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800"
                    >
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
