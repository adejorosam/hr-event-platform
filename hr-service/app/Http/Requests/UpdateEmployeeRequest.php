<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $country = $this->input('country', $employee?->country);

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
            'country' => ['sometimes', 'string', 'in:USA,Germany'],
        ];

        return match ($country) {
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
