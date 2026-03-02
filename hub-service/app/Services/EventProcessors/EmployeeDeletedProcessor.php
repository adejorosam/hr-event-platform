<?php

namespace App\Services\EventProcessors;

use App\Contracts\EventProcessorInterface;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

class EmployeeDeletedProcessor implements EventProcessorInterface
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    public function supports(string $eventType): bool
    {
        return $eventType === 'EmployeeDeleted';
    }

    public function process(array $event): void
    {
        $country = $event['country'];
        $employeeId = $event['data']['employee_id'];

        Log::info("Processing EmployeeDeleted event", [
            'employee_id' => $employeeId,
            'country' => $country,
        ]);

        // Remove employee from cache
        $this->cacheService->removeEmployee($country, $employeeId);

        // Invalidate list and checklist caches
        $this->cacheService->invalidateEmployeeCache($country);
        $this->cacheService->invalidateChecklistCache($country);

        // Broadcast real-time updates
        broadcast(new EmployeeListUpdated($country))->toOthers();
        broadcast(new ChecklistUpdated($country))->toOthers();

        Log::info("EmployeeDeleted event processed successfully", [
            'employee_id' => $employeeId,
        ]);
    }
}
