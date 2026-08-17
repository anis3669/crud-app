@extends('layouts.app')

@section('title', $product->name)

@section('content')

<div class="mx-auto max-w-4xl">

    {{-- Page Header --}}
    <div class="mb-6">

        <a
            href="{{ route('products.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-gray-900">
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

    </div>


    {{-- Product Card --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Product Header --}}
        <div class="border-b border-gray-100 px-6 py-6 sm:px-8">

            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        Product #{{ $product->id }}
                    </p>

                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                        {{ $product->name }}
                    </h1>

                </div>


                {{-- Stock Status --}}
                @if($product->quantity == 0)

                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700">

                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>

                    Out of stock

                </span>

                @elseif($product->quantity <= 5)

                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-yellow-50 px-3 py-1.5 text-sm font-semibold text-yellow-700">

                    <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span>

                    {{ $product->quantity }} left

                    </span>

                    @else

                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-sm font-semibold text-green-700">

                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>

                        {{ $product->quantity }} in stock

                    </span>

                    @endif

            </div>

        </div>


        {{-- Product Details --}}
        <div class="px-6 py-7 sm:px-8">

            <div class="grid gap-8 sm:grid-cols-2">

                {{-- Description --}}
                <div class="sm:col-span-2">

                    <h2 class="text-sm font-semibold text-gray-900">
                        Description
                    </h2>

                    <div class="mt-2 rounded-lg bg-gray-50 px-4 py-4">

                        <p class="leading-7 text-gray-600">
                            {{ $product->description ?: 'No description provided.' }}
                        </p>

                    </div>

                </div>


                {{-- Price --}}
                <div class="rounded-lg border border-gray-100 p-5">

                    <p class="text-sm font-medium text-gray-500">
                        Price
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        Rs. {{ number_format($product->price, 2) }}
                    </p>

                </div>


                {{-- Quantity --}}
                <div class="rounded-lg border border-gray-100 p-5">

                    <p class="text-sm font-medium text-gray-500">
                        Available Quantity
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ $product->quantity }}
                    </p>

                </div>

            </div>

        </div>


        {{-- Footer Actions --}}
        <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-8">

            <a
                href="{{ route('products.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                Back
            </a>

            <a
                href="{{ route('products.edit', $product) }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">

                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-7-7l7-7m0 0v5m0-5h-5" />
                </svg>

                Edit Product

            </a>

        </div>

    </div>

</div>

@endsection