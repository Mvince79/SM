@extends('layouts.app')
@section('title', $article->meta_title ?: $article->title)
@push('head')
    <meta name="description" content="{{ $article->meta_description }}">
    <x-json-ld type="article" :data="[
            'title' => $article->title,
            'meta_description' => $article->meta_description,
            'image' => $article->cover_image_path ? \Illuminate\Support\Facades\Storage::url($article->cover_image_path) : null,
            'author_name' => $article->author->full_name,
            'published_at' => $article->published_at,
            'updated_at' => $article->updated_at,
            'url' => route('articles.show', $article),
        ]" />
@endpush
@php
    // Thời gian đọc ước tính: ~200 từ/phút, làm tròn lên, tối thiểu 1 phút.
    $plainText = trim(strip_tags($article->content ?? ''));
    $wordCount = $plainText === '' ? 0 : str_word_count($plainText);
    $readingMinutes = max(1, (int) ceil($wordCount / 200));
@endphp
@section('content')
    <main class="pb-10">
        {{-- Breadcrumb --}}
        <nav class="max-w-3xl mx-auto px-4 pt-6 flex items-center gap-1.5 text-xs font-bold text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-primary">Trang chủ</a>
            <x-heroicon-o-chevron-right class="w-3 h-3" />
            <a href="{{ route('articles.index') }}" class="hover:text-primary">Bài viết</a>
            <x-heroicon-o-chevron-right class="w-3 h-3" />
            <span class="text-slate-600 truncate">{{ $article->title }}</span>
        </nav>

        {{-- Hero image: cùng khung width với nội dung, không crop mất mặt (nền mờ lấp khoảng trống) --}}
        @if ($article->cover_image_path)
            <div class="max-w-3xl mx-auto px-4">
                <div class="relative w-full h-[260px] sm:h-[360px] lg:h-[440px] mt-6 rounded-2xl overflow-hidden bg-slate-900">
                    <img src="{{ Storage::url($article->cover_image_path) }}" alt=""
                        class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-50" aria-hidden="true" />
                    <img src="{{ Storage::url($article->cover_image_path) }}" alt="{{ $article->title }}"
                        class="relative w-full h-full object-contain" />
                </div>
            </div>
        @endif

        <div class="max-w-3xl mx-auto px-4">
            {{-- Tiêu đề đặt dưới ảnh để đảm bảo tương phản chữ luôn rõ --}}
            <div class="flex items-start justify-between gap-4 mt-6">
                <h1 class="min-w-0 flex-1 text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 leading-tight">
                    {{ $article->title }}
                </h1>

                {{-- Nút chia sẻ nhanh --}}
                <div class="flex items-center gap-2 flex-shrink-0 mt-1">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('articles.show', $article)) }}"
                        target="_blank" rel="noopener" title="Chia sẻ lên Facebook"
                        class="p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm">
                        <x-heroicon-o-share class="w-4 h-4 text-slate-500" />
                    </a>
                    <button type="button" title="Sao chép liên kết"
                        onclick="navigator.clipboard.writeText(window.location.href); this.dataset.copied = 1; setTimeout(() => this.removeAttribute('data-copied'), 1500)"
                        class="relative p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors shadow-sm">
                        <x-heroicon-o-link class="w-4 h-4 text-slate-500" />
                        <span
                            class="pointer-events-none absolute -top-8 right-0 whitespace-nowrap bg-slate-900 text-white text-[10px] font-bold px-2 py-1 rounded-lg opacity-0 [[data-copied]_&]:opacity-100 transition-opacity">
                            Đã sao chép!
                        </span>
                    </button>
                </div>
            </div>

            {{-- Khối tác giả --}}
            <div class="flex items-center gap-3 mt-4">
                <img src="{{ $article->author->avatar_url }}" alt="{{ $article->author->full_name }}"
                    class="w-10 h-10 rounded-full object-cover border border-slate-200 flex-shrink-0" />
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ $article->author->full_name }}</p>
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold">
                        <span>{{ $article->published_at->translatedFormat('d/m/Y') }}</span>
                        <span>·</span>
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-o-clock class="w-3 h-3" />
                            {{ $readingMinutes }} phút đọc
                        </span>
                    </div>
                </div>
            </div>

            <div class="prose prose-slate max-w-none mt-8">
                {!! $article->content !!}
            </div>

            {{-- Bài liên quan --}}
            @if ($related->isNotEmpty())
                <div class="mt-14 pt-8 border-t border-slate-100">
                    <h3 class="font-black text-slate-900 mb-4">Bài viết liên quan</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach ($related as $item)
                            <a href="{{ route('articles.show', $item) }}"
                                class="flex items-center gap-3 bg-slate-50 rounded-xl p-3 hover:bg-slate-100 transition-colors">
                                <div class="w-20 h-16 rounded-lg overflow-hidden bg-slate-200 flex-shrink-0">
                                    @if ($item->cover_image_path)
                                        <img src="{{ Storage::url($item->cover_image_path) }}"
                                            alt="{{ $item->title }}" class="w-full h-full object-cover" />
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <x-heroicon-o-photo class="w-6 h-6 text-slate-400" />
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-800 line-clamp-2">{{ $item->title }}</p>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-1">
                                        {{ $item->published_at->translatedFormat('d/m/Y') }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection