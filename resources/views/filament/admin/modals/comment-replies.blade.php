{{-- resources/views/filament/modals/comment-replies.blade.php --}}
{{-- Nhận $comment (App\Models\Comment) từ Action::modalContent() --}}

@php
    $replies = $comment->replies()->with('user')->latest()->get();
@endphp

<div class="space-y-3 py-2">

    @if ($replies->isEmpty())
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-6">
            Chưa có replies nào.
        </div>
    @else
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-4">
            {{ $replies->count() }} reply
        </p>

        @foreach ($replies as $reply)
            <div
                class="flex gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3
                       {{ (int) $reply->is_hidden === 1 ? 'opacity-50' : '' }}"
            >
                {{-- Avatar placeholder --}}
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-primary-700 dark:text-primary-300 text-sm font-semibold uppercase">
                        {{ mb_substr($reply->user?->full_name ?? 'K', 0, 1) }}
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $reply->user?->full_name ?? 'Khách' }}
                        </span>

                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $reply->created_at->format('d/m/Y H:i') }}
                        </span>

                        @if ((int) $reply->is_hidden === 1)
                            <span class="inline-flex items-center rounded-full bg-red-100 dark:bg-red-900 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                                Ẩn
                            </span>
                        @endif

                        @if ((int) $reply->is_approved === 0)
                            <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:text-yellow-300">
                                Chờ duyệt
                            </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 break-words">
                        {{ $reply->body }}
                    </p>
                </div>
            </div>
        @endforeach
    @endif

</div>