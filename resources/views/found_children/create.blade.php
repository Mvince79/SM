@extends('layouts.app')

@section('title', 'Tạo hồ sơ tìm được')

@section('content')
<main class="pt-24 pb-16">
    <div class="max-w-4xl mx-auto px-4">
        <h1 class="text-2xl font-bold mb-6">Tạo hồ sơ tìm được</h1>

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                <strong>Lỗi:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('found-children.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg border">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Tỉnh/Thành -->
                <div>
                    <label class="block text-sm font-medium mb-1">Tỉnh/Thành <span class="text-red-500">*</span></label>
                    <select name="province_id" class="w-full border rounded h-12 px-3 @error('province_id') border-red-500 @enderror">
                        <option value="">-- Chọn --</option>
                        @foreach($provinces as $p)
                            <option value="{{ $p->id }}" {{ old('province_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('province_id')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>

                <!-- Ngày phát hiện -->
                <div>
                    <label class="block text-sm font-medium mb-1">Ngày phát hiện <span class="text-red-500">*</span></label>
                    <input type="date" name="found_date" value="{{ old('found_date') }}" class="w-full border rounded h-12 px-3 @error('found_date') border-red-500 @enderror" />
                    @error('found_date')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>

                <!-- Giờ phát hiện -->
                <div>
                    <label class="block text-sm font-medium mb-1">Giờ phát hiện</label>
                    <input type="time" name="found_time" value="{{ old('found_time') }}" class="w-full border rounded h-12 px-3 @error('found_time') border-red-500 @enderror" />
                    @error('found_time')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>

                <!-- Giới tính ước tính -->
                <div>
                    <label class="block text-sm font-medium mb-1">Giới tính ước tính</label>
                    <select name="gender_estimate" class="w-full border rounded h-12 px-3 @error('gender_estimate') border-red-500 @enderror">
                        <option value="">-- Không xác định --</option>
                        <option value="MALE" {{ old('gender_estimate') == 'MALE' ? 'selected' : '' }}>Nam</option>
                        <option value="FEMALE" {{ old('gender_estimate') == 'FEMALE' ? 'selected' : '' }}>Nữ</option>
                        <option value="UNKNOWN" {{ old('gender_estimate') == 'UNKNOWN' ? 'selected' : '' }}>Không rõ</option>
                    </select>
                    @error('gender_estimate')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>

                <!-- Tuổi ước tính -->
                <div>
                    <label class="block text-sm font-medium mb-1">Tuổi ước tính</label>
                    <input type="number" name="age_estimate" value="{{ old('age_estimate') }}" min="0" max="120" class="w-full border rounded h-12 px-3 @error('age_estimate') border-red-500 @enderror" />
                    @error('age_estimate')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>

                <!-- Vị trí hiện tại -->
                <div>
                    <label class="block text-sm font-medium mb-1">Vị trí hiện tại</label>
                    <input type="text" name="current_location" value="{{ old('current_location') }}" placeholder="Địa chỉ, tên địa điểm..." class="w-full border rounded h-12 px-3 @error('current_location') border-red-500 @enderror" />
                    @error('current_location')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>

                <!-- Cơ quan bàn giao -->
                <div>
                    <label class="block text-sm font-medium mb-1">Cơ quan/Nơi bàn giao</label>
                    <input type="text" name="handover_agency" value="{{ old('handover_agency') }}" placeholder="Công an, Y tế, SOS..." class="w-full border rounded h-12 px-3 @error('handover_agency') border-red-500 @enderror" />
                    @error('handover_agency')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                </div>
            </div>

            <!-- Mô tả tình trạng -->
            <div class="mt-4">
                <label class="block text-sm font-medium mb-1">Mô tả tình trạng</label>
                <textarea name="condition_desc" placeholder="Mô tả chi tiết tình trạng sức khỏe, dấu hiệu khác thường..." class="w-full border rounded p-3 @error('condition_desc') border-red-500 @enderror" rows="4">{{ old('condition_desc') }}</textarea>
                @error('condition_desc')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
            </div>

            <!-- Mô tả quần áo -->
            <div class="mt-4">
                <label class="block text-sm font-medium mb-1">Mô tả quần áo</label>
                <textarea name="clothes_desc" placeholder="Mô tả chi tiết quần áo, tính chất đặc biệt..." class="w-full border rounded p-3 @error('clothes_desc') border-red-500 @enderror" rows="3">{{ old('clothes_desc') }}</textarea>
                @error('clothes_desc')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
            </div>

            <!-- Dấu hiệu đặc biệt -->
            <div class="mt-4">
                <label class="block text-sm font-medium mb-1">Dấu hiệu đặc biệt (scar, birthmark...)</label>
                <textarea name="special_marks" placeholder="Mô tả dấu chân, scar, điểm nhận dạng khác..." class="w-full border rounded p-3 @error('special_marks') border-red-500 @enderror" rows="3">{{ old('special_marks') }}</textarea>
                @error('special_marks')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
            </div>

            <!-- Ảnh -->
            <div class="mt-4">
                <label class="block text-sm font-medium mb-1">Ảnh (bắt buộc) <span id="photos-count" class="text-xs text-slate-500">(0)</span></label>
                <input id="photos-input" type="file" name="photos[]" accept="image/*" multiple class="w-full @error('photos') border-red-500 @enderror" required />
                <p class="text-xs text-slate-500 mt-1">Chọn nhiều ảnh nếu cần. Định dạng: jpg, jpeg, png, webp. Kích thước tối đa 5MB mỗi ảnh.</p>
                @error('photos')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
                @error('photos.*')<span class="text-xs text-red-500 mt-1">{{ $message }}</span>@enderror
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('found-children.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Hủy</a>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:opacity-90">Ok</button>
            </div>
        </form>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('photos-input');
    const count = document.getElementById('photos-count');
    if (!input) return;
    function update() {
        const n = input.files ? input.files.length : 0;
        count.textContent = `(${n})`;
    }
    input.addEventListener('change', update);
    update();
});
</script>
@endsection
