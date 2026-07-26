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
@section('content')
    <main class="max-w-3xl mx-auto px-4 py-10">
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-1.5 text-xs font-bold text-slate-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-primary">Trang chủ</a>
            <x-heroicon-o-chevron-right class="w-3 h-3" />
            <a href="{{ route('articles.index') }}" class="hover:text-primary">Bài viết</a>
            <x-heroicon-o-chevron-right class="w-3 h-3" />
            <span class="text-slate-600 truncate">{{ $article->title }}</span>
        </nav>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">{{ $article->title }}</h1>
        <p class="text-xs text-slate-400 font-semibold mt-2">
            {{ $article->author->full_name }} · {{ $article->published_at->translatedFormat('d/m/Y') }}
        </p>

        @if ($article->cover_image_path)
            <img src="{{ Storage::url($article->cover_image_path) }}" class="w-full rounded-2xl mt-6" />
        @endif

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
                            class="bg-slate-50 rounded-xl p-4 hover:bg-slate-100 transition-colors">
                            <p class="text-sm font-bold text-slate-800 line-clamp-2">{{ $item->title }}</p>
                            <p class="text-[10px] text-slate-400 font-semibold mt-1">
                                {{ $item->published_at->translatedFormat('d/m/Y') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </main>
@endsection