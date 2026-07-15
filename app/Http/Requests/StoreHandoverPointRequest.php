<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHandoverPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:POLICE_STATION,COMMUNE_PEOPLE_COMMITTEE,HOTLINE_111,NGO,OTHER'],
            'address' => ['required', 'string', 'max:500'],
            'province_id' => ['required', 'exists:provinces,id'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'hotline_phone' => ['nullable', 'string', 'max:20'],
            'responsible_org_name' => ['required', 'string', 'max:255'],
            // required trừ HOTLINE_111
            'legal_authority_note' => ['required_unless:type,HOTLINE_111', 'nullable', 'string'],
            'verification_document_path' => ['nullable', 'string', 'max:500'],
            'priority_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'legal_authority_note.required_unless' => 'Văn bản xác nhận thẩm quyền pháp lý là bắt buộc với loại điểm này.',
        ];
    }
}