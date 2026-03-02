<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.hr_service.url');
    }

    public function getEmployees(string $country, int $page = 1, int $perPage = 15): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/api/employees", [
                    'country' => $country,
                    'page' => $page,
                    'per_page' => $perPage,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('HR Service API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['data' => [], 'meta' => []];
        } catch (\Exception $e) {
            Log::error('Failed to fetch employees from HR Service', [
                'error' => $e->getMessage(),
            ]);

            return ['data' => [], 'meta' => []];
        }
    }

    public function getEmployee(int $employeeId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/api/employees/{$employeeId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch employee from HR Service', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getAllEmployeesByCountry(string $country): array
    {
        $allEmployees = [];
        $page = 1;

        do {
            $response = $this->getEmployees($country, $page, 100);
            $employees = $response['data'] ?? [];
            $allEmployees = array_merge($allEmployees, $employees);

            $lastPage = $response['meta']['last_page'] ?? 1;
            $page++;
        } while ($page <= $lastPage);

        return $allEmployees;
    }
}
