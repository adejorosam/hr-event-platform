<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeUpdatedBroadcast implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $country,
        public readonly int $employeeId,
        public readonly array $changedFields
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("employee.{$this->country}.{$this->employeeId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'country' => $this->country,
            'changed_fields' => $this->changedFields,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
