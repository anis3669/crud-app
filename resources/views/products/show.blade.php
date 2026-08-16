@extends('layouts.app')

@section('title', $product->name)

@section('content')

<div class="mx-auto max-w-4xl">

    {{-- Back --}}
    <div class="mb-6">
        <a
    href="{{ route('products.index') }}"
    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
>
     Back to Products
</a>
    </div>


    {{-- Product Card --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Header --}}
        <div class="border-b border-gray-200 px-6 py-6 sm:px-8">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Product #{{ $product->id }}
                    </p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                        {{ $product->name }}
                    </h1>

                </div>


                {{-- Stock Status --}}
                @if($product->quantity > 0)

                    <span class="inline-flex w-fit rounded-full bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700">
                        {{ $product->quantity }} in stock
                    </span>

                @else

                    <span class="inline-flex w-fit rounded-full bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700">
                        Out of stock
                    </span>

                @endif

            </div>

        </div>


        {{-- Details --}}
        <div class="px-6 py-6 sm:px-8">

            <div class="grid gap-8 sm:grid-cols-2">

                {{-- Description --}}
                <div class="sm:col-span-2">

                    <h2 class="text-sm font-semibold text-gray-900">
                        Description
                    </h2>

                    <p class="mt-2 leading-7 text-gray-600">
                        {{ $product->description ?: 'No description provided.' }}
                    </p>

                </div>


                {{-- Price --}}
                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Price
                    </p>

                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        Rs. {{ number_format($product->price, 2) }}
                    </p>

                </div>


                {{-- Quantity --}}
                <div>

                    <p class="text-sm font-medium text-gray-500">
                        Available Quantity
                    </p>

                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        {{ $product->quantity }}
                    </p>

                </div>

            </div>

        </div>


        {{-- Footer Actions --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:px-8">

            <a
                href="{{ route('products.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Back
            </a>

            <a
                href="{{ route('products.edit', $product) }}"
                class="rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
            >
                Edit Product
            </a>

        </div>

    </div>

</div>

@endsection