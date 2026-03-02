<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeListRequest;
use App\Services\Cache\CacheService;
use App\Services\HrServiceClient;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    private const COLUMN_DEFINITIONS = [
        'USA' => [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['key' => 'last_name', 'label' => 'Last Name', 'type' => 'text'],
            ['key' => 'salary', 'label' => 'Salary', 'type' => 'currency'],
            ['key' => 'ssn', 'label' => 'SSN', 'type' => 'masked'],
        ],
        'Germany' => [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['key' => 'last_name', 'label' => 'Last Name', 'type' => 'text'],
            ['key' => 'salary', 'label' => 'Salary', 'type' => 'currency'],
            ['key' => 'goal', 'label' => 'Goal', 'type' => 'text'],
        ],
    ];

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly HrServiceClient $hrServiceClient
    ) {}

    public function index(EmployeeListRequest $request): JsonResponse
    {
        $country = $request->validated('country');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        // Try cache first
        $cached = $this->cacheService->getEmployeeList($country, $page, $perPage);
        if ($cached !== null) {
            return response()->json($cached);
        }

        // Fetch from HR Service
        $response = $this->hrServiceClient->getEmployees($country, $page, $perPage);

        // Mask sensitive data based on country
        $employees = array_map(
            fn ($emp) => $this->maskSensitiveFields($emp, $country),
            $response['data'] ?? []
        );

        $result = [
            'country' => $country,
            'columns' => self::COLUMN_DEFINITIONS[$country] ?? [],
            'data' => $employees,
            'meta' => $response['meta'] ?? [],
            'real_time_channel' => "country.{$country}",
        ];

        // Cache the result
        $this->cacheService->setEmployeeList($country, $page, $perPage, $result);

        return response()->json($result);
    }

    private function maskSensitiveFields(array $employee, string $country): array
    {
        if ($country === 'USA' && isset($employee['ssn'])) {
            $ssn = $employee['ssn'];
            // Mask SSN: show only last 4 digits
            if (strlen($ssn) >= 4) {
                $employee['ssn'] = '***-**-' . substr($ssn, -4);
            }
        }

        return $employee;
    }
}
