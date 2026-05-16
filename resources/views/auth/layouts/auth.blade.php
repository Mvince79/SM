<!DOCTYPE html>
<html lang="vi" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Xác thực') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-[#f5f4f0] font-sans">

    <div class="min-h-full flex flex-col justify-center py-10 px-4">

        {{-- Brand --}}
        <div class="mx-auto w-full max-w-md mb-7">
            <div class="flex items-center justify-center gap-3">
                <div
                    class="w-11 h-11 bg-[#1a56db] rounded-2xl flex items-center justify-center shadow-md flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m21 21-4.35-4.35" />
                    </svg>
                </div>
                <div>
                    <div class="text-base font-bold text-gray-900 leading-tight" style="letter-spacing:-0.015em;">
                        Tìm Kiếm Trẻ Em Thất Lạc
                    </div>
                    <div class="text-xs text-gray-400 font-normal">Cùng nhau tìm về nhà</div>
                </div>
            </div>

            <div class="mt-5 text-center">
                <h1 class="text-2xl font-bold text-gray-900" style="letter-spacing:-0.02em;">
                    @yield('title', 'Xác thực')
                </h1>
                @hasSection('subtitle')
                    <p class="mt-1 text-sm text-gray-500">@yield('subtitle')</p>
                @endif
            </div>
        </div>

        {{-- Card --}}
        <div class="mx-auto w-full max-w-md">
            <div class="bg-white rounded-2xl px-7 py-8 shadow-sm" style="border: 1px solid #e9e7df;">

                {{-- Flash --}}
                @if(session('success'))
                    <div class="mb-5 flex gap-2.5 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
                        <svg class="w-4 h-4 text-green-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-green-700">{{ session('success') }}</p>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 flex gap-2.5 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                        <svg class="w-4 h-4 text-red-600 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-red-700">{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>

    </div>

</body>

</html>