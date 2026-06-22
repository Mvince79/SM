<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WatchAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cho phép cả guest — đây là tính năng không cần đăng nhập
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'mã thiết bị',
            'lat' => 'vĩ độ',
            'lng' => 'kinh độ',
            'radius_km' => 'bán kính theo dõi',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute là bắt buộc.',
            'numeric' => ':attribute phải là số.',
            'between' => ':attribute phải trong khoảng hợp lệ.',
            'integer' => ':attribute phải là số nguyên.',
            'min' => ':attribute phải lớn hơn hoặc bằng :min.',
            'max' => ':attribute không được vượt quá :max.',
        ];
    }
}