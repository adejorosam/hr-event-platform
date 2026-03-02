<?php

namespace Tests\Feature;

use App\Services\HrServiceClient;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ChecklistApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache to prevent cross-test contamination
        Cache::flush();

        // Mock HrServiceClient to avoid dependency on HR Service
        $mock = Mockery::mock(HrServiceClient::class);
        $mock->shouldReceive('getAllEmployeesByCountry')
            ->with('USA')
            ->andReturn([
                [
                    'id' => 1,
                    'name' => 'John',
                    'last_name' => 'Doe',
                    'salary' => 75000,
                    'ssn' => '123-45-6789',
                    'address' => '123 Main St, New York, NY',
                    'country' => 'USA',
                ],
                [
                    'id' => 2,
                    'name' => 'Jane',
                    'last_name' => 'Smith',
                    'salary' => 0,
                    'ssn' => null,
                    'address' => '',
                    'country' => 'USA',
                ],
            ]);

        $mock->shouldReceive('getAllEmployeesByCountry')
            ->with('Germany')
            ->andReturn([
                [
                    'id' => 3,
                    'name' => 'Hans',
                    'last_name' => 'Mueller',
                    'salary' => 65000,
                    'goal' => 'Increase team productivity',
                    'tax_id' => 'DE123456789',
                    'country' => 'Germany',
                ],
            ]);

        $this->app->instance(HrServiceClient::class, $mock);
    }

    public function test_returns_usa_checklist_data(): void
    {
        $response = $this->getJson('/api/checklists?country=USA');

        $response->assertStatus(200)
            ->assertJsonPath('country', 'USA')
            ->assertJsonPath('total_employees', 2)
            ->assertJsonCount(2, 'employees');
    }

    public function test_usa_complete_employee_has_100_percent(): void
    {
        $response = $this->getJson('/api/checklists?country=USA');

        $employees = $response->json('employees');
        $john = collect($employees)->firstWhere('employee_id', 1);

        $this->assertEquals(100, $john['completion_percentage']);
    }

    public function test_usa_incomplete_employee_has_low_percentage(): void
    {
        $response = $this->getJson('/api/checklists?country=USA');

        $employees = $response->json('employees');
        $jane = collect($employees)->firstWhere('employee_id', 2);

        $this->assertLessThan(100, $jane['completion_percentage']);
    }

    public function test_checklist_includes_item_details(): void
    {
        $response = $this->getJson('/api/checklists?country=USA');

        $employees = $response->json('employees');
        $firstEmployee = $employees[0];

        $this->assertArrayHasKey('items', $firstEmployee);
        foreach ($firstEmployee['items'] as $item) {
            $this->assertArrayHasKey('field', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('complete', $item);
            $this->assertArrayHasKey('message', $item);
        }
    }

    public function test_returns_germany_checklist_data(): void
    {
        $response = $this->getJson('/api/checklists?country=Germany');

        $response->assertStatus(200)
            ->assertJsonPath('country', 'Germany')
            ->assertJsonPath('total_employees', 1)
            ->assertJsonPath('fully_complete', 1)
            ->assertJsonPath('completion_rate', 100);
    }

    public function test_requires_country_parameter(): void
    {
        $response = $this->getJson('/api/checklists');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    public function test_includes_completion_rate(): void
    {
        $response = $this->getJson('/api/checklists?country=USA');

        $response->assertStatus(200);
        $this->assertArrayHasKey('completion_rate', $response->json());
        $this->assertArrayHasKey('fully_complete', $response->json());
    }
}
