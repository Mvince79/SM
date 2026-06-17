@if (session('error') || session('errors')?->any())
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[calc(100%-2rem)] max-w-lg">
        <div
            class="bg-red-50 border border-red-200 text-red-600 font-bold text-sm px-5 py-4 rounded-2xl shadow-lg flex items-center gap-3">
            <span class="text-base shrink-0"><x-heroicon-o-x-circle class="w-4 h-4" /></span>
            <span class="flex-1">
                @if (session('error'))
                    {{ session('error') }}
                @else
                    @foreach (session('errors')->all() as $error)
                        <span class="block">{{ $error }}</span>
                    @endforeach
                @endif
            </span>
            <button @click="show = false" class="shrink-0 text-red-400 hover:text-red-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
@endif
