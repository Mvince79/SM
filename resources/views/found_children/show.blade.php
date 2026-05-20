@extends('layouts.app')

@section('title', 'Chi tiết hồ sơ')

@section('content')
<main class="pt-24 pb-16">
    <div class="max-w-4xl mx-auto px-4">
        @if(session('success'))
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Chi tiết hồ sơ</h1>
                <p class="text-sm text-slate-500 mt-1">Người báo: {{ $item->user->full_name }} | Ngày: {{ $item->created_at->format('d/m/Y') }}</p>
            </div>
            @if(auth()->id() === $item->user_id || auth()->user()->isAdmin())
            <div class="flex gap-2">
                <a href="{{ route('found-children.edit', $item) }}" class="px-4 py-2 border rounded bg-white hover:bg-gray-50">Sửa</a>
                <form method="POST" action="{{ route('found-children.destroy', $item) }}" class="inline" onsubmit="return confirm('Xóa hồ sơ này? Thao tác không thể hoàn tác.')">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 bg-rose-500 text-white rounded hover:bg-rose-600">Xóa</button>
                </form>
            </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Main Info -->
            <div class="lg:col-span-2">
                <!-- Basic Info -->
                <div class="bg-white p-6 rounded-lg border mb-4">
                    <h2 class="font-bold text-lg mb-4">Thông tin cơ bản</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Ngày phát hiện</p>
                            <p class="text-sm font-semibold mt-1">{{ $item->found_date->format('d/m/Y') }}</p>
                        </div>
                        @if($item->found_time)
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Giờ phát hiện</p>
                            <p class="text-sm font-semibold mt-1">{{ $item->found_time }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Tỉnh/Thành</p>
                            <p class="text-sm font-semibold mt-1">{{ $item->province->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Trạng thái</p>
                            <p class="text-sm font-semibold mt-1">
                                @php($status = $item->getAttribute('status'))
                                <span class="px-2 py-1 rounded text-xs {{ $status == 'ACTIVE' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
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
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Estimate Info -->
                <div class="bg-white p-6 rounded-lg border mb-4">
                    <h2 class="font-bold text-lg mb-4">Thông tin ước tính</h2>
                    <div class="grid grid-cols-2 gap-4">
                        @if($item->gender_estimate)
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Giới tính</p>
                            <p class="text-sm font-semibold mt-1">{{ $item->gender_estimate == 'MALE' ? 'Nam' : ($item->gender_estimate == 'FEMALE' ? 'Nữ' : 'Không xác định') }}</p>
                        </div>
                        @endif
                        @if($item->age_estimate)
                        <div>
                            <p class="text-xs text-slate-500 uppercase font-semibold">Tuổi ước tính</p>
                            <p class="text-sm font-semibold mt-1">{{ $item->age_estimate }} tuổi</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Descriptions -->
                @if($item->condition_desc)
                <div class="bg-white p-6 rounded-lg border mb-4">
                    <h2 class="font-bold text-lg mb-3">Mô tả tình trạng</h2>
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $item->condition_desc }}</p>
                </div>
                @endif

                @if($item->clothes_desc)
                <div class="bg-white p-6 rounded-lg border mb-4">
                    <h2 class="font-bold text-lg mb-3">Mô tả quần áo</h2>
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $item->clothes_desc }}</p>
                </div>
                @endif

                @if($item->special_marks)
                <div class="bg-white p-6 rounded-lg border mb-4">
                    <h2 class="font-bold text-lg mb-3">Dấu hiệu đặc biệt</h2>
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $item->special_marks }}</p>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Location -->
                @if($item->current_location)
                <div class="bg-white p-4 rounded-lg border mb-4">
                    <h3 class="font-bold text-sm mb-2">Vị trí hiện tại</h3>
                    <p class="text-sm text-slate-700">{{ $item->current_location }}</p>
                </div>
                @endif

                <!-- Handover Agency -->
                @if($item->handover_agency)
                <div class="bg-white p-4 rounded-lg border mb-4">
                    <h3 class="font-bold text-sm mb-2">Bàn giao cho</h3>
                    <p class="text-sm text-slate-700">{{ $item->handover_agency }}</p>
                </div>
                @endif

                <!-- Reported by -->
                <div class="bg-white p-4 rounded-lg border">
                    <h3 class="font-bold text-sm mb-2">Người báo cáo</h3>
                    <p class="text-sm font-semibold text-slate-800">{{ $item->user->full_name }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $item->user->email }}</p>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
