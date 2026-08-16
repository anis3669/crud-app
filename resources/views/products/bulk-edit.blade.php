@extends('layouts.app')

@section('title', 'Edit Selected Products')

@section('content')

<div class="mx-auto max-w-4xl">

    {{-- Header --}}
    <div class="mb-8">

          <a
                  href="{{ route('products.index') }}"
                 class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
>
          Back to Products
        </a>

        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            Edit Selected Products
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Update each selected product individually.
        </p>

    </div>


    {{-- Edit Form --}}
    <form
        action="{{ route('products.bulkUpdate') }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            {{-- Table --}}
            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="border-b border-gray-200 bg-gray-50">

                        <tr>

                            <th class="px-6 py-4 font-semibold text-gray-900">
                                Product Name
                            </th>

                            <th class="px-6 py-4 font-semibold text-gray-900">
                                Price
                            </th>

                            <th class="px-6 py-4 font-semibold text-gray-900">
                                Quantity
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        @foreach($products as $index => $product)

                            <tr>

                                {{-- Product --}}
                                 <td class="px-6 py-5">

    <input
        type="hidden"
        name="products[{{ $index }}][id]"
        value="{{ $product->id }}"
    >

    <label
        for="name-{{ $product->id }}"
        class="sr-only"
    >
        Product Name
    </label>

    <input
        type="text"
        id="name-{{ $product->id }}"
        name="products[{{ $index }}][name]"
        value="{{ old("products.$index.name", $product->name) }}"
        maxlength="255"
        required
        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
    >

    @error("products.$index.name")

        <p class="mt-1 text-xs text-red-600">
            {{ $message }}
        </p>

    @enderror

</td>


                                {{-- Price --}}
                                <td class="px-6 py-5">

                                    <label
                                        for="price-{{ $product->id }}"
                                        class="sr-only"
                                    >
                                        Price
                                    </label>

                                    <div class="relative">

                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500"
                                        >
                                            Rs.
                                        </span>

                                        <input
                                            type="number"
                                            id="price-{{ $product->id }}"
                                            name="products[{{ $index }}][price]"
                                            value="{{ old("products.$index.price", $product->price) }}"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="w-full rounded-lg border border-gray-300 py-2.5 pl-11 pr-3 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                                        >

                                    </div>

                                    @error("products.$index.price")

                                        <p class="mt-1 text-xs text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </td>


                                {{-- Quantity --}}
                                <td class="px-6 py-5">

                                    <label
                                        for="quantity-{{ $product->id }}"
                                        class="sr-only"
                                    >
                                        Quantity
                                    </label>

                                    <input
                                        type="number"
                                        id="quantity-{{ $product->id }}"
                                        name="products[{{ $index }}][quantity]"
                                        value="{{ old("products.$index.quantity", $product->quantity) }}"
                                        min="0"
                                        required
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                                    >

                                    @error("products.$index.quantity")

                                        <p class="mt-1 text-xs text-red-600">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Footer --}}
            <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">

                <p class="text-sm text-gray-500">
                    {{ $products->count() }} product(s) selected
                </p>


                <div class="flex items-center gap-3">

                    <a
                        href="{{ route('products.index') }}"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                    >
                        Update Products
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>

@endsection