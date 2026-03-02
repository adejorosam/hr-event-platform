<?php

namespace App\Services\Checklist;

use App\Contracts\CountryChecklistInterface;

class USAChecklist implements CountryChecklistInterface
{
    public function getCountryCode(): string
    {
        return 'USA';
    }

    public function getRequirements(): array
    {
        return [
            'ssn' => [
                'label' => 'Social Security Number',
                'rule' => fn ($value) => !empty($value),
            ],
            'salary' => [
                'label' => 'Salary',
                'rule' => fn ($value) => !empty($value) && (float) $value > 0,
            ],
            'address' => [
                'label' => 'Address',
                'rule' => fn ($value) => !empty($value) && trim($value) !== '',
            ],
        ];
    }

    public function validate(array $employee): array
    {
        $items = [];

        foreach ($this->getRequirements() as $field => $config) {
            $value = $employee[$field] ?? null;
            $isComplete = ($config['rule'])($value);

            $items[] = [
                'field' => $field,
                'label' => $config['label'],
                'complete' => $isComplete,
                'message' => $isComplete ? null : "Missing {$config['label']}: Please provide the employee's {$config['label']}.",
            ];
        }

        return $items;
    }
}
