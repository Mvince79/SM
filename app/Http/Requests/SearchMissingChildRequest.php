<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class SearchMissingChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'gender' => 'nullable|in:MALE,FEMALE,UNKNOWN',
            'age_range' => 'nullable|string|in:0-5,5-10,10-15,15-99',
            'age_min' => 'nullable|integer|min:0|max:100',
            'age_max' => 'nullable|integer|min:0|max:100',
            'missing_date_from' => 'nullable|date',
            'missing_date_to' => 'nullable|date',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius_km' => 'nullable|numeric|min:1|max:500',
            'sort' => 'nullable|in:newest,oldest,near_me',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'province_id.integer' => 'Tỉnh phải là số nguyên',
            'age_min.integer' => 'Tuổi tối thiểu phải là số',
            'age_max.integer' => 'Tuổi tối đa phải là số',
            'gender.in' => 'Giới tính không hợp lệ',
            'sort.in' => 'Loại sắp xếp không hợp lệ',
            'lat.between' => 'Vĩ độ phải trong khoảng -90 đến 90',
            'lng.between' => 'Kinh độ phải trong khoảng -180 đến 180',
        ];
    }
}
