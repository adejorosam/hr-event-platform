<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'country' => ['required', 'string', 'in:USA,Germany'],
        ];

        return match ($this->input('country')) {
            'USA' => array_merge($rules, [
                'ssn' => ['nullable', 'string', 'regex:/^\d{3}-\d{2}-\d{4}$/'],
                'address' => ['nullable', 'string', 'max:500'],
            ]),
            'Germany' => array_merge($rules, [
                'goal' => ['nullable', 'string', 'max:500'],
                'tax_id' => ['nullable', 'string', 'regex:/^DE\d{9}$/'],
            ]),
            default => $rules,
        };
    }
}
