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
            'province_id' => ['nullable', 'integer'],  
            'gender' => ['nullable', 'in:MALE,FEMALE,UNKNOWN'],
            'age_min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'age_max' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort' => ['nullable', 'in:newest,oldest,near_me'],    
            'status' => ['nullable', 'in:APPROVED,FOUND'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return array_filter([
            'q' => $this->query('q', ''),
            'province_id' => $this->query('province_id'),  
            'gender' => $this->query('gender'),
            'age_min' => $this->query('age_min'),       
            'age_max' => $this->query('age_max'),
            'sort' => $this->query('sort', 'newest'),
            'status' => $this->query('status'),
            'page' => $this->query('page', 1),
        ], fn($v) => $v !== null && $v !== '');
    }
}