<?php

namespace App\Http\Requests\Missing;

use Illuminate\Foundation\Http\FormRequest;

class SearchMissingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'integer'],
            'gender' => ['nullable', 'in:MALE,FEMALE'],
            'age' => ['nullable', 'in:0-5,5-10,10-15,15+'],
            'status' => ['nullable', 'in:APPROVED,FOUND'],
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
            'status' => $this->query('status', ''),
        ];
    }
}