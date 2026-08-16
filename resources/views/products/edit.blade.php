@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')

<div class="mx-auto max-w-3xl">

    {{-- Header --}}
    <div class="mb-8">

       <a
    href="{{ route('products.index') }}"
    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
>
     Back to Products
</a>

        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            Edit Product
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            Update the information for {{ $product->name }}.
        </p>

    </div>


    {{-- Form --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">

        <form
            action="{{ route('products.update', $product) }}"
            method="POST"
        >

            @csrf
            @method('PUT')


            {{-- Product Name --}}
            <div class="mb-6">

                <label
                    for="name"
                    class="mb-2 block text-sm font-medium text-gray-900"
                >
                    Product Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $product->name) }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                >

                @error('name')
                    <p class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- Description --}}
            <div class="mb-6">

                <label
                    for="description"
                    class="mb-2 block text-sm font-medium text-gray-900"
                >
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                >{{ old('description', $product->description) }}</textarea>

                @error('description')
                    <p class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- Price + Quantity --}}
            <div class="grid gap-6 sm:grid-cols-2">

                {{-- Price --}}
                <div>

                    <label
                        for="price"
                        class="mb-2 block text-sm font-medium text-gray-900"
                    >
                        Price
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        value="{{ old('price', $product->price) }}"
                        step="0.01"
                        min="0"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                    >

                    @error('price')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Quantity --}}
                <div>

                    <label
                        for="quantity"
                        class="mb-2 block text-sm font-medium text-gray-900"
                    >
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="{{ old('quantity', $product->quantity) }}"
                        min="0"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-900/10"
                    >

                    @error('quantity')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>


            {{-- Actions --}}
            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a
                    href="{{ route('products.show', $product) }}"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
                >
                    Update Product
                </button>

            </div>

        </form>

    </div>

</div>

@endsection