<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FoundChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'province_id' => 'required|exists:provinces,id',
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'found_date' => 'required|date',
            'found_time' => 'nullable|date_format:H:i',
            'gender_estimate' => 'nullable|in:MALE,FEMALE,UNKNOWN',
            'age_estimate' => 'nullable|integer|min:0|max:120',
            'condition_desc' => 'nullable|string',
            'clothes_desc' => 'nullable|string',
            'special_marks' => 'nullable|string',
            'current_location' => 'nullable|string|max:300',
            'handover_agency' => 'nullable|string|max:200',
            'idempotency_key' => 'nullable|uuid',
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
            'current_location' => 'vị trí hiện tại',
            'handover_agency' => 'cơ quan/nơi bàn giao',
            'idempotency_key' => 'mã chống gửi trùng',
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
