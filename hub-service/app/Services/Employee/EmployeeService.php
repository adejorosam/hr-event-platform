<?php

namespace App\Services\Employee;

use App\Services\Cache\CacheService;
use App\Services\HrServiceClient;

class EmployeeService
{
    private const COLUMN_DEFINITIONS = [
        'USA' => [
            ['key' => 'name',      'label' => 'Name',      'type' => 'text'],
            ['key' => 'last_name', 'label' => 'Last Name', 'type' => 'text'],
            ['key' => 'salary',    'label' => 'Salary',    'type' => 'currency'],
            ['key' => 'ssn',       'label' => 'SSN',       'type' => 'masked'],
        ],
        'Germany' => [
            ['key' => 'name',      'label' => 'Name',      'type' => 'text'],
            ['key' => 'last_name', 'label' => 'Last Name', 'type' => 'text'],
            ['key' => 'salary',    'label' => 'Salary',    'type' => 'currency'],
            ['key' => 'goal',      'label' => 'Goal',      'type' => 'text'],
        ],
    ];

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly HrServiceClient $hrServiceClient
    ) {}

    public function getEmployeeList(string $country, int $page, int $perPage): array
    {
        $cached = $this->cacheService->getEmployeeList($country, $page, $perPage);
        if ($cached !== null) {
            return $cached;
        }

        $response = $this->hrServiceClient->getEmployees($country, $page, $perPage);

        $employees = array_map(
            fn ($emp) => $this->maskSensitiveFields($emp, $country),
            $response['data'] ?? []
        );

        $result = [
            'country'          => $country,
            'columns'          => self::COLUMN_DEFINITIONS[$country] ?? [],
            'data'             => $employees,
            'meta'             => $response['meta'] ?? [],
            'real_time_channel' => "country.{$country}",
        ];

        $this->cacheService->setEmployeeList($country, $page, $perPage, $result);

        return $result;
    }

    private function maskSensitiveFields(array $employee, string $country): array
    {
        if ($country === 'USA' && isset($employee['ssn'])) {
            $ssn = $employee['ssn'];
            if (strlen($ssn) >= 4) {
                $employee['ssn'] = '***-**-' . substr($ssn, -4);
            }
        }

        return $employee;
    }
}
