<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeListUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $country
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("country.{$this->country}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'employee.list.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'country' => $this->country,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
