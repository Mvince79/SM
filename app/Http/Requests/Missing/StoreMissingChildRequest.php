<?php

namespace App\Http\Requests\Missing;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreMissingChildRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Bước 1
            'full_name' => ['required', 'string', 'max:100'],
            'dob' => ['nullable', 'date', 'before:today'],
            'age_estimate' => ['nullable', 'integer', 'min:0', 'max:18'],
            'gender' => ['required', 'in:MALE,FEMALE,UNKNOWN'],
            'missing_date' => ['required', 'date', 'before_or_equal:today'],
            'missing_time' => ['nullable', 'date_format:H:i'],
            'province_id' => ['required', 'exists:provinces,id'],
            'district' => ['nullable', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'address_detail' => ['nullable', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'circumstances' => ['nullable', 'string'],

            // Bước 2
            'skin_tone' => ['nullable', 'in:LIGHT,MEDIUM,DARK'],
            'height_cm' => ['nullable', 'integer', 'min:50', 'max:200'],
            'weight_kg' => ['nullable', 'numeric', 'min:5', 'max:100'],
            'clothes_desc' => ['nullable', 'string'],
            'clothes_color' => ['nullable', 'string', 'max:100'],
            'special_marks' => ['required', 'string', 'min:20'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'max:5120'],

            // Bước 3
            'contact_name' => ['required', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{8,10}$/'],
            'contact_relation' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên của bé.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'missing_date.required' => 'Vui lòng nhập ngày mất tích.',
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'special_marks.required' => 'Vui lòng mô tả đặc điểm nhận dạng.',
            'special_marks.min' => 'Mô tả cần ít nhất 20 ký tự.',
            'contact_name.required' => 'Vui lòng nhập tên người liên hệ.',
            'contact_phone.required' => 'Vui lòng nhập số điện thoại.',
            'contact_phone.regex' => 'Số điện thoại không hợp lệ.',
            'photos.*.max' => 'Mỗi ảnh tối đa 5MB.',
            'photos.max' => 'Tối đa 5 ảnh.',
        ];
    }
}
