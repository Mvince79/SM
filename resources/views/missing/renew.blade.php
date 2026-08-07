@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-12">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-black text-slate-900">Gia hạn hồ sơ tìm kiếm</h1>

            <p class="mt-3 text-sm text-slate-600">
                Hồ sơ <strong>{{ $missingChild->case_code }} — {{ $missingChild->full_name }}</strong>
                đang có hạn đến {{ $missingChild->expires_at->format('d/m/Y') }}.
            </p>

            <p class="mt-2 text-sm text-slate-600">
                Chỉ xác nhận nếu hồ sơ vẫn còn hiệu lực. Sau khi xác nhận, thời hạn sẽ được gia hạn thêm 2 năm.
            </p>

            <form method="POST" action="{{ $renewalAction }}" class="mt-6">
                @csrf
                <button type="submit" class="rounded-xl bg-primary px-5 py-3 text-sm font-bold text-white">
                    Xác nhận và gia hạn
                </button>
            </form>
        </div>
    </div>
@endsection
