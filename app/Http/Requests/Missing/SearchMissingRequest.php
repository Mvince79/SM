<?php

namespace App\Http\Requests\Missing;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchMissingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'integer', 'exists:provinces,id'],
            'gender' => ['nullable', 'in:MALE,FEMALE'],
            'age' => ['nullable', 'in:0-5,5-10,10-15,15+'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return [
            'q' => $this->query('q', ''),
            'province' => $this->query('province', ''),
            'gender' => $this->query('gender', ''),
            'age' => $this->query('age', ''),
        ];
    }
}
