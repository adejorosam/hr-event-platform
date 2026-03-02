<?php

namespace App\Services\EventProcessors;

use App\Contracts\EventProcessorInterface;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

class EmployeeCreatedProcessor implements EventProcessorInterface
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'EmployeeCreated';
    }

    public function process(array $event): void
    {
        $country = $event['country'];
        $employeeData = $event['data']['employee'] ?? [];
        $employeeId = $event['data']['employee_id'];

        Log::info("Processing EmployeeCreated event", [
            'employee_id' => $employeeId,
            'country' => $country,
        ]);

        // Cache the individual employee
        $this->cacheService->setEmployee($country, $employeeId, $employeeData);

        // Invalidate list and checklist caches for this country
        $this->cacheService->invalidateEmployeeCache($country);
        $this->cacheService->invalidateChecklistCache($country);

        // Broadcast real-time updates
        broadcast(new EmployeeListUpdated($country))->toOthers();
        broadcast(new ChecklistUpdated($country))->toOthers();

        Log::info("EmployeeCreated event processed successfully", [
            'employee_id' => $employeeId,
        ]);
    }
}
