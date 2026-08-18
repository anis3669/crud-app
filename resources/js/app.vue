```vue
<script setup>
import { ref } from 'vue';

import ProductList from './components/products/ProductList.vue';
import ProductCreate from './components/products/ProductCreate.vue';
import ProductEdit from './components/products/ProductEdit.vue';

const products = ref([
    {
        id: 1,
        name: 'Laptop',
        description: 'High performance laptop',
        price: 85000,
        quantity: 10,
    },
    {
        id: 2,
        name: 'Keyboard',
        description: 'Mechanical keyboard',
        price: 4500,
        quantity: 5,
    },
    {
        id: 3,
        name: 'Mouse',
        description: 'Wireless mouse',
        price: 2500,
        quantity: 0,
    },
]);

const currentPage = ref('list');

const selectedProduct = ref(null);

function showCreate() {
    currentPage.value = 'create';
}

function showList() {
    currentPage.value = 'list';
    selectedProduct.value = null;
}

function showEdit(product) {
    selectedProduct.value = product;
    currentPage.value = 'edit';
}

function addProduct(product) {
    products.value.push({
        id: Date.now(),
        ...product,
    });

    showList();
}

function updateProduct(updatedProduct) {
    const index = products.value.findIndex(
        product => product.id === updatedProduct.id
    );

    if (index !== -1) {
        products.value[index] = updatedProduct;
    }

    showList();
}
function deleteProduct(product) {
    products.value = products.value.filter(
        item => item.id !== product.id
    );
}
</script>

<template>

    <!-- Product List -->
    <ProductList
        v-if="currentPage === 'list'"
        :products="products"
        @add-product="showCreate"
        @edit-product="showEdit"
        @delete-product="deleteProduct"
    />

    <!-- Product Create -->
    <ProductCreate
        v-else-if="currentPage === 'create'"
        @cancel="showList"
        @product-created="addProduct"
    />

    <!-- Product Edit -->
    <ProductEdit
        v-else-if="currentPage === 'edit' && selectedProduct"
        :product="selectedProduct"
        @cancel="showList"
        @product-updated="updateProduct"
    />

</template>
```
