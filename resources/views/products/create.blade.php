@extends('layouts.app')

@section('title', 'Add Product')

@section('content')

<div class="mx-auto max-w-3xl">

    {{-- Page Header --}}
    <div class="mb-8">

        <a
            href="{{ route('products.index') }}"
            class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-gray-900">
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7" />
            </svg>

            Back to Products
        </a>


        <h1 class="text-2xl font-bold tracking-tight text-gray-900">
            Add Product
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Add a new product to your inventory.
        </p>

    </div>


    {{-- Form Card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Card Header --}}
        <div class="border-b border-gray-100 px-6 py-5 sm:px-8">

            <h2 class="text-base font-semibold text-gray-900">
                Product Information
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Enter the details of the product below.
            </p>

        </div>


        <form
            action="{{ route('products.store') }}"
            method="POST"
            class="p-6 sm:p-8">

            @csrf


            {{-- Product Name --}}
            <div class="mb-6">

                <label
                    for="name"
                    class="mb-2 block text-sm font-semibold text-gray-900">
                    Product Name
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="e.g. HP Victus 15"
                    class="w-full rounded-lg border {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} px-4 py-2.5 text-sm outline-none transition focus:ring-2">

                @error('name')
                <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.46 0z" />
                    </svg>

                    {{ $message }}
                </p>
                @enderror

            </div>


            {{-- Description --}}
            <div class="mb-6">

                <label
                    for="description"
                    class="mb-2 block text-sm font-semibold text-gray-900">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    placeholder="Describe the product..."
                    class="w-full resize-none rounded-lg border {{ $errors->has('description') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} px-4 py-2.5 text-sm outline-none transition focus:ring-2">{{ old('description') }}</textarea>

                @error('description')
                <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                    <svg
                        class="h-4 w-4 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.46 0z" />
                    </svg>

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
                        class="mb-2 block text-sm font-semibold text-gray-900">
                        Price
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">

                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">
                            Rs.
                        </span>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            value="{{ old('price') }}"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="w-full rounded-lg border {{ $errors->has('price') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} py-2.5 pl-11 pr-4 text-sm outline-none transition focus:ring-2">

                    </div>

                    @error('price')
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                        <svg
                            class="h-4 w-4 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.46 0z" />
                        </svg>

                        {{ $message }}
                    </p>
                    @enderror

                </div>


                {{-- Quantity --}}
                <div>

                    <label
                        for="quantity"
                        class="mb-2 block text-sm font-semibold text-gray-900">
                        Quantity
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        value="{{ old('quantity') }}"
                        min="0"
                        placeholder="0"
                        class="w-full rounded-lg border {{ $errors->has('quantity') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-300 focus:border-gray-900 focus:ring-gray-900/10' }} px-4 py-2.5 text-sm outline-none transition focus:ring-2">

                    @error('quantity')
                    <p class="mt-2 flex items-center gap-1.5 text-sm text-red-600">
                        <svg
                            class="h-4 w-4 shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.46 0z" />
                        </svg>

                        {{ $message }}
                    </p>
                    @enderror

                </div>

            </div>


            {{-- Form Actions --}}
            <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:items-center sm:justify-end">

                <a
                    href="{{ route('products.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">

                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4" />
                    </svg>

                    Create Product

                </button>

            </div>

        </form>

    </div>

</div>

@endsection