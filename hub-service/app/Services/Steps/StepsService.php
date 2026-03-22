<?php

namespace App\Services\Steps;

class StepsService
{
    private const STEPS = [
        'USA' => [
            ['id' => 'dashboard',  'label' => 'Dashboard',  'icon' => 'dashboard',   'path' => '/dashboard',  'order' => 1],
            ['id' => 'employees',  'label' => 'Employees',  'icon' => 'people',      'path' => '/employees',  'order' => 2],
        ],
        'Germany' => [
            ['id' => 'dashboard',     'label' => 'Dashboard',     'icon' => 'dashboard',   'path' => '/dashboard',     'order' => 1],
            ['id' => 'employees',     'label' => 'Employees',     'icon' => 'people',      'path' => '/employees',     'order' => 2],
            ['id' => 'documentation', 'label' => 'Documentation', 'icon' => 'description', 'path' => '/documentation', 'order' => 3],
        ],
    ];

    public function getSteps(string $country): array
    {
        return self::STEPS[$country] ?? [];
    }
}
