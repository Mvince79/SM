<?php

namespace App\Http\Requests;

use App\Rules\SecureImage;
use Illuminate\Foundation\Http\FormRequest;

class FoundChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    // F-trim — mặc định found_date = hôm nay khi bỏ trống, tránh vi phạm
    // ràng buộc NOT NULL của DB (form người dùng đã để optional).
    protected function prepareForValidation(): void
    {
        if (empty($this->found_date)) {
            $this->merge(['found_date' => now()->toDateString()]);
        }
    }

    public function rules(): array
    {
        return [
            'province_id' => 'required|exists:provinces,id',
            'photos' => 'required|array|min:1',
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', new SecureImage],
            // F-trim — để optional (form mặc định hôm nay). prepareForValidation
            // đảm bảo API không bao giờ insert NULL vào cột found_date NOT NULL.
            'found_date' => 'nullable|date',
            'found_time' => 'nullable|date_format:H:i',
            'gender_estimate' => 'nullable|in:MALE,FEMALE,UNKNOWN',
            'age_estimate' => 'nullable|integer|min:0|max:120',
            'condition_desc' => 'nullable|string',
            'clothes_desc' => 'nullable|string',
            'special_marks' => 'nullable|string',
            // U-01 — `current_location` KHÔNG nhận từ input: vị trí chỉ đến từ map
            // picker (lat/lng) và cấp hành chính do reverse-geocode nội bộ suy ra.
            // Hành chính VN từ 01/07/2025 chỉ còn 2 cấp Tỉnh → Xã/Phường.
            'handover_agency' => 'nullable|string|max:200',
            'idempotency_key' => 'nullable|uuid',

            // C-01 — Checkbox cam kết giới hạn sử dụng dữ liệu (bắt buộc).
            'data_use_consent' => 'accepted',
        ];
    }

    public function attributes(): array
    {
        return [
            'province_id' => 'tỉnh/thành',
            'found_date' => 'ngày phát hiện',
            'photos' => 'ảnh',
            'found_time' => 'giờ phát hiện',
            'gender_estimate' => 'giới tính ước tính',
            'age_estimate' => 'tuổi ước tính',
            'condition_desc' => 'mô tả tình trạng',
            'clothes_desc' => 'mô tả quần áo',
            'special_marks' => 'dấu hiệu đặc biệt',
            'handover_agency' => 'cơ quan/nơi bàn giao',
            'idempotency_key' => 'mã chống gửi trùng',
            'data_use_consent' => 'đồng ý giới hạn sử dụng dữ liệu',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute là bắt buộc.',
            'exists' => ':attribute không hợp lệ.',
            'date' => ':attribute phải là ngày hợp lệ.',
            'date_format' => ':attribute phải đúng định dạng HH:MM.',
            'in' => ':attribute không hợp lệ.',
            'integer' => ':attribute phải là số nguyên.',
            'min' => ':attribute phải lớn hơn hoặc bằng :min.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'string' => ':attribute phải là chuỗi ký tự.',
        ];
    }
}
