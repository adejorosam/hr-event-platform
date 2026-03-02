<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'salary' => (float) $this->salary,
            'country' => $this->country,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Include country-specific fields
        match ($this->country) {
            'USA' => $data = array_merge($data, [
                'ssn' => $this->ssn,
                'address' => $this->address,
            ]),
            'Germany' => $data = array_merge($data, [
                'goal' => $this->goal,
                'tax_id' => $this->tax_id,
            ]),
            default => null,
        };

        return $data;
    }
}
