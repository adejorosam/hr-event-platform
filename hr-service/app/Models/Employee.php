<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'salary',
        'country',
        'ssn',
        'address',
        'goal',
        'tax_id',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
    ];

    /**
     * Get the country-specific fields for this employee.
     */
    public function getCountryFields(): array
    {
        return match ($this->country) {
            'USA' => ['ssn', 'address'],
            'Germany' => ['goal', 'tax_id'],
            default => [],
        };
    }
}
