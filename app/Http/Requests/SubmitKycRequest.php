<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ cần đăng nhập là được nộp KYC (chưa cần kyc_status VERIFIED -
        // ngược lại sẽ không ai nộp được lần đầu). Việc chặn đăng tin khi
        // chưa VERIFIED nằm ở MissingChildPolicy/FoundChildPolicy, không
        // phải ở đây.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_type' => ['required', 'in:CMND_9,CCCD_12'],
            'id_number' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $idType = $this->input('id_type');

                    if ($idType === 'CMND_9' && !preg_match('/^\d{9}$/', $value)) {
                        $fail('CMND phải gồm đúng 9 chữ số.');
                    }

                    if ($idType === 'CCCD_12' && !preg_match('/^\d{12}$/', $value)) {
                        $fail('CCCD phải gồm đúng 12 chữ số.');
                    }
                },
            ],
            'full_name_on_id' => ['nullable', 'string', 'max:255'],
            'dob_on_id' => ['nullable', 'date', 'before:today'],
            'front_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'back_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_type.required' => 'Vui lòng chọn loại giấy tờ (CMND hoặc CCCD).',
            'id_type.in' => 'Loại giấy tờ không hợp lệ.',
            'id_number.required' => 'Vui lòng nhập số CCCD/CMND.',
            'front_image.required' => 'Vui lòng tải ảnh mặt trước CCCD.',
            'back_image.required' => 'Vui lòng tải ảnh mặt sau CCCD.',
            'selfie.required' => 'Vui lòng tải ảnh selfie.',
            '*.image' => 'File phải là hình ảnh.',
            '*.max' => 'Dung lượng ảnh tối đa 5MB.',
        ];
    }
}
