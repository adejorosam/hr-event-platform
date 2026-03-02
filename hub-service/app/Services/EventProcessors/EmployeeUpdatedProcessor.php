<?php

namespace App\Services\EventProcessors;

use App\Contracts\EventProcessorInterface;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Events\EmployeeUpdatedBroadcast;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

class EmployeeUpdatedProcessor implements EventProcessorInterface
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'EmployeeUpdated';
    }

    public function process(array $event): void
    {
        $country = $event['country'];
        $employeeData = $event['data']['employee'] ?? [];
        $employeeId = $event['data']['employee_id'];
        $changedFields = $event['data']['changed_fields'] ?? [];

        Log::info("Processing EmployeeUpdated event", [
            'employee_id' => $employeeId,
            'country' => $country,
            'changed_fields' => $changedFields,
        ]);

        // Update cached employee data
        $this->cacheService->setEmployee($country, $employeeId, $employeeData);

        // Invalidate list and checklist caches
        $this->cacheService->invalidateEmployeeCache($country);
        $this->cacheService->invalidateChecklistCache($country);

        // Broadcast real-time updates
        broadcast(new EmployeeListUpdated($country))->toOthers();
        broadcast(new ChecklistUpdated($country))->toOthers();
        broadcast(new EmployeeUpdatedBroadcast($country, $employeeId, $changedFields))->toOthers();

        Log::info("EmployeeUpdated event processed successfully", [
            'employee_id' => $employeeId,
        ]);
    }
}
