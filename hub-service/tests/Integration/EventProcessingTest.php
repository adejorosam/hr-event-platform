<?php

namespace Tests\Integration;

use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Events\EmployeeUpdatedBroadcast;
use App\Services\Cache\CacheService;
use App\Services\EventProcessors\EmployeeCreatedProcessor;
use App\Services\EventProcessors\EmployeeDeletedProcessor;
use App\Services\EventProcessors\EmployeeUpdatedProcessor;
use App\Services\EventProcessors\EventProcessorRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventProcessingTest extends TestCase
{
    private CacheService $cacheService;
    private EventProcessorRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->cacheService = new CacheService();
        $this->registry = new EventProcessorRegistry();
        $this->registry->register(new EmployeeCreatedProcessor($this->cacheService));
        $this->registry->register(new EmployeeUpdatedProcessor($this->cacheService));
        $this->registry->register(new EmployeeDeletedProcessor($this->cacheService));
    }

    public function test_employee_created_event_caches_employee(): void
    {
        $event = [
            'event_type' => 'EmployeeCreated',
            'event_id' => 'test-uuid',
            'timestamp' => now()->toIso8601String(),
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => [
                    'id' => 1,
                    'name' => 'John',
                    'last_name' => 'Doe',
                    'salary' => 75000,
                    'ssn' => '123-45-6789',
                    'address' => '123 Main St',
                    'country' => 'USA',
                ],
            ],
        ];

        $this->registry->process($event);

        // Verify employee was cached
        $cached = $this->cacheService->getEmployee('USA', 1);
        $this->assertNotNull($cached);
        $this->assertEquals('John', $cached['name']);

        // Verify broadcasts were dispatched
        Event::assertDispatched(EmployeeListUpdated::class, fn ($e) => $e->country === 'USA');
        Event::assertDispatched(ChecklistUpdated::class, fn ($e) => $e->country === 'USA');
    }

    public function test_employee_updated_event_updates_cache_and_broadcasts(): void
    {
        // Pre-populate cache
        $this->cacheService->setEmployee('USA', 1, ['id' => 1, 'name' => 'John', 'salary' => 50000]);

        $event = [
            'event_type' => 'EmployeeUpdated',
            'event_id' => 'test-uuid',
            'timestamp' => now()->toIso8601String(),
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'changed_fields' => ['salary'],
                'employee' => [
                    'id' => 1,
                    'name' => 'John',
                    'last_name' => 'Doe',
                    'salary' => 80000,
                    'country' => 'USA',
                ],
            ],
        ];

        $this->registry->process($event);

        // Verify cache was updated
        $cached = $this->cacheService->getEmployee('USA', 1);
        $this->assertEquals(80000, $cached['salary']);

        Event::assertDispatched(EmployeeListUpdated::class);
        Event::assertDispatched(ChecklistUpdated::class);
        Event::assertDispatched(EmployeeUpdatedBroadcast::class, function ($e) {
            return $e->employeeId === 1 && in_array('salary', $e->changedFields);
        });
    }

    public function test_employee_deleted_event_removes_from_cache(): void
    {
        // Pre-populate cache
        $this->cacheService->setEmployee('USA', 1, ['id' => 1, 'name' => 'John']);

        $event = [
            'event_type' => 'EmployeeDeleted',
            'event_id' => 'test-uuid',
            'timestamp' => now()->toIso8601String(),
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => ['id' => 1, 'name' => 'John', 'country' => 'USA'],
            ],
        ];

        $this->registry->process($event);

        // Verify employee was removed from cache
        $cached = $this->cacheService->getEmployee('USA', 1);
        $this->assertNull($cached);

        Event::assertDispatched(EmployeeListUpdated::class);
        Event::assertDispatched(ChecklistUpdated::class);
    }

    public function test_checklist_cache_is_invalidated_on_employee_change(): void
    {
        // Pre-populate checklist cache
        $this->cacheService->setChecklist('USA', ['some' => 'data']);
        $this->assertNotNull($this->cacheService->getChecklist('USA'));

        $event = [
            'event_type' => 'EmployeeCreated',
            'event_id' => 'test-uuid',
            'timestamp' => now()->toIso8601String(),
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => ['id' => 1, 'name' => 'John', 'country' => 'USA'],
            ],
        ];

        $this->registry->process($event);

        // Checklist cache should be cleared
        $this->assertNull($this->cacheService->getChecklist('USA'));
    }
}
