<?php

namespace Tests\Unit;

use App\Contracts\EventProcessorInterface;
use App\Services\EventProcessors\EventProcessorRegistry;
use Tests\TestCase;

class EventProcessorRegistryTest extends TestCase
{
    public function test_routes_event_to_correct_processor(): void
    {
        $registry = new EventProcessorRegistry();

        $processor = $this->createMock(EventProcessorInterface::class);
        $processor->method('supports')->willReturnCallback(
            fn (string $type) => $type === 'EmployeeCreated'
        );
        $processor->expects($this->once())->method('process');

        $registry->register($processor);

        $registry->process([
            'event_type' => 'EmployeeCreated',
            'country' => 'USA',
            'data' => ['employee_id' => 1],
        ]);
    }

    public function test_does_not_call_unsupported_processor(): void
    {
        $registry = new EventProcessorRegistry();

        $processor = $this->createMock(EventProcessorInterface::class);
        $processor->method('supports')->willReturn(false);
        $processor->expects($this->never())->method('process');

        $registry->register($processor);

        $registry->process([
            'event_type' => 'UnknownEvent',
            'country' => 'USA',
            'data' => [],
        ]);
    }
}
