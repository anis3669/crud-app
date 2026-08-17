@extends('layouts.app')

@section('title', 'Register')

@section('content')

<div class="mx-auto max-w-md">
    
<div class="mb-8 text-center">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">
        Create Your Account
    </h1>

    <p class="mt-2 text-sm text-gray-500">
        Register to start managing your products.
    </p>
</div>


<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

    <form action="{{ route('register') }}" method="POST" class="space-y-5">

        @csrf


        {{-- Name --}}
        <div>

            <label
                for="name"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                placeholder="Enter your name"
                class="block w-full rounded-lg border
                @error('name')
                    border-red-500 focus:border-red-500 focus:ring-red-500
                @else
                    border-gray-300 focus:border-gray-900 focus:ring-gray-900
                @enderror
                px-3 py-2.5 text-sm text-gray-900 outline-none transition"
            >

            @error('name')
                <p class="mt-1.5 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Email --}}
        <div>

            <label
                for="email"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Email Address
            </label>

            <input
                type="text"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="name@example.com"
                class="block w-full rounded-lg border
                @error('email')
                    border-red-500 focus:border-red-500 focus:ring-red-500
                @else
                    border-gray-300 focus:border-gray-900 focus:ring-gray-900
                @enderror
                px-3 py-2.5 text-sm text-gray-900 outline-none transition"
            >

            @error('email')
                <p class="mt-1.5 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Password --}}
        <div>

            <label
                for="password"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Minimum 8 characters"
                class="block w-full rounded-lg border
                @error('password')
                    border-red-500 focus:border-red-500 focus:ring-red-500
                @else
                    border-gray-300 focus:border-gray-900 focus:ring-gray-900
                @enderror
                px-3 py-2.5 text-sm text-gray-900 outline-none transition"
            >

            @error('password')
                <p class="mt-1.5 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Confirm Password --}}
        <div>

            <label
                for="password_confirmation"
                class="mb-2 block text-sm font-medium text-gray-700"
            >
                Confirm Password
            </label>

            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                placeholder="Re-enter your password"
                class="block w-full rounded-lg border
                @error('password')
                    border-red-500 focus:border-red-500 focus:ring-red-500
                @else
                    border-gray-300 focus:border-gray-900 focus:ring-gray-900
                @enderror
                px-3 py-2.5 text-sm text-gray-900 outline-none transition"
            >

            @error('password')
                <p class="mt-1.5 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror

        </div>


        {{-- Register Button --}}
        <button
            type="submit"
            class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
        >
            Create Account
        </button>

    </form>


    {{-- Login Link --}}
    <div class="mt-6 border-t border-gray-100 pt-5 text-center">

        <p class="text-sm text-gray-500">
            Already have an account?

            <a
                href="{{ route('login') }}"
                class="font-medium text-gray-900 underline-offset-4 hover:underline"
            >
                Sign in
            </a>
        </p>

    </div>

</div>
```

</div>

@endsection
