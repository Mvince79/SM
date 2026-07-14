@extends('layouts.app')

@section('content')
    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">
                    Bảng điều khiển tình nguyện viên
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Tìm và xác minh các manh mối gần vị trí của bạn.
                </p>
            </div>

            <livewire:volunteer.assignment-board />
        </div>
    </div>
@endsection