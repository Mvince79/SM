<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'in:BUG,COMPLAINT,FEEDBACK,FRAUD_SUSPECT'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:LOW,MEDIUM,HIGH'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Vui lòng chọn loại yêu cầu.',
            'category.in' => 'Loại yêu cầu không hợp lệ.',
            'subject.required' => 'Vui lòng nhập tiêu đề.',
            'description.required' => 'Vui lòng mô tả chi tiết vấn đề.',
        ];
    }
}