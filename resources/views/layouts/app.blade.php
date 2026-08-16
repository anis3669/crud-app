<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'ProductHub')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-900">

    <div class="min-h-screen">

        <!-- Top Navigation -->
        <!-- Top Navigation -->
<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

        <!-- Brand -->
        <a
            href="{{ route('products.index') }}"
            class="flex items-center gap-2"
        >
            <div class="flex h-8 w-8 items-center justify-center rounded-md bg-gray-900 text-sm font-semibold text-white">
                P
            </div>

            <span class="text-base font-semibold tracking-tight text-gray-900">
                ProductHub
            </span>
        </a>

    </div>
</header>


        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @if(session('success'))
    <div
        id="success-message"
        class="fixed right-6 top-6 z-50 rounded-lg bg-green-600 px-5 py-3 text-sm font-medium text-white shadow-lg"
    >
         {{ session('success') }}
    </div>

    <script>
        setTimeout(() => {
            const message = document.getElementById('success-message');

            if (message) {
                message.remove();
            }
        }, 3000);
    </script>
@endif

            @yield('content')

        </main>

    </div>

</body>
</html>