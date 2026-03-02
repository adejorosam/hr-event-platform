<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\EventPublisher;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    public function __construct(
        private readonly EventPublisher $eventPublisher
    ) {}

    public function created(Employee $employee): void
    {
        try {
            $this->eventPublisher->publish('EmployeeCreated', $employee->country, [
                'employee_id' => $employee->id,
                'employee' => $employee->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish EmployeeCreated event', ['error' => $e->getMessage()]);
        }
    }

    public function updated(Employee $employee): void
    {
        try {
            $changedFields = array_keys($employee->getChanges());
            // Remove timestamps from changed fields
            $changedFields = array_diff($changedFields, ['updated_at', 'created_at']);

            $this->eventPublisher->publish('EmployeeUpdated', $employee->country, [
                'employee_id' => $employee->id,
                'changed_fields' => array_values($changedFields),
                'employee' => $employee->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish EmployeeUpdated event', ['error' => $e->getMessage()]);
        }
    }

    public function deleted(Employee $employee): void
    {
        try {
            $this->eventPublisher->publish('EmployeeDeleted', $employee->country, [
                'employee_id' => $employee->id,
                'employee' => $employee->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish EmployeeDeleted event', ['error' => $e->getMessage()]);
        }
    }
}
