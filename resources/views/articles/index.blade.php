@extends('layouts.app')
@section('title', 'Bài viết')
@section('content')
<main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-black text-slate-900 mb-8">Bài viết</h1>

    @if ($articles->isEmpty())
        <div class="px-4 py-24 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                <x-heroicon-o-newspaper class="h-8 w-8" />
            </div>
            <p class="mt-4 text-lg font-black text-slate-700">Chưa có bài viết nào</p>
            <p class="mt-1 text-sm text-slate-500">Các bài viết mới sẽ xuất hiện ở đây khi được đăng tải.</p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($articles as $article)
                <a href="{{ route('articles.show', $article) }}"
                    class="bg-white border border-slate-200/85 rounded-2xl overflow-hidden hover:shadow-lg transition-shadow group">
                    @if ($article->cover_image_path)
                        <img src="{{ Storage::url($article->cover_image_path) }}"
                            class="w-full aspect-video object-cover" />
                    @else
                        <div class="w-full aspect-video bg-slate-100 flex items-center justify-center">
                            <x-heroicon-o-newspaper class="w-8 h-8 text-slate-300" />
                        </div>
                    @endif
                    <div class="p-5">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1.5">
                            {{ $article->published_at->translatedFormat('d/m/Y') }}
                        </p>
                        <h2 class="font-black text-slate-900 group-hover:text-primary transition-colors line-clamp-2">
                            {{ $article->title }}
                        </h2>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-10">{{ $articles->links() }}</div>
    @endif
</main>
@endsection