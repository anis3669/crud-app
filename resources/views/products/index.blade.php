@extends('layouts.app')

@section('title', 'Products')

@section('content')

<div class="mx-auto max-w-7xl">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">

        <div>
            <div class="flex items-center gap-3">

                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Products
                </h1>

                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                    {{ $products->count() }}
                </span>

            </div>

            <p class="mt-1 text-sm text-gray-500">
                Manage your product inventory.
            </p>
        </div>


        {{-- Add Product --}}
        <a
            href="{{ route('products.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
        >
            <span class="mr-2 text-base">+</span>
            Add Product
        </a>

    </div>


    {{-- Success Message --}}
    @if(session('success'))

        <div
            id="success-message"
            class="fixed right-6 top-6 z-50 rounded-lg border border-green-200 bg-green-50 px-5 py-3 text-sm font-medium text-green-800 shadow-lg"
        >
            {{ session('success') }}
        </div>

        <script>
            setTimeout(() => {

                const message =
                    document.getElementById('success-message');

                if (message) {
                    message.remove();
                }

            }, 3000);
        </script>

    @endif


    {{-- Error Message --}}
    @if(session('error'))

        <div
            id="error-message"
            class="fixed right-6 top-6 z-50 rounded-lg border border-red-200 bg-red-50 px-5 py-3 text-sm font-medium text-red-800 shadow-lg"
        >
            {{ session('error') }}
        </div>

        <script>
            setTimeout(() => {

                const message =
                    document.getElementById('error-message');

                if (message) {
                    message.remove();
                }

            }, 3000);
        </script>

    @endif


    {{-- Bulk Actions --}}
    <div
        id="bulk-actions"
        class="mb-4 hidden items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm"
    >

        {{-- Selected Count --}}
        <div>

            <span
                id="selected-count"
                class="text-sm font-medium text-gray-700"
            >
                0 selected
            </span>

        </div>


        {{-- Bulk Buttons --}}
        <div class="flex items-center gap-2">

            {{-- Edit Selected --}}
            <button
                type="button"
                id="bulk-edit-button"
                class="rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
            >
                Edit Selected
            </button>


            {{-- Delete Selected --}}
            <button
                type="button"
                id="bulk-delete-button"
                class="rounded-lg bg-red-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
            >
                Delete Selected
            </button>

        </div>

    </div>


    {{-- Product Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                {{-- Table Header --}}
                <thead class="border-b border-gray-200 bg-gray-50/80">

                    <tr>

                        {{-- Select All --}}
                        <th class="w-12 px-6 py-4">

                            <input
                                type="checkbox"
                                id="select-all"
                                class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                            >

                        </th>


                        {{-- Product --}}
                        <th class="px-6 py-4 font-semibold text-gray-900">
                            Product
                        </th>


                        {{-- Description --}}
                        <th class="px-6 py-4 font-semibold text-gray-900">
                            Description
                        </th>


                        {{-- Price --}}
                        <th class="px-6 py-4 font-semibold text-gray-900">
                            Price
                        </th>


                        {{-- Stock --}}
                        <th class="px-6 py-4 font-semibold text-gray-900">
                            Stock
                        </th>


                        {{-- Actions --}}
                        <th class="px-6 py-4 text-right font-semibold text-gray-900">
                            Actions
                        </th>

                    </tr>

                </thead>


                {{-- Table Body --}}
                <tbody class="divide-y divide-gray-100">

                    @forelse($products as $product)

                        <tr class="transition hover:bg-gray-50">

                            {{-- Product Checkbox --}}
                            <td class="px-6 py-4">

                                <input
                                    type="checkbox"
                                    value="{{ $product->id }}"
                                    class="product-checkbox h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                >

                            </td>


                            {{-- Product --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                <div class="font-medium text-gray-900">
                                    {{ $product->name }}
                                </div>

                                <div class="mt-1 text-xs text-gray-400">
                                    Product #{{ $loop->iteration }}
                                </div>

                            </td>


                            {{-- Description --}}
                            <td class="max-w-xs px-6 py-4">

                                <p class="truncate text-gray-600">
                                    {{ $product->description ?: 'No description' }}
                                </p>

                            </td>


                            {{-- Price --}}
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">

                                Rs. {{ number_format($product->price, 2) }}

                            </td>


                            {{-- Stock --}}
                            <td class="whitespace-nowrap px-6 py-4">

                                @if($product->quantity == 0)

                                    <span
                                        class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700"
                                    >
                                        Out of stock
                                    </span>

                                @elseif($product->quantity <= 5)

                                    <span
                                        class="inline-flex rounded-full bg-yellow-50 px-2.5 py-1 text-xs font-medium text-yellow-700"
                                    >
                                        {{ $product->quantity }} left
                                    </span>

                                @else

                                    <span
                                        class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700"
                                    >
                                        {{ $product->quantity }} in stock
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="whitespace-nowrap px-6 py-4 text-right">

                                {{-- View --}}
                                <a
                                    href="{{ route('products.show', $product) }}"
                                    class="font-medium text-gray-700 transition hover:text-gray-900"
                                >
                                    View
                                </a>


                                {{-- Edit --}}
                                <a
                                    href="{{ route('products.edit', $product) }}"
                                    class="ml-4 font-medium text-blue-600 transition hover:text-blue-800"
                                >
                                    Edit
                                </a>


                                {{-- Delete --}}
                                <form
                                    action="{{ route('products.destroy', $product) }}"
                                    method="POST"
                                    class="ml-4 inline"
                                    onsubmit="return confirm('Are you sure you want to delete {{ $product->name }}?');"
                                >

                                    @csrf

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="font-medium text-red-600 transition hover:text-red-800"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>


                    @empty

                        {{-- Empty State --}}
                        <tr>

                            <td
                                colspan="6"
                                class="px-6 py-12 text-center"
                            >

                                <div class="text-gray-500">

                                    <p class="text-base font-medium text-gray-900">
                                        No products found
                                    </p>

                                    <p class="mt-1 text-sm">
                                        Get started by adding your first product.
                                    </p>

                                    <a
                                        href="{{ route('products.create') }}"
                                        class="mt-4 inline-block text-sm font-medium text-gray-900 underline"
                                    >
                                        Add your first product
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>



<!-- bulk delete form -->
<form
    id="bulk-delete-form"
    action="{{ route('products.bulkDelete') }}"
    method="POST"
    class="hidden"
>

    @csrf

    @method('DELETE')

</form>
<!-- javascript -->
<script>
// elements
    const selectAll =
        document.getElementById('select-all');

    const productCheckboxes =
        document.querySelectorAll('.product-checkbox');

    const bulkActions =
        document.getElementById('bulk-actions');

    const selectedCount =
        document.getElementById('selected-count');

// update bulk actions based on selected checkboxes
    function updateBulkActions() {

        const checkedBoxes =
            document.querySelectorAll(
                '.product-checkbox:checked'
            );

        const count =
            checkedBoxes.length;
// update selected count text
        selectedCount.textContent =
            `${count} selected`;
// show or hide bulk actions based on selection

        if (count > 0) {

            bulkActions.classList.remove('hidden');

            bulkActions.classList.add('flex');

        } else {

            bulkActions.classList.add('hidden');

            bulkActions.classList.remove('flex');

        }
// update select all checkbox state

        if (selectAll) {

            selectAll.checked =
                count === productCheckboxes.length &&
                productCheckboxes.length > 0;

        }

    }
// select all checkbox

    selectAll?.addEventListener(
        'change',
        function () {

            productCheckboxes.forEach(
                checkbox => {

                    checkbox.checked =
                        this.checked;

                }
            );

            updateBulkActions();

        }
    );
// individual product checkboxes

    productCheckboxes.forEach(
        checkbox => {

            checkbox.addEventListener(
                'change',
                function () {

                    updateBulkActions();

                }
            );

        }
    );
// edit selected products
    document
        .getElementById('bulk-edit-button')
        ?.addEventListener(
            'click',
            function () {

                const checkedBoxes =
                    document.querySelectorAll(
                        '.product-checkbox:checked'
                    );
// no products selected
                if (checkedBoxes.length === 0) {

                    return;

                }
// create a form to submit selected product IDs to the bulk edit route
                const form =
                    document.createElement('form');

                form.method = 'GET';

                form.action =
                    "{{ route('products.bulkEdit') }}";
// add selected product IDs to the form
                checkedBoxes.forEach(
                    checkbox => {

                        const input =
                            document.createElement('input');

                        input.type =
                            'hidden';

                        input.name =
                            'selected_products[]';

                        input.value =
                            checkbox.value;

                        form.appendChild(input);

                    }
                );
    // submit the form
                document.body.appendChild(form);

                form.submit();

            }
        );
// delete selected products

    document
        .getElementById('bulk-delete-button')
        ?.addEventListener(
            'click',
            function () {

                const checkedBoxes =
                    document.querySelectorAll(
                        '.product-checkbox:checked'
                    );
// No products selected

                if (checkedBoxes.length === 0) {

                    return;

                }
// confirm deletion

                const confirmed =
                    confirm(
                        `Are you sure you want to delete ${checkedBoxes.length} selected product(s)?`
                    );


                if (!confirmed) {

                    return;

                }
// bulk delete form
                const form =
                    document.getElementById(
                        'bulk-delete-form'
                    );
// Add selected product IDs to the form

                checkedBoxes.forEach(
                    checkbox => {

                        const input =
                            document.createElement('input');

                        input.type =
                            'hidden';

                        input.name =
                            'selected_products[]';

                        input.value =
                            checkbox.value;

                        form.appendChild(input);

                    }
                );
// submit the form

                form.submit();

            }
        );

</script>

@endsection