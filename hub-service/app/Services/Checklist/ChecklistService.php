<?php

namespace App\Services\Checklist;

use App\Contracts\CountryChecklistInterface;
use App\Services\Cache\CacheService;
use App\Services\HrServiceClient;
use Illuminate\Support\Facades\Log;

class ChecklistService
{
    /** @var array<string, CountryChecklistInterface> */
    private array $checklists = [];

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly HrServiceClient $hrServiceClient
    ) {}

    public function registerChecklist(CountryChecklistInterface $checklist): void
    {
        $this->checklists[$checklist->getCountryCode()] = $checklist;
    }

    public function getChecklistData(string $country): array
    {
        // Try cache first
        $cached = $this->cacheService->getChecklist($country);
        if ($cached !== null) {
            Log::debug("Checklist cache hit for {$country}");
            return $cached;
        }

        Log::debug("Checklist cache miss for {$country}, computing...");

        $checklist = $this->getChecklistForCountry($country);
        if ($checklist === null) {
            return $this->emptyResponse($country);
        }

        // Fetch all employees from HR Service
        $employees = $this->hrServiceClient->getAllEmployeesByCountry($country);

        $employeeResults = [];
        $fullyComplete = 0;
        $totalRequirements = count($checklist->getRequirements());

        foreach ($employees as $employee) {
            $items = $checklist->validate($employee);
            $completedItems = count(array_filter($items, fn ($item) => $item['complete']));
            $percentage = $totalRequirements > 0
                ? round(($completedItems / $totalRequirements) * 100, 1)
                : 100;

            if ($percentage >= 100) {
                $fullyComplete++;
            }

            $employeeResults[] = [
                'employee_id' => $employee['id'],
                'name' => $employee['name'] . ' ' . $employee['last_name'],
                'completion_percentage' => $percentage,
                'items' => $items,
            ];
        }

        $totalEmployees = count($employees);
        $result = [
            'country' => $country,
            'total_employees' => $totalEmployees,
            'fully_complete' => $fullyComplete,
            'completion_rate' => $totalEmployees > 0
                ? round(($fullyComplete / $totalEmployees) * 100, 1)
                : 0,
            'employees' => $employeeResults,
        ];

        // Cache the result
        $this->cacheService->setChecklist($country, $result);

        return $result;
    }

    public function getChecklistForCountry(string $country): ?CountryChecklistInterface
    {
        return $this->checklists[$country] ?? null;
    }

    private function emptyResponse(string $country): array
    {
        return [
            'country' => $country,
            'total_employees' => 0,
            'fully_complete' => 0,
            'completion_rate' => 0,
            'employees' => [],
        ];
    }
}
