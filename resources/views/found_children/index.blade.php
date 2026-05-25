@extends('layouts.app')

@section('title', 'Hồ sơ tìm được')

@section('content')
<main class="pt-24 pb-16">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Hồ sơ tìm được</h1>
            <a href="{{ route('found-children.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:opacity-90">+ Tạo mới</a>
        </div>

        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($items->isEmpty())
            <div class="p-8 text-center border rounded-lg bg-gray-50">
                <p class="text-slate-600">Chưa có hồ sơ tìm được nào. <a href="{{ route('found-children.create') }}" class="text-primary font-semibold">Tạo một hồ sơ</a></p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($items as $item)
                    <a href="{{ route('found-children.show', $item) }}" class="block p-4 border rounded-lg hover:shadow-md transition bg-white">
                        <div class="mb-3">
                            @php($primary = $item->photos->where('is_primary', 1)->first() ?? $item->photos->first())
                            @if($primary)
                                <img src="{{ Storage::url($primary->path_original) }}" alt="Ảnh" class="w-full h-40 object-cover rounded mb-2" />
                            @else
                                <div class="w-full h-40 bg-gray-100 rounded flex items-center justify-center text-sm text-slate-400 mb-2">Không có ảnh</div>
                            @endif
                        </div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold text-slate-500 uppercase">{{ $item->found_date->format('d/m/Y') }}</span>
                            @php($status = $item->getAttribute('status'))
                            <span class="text-xs px-2 py-1 rounded {{ $status == 'ACTIVE' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ match ($status) {
                                    'PENDING' => 'Đang chờ',
                                    'APPROVED' => 'Đã duyệt',
                                    'MATCHED' => 'Đã khớp',
                                    'FOUND' => 'Đã tìm thấy',
                                    'REJECTED' => 'Bị từ chối',
                                    'ACTIVE' => 'Đang hoạt động',
                                    'RESOLVED' => 'Đã xử lý',
                                    'CLOSED' => 'Đã đóng',
                                    default => $status,
                                } }}
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-2">{{ $item->current_location ?? 'Vị trí chưa xác định' }}</h3>
                        <p class="text-sm text-slate-600 mb-3 line-clamp-2">{{ $item->condition_desc ?? 'Chưa có mô tả' }}</p>
                        <div class="flex justify-between items-center text-xs text-slate-500">
                            <span>{{ $item->province->name }}</span>
                            <span>Người báo: {{ Str::limit($item->user->full_name, 12) }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($items->hasPages())
                <div class="mt-8">
                    {{ $items->links() }}
                </div>
            @endif
        @endif
    </div>
</main>
@endsection
