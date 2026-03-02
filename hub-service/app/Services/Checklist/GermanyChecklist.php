<?php

namespace App\Services\Checklist;

use App\Contracts\CountryChecklistInterface;

class GermanyChecklist implements CountryChecklistInterface
{
    public function getCountryCode(): string
    {
        return 'Germany';
    }

    public function getRequirements(): array
    {
        return [
            'salary' => [
                'label' => 'Salary',
                'rule' => fn ($value) => !empty($value) && (float) $value > 0,
            ],
            'goal' => [
                'label' => 'Goal',
                'rule' => fn ($value) => !empty($value) && trim($value) !== '',
            ],
            'tax_id' => [
                'label' => 'Tax ID',
                'rule' => fn ($value) => !empty($value) && preg_match('/^DE\d{9}$/', $value),
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
